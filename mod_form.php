<?php
/**
 * Instance configuration form
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/ortattendancebot/lib.php');

class mod_ortattendancebot_mod_form extends moodleform_mod {
    
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        
        // General
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        
        $this->standard_intro_elements();
        
        // Attendance Configuration
        $mform->addElement('header', 'config', get_string('configuration', 'mod_ortattendancebot'));
        
        $mform->addElement('selectyesno', 'enabled', get_string('enabled', 'mod_ortattendancebot'));
        $mform->setDefault('enabled', 1);
        $mform->addHelpButton('enabled', 'enabled', 'mod_ortattendancebot');
        
        // Camera settings
        $mform->addElement('selectyesno', 'camera_required', get_string('camera_required', 'mod_ortattendancebot'));
        $mform->setDefault('camera_required', 1);
        $mform->addHelpButton('camera_required', 'camera_required', 'mod_ortattendancebot');
        
        $mform->addElement('text', 'camera_threshold', get_string('camera_threshold', 'mod_ortattendancebot'), ['size' => '10']);
        $mform->setType('camera_threshold', PARAM_INT);
        $mform->setDefault('camera_threshold', 60);
        $mform->addHelpButton('camera_threshold', 'camera_threshold', 'mod_ortattendancebot');
        $mform->disabledIf('camera_threshold', 'camera_required', 'eq', 0);
        
        // Attendance settings
        $mform->addElement('text', 'min_percentage', get_string('min_percentage', 'mod_ortattendancebot'), ['size' => '10']);
        $mform->setType('min_percentage', PARAM_INT);
        $mform->setDefault('min_percentage', 75);
        $mform->addHelpButton('min_percentage', 'min_percentage', 'mod_ortattendancebot');
        
        $mform->addElement('text', 'late_tolerance', get_string('late_tolerance', 'mod_ortattendancebot'), ['size' => '10']);
        $mform->setType('late_tolerance', PARAM_INT);
        $mform->setDefault('late_tolerance', 15);
        $mform->addHelpButton('late_tolerance', 'late_tolerance', 'mod_ortattendancebot');
        
        // Date/Time ranges
        $mform->addElement('header', 'datetime', get_string('datetime_range', 'mod_ortattendancebot'));
        
        $mform->addElement('date_selector', 'start_date', get_string('start_date', 'mod_ortattendancebot'));
        $mform->addElement('date_selector', 'end_date', get_string('end_date', 'mod_ortattendancebot'));
        
        // Time range for daily classes
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = sprintf('%02d', $i);
        }
        $minutes = ['00' => '00', '15' => '15', '30' => '30', '45' => '45'];
        
        $starttime = [];
        $starttime[] = $mform->createElement('select', 'start_hour', '', $hours);
        $starttime[] = $mform->createElement('select', 'start_minute', '', $minutes);
        $mform->addGroup($starttime, 'start_time', get_string('start_time', 'mod_ortattendancebot'), ' : ', false);
        $mform->setDefault('start_hour', 8);
        $mform->setDefault('start_minute', '00');
        
        $endtime = [];
        $endtime[] = $mform->createElement('select', 'end_hour', '', $hours);
        $endtime[] = $mform->createElement('select', 'end_minute', '', $minutes);
        $mform->addGroup($endtime, 'end_time', get_string('end_time', 'mod_ortattendancebot'), ' : ', false);
        $mform->setDefault('end_hour', 18);
        $mform->setDefault('end_minute', '00');
        
        // Recording Backup Section
        $mform->addElement('header', 'recordings', get_string('recordings_backup', 'mod_ortattendancebot'));
        
        $mform->addElement('selectyesno', 'backup_recordings', get_string('backup_recordings', 'mod_ortattendancebot'));
        $mform->setDefault('backup_recordings', 0);
        $mform->addHelpButton('backup_recordings', 'backup_recordings', 'mod_ortattendancebot');
        
        $defaultpath = $CFG->dataroot . '/ortattendancebot_recordings';
        $mform->addElement('text', 'recordings_path', get_string('recordings_path', 'mod_ortattendancebot'), ['size' => '64']);
        $mform->setType('recordings_path', PARAM_TEXT);
        $mform->setDefault('recordings_path', $defaultpath);
        $mform->addHelpButton('recordings_path', 'recordings_path', 'mod_ortattendancebot');
        $mform->disabledIf('recordings_path', 'backup_recordings', 'eq', 0);
        
        $mform->addElement('selectyesno', 'delete_source', get_string('delete_source', 'mod_ortattendancebot'));
        $mform->setDefault('delete_source', 0);
        $mform->addHelpButton('delete_source', 'delete_source', 'mod_ortattendancebot');
        $mform->disabledIf('delete_source', 'backup_recordings', 'eq', 0);
        
        // Standard elements
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate recordings path if backup is enabled
        if (!empty($data['backup_recordings'])) {
            $path = $data['recordings_path'];
            
            if (empty($path)) {
                $errors['recordings_path'] = get_string('error_path_empty', 'mod_ortattendancebot');
            } else {
                // Check if path exists or can be created
                if (!file_exists($path)) {
                    if (!@mkdir($path, 0775, true)) {
                        $errors['recordings_path'] = get_string('error_path_not_writable', 'mod_ortattendancebot');
                    }
                } else if (!is_writable($path)) {
                    $errors['recordings_path'] = get_string('error_path_not_writable', 'mod_ortattendancebot');
                }
            }
        }
        
        return $errors;
    }
    
    public function data_preprocessing(&$default_values) {
        if (isset($default_values['start_time'])) {
            $default_values['start_hour'] = floor($default_values['start_time'] / 3600);
            $default_values['start_minute'] = ($default_values['start_time'] % 3600) / 60;
        }
        if (isset($default_values['end_time'])) {
            $default_values['end_hour'] = floor($default_values['end_time'] / 3600);
            $default_values['end_minute'] = ($default_values['end_time'] % 3600) / 60;
        }
    }
    
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        
        if (isset($data->start_hour) && isset($data->start_minute)) {
            $data->start_time = ortattendancebot_time_to_seconds($data->start_hour, $data->start_minute);
        }
        if (isset($data->end_hour) && isset($data->end_minute)) {
            $data->end_time = ortattendancebot_time_to_seconds($data->end_hour, $data->end_minute);
        }
    }
}
