<?php
/**
 * Scheduled task that runs at 1 AM to queue meetings
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\task;

defined('MOODLE_INTERNAL') || die();

class scheduler_task extends \core\task\scheduled_task {
    
    public function get_name() {
        return get_string('scheduler_task', 'mod_ortattendancebot');
    }
    
    public function execute() {
        global $CFG, $DB;
        
        
        require_once($CFG->dirroot . '/mod/ortattendancebot/lib.php');
        
        mtrace('=== Attendance Bot Scheduler Started ===');
        
        
        $installations = $this->get_active_installations();
        mtrace('Found ' . count($installations) . ' active installations');
        
        foreach ($installations as $installation) {
            mtrace("Processing installation: {$installation->name} (Course: {$installation->course})");
            
            try {
                $this->queue_meetings($installation);
                $this->schedule_processor($installation);
            } catch (\Exception $e) {
                mtrace('ERROR: ' . $e->getMessage());
                continue;
            }
        }
        
        mtrace('=== Attendance Bot Scheduler Completed ===');
    }
    
    
    private function get_active_installations() {
        global $DB;
        
        $now = time();
        
        $sql = "SELECT ab.* 
                FROM {ortattendancebot} ab
                JOIN {course_modules} cm ON cm.instance = ab.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'ortattendancebot'
                WHERE ab.enabled = 1 
                AND ab.start_date <= :now1 
                AND ab.end_date >= :now2
                AND cm.deletioninprogress = 0";
        
        return $DB->get_records_sql($sql, ['now1' => $now, 'now2' => $now]);
    }
    
    
    private function queue_meetings($installation, $retroactive = false) {
        global $CFG, $DB;
        
        
        if ($retroactive) {
            
            $from_date = date('Y-m-d', $installation->start_date);
            $to_date = date('Y-m-d', min($installation->end_date, time())); 
            mtrace("  RETROACTIVE MODE: Fetching meetings from $from_date to $to_date");
        } else {
            
            $yesterday = strtotime('yesterday');
            $from_date = date('Y-m-d', $yesterday);
            $to_date = $from_date;
            mtrace("  Fetching meetings for date: $from_date");
        }
        
        
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/api/client_connection.php');
        $client = \mod_ortattendancebot\api\client_connection::get_client();
        
        
        $meetings = $client->get_meetings_by_date_range($from_date, $to_date);
        mtrace("  Found " . count($meetings) . " meetings in date range");
        
        $queued_count = 0;
        $skipped_count = 0;
        
        foreach ($meetings as $meeting) {
            
            $meeting_time = strtotime($meeting['start_time']);
            $meeting_seconds = $meeting_time % 86400; 
            
            if ($meeting_seconds >= $installation->start_time && 
                $meeting_seconds <= $installation->end_time) {
                
                
                $exists = $DB->record_exists('ortattendancebot_queue', [
                    'attendancebotid' => $installation->id,
                    'meeting_id' => $meeting['id']
                ]);
                
                if (!$exists) {
                    $DB->insert_record('ortattendancebot_queue', [
                        'attendancebotid' => $installation->id,
                        'meeting_id' => $meeting['id'],
                        'meeting_date' => $meeting_time,
                        'processed' => 0,
                        'timecreated' => time()
                    ]);
                    mtrace("    Queued meeting: {$meeting['id']} (" . date('Y-m-d H:i', $meeting_time) . ")");
                    $queued_count++;
                } else {
                    $skipped_count++;
                }
            }
        }
        
        mtrace("  Summary: Queued $queued_count new meetings, skipped $skipped_count already queued");
    }
    
    
    private function schedule_processor($installation) {
        $task = new \mod_ortattendancebot\task\meeting_processor_task();
        $task->set_custom_data([
            'attendancebotid' => $installation->id,
            'courseid' => $installation->course
        ]);
        \core\task\manager::queue_adhoc_task($task);
        mtrace("  Scheduled processor task");
    }
}