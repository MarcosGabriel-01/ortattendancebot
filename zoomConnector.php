<?php
require_once(__DIR__ . '/../../config.php');

$zoom_access_token = 'ACCES_TOKEN';
$download_dir = $CFG->dataroot . '/attendancebot/tmp_downloads';

if (!file_exists($download_dir)) {
    mkdir($download_dir, 0777, true);
}

function zoom_api_get($endpoint, $token) {
    $ch = curl_init("https://api.zoom.us/v2/$endpoint");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]
    ]);
    $res = curl_exec($ch);
    if (curl_errno($ch)) throw new Exception('Zoom API error: ' . curl_error($ch));
    curl_close($ch);
    return json_decode($res, true);
}

function zoom_download_file($url, $filename, $token, $dir) {
    $target = rtrim($dir, '/') . '/' . basename($filename);
    $fp = fopen($target, 'w+');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    curl_exec($ch);
    fclose($fp);
    if (curl_errno($ch)) throw new Exception('Download failed: ' . curl_error($ch));
    curl_close($ch);
    return $target;
}

function zoom_fetch_recordings($zoom_access_token, $download_dir) {
    $userId = 'me';
    $response = zoom_api_get("users/$userId/recordings?page_size=5", $zoom_access_token);
    $files = [];

    foreach ($response['meetings'] ?? [] as $meeting) {
        foreach ($meeting['recording_files'] ?? [] as $file) {
            if ($file['file_type'] === 'MP4') {
                $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $meeting['topic']);
                $filename = "{$name}_{$file['id']}.mp4";
                $files[] = zoom_download_file($file['download_url'], $filename, $zoom_access_token, $download_dir);
            }
        }
    }

    return $files;
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $downloaded = zoom_fetch_recordings($zoom_access_token, $download_dir);
    print_r($downloaded);
}
