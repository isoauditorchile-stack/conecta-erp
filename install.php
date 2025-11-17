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

    // Módulos del sistema (19 módulos completos)
    'sql/modulos/01_entidades.sql',
    'sql/modulos/02_ventas.sql',
    'sql/modulos/03_compras.sql',
    'sql/modulos/04_inventario.sql',
    'sql/modulos/05_contabilidad.sql',
    'sql/modulos/06_rrhh.sql',
    'sql/modulos/07_crm.sql',
    'sql/modulos/08_produccion.sql',
    'sql/modulos/09_finanzas.sql',
    'sql/modulos/10_logistica.sql',
    'sql/modulos/11_marketing.sql',
    'sql/modulos/12_business_intelligence.sql',
    'sql/modulos/13_configuracion_avanzada.sql',
    'sql/modulos/14_api_y_reportes.sql',
    'sql/modulos/15_ecommerce.sql',
    'sql/modulos/16_proyectos_calidad_mantenimiento.sql',
    'sql/modulos/17_bi_avanzado.sql',
    'sql/modulos/18_reloj_control.sql',
    'sql/modulos/19_password_recovery.sql'
];

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

        foreach ($sql_files as $file) {
            $file_path = __DIR__ . '/' . $file;

            if (!file_exists($file_path)) {
                $errors[] = "El archivo $file no existe";
                continue;
            }

            $sql_content = file_get_contents($file_path);

            // Dividir el contenido por declaraciones SQL
            $statements = array_filter(
                array_map('trim', explode(';', $sql_content)),
                function($stmt) {
                    return !empty($stmt) && substr(trim($stmt), 0, 2) !== '--';
                }
            );

            $file_basename = basename($file);
            $executed = 0;
            $failed = 0;

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;

                // Ejecutar la declaración
                if ($conn->query($statement)) {
                    $executed++;
                } else {
                    $failed++;
                    $errors[] = "Error en $file_basename: " . $conn->error;
                }
            }

            if ($failed === 0) {
                $success_messages[] = "✓ $file_basename: $executed declaraciones ejecutadas exitosamente";
            } else {
                $errors[] = "✗ $file_basename: $failed declaraciones fallaron de $executed totales";
            }
        }

        // Reactivar verificación de foreign keys
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        if (empty($errors)) {
            $success_messages[] = "🎉 <strong>Instalación completada exitosamente!</strong>";
            $success_messages[] = "Puedes acceder al sistema con:";
            $success_messages[] = "<strong>Email:</strong> auditorexchile@gmail.com";
            $success_messages[] = "<strong>Username:</strong> auditorex chile";
            $success_messages[] = "<strong>Password:</strong> password";
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
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
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
                    <h5><i class="fas fa-exclamation-triangle"></i> Errores de Instalación</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
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
                    <span class="<?php echo empty($errors) ? 'status-connected' : 'status-error'; ?>">
                        <?php echo empty($errors) ? '✓ Conectado' : '✗ Error de conexión'; ?>
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
                    <i class="fas fa-file-code"></i> Archivos SQL a Ejecutar
                </h3>
                <?php foreach ($sql_files as $index => $file): ?>
                    <div class="sql-file-item">
                        <i class="fas fa-check-circle"></i>
                        <strong><?php echo $index + 1; ?>.</strong>
                        <?php echo basename($file); ?>
                    </div>
                <?php endforeach; ?>
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
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Ir al Login
                    </a>
                    |
                <?php endif; ?>
                <a href="index.php">
                    <i class="fas fa-home"></i> Ir al Inicio
                </a>
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
