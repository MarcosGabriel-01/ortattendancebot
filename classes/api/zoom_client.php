<?php
/**
 * Zoom API client - Reuses credentials from mod_zoom if available
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\api;

defined('MOODLE_INTERNAL') || die();

class zoom_client {
    
    private $base_url;
    private $token;
    private $use_mock;
    private $host_email;
    
    public function __construct() {
        $this->use_mock = get_config('mod_ortattendancebot', 'use_mock_api');
        
        if ($this->use_mock) {
            $this->base_url = get_config('mod_ortattendancebot', 'mock_api_url') ?: 'http://localhost:5000';
        } else {
            $this->base_url = 'https://api.zoom.us/v2';
            
            // Try to get credentials from mod_zoom or zoommtg first
            $zoom_config = $this->get_zoom_credentials();
            
            if ($zoom_config) {
                $this->token = $zoom_config['token'];
                $this->host_email = $zoom_config['email'];
                mtrace("Using Zoom credentials from: {$zoom_config['source']}");
            } else {
                // Fall back to ortattendancebot's own config
                $this->token = get_config('mod_ortattendancebot', 'zoom_oauth_token');
                $this->host_email = get_config('mod_ortattendancebot', 'zoom_host_email');
                mtrace("Using Zoom credentials from: ortattendancebot settings");
            }
            
            if (!$this->token) {
                throw new \Exception('Zoom OAuth token not found. Install mod_zoom or configure ortattendancebot settings.');
            }
            if (!$this->host_email) {
                throw new \Exception('Zoom host email not found. Install mod_zoom or configure ortattendancebot settings.');
            }
        }
    }
    
    /**
     * Get Zoom credentials from various sources
     * Priority: mod_zoom > zoommtg > auth_zoom > local_zoom
     * 
     * @return array|null ['token' => string, 'email' => string, 'source' => string]
     */
    private function get_zoom_credentials() {
        // Check mod_zoom (most common Zoom plugin)
        $token = get_config('mod_zoom', 'apikey') ?: get_config('zoom', 'apikey');
        $email = get_config('mod_zoom', 'email') ?: get_config('zoom', 'email');
        
        if ($token && $email) {
            return ['token' => $token, 'email' => $email, 'source' => 'mod_zoom'];
        }
        
        // Check zoommtg (Zoom meeting type plugin)
        $token = get_config('zoommtg', 'apikey');
        $email = get_config('zoommtg', 'email');
        
        if ($token && $email) {
            return ['token' => $token, 'email' => $email, 'source' => 'zoommtg'];
        }
        
        // Check auth_zoom (Zoom authentication plugin)
        $token = get_config('auth_zoom', 'apikey');
        $email = get_config('auth_zoom', 'email');
        
        if ($token && $email) {
            return ['token' => $token, 'email' => $email, 'source' => 'auth_zoom'];
        }
        
        // Check local_zoom (local Zoom plugin)
        $token = get_config('local_zoom', 'apikey');
        $email = get_config('local_zoom', 'email');
        
        if ($token && $email) {
            return ['token' => $token, 'email' => $email, 'source' => 'local_zoom'];
        }
        
        return null;
    }
    
    /**
     * Get meetings for a specific date
     */
    public function get_meetings_by_date($date) {
        if ($this->use_mock) {
            $url = "{$this->base_url}/report/meetings?from={$date}&to={$date}";
        } else {
            $url = "{$this->base_url}/report/users/{$this->host_email}/meetings?from={$date}&to={$date}";
        }
        
        $response = $this->make_request($url);
        return $response['meetings'] ?? [];
    }
    
    /**
     * Get meetings for a date range
     */
    public function get_meetings_by_date_range($from_date, $to_date) {
        if ($this->use_mock) {
            $url = "{$this->base_url}/report/meetings?from={$from_date}&to={$to_date}";
        } else {
            $url = "{$this->base_url}/report/users/{$this->host_email}/meetings?from={$from_date}&to={$to_date}";
        }
        
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
    
    public function get_meeting_info($meeting_id) {
        $url = "{$this->base_url}/meetings/{$meeting_id}";
        return $this->make_request($url);
    }

    /**
     * Make HTTP request with retry logic
     */
    private function make_request($url, $method = 'GET', $data = null) {
        $max_retries = 3;
        $retry_delays = [60, 300, 900];
        
        for ($attempt = 0; $attempt < $max_retries; $attempt++) {
            $ch = curl_init();
            
            $headers = ['Content-Type: application/json'];
            
            if (!$this->use_mock && $this->token) {
                $headers[] = 'Authorization: Bearer ' . $this->token;
            }
            
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
                throw new \Exception("cURL error: $error");
            }
            
            if ($http_code === 429) {
                if ($attempt < $max_retries - 1) {
                    sleep($retry_delays[$attempt]);
                    continue;
                }
                throw new \Exception("Rate limit exceeded after $max_retries attempts");
            }
            
            if ($http_code >= 400) {
                throw new \Exception("HTTP error $http_code: $response");
            }
            
            if ($method === 'DELETE' && $http_code === 204) {
                return [];
            }
            
            $decoded = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON decode error: " . json_last_error_msg());
            }
            
            return $decoded;
        }
        
        throw new \Exception("Request failed after $max_retries attempts");
    }
}
