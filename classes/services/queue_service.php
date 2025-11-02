<?php
/**
 * Queue service - handles queue CRUD operations
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\services;

defined('MOODLE_INTERNAL') || die();

class queue_service {
    
    /**
     * Add meetings to queue
     * 
     * @param int $attendancebotid
     * @param array $meetings
     * @return array ['queued' => int, 'skipped' => int]
     */
    public function add_meetings($attendancebotid, $meetings) {
        global $DB;
        
        $queued = 0;
        $skipped = 0;
        
        foreach ($meetings as $meeting) {
            $meeting_time = strtotime($meeting['start_time']);
            
            $exists = $DB->record_exists('ortattendancebot_queue', [
                'attendancebotid' => $attendancebotid,
                'meeting_id' => $meeting['id']
            ]);
            
            if (!$exists) {
                $DB->insert_record('ortattendancebot_queue', [
                    'attendancebotid' => $attendancebotid,
                    'meeting_id' => $meeting['id'],
                    'meeting_date' => $meeting_time,
                    'processed' => 0,
                    'timecreated' => time()
                ]);
                $queued++;
            } else {
                $skipped++;
            }
        }
        
        return [
            'queued' => $queued,
            'skipped' => $skipped
        ];
    }
    
    /**
     * Get pending queue items
     * 
     * @param int $attendancebotid
     * @return array
     */
    public function get_pending($attendancebotid) {
        global $DB;
        return $DB->get_records('ortattendancebot_queue', [
            'attendancebotid' => $attendancebotid,
            'processed' => 0
        ]);
    }
    
    /**
     * Clear all queue items
     * 
     * @param int $attendancebotid
     * @return int Number deleted
     */
    public function clear_all($attendancebotid) {
        global $DB;
        $count = $DB->count_records('ortattendancebot_queue', ['attendancebotid' => $attendancebotid]);
        $DB->delete_records('ortattendancebot_queue', ['attendancebotid' => $attendancebotid]);
        return $count;
    }
    
    /**
     * Get all queue items for display
     * 
     * @param int $attendancebotid
     * @param int $limit
     * @return array
     */
    public function get_all($attendancebotid, $limit = 50) {
        global $DB;
        return $DB->get_records('ortattendancebot_queue', 
            ['attendancebotid' => $attendancebotid], 
            'meeting_date DESC', 
            '*', 
            0, 
            $limit
        );
    }
}
