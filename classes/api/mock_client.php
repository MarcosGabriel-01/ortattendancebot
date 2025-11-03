<?php
/**
 * Mock API client - For testing and development
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\api;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/client_interface.php');

class mock_client implements client_interface {
    
    private $base_url;
    
    public function __construct() {
        $this->base_url = get_config('mod_ortattendancebot', 'mock_api_url') ?: 'http://localhost:5000';
        mtrace("Mock API URL: {$this->base_url}");
    }
    
    /**
     * Get meetings for a specific date
     */
    public function get_meetings_by_date($date) {
        $url = "{$this->base_url}/report/meetings?from={$date}&to={$date}";
        $response = $this->make_request($url);
        return $response['meetings'] ?? [];
    }
    
    /**
     * Get meetings for a date range
     */
    public function get_meetings_by_date_range($from_date, $to_date) {
        $url = "{$this->base_url}/report/meetings?from={$from_date}&to={$to_date}";
        $response = $this->make_request($url);
        return $response['meetings'] ?? [];
    }
    
    /**
     * Get participants for a specific meeting
     */
    public function get_meeting_participants($meeting_id) {
        $url = "{$this->base_url}/metrics/meetings/{$meeting_id}/participants";
        $response = $this->make_request($url);
        return $response['participants'] ?? [];
    }
    
    /**
     * Get recording metadata
     */
    public function get_recording_metadata($meeting_id) {
        $url = "{$this->base_url}/meetings/{$meeting_id}/recordings";
        
        try {
            $response = $this->make_request($url);
            return $response['recording_files'] ?? [];
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'HTTP error 404') !== false) {
                throw new \Exception('Recording not ready yet', 404);
            }
            throw $e;
        }
    }
    
    /**
     * Delete a recording
     */
    public function delete_recording($meeting_id, $recording_id) {
        $url = "{$this->base_url}/meetings/{$meeting_id}/recordings/{$recording_id}";
        $this->make_request($url, 'DELETE');
    }
    
    /**
     * Get meeting information
     */
    public function get_meeting_info($meeting_id) {
        $url = "{$this->base_url}/meetings/{$meeting_id}";
        return $this->make_request($url);
    }
    
    /**
     * Make HTTP request to mock API
     */
    private function make_request($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        $headers = ['Content-Type: application/json'];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        if ($data && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("Mock API cURL error: $error");
        }
        
        if ($http_code >= 400) {
            throw new \Exception("Mock API HTTP error $http_code: $response");
        }
        
        // DELETE requests return 204 No Content
        if ($method === 'DELETE' && $http_code === 204) {
            return [];
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Mock API JSON decode error: " . json_last_error_msg());
        }
        
        return $decoded;
    }
}