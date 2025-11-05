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
    
    
    public function get_meetings_by_date($date);
    
    
    public function get_meetings_by_date_range($from_date, $to_date);
    
    
    public function get_meeting_participants($meeting_id);
    
    
    public function get_recording_metadata($meeting_id);
    
    
    public function delete_recordings($recordings);
    
    
    public function get_meeting_info($meeting_id);
}