<?php
/**
 * folderCreation.php
 * Utility helpers for local directory management.
 */

function folder_ensure_path_exists(string $path): void {
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}
