<?php
// runAttendanceBot.php
// CLI script to orchestrate video download, folder creation, and Moodle upload

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');  // Moodle config
require_once(__DIR__ . '/testApi.php');
require_once(__DIR__ . '/folderCreation.php');
require_once(__DIR__ . '/recordingProcessor.php');
require_once(__DIR__ . '/moodleMirroring.php');

echo "=== AttendanceBot Pipeline Start ===\n";

try {
    // 1️⃣ Download video from test API
    $url = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
    $tempFolder = ATTBOT_BASEFOLDER . '/temp';
    echo "Downloading video...\n";
    $downloadedFile = download_sample_video($url, $tempFolder);
    echo "Downloaded to: $downloadedFile\n";

    // 2️⃣ Create folder structure based on filename
    $targetFolder = create_folder_from_filename(basename($downloadedFile));
    echo "Folder created: $targetFolder\n";

    // 3️⃣ Move the downloaded video into structured folder
    $targetFile = $targetFolder . '/' . basename($downloadedFile);
    rename($downloadedFile, $targetFile);
    echo "Moved file to: $targetFile\n";

    // 4️⃣ Mirror leaf folder into Moodle
    // Hardcoded course 21
    $courseid = 21;
    $res = attbot_process_leaf_folder($courseid, $targetFolder);
    echo "Moodle Upload: Processed {$res['processed']} folder(s), Uploaded {$res['uploaded']} file(s)\n";

    echo "=== AttendanceBot Pipeline Complete ===\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
