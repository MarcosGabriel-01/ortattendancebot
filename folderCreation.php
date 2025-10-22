<?php
function folder_ensure_path_exists(string $path): void {
    if (!file_exists($path)) mkdir($path, 0777, true);
}
