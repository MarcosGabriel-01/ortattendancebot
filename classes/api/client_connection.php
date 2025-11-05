<?php
/**
 * Client connection factory - Determines which video conferencing client to use
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\api;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/client_interface.php');
require_once(__DIR__ . '/zoom_client.php');
require_once(__DIR__ . '/meet_client.php');
require_once(__DIR__ . '/mock_client.php');

class client_connection {
    
    const PROVIDER_ZOOM = 'zoom';
    const PROVIDER_MEET = 'meet';
    const PROVIDER_MOCK = 'mock';
    
    /**
     * Get the appropriate client based on configuration
     * 
     * @return client_interface
     * @throws \Exception
     */
    public static function get_client() {
    $provider = get_config('mod_ortattendancebot', 'video_provider') ?: self::PROVIDER_ZOOM;

        switch ($provider) {
            case self::PROVIDER_ZOOM:
                mtrace("Using Zoom API Client");
                return new zoom_client();

            case self::PROVIDER_MEET:
                mtrace("Using Google Meet API Client");
                return new meet_client();

            case self::PROVIDER_MOCK:
                mtrace("Using Mock API Client");
                return new mock_client();

            default:
                throw new \Exception("Unknown video provider: $provider");
        }
    }


    /**
     * Get client by provider type
     * 
     * @param string $provider Provider constant (PROVIDER_ZOOM, PROVIDER_MEET, PROVIDER_MOCK)
     * @return client_interface
     */
    public static function get_client_by_provider($provider) {
        switch ($provider) {
            case self::PROVIDER_ZOOM:
                return new zoom_client();
                
            case self::PROVIDER_MEET:
                return new meet_client();
                
            case self::PROVIDER_MOCK:
                return new mock_client();
                
            default:
                throw new \Exception("Unknown provider: $provider");
        }
    }
    
    /**
     * Force Zoom client
     */
    public static function get_zoom_client() {
        return new zoom_client();
    }
    
    /**
     * Force Google Meet client
     */
    public static function get_meet_client() {
        return new meet_client();
    }
    
    /**
     * Force mock client
     */
    public static function get_mock_client() {
        return new mock_client();
    }
}