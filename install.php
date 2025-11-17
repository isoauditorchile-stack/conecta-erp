<?php
/**
 * ============================================
 * CONECTA ERP - INSTALADOR AUTOMÁTICO
 * ============================================
 * Este script instala automáticamente todas las tablas
 * y datos iniciales del sistema CONECTA ERP
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Archivos SQL a ejecutar en orden
$sql_files = [
    // Tablas base del sistema
    'sql/01_tablas_basicas.sql',
    'sql/02_usuarios_autenticacion.sql',

    // Módulos del sistema (orden correcto según archivos reales)
    'sql/modulos/00_dashboard_tables.sql',
    'sql/modulos/01_administracion.sql',
    'sql/modulos/02_entidades.sql',
    'sql/modulos/03_finanzas.sql',
    'sql/modulos/04_controlling.sql',
    'sql/modulos/05_ventas.sql',
    'sql/modulos/06_materiales.sql',
    'sql/modulos/07_produccion.sql',
    'sql/modulos/08_rrhh.sql',
    'sql/modulos/09_scm.sql',
    'sql/modulos/10_crm.sql',
    'sql/modulos/11_fidelizacion.sql',
    'sql/modulos/12_business_intelligence.sql',
    'sql/modulos/13_configuracion.sql',
    'sql/modulos/14_api_y_reportes.sql',
    'sql/modulos/15_ecommerce.sql',
    'sql/modulos/16_proyectos_calidad_mantenimiento.sql',
    'sql/modulos/17_bi_avanzado.sql',
    'sql/modulos/18_reloj_control.sql',
    'sql/modulos/19_password_recovery.sql',
    'sql/modulos/20_control_acceso_planes.sql',
    'sql/modulos/21_sistema_integracion_completa.sql'  // Sistema completo: Indicadores, SII, Libros, DJ, Fechas
];

/**
 * Códigos de error MySQL que deben ser ignorados (normales en instalación)
 */
function esErrorIgnorable($errno) {
    $errores_ignorables = [
        1007, // Can't create database 'x'; database exists
        1050, // Table 'x' already exists
        1060, // Duplicate column name 'x'
        1061, // Duplicate key name 'x'
        1062, // Duplicate entry 'x' for key 'y'
        1227, // Access denied; you need RELOAD privilege
        1304, // PROCEDURE/FUNCTION already exists
        1359, // Trigger already exists
        1360, // Trigger already exists
        1051, // Unknown table 'x' (DROP IF EXISTS)
        1091, // Can't DROP 'x'; check that column/key exists
        1146, // Table 'x' doesn't exist (en algunos ALTER)
    ];
    return in_array($errno, $errores_ignorables);
}

/**
 * Parsear archivo SQL manejando correctamente DELIMITER y comentarios
 */
function parseSQLFile($sql_content) {
    // Eliminar comentarios multilínea /* ... */
    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);

    $statements = [];
    $current_delimiter = ';';
    $current_statement = '';

    // Dividir por líneas
    $lines = explode("\n", $sql_content);

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Ignorar líneas vacías y comentarios
        if (empty($trimmed) || substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') {
            continue;
        }

        // Ignorar FLUSH PRIVILEGES (requiere permisos especiales)
        if (stripos($trimmed, 'FLUSH PRIVILEGES') !== false) {
            continue;
        }

        // Detectar cambio de DELIMITER
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $matches)) {
            $current_delimiter = trim($matches[1]);
            continue;
        }

        // Agregar línea al statement actual
        $current_statement .= $line . "\n";

        // Verificar si la línea termina con el delimitador actual
        if (substr(rtrim($line), -strlen($current_delimiter)) === $current_delimiter) {
            $stmt = substr($current_statement, 0, -strlen($current_delimiter) - 1);
            $stmt = trim($stmt);

            if (!empty($stmt)) {
                $statements[] = $stmt;
            }

            $current_statement = '';
        }
    }

    // Agregar cualquier statement pendiente
    $stmt = trim($current_statement);
    if (!empty($stmt)) {
        $statements[] = $stmt;
    }

    return $statements;
}

