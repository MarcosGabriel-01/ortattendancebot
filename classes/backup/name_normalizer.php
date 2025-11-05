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

class name_normalizer {
    
    
    public static function normalize_file_name($raw_name, $meeting_timestamp) {
        
        $clean_name = self::remove_brackets($raw_name);
        
        
        $date = self::extract_date($clean_name, $meeting_timestamp);
        
        
        $code = self::detect_code_pattern($clean_name);
        
        if ($code !== null) {
            
            return self::parse_code($code, $date);
        } else {
            
            return self::parse_text($clean_name, $date);
        }
    }
    
    
    private static function remove_brackets($name) {
        return preg_replace('/\s*\[.*?\]\s*/', ' ', $name);
    }
    
    
    private static function detect_code_pattern($name) {
        
        if (preg_match('/\b([A-Z]{2,3}-[A-Z0-9]{3,4}[A-Z]?)\b/i', $name, $matches)) {
            return strtoupper($matches[1]);
        }
        return null;
    }
    
    
    private static function parse_code($code, $date) {
        
        $parts = explode('-', $code);
        
        if (count($parts) >= 2) {
            $prefix = $parts[0]; 
            $middle_and_suffix = $parts[1]; 
            
            
            $suffix = substr($middle_and_suffix, -1); 
            $middle = substr($middle_and_suffix, 0, -1); 
            
            
            $normalized_name = $prefix . '-' . $middle . '-' . $suffix;
            
            
            $path_parts = [$prefix, $middle, $suffix, $date];
            
            return [
                'name' => $normalized_name,
                'date' => $date,
                'path' => $path_parts
            ];
        }
        
        
        return [
            'name' => $code,
            'date' => $date,
            'path' => [$code, $date]
        ];
    }
    
    
    private static function parse_text($name, $date) {
        $name = trim($name);
        
        
        if (preg_match('/\bCURSO\s+([A-Z])\b/i', $name, $matches)) {
            $suffix = strtoupper($matches[1]); 
            
            
            $base_name = preg_replace('/\s*-\s*CURSO\s+[A-Z]\b/i', '', $name);
            $base_name = trim($base_name);
            
            
            $base_name = preg_replace('/[^A-Za-z0-9\s]/', '', $base_name);
            $base_name = preg_replace('/\s+/', ' ', $base_name); 
            $base_name = trim($base_name);
            
            
            $normalized_name = $base_name . '-' . $suffix;
            
            
            $path_parts = [$base_name, $suffix, $date];
            
            return [
                'name' => $normalized_name,
                'date' => $date,
                'path' => $path_parts
            ];
        }
        
        
        $clean_name = preg_replace('/[^A-Za-z0-9\s]/', '', $name);
        $clean_name = preg_replace('/\s+/', '-', trim($clean_name));
        
        return [
            'name' => $clean_name,
            'date' => $date,
            'path' => [$clean_name, $date]
        ];
    }
    
    
    private static function extract_date($name, $timestamp) {
        
        if (preg_match('/\b(20\d{6})\b/', $name, $matches)) {
            return $matches[1];
        }
        
        
        return date('Ymd', $timestamp);
    }
}
