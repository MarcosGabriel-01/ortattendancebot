# ORT Bot de Asistencia - Plugin de Moodle

Procesa automáticamente la asistencia de reuniones Zoom y respalda grabaciones en nube a Moodle.

## Versión

**Versión:** 2025102902
**Moodle Requerido:** 4.0+
**Nombre del Módulo:** mod_ortattendancebot

## Características

### 1. Procesamiento de Asistencia
- Obtiene automáticamente reuniones de Zoom y procesa asistencia
- Relaciona participantes por email
- Verifica porcentaje de cámara encendida
- Crea sesiones de asistencia en módulo mod_attendance
- Umbrales configurables para presente/tarde/ausente

### 2. Respaldo de Grabaciones
- Descarga grabaciones en nube de Zoom
- Organiza en jerarquía estructurada de carpetas
- Sube a carpetas de cursos Moodle
- Opcionalmente elimina de Zoom después del respaldo
- Gestiona reintentos y recuperación de errores

## Cambios Clave desde attendancebot

1. **Módulo renombrado:** `attendancebot` → `ortattendancebot`
2. **Función de respaldo de grabaciones agregada**
3. **Límites de procesamiento de cola:** 25 asistencias, 5 respaldos por lote
4. **Nuevas tablas de base de datos:** backup_queue, cleanup_queue
5. **Análisis mejorado de nombres** para organización de carpetas

## Instalación

1. Extraer el archivo zip a `/ruta/a/moodle/mod/ortattendancebot`
2. Visitar Administración del Sitio > Notificaciones para completar instalación
3. Configurar credenciales de API de Zoom en ajustes del plugin

## Configuración

### Ajustes Globales del Sitio
- **URL Base de API de Zoom**: https://api.zoom.us/v2
- **Token OAuth de Zoom**: Tu token OAuth Server-to-Server
- **Email del Anfitrión de Zoom**: Email del usuario cuyos meetings rastrear

### Ajustes de Actividad

#### Configuración de Asistencia
- **Umbral de Cámara**: 60% (predeterminado)
- **Porcentaje Mínimo**: 75% (predeterminado)
- **Tolerancia de Retraso**: 15 minutos (predeterminado)

#### Respaldo de Grabaciones
- **Habilitar Respaldo de Grabaciones**: Activa descargas automáticas
- **Ruta de Grabaciones**: Ruta de almacenamiento local (predeterminado: {moodledata}/ortattendancebot_recordings)
- **Eliminar de Zoom**: Auto-eliminar después del respaldo

## Ejemplos de Análisis de Nombres de Reunión

**Basado en Código:**
```
"Matemáticas BE-MATB [asc-be-11b]" → /recordings/BE/MAT/B/20251015/
```

**Basado en Texto:**
```
"Tendencias - CURSO A [din-be-21a]" → /recordings/Tendencias/A/20251015/
```

## Procesamiento de Cola

- **Asistencia:** 25 reuniones por lote
- **Respaldos:** 5 grabaciones por lote
- **Lógica de Reintentos:** Máximo 3 intentos, intervalos de 24h

## Endpoints de API Requeridos

```
GET /users/{email}/meetings?from=YYYY-MM-DD&to=YYYY-MM-DD
GET /past_meetings/{meeting_id}/participants
GET /meetings/{meeting_id}/recordings
```

## Tablas de Base de Datos

- `mdl_ortattendancebot` - Instancias de actividad
- `mdl_ortattendancebot_queue` - Cola de procesamiento de asistencia
- `mdl_ortattendancebot_backup_queue` - Cola de respaldo de grabaciones
- `mdl_ortattendancebot_cleanup_queue` - Cola de limpieza de Zoom
- `mdl_ortattendancebot_recordings` - Registro de grabaciones respaldadas

## Licencia

GNU GPL v3 o posterior
