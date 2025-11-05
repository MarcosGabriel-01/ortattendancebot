<?php
/**
 * Moodle mirroring for recording backup
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\backup;

defined('MOODLE_INTERNAL') || die();

class moodle_mirroring {
    
    const SECTION_NAME = 'Clases grabadas bot';
    
    
    public static function ensure_recordings_section($courseid) {
        global $DB;
        
        $section = $DB->get_record('course_sections', [
            'course' => $courseid,
            'name' => self::SECTION_NAME
        ]);
        
        if ($section) {
            return $section;
        }
        
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $format = course_get_format($course);
        
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section DESC', 'section', 0, 1);
        $last_section = reset($sections);
        $new_section_num = $last_section ? $last_section->section + 1 : 1;
        
        $section = new \stdClass();
        $section->course = $courseid;
        $section->section = $new_section_num;
        $section->name = self::SECTION_NAME;
        $section->summary = '';
        $section->summaryformat = FORMAT_HTML;
        $section->sequence = '';
        $section->visible = 1;
        $section->availability = null;
        $section->timemodified = time();
        
        $section->id = $DB->insert_record('course_sections', $section);
        
        rebuild_course_cache($courseid);
        
        return $section;
    }
    
    
    public static function get_or_create_folder($courseid, $folder_name) {
        global $CFG, $DB;
        
        require_once($CFG->dirroot . '/mod/folder/lib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        
        $section = self::ensure_recordings_section($courseid);
        
        $sql = "SELECT cm.*, f.name as foldername
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {folder} f ON f.id = cm.instance
                WHERE cm.course = :courseid
                AND cm.section = :sectionid
                AND m.name = 'folder'
                AND f.name = :foldername
                AND cm.deletioninprogress = 0";
        
        $existing = $DB->get_record_sql($sql, [
            'courseid' => $courseid,
            'sectionid' => $section->id,
            'foldername' => $folder_name
        ]);
        
        if ($existing) {
            return $existing;
        }
        
        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = 'folder';
        
        $module = $DB->get_record('modules', ['name' => 'folder'], '*', MUST_EXIST);
        $moduleinfo->module = $module->id;
        $moduleinfo->files = null;
        
        $moduleinfo->course = $courseid;
        $moduleinfo->section = $section->section;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->name = $folder_name;
        $moduleinfo->intro = '';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->showexpanded = 0;
        $moduleinfo->showdownloadfolder = 1;
        
        $moduleinfo = add_moduleinfo($moduleinfo, $DB->get_record('course', ['id' => $courseid]));
        
        return $DB->get_record('course_modules', ['id' => $moduleinfo->coursemodule]);
    }
    
    
    public static function upload_to_moodle($courseid, $folder_name, $subfolder_name, $filename, $filepath) {
        global $DB;
        
        $cm = self::get_or_create_folder($courseid, $folder_name);
        
        $existing_file = self::check_duplicate($cm->instance, $subfolder_name, $filename, filesize($filepath));
        if ($existing_file) {
            return $existing_file->get_id();
        }
        
        $context = \context_module::instance($cm->id);
        
        $file_record = [
            'contextid' => $context->id,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/' . $subfolder_name . '/',
            'filename' => $filename,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        
        $fs = get_file_storage();
        $file = $fs->create_file_from_pathname($file_record, $filepath);
        
        if (!$file) {
            throw new \Exception("Failed to create file in Moodle: $filename");
        }
        
        return $file->get_id();
    }
    
    
    private static function check_duplicate($folder_instance, $subfolder, $filename, $filesize) {
        global $DB;
        
        $fs = get_file_storage();
        
        $cm = get_coursemodule_from_instance('folder', $folder_instance);
        $context = \context_module::instance($cm->id);
        
        $files = $fs->get_area_files(
            $context->id,
            'mod_folder',
            'content',
            0,
            'filepath, filename',
            false
        );
        
        $filepath = '/' . $subfolder . '/';
        
        foreach ($files as $file) {
            if ($file->get_filepath() === $filepath && $file->get_filename() === $filename) {
                $existing_size = $file->get_filesize();
                $size_diff = abs($existing_size - $filesize);
                $threshold = $filesize * 0.05;
                
                if ($size_diff <= $threshold) {
                    return $file;
                }
            }
        }
        
        return false;
    }
}
