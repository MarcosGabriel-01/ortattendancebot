<?php
require_once($GLOBALS['CFG']->libdir . '/filelib.php');
require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');
require_once($GLOBALS['CFG']->dirroot . '/mod/folder/lib.php');
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

function upload_to_moodle(int $courseid, string $filepath, array $file_data): void {
    global $DB;

    $sectionname = 'Clases grabadas bot';
    $section = ensure_recordings_section($courseid, $sectionname);
    $sectionid = $section->id;

    $filename = basename($filepath);
    $basefolder = $file_data['name'];
    $datefolder = $file_data['date'];

    $folder = $DB->get_record('folder', [
        'course' => $courseid,
        'name' => $basefolder
    ]);

    if (!$folder) {
        $folder = (object)[
            'course' => $courseid,
            'name' => $basefolder,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'timemodified' => time()
        ];
        $folder->id = $DB->insert_record('folder', $folder);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'folder'], MUST_EXIST);
        $cm = (object)[
            'course' => $courseid,
            'module' => $moduleid,
            'instance' => $folder->id,
            'visible' => 1
        ];
        $cmid = add_course_module($cm);
        course_add_cm_to_section($courseid, $cmid, $section->section);
    } else {
        $cmid = $DB->get_field('course_modules', 'id', [
            'instance' => $folder->id,
            'module' => $DB->get_field('modules', 'id', ['name' => 'folder'])
        ]);
    }

    $context = context_module::instance($cmid);
    $fs = get_file_storage();

    if ($fs->file_exists($context->id, 'mod_folder', 'content', 0, "/{$datefolder}/", $filename)) {
        echo "⚠️ Skipped duplicate: {$filename} already exists in {$basefolder}/{$datefolder}\n";
        return;
    }

    $filepath_in_folder = "/{$datefolder}/";

    $fs->create_file_from_pathname([
        'contextid' => $context->id,
        'component' => 'mod_folder',
        'filearea'  => 'content',
        'itemid'    => 0,
        'filepath'  => $filepath_in_folder,
        'filename'  => $filename
    ], $filepath);

    echo "✅ Uploaded '{$filename}' to '{$sectionname}/{$basefolder}/{$datefolder}' (course ID {$courseid})\n";
}
