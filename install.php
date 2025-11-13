<?php
/**
 * CONECTA ERP v2.0.0 - INSTALADOR WEB COMPLETO
 * Instala TODO el sistema automáticamente desde el navegador
 *
 * INSTRUCCIONES:
 * 1. Sube todo el proyecto a tu servidor
 * 2. Accede a: http://tu-dominio.com/install.php
 * 3. Sigue los pasos en pantalla
 * 4. ¡Listo!
 */

session_start();

// Evitar timeouts
set_time_limit(300);
ini_set('max_execution_time', 300);

// Detectar el paso actual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si es POST, detectar el paso desde los datos enviados
    if (isset($_POST['db_config'])) {
        $step = 2;
    } elseif (isset($_POST['install_database'])) {
        $step = 3;
    } else {
        $step = 1;
    }
} else {
    // Si es GET, usar el parámetro step de la URL
    $step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
}

$error = '';
$success = '';

// Función para verificar requisitos
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'Extension: PDO' => extension_loaded('pdo'),
        'Extension: PDO MySQL' => extension_loaded('pdo_mysql'),
        'Extension: JSON' => extension_loaded('json'),
        'Extension: mbstring' => extension_loaded('mbstring'),
        'Extension: OpenSSL' => extension_loaded('openssl'),
        'Writable: includes/' => is_writable(__DIR__ . '/includes'),
    ];

    return $requirements;
}

// Función para probar conexión MySQL
function testMySQLConnection($host, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para crear base de datos
function createDatabase($pdo, $dbname) {
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para ejecutar archivo SQL
function executeSQLFile($pdo, $filepath) {
    if (!file_exists($filepath)) {
        return ['success' => false, 'error' => 'Archivo no encontrado: ' . $filepath];
    }

    $sql = file_get_contents($filepath);

    // Separar declaraciones
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $sql)),
        function($stmt) {
            return !empty($stmt)
                && !preg_match('/^--/', $stmt)
                && !preg_match('/^\/\*/', $stmt)
                && strlen(trim($stmt)) > 5;
        }
    );

    $executed = 0;
    $errors = [];

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignorar errores de "ya existe"
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }

    return [
        'success' => count($errors) === 0,
        'executed' => $executed,
        'errors' => $errors
    ];
}

