<?php
require_once(__DIR__ . '/../../config.php');

$DOWNLOAD_DIR = $CFG->dataroot . '/attendancebot/tmp_downloads';
if (!file_exists($DOWNLOAD_DIR)) mkdir($DOWNLOAD_DIR, 0777, true);

function download_video($url, $filename): string {
    global $DOWNLOAD_DIR;
    $target = $DOWNLOAD_DIR . '/' . $filename;

    $fp = fopen($target, 'w+');
    if (!$fp) throw new Exception("Cannot open file for writing: $target");

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FAILONERROR => true
    ]);
    if (!curl_exec($ch)) {
        $err = curl_error($ch);
        curl_close($ch); fclose($fp); unlink($target);
        throw new Exception("Download failed: $err");
    }
    curl_close($ch); fclose($fp);

    return $target;
}
