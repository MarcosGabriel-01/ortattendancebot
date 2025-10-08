<?php
require(__DIR__ . '/../../config.php');
require_sesskey();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/folder/lib.php');

define('ATTBOT_BASEFOLDER', $CFG->dataroot . '/attendancebot');
define('SECTION_NAME', 'Clases Grabadas Bot');

$cmid = required_param('id', PARAM_INT);
global $DB;

$cm = get_coursemodule_from_id('attendancebot', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);

$context = context_course::instance($course->id);

/**
 * Ensure main section exists
 */
function attbot_get_or_create_main_section(int $courseid): stdClass {
    global $DB;

    if ($section = $DB->get_record('course_sections', ['course' => $courseid, 'name' => SECTION_NAME])) {
        return $section;
    }

    $maxsection = (int)$DB->get_field_sql('SELECT MAX(section) FROM {course_sections} WHERE course = ?', [$courseid]);
    $sectionnumber = $maxsection + 1;

    $section = (object)[
        'course' => $courseid,
        'section' => $sectionnumber,
        'name' => SECTION_NAME,
        'summary' => '',
        'visible' => 1,
        'timemodified' => time()
    ];

    $section->id = $DB->insert_record('course_sections', $section);
    return $section;
}

/**
 * Get or create division-level folder module in a section
 */
function attbot_get_or_create_division_folder(int $courseid, string $divisionname, int $sectionnumber): array {
    global $DB;

    $moduleid = $DB->get_field('modules', 'id', ['name' => 'folder']);

    $sql = "SELECT cm.*
            FROM {course_modules} cm
            JOIN {folder} f ON f.id = cm.instance
            WHERE cm.course = ? AND cm.section = ? AND cm.module = ? AND f.name = ?
            LIMIT 1";
    $cm = $DB->get_record_sql($sql, [$courseid, $sectionnumber, $moduleid, $divisionname]);
    if ($cm) {
        $folder = $DB->get_record('folder', ['id' => $cm->instance]);
        return ['folder' => $folder, 'cm' => $cm];
    }

    $folder = $DB->get_record('folder', ['course' => $courseid, 'name' => $divisionname]);
    if ($folder) {
        $existingcm = $DB->get_record('course_modules', [
            'instance' => $folder->id,
            'module' => $moduleid,
            'course' => $courseid,
            'section' => $sectionnumber
        ]);
        if ($existingcm) {
            return ['folder' => $folder, 'cm' => $existingcm];
        }

        $module = new stdClass();
        $module->course = $courseid;
        $module->module = $moduleid;
        $module->instance = $folder->id;
        $module->section = $sectionnumber;
        $module->visible = 1;
        $module->visibleoncoursepage = 1;

        $cmid = add_course_module($module);
        course_add_cm_to_section($courseid, $cmid, $sectionnumber);
        $newcm = $DB->get_record('course_modules', ['id' => $cmid]);
        return ['folder' => $folder, 'cm' => $newcm];
    }

    $folder = new stdClass();
    $folder->course = $courseid;
    $folder->name = $divisionname;
    $folder->intro = "Auto-created division folder: {$divisionname}";
    $folder->introformat = FORMAT_HTML;
    $folder->timemodified = time();
    $folder->id = $DB->insert_record('folder', $folder);

    $module = new stdClass();
    $module->course = $courseid;
    $module->module = $moduleid;
    $module->instance = $folder->id;
    $module->section = $sectionnumber;
    $module->visible = 1;
    $module->visibleoncoursepage = 1;

    $cmid = add_course_module($module);
    course_add_cm_to_section($courseid, $cmid, $sectionnumber);
    $newcm = $DB->get_record('course_modules', ['id' => $cmid]);

    return ['folder' => $folder, 'cm' => $newcm];
}

/**
 * Mirror filesystem structure
 */
function attbot_mirror_folders(int $courseid, string $basepath, context_course $context): array {
    global $DB;

    if (!is_dir($basepath) || count(glob($basepath . '/*', GLOB_ONLYDIR)) === 0) {
        return ['success' => false, 'message' => 'filesystemempty'];
    }

    $section = attbot_get_or_create_main_section($courseid);
    $sectionnumber = $section->section;
    $fs = get_file_storage();
    $processed = 0;
    $renamed = [];

    $sites = glob($basepath . '/*', GLOB_ONLYDIR);
    foreach ($sites as $sitepath) {
        $classes = glob($sitepath . '/*', GLOB_ONLYDIR);
        foreach ($classes as $classpath) {
            $divisions = glob($classpath . '/*', GLOB_ONLYDIR);
            foreach ($divisions as $divisionpath) {
                $division = basename($divisionpath);

                $df = attbot_get_or_create_division_folder($courseid, $division, $sectionnumber);
                $divisionfolder = $df['folder'];
                $divisioncm = $df['cm'];

                $cmobj = get_coursemodule_from_id('folder', $divisioncm->id, 0, false, MUST_EXIST);
                $modcontext = context_module::instance($cmobj->id);

                $dates = glob($divisionpath . '/*', GLOB_ONLYDIR);
                foreach ($dates as $datepath) {
                    $date = basename($datepath);
                    $files = glob($datepath . '/*.mp4');

                    $subpath = '/' . $date . '/';

                    foreach ($files as $filepath) {
                        $filename = basename($filepath);
                        $original = $filename;
                        $base = pathinfo($filename, PATHINFO_FILENAME);
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $i = 1;

                        while ($fs->get_file($modcontext->id, 'mod_folder', 'content', $divisionfolder->id, $subpath, $filename)) {
                            $filename = "{$base}_{$i}.{$ext}";
                            $i++;
                        }

                        if ($filename !== $original) {
                            $renamed[] = "{$original} → {$filename}";
                        }

                        $file_record = [
                            'contextid' => $modcontext->id,
                            'component' => 'mod_folder',
                            'filearea' => 'content',
                            'itemid' => $divisionfolder->id,
                            'filepath' => $subpath,
                            'filename' => $filename
                        ];

                        $fs->create_file_from_pathname($file_record, $filepath);
                    }

                    $processed++;
                }
            }
        }
    }

    return ['success' => true, 'processed' => $processed, 'renamed' => $renamed];
}

// Execute
$result = attbot_mirror_folders($course->id, ATTBOT_BASEFOLDER, $context);

echo $OUTPUT->header();

if (!$result['success'] && $result['message'] === 'filesystemempty') {
    echo $OUTPUT->notification(get_string('filesystemempty', 'attendancebot'), 'notifywarning');
} elseif ($result['success'] && $result['processed'] > 0) {
    echo $OUTPUT->notification(get_string('foldersmirrored', 'attendancebot', $result['processed']), 'notifysuccess');
    if (!empty($result['renamed'])) {
        echo $OUTPUT->notification(get_string('filesrenamed', 'attendancebot') . '<br>' . implode('<br>', $result['renamed']), 'notifywarning');
    }
} else {
    echo $OUTPUT->notification(get_string('nofolderscreated', 'attendancebot'), 'notifyinfo');
}

$returnurl = new moodle_url('/mod/attendancebot/view.php', ['id' => $cmid]);
echo $OUTPUT->single_button($returnurl, get_string('back', 'attendancebot'));
echo $OUTPUT->footer();
?>
