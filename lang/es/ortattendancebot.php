<?php
/**
 * Cadenas de idioma en español
 *
 * @package     mod_ortattendancebot
 * @copyright   2025 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'ORT Bot de Asistencia';
$string['modulename'] = 'ORT Bot de Asistencia';
$string['modulenameplural'] = 'ORT Bots de Asistencia';
$string['modulename_help'] = 'Sincroniza automáticamente la asistencia de reuniones Zoom al módulo de Asistencia de Moodle y respalda las grabaciones';
$string['pluginadministration'] = 'Administración del ORT Bot de Asistencia';
$string['ortattendancebot:addinstance'] = 'Agregar un nuevo ORT Bot de Asistencia';
$string['ortattendancebot:view'] = 'Ver ORT Bot de Asistencia';
$string['api_settings'] = 'Configuración de API';
$string['api_settings_desc'] = 'Configurar acceso a la API de Zoom';
$string['zoom_api_base_url'] = 'URL Base de la API de Zoom';
$string['zoom_api_base_url_desc'] = 'URL base para la API de Zoom (predeterminado: https://api.zoom.us/v2)';
$string['zoom_oauth_token'] = 'Token OAuth de Zoom';
$string['zoom_oauth_token_desc'] = 'Token OAuth Server-to-Server para la API de Zoom';
$string['mock_api_url'] = 'URL de API de Prueba';
$string['mock_api_url_desc'] = 'URL para API de prueba (solo para testing)';
$string['use_mock_api'] = 'Usar API de Prueba';
$string['use_mock_api_desc'] = 'Habilitar para usar API de prueba en lugar de la API real de Zoom';
$string['configuration'] = 'Configuración';
$string['enabled'] = 'Habilitado';
$string['enabled_help'] = 'Habilitar procesamiento automático de asistencia';
$string['disabled'] = 'Deshabilitado';
$string['camera_required'] = 'Cámara Requerida';
$string['camera_required_help'] = 'Requerir que la cámara esté encendida para registrar asistencia';
$string['camera_threshold'] = 'Umbral de Cámara (%)';
$string['camera_threshold_help'] = 'Porcentaje mínimo de tiempo que la cámara debe estar encendida';
$string['min_percentage'] = 'Asistencia Mínima (%)';
$string['min_percentage_help'] = 'Porcentaje mínimo de asistencia para ser marcado como presente';
$string['late_tolerance'] = 'Tolerancia de Retraso (minutos)';
$string['late_tolerance_help'] = 'Minutos después de la hora de inicio antes de ser marcado como tarde';
$string['datetime_range'] = 'Rango de Fecha/Hora';
$string['start_date'] = 'Fecha de Inicio';
$string['end_date'] = 'Fecha de Fin';
$string['start_time'] = 'Hora de Inicio Diaria';
$string['end_time'] = 'Hora de Fin Diaria';
$string['recordings_backup'] = 'Respaldo de Grabaciones';
$string['backup_recordings'] = 'Habilitar Respaldo de Grabaciones';
$string['backup_recordings_help'] = 'Descargar y respaldar automáticamente las grabaciones en la nube de Zoom a Moodle';
$string['recordings_path'] = 'Ruta Local de Grabaciones';
$string['recordings_path_help'] = 'Ruta del sistema de archivos local donde se almacenarán las grabaciones antes de subirlas a Moodle. Debe tener permisos de escritura para el servidor web.';
$string['delete_source'] = 'Eliminar de Zoom Después del Respaldo';
$string['delete_source_help'] = 'Eliminar automáticamente las grabaciones de la nube de Zoom después de un respaldo exitoso a Moodle';
$string['error_path_empty'] = 'La ruta de grabaciones no puede estar vacía cuando el respaldo está habilitado';
$string['error_path_not_writable'] = 'No se puede escribir en la ruta de grabaciones. Por favor verifique los permisos del directorio.';
$string['scheduler_task'] = 'Programador del ORT Bot de Asistencia';
$string['meeting_processor_task'] = 'Procesador de Reuniones y Grabaciones';
$string['status'] = 'Estado';
$string['last_meeting'] = 'Última Reunión';
$string['processed'] = 'Procesado';
$string['no_instances'] = 'No hay instancias del ORT Bot de Asistencia en este curso';
$string['zoom_host_email'] = 'Email del Anfitrión de Zoom';
$string['zoom_host_email_desc'] = 'Dirección de correo electrónico del usuario de Zoom cuyas reuniones desea rastrear. Requerido para la API real de Zoom. Ejemplo: profesor@universidad.edu';
