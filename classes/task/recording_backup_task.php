<?php
namespace mod_attendancebot\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task that runs the recording backup each night.
 */
class recording_backup_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('recordingbackup', 'mod_attendancebot');
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/attendancebot/recordingBackup.php');
        mtrace("▶️ Starting nightly recording backup...");

        try {
            $courseid = required_param('courseid', PARAM_INT);
            recording_backup_run($courseid);
            mtrace("✅ Recording backup completed successfully.");
        } catch (\Exception $e) {
            mtrace("❌ Recording backup failed: " . $e->getMessage());
        }

        return true;
    }
}
