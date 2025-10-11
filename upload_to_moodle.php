<?php
// File: upload_to_moodle.php

require(__DIR__ . '/../../config.php');
require_login();
require_sesskey();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/resource/lib.php');
require_once($CFG->dirroot . '/mod/resource/locallib.php');
require_once($CFG->dirroot . '/course/modlib.php');

// === INPUT PARAMS ===
$courseId = required_param('courseid', PARAM_INT);
$filename = required_param('filename', PARAM_FILE);
$filepath = required_param('filepath', PARAM_RAW);

// === VALIDATE COURSE AND FILE ===
$course = get_course($courseId);
$context = context_course::instance($courseId);
require_capability('moodle/course:manageactivities', $context);

if (!file_exists($filepath) || !is_readable($filepath)) {
    print_error("El archivo no existe o no es legible: $filepath");
}

// === DELETE PREVIOUS IF EXISTS ===
$fs = get_file_storage();
$existingFile = $fs->get_file($context->id, 'mod_resource', 'content', 0, '/', $filename);
if ($existingFile) {
    $existingFile->delete();
}

// === STORE FILE IN MOODLE ===
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
    print_error("Fallo al guardar archivo en Moodle (mdl_files)");
}

// === CREATE RESOURCE MODULE ===
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
$moduleinfo->section        = 0; // Section 0 = general
$moduleinfo->visible        = 1;
$moduleinfo->course         = $course->id;
$moduleinfo->name           = $resource->name;
$moduleinfo->intro          = $resource->intro;
$moduleinfo->introformat    = FORMAT_HTML;
$moduleinfo->display        = $resource->display;
$moduleinfo->type           = 'file';
$moduleinfo->contentfiles   = [$file->get_id()];

$mod = add_moduleinfo($moduleinfo, $course);

// === DONE ===
echo $OUTPUT->header();
echo $OUTPUT->notification("Grabación '$filename' subida exitosamente como recurso en el curso.", 'notifysuccess');

$returnurl = new moodle_url('/course/view.php', ['id' => $courseId]);
echo $OUTPUT->single_button($returnurl, get_string('backtocourse', 'moodle'));
echo $OUTPUT->footer();