// Función para crear config.php
function createConfigFile($host, $dbname, $user, $pass) {
    $config_content = <<<PHP
<?php
/**
 * CONECTA ERP - Configuración de Base de Datos
 * Generado automáticamente por el instalador
 */

// Configuración de Base de Datos
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_CHARSET', 'utf8mb4');

// Configuración de Sesión
define('SESSION_NAME', 'conecta_erp_session');
define('SESSION_LIFETIME', 3600); // 1 hora

// Timezone
date_default_timezone_set('America/Santiago');

/**
 * Clase Database Singleton
 */
class Database {
    private static \$instance = null;
    private \$connection;

    private function __construct() {
        try {
            \$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die("Error de conexión a la base de datos: " . \$e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    public function getConnection() {
        return \$this->connection;
    }

    public function query(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            \$stmt->execute(\$params);
            return \$stmt;
        } catch (PDOException \$e) {
            error_log("Database query error: " . \$e->getMessage());
            throw \$e;
        }
    }

    public function fetchOne(\$sql, \$params = []) {
        return \$this->query(\$sql, \$params)->fetch();
    }

    public function fetchAll(\$sql, \$params = []) {
        return \$this->query(\$sql, \$params)->fetchAll();
    }

    public function insert(\$sql, \$params = []) {
        \$this->query(\$sql, \$params);
        return \$this->connection->lastInsertId();
    }

    public function update(\$sql, \$params = []) {
        return \$this->query(\$sql, \$params)->rowCount();
    }
}

/**
 * Inicializar sesión
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 1 : 0);
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Log de actividad
 */
function logActivity(\$user_id, \$action, \$description = '', \$module = '') {
    try {
        \$db = Database::getInstance();
        \$db->insert(
            "INSERT INTO activity_log (user_id, action, description, module, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                \$user_id,
                \$action,
                \$description,
                \$module,
                \$_SERVER['REMOTE_ADDR'] ?? 'unknown',
                \$_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );
    } catch (Exception \$e) {
        error_log("Error logging activity: " . \$e->getMessage());
    }
}

/**
 * Formatear fecha
 */
function formatDate(\$date, \$format = 'Y-m-d H:i:s') {
    if (empty(\$date)) return '';
    \$datetime = new DateTime(\$date);
    return \$datetime->format(\$format);
}

/**
 * Sistema de alertas
 */
function showAlert(\$message, \$type = 'info') {
    \$_SESSION['alert'] = ['message' => \$message, 'type' => \$type];
}

function getAlert() {
    if (isset(\$_SESSION['alert'])) {
        \$alert = \$_SESSION['alert'];
        unset(\$_SESSION['alert']);
        return \$alert;
    }
    return null;
}
PHP;

    $config_file = __DIR__ . '/includes/config.php';
    $result = file_put_contents($config_file, $config_content);

    if ($result !== false) {
        chmod($config_file, 0644);
        return ['success' => true];
    }

    return ['success' => false, 'error' => 'No se pudo escribir el archivo config.php'];
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // PASO 2: Configuración de Base de Datos
    if ($step === 2 && isset($_POST['db_config'])) {
        $host = trim($_POST['db_host']);
        $user = trim($_POST['db_user']);
        $pass = trim($_POST['db_pass']);
        $dbname = trim($_POST['db_name']);

        // Guardar en sesión
        $_SESSION['db_host'] = $host;
        $_SESSION['db_user'] = $user;
        $_SESSION['db_pass'] = $pass;
        $_SESSION['db_name'] = $dbname;

        // Probar conexión
        $test = testMySQLConnection($host, $user, $pass);

        if ($test['success']) {
            // Crear base de datos
            $create = createDatabase($test['pdo'], $dbname);

            if ($create['success']) {
                $success = "✓ Conexión exitosa. Base de datos '$dbname' lista.";
                $step = 3; // Avanzar al siguiente paso
            } else {
                $error = "Error al crear la base de datos: " . $create['error'];
            }
        } else {
            $error = "Error de conexión: " . $test['error'];
        }
    }

    // PASO 3: Instalar Tablas y Datos
    if ($step === 3 && isset($_POST['install_database'])) {
        $host = $_SESSION['db_host'];
        $user = $_SESSION['db_user'];
        $pass = $_SESSION['db_pass'];
        $dbname = $_SESSION['db_name'];

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Ejecutar scripts SQL en orden
            $scripts = [
                'database/schema_completo_base.sql',
                'database/schema_improvements.sql',
                'database/schema_planes_pagos.sql'
            ];

            $_SESSION['installation_log'] = [];
            $all_success = true;

            foreach ($scripts as $script) {
                $filepath = __DIR__ . '/' . $script;
                $result = executeSQLFile($pdo, $filepath);

                $_SESSION['installation_log'][] = [
                    'script' => basename($script),
                    'success' => $result['success'],
                    'executed' => $result['executed'] ?? 0,
                    'errors' => $result['errors'] ?? []
                ];

                if (!$result['success']) {
                    $all_success = false;
                }
            }

            if ($all_success) {
                // Crear archivo config.php
                $config = createConfigFile($host, $dbname, $user, $pass);

                if ($config['success']) {
                    $_SESSION['installation_complete'] = true;
                    $step = 4; // Paso final
                } else {
                    $error = "Error al crear config.php: " . ($config['error'] ?? 'Desconocido');
                }
            } else {
                $error = "Hubo errores al instalar las tablas. Revisa el log.";
            }

        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador CONECTA ERP v2.0.0</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; }
        .content { padding: 2rem; }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            position: relative;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: -1;
        }
        .step:last-child::after { display: none; }
        .step.active { color: #667eea; font-weight: 600; }
        .step.active .step-number { background: #667eea; color: white; }
        .step.completed { color: #10b981; }
        .step.completed .step-number { background: #10b981; color: white; }
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 2px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 2px solid #ef4444; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 2px solid #f59e0b; }
        .requirements { list-style: none; }
        .requirements li {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .requirements li.pass { background: #d1fae5; color: #065f46; }
        .requirements li.fail { background: #fee2e2; color: #991b1b; }
        .icon-check::before { content: '✓'; font-weight: bold; }
        .icon-cross::before { content: '✗'; font-weight: bold; }
        .installation-log {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }
        .log-item { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; }
        .log-item:last-child { border-bottom: none; }
        .success-box {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 2rem;
        }
        .success-box h2 { color: #065f46; margin-bottom: 1rem; }
        .success-box .big-icon { font-size: 4rem; margin-bottom: 1rem; }
        .links { display: flex; gap: 1rem; flex-wrap: wrap; }
        .link-box {
            flex: 1;
            min-width: 200px;
            padding: 1.5rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
        }
        .link-box:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .info-box {
            background: #f0f9ff;
            border: 2px solid #3b82f6;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .info-box strong { color: #1e40af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador CONECTA ERP</h1>
            <p>Sistema de Producción REAL v2.0.0</p>
        </div>

        <div class="content">
            <!-- Indicador de pasos -->
            <div class="steps">
                <div class="step <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                    <div class="step-number">1</div>
                    <div>Requisitos</div>
                </div>
                <div class="step <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                    <div class="step-number">2</div>
                    <div>Base de Datos</div>
                </div>
                <div class="step <?php echo $step === 3 ? 'active' : ($step > 3 ? 'completed' : ''); ?>">
                    <div class="step-number">3</div>
                    <div>Instalación</div>
                </div>
                <div class="step <?php echo $step === 4 ? 'active' : ''; ?>">
                    <div class="step-number">4</div>
                    <div>Completado</div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="icon-cross"></span>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="icon-check"></span>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- PASO 1: Verificar Requisitos -->
            <h2 style="margin-bottom: 1.5rem;">Paso 1: Verificar Requisitos del Sistema</h2>

            <ul class="requirements">
                <?php
                $requirements = checkRequirements();
                $all_pass = true;
                foreach ($requirements as $name => $pass):
                    if (!$pass) $all_pass = false;
                ?>
                <li class="<?php echo $pass ? 'pass' : 'fail'; ?>">
                    <span class="<?php echo $pass ? 'icon-check' : 'icon-cross'; ?>"></span>
                    <span><?php echo htmlspecialchars($name); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($all_pass): ?>
            <div style="margin-top: 2rem;">
                <a href="?step=2" class="btn">Continuar a Configuración →</a>
            </div>
            <?php else: ?>
            <div class="alert alert-error" style="margin-top: 2rem;">
                <span class="icon-cross"></span>
                <div>
                    <strong>Requisitos no cumplidos.</strong><br>
                    Por favor, instala las extensiones faltantes y asegúrate de que el directorio 'includes/' sea escribible.
                </div>
            </div>
            <?php endif; ?>

            <?php elseif ($step === 2): ?>
            <!-- PASO 2: Configuración de Base de Datos -->
            <h2 style="margin-bottom: 1.5rem;">Paso 2: Configuración de Base de Datos</h2>

            <div class="info-box">
                <strong>ℹ️ Información:</strong><br>
                Ingresa las credenciales de tu base de datos MySQL. Si la base de datos no existe, se creará automáticamente.
            </div>

            <form method="POST" action="?step=2">
                <input type="hidden" name="db_config" value="1">

                <div class="form-group">
                    <label>Host de MySQL</label>
                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($_SESSION['db_host'] ?? 'localhost'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Usuario de MySQL</label>
                    <input type="text" name="db_user" value="<?php echo htmlspecialchars($_SESSION['db_user'] ?? 'conectae_conectaerpuser'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Contraseña de MySQL</label>
                    <input type="password" name="db_pass" value="<?php echo htmlspecialchars($_SESSION['db_pass'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Nombre de la Base de Datos</label>
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($_SESSION['db_name'] ?? 'conectae_conectaerpbd'); ?>" required>
                </div>

                <button type="submit" class="btn">Probar Conexión y Continuar →</button>
            </form>

            <?php elseif ($step === 3): ?>
            <!-- PASO 3: Instalar Tablas y Datos -->
            <h2 style="margin-bottom: 1.5rem;">Paso 3: Instalar Tablas y Datos</h2>

            <div class="alert alert-warning">
                <span>⚠️</span>
                <div>
                    <strong>Importante:</strong><br>
                    Este paso instalará todas las tablas y datos iniciales en tu base de datos.<br>
                    Esto incluye:
                    <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                        <li>14 módulos principales</li>
                        <li>106 submódulos (100% en español)</li>
                        <li>8 países configurados</li>
                        <li>5 planes de suscripción</li>
                        <li>Sistema de usuarios, pagos y notificaciones</li>
                        <li>Traducciones en 8 idiomas</li>
                    </ul>
                </div>
            </div>

            <form method="POST" action="?step=3">
                <input type="hidden" name="install_database" value="1">
                <button type="submit" class="btn">Instalar Ahora →</button>
            </form>

            <?php elseif ($step === 4): ?>
            <!-- PASO 4: Instalación Completada -->
            <div class="success-box">
                <div class="big-icon">🎉</div>
                <h2>¡Instalación Completada Exitosamente!</h2>
                <p>CONECTA ERP v2.0.0 está listo para usar</p>
            </div>

            <?php if (isset($_SESSION['installation_log'])): ?>
            <h3 style="margin-bottom: 1rem;">Resumen de Instalación:</h3>
            <div class="installation-log">
                <?php foreach ($_SESSION['installation_log'] as $log): ?>
                <div class="log-item">
                    <strong><?php echo htmlspecialchars($log['script']); ?></strong><br>
                    <span style="color: <?php echo $log['success'] ? '#10b981' : '#ef4444'; ?>;">
                        <?php echo $log['success'] ? '✓ Exitoso' : '✗ Con errores'; ?>
                    </span>
                    - Ejecutadas: <?php echo $log['executed']; ?> declaraciones
                    <?php if (!empty($log['errors'])): ?>
                    <br><span style="color: #ef4444;">Errores: <?php echo count($log['errors']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h3 style="margin: 2rem 0 1rem 0;">Próximos Pasos:</h3>

            <div class="links">
                <a href="index.php" class="link-box">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏠</div>
                    <div>Ir a la Página Principal</div>
                </a>
                <a href="admin/panel_super_admin.php" class="link-box">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">👑</div>
                    <div>Panel Super Admin</div>
                </a>
            </div>

            <div class="info-box" style="margin-top: 2rem;">
                <strong>🔐 Crear tu cuenta de Super Admin:</strong><br>
                1. Haz click en "Ir a la Página Principal"<br>
                2. Regístrate con el email: <strong>auditorexchile@gmail.com</strong><br>
                3. Accederás automáticamente como Super Administrador<br>
                4. Desde el panel podrás aprobar usuarios, verificar pagos, etc.
            </div>

            <div class="alert alert-warning" style="margin-top: 1.5rem;">
                <span>⚠️</span>
                <div>
                    <strong>Importante por Seguridad:</strong><br>
                    Elimina o renombra el archivo <code>install.php</code> para evitar reinstalaciones accidentales.
                </div>
            </div>

            <?php
            // Limpiar sesión
            unset($_SESSION['installation_log']);
            unset($_SESSION['db_host']);
            unset($_SESSION['db_user']);
            unset($_SESSION['db_pass']);
            unset($_SESSION['db_name']);
            ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
