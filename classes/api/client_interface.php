<?php
/**
 * Video conferencing client interface - Common contract for all clients
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\api;

defined('MOODLE_INTERNAL') || die();

interface client_interface {
    
    /**
     * Get meetings for a specific date
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @return array Array of meeting objects
     */
    public function get_meetings_by_date($date);
    
    /**
     * Get meetings for a date range
     * 
     * @param string $from_date Start date in YYYY-MM-DD format
     * @param string $to_date End date in YYYY-MM-DD format
     * @return array Array of meeting objects
     */
    public function get_meetings_by_date_range($from_date, $to_date);
    
    /**
     * Get participants for a specific meeting
     * 
     * @param string $meeting_id Meeting ID
     * @return array Array of participant objects
     */
    public function get_meeting_participants($meeting_id);
    
    /**
     * Get recording metadata
     * 
     * @param string $meeting_id Meeting ID
     * @return array Array of recording file objects
     * @throws \Exception If recording not found or not ready
     */
    public function get_recording_metadata($meeting_id);
    
    /**
     * Delete recordings in batch
     * 
     * @param array $recordings Array of [{meeting_id, recording_id}, ...]
     * @return array Results [{meeting_id, recording_id, success, error}, ...]
     */
    public function delete_recordings($recordings);
    
    /**
     * Get meeting information
     * 
     * @param string $meeting_id Meeting ID
     * @return array Meeting information
     */
    public function get_meeting_info($meeting_id);
}