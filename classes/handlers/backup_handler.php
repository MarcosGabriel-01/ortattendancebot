<?php
/**
 * Backup handler - processes recording backup queue
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\handlers;

defined('MOODLE_INTERNAL') || die();

class backup_handler {
    
    private $attendancebot;
    private $course;
    
    public function __construct($attendancebot, $course) {
        $this->attendancebot = $attendancebot;
        $this->course = $course;
    }
    
    /**
     * Process backup queue
     * 
     * @return array Result data
     */
    public function process() {
        global $CFG;
        
        if (!$this->attendancebot->backup_recordings) {
            return [
                'success' => false,
                'action' => 'process_backup',
                'message' => 'Backup not enabled'
            ];
        }
        
        // Capture output
        ob_start();
        
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/task/meeting_processor_task.php');
        $task = new \mod_ortattendancebot\task\meeting_processor_task();
        $task->set_custom_data([
            'attendancebotid' => $this->attendancebot->id,
            'courseid' => $this->course->id
        ]);
        $task->execute();
        
        $output = ob_get_clean();
        
        return [
            'success' => true,
            'action' => 'process_backup',
            'output' => $output
        ];
    }
}
