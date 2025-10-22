<?php

// Asegurate de requerir config.php directamente, no usando $CFG todavía.
require_once(__DIR__ . '/../../../../config.php'); // ajustar según la ubicación real de tu archivo
require_once($CFG->dirroot . '/mod/attendancebot/utilities.php');

class ZoomRecordingBackup  {

    private int $courseId;
    private string $recordingPath = "";

    public function __construct(int $courseId)  {
        $this->courseId = $courseId;

         // 📁 Definir carpeta temporal dentro de moodledata
        global $CFG;
        $this->recordingPath = $CFG->dataroot . '/attendancebot_temp_recordings/';

        // crear la carpeta si no existe
        if (!is_dir($this->recordingPath)) {
            mkdir($this->recordingPath, 0775, true);
        }
    }



    public function processRecordings(array $meetings): void  {

        foreach ($meetings as $meetingId) {
            $recordings = $this->getRecordings($meetingId);
            if (!$recordings) {
                mtrace("No hay recordings para meeting $meetingId");
                continue;
            }

            // MP4 DEL TIPO DESEADO
            $filtered = array_filter($recordings, function ($rec) {
                return $rec['file_type'] === 'MP4'
                    && in_array($rec['recording_type'], [
                        'shared_screen_with_speaker_view',
                        'shared_screen_with_gallery_view',
                        'active_speaker'
                    ]);
            });

            // SE GUARDA EL PRIMERO QUE SE ENCUENTRA PARA PROBAR
            $recording = reset($filtered);
            if (!$recording) {
                mtrace("No se encontró ningún archivo MP4 adecuado para $meetingId");
                continue;
            }

            $fileUrl = $recording['download_url']; // . "?access_token=" . getZoomToken(); línea comentada para probar mock
            $expectedSize = (int) $recording['file_size'];
            $filename = $recording['id'] . ".mp4";
            $localPath = $this->recordingPath . $filename;

            try {

                $actualSize = $this->download($fileUrl, $localPath);
        // 🧩 Ajuste: solo comparar tamaños si el mock tiene tamaño conocido (>0)
                if ($expectedSize > 0 && $actualSize !== $expectedSize) {
                    mtrace("Tamanio no coincide para $filename. Esperado: $expectedSize, Obtenido: $actualSize");
                    unlink($localPath);
                    continue;
                }

                $this->uploadToMoodle($filename, $localPath);
                // $this->deleteRecording($meetingId, $recording['id']);
            } catch (Exception $e) {
                mtrace("Error descargando $filename: " . $e->getMessage());
            }
    }
}



    
//     private function getRecordings(string $meetingId): array  {

//         $encodedId = urlencode($meetingId);
//         mtrace("Usando ID codificado en Zoom API: $encodedId");
//         $url = "https://api.zoom.us/v2/meetings/$encodedId/recordings";
//         $response = $this->makeZoomRequest($url);

//         return $response['recording_files'] ?? [];
// }

//MOCKEO getRecordings():

private function getRecordings(string $meetingId): array  {

    // === MOCK para pruebas locales: retorna un MP4 público para que el flujo se ejecute ===
    // Cambiá este URL por otro si querés probar con otro archivo.
    $publicMp4Url = 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';

    // idealmente, el "id" debe ser único por meeting+archivo
    return [[
        'id' => 'mock-' . $meetingId . '-' . time(),
        'file_type' => 'MP4',
        'recording_type' => 'active_speaker',
        'download_url' => $publicMp4Url,
        // ponemos 0 para indicar "desconocido" — la verificación de tamaño será tolerante
        'file_size' => 0
    ]];

    // ==== FIN DEL MOCK ====
    // En producción, restaurar la versión original que hace la llamada a Zoom API:
    // $encodedId = urlencode($meetingId);
    // $url = "https://api.zoom.us/v2/meetings/$encodedId/recordings";
    // $response = $this->makeZoomRequest($url);
    // return $response['recording_files'] ?? [];
}


    

