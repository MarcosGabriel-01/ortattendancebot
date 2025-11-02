<?php
/**
 * Meeting handler - processes meeting fetch actions
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\handlers;

defined('MOODLE_INTERNAL') || die();

class meeting_handler {
    
    private $attendancebot;
    private $course;
    private $meeting_service;
    private $queue_service;
    
    public function __construct($attendancebot, $course) {
        global $CFG;
        
        $this->attendancebot = $attendancebot;
        $this->course = $course;
        
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/services/meeting_service.php');
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/services/queue_service.php');
        
        $this->meeting_service = new \mod_ortattendancebot\services\meeting_service();
        $this->queue_service = new \mod_ortattendancebot\services\queue_service();
    }
    
    /**
     * Fetch all meetings in configured date range
     * 
     * @return array Result data for template
     */
    public function fetch_retroactive() {
        $from_date = date('Y-m-d', $this->attendancebot->start_date);
        $to_date = date('Y-m-d', min($this->attendancebot->end_date, time()));
        
        // Fetch from API
        $fetch_result = $this->meeting_service->fetch_meetings($from_date, $to_date);
        
        // Filter by time window
        $filter_result = $this->meeting_service->filter_by_time(
            $fetch_result['meetings'],
            $this->attendancebot->start_time,
            $this->attendancebot->end_time
        );
        
        // Add to queue
        $queue_result = $this->queue_service->add_meetings(
            $this->attendancebot->id,
            $filter_result['valid']
        );
        
        return [
            'success' => true,
            'action' => 'retroactive_fetch',
            'from_date' => $from_date,
            'to_date' => $to_date,
            'total_meetings' => $fetch_result['count'],
            'meetings' => $fetch_result['meetings'],
            'filtered_out' => $filter_result['filtered'],
            'queued' => $queue_result['queued'],
            'skipped' => $queue_result['skipped']
        ];
    }
    
    /**
     * Fetch yesterday's meetings
     * 
     * @return array Result data
     */
    public function fetch_yesterday() {
        $yesterday = strtotime('yesterday');
        $date = date('Y-m-d', $yesterday);
        
        // Fetch from API
        $fetch_result = $this->meeting_service->fetch_meetings($date, $date);
        
        // Filter by time window
        $filter_result = $this->meeting_service->filter_by_time(
            $fetch_result['meetings'],
            $this->attendancebot->start_time,
            $this->attendancebot->end_time
        );
        
        // Add to queue
        $queue_result = $this->queue_service->add_meetings(
            $this->attendancebot->id,
            $filter_result['valid']
        );
        
        return [
            'success' => true,
            'action' => 'queue_yesterday',
            'date' => $date,
            'total_meetings' => $fetch_result['count'],
            'meetings' => $fetch_result['meetings'],
            'filtered_out' => $filter_result['filtered'],
            'queued' => $queue_result['queued'],
            'skipped' => $queue_result['skipped']
        ];
    }
}
