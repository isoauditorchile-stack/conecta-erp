<?php
/**
 * Gestor de Descargas de Formatos ISO 9001
 * Descarga segura de archivos con validación y registro
 */

define('ACCESO_PERMITIDO', true);
session_name('AUDITORPRO_SESSION');
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
    http_response_code(403);
    die('Acceso denegado. Debe iniciar sesión.');
}

// Configuración de seguridad
ini_set('display_errors', 0);
error_reporting(0);

// Directorio donde están los formatos
define('DIR_FORMATOS', __DIR__ . '/formatos/');

/**
 * Valida que el nombre de archivo sea seguro
 */
function validar_nombre_archivo($nombre) {
    // Eliminar espacios al inicio y final
    $nombre = trim($nombre);

    // Verificar que no esté vacío
    if (empty($nombre)) {
        return false;
    }

    // Verificar que no contenga caracteres peligrosos
    // No permitir: .. / \ : * ? " < > |
    if (preg_match('/[\/\\\\:\*\?"<>\|]|\.\./', $nombre)) {
        return false;
    }

    // Verificar extensión permitida
    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $extensiones_permitidas = ['xlsx', 'xls', 'docx', 'doc', 'pdf'];

    if (!in_array($extension, $extensiones_permitidas)) {
        return false;
    }

    return true;
}

/**
 * Registra la descarga en base de datos (opcional)
 */
function registrar_descarga($archivo, $usuario_id) {
    try {
        $dsn = "mysql:host=localhost;dbname=conectae_isogestionbd;charset=utf8mb4";
        $bd = new PDO($dsn, 'conectae_isogestionuser', 'pt125824caraud');
        $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO descargas_formatos (archivo, usuario_id, fecha_descarga, ip_address)
                VALUES (:archivo, :usuario_id, NOW(), :ip)";

        $stmt = $bd->prepare($sql);
        $stmt->execute([
            ':archivo' => $archivo,
            ':usuario_id' => $usuario_id,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Si falla el registro, no afecta la descarga
        error_log("Error registrando descarga: " . $e->getMessage());
    }
}

// Obtener nombre de archivo solicitado
$nombre_archivo = $_GET['archivo'] ?? '';

// Validar nombre de archivo
if (!validar_nombre_archivo($nombre_archivo)) {
    http_response_code(400);
    die('Nombre de archivo inválido.');
}

// Construir ruta completa
$ruta_completa = DIR_FORMATOS . basename($nombre_archivo);

// Verificar que el archivo existe
if (!file_exists($ruta_completa) || !is_file($ruta_completa)) {
    http_response_code(404);
    die('Archivo no encontrado.');
}

// Verificar que está dentro del directorio permitido (seguridad adicional)
$ruta_real = realpath($ruta_completa);
$dir_real = realpath(DIR_FORMATOS);

if (strpos($ruta_real, $dir_real) !== 0) {
    http_response_code(403);
    die('Acceso denegado.');
}

// Obtener información del archivo
$extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
$tamanio = filesize($ruta_completa);

// Determinar tipo MIME
$tipos_mime = [
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls'  => 'application/vnd.ms-excel',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc'  => 'application/msword',
    'pdf'  => 'application/pdf'
];

$tipo_mime = $tipos_mime[$extension] ?? 'application/octet-stream';

// Registrar descarga (opcional)
if (isset($_SESSION['usuario_id'])) {
    registrar_descarga($nombre_archivo, $_SESSION['usuario_id']);
}

// Limpiar buffer de salida
if (ob_get_level()) {
    ob_end_clean();
}

// Configurar headers para descarga
header('Content-Description: File Transfer');
header('Content-Type: ' . $tipo_mime);
header('Content-Disposition: attachment; filename="' . basename($nombre_archivo) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $tamanio);

// Leer y enviar archivo
readfile($ruta_completa);

exit;
