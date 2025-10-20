<?php
// File: backup_recordings.php

require(__DIR__ . '/../../config.php');
require_login();

require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/resource/lib.php');
require_once($CFG->dirroot . '/mod/resource/locallib.php');
require_once($CFG->dirroot . '/course/modlib.php');

// === CONFIG ===
$courseId = required_param('courseid', PARAM_INT);
$meetingUUIDs = optional_param_array('uuids', [], PARAM_RAW); // Can be passed via GET or POST
$recordingPath = $CFG->dataroot . '/attendancebot/recordings/';

if (!is_dir($recordingPath)) {
    mkdir($recordingPath, 0777, true);
}

$token = getZoomToken();
if (!$token) {
    print_error("No se pudo obtener el token de Zoom.");
}

$context = context_course::instance($courseId);
$course = get_course($courseId);
require_capability('moodle/course:manageactivities', $context);

// === PROCESS EACH MEETING ===
foreach ($meetingUUIDs as $uuid) {
    $encodedId = urlencode($uuid);
    $url = "https://api.zoom.us/v2/meetings/{$encodedId}/recordings";

    $headers = [
        "Authorization: Bearer {$token}",
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
        mtrace("❌ Fallo al obtener grabaciones para UUID $uuid (HTTP $httpCode)");
        continue;
    }

    $data = json_decode($response, true);
    $recordings = $data['recording_files'] ?? [];

    // Filter relevant MP4s
    $filtered = array_filter($recordings, function ($rec) {
        return $rec['file_type'] === 'MP4' &&
               in_array($rec['recording_type'], [
                   'shared_screen_with_speaker_view',
                   'shared_screen_with_gallery_view',
                   'active_speaker'
               ]);
    });

    $recording = reset($filtered);
    if (!$recording) {
        mtrace("⚠️ No hay grabaciones MP4 relevantes para UUID $uuid");
        continue;
    }

    $fileUrl = $recording['download_url'] . "?access_token={$token}";
    $expectedSize = (int)$recording['file_size'];
    $filename = $recording['id'] . ".mp4";
    $filepath = $recordingPath . DIRECTORY_SEPARATOR . $filename;

    // === Download ===
    $fp = fopen($filepath, 'w+b');
    if (!$fp) {
        mtrace("❌ No se pudo abrir el archivo para escribir: $filename");
        continue;
    }

    $ch = curl_init($fileUrl);
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
        mtrace("❌ Fallo al descargar $filename (HTTP $httpCode)");
        unlink($filepath);
        continue;
    }

    // === Upload to Moodle ===
    $fs = get_file_storage();

    // Delete previous if exists
    $existing = $fs->get_file($context->id, 'mod_resource', 'content', 0, '/', $filename);
    if ($existing) {
        $existing->delete();
    }

    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'mod_resource',
        'filearea'  => 'content',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename,
    ];
    $file = $fs->create_file_from_pathname($fileinfo, $filepath);

    if (!$file || !$file->get_id()) {
        mtrace("❌ Fallo al subir el archivo a Moodle: $filename");
        continue;
    }

    // === Create Resource Module ===
    $resource = new stdClass();
    $resource->course       = $course->id;
    $resource->name         = 'Grabación Zoom - ' . date('Y-m-d H:i');
    $resource->intro        = 'Grabación subida automáticamente.';
    $resource->introformat  = FORMAT_HTML;
    $resource->display      = RESOURCELIB_DISPLAY_AUTO;
    $resource->timemodified = time();

    $moduleinfo = new stdClass();
    $moduleinfo->modulename     = 'resource';
    $moduleinfo->module         = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);
    $moduleinfo->section        = 0;
    $moduleinfo->visible        = 1;
    $moduleinfo->course         = $course->id;
    $moduleinfo->name           = $resource->name;
    $moduleinfo->intro          = $resource->intro;
    $moduleinfo->introformat    = FORMAT_HTML;
    $moduleinfo->display        = $resource->display;
    $moduleinfo->type           = 'file';
    $moduleinfo->contentfiles   = [$file->get_id()];

    add_moduleinfo($moduleinfo, $course);
    mtrace("✅ Grabación subida y recurso creado: $filename");
}

echo $OUTPUT->header();
echo $OUTPUT->notification("Proceso de respaldo de grabaciones completado.", 'notifysuccess');
$returnurl = new moodle_url('/course/view.php', ['id' => $courseId]);
echo $OUTPUT->single_button($returnurl, get_string('backtocourse', 'moodle'));
echo $OUTPUT->footer();
