# Bot de Asistencia - Resumen Rápido

## Qué Hace Este Plugin

✅ **Cron a la 1 AM**: Tarea programada se ejecuta diariamente para obtener reuniones del día anterior
✅ **Cola de Reuniones**: Almacena IDs de reuniones en cola para procesamiento
✅ **Procesamiento Secuencial**: Tarea adhoc procesa cada reunión una por una
✅ **Coincidencia por Email**: Relaciona participantes con usuarios de Moodle por correo electrónico
✅ **Detección de Cámara**: Verifica porcentaje de cámara encendida vía API
✅ **Almacenamiento de Asistencia**: Escribe en mod_attendance (tabla attendance_log)
✅ **Respaldo de Grabaciones**: Descarga y guarda grabaciones en Moodle
✅ **Multi-Proveedor**: Soporta Zoom, Google Meet, y API Mock
✅ **Configuración Flexible**: Umbrales y requisitos por instancia

## Arquitectura de API

### Diseño Multi-Proveedor

El plugin utiliza un patrón Factory para soportar múltiples proveedores de videoconferencia:


api/
├── client_connection.php   - Factory (selecciona proveedor automáticamente)
├── client_interface.php    - Interfaz común para todos los clientes
├── zoom_client.php         - Implementación Zoom API
├── meet_client.php         - Implementación Google Meet API
└── mock_client.php         - Implementación Mock para testing


**Selección Automática**:
- Si `use_mock_api` = true → usa `mock_client`
- Si `video_provider` = 'zoom' → usa `zoom_client`
- Si `video_provider` = 'meet' → usa `meet_client`

**Uso en Código**:
php
require_once(__DIR__ . '/api/client_connection.php');
$client = \mod_ortattendancebot\api\client_connection::get_client();

// Todos los clientes implementan la misma interfaz
$meetings = $client->get_meetings_by_date('2025-11-03');
$participants = $client->get_meeting_participants($meeting_id);
$recordings = $client->get_recording_metadata($meeting_id);


## Archivos Clave

**Tareas**:
- `classes/task/scheduler_task.php` - Se ejecuta a la 1 AM, encola reuniones
- `classes/task/meeting_processor_task.php` - Procesa reuniones en cola

**API Clients**:
- `classes/api/client_connection.php` - Factory para selección de proveedor
- `classes/api/client_interface.php` - Contrato común
- `classes/api/zoom_client.php` - Cliente Zoom (producción)
- `classes/api/meet_client.php` - Cliente Google Meet (producción)
- `classes/api/mock_client.php` - Cliente Mock (testing)

**Lógica**:
- `classes/processor/meeting_processor.php` - Lógica principal de asistencia
- `classes/backup/recording_backup.php` - Gestión de respaldo de grabaciones

**Configuración**:
- `settings.php` - Configuración admin (API, respaldos, proveedor)
- `mod_form.php` - Configuración de actividad (umbrales, fechas)

## Endpoints de API Requeridos

### Interfaz Común (client_interface.php)

Todos los clientes deben implementar:

php
get_meetings_by_date($date)
get_meetings_by_date_range($from_date, $to_date)
get_meeting_participants($meeting_id)
get_recording_metadata($meeting_id)
delete_recording($meeting_id, $recording_id)
get_meeting_info($meeting_id)


### Zoom API

GET /report/users/{email}/meetings?from=YYYY-MM-DD&to=YYYY-MM-DD
GET /metrics/meetings/{meeting_id}/participants
GET /meetings/{meeting_id}/recordings
DELETE /meetings/{meeting_id}/recordings/{recording_id}


### Google Meet API

GET /calendar/v3/calendars/{calendarId}/events?timeMin=...&timeMax=...
GET /meet/v2/conferenceRecords/{meeting_id}/participants
GET /drive/v3/files?q=name contains '{meeting_id}'
DELETE /drive/v3/files/{recording_id}


### Respuesta de Participantes

Debe incluir:
- `user_email` o `email`
- `join_time`, `leave_time`
- `camera_on` o `has_video` (boolean)

### Respuesta de Grabaciones

Debe incluir:
- `recording_files` array con `download_url` (Zoom)
- `files` array con `webContentLink` (Google Meet)

## Configuración de Proveedores

### Zoom

**Credenciales** (prioridad):
1. `mod_zoom` plugin (si está instalado)
2. `zoommtg` plugin
3. `auth_zoom` plugin
4. Configuración propia en `mod_ortattendancebot`

**Configuración Admin**:
- OAuth Token
- Email del host

### Google Meet

**Credenciales** (prioridad):
1. `auth_googleoauth2` plugin (si está instalado)
2. `local_o365` plugin
3. Configuración propia en `mod_ortattendancebot`

**Configuración Admin**:
- OAuth Token
- Calendar ID (default: 'primary')

**APIs Requeridas**:
- Google Calendar API (para eventos)
- Google Meet API (para participantes)
- Google Drive API (para grabaciones)

### Mock API

**Configuración Admin**:
- URL de API Mock: `http://localhost:5000`
- Activar: `use_mock_api` = true

## Lógica de Estados


