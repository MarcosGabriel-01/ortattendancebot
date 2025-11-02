<?php
/**
 * Cleanup handler - clears queue and attendance data
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\handlers;

defined('MOODLE_INTERNAL') || die();

class cleanup_handler {
    
    private $attendancebot;
    private $course;
    
    public function __construct($attendancebot, $course) {
        $this->attendancebot = $attendancebot;
        $this->course = $course;
    }
    
    /**
     * Clear queue
     * 
     * @return array Result data
     */
    public function clear_queue() {
        global $CFG;
        
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/services/queue_service.php');
        $queue_service = new \mod_ortattendancebot\services\queue_service();
        
        $count = $queue_service->clear_all($this->attendancebot->id);
        
        return [
            'success' => true,
            'action' => 'clear_queue',
            'deleted' => $count
        ];
    }
    
    /**
     * Clear attendance sessions
     * 
     * @return array Result data
     */
    public function clear_attendance() {
        global $DB;
        
        // Get attendance instance
        $modules = $DB->get_record('modules', ['name' => 'attendance']);
        
        if (!$modules) {
            return [
                'success' => false,
                'action' => 'clear_attendance',
                'message' => 'Attendance module not installed'
            ];
        }
        
        $cm_attendance = $DB->get_record('course_modules', [
            'course' => $this->course->id,
            'module' => $modules->id
        ]);
        
        if (!$cm_attendance) {
            return [
                'success' => false,
                'action' => 'clear_attendance',
                'message' => 'No attendance module in this course'
            ];
        }
        
        $attendance_id = $cm_attendance->instance;
        
        // Get bot-created sessions
        $sessions = $DB->get_records_select('attendance_sessions', 
            "attendanceid = ? AND description LIKE ?", 
            [$attendance_id, '%AttendanceBot%']
        );
        
        $log_count = 0;
        foreach ($sessions as $session) {
            $log_count += $DB->count_records('attendance_log', ['sessionid' => $session->id]);
            $DB->delete_records('attendance_log', ['sessionid' => $session->id]);
        }
        
        $session_count = $DB->delete_records_select('attendance_sessions',
            "attendanceid = ? AND description LIKE ?",
            [$attendance_id, '%AttendanceBot%']
        );
        
        return [
            'success' => true,
            'action' => 'clear_attendance',
            'sessions_deleted' => $session_count,
            'logs_deleted' => $log_count
        ];
    }
}
