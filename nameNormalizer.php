<?php
function normalize_file_name(string $raw_name): string {
    $name = trim($raw_name);
    $name = str_replace('_', '-', $name);

    $name_date = separateName($name);

    if (is_special_name($name_date[0])) {
        return special_handler($name_date[0], $name_date[1]);
    } else {
        return normal_handler($name_date[0], $name_date[1]);
    }
}

function separateName(string $name): array {
    $parts = explode('-', $name);
    $date = '';
    if (!empty($parts) && preg_match('/^\d{8}$/', end($parts))) {
        $date = array_pop($parts);
    }
    $name_part = implode('-', $parts);
    return [$name_part, $date];
}

function is_special_name(string $name): bool {
    return !preg_match('/^(YA|BE)-[A-Z0-9]+-[A-Z0-9]+$/i', $name);
}

function special_handler(string $name, string $date): string {
    $folder_name = strtolower(str_replace(' ', '-', $name));
    return $folder_name . '/' . $date;
}

function normal_handler(string $name, string $date): string {
    $parts = explode('-', $name);
    $folder_path = implode('/', $parts);
    return $folder_path . '/' . $date;
}
