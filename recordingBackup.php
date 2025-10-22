<?php
/**
 * recordingBackup.php
 *
 * Downloads sample video from testApi, organizes it locally,
 * creates a single main section in Moodle, folder modules per video,
 * and uploads the MP4.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/testApi.php');       // Sample video connector
require_once(__DIR__ . '/folderCreation.php'); // Ensures folder path exists
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/mod/folder/lib.php');

$courseid = 21;
$sectionname = "Clases grabadas bot"; // Single main section
define('ATTBOT_BASE', $CFG->dataroot . '/attendancebot');
define('RECORDINGS_DIR', ATTBOT_BASE . '/recordings');

// ---------------------------
// FUNCTIONS
// ---------------------------

/**
 * Move a file into a structured local folder.
 */
function attbot_move_to_structure(string $filepath): string {
    $filename = basename($filepath);
    $target_dir = RECORDINGS_DIR;
    folder_ensure_path_exists($target_dir);
    $target_file = $target_dir . '/' . $filename;
    rename($filepath, $target_file);
    return $target_file;
}

/**
 * Ensure a main section exists and return its section ID.
 */
function ensure_main_section(int $courseid, string $sectionname = 'Clases grabadas bot'): int {
    global $DB;

    // Check if section 0 exists for this course
    $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => 0]);
    if ($section) {
        // Optionally update the name if empty or different
        if (empty($section->name) || $section->name !== $sectionname) {
            $section->name = $sectionname;
            $section->timemodified = time();
            $DB->update_record('course_sections', $section);
        }
        return $section->id;
    }

    // Create new section if none exists (rare)
    $section = new stdClass();
    $section->course = $courseid;
    $section->section = 0;
    $section->name = $sectionname;
    $section->summary = '';
    $section->summaryformat = 1;
    $section->visible = 1;
    $section->timemodified = time();
    return $DB->insert_record('course_sections', $section);
}


/**
 * Create a folder module for a video inside a section
 */
function create_folder_module(int $courseid, int $sectionid, string $foldername): int {
    global $DB;

    $moduleid = $DB->get_field('modules', 'id', ['name' => 'folder'], MUST_EXIST);

    // Check if already exists
    $record = $DB->get_record_sql("
        SELECT cm.* FROM {course_modules} cm
        JOIN {folder} f ON f.id = cm.instance
        WHERE cm.course = ? AND cm.section = ? AND cm.module = ? AND f.name = ?
        LIMIT 1
    ", [$courseid, $sectionid, $moduleid, $foldername]);

    if ($record) {
        return (int)$record->id;
    }

    // Create folder
    $folder = new stdClass();
    $folder->course = $courseid;
    $folder->name = $foldername;
    $folder->intro = "Video: $foldername";
    $folder->introformat = FORMAT_HTML;
    $folder->revision = 1;
    $folder->timemodified = time();
    $folder->id = $DB->insert_record('folder', $folder);

    // Create course module
    $module = new stdClass();
    $module->course = $courseid;
    $module->module = $moduleid;
    $module->instance = $folder->id;
    $module->section = $sectionid;
    $module->visible = 1;
    $cmid = add_course_module($module);
    course_add_cm_to_section($courseid, $cmid, $sectionid);

    return $cmid;
}

/**
 * Upload MP4 to Moodle folder module
 */
function upload_mp4_to_folder(int $cmid, string $filepath): bool {
    global $DB;

    $cm = get_coursemodule_from_id('folder', $cmid, 0, false, MUST_EXIST);
    $modcontext = context_module::instance($cm->id);
    $folder = $DB->get_record('folder', ['id' => $cm->instance], '*', MUST_EXIST);

    $fs = get_file_storage();
    $filename = basename($filepath);

    $file_record = [
        'contextid' => $modcontext->id,
        'component' => 'mod_folder',
        'filearea'  => 'content',
        'itemid'    => $folder->revision,
        'filepath'  => '/',
        'filename'  => $filename
    ];

    $fs->create_file_from_pathname($file_record, $filepath);

    $folder->revision++;
    $folder->timemodified = time();
    $DB->update_record('folder', $folder);

    return true;
}

// ---------------------------
// MAIN EXECUTION
// ---------------------------

try {
    echo "⏳ Downloading sample video...\n";

    $video_url = "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4";
    $tmp_file = download_sample_video($video_url, sys_get_temp_dir());

    echo "✅ Downloaded to temporary location: $tmp_file\n";

    // Organize locally
    $final_path = attbot_move_to_structure($tmp_file);
    echo "📂 Moved to structured folder: $final_path\n";

    // Ensure main section
    $sectionid = ensure_main_section($courseid, $sectionname);

    // Create folder module
    $foldername = pathinfo($final_path, PATHINFO_FILENAME);
    $cmid = create_folder_module($courseid, $sectionid, $foldername);

    // Upload MP4
    upload_mp4_to_folder($cmid, $final_path);

    echo "✅ Uploaded $final_path into Moodle folder module: $foldername\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
