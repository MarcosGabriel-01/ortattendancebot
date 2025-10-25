<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/zoomManager.php');
require_once(__DIR__ . '/nameNormalizer.php');
require_once(__DIR__ . '/fileProcessor.php');
require_once(__DIR__ . '/moodleMirroring.php');

function recording_backup_run(int $courseid, string $meeting_id, bool $delete_cloud = false): void {
    try {
        $recordingData = fetchRecording($meeting_id);

        $file_data = normalize_file_name($recordingData['name']);

        $final_path = move_to_folder($recordingData['tmp_file'], $file_data['path']);

        upload_to_moodle($courseid, $final_path);

        if ($delete_cloud) {
            $recordingData['meeting_id'] = $meeting_id;
            cloudDelete($recordingData);
            echo "✅ Zoom cloud recording deleted for meeting {$meeting_id}\n";
        }

    } catch (Exception $e) {
        echo "❌ Error processing meeting {$meeting_id}: " . $e->getMessage() . "\n";
    }
}