/**
 * Clasificar statement por tipo para ejecutar en el orden correcto
 */
function getStatementType($statement) {
    $stmt_upper = strtoupper(substr($statement, 0, 100));

    // Pasada 1: DDL básico (tablas, índices, datos)
    if (preg_match('/^(CREATE\s+TABLE|ALTER\s+TABLE|CREATE\s+INDEX|INSERT\s+INTO|DROP\s+TABLE)/i', $stmt_upper)) {
        return 'phase1_ddl';
    }

    // Pasada 2: Objetos que dependen de tablas
    if (preg_match('/^(CREATE\s+(PROCEDURE|TRIGGER|FUNCTION|VIEW|EVENT)|DROP\s+(PROCEDURE|TRIGGER|FUNCTION|VIEW))/i', $stmt_upper)) {
        return 'phase2_objects';
    }

    // Por defecto, ejecutar en pasada 1
    return 'phase1_ddl';
}

// Verificar si ya está instalado
$already_installed = false;
$errors = [];
$success_messages = [];

// Conectar a la base de datos
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    $conn->set_charset(DB_CHARSET);

    // Verificar si ya hay tablas instaladas
    $result = $conn->query("SHOW TABLES");
    if ($result && $result->num_rows > 0) {
        $already_installed = true;
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

// Procesar instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    if (!$already_installed || isset($_POST['force_reinstall'])) {

        // Desactivar verificación de foreign keys temporalmente
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

        // PASO 1: Recolectar y clasificar todos los statements
        $phase1_statements = []; // CREATE TABLE, ALTER TABLE, INSERT
        $phase2_statements = []; // CREATE PROCEDURE, TRIGGER, FUNCTION, VIEW

        foreach ($sql_files as $file) {
            $file_path = __DIR__ . '/' . $file;

            if (!file_exists($file_path)) {
                $errors[] = "El archivo $file no existe";
                continue;
            }

            $sql_content = file_get_contents($file_path);
            $statements = parseSQLFile($sql_content);

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;

                $type = getStatementType($statement);

                if ($type === 'phase1_ddl') {
                    $phase1_statements[] = [
                        'sql' => $statement,
                        'file' => basename($file)
                    ];
                } else {
                    $phase2_statements[] = [
                        'sql' => $statement,
                        'file' => basename($file)
                    ];
                }
            }
        }

        $success_messages[] = "📊 Fase 1: " . count($phase1_statements) . " tablas/datos a crear";
        $success_messages[] = "📊 Fase 2: " . count($phase2_statements) . " procedimientos/triggers a crear";

        // PASO 2: Ejecutar FASE 1 (Tablas y datos)
        $phase1_executed = 0;
        $phase1_failed = 0;
        $phase1_errors = [];

        foreach ($phase1_statements as $stmt_data) {
            $is_procedure = stripos($stmt_data['sql'], 'CREATE PROCEDURE') !== false ||
                            stripos($stmt_data['sql'], 'CREATE FUNCTION') !== false;

            if ($is_procedure) {
                $success = $conn->multi_query($stmt_data['sql']);
                if ($success) {
                    do {
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                    } while ($conn->more_results() && $conn->next_result());
                }
            } else {
                $success = $conn->query($stmt_data['sql']);
            }

            if ($success) {
                $phase1_executed++;
            } else {
                // Verificar si es un error ignorable
                if (esErrorIgnorable($conn->errno)) {
                    $phase1_executed++; // Contar como exitoso
                } else {
                    $phase1_failed++;
                    $stmt_preview = substr($stmt_data['sql'], 0, 150);
                    $stmt_preview = str_replace(["\n", "\r", "\t"], ' ', $stmt_preview);
                    $stmt_preview = preg_replace('/\s+/', ' ', $stmt_preview);

                    $phase1_errors[] = [
                        'file' => $stmt_data['file'],
                        'errno' => $conn->errno,
                        'error' => $conn->error,
                        'sql' => $stmt_preview . '...'
                    ];
                }
            }
        }

        $success_messages[] = "✅ Fase 1: $phase1_executed/" . count($phase1_statements) . " ejecutadas ($phase1_failed errores)";

        // Mostrar errores de Fase 1 detalladamente
        if ($phase1_failed > 0) {
            $errors[] = "<div class='mt-3'><strong>⚠️ ERRORES EN FASE 1 (Tablas/Datos):</strong></div>";
            foreach (array_slice($phase1_errors, 0, 20) as $err) {
                $errors[] = "<div class='ms-3 mb-2'><strong>[{$err['file']}]</strong> {$err['error']}<br><code style='font-size:10px; color:#666'>{$err['sql']}</code></div>";
            }
            if ($phase1_failed > 20) {
                $errors[] = "<div class='ms-3'>... y " . ($phase1_failed - 20) . " errores más</div>";
            }
        }

        // PASO 3: Ejecutar FASE 2 (Procedimientos, triggers, funciones)
        $phase2_executed = 0;
        $phase2_failed = 0;
        $phase2_errors = [];

        foreach ($phase2_statements as $stmt_data) {
            $success = $conn->multi_query($stmt_data['sql']);
            if ($success) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
                $phase2_executed++;
            } else {
                // Verificar si es un error ignorable
                if (esErrorIgnorable($conn->errno)) {
                    $phase2_executed++; // Contar como exitoso
                } else {
                    $phase2_failed++;
                    $stmt_preview = substr($stmt_data['sql'], 0, 150);
                    $stmt_preview = str_replace(["\n", "\r", "\t"], ' ', $stmt_preview);
                    $stmt_preview = preg_replace('/\s+/', ' ', $stmt_preview);

                    $phase2_errors[] = [
                        'file' => $stmt_data['file'],
                        'errno' => $conn->errno,
                        'error' => $conn->error,
                        'sql' => $stmt_preview . '...'
                    ];
                }
            }
        }

        $success_messages[] = "✅ Fase 2: $phase2_executed/" . count($phase2_statements) . " ejecutadas ($phase2_failed errores)";

        // Mostrar errores de Fase 2 detalladamente
        if ($phase2_failed > 0) {
            $errors[] = "<div class='mt-3'><strong>⚠️ ERRORES EN FASE 2 (Procedimientos/Triggers):</strong></div>";
            foreach (array_slice($phase2_errors, 0, 20) as $err) {
                $errors[] = "<div class='ms-3 mb-2'><strong>[{$err['file']}]</strong> {$err['error']}<br><code style='font-size:10px; color:#666'>{$err['sql']}</code></div>";
            }
            if ($phase2_failed > 20) {
                $errors[] = "<div class='ms-3'>... y " . ($phase2_failed - 20) . " errores más</div>";
            }
        }

        // Reactivar verificación de foreign keys
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        if ($phase1_failed === 0 && $phase2_failed === 0) {
            $success_messages[] = "🎉 <strong>Instalación completada exitosamente!</strong>";
            $success_messages[] = "Puedes acceder al sistema con:";
            $success_messages[] = "<strong>Email:</strong> auditorexchile@gmail.com";
            $success_messages[] = "<strong>Username:</strong> auditorex chile";
            $success_messages[] = "<strong>Password:</strong> password";
        } else {
            $total_errors = $phase1_failed + $phase2_failed;
            $errors[] = "⚠️ Instalación completada con $total_errors errores (algunos pueden ser normales)";
        }
    } else {
        $errors[] = "El sistema ya está instalado. Marca 'Forzar reinstalación' si deseas reinstalar.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .installer-container {
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .installer-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .installer-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .installer-body {
            padding: 40px;
            overflow-y: auto;
        }

        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .info-section h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #333;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
        }

        .info-value {
            color: #333;
        }

        .status-connected {
            color: #28a745;
            font-weight: 600;
        }

        .status-error {
            color: #dc3545;
            font-weight: 600;
        }

        .btn-install {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .alert ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .alert li {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .sql-files-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .sql-file-item {
            padding: 10px;
            background: white;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sql-file-item i {
            color: #667eea;
        }

        .home-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .home-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .home-link a:hover {
            text-decoration: underline;
        }

        .error-count {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1><i class="fas fa-rocket"></i> CONECTA ERP</h1>
            <p>Instalador Automático del Sistema</p>
        </div>

        <div class="installer-body">
            <!-- Mensajes de éxito -->
            <?php if (!empty($success_messages)): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Instalación Exitosa</h5>
                    <?php foreach ($success_messages as $msg): ?>
                        <div><?php echo $msg; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Mensajes de error -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5>
                        <i class="fas fa-exclamation-triangle"></i> Errores de Instalación
                        <span class="error-count"><?php echo count($errors); ?> errores</span>
                    </h5>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-3 mb-0">
                        <strong>Nota:</strong> Algunos errores son normales durante la instalación inicial (tablas que no existen aún, permisos de RELOAD, etc.).
                        Si ves mensajes de éxito arriba, el sistema se instaló correctamente.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Información de conexión -->
            <div class="info-section">
                <h3><i class="fas fa-database"></i> Configuración de Base de Datos</h3>
                <div class="info-item">
                    <span class="info-label">Host:</span>
                    <span class="info-value"><?php echo DB_HOST; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Base de Datos:</span>
                    <span class="info-value"><?php echo DB_NAME; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Usuario:</span>
                    <span class="info-value"><?php echo DB_USER; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado de Conexión:</span>
                    <span class="<?php echo isset($conn) && $conn->ping() ? 'status-connected' : 'status-error'; ?>">
                        <?php echo isset($conn) && $conn->ping() ? '✓ Conectado' : '✗ Error de conexión'; ?>
                    </span>
                </div>
                <?php if ($already_installed): ?>
                <div class="info-item">
                    <span class="info-label">Estado de Instalación:</span>
                    <span class="status-connected">✓ Sistema ya instalado</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Archivos SQL a instalar -->
            <div class="sql-files-list">
                <h3 style="font-size: 1.2rem; margin-bottom: 15px; color: #333;">
                    <i class="fas fa-file-code"></i> Archivos SQL a Ejecutar (<?php echo count($sql_files); ?> archivos)
                </h3>
                <div style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($sql_files as $index => $file): ?>
                        <div class="sql-file-item">
                            <i class="fas fa-check-circle"></i>
                            <strong><?php echo $index + 1; ?>.</strong>
                            <?php echo basename($file); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Formulario de instalación -->
            <?php if (empty($success_messages)): ?>
            <form method="POST" action="">
                <?php if ($already_installed): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Advertencia:</strong> El sistema ya está instalado. Si continúas, se eliminarán todos los datos existentes.
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="force_reinstall" id="force_reinstall" required>
                        <label class="form-check-label" for="force_reinstall">
                            Confirmo que deseo reinstalar el sistema (se perderán todos los datos)
                        </label>
                    </div>
                <?php endif; ?>

                <button type="submit" name="install" class="btn-install">
                    <i class="fas fa-download"></i>
                    <?php echo $already_installed ? 'Reinstalar Sistema' : 'Instalar CONECTA ERP'; ?>
                </button>
            </form>
            <?php endif; ?>

            <!-- Links de navegación -->
            <div class="home-link">
                <?php if (!empty($success_messages)): ?>
                    <a href="index.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-home"></i> Ir al Inicio y Registrarse
                    </a>
                <?php else: ?>
                    <a href="index.php">
                        <i class="fas fa-home"></i> Volver al Inicio
                    </a>
                <?php endif; ?>
            </div>

            <!-- Información del Super Admin -->
            <?php if (empty($success_messages) && !$already_installed): ?>
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-info-circle"></i> Información Importante</h6>
                <p class="mb-1">Después de la instalación, podrás acceder como Super Administrador con:</p>
                <p class="mb-1"><strong>Email:</strong> auditorexchile@gmail.com</p>
                <p class="mb-1"><strong>Username:</strong> auditorex chile</p>
                <p class="mb-0"><strong>Password:</strong> password</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
