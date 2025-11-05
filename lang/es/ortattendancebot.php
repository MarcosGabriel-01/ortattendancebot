<?php
/**
 * Cadenas de idioma en español
 *
 * @package     mod_ortattendancebot
 * @copyright   2025
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'ORT Bot de Asistencia';
$string['modulename'] = 'ORT Bot de Asistencia';
$string['modulenameplural'] = 'ORT Bots de Asistencia';
$string['modulename_help'] = 'Sincroniza automáticamente la asistencia de reuniones de Zoom con el módulo de Asistencia de Moodle y respalda las grabaciones.';
$string['pluginadministration'] = 'Administración del ORT Bot de Asistencia';

$string['ortattendancebot:addinstance'] = 'Agregar un nuevo ORT Bot de Asistencia';
$string['ortattendancebot:view'] = 'Ver ORT Bot de Asistencia';

$string['configuration'] = 'Configuración general';
$string['enabled'] = 'Habilitado';
$string['enabled_help'] = 'Activa o desactiva el procesamiento automático de asistencia.';
$string['disabled'] = 'Deshabilitado';
$string['status'] = 'Estado';
$string['status_enabled'] = 'Habilitado';
$string['status_disabled'] = 'Deshabilitado';

$string['api_settings'] = 'Configuración de API';
$string['api_settings_desc'] = 'Configure el acceso a las API utilizadas para obtener información de reuniones y participantes.';
$string['zoom_api_base_url'] = 'URL base de la API de Zoom';
$string['zoom_api_base_url_desc'] = 'URL base utilizada para las solicitudes a la API de Zoom (predeterminado: https://api.zoom.us/v2).';
$string['mock_api_url'] = 'URL de API de prueba';
$string['mock_api_url_desc'] = 'URL de una API simulada para pruebas y desarrollo.';

$string['provider_settings'] = 'Proveedor de videoconferencia';
$string['provider_settings_desc'] = 'Seleccione la plataforma utilizada para las reuniones en línea.';
$string['video_provider'] = 'Proveedor de video';
$string['video_provider_desc'] = 'Seleccione la plataforma que se utilizará para las reuniones (Zoom o Google Meet).';

$string['zoom_configuration'] = 'Configuración de Zoom';
$string['zoom_credentials_detected'] = 'Credenciales de {$a} detectadas';
$string['zoom_using_mod_zoom'] = 'Usando credenciales OAuth de Servidor a Servidor del plugin mod_zoom.';
$string['zoom_accountid_label'] = 'ID de cuenta';
$string['zoom_configured_success'] = 'mod_zoom está configurado y será utilizado para las llamadas a la API.';
$string['zoom_not_detected'] = 'No se detectaron credenciales de Zoom.';
$string['zoom_configure_or_install'] = 'Configure sus propias credenciales OAuth de Zoom a continuación o instale el plugin {$a}.';

$string['zoom_account_id'] = 'ID de cuenta de Zoom';
$string['zoom_account_id_desc'] = 'ID de cuenta obtenido de su aplicación OAuth de Servidor a Servidor en Zoom Marketplace.';
$string['zoom_client_id'] = 'ID de cliente de Zoom';
$string['zoom_client_id_desc'] = 'ID de cliente obtenido de su aplicación OAuth de Servidor a Servidor de Zoom.';
$string['zoom_client_secret'] = 'Secreto de cliente de Zoom';
$string['zoom_client_secret_desc'] = 'Secreto de cliente de su aplicación OAuth de Servidor a Servidor de Zoom.';

$string['google_oauth_token'] = 'Token OAuth de Google';
$string['google_oauth_token_desc'] = 'Token OAuth 2.0 para acceder a las API de Google.';
$string['google_calendar_id'] = 'ID del calendario de Google';
$string['google_calendar_id_desc'] = 'ID del calendario desde el cual se obtendrán las reuniones (predeterminado: principal).';

$string['mock_configuration'] = 'Configuración de API de prueba';
$string['mock_configuration_desc'] = 'Solo para desarrollo y pruebas. Permite simular respuestas de la API.';

$string['camera_required'] = 'Cámara requerida';
$string['camera_required_help'] = 'Si se habilita, solo se marcará asistencia si la cámara estuvo encendida.';
$string['camera_threshold'] = 'Umbral de cámara (%)';
$string['camera_threshold_help'] = 'Porcentaje mínimo de tiempo que la cámara debe estar encendida para marcar asistencia.';
$string['min_percentage'] = 'Asistencia mínima (%)';
$string['min_percentage_help'] = 'Porcentaje mínimo de tiempo en la reunión para ser considerado presente.';
$string['late_tolerance'] = 'Tolerancia de retraso (minutos)';
$string['late_tolerance_help'] = 'Cantidad de minutos después de la hora de inicio antes de ser marcado como tarde.';

$string['datetime_range'] = 'Rango de fecha/hora';
$string['start_date'] = 'Fecha de inicio';
$string['end_date'] = 'Fecha de fin';
$string['start_time'] = 'Hora de inicio diaria';
$string['end_time'] = 'Hora de fin diaria';

$string['backup_settings'] = 'Respaldo de grabaciones';
$string['backup_settings_desc'] = 'Configure el respaldo automático de las grabaciones de Zoom.';
$string['backup_recordings'] = 'Habilitar respaldo de grabaciones';
$string['backup_recordings_help'] = 'Descarga y guarda automáticamente las grabaciones en Moodle desde la nube de Zoom.';
$string['recordings_backup'] = 'Respaldo de grabaciones';
$string['recordings_path'] = 'Ruta local de grabaciones';
$string['recordings_path_desc'] = 'Ruta en el sistema de archivos donde se almacenarán temporalmente las grabaciones.';
$string['recordings_path_help'] = 'Debe ser una carpeta con permisos de escritura para el servidor web.';
$string['delete_source'] = 'Eliminar desde Zoom tras respaldo';
$string['delete_source_help'] = 'Eliminar automáticamente las grabaciones de Zoom después de respaldarlas en Moodle.';
$string['error_path_empty'] = 'La ruta de grabaciones no puede estar vacía si el respaldo está habilitado.';
$string['error_path_not_writable'] = 'No se puede escribir en la ruta de grabaciones. Verifique los permisos del directorio.';

$string['scheduler_task'] = 'Programador del ORT Bot de Asistencia';
$string['meeting_processor_task'] = 'Procesador de reuniones y grabaciones';

$string['last_meeting'] = 'Última reunión';
$string['processed'] = 'Procesado';
$string['no_instances'] = 'No hay instancias del ORT Bot de Asistencia en este curso.';
$string['view_configuration'] = 'Configuración';
$string['view_date_range'] = 'Rango de fechas';
$string['view_time_window'] = 'Ventana de tiempo';
$string['view_status'] = 'Estado';
$string['view_actions'] = 'Acciones';
$string['view_attendance_queue'] = 'Cola de asistencia';
$string['view_testing_controls'] = 'Controles de prueba';
$string['view_testing_warning'] = '¡Estas acciones eliminan datos! Utilice solo para pruebas.';
$string['view_no_queue'] = 'No hay reuniones en cola.';
$string['view_found_meetings'] = '{$a} reuniones encontradas.';

$string['action_fetch_all'] = 'Obtener todas las reuniones';
$string['action_queue_yesterday'] = 'Encolar reuniones de ayer';
$string['action_process_attendance'] = 'Procesar asistencia';
$string['action_process_backup'] = 'Procesar respaldo';
$string['action_clear_queue'] = 'Limpiar cola';
$string['action_clear_attendance'] = 'Limpiar asistencia';
$string['action_back'] = 'Volver';

$string['confirm_clear_queue'] = '¿Desea eliminar todos los elementos de la cola?';
$string['confirm_clear_attendance'] = '¿Desea eliminar todas las sesiones del AttendanceBot?';

$string['result_total_meetings'] = 'Total de reuniones encontradas';
$string['result_queued'] = 'Encoladas';
$string['result_already_queued'] = 'Ya estaban en cola';
$string['result_filtered_out'] = 'Filtradas';
$string['result_deleted'] = 'Eliminadas';
$string['result_sessions_deleted'] = 'Sesiones eliminadas';
$string['result_logs_deleted'] = 'Registros eliminados';

$string['table_meeting_id'] = 'ID de reunión';
$string['table_topic'] = 'Tema';
$string['table_start_time'] = 'Hora de inicio';
$string['table_date'] = 'Fecha';
$string['table_status'] = 'Estado';

$string['status_processed'] = 'Procesado';
$string['status_pending'] = 'Pendiente';
$string['error_general'] = 'Error general';
