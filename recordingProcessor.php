<?php

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/testApi.php'); // use our test connector
require_once(__DIR__ . '/folderCreation.php');

define('ATTBOT_BASE', $CFG->dataroot . '/attendancebot');
define('TMP_DIR', ATTBOT_BASE . '/tmp_downloads');
define('RECORDINGS_DIR', ATTBOT_BASE . '/recordings');

/**
 * Extract path variables (e.g. YA_PNT_B_20251020.mp4 → YA/PNT/B/20251020)
 */
function attbot_extract_path_from_filename(string $filename): string {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $parts = explode('_', $name);
    if (count($parts) < 4) {
        throw new Exception("Invalid filename format: $filename");
    }
    return "{$parts[0]}/{$parts[1]}/{$parts[2]}/{$parts[3]}";
}

/**
 * Move a file into its structured folder path.
 */
function attbot_move_to_structure(string $filepath): string {
    $filename = basename($filepath);
    $relpath = attbot_extract_path_from_filename($filename);
    $target_dir = RECORDINGS_DIR . '/' . $relpath;

    folder_ensure_path_exists($target_dir);

    $target_file = $target_dir . '/' . $filename;
    rename($filepath, $target_file);
    return $target_file;
}

// --- MAIN EXECUTION ---
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    try {
        $downloaded = testapi_download_sample_video(); // fetch from testApi
        $finalpath = attbot_move_to_structure($downloaded);
        echo "✅ File organized at: $finalpath\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
