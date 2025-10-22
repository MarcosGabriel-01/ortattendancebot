<?php
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/folder/lib.php');

define('RECORDINGS_DIR', $CFG->dataroot . '/attendancebot/recordings');
define('SECTION_NAME', 'Clases Grabadas Bot');

global $DB, $USER;

$cmid = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('attendancebot', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_course::instance($course->id);

/**
 * Ensure main section exists.
 */
function attbot_get_or_create_section($courseid): stdClass {
    global $DB;
    if ($section = $DB->get_record('course_sections', ['course' => $courseid, 'name' => SECTION_NAME])) {
        return $section;
    }
    $max = (int)$DB->get_field_sql('SELECT MAX(section) FROM {course_sections} WHERE course = ?', [$courseid]);
    $section = (object)[
        'course' => $courseid,
        'section' => $max + 1,
        'name' => 'clases grabadas',
        'summary' => '',
        'visible' => 1,
        'timemodified' => time()
    ];
    $section->id = $DB->insert_record('course_sections', $section);
    return $section;
}

/**
 * Create or find Moodle folder resource.
 */
function attbot_get_or_create_folder($courseid, $sectionid, $foldername): array {
    global $DB;
    $moduleid = $DB->get_field('modules', 'id', ['name' => 'folder']);
    $sql = "SELECT cm.*, f.* FROM {course_modules} cm
            JOIN {folder} f ON f.id = cm.instance
            WHERE cm.course = ? AND cm.section = ? AND f.name = ?";
    $record = $DB->get_record_sql($sql, [$courseid, $sectionid, $foldername]);
    if ($record) {
        return ['folder' => (object)$record, 'cm' => (object)$record];
    }

    $folder = (object)[
        'course' => $courseid,
        'name' => $foldername,
        'intro' => 'Auto-created folder',
        'introformat' => FORMAT_HTML,
        'revision' => 1,
        'timemodified' => time()
    ];
    $folder->id = $DB->insert_record('folder', $folder);

    $cm = (object)[
        'course' => $courseid,
        'module' => $moduleid,
        'instance' => $folder->id,
        'section' => $sectionid,
        'visible' => 1
    ];
    $cmid = add_course_module($cm);
    course_add_cm_to_section($courseid, $cmid, $sectionid);
    $cmrec = $DB->get_record('course_modules', ['id' => $cmid]);

    return ['folder' => $folder, 'cm' => $cmrec];
}

/**
 * Upload file to folder resource.
 */
function upload_mp4_to_folder(int $cmid, string $filepath): bool {
    global $DB;

    // Get course module and folder
    $cm = get_coursemodule_from_id('folder', $cmid, 0, false, MUST_EXIST);
    $folder = $DB->get_record('folder', ['id' => $cm->instance], '*', MUST_EXIST);
    $modcontext = context_module::instance($cm->id);

    // Ensure revision exists
    if (empty($folder->revision)) {
        $folder->revision = 1;
    }

    $fs = get_file_storage();
    $filename = basename($filepath);

    // Delete previous file if exists
    $existing = $fs->get_file($modcontext->id, 'mod_folder', 'content', $folder->revision, '/', $filename);
    if ($existing) {
        $existing->delete();
    }

    // Create the file inside the correct filearea & revision
    $file_record = [
        'contextid' => $modcontext->id,
        'component' => 'mod_folder',
        'filearea'  => 'content',
        'itemid'    => $folder->revision,
        'filepath'  => '/',
        'filename'  => $filename
    ];

    $fs->create_file_from_pathname($file_record, $filepath);

    // Increment revision and update folder
    $folder->revision++;
    $folder->timemodified = time();
    $DB->update_record('folder', $folder);

    return true;
}


/**
 * Mirror all recordings.
 */
function attbot_mirror_recordings($courseid, $sectionid) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(RECORDINGS_DIR));
    foreach ($it as $file) {
        if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
            $relpath = str_replace(RECORDINGS_DIR . '/', '', dirname($file));
            $foldername = str_replace('/', ' - ', $relpath);
            $pair = attbot_get_or_create_folder($courseid, $sectionid, $foldername);
            $context = context_module::instance($pair['cm']->id);
            upload_mp4_to_folder($context, $pair['folder'], $file);
        }
    }
}

// --- MAIN EXECUTION ---
$section = attbot_get_or_create_section($course->id);
attbot_mirror_recordings($course->id, $section->section);

echo $OUTPUT->header();
echo $OUTPUT->notification('✅ Recordings mirrored successfully.', 'notifysuccess');
echo $OUTPUT->footer();
