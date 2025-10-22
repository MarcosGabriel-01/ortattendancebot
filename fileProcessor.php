<?php
require_once(__DIR__ . '/folderCreation.php');

function move_to_folder(string $filepath, string $normalized_path): string {
    $target_dir = RECORDINGS_DIR . '/' . $normalized_path;
    folder_ensure_path_exists($target_dir);
    $target_file = $target_dir . '/' . basename($filepath);
    rename($filepath, $target_file);
    return $target_file;
}
