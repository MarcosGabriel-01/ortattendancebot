<?php
// File: download_recordings.php

require(__DIR__ . '/../../config.php');
require_login();
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');

$meetingId = required_param('uuid', PARAM_RAW); // UUID from Zoom
$targetDir = optional_param('target', '', PARAM_RAW); // Optional download path (absolute)
$recordingPath = $targetDir ?: $CFG->dataroot . '/attendancebot/recordings/';

if (!is_dir($recordingPath)) {
    if (!mkdir($recordingPath, 0777, true)) {
        print_error("No se pudo crear el directorio de destino: $recordingPath");
    }
}

$accessToken = getZoomToken();
if (!$accessToken) {
    print_error("Token de Zoom inválido o no disponible.");
}

// Zoom API: Get meeting recordings
$encodedId = urlencode($meetingId);
$url = "https://api.zoom.us/v2/meetings/{$encodedId}/recordings";

$headers = [
    "Authorization: Bearer $accessToken",
    "User-Agent: MoodleAttendanceBot/1.0"
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => $headers
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 400 || !$response) {
    print_error("Error al consultar grabaciones: HTTP $httpCode - $response");
}

$data = json_decode($response, true);
$recordings = $data['recording_files'] ?? [];

if (empty($recordings)) {
    echo $OUTPUT->notification("No hay grabaciones para esta reunión.", 'notifymessage');
    exit;
}

// Filter desired recording types
$filtered = array_filter($recordings, function ($rec) {
    return $rec['file_type'] === 'MP4' &&
           in_array($rec['recording_type'], [
               'shared_screen_with_speaker_view',
               'shared_screen_with_gallery_view',
               'active_speaker'
           ]);
});

$downloaded = 0;

foreach ($filtered as $recording) {
    $downloadUrl = $recording['download_url'] . "?access_token=" . $accessToken;
    $filename = $recording['id'] . ".mp4";
    $filepath = $recordingPath . DIRECTORY_SEPARATOR . $filename;

    // Download the file
    $fp = fopen($filepath, 'w+b');
    if (!$fp) {
        echo $OUTPUT->notification("No se pudo abrir el archivo para escribir: $filename", 'notifyproblem');
        continue;
    }

    $ch = curl_init($downloadUrl);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FAILONERROR    => true,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $success = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if (!$success || $httpCode >= 400) {
        unlink($filepath);
        echo $OUTPUT->notification("Fallo al descargar $filename (HTTP $httpCode)", 'notifyproblem');
    } else {
        $downloaded++;
        echo $OUTPUT->notification("Grabación descargada: $filename", 'notifysuccess');
    }
}

echo $OUTPUT->header();
echo html_writer::tag('p', "Total descargado: $downloaded archivo(s) en $recordingPath");
echo $OUTPUT->footer();
