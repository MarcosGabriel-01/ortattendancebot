<?php
/**
 * Meeting name normalization for recording backup
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ortattendancebot\backup;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalizes meeting names into folder paths
 */
class name_normalizer {
    
    /**
     * Main normalization function
     * 
     * @param string $raw_name Raw meeting name from Zoom
     * @param int $meeting_timestamp Meeting start time
     * @return array ['name' => normalized name, 'date' => YYYYMMDD, 'path' => folder structure]
     */
    public static function normalize_file_name($raw_name, $meeting_timestamp) {
        // Step 1: Remove bracket content
        $clean_name = self::remove_brackets($raw_name);
        
        // Step 2: Extract date
        $date = self::extract_date($clean_name, $meeting_timestamp);
        
        // Step 3: Detect if this is code-based or text-based
        $code = self::detect_code_pattern($clean_name);
        
        if ($code !== null) {
            // Code-based parsing
            return self::parse_code($code, $date);
        } else {
            // Text-based parsing
            return self::parse_text($clean_name, $date);
        }
    }
    
    /**
     * Remove content in brackets [...]
     * 
     * @param string $name
     * @return string
     */
    private static function remove_brackets($name) {
        return preg_replace('/\s*\[.*?\]\s*/', ' ', $name);
    }
    
    /**
     * Detect code pattern XX-YYYX (e.g., BE-MATB, din-be-21a)
     * 
     * @param string $name
     * @return string|null The detected code or null
     */
    private static function detect_code_pattern($name) {
        // Match patterns like: BE-MATB, BE-MAT1B, YA-THPE, din-be-21a
        if (preg_match('/\b([A-Z]{2,3}-[A-Z0-9]{3,4}[A-Z]?)\b/i', $name, $matches)) {
            return strtoupper($matches[1]);
        }
        return null;
    }
    
    /**
     * Parse code-based name (e.g., BE-MATB → BE-MAT-B)
     * 
     * @param string $code
     * @param string $date
     * @return array
     */
    private static function parse_code($code, $date) {
        // Split by hyphen
        $parts = explode('-', $code);
        
        if (count($parts) >= 2) {
            $prefix = $parts[0]; // BE
            $middle_and_suffix = $parts[1]; // MATB
            
            // Extract last character as suffix
            $suffix = substr($middle_and_suffix, -1); // B
            $middle = substr($middle_and_suffix, 0, -1); // MAT
            
            // Build normalized name: BE-MAT-B
            $normalized_name = $prefix . '-' . $middle . '-' . $suffix;
            
            // Build path: /recordings/BE/MAT/B/20251015/
            $path_parts = [$prefix, $middle, $suffix, $date];
            
            return [
                'name' => $normalized_name,
                'date' => $date,
                'path' => $path_parts
            ];
        }
        
        // Fallback if parsing fails
        return [
            'name' => $code,
            'date' => $date,
            'path' => [$code, $date]
        ];
    }
    
    /**
     * Parse text-based name (e.g., "Tendencias - CURSO A" → Tendencias-A)
     * Path: /recordings/Tendencias/A/20251015/
     * 
     * @param string $name
     * @param string $date
     * @return array
     */
    private static function parse_text($name, $date) {
        $name = trim($name);
        
        // Look for "CURSO X" pattern
        if (preg_match('/\bCURSO\s+([A-Z])\b/i', $name, $matches)) {
            $suffix = strtoupper($matches[1]); // A, B, C, etc.
            
            // Extract the base name before " - CURSO"
            $base_name = preg_replace('/\s*-\s*CURSO\s+[A-Z]\b/i', '', $name);
            $base_name = trim($base_name);
            
            // Remove any remaining special characters but keep letters and numbers
            $base_name = preg_replace('/[^A-Za-z0-9\s]/', '', $base_name);
            $base_name = preg_replace('/\s+/', ' ', $base_name); // Normalize spaces
            $base_name = trim($base_name);
            
            // Build normalized name: Tendencias-A
            $normalized_name = $base_name . '-' . $suffix;
            
            // Build path: /recordings/Tendencias/A/20251015/
            $path_parts = [$base_name, $suffix, $date];
            
            return [
                'name' => $normalized_name,
                'date' => $date,
                'path' => $path_parts
            ];
        }
        
        // No CURSO pattern found - use whole name
        $clean_name = preg_replace('/[^A-Za-z0-9\s]/', '', $name);
        $clean_name = preg_replace('/\s+/', '-', trim($clean_name));
        
        return [
            'name' => $clean_name,
            'date' => $date,
            'path' => [$clean_name, $date]
        ];
    }
    
    /**
     * Extract date from name or use meeting timestamp
     * 
     * @param string $name
     * @param int $timestamp
     * @return string YYYYMMDD format
     */
    private static function extract_date($name, $timestamp) {
        // Try to extract YYYYMMDD from name
        if (preg_match('/\b(20\d{6})\b/', $name, $matches)) {
            return $matches[1];
        }
        
        // Use meeting timestamp
        return date('Ymd', $timestamp);
    }
}