Presente: asistencia% ≥ umbral Y (cámara% ≥ umbral_cámara O cámara no requerida) Y ingresó a tiempo
Tarde: Condiciones de presente cumplidas PERO ingresó tarde
Ausente: asistencia% < umbral O (cámara requerida Y cámara% < umbral)


## Respaldo de Grabaciones

**Configuración Admin**:
- Ruta local para almacenar grabaciones temporalmente
- Opción para eliminar de fuente después del respaldo

**Proceso**:
1. Obtiene metadata de grabación vía API
2. Descarga grabación a ruta local temporal
3. Normaliza nombre según formato: `NombreCurso_YYYY-MM-DD.mp4`
4. Organiza en estructura de carpetas
5. Sube archivo a área de archivos de Moodle
6. Opcionalmente elimina de cloud (Zoom/Meet)
7. Registra en base de datos

**Prioridad de Grabaciones** (Zoom):
1. `shared_screen_with_speaker_view`
2. `shared_screen_with_gallery_view`
3. `active_speaker`

## Instalación

1. Extraer a `/moodle/mod/ortattendancebot`
2. Ejecutar: `php admin/cli/upgrade.php`
3. Configurar:
   - Administración → Plugins → ORT Bot de Asistencia
   - Seleccionar proveedor: Zoom o Google Meet
   - Configurar credenciales API
4. Agregar al curso: Activar edición → Agregar actividad → ORT Bot de Asistencia
5. Probar: `php admin/tool/task/cli/schedule_task.php --execute=\\mod_ortattendancebot\\task\\scheduler_task`

## Características Principales

**Sistema de Asistencia**:
- Sincronización automática con mod_attendance
- Coincidencia por email entre videoconferencia y Moodle
- Cálculo de porcentajes de asistencia y cámara
- Detección de llegadas tarde
- Umbrales configurables por actividad
- Soporte multi-proveedor (Zoom/Meet)

**Respaldo de Grabaciones**:
- Descarga automática de grabaciones en nube
- Normalización de nombres de archivo
- Estructura de carpetas organizada
- Almacenamiento en sistema de archivos de Moodle
- Opción de eliminar fuente después del respaldo
- Gestión de múltiples archivos por reunión
- Cola de reintentos (hasta 3 intentos)

**Configuración**:
- Selección de proveedor (Zoom/Meet/Mock)
- API real o API mock para testing
- Rangos de fecha/hora personalizables
- Requisitos de cámara opcionales
- Tolerancia de retraso configurable
- Umbrales de asistencia por actividad

## Testing con API Mock

Configurar en ajustes admin:
- Video Provider: Mock
- URL de API Mock: `http://localhost:5000`
- Activar: `use_mock_api` = true

Tu API mock debe devolver JSON compatible con formato Zoom:

**Ejemplo - Listar Reuniones**:
json
{
  "meetings": [
    {
      "id": "12345",
      "topic": "Clase de PHP",
      "start_time": "2025-11-03T10:00:00Z",
      "duration": 60
    }
  ]
}


**Ejemplo - Participantes**:
json
{
  "participants": [
    {
      "user_email": "estudiante@ejemplo.com",
      "name": "Juan Pérez",
      "join_time": "2025-11-03T10:05:00Z",
      "leave_time": "2025-11-03T10:55:00Z",
      "camera_on": true
    }
  ]
}


## Tablas de Base de Datos

- `ortattendancebot` - Instancias de actividad
- `ortattendancebot_queue` - Cola de reuniones pendientes
- `ortattendancebot_backup_queue` - Cola de respaldos de grabaciones
- `ortattendancebot_cleanup_queue` - Cola de limpieza (eliminaciones fallidas)

## Flujo de Procesamiento

### 1. Programación (1 AM diaria)

scheduler_task.php
  ↓
Obtiene instalaciones activas
  ↓
Para cada instalación:
  - Obtiene reuniones del día anterior vía API
  - Filtra por rango horario configurado
  - Encola IDs en ortattendancebot_queue
  - Programa meeting_processor_task


### 2. Procesamiento de Asistencia
meeting_processor_task.php
  ↓
Procesa cola de asistencia (sin límite)
  ↓
Para cada reunión:
  - Obtiene participantes vía API
  - Calcula tiempos y porcentajes
  - Determina estado (presente/tarde/ausente)
  - Guarda en attendance_log
  - Marca ausentes a no asistentes
  - Si backup habilitado: encola en backup_queue

### 3. Procesamiento de Respaldos
meeting_processor_task.php
  ↓
Procesa cola de respaldo (límite 5 por ejecución)
  ↓
Para cada grabación:
  - Obtiene metadata vía API
  - Descarga archivo
  - Normaliza nombre
  - Sube a Moodle
  - Opcionalmente elimina de cloud
  - Registra en base de datos
  - Reintenta hasta 3 veces si falla

## Extensibilidad

Para agregar un nuevo proveedor:

1. Crear `classes/api/nuevo_provider_client.php`
2. Implementar `client_interface`
3. Agregar constante en `client_connection.php`
4. Actualizar método `get_client()` con nuevo case
5. Configurar credenciales en `settings.php`

Ejemplo:
class teams_client implements client_interface {
    public function get_meetings_by_date($date) {
        // Implementación para Microsoft Teams
    }
    // ... otros métodos
}
