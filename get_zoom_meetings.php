<?php
// File: get_zoom_meetings.php

require(__DIR__ . '/../../config.php');
require_login();
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');

// Set timezone
date_default_timezone_set('UTC');

$accessToken = getZoomToken();
if (!$accessToken) {
    print_error("No se pudo obtener el token de Zoom.");
}

// Adjust these params as needed
$pageSize = 30;
$fromDate = date('Y-m-d', strtotime('-7 days'));
$toDate   = date('Y-m-d');

// Zoom API URL to fetch past user meetings
$userId = 'me'; // or a specific Zoom user ID
$url = "https://api.zoom.us/v2/users/{$userId}/meetings?type=past&from=$fromDate&to=$toDate&page_size=$pageSize";

// Prepare headers
$headers = [
    "Authorization: Bearer $accessToken",
    "Content-Type: application/json",
    "User-Agent: MoodleAttendanceBot/1.0"
];

// Initialize curl
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => $headers,
]);

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    print_error("cURL error: $error");
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 400) {
    print_error("Zoom API Error [$httpCode]: $response");
}

$data = json_decode($response, true);

if (!is_array($data) || empty($data['meetings'])) {
    echo $OUTPUT->notification("No se encontraron reuniones en Zoom.", 'notifymessage');
} else {
    echo $OUTPUT->header();
    echo html_writer::tag('h3', "Reuniones encontradas de $fromDate a $toDate:");
    echo html_writer::start_tag('ul');

    foreach ($data['meetings'] as $meeting) {
        $topic = $meeting['topic'] ?? 'Sin título';
        $start = $meeting['start_time'] ?? 'N/A';
        $id    = $meeting['uuid'] ?? 'N/A';

        echo html_writer::tag('li', "[$start] $topic (UUID: $id)");
    }

    echo html_writer::end_tag('ul');
    echo $OUTPUT->footer();
}
