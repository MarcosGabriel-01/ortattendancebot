# Bot de Asistencia - Resumen Rápido

## Qué Hace Este Plugin

✅ **Cron a la 1 AM**: Tarea programada se ejecuta diariamente para obtener reuniones del día anterior
✅ **Cola de Reuniones**: Almacena IDs de reuniones en cola para procesamiento
✅ **Procesamiento Secuencial**: Tarea adhoc procesa cada reunión una por una
✅ **Coincidencia por Email**: Relaciona participantes de Zoom con usuarios de Moodle por correo electrónico
✅ **Detección de Cámara**: Verifica porcentaje de cámara encendida vía API de Zoom
✅ **Almacenamiento de Asistencia**: Escribe en mod_attendance (tabla attendance_log)
✅ **Respaldo de Grabaciones**: Descarga y guarda grabaciones de Zoom en Moodle
✅ **Configuración Flexible**: Umbrales y requisitos por instancia

## Archivos Clave

**Tareas**:
- `classes/task/scheduler_task.php` - Se ejecuta a la 1 AM, encola reuniones
- `classes/task/meeting_processor_task.php` - Procesa reuniones en cola

**Lógica**:
- `classes/api/zoom_client.php` - Llama API de Zoom (o mock)
- `classes/processor/meeting_processor.php` - Lógica principal de asistencia
- `classes/processor/recording_processor.php` - Gestión de grabaciones

**Configuración**:
- `settings.php` - Configuración admin (API, respaldos)
- `mod_form.php` - Configuración de actividad (umbrales, fechas)

## Endpoints de API Requeridos

```
GET /report/meetings?from=YYYY-MM-DD&to=YYYY-MM-DD
GET /metrics/meetings/{meeting_id}/participants
GET /meetings/{meeting_id}/recordings
```

Respuesta de participantes debe incluir:
- `user_email` o `email`
- `join_time`, `leave_time`
- `camera_on` o `has_video` (boolean)

Respuesta de grabaciones debe incluir:
- `recording_files` array con `download_url`

## Lógica de Estados

```
Presente: asistencia% ≥ umbral Y (cámara% ≥ umbral_cámara O cámara no requerida) Y ingresó a tiempo
Tarde: Condiciones de presente cumplidas PERO ingresó tarde
Ausente: asistencia% < umbral O (cámara requerida Y cámara% < umbral)
```

## Respaldo de Grabaciones

**Configuración Admin**:
- Ruta local para almacenar grabaciones temporalmente
- Opción para eliminar de Zoom después del respaldo

**Proceso**:
1. Descarga grabación de Zoom a ruta local
2. Sube archivo a área de archivos de Moodle
3. Opcionalmente elimina de Zoom cloud
4. Registra en base de datos

## Instalación

1. Extraer a `/moodle/mod/ortattendancebot`
2. Ejecutar: `php admin/cli/upgrade.php`
3. Configurar API: Administración → Plugins → ORT Bot de Asistencia
4. Agregar al curso: Activar edición → Agregar actividad → ORT Bot de Asistencia
5. Probar: `php admin/tool/task/cli/schedule_task.php --execute=\\mod_ortattendancebot\\task\\scheduler_task`

## Características Principales

**Sistema de Asistencia**:
- Sincronización automática con mod_attendance
- Coincidencia por email entre Zoom y Moodle
- Cálculo de porcentajes de asistencia y cámara
- Detección de llegadas tarde
- Umbrales configurables por actividad

**Respaldo de Grabaciones**:
- Descarga automática de grabaciones en nube
- Almacenamiento en sistema de archivos de Moodle
- Opción de eliminar fuente después del respaldo
- Gestión de múltiples archivos por reunión

**Configuración**:
- API real de Zoom o API mock para testing
- Rangos de fecha/hora personalizables
- Requisitos de cámara opcionales
- Tolerancia de retraso configurable

## Testing con API Mock

Configurar en ajustes admin:
- URL de API Mock: `http://localhost:5000`
- Usar API Mock: Sí

Tu API mock debe devolver JSON con formato de Zoom (ver README.md para ejemplos).

## Tablas de Base de Datos

- `ortattendancebot` - Instancias de actividad
- `ortattendancebot_queue` - Cola de reuniones pendientes
- `ortattendancebot_recordings` - Registro de grabaciones respaldadas
