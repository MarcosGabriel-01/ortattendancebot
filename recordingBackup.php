<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/downloader.php');
require_once(__DIR__ . '/nameNormalizer.php');
require_once(__DIR__ . '/fileProcessor.php');
require_once(__DIR__ . '/moodleMirroring.php');

define('ATTBOT_BASE', $CFG->dataroot . '/attendancebot');
define('RECORDINGS_DIR', $CFG->dataroot . '/attendancebot/recordings');

function recording_backup_run(int $courseid): void {
    // por ahora solo toma casos como 'Oficina de Alumnos_'
    // necesita el _ para funcionar
    // YA_PNT_A_
    $url = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
    $filename = 'Oficina de Alumnos_' . date('Ymd') . '.mp4';

    try {
        $tmp_file = download_video($url, $filename);

        $file_data = normalize_file_name(pathinfo($tmp_file, PATHINFO_FILENAME));

        $final_path = move_to_folder($tmp_file, $file_data['path']);

        upload_to_moodle($courseid, $final_path, $file_data);

        echo "✅ Backup completed for course ID {$courseid}\n";

    } catch (Exception $e) {
        echo "❌ Error during backup: " . $e->getMessage() . "\n";
        throw $e;
    }
}
