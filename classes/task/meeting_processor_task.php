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
        
        
        $config = $DB->get_record('ortattendancebot', ['id' => $attendancebotid], '*', MUST_EXIST);
        
        
        $this->process_attendance_queue($config, $courseid, $attendancebotid);
        
        
        if ($config->backup_recordings) {
            $this->process_backup_queue($config, $courseid, $attendancebotid);
        }
        
        mtrace("\n=== Processing completed ===");
    }
    
    
    private function process_attendance_queue($config, $courseid, $attendancebotid) {
        global $DB;
        
        mtrace("\n--- ATTENDANCE QUEUE ---");
        
        
        $queued = $DB->get_records('ortattendancebot_queue', [
            'attendancebotid' => $attendancebotid,
            'processed' => 0
        ], 'meeting_date ASC');
        
        $count = count($queued);
        mtrace("Processing $count attendance items");
        
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
                
                
                $queue_item->processed = 1;
                $queue_item->timeprocessed = time();
                $DB->update_record('ortattendancebot_queue', $queue_item);
                
                mtrace("✓ Attendance recorded");
                
                
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
    
    
    private function process_backup_queue($config, $courseid, $attendancebotid) {
        global $DB;
        
        mtrace("\n--- BACKUP QUEUE ---");
        
        
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
        
        
        require_once(__DIR__ . '/../api/client_connection.php');
        $api_client = \mod_ortattendancebot\api\client_connection::get_client();
        
        
        require_once(__DIR__ . '/../backup/recording_backup.php');
        $backup_processor = new \mod_ortattendancebot\backup\recording_backup(
            $courseid,
            $config->recordings_path,
            $config->delete_source,
            $api_client
        );
        
        foreach ($queued as $backup_item) {
            try {
                mtrace("\nBacking up meeting: {$backup_item->meeting_id}");
                
                
                $backup_item->attempts++;
                $backup_item->last_attempt = time();
                $DB->update_record('ortattendancebot_backup_queue', $backup_item);
                
                
                $result = $backup_processor->process_backup($backup_item);
                
                if ($result['success']) {
                    
                    $backup_item->backed_up = 1;
                    $backup_item->local_path = $result['local_path'];
                    $backup_item->moodle_file_id = $result['moodle_file_id'];
                    $backup_item->error_message = null;
                    $backup_item->timemodified = time();
                    $DB->update_record('ortattendancebot_backup_queue', $backup_item);
                    
                    mtrace("✓ Recording backed up successfully");
                } else {
                    
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
                
                
                $backup_item->error_message = $e->getMessage();
                $backup_item->timemodified = time();
                $DB->update_record('ortattendancebot_backup_queue', $backup_item);
                
                continue;
            }
        }
    }
    
    
    private function add_to_backup_queue($queue_item, $attendancebotid) {
        global $DB;
        
        
        $exists = $DB->record_exists('ortattendancebot_backup_queue', [
            'attendancebotid' => $attendancebotid,
            'meeting_id' => $queue_item->meeting_id
        ]);
        
        if ($exists) {
            return; 
        }
        
        $backup = new \stdClass();
        $backup->attendancebotid = $attendancebotid;
        $backup->meeting_id = $queue_item->meeting_id;
        $backup->meeting_name = ''; 
        $backup->attempts = 0;
        $backup->backed_up = 0;
        $backup->timecreated = time();
        
        $DB->insert_record('ortattendancebot_backup_queue', $backup);
    }
}