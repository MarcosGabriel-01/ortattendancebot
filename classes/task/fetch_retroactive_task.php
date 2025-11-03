<?php
namespace mod_ortattendancebot\task;

defined('MOODLE_INTERNAL') || die();

class fetch_retroactive_task extends \core\task\adhoc_task {
    
    public function execute() {
        global $DB;
        
        $data = $this->get_custom_data();
        
        try {
            // Fetch fresh records from database
            $attendancebot = $DB->get_record('ortattendancebot', ['id' => $data->attendancebotid], '*', MUST_EXIST);
            $course = $DB->get_record('course', ['id' => $data->courseid], '*', MUST_EXIST);
            
            require_once(__DIR__ . '/../../classes/handlers/meeting_handler.php');
            
            $handler = new \mod_ortattendancebot\handlers\meeting_handler($attendancebot, $course);
            $result = $handler->fetch_retroactive();
            
            mtrace('Retroactive fetch completed for course: ' . $course->id);
            
        } catch (\Exception $e) {
            mtrace('Error in retroactive fetch: ' . $e->getMessage());
            throw $e; // Re-throw to mark task as failed
        }
    }
}