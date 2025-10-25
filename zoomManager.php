<?php
defined('MOODLE_INTERNAL') || die();

const ZOOM_API_BASE = 'https://api.zoom.us/v2';

/**
 * Fetch Zoom recording info and download directly to a temporary file.
 *
 * @param string $meeting_id
 * @return array ['name'=>string, 'tmp_file'=>string, 'auth'=>['token'=>string]]
 * @throws Exception
 */
function fetchRecording(string $meeting_id): array {
    $token = zoom_generate_access_token(); // implement your OAuth/token retrieval

    // Step 1: Get recording metadata
    $url = ZOOM_API_BASE . "/meetings/{$meeting_id}/recordings";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code !== 200) {
        throw new Exception("Failed to fetch Zoom metadata for meeting {$meeting_id}: {$response}");
    }

    $data = json_decode($response, true);
    if (empty($data['recording_files'][0]['download_url'])) {
        throw new Exception("No recording found for meeting {$meeting_id}");
    }

    $file_url = $data['recording_files'][0]['download_url'];

    // Step 2: Stream download to temporary file
    $tmp_file = tempnam(sys_get_temp_dir(), 'zoom_') . '.mp4';
    $fp = fopen($tmp_file, 'w');
    if (!$fp) {
        throw new Exception("Cannot open temporary file for writing: {$tmp_file}");
    }

    $curl = curl_init($file_url . "?access_token={$token}");
    curl_setopt_array($curl, [
        CURLOPT_FILE => $fp,            // stream directly to file
        CURLOPT_FOLLOWLOCATION => true, // follow redirects
        CURLOPT_TIMEOUT => 0,           // no timeout for long recordings
    ]);

    $success = curl_exec($curl);
    if (!$success) {
        $err = curl_error($curl);
        curl_close($curl);
        fclose($fp);
        unlink($tmp_file);
        throw new Exception("Failed to download Zoom recording: {$err}");
    }

    curl_close($curl);
    fclose($fp);

    // ✅ Return recording info
    return [
        'name' => $data['topic'],
        'tmp_file' => $tmp_file,
        'auth' => ['token' => $token],
        'meeting_id' => $meeting_id
    ];
}
