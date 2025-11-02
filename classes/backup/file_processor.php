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

/**
 * Handles local file operations for recordings
 */
class file_processor {
    
    /**
     * Move file to organized folder structure
     * 
     * @param string $source_filepath Current file path
     * @param string $base_path Base recordings path
     * @param array $path_parts Path components [folder1, folder2, ...]
     * @param string $filename Final filename
     * @return string Final file path
     * @throws \Exception
     */
    public static function move_to_folder($source_filepath, $base_path, $path_parts, $filename) {
        // Build target directory
        $target_dir = $base_path;
        foreach ($path_parts as $part) {
            $target_dir .= '/' . $part;
        }
        
        // Ensure directory exists
        self::ensure_path_exists($target_dir);
        
        // Build final path
        $target_filepath = $target_dir . '/' . $filename;
        
        // Move file
        if (!rename($source_filepath, $target_filepath)) {
            throw new \Exception("Failed to move file from $source_filepath to $target_filepath");
        }
        
        return $target_filepath;
    }
    
    /**
     * Ensure path exists with proper permissions
     * 
     * @param string $path Directory path
     * @throws \Exception
     */
    public static function ensure_path_exists($path) {
        if (file_exists($path)) {
            if (!is_dir($path)) {
                throw new \Exception("Path exists but is not a directory: $path");
            }
            return;
        }
        
        // Create directory recursively
        if (!mkdir($path, 0775, true)) {
            throw new \Exception("Failed to create directory: $path");
        }
        
        // Verify it was created
        if (!is_dir($path)) {
            throw new \Exception("Directory was not created: $path");
        }
    }
    
    /**
     * Download file from URL to temporary location
     * 
     * @param string $url Download URL
     * @param string $temp_path Temporary file path
     * @return int Downloaded file size in bytes
     * @throws \Exception
     */
    public static function download_file($url, $temp_path) {
        $fp = fopen($temp_path, 'w+');
        if (!$fp) {
            throw new \Exception("Cannot open file for writing: $temp_path");
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600); // 1 hour timeout for large files
        
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
    
    /**
     * Get file size
     * 
     * @param string $filepath
     * @return int File size in bytes
     */
    public static function get_file_size($filepath) {
        if (!file_exists($filepath)) {
            return 0;
        }
        return filesize($filepath);
    }
    
    /**
     * Delete file safely
     * 
     * @param string $filepath
     * @return bool Success
     */
    public static function delete_file($filepath) {
        if (file_exists($filepath)) {
            return @unlink($filepath);
        }
        return true; // Already deleted
    }
}
