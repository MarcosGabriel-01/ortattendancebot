<?php
require(__DIR__ . '/../../config.php');
require_sesskey();

require_once($CFG->dirroot . '/course/lib.php'); 
require_once($CFG->dirroot . '/mod/folder/lib.php'); 

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
define('ATTBOT_BASEFOLDER', $CFG->dataroot . '/attendancebot');

// -----------------------------------------------------------------------------
// Parameters
// -----------------------------------------------------------------------------
$cmid = required_param('id', PARAM_INT);

// -----------------------------------------------------------------------------
// Get course and module
// -----------------------------------------------------------------------------
$cm = get_coursemodule_from_id('attendancebot', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm); // sets PAGE context internally

$PAGE->set_url(new moodle_url('/mod/attendancebot/create_folder.php', ['id' => $cmid]));
$PAGE->set_title(get_string('createfolder_title', 'attendancebot'));
$PAGE->set_heading(format_string($course->fullname));

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------
function attbot_get_or_create_section(int $courseid): int {
    global $DB;

    $section = $DB->get_record('course_sections', [
        'course' => $courseid,
        'name' => 'Clases Grabadas'
    ]);

    if ($section) {
        return $section->section;
    }

    $maxsection = $DB->get_field_sql('SELECT MAX(section) FROM {course_sections} WHERE course = ?', [$courseid]);
    $sectionnumber = $maxsection + 1;

    $section = new stdClass();
    $section->course = $courseid;
    $section->name = 'Clases Grabadas';
    $section->section = $sectionnumber;
    $section->summary = '';
    $section->visible = 1;
    $section->timemodified = time();

    $section->id = $DB->insert_record('course_sections', $section);

    return $sectionnumber;
}

function attbot_mirror_all_folders_in_course(int $courseid, string $basepath) {
    global $DB;

    $context = context_course::instance($courseid);
    $sectionnumber = attbot_get_or_create_section($courseid);
    $fs = get_file_storage();

    if (!is_dir($basepath)) {
        return ['success' => false, 'message' => "Base folder does not exist: $basepath"];
    }

    $sites = glob($basepath . '/*', GLOB_ONLYDIR);
    $processed = 0;

    foreach ($sites as $sitepath) {
        $classes = glob($sitepath . '/*', GLOB_ONLYDIR);
        foreach ($classes as $classpath) {
            $divisions = glob($classpath . '/*', GLOB_ONLYDIR);
            foreach ($divisions as $divisionpath) {
                $dates = glob($divisionpath . '/*', GLOB_ONLYDIR);
                foreach ($dates as $datepath) {
                    $division = basename($divisionpath);
                    $date = basename($datepath);
                    $foldername = "[{$division}-{$date}]";

                    // ---------------------------
                    // Reuse existing folder if it exists
                    // ---------------------------
                    $existing = $DB->get_record('folder', ['course' => $courseid, 'name' => $foldername]);
                    if ($existing) {
                        $folderid = $existing->id;
                        $action = 'updated';
                    } else {
                        // Create Moodle folder
                        $folder = new stdClass();
                        $folder->course = $courseid;
                        $folder->name = $foldername;
                        $folder->intro = "AttendanceBot folder: {$foldername}";
                        $folder->introformat = FORMAT_HTML;
                        $folder->timemodified = time();
                        $folderid = $DB->insert_record('folder', $folder);

                        $module = new stdClass();
                        $module->course = $courseid;
                        $module->module = $DB->get_field('modules', 'id', ['name' => 'folder']);
                        $module->instance = $folderid;
                        $module->section = $sectionnumber;
                        $module->visible = 1;
                        $module->visibleoncoursepage = 1;

                        $cmid = add_course_module($module);
                        course_add_cm_to_section($courseid, $cmid, $sectionnumber);

                        $action = 'created';
                    }

                    // ---------------------------
                    // Copy files with automatic renaming if duplicates exist
                    // ---------------------------
                    $files = glob($datepath . '/*');
                    foreach ($files as $filepath) {
                        if (is_file($filepath)) {
                            $filename = basename($filepath);

                            $i = 1;
                            $base = pathinfo($filename, PATHINFO_FILENAME);
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);

                            // Automatically rename if file exists
                            while ($fs->get_file($context->id, 'mod_folder', 'content', $folderid, '/', $filename)) {
                                $filename = "{$base}_{$i}.{$ext}";
                                $i++;
                            }

                            $file_record = [
                                'contextid' => $context->id,
                                'component' => 'mod_folder',
                                'filearea' => 'content',
                                'itemid' => $folderid,
                                'filepath' => '/',
                                'filename' => $filename,
                            ];
                            $fs->create_file_from_pathname($file_record, $filepath);
                        }
                    }

                    $processed++;
                }
            }
        }
    }

    return ['success' => true, 'processed' => $processed];
}

// -----------------------------------------------------------------------------
// Execute
// -----------------------------------------------------------------------------
$result = attbot_mirror_all_folders_in_course($course->id, ATTBOT_BASEFOLDER);

echo $OUTPUT->header();

if ($result['success']) {
    if ($result['processed'] > 0) {
        echo $OUTPUT->notification(get_string('folder_mirrored', 'attendancebot') . " ({$result['processed']} folders processed)", 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('folder_exists', 'attendancebot'), 'notifywarning');
    }
} else {
    echo $OUTPUT->notification("Error: " . $result['message'], 'notifyproblem');
}

$returnurl = new moodle_url('/mod/attendancebot/view.php', ['id' => $cmid]);
echo $OUTPUT->single_button($returnurl, get_string('back', 'attendancebot'));
echo $OUTPUT->footer();
