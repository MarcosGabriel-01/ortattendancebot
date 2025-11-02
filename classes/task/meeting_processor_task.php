<?php
/**
 * Adhoc task to process queued meetings and recordings
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\task;

defined('MOODLE_INTERNAL') || die();

class meeting_processor_task extends \core\task\adhoc_task {
    
    const ATTENDANCE_BATCH_SIZE = 25;
    const BACKUP_BATCH_SIZE = 5;
    
    public function get_name() {
        return get_string('meeting_processor_task', 'mod_ortattendancebot');
    }
    
    public function execute() {
        global $DB;
        
        $data = $this->get_custom_data();
        $attendancebotid = $data->attendancebotid;
        $courseid = $data->courseid;
        
        mtrace("=== Processing for ortattendancebot ID: $attendancebotid ===");
        
        // Get configuration
        $config = $DB->get_record('ortattendancebot', ['id' => $attendancebotid], '*', MUST_EXIST);
        
        // Step 1: Process attendance queue (limit 25)
        $this->process_attendance_queue($config, $courseid, $attendancebotid);
        
        // Step 2: Process backup queue (limit 5) - only if backup enabled
        if ($config->backup_recordings) {
            $this->process_backup_queue($config, $courseid, $attendancebotid);
        }
        
        mtrace("\n=== Processing completed ===");
    }
    
    /**
     * Process attendance queue
     */
    private function process_attendance_queue($config, $courseid, $attendancebotid) {
        global $DB;
        
        mtrace("\n--- ATTENDANCE QUEUE ---");
        
        // Get unprocessed meetings (limit 25)
        $queued = $DB->get_records('ortattendancebot_queue', [
            'attendancebotid' => $attendancebotid,
            'processed' => 0
        ], 'meeting_date ASC', '*', 0, self::ATTENDANCE_BATCH_SIZE);
        
        $count = count($queued);
        mtrace("Processing $count attendance items (max " . self::ATTENDANCE_BATCH_SIZE . ")");
        
        if ($count === 0) {
            mtrace("No attendance items to process");
            return;
        }
        
        require_once(__DIR__ . '/../processor/meeting_processor.php');
        $processor = new \mod_ortattendancebot\processor\meeting_processor($config, $courseid);
        
        foreach ($queued as $queue_item) {
            try {
                mtrace("\nProcessing meeting: {$queue_item->meeting_id}");
                
                $processor->process_meeting($queue_item->meeting_id);
                
                // Mark as processed
                $queue_item->processed = 1;
                $queue_item->timeprocessed = time();
                $DB->update_record('ortattendancebot_queue', $queue_item);
                
                mtrace("✓ Attendance recorded");
                
                // Add to backup queue if backup enabled
                if ($config->backup_recordings) {
                    $this->add_to_backup_queue($queue_item, $attendancebotid);
                    mtrace("✓ Added to backup queue");
                }
                
            } catch (\Exception $e) {
                mtrace("✗ ERROR: " . $e->getMessage());
                continue;
            }
        }
    }
    
    /**
     * Process backup queue
     */
    private function process_backup_queue($config, $courseid, $attendancebotid) {
        global $DB;
        
        mtrace("\n--- BACKUP QUEUE ---");
        
        // Get pending backups (not yet backed up, less than 3 attempts) - limit 5
        $queued = $DB->get_records_select(
            'ortattendancebot_backup_queue',
            'attendancebotid = ? AND backed_up = 0 AND attempts < 3',
            [$attendancebotid],
            'timecreated ASC',
            '*',
            0,
            self::BACKUP_BATCH_SIZE
        );
        
        $count = count($queued);
        mtrace("Processing $count backup items (max " . self::BACKUP_BATCH_SIZE . ")");
        
        if ($count === 0) {
            mtrace("No backup items to process");
            return;
        }
        
        // Initialize Zoom client
        require_once(__DIR__ . '/../api/zoom_client.php');
        $zoom_client = new \mod_ortattendancebot\api\zoom_client();
        
        // Initialize backup processor
        require_once(__DIR__ . '/../backup/recording_backup.php');
        $backup_processor = new \mod_ortattendancebot\backup\recording_backup(
            $courseid,
            $config->recordings_path,
            $config->delete_source,
            $zoom_client
        );
        
        foreach ($queued as $backup_item) {
            try {
                mtrace("\nBacking up meeting: {$backup_item->meeting_id}");
                
                // Increment attempts
                $backup_item->attempts++;
                $backup_item->last_attempt = time();
                $DB->update_record('ortattendancebot_backup_queue', $backup_item);
                
                // Process backup
                $result = $backup_processor->process_backup($backup_item);
                
                if ($result['success']) {
                    // Mark as backed up
                    $backup_item->backed_up = 1;
                    $backup_item->local_path = $result['local_path'];
                    $backup_item->moodle_file_id = $result['moodle_file_id'];
                    $backup_item->error_message = null;
                    $backup_item->timemodified = time();
                    $DB->update_record('ortattendancebot_backup_queue', $backup_item);
                    
                    mtrace("✓ Recording backed up successfully");
                } else {
                    // Update error
                    $backup_item->error_message = $result['error'];
                    $backup_item->timemodified = time();
                    $DB->update_record('ortattendancebot_backup_queue', $backup_item);
                    
                    if ($backup_item->attempts >= 3) {
                        mtrace("✗ FAILED after 3 attempts: " . $result['error']);
                    } else {
                        mtrace("✗ RETRY ({$backup_item->attempts}/3): " . $result['error']);
                    }
                }
                
            } catch (\Exception $e) {
                mtrace("✗ ERROR: " . $e->getMessage());
                
                // Update error message
                $backup_item->error_message = $e->getMessage();
                $backup_item->timemodified = time();
                $DB->update_record('ortattendancebot_backup_queue', $backup_item);
                
                continue;
            }
        }
    }
    
    /**
     * Add meeting to backup queue
     */
    private function add_to_backup_queue($queue_item, $attendancebotid) {
        global $DB;
        
        // Check if already in backup queue
        $exists = $DB->record_exists('ortattendancebot_backup_queue', [
            'attendancebotid' => $attendancebotid,
            'meeting_id' => $queue_item->meeting_id
        ]);
        
        if ($exists) {
            return; // Already queued
        }
        
        $backup = new \stdClass();
        $backup->attendancebotid = $attendancebotid;
        $backup->meeting_id = $queue_item->meeting_id;
        $backup->meeting_name = ''; // Will be fetched from Zoom API
        $backup->attempts = 0;
        $backup->backed_up = 0;
        $backup->timecreated = time();
        
        $DB->insert_record('ortattendancebot_backup_queue', $backup);
    }
}
