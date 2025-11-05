<?php
/**
 * Recording backup orchestrator
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\backup;

defined('MOODLE_INTERNAL') || die();

class recording_backup {
    
    
    private $courseid;
    
    
    private $base_path;
    
    
    private $delete_source;
    
    
    private $api_client;
    
    
    public function __construct($courseid, $base_path, $delete_source, $api_client) {
        $this->courseid = $courseid;
        $this->base_path = rtrim($base_path, '/');
        $this->delete_source = $delete_source;
        $this->api_client = $api_client;
    }
    
    
    public function process_backup($backup_item) {
        global $CFG, $DB;
        
        
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/backup/name_normalizer.php');
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/backup/file_processor.php');
        require_once($CFG->dirroot . '/mod/ortattendancebot/classes/backup/moodle_mirroring.php');
        
        $result = [
            'success' => false,
            'error' => null,
            'local_path' => null,
            'moodle_file_id' => null
        ];
        
        try {
            
            if (empty($backup_item->recording_url)) {
                $recordings = $this->api_client->get_recording_metadata($backup_item->meeting_id);
                
                if (empty($recordings)) {
                    throw new \Exception("No recordings found for meeting: " . $backup_item->meeting_id);
                }
                
                
                $selected = $this->select_recording($recordings);
                
                if (!$selected) {
                    throw new \Exception("No suitable MP4 recording found");
                }
                
                
                $backup_item->recording_id = $selected['id'];
                $backup_item->recording_url = $selected['download_url'];
                $backup_item->file_size = $selected['file_size'];
            }
            
            
            $temp_path = $this->base_path . '/temp/' . $backup_item->meeting_id . '.mp4';
            file_processor::ensure_path_exists(dirname($temp_path));
            
            $downloaded_size = file_processor::download_file($backup_item->recording_url, $temp_path);
            
            
            $meeting_timestamp = $backup_item->timecreated;
            $normalized = name_normalizer::normalize_file_name($backup_item->meeting_name, $meeting_timestamp);
            
            $filename = $normalized['name'] . '_' . $normalized['date'] . '.mp4';
            
            
            $local_path = file_processor::move_to_folder(
                $temp_path,
                $this->base_path,
                $normalized['path'],
                $filename
            );
            
            $result['local_path'] = $local_path;
            
            
            $folder_name = $normalized['name'];
            $subfolder_name = $normalized['date'];
            
            $moodle_file_id = moodle_mirroring::upload_to_moodle(
                $this->courseid,
                $folder_name,
                $subfolder_name,
                $filename,
                $local_path
            );
            
            $result['moodle_file_id'] = $moodle_file_id;
            
            
            if ($this->delete_source) {
                try {
                    $this->api_client->delete_recordings([[
                        'meeting_id' => $backup_item->meeting_id,
                        'recording_id' => $backup_item->recording_id
                    ]]);
                } catch (\Exception $e) {
                    
                    $this->add_to_cleanup_queue($backup_item, $e->getMessage());
                }
            }
            
            $result['success'] = true;
            
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            
            
            if (isset($temp_path) && file_exists($temp_path)) {
                file_processor::delete_file($temp_path);
            }
        }
        
        return $result;
    }
    
    
    private function select_recording($recordings) {
        $priority = [
            'shared_screen_with_speaker_view' => 3,
            'shared_screen_with_gallery_view' => 2,
            'active_speaker' => 1
        ];
        
        $mp4_recordings = [];
        
        foreach ($recordings as $rec) {
            if ($rec['file_type'] === 'MP4' && isset($rec['download_url'])) {
                $type = $rec['recording_type'] ?? 'active_speaker';
                $mp4_recordings[] = [
                    'id' => $rec['id'],
                    'download_url' => $rec['download_url'],
                    'file_size' => $rec['file_size'] ?? 0,
                    'type' => $type,
                    'priority' => $priority[$type] ?? 0
                ];
            }
        }
        
        if (empty($mp4_recordings)) {
            return null;
        }
        
        
        usort($mp4_recordings, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        return $mp4_recordings[0];
    }
    
    
    private function add_to_cleanup_queue($backup_item, $error_message) {
        global $DB;
        
        $cleanup = new \stdClass();
        $cleanup->attendancebotid = $backup_item->attendancebotid;
        $cleanup->meeting_id = $backup_item->meeting_id;
        $cleanup->recording_id = $backup_item->recording_id;
        $cleanup->attempts = 0;
        $cleanup->deleted = 0;
        $cleanup->error_message = $error_message;
        $cleanup->timecreated = time();
        
        $DB->insert_record('ortattendancebot_cleanup_queue', $cleanup);
    }
}