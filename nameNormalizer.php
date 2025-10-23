<?php
function normalize_file_name(string $raw_name): array {
    $name = trim($raw_name);
    $name = str_replace('_', '-', $name);

    [$name_without_date, $date] = extract_name_and_date($name);

    if (is_special_name($name_without_date)) {
        $folder_path = handle_special_name($name_without_date, $date);
    } else {
        $folder_path = handle_standard_name($name_without_date, $date);
    }

    return [
        'name' => $name_without_date,
        'date' => $date,
        'path' => $folder_path
    ];
}

function is_special_name(string $name_without_date): bool {
    return !preg_match('/^(YA|BE)-[A-Z0-9]+-[A-Z0-9]+$/i', $name_without_date);
}

function extract_name_and_date(string $name): array {
    $parts = explode('-', $name);
    $date = '';

    if (!empty($parts) && preg_match('/^\d{8}$/', end($parts))) {
        $date = array_pop($parts);
    }

    $name_without_date = implode('-', $parts);
    $name_without_date = trim(preg_replace('/\s+/', '-', $name_without_date));

    return [$name_without_date, $date];
}

function handle_special_name(string $name_without_date, string $date): string {
    $folder_name = str_replace(' ', '-', $name_without_date);
    return $folder_name . '/' . $date;
}

function handle_standard_name(string $name_without_date, string $date): string {
    $folder_path = implode('/', explode('-', strtoupper($name_without_date))) . '/' . $date;
    return $folder_path;
}
