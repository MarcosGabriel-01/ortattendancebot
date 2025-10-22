<?php
/**
 * nameNormalizer.php
 * Normalizes course/video names into structured folder paths.
 */

function normalize_file_name(string $raw_name): string {
    // Example inputs:
    // YA-FPR-A-20251022 → YA/FPR/A/20251022
    // YA_FPR_A_20251022 → YA/FPR/A/20251022
    // Oficina de Alumnos → Oficina-de-Alumnos/20251022

    $name = trim($raw_name);

    // Unify separators to "-"
    $name = str_replace('_', '-', $name);

    // Handle "special" names (no YA-XXX- pattern)
    if (!preg_match('/^(YA|BE)-[A-Z0-9]+-[A-Z0-9]+-[0-9]{8}$/i', $name)) {
        // Replace spaces with hyphens and add today's date
        return str_replace(' ', '-', $name) . '/' . date('Ymd');
    }

    // Split by "-"
    $parts = explode('-', $name);

    // If the last part is the date (8 digits), extract it
    $date = '';
    if (preg_match('/^\d{8}$/', end($parts))) {
        $date = array_pop($parts);
    }

    // Join everything else as folders
    $folder_path = implode('/', $parts);

    // Append the date as a subfolder
    return $folder_path . '/' . $date;
}
