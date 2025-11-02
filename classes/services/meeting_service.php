<?php
/**
 * Meeting service - handles API calls and meeting operations
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\services;

defined('MOODLE_INTERNAL') || die();

class meeting_service {
    
    private $api_client;
    
    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/api/zoom_client.php');
        $this->api_client = new \mod_ortattendancebot\api\zoom_client();
    }
    
    /**
     * Fetch meetings for a date range
     * 
     * @param string $from_date Y-m-d format
     * @param string $to_date Y-m-d format
     * @return array ['meetings' => array, 'count' => int, 'api_url' => string]
     */
    public function fetch_meetings($from_date, $to_date) {
        $meetings = $this->api_client->get_meetings_by_date_range($from_date, $to_date);
        
        return [
            'meetings' => $meetings,
            'count' => count($meetings),
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
    }
    
    /**
     * Filter meetings by time window
     * 
     * @param array $meetings
     * @param int $start_time Seconds from midnight
     * @param int $end_time Seconds from midnight
     * @return array ['valid' => array, 'filtered' => int]
     */
    public function filter_by_time($meetings, $start_time, $end_time) {
        $valid = [];
        $filtered = 0;
        
        foreach ($meetings as $meeting) {
            $meeting_time = strtotime($meeting['start_time']);
            $meeting_seconds = $meeting_time % 86400;
            
            if ($meeting_seconds >= $start_time && $meeting_seconds <= $end_time) {
                $valid[] = $meeting;
            } else {
                $filtered++;
            }
        }
        
        return [
            'valid' => $valid,
            'filtered' => $filtered
        ];
    }
}
