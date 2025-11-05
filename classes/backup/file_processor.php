<?php
/**
 * File processor for recording backup
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\backup;

defined('MOODLE_INTERNAL') || die();

class file_processor {
    
    
    public static function move_to_folder($source_filepath, $base_path, $path_parts, $filename) {
        
        $target_dir = $base_path;
        foreach ($path_parts as $part) {
            $target_dir .= '/' . $part;
        }
        
        
        self::ensure_path_exists($target_dir);
        
        
        $target_filepath = $target_dir . '/' . $filename;
        
        
        if (!rename($source_filepath, $target_filepath)) {
            throw new \Exception("Failed to move file from $source_filepath to $target_filepath");
        }
        
        return $target_filepath;
    }
    
    
    public static function ensure_path_exists($path) {
        if (file_exists($path)) {
            if (!is_dir($path)) {
                throw new \Exception("Path exists but is not a directory: $path");
            }
            return;
        }
        
        
        if (!mkdir($path, 0775, true)) {
            throw new \Exception("Failed to create directory: $path");
        }
        
        
        if (!is_dir($path)) {
            throw new \Exception("Directory was not created: $path");
        }
    }
    
    
    public static function download_file($url, $temp_path) {
        $fp = fopen($temp_path, 'w+');
        if (!$fp) {
            throw new \Exception("Cannot open file for writing: $temp_path");
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600); 
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $file_size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        
        curl_close($ch);
        fclose($fp);
        
        if (!$result || $http_code !== 200) {
            @unlink($temp_path);
            throw new \Exception("Download failed with HTTP code: $http_code");
        }
        
        if ($file_size === 0) {
            @unlink($temp_path);
            throw new \Exception("Downloaded file is empty");
        }
        
        return (int)$file_size;
    }
    
    
    public static function get_file_size($filepath) {
        if (!file_exists($filepath)) {
            return 0;
        }
        return filesize($filepath);
    }
    
    
    public static function delete_file($filepath) {
        if (file_exists($filepath)) {
            return @unlink($filepath);
        }
        return true; 
    }
}