    private function deleteRecording(string $meetingId, string $recordingId): void  {
        $url = "https://api.zoom.us/v2/meetings/$meetingId/recordings/$recordingId";
        $this->makeZoomRequest($url, 'DELETE');
        mtrace("Recording eliminado: $recordingId");
    } 
        




    private function makeZoomRequest(string $url, string $method = 'GET'): array  {

        $token = getZoomToken();

        $headers = [
            "Authorization: Bearer {$token}",
            "User-Agent: MoodleAttendanceBot/1.0"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new Exception("cURL error ($errno): $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        mtrace("Zoom API raw response (HTTP $httpCode):");
        mtrace($response ?: '[empty]');
        curl_close($ch);

        if ($httpCode === 204) {
            return []; // no content
        }

        if ($httpCode >= 400) {
            throw new Exception("Zoom API Error [$httpCode]: $response");
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new Exception("Zoom API response not valid JSON: $response");
        }

        return $decoded;
    }

//DESCOMENTAR CUANDO SE PUEDA UTILIZAR LAS GRABACIONES DE ZOOM
    // private function download(string $url, string $path): int  {

    //     $token = getZoomToken();

    //     $headers = [
    //         "Authorization: Bearer {$token}",
    //         "User-Agent: MoodleAttendanceBot/1.0"
    //     ];

    //     $fp = fopen($path, 'w+b');
    //     if (!$fp) {
    //         throw new Exception("No se pudo abrir el archivo para escribir: $path");
    //     }

    //     $ch = curl_init($url);
    //     curl_setopt_array($ch, [
    //         CURLOPT_FILE => $fp,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_FAILONERROR => true,
    //         CURLOPT_HTTPHEADER => $headers,
    //     ]);

    //     $success = curl_exec($ch);
    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //     $error = curl_error($ch);

    //     curl_close($ch);
    //     fclose($fp);

    //     if (!$success || $httpCode >= 400) {
    //         unlink($path);
    //         throw new Exception("Descarga fallida: HTTP $httpCode. Error: $error");
    //     }

    //     return filesize($path);
    // }

 // MOCKEO
    private function download(string $url, string $localPath): int {
        $fp = fopen($localPath, 'w+');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return filesize($localPath);
    }


//     private function uploadToMoodle(string $filename, string $filepath): void  {

//         global $CFG, $DB, $USER;

//         require_once($CFG->libdir . '/filelib.php');
//         require_once($CFG->libdir . '/completionlib.php');
//         require_once($CFG->dirroot . '/course/lib.php');
//         require_once($CFG->dirroot . '/mod/resource/lib.php');
//         require_once($CFG->dirroot . '/mod/resource/locallib.php');
//         require_once($CFG->dirroot . '/course/modlib.php');

//         $transaction = $DB->start_delegated_transaction();

//         $course = get_course($this->courseId);
//         $context = context_course::instance($this->courseId);
//         $fs = get_file_storage();

//         // Validar existencia y legibilidad
//         if (!file_exists($filepath) || !is_readable($filepath)) {
//             throw new moodle_exception("Archivo no accesible: $filepath");
//         }

//         // Eliminar archivo anterior si existe
//         $existingFile = $fs->get_file($context->id, 'mod_resource', 'content', 0, '/', $filename);
//         if ($existingFile) {
//             $existingFile->delete();
//         }

//         // Crear nuevo archivo en Moodle
//         $fileinfo = [
//             'contextid' => $context->id,
//             'component' => 'mod_resource',
//             'filearea'  => 'content',
//             'itemid'    => 0,
//             'filepath'  => '/',
//             'filename'  => $filename,
//         ];
//         $file = $fs->create_file_from_pathname($fileinfo, $filepath);

//         if (!$file || !$file->get_id()) {
//             throw new moodle_exception("Fallo al guardar archivo en Moodle (mdl_files)");
//         }

//         // Crear objeto del recurso
//         $modulename = 'resource';
//         $moduleid = $DB->get_field('modules', 'id', ['name' => $modulename], MUST_EXIST);

//         $resource = new stdClass();
//         $resource->course = $course->id;
//         $resource->name = 'Grabación Zoom - ' . date('Y-m-d H:i');
//         $resource->intro = 'Grabación subida automáticamente.';
//         $resource->introformat = FORMAT_HTML;
//         $resource->display = RESOURCELIB_DISPLAY_AUTO;
//         $resource->timemodified = time();

//         $moduleinfo = new stdClass();
//         $moduleinfo->modulename = $modulename;
//         $moduleinfo->module = $moduleid;
//         $moduleinfo->section = 0;
//         $moduleinfo->visible = 1;
//         $moduleinfo->course = $course->id;
//         $moduleinfo->name = $resource->name;
//         $moduleinfo->intro = $resource->intro;
//         $moduleinfo->introformat = FORMAT_HTML;
//         $moduleinfo->display = $resource->display;
//         $moduleinfo->type = 'file';
//         // $moduleinfo->contentfiles = [$file->get_id()]; DESCOMENTAR CUANDO SE PUEDA PROBAR CON ZOOM

//         add_moduleinfo($moduleinfo, $course);

//         $transaction->allow_commit();

//         mtrace("Grabación $filename subida y registrada en mdl_files.");
// }

private function uploadToMoodle(string $filename, string $filepath): void {
    global $CFG, $DB;

    require_once($CFG->libdir . '/filelib.php');
    require_once($CFG->libdir . '/completionlib.php');
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/mod/resource/lib.php');
    require_once($CFG->dirroot . '/mod/resource/locallib.php');
    require_once($CFG->dirroot . '/course/modlib.php');

    $transaction = $DB->start_delegated_transaction();

    $course = get_course($this->courseId);
    $fs = get_file_storage();

    // 1️⃣ Validar existencia del archivo local
    if (!file_exists($filepath) || !is_readable($filepath)) {
        throw new moodle_exception("Archivo no accesible: $filepath");
    }

    // 2️⃣ Obtener el ID del módulo "resource"
    $moduleid = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);

    // 3️⃣ Crear el módulo en el curso
    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'resource';
    $moduleinfo->module = $moduleid; // ⚠️ ESTA LÍNEA ERA LA QUE FALTABA
    $moduleinfo->course = $course->id;
    $moduleinfo->section = 0;
    $moduleinfo->visible = 1;
    $moduleinfo->name = 'Grabación Zoom - ' . date('Y-m-d H:i');
    $moduleinfo->intro = 'Grabación subida automáticamente.';
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->display = RESOURCELIB_DISPLAY_AUTO;
    $moduleinfo->type = 'file';

    // 4️⃣ Crear el módulo vacío
    $moduleinfo = add_moduleinfo($moduleinfo, $course);

    // 5️⃣ Subir el archivo al contexto del módulo recién creado
    $context = context_module::instance($moduleinfo->coursemodule);

    // Eliminar archivo anterior si existiera (por nombre)
    $fs = get_file_storage();
    $existingFile = $fs->get_file($context->id, 'mod_resource', 'content', 0, '/', $filename);
    if ($existingFile) {
        $existingFile->delete();
    }

    // Crear archivo en la filearea del recurso
    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'mod_resource',
        'filearea'  => 'content',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename,
    ];

    $file = $fs->create_file_from_pathname($fileinfo, $filepath);

    if (!$file || !$file->get_id()) {
        throw new moodle_exception("Fallo al guardar archivo en Moodle (mdl_files)");
    }

    $transaction->allow_commit();

    mtrace("✅ Grabación '{$filename}' subida correctamente y vinculada al recurso (cmid={$moduleinfo->coursemodule}).");
}

}