<?php
require_once($GLOBALS['CFG']->libdir . '/filelib.php');
require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');
require_once($GLOBALS['CFG']->dirroot . '/mod/resource/lib.php');


function ensure_recordings_section(int $courseid, string $sectionname = 'Clases grabadas bot'): stdClass {
    global $DB;

    $section = $DB->get_record('course_sections', [
        'course' => $courseid,
        'name'   => $sectionname
    ]);

    if ($section) {
        return $section;
    }
    $section = new stdClass();
    $section->course = $courseid;
    $section->name = $sectionname;
    $section->summary = '';
    $section->summaryformat = FORMAT_HTML;
    $section->visible = 1;
    $section->timemodified = time();

    $section->section = $DB->get_field_sql("
        SELECT COALESCE(MAX(section), 0) + 1
        FROM {course_sections}
        WHERE course = ?
    ", [$courseid]);

    $section->id = $DB->insert_record('course_sections', $section);

    return $section;
}

function upload_to_moodle(int $courseid, string $filepath): void {
    global $DB;

    $sectionname = 'Clases grabadas bot';
    $section = ensure_recordings_section($courseid, $sectionname);
    $sectionid = $section->id;

    $filename = basename($filepath);
    $basename = pathinfo($filename, PATHINFO_FILENAME);

    $existing = $DB->get_records_sql("
        SELECT r.id
        FROM {resource} r
        JOIN {course_modules} cm ON cm.instance = r.id
        WHERE r.course = ? AND r.name = ? AND cm.section = ?
    ", [$courseid, $basename, $sectionid]);

    if (!empty($existing)) {
        throw new Exception("Duplicate detected: '$filename' already exists in section '$sectionname'.");
    }

    $resource = (object)[
        'course' => $courseid,
        'name' => $basename,
        'intro' => '',
        'introformat' => FORMAT_HTML,
        'timemodified' => time(),
    ];
    $resource->id = $DB->insert_record('resource', $resource);

    $moduleid = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);
    $cm = (object)[
        'course' => $courseid,
        'module' => $moduleid,
        'instance' => $resource->id,
        'visible' => 1,
    ];
    $cmid = add_course_module($cm);

    course_add_cm_to_section($courseid, $cmid, $section->section);

    $context = context_module::instance($cmid);
    $fs = get_file_storage();

    $fs->create_file_from_pathname([
        'contextid' => $context->id,
        'component' => 'mod_resource',
        'filearea'  => 'content',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename
    ], $filepath);

    echo "✅ Uploaded '$filename' to section '$sectionname' in course ID $courseid\n";
}
