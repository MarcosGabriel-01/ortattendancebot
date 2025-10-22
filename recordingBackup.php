<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/downloader.php');
require_once(__DIR__ . '/nameNormalizer.php');
require_once(__DIR__ . '/folderCreation.php');
require_once(__DIR__ . '/fileProcessor.php');
require_once(__DIR__ . '/moodleMirroring.php');

define('ATTBOT_BASE', $CFG->dataroot . '/attendancebot');
define('RECORDINGS_DIR', $CFG->dataroot . '/attendancebot/recordings');

function recording_backup_run(int $courseid): void {
    //hardcoded mp4 and filename
    $url = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
    $filename = 'BE_FPR_C_' . date('Ymd') . '.mp4';

    try {
        $tmp_file = download_video($url,$filename);

        $normalized_path = normalize_file_name(pathinfo($tmp_file, PATHINFO_FILENAME));

        $final_path = move_to_folder($tmp_file, $normalized_path);

        upload_to_moodle($courseid, $final_path);

    } catch (Exception $e) {
        throw $e;
    }
}
