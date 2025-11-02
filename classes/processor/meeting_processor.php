<?php
/**
 * Meeting processor - handles attendance logic
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\processor;

defined('MOODLE_INTERNAL') || die();

class meeting_processor {
    
    private $config;
    private $courseid;
    private $api_client;
    
    public function __construct($config, $courseid) {
        $this->config = $config;
        $this->courseid = $courseid;
        
        require_once(__DIR__ . '/../api/zoom_client.php');
        $this->api_client = new \mod_ortattendancebot\api\zoom_client();
    }
    
    /**
     * Process a single meeting
     */
    public function process_meeting($meeting_id) {
        global $CFG, $DB;
        
        mtrace("Fetching participants for meeting: $meeting_id");
        
        // Get participants from API
        $participants = $this->api_client->get_meeting_participants($meeting_id);
        
        if (empty($participants)) {
            mtrace("No participants found");
            return;
        }
        
        mtrace("Found " . count($participants) . " participants");
        
        // Get attendance instance ID
        $attendance_id = $this->get_attendance_instance();
        
        if (!$attendance_id) {
            throw new \Exception("No attendance module found in course");
        }
        
        // Get first participant to determine meeting time
        $first = reset($participants);
        $meeting_start = strtotime($first['join_time']);
        $meeting_end = strtotime($first['leave_time']);
        
        foreach ($participants as $p) {
            $join = strtotime($p['join_time']);
            $leave = strtotime($p['leave_time']);
            if ($join < $meeting_start) $meeting_start = $join;
            if ($leave > $meeting_end) $meeting_end = $leave;
        }
        
        $meeting_duration = $meeting_end - $meeting_start;
        mtrace("Meeting duration: " . round($meeting_duration / 60) . " minutes");
        
        // Create or find session
        $session_id = $this->get_or_create_session($attendance_id, $meeting_start, $meeting_duration);
        
        if (!$session_id) {
            throw new \Exception("Failed to create/find attendance session");
        }
        
        mtrace("Using session ID: $session_id");
        
        // Get status IDs
        $statuses = $this->get_statuses($attendance_id);
        
        // Process each participant
        foreach ($participants as $participant) {
            try {
                $this->process_participant($participant, $session_id, $meeting_start, $meeting_duration, $statuses);
            } catch (\Exception $e) {
                mtrace("  Error processing participant: " . $e->getMessage());
                continue;
            }
        }
        
        // Mark session as taken
        $DB->set_field('attendance_sessions', 'lasttaken', time(), ['id' => $session_id]);
        $DB->set_field('attendance_sessions', 'lasttakenby', 2, ['id' => $session_id]);
        mtrace("✓ Session marked as taken");
        
        // Mark absent students
        $this->mark_absent_students($participants, $session_id, $statuses);
        mtrace("✓ Marked non-attendees as absent");
        
        // Add to backup queue if enabled
        if ($this->config->backup_recordings) {
            $this->add_to_backup_queue($meeting_id, $meeting_start);
            mtrace("✓ Added to backup queue");
        }
    }
    
    /**
     * Mark absent students who didn't attend
     */
    private function mark_absent_students($participants, $session_id, $statuses) {
        global $DB;
        
        $enrolled = $DB->get_records_sql(
            "SELECT u.id 
             FROM {user} u
             JOIN {user_enrolments} ue ON ue.userid = u.id
             JOIN {enrol} e ON e.id = ue.enrolid
             WHERE e.courseid = ? AND ue.status = 0",
            [$this->courseid]
        );
        
        $attended_ids = [];
        foreach ($participants as $p) {
            $email = $p['user_email'] ?? $p['email'] ?? null;
            if ($email) {
                $u = $DB->get_record('user', ['email' => $email], 'id');
                if ($u) $attended_ids[] = $u->id;
            }
        }
        
        foreach ($enrolled as $enrolled_user) {
            if (!in_array($enrolled_user->id, $attended_ids)) {
                if (!$DB->record_exists('attendance_log', [
                    'sessionid' => $session_id,
                    'studentid' => $enrolled_user->id
                ])) {
                    $this->save_attendance($enrolled_user->id, $session_id, $statuses['absent']);
                }
            }
        }
    }
    
    /**
     * Add meeting to backup queue with topic
     */
    private function add_to_backup_queue($meeting_id, $meeting_start) {
        global $DB;
        
        // Get meeting topic
        try {
            $meeting_info = $this->api_client->get_meeting_info($meeting_id);
            $topic = $meeting_info['topic'] ?? '';
        } catch (\Exception $e) {
            mtrace("  Warning: Could not fetch meeting topic: " . $e->getMessage());
            $topic = '';
        }
        
        $backup = new \stdClass();
        $backup->attendancebotid = $this->config->id;
        $backup->meeting_id = $meeting_id;
        $backup->meeting_name = $topic;
        $backup->timecreated = $meeting_start;
        $backup->processed = 0;
        
        $DB->insert_record('ortattendancebot_backup_queue', $backup);
    }
    
    /**
     * Process individual participant
     */
    private function process_participant($participant, $session_id, $meeting_start, $meeting_duration, $statuses) {
        global $DB;
        
        $email = $participant['user_email'] ?? $participant['email'] ?? null;
        
        if (!$email) {
            mtrace("  Skipping participant without email");
            return;
        }
        
        // Match by email
        $user = $DB->get_record('user', ['email' => $email], 'id,firstname,lastname');
        
        if (!$user) {
            mtrace("  No Moodle user found for: $email");
            return;
        }
        
        mtrace("  Processing: {$user->firstname} {$user->lastname} ($email)");
        
        // Calculate attendance
        $join_time = strtotime($participant['join_time']);
        $leave_time = strtotime($participant['leave_time']);
        $attended_seconds = $leave_time - $join_time;
        $attendance_percent = ($attended_seconds / $meeting_duration) * 100;
        
        mtrace("    Attended: " . round($attendance_percent) . "%");
        
        // Check camera
        $camera_on = $participant['camera_on'] ?? $participant['has_video'] ?? false;
        mtrace("    Camera: " . ($camera_on ? 'ON' : 'OFF'));
        
        // Determine status
        $status_id = $this->determine_status($user->id, $attendance_percent, $camera_on, $join_time, $meeting_start, $statuses);
        
        // Save attendance
        $this->save_attendance($user->id, $session_id, $status_id);
        
        mtrace("    Status: " . $this->get_status_name($status_id, $statuses));
    }
    
    /**
     * Determine attendance status
     */
    private function determine_status($userid, $attendance_percent, $camera_on, $join_time, $meeting_start, $statuses) {
        if ($attendance_percent < $this->config->min_percentage) {
            return $statuses['absent'];
        }
        
        if ($this->config->camera_required && !$camera_on) {
            return $statuses['absent'];
        }
        
        $late_seconds = $this->config->late_tolerance * 60;
        if ($join_time > ($meeting_start + $late_seconds)) {
            return $statuses['late'];
        }
        
        return $statuses['present'];
    }
    
    /**
     * Save attendance log
     */
    private function save_attendance($userid, $session_id, $status_id) {
        global $DB;
        
        $session = $DB->get_record('attendance_sessions', ['id' => $session_id], 'statusset', MUST_EXIST);
        
        $existing = $DB->get_record('attendance_log', [
            'sessionid' => $session_id,
            'studentid' => $userid
        ]);
        
        if ($existing) {
            $existing->statusid = $status_id;
            $existing->statusset = $session->statusset;
            $existing->timetaken = time();
            $existing->takenby = 2;
            $DB->update_record('attendance_log', $existing);
            mtrace("    ✓ Updated existing log");
        } else {
            $log = new \stdClass();
            $log->sessionid = $session_id;
            $log->studentid = $userid;
            $log->statusid = $status_id;
            $log->statusset = $session->statusset;
            $log->timetaken = time();
            $log->takenby = 2;
            $log->remarks = '';
            
            $DB->insert_record('attendance_log', $log);
            mtrace("    ✓ Created new log");
        }
    }
    
    /**
     * Get or create attendance session
     */
    private function get_or_create_session($attendance_id, $start_time, $duration) {
        global $DB;
        
        $tolerance = 1800;
        $existing = $DB->get_record_sql(
            "SELECT id FROM {attendance_sessions} 
             WHERE attendanceid = ? AND sessdate >= ? AND sessdate <= ?
             ORDER BY sessdate DESC LIMIT 1",
            [$attendance_id, $start_time - $tolerance, $start_time + $tolerance]
        );
        
        if ($existing) {
            return $existing->id;
        }
        
        $statusset = $DB->get_field_sql(
            "SELECT MIN(setnumber) FROM {attendance_statuses} WHERE attendanceid = ?",
            [$attendance_id]
        );
        
        if ($statusset === false) {
            $statusset = 0;
        }
        
        $session = new \stdClass();
        $session->attendanceid = $attendance_id;
        $session->groupid = 0;
        $session->sessdate = $start_time;
        $session->duration = $duration;
        $session->lasttaken = null;
        $session->lasttakenby = 0;
        $session->timemodified = time();
        $session->description = 'Auto-recorded by AttendanceBot';
        $session->descriptionformat = FORMAT_HTML;
        $session->studentscanmark = 0;
        $session->autoassignstatus = 0;
        $session->subnet = '';
        $session->automarkcompleted = 0;
        $session->statusset = $statusset;
        $session->includeqrcode = 0;
        $session->studentpassword = '';
        $session->calendarevent = 0;
        
        return $DB->insert_record('attendance_sessions', $session);
    }
    
    /**
     * Get attendance module instance ID
     */
    private function get_attendance_instance() {
        global $DB;
        require_once(__DIR__ . '/../../lib.php');
        return ortattendancebot_get_module_instance('attendance', $this->courseid);
    }
    
    /**
     * Get status IDs for attendance
     */
    private function get_statuses($attendance_id) {
        global $DB;
        
        $records = $DB->get_records('attendance_statuses', ['attendanceid' => $attendance_id]);
        
        $statuses = [];
        foreach ($records as $record) {
            $acronym = strtolower($record->acronym);
            if ($acronym == 'p' || $acronym == 'present') {
                $statuses['present'] = $record->id;
            } elseif ($acronym == 'l' || $acronym == 'late') {
                $statuses['late'] = $record->id;
            } elseif ($acronym == 'a' || $acronym == 'absent') {
                $statuses['absent'] = $record->id;
            }
        }
        
        if (empty($statuses['present']) || empty($statuses['absent'])) {
            throw new \Exception("Required attendance statuses not found");
        }
        
        if (empty($statuses['late'])) {
            $statuses['late'] = $statuses['present'];
        }
        
        return $statuses;
    }
    
    /**
     * Get status name for logging
     */
    private function get_status_name($status_id, $statuses) {
        foreach ($statuses as $name => $id) {
            if ($id == $status_id) {
                return ucfirst($name);
            }
        }
        return 'Unknown';
    }
}
