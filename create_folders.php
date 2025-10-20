<?php
namespace local_yourplugin;

defined('MOODLE_INTERNAL') || die();

class create_folder {
    public $site;
    public $course;
    public $section;
    public $basePath;
    public $fullpath; // ← new: stores the final folder path

    public function __construct($input, $basePath = null) {
        global $CFG;

        // Default to Moodle dataroot if not provided
        $this->basePath = $basePath ?? $CFG->dataroot . '/customfolders';

        $parsed = $this->parseCourseInfo($input);

        if ($parsed) {
            $this->site = $parsed['site'];
            $this->course = $parsed['course'];
            $this->section = $parsed['section'];

            // Create the directory and save the full path
            $this->fullpath = $this->createFolders();
        } else {
            throw new \moodle_exception("Invalid course format in input: $input");
        }
    }

    private function parseCourseInfo($input) {
        if (preg_match('/\b([A-Z]{2})-([A-Z0-9]{3})([A-Z])\b/', $input, $matches)) {
            return [
                'site' => strtoupper($matches[1]),
                'course' => strtoupper($matches[2]),
                'section' => strtoupper($matches[3])
            ];
        }
        return null;
    }

    private function createFolders() {
        $path = "{$this->basePath}/{$this->site}/{$this->course}/{$this->section}";

        if (!is_dir($path)) {
            if (mkdir($path, 0777, true)) {
                debugging("✅ Folder created: $path", DEBUG_DEVELOPER);
            } else {
                debugging("❌ Failed to create folder: $path", DEBUG_DEVELOPER);
            }
        } else {
            debugging("⚠️ Folder already exists: $path", DEBUG_DEVELOPER);
        }

        return $path; // ← return the full path for later use
    }
}
