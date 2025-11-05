<?php
/**
 * Google Meet API client - Connects to Google Workspace APIs
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\api;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/client_interface.php');

class meet_client implements client_interface {
    
    private $calendar_base_url = 'https://www.googleapis.com/calendar/v3';
    private $meet_base_url = 'https://meet.googleapis.com/v2';
    private $drive_base_url = 'https://www.googleapis.com/drive/v3';
    private $token;
    private $calendar_id;
    
    public function __construct() {
        // Try to get credentials from various Google plugins
        $google_config = $this->get_google_credentials();
        
        if ($google_config) {
            $this->token = $google_config['token'];
            $this->calendar_id = $google_config['calendar_id'];
            mtrace("Using Google credentials from: {$google_config['source']}");
        } else {
            // Fall back to ortattendancebot's own config
            $this->token = get_config('mod_ortattendancebot', 'google_oauth_token');
            $this->calendar_id = get_config('mod_ortattendancebot', 'google_calendar_id') ?: 'primary';
            mtrace("Using Google credentials from: ortattendancebot settings");
        }
        
        if (!$this->token) {
            throw new \Exception('Google OAuth token not found. Configure ortattendancebot settings or install Google Workspace plugin.');
        }
    }
    
    /**
     * Get Google credentials from various sources
     * Priority: auth_googleoauth2 > local_o365 > ortattendancebot
     * 
     * @return array|null ['token' => string, 'calendar_id' => string, 'source' => string]
     */
    private function get_google_credentials() {
        // Check auth_googleoauth2 (Google OAuth2 authentication)
        $token = get_config('auth_googleoauth2', 'oauth_token');
        $calendar_id = get_config('auth_googleoauth2', 'calendar_id') ?: 'primary';
        
        if ($token) {
            return ['token' => $token, 'calendar_id' => $calendar_id, 'source' => 'auth_googleoauth2'];
        }
        
        // Check local_o365 (Microsoft/Google integration)
        $token = get_config('local_o365', 'google_oauth_token');
        $calendar_id = get_config('local_o365', 'google_calendar_id') ?: 'primary';
        
        if ($token) {
            return ['token' => $token, 'calendar_id' => $calendar_id, 'source' => 'local_o365'];
        }
        
        return null;
    }
    
    /**
     * Get meetings for a specific date
     */
    public function get_meetings_by_date($date) {
        $time_min = $date . 'T00:00:00Z';
        $time_max = $date . 'T23:59:59Z';
        
        $url = "{$this->calendar_base_url}/calendars/{$this->calendar_id}/events?" . http_build_query([
            'timeMin' => $time_min,
            'timeMax' => $time_max,
            'singleEvents' => 'true',
            'orderBy' => 'startTime'
        ]);
        
        $response = $this->make_request($url);
        return $this->filter_meet_events($response['items'] ?? []);
    }
    
    /**
     * Get meetings for a date range
     */
    public function get_meetings_by_date_range($from_date, $to_date) {
        $time_min = $from_date . 'T00:00:00Z';
        $time_max = $to_date . 'T23:59:59Z';
        
        $url = "{$this->calendar_base_url}/calendars/{$this->calendar_id}/events?" . http_build_query([
            'timeMin' => $time_min,
            'timeMax' => $time_max,
            'singleEvents' => 'true',
            'orderBy' => 'startTime'
        ]);
        
        $response = $this->make_request($url);
        return $this->filter_meet_events($response['items'] ?? []);
    }
    
    /**
     * Filter events to only include Google Meet meetings
     */
    private function filter_meet_events($events) {
        $meetings = [];
        
        foreach ($events as $event) {
            // Check if event has Google Meet conference data
            if (isset($event['conferenceData']) && 
                isset($event['conferenceData']['conferenceId'])) {
                
                $meetings[] = [
                    'id' => $event['conferenceData']['conferenceId'],
                    'meeting_id' => $event['conferenceData']['conferenceId'],
                    'topic' => $event['summary'] ?? 'Untitled Meeting',
                    'start_time' => $event['start']['dateTime'] ?? $event['start']['date'],
                    'end_time' => $event['end']['dateTime'] ?? $event['end']['date'],
                    'duration' => $this->calculate_duration($event),
                    'event_id' => $event['id']
                ];
            }
        }
        
        return $meetings;
    }
    
    /**
     * Calculate meeting duration in minutes
     */
    private function calculate_duration($event) {
        $start = strtotime($event['start']['dateTime'] ?? $event['start']['date']);
        $end = strtotime($event['end']['dateTime'] ?? $event['end']['date']);
        return round(($end - $start) / 60);
    }
    
    /**
     * Get participants for a specific meeting
     */
    public function get_meeting_participants($meeting_id) {
        // Google Meet API v2 for conference records
        $url = "{$this->meet_base_url}/conferenceRecords/{$meeting_id}/participants";
        
        try {
            $response = $this->make_request($url);
            return $this->format_participants($response['participants'] ?? []);
        } catch (\Exception $e) {
            // Fall back to calendar event attendees if Meet API fails
            return $this->get_calendar_attendees($meeting_id);
        }
    }
    
    /**
     * Format participants to standard structure
     */
    private function format_participants($participants) {
        $formatted = [];
        
        foreach ($participants as $participant) {
            $formatted[] = [
                'user_id' => $participant['name'] ?? $participant['participantId'],
                'name' => $participant['displayName'] ?? 'Unknown',
                'user_email' => $participant['email'] ?? '',
                'join_time' => $participant['earliestStartTime'] ?? '',
                'leave_time' => $participant['latestEndTime'] ?? '',
                'duration' => $this->calculate_participant_duration($participant)
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Calculate participant duration
     */
    private function calculate_participant_duration($participant) {
        if (isset($participant['earliestStartTime']) && isset($participant['latestEndTime'])) {
            $start = strtotime($participant['earliestStartTime']);
            $end = strtotime($participant['latestEndTime']);
            return round(($end - $start) / 60);
        }
        return 0;
    }
    
    /**
     * Get attendees from calendar event (fallback)
     */
    private function get_calendar_attendees($event_id) {
        $url = "{$this->calendar_base_url}/calendars/{$this->calendar_id}/events/{$event_id}";
        
        try {
            $response = $this->make_request($url);
            $attendees = $response['attendees'] ?? [];
            
            $formatted = [];
            foreach ($attendees as $attendee) {
                if ($attendee['responseStatus'] === 'accepted') {
                    $formatted[] = [
                        'user_id' => $attendee['email'],
                        'name' => $attendee['displayName'] ?? $attendee['email'],
                        'user_email' => $attendee['email'],
                        'join_time' => '',
                        'leave_time' => '',
                        'duration' => 0
                    ];
                }
            }
            
            return $formatted;
        } catch (\Exception $e) {
            mtrace("Could not fetch calendar attendees: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recording metadata (recordings are stored in Google Drive)
     */
    public function get_recording_metadata($meeting_id) {
        // Search for recordings in Drive with meeting ID in name
        $query = "mimeType='video/mp4' and name contains '{$meeting_id}' and trashed=false";
        $url = "{$this->drive_base_url}/files?" . http_build_query([
            'q' => $query,
            'fields' => 'files(id,name,mimeType,size,createdTime,webViewLink,webContentLink)',
            'orderBy' => 'createdTime desc'
        ]);
        
        try {
            $response = $this->make_request($url);
            return $this->format_recordings($response['files'] ?? []);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'HTTP error 404') !== false) {
                throw new \Exception('Recording not ready yet', 404);
            }
            throw $e;
        }
    }
    
    /**
     * Format recordings to standard structure
     */
    private function format_recordings($files) {
        $recordings = [];
        
        foreach ($files as $file) {
            $recordings[] = [
                'id' => $file['id'],
                'recording_id' => $file['id'],
                'file_name' => $file['name'],
                'file_type' => 'MP4',
                'file_size' => $file['size'] ?? 0,
                'recording_start' => $file['createdTime'] ?? '',
                'download_url' => $file['webContentLink'] ?? '',
                'play_url' => $file['webViewLink'] ?? ''
            ];
        }
        
        return $recordings;
    }
    
    /**
     * Delete recordings from Google Drive
     */
    public function delete_recordings($recordings) {
        if (!isset($recordings[0])) {
            $recordings = [$recordings];
        }
        
        $results = [];
        foreach ($recordings as $rec) {
            try {
                $url = "{$this->drive_base_url}/files/{$rec['recording_id']}";
                $this->make_request($url, 'DELETE');
                $results[] = ['meeting_id' => $rec['meeting_id'], 'recording_id' => $rec['recording_id'], 'success' => true, 'error' => null];
            } catch (\Exception $e) {
                $results[] = ['meeting_id' => $rec['meeting_id'], 'recording_id' => $rec['recording_id'], 'success' => false, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }
    
    /**
     * Get meeting information
     */
    public function get_meeting_info($meeting_id) {
        $url = "{$this->meet_base_url}/conferenceRecords/{$meeting_id}";
        
        try {
            return $this->make_request($url);
        } catch (\Exception $e) {
            // Fall back to calendar search
            return $this->search_calendar_event($meeting_id);
        }
    }
    
    /**
     * Search for meeting in calendar events (fallback)
     */
    private function search_calendar_event($meeting_id) {
        $time_min = date('Y-m-d', strtotime('-30 days')) . 'T00:00:00Z';
        $time_max = date('Y-m-d', strtotime('+1 day')) . 'T23:59:59Z';
        
        $url = "{$this->calendar_base_url}/calendars/{$this->calendar_id}/events?" . http_build_query([
            'timeMin' => $time_min,
            'timeMax' => $time_max,
            'singleEvents' => 'true'
        ]);
        
        $response = $this->make_request($url);
        $events = $response['items'] ?? [];
        
        foreach ($events as $event) {
            if (isset($event['conferenceData']['conferenceId']) && 
                $event['conferenceData']['conferenceId'] === $meeting_id) {
                return [
                    'id' => $meeting_id,
                    'topic' => $event['summary'] ?? 'Untitled Meeting',
                    'start_time' => $event['start']['dateTime'] ?? $event['start']['date'],
                    'duration' => $this->calculate_duration($event)
                ];
            }
        }
        
        throw new \Exception("Meeting not found: $meeting_id");
    }
    
    /**
     * Make HTTP request with retry logic
     */
    private function make_request($url, $method = 'GET', $data = null) {
        $max_retries = 3;
        $retry_delays = [60, 300, 900]; // 1min, 5min, 15min
        
        for ($attempt = 0; $attempt < $max_retries; $attempt++) {
            $ch = curl_init();
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token
            ];
            
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
            
            // Handle rate limiting with retry
            if ($http_code === 429) {
                if ($attempt < $max_retries - 1) {
                    $delay = $retry_delays[$attempt];
                    mtrace("Rate limited. Retrying in {$delay} seconds...");
                    sleep($delay);
                    continue;
                }
                throw new \Exception("Rate limit exceeded after $max_retries attempts");
            }
            
            if ($http_code >= 400) {
                throw new \Exception("HTTP error $http_code: $response");
            }
            
            // DELETE requests return 204 No Content
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