<?php
/**
 * Zoom API client - Uses mod_zoom for metadata, direct API for downloads/deletes
 *
 * @package     mod_ortattendancebot
 * @copyright   2025
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\api;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/client_interface.php');

class zoom_client implements client_interface {

    private $base_url = 'https://api.zoom.us/v2';
    private $token;
    private $webservice;
    private $use_mod_zoom;

    public function __construct() {
        global $CFG;

        
        $this->use_mod_zoom = file_exists($CFG->dirroot . '/mod/zoom/classes/webservice.php');

        if ($this->use_mod_zoom) {
            require_once($CFG->dirroot . '/mod/zoom/locallib.php');
            try {
                $this->webservice = zoom_webservice();
                mtrace("Using mod_zoom webservice for metadata queries");
            } catch (\Exception $e) {
                mtrace("mod_zoom found but webservice initialization failed: " . $e->getMessage());
                $this->use_mod_zoom = false;
            }
        }

        
        $this->token = $this->get_zoom_token();

        if (!$this->token) {
            throw new \Exception('Zoom OAuth token not found. Please configure Server-to-Server OAuth in mod_zoom.');
        }
    }

    
    private function get_zoom_token() {
        global $CFG;

        $clientid = get_config('zoom', 'clientid');
        $clientsecret = get_config('zoom', 'clientsecret');
        $accountid = get_config('zoom', 'accountid');

        
        if ($this->use_mod_zoom && $clientid && $clientsecret && $accountid) {
            mtrace("Using mod_zoom managed token");
            return 'mod_zoom_managed';
        }

        
        if ($clientid && $clientsecret && $accountid) {
            $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$accountid}";
            $auth = base64_encode("{$clientid}:{$clientsecret}");

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Basic {$auth}"
                ]
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code != 200 || !$response) {
                mtrace("Failed to retrieve Zoom OAuth token (HTTP $http_code)");
                return null;
            }

            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        return null;
    }

    
    public function get_meetings_by_date($date) {
        if ($this->use_mod_zoom) {
            return $this->webservice->get_meetings($date, $date);
        }

        return $this->get_meetings_by_date_range($date, $date);
    }

    
    public function get_meetings_by_date_range($from_date, $to_date) {
        if ($this->use_mod_zoom) {
            return $this->webservice->get_meetings($from_date, $to_date);
        }

        $url = "{$this->base_url}/metrics/meetings";
        $params = [
            'type' => 'past',
            'from' => $from_date,
            'to' => $to_date,
            'query_date_type' => 'end_time',
        ];

        $response = $this->make_request($url . '?' . http_build_query($params));
        return $response['meetings'] ?? [];
    }

    
    public function get_meeting_participants($meeting_uuid) {
        if ($this->use_mod_zoom) {
            return $this->webservice->get_meeting_participants($meeting_uuid, false);
        }

        $encoded_uuid = rawurlencode(rawurlencode($meeting_uuid));
        $url = "{$this->base_url}/metrics/meetings/{$encoded_uuid}/participants";
        $response = $this->make_request($url);
        return $response['participants'] ?? [];
    }

    
    public function get_recording_metadata($meeting_id, $user_id = null) {
        if ($this->use_mod_zoom && $user_id) {
            $recordings = $this->webservice->get_user_recordings(
                $user_id, date('Y-m-d', strtotime('-30 days')), date('Y-m-d')
            );

            return array_values(array_filter($recordings, function($rec) use ($meeting_id) {
                return $rec->meetingid == $meeting_id;
            }));
        }

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

    
    public function get_meeting_info($meeting_id) {
        if ($this->use_mod_zoom) {
            return $this->webservice->get_meeting_webinar_info($meeting_id, false);
        }

        $url = "{$this->base_url}/meetings/{$meeting_id}";
        return $this->make_request($url);
    }

    
    public function download_recordings_batch($recordings, $base_path) {
        $results = [];

        foreach ($recordings as $rec) {
            try {
                $filepath = $this->download_single_recording(
                    $rec['download_url'], $base_path, $rec['filename'] ?? null
                );

                $results[] = [
                    'recording_id' => $rec['recording_id'],
                    'meeting_id' => $rec['meeting_id'],
                    'success' => true,
                    'filepath' => $filepath,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'recording_id' => $rec['recording_id'],
                    'meeting_id' => $rec['meeting_id'],
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                mtrace("Failed to download recording {$rec['recording_id']}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    
    private function download_single_recording($download_url, $base_path, $filename = null) {
        if (!file_exists($base_path)) {
            mkdir($base_path, 0775, true);
        }

        if (!$filename) {
            $filename = basename(parse_url($download_url, PHP_URL_PATH)) ?: 'recording_' . time() . '.mp4';
        }

        $filepath = rtrim($base_path, '/') . '/' . $filename;

        $ch = curl_init($download_url);
        $fp = fopen($filepath, 'wb');

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 3600,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
            ],
        ]);

        $success = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if (!$success || $http_code >= 400) {
            @unlink($filepath);
            throw new \Exception("Download failed with HTTP {$http_code}: {$error}");
        }

        return $filepath;
    }

    
    public function delete_recordings($recordings) {
        if (!isset($recordings[0])) {
            $recordings = [$recordings];
        }

        $results = [];
        foreach ($recordings as $rec) {
            try {
                $url = "{$this->base_url}/meetings/{$rec['meeting_id']}/recordings/{$rec['recording_id']}";
                $this->make_request($url, 'DELETE');
                $results[] = [
                    'meeting_id' => $rec['meeting_id'],
                    'recording_id' => $rec['recording_id'],
                    'success' => true,
                ];
                mtrace("Deleted recording {$rec['recording_id']} from meeting {$rec['meeting_id']}");
            } catch (\Exception $e) {
                $results[] = [
                    'meeting_id' => $rec['meeting_id'],
                    'recording_id' => $rec['recording_id'],
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                mtrace("Failed to delete recording {$rec['recording_id']}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    
    private function make_request($url, $method = 'GET', $data = null) {
        $max_retries = 3;
        $retry_delays = [60, 300, 900];

        for ($attempt = 0; $attempt < $max_retries; $attempt++) {
            $ch = curl_init();

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
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

            if ($http_code === 429 && $attempt < $max_retries - 1) {
                $delay = $retry_delays[$attempt];
                mtrace("Rate limited. Retrying in {$delay} seconds...");
                sleep($delay);
                continue;
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
