<?php
/**
 * CRON JOB - BACKUP AUTOMÁTICO DE BASE DE DATOS
 * Ejecutar diariamente a las 02:00 AM
 * Crontab: 0 2 * * * /usr/bin/php /path/to/conecta-erp/cron/backup_database.php
 */

require_once __DIR__ . '/../includes/config.php';

// Configuración backup
$backup_dir = __DIR__ . '/../backups/';
$fecha = date('Y-m-d_H-i-s');
$archivo_backup = $backup_dir . "backup_{$fecha}.sql";

// Crear directorio si no existe
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Obtener credenciales de base de datos
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

// Ejecutar mysqldump
$comando = "mysqldump --host={$db_host} --user={$db_user} --password={$db_pass} " .
           "--single-transaction --quick --lock-tables=false {$db_name} > {$archivo_backup}";

exec($comando, $output, $return_var);

if ($return_var === 0) {
    // Comprimir backup
    $archivo_comprimido = $archivo_backup . '.gz';
    exec("gzip {$archivo_backup}");

    $tamano = filesize($archivo_comprimido);
    $tamano_mb = round($tamano / 1024 / 1024, 2);

    // Registrar en log
    $log_mensaje = "[" . date('Y-m-d H:i:s') . "] Backup exitoso: {$archivo_comprimido} ({$tamano_mb} MB)\n";
    file_put_contents($backup_dir . 'backup.log', $log_mensaje, FILE_APPEND);

    // Eliminar backups antiguos (mantener últimos 30 días)
    $archivos = glob($backup_dir . '*.sql.gz');
    foreach ($archivos as $archivo) {
        if (filemtime($archivo) < strtotime('-30 days')) {
            unlink($archivo);
        }
    }

    // Enviar notificación a administradores
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje)
        SELECT u.id, 1, 'success', 'Backup automático completado',
               CONCAT('Backup de base de datos creado exitosamente: ', ?)
        FROM usuarios u WHERE u.rol = 'admin'");
    $stmt->bind_param("s", basename($archivo_comprimido));
    $stmt->execute();
    $stmt->close();

    echo "Backup completado exitosamente\n";

} else {
    // Error en backup
    $log_mensaje = "[" . date('Y-m-d H:i:s') . "] ERROR en backup: " . implode("\n", $output) . "\n";
    file_put_contents($backup_dir . 'backup.log', $log_mensaje, FILE_APPEND);

    // Notificar error
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje)
        SELECT u.id, 1, 'error', 'ERROR en backup automático',
               'Ocurrió un error al crear el backup de base de datos'
        FROM usuarios u WHERE u.rol = 'admin'");
    $stmt->execute();
    $stmt->close();

    echo "ERROR en backup\n";
}

$conn->close();
