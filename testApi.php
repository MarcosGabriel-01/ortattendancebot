<?php
/**
 * testApi.php
 * Downloads a sample MP4 video into /attendancebot/tmp_downloads/.
 */

require_once(__DIR__ . '/../../config.php');

$download_dir = $CFG->dataroot . '/attendancebot/tmp_downloads';

if (!file_exists($download_dir)) {
    mkdir($download_dir, 0777, true);
}

function fetch_video_from_url($url, $filename, $dir) {
    $target = rtrim($dir, '/') . '/' . basename($filename);
    $fp = fopen($target, 'w+');
    if (!$fp) {
        throw new Exception("Cannot open file for writing: $target");
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FAILONERROR => true
    ]);
    $success = curl_exec($ch);
    if (!$success) {
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        unlink($target);
        throw new Exception("Download failed: $error");
    }
    curl_close($ch);
    fclose($fp);

    return $target;
}

// Main function used by other scripts
function download_sample_video() {
    global $download_dir;
    $url      = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
    $filename = 'YA_PNT_B_20251020.mp4'; // simulated pattern for processing
    return fetch_video_from_url($url, $filename, $download_dir);
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    try {
        $downloaded = download_sample_video();
        echo "✅ Downloaded file: $downloaded\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
