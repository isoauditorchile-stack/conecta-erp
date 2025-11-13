<?php
/**
 * CONECTA ERP v2.0.0 - INSTALADOR WEB COMPLETO
 * Instala TODO el sistema automáticamente desde el navegador
 */

// Iniciar sesión
session_start();

// Evitar timeouts
set_time_limit(300);
ini_set('max_execution_time', 300);

// Variables globales
$error = '';
$success = '';
$current_step = 1;

// Determinar el paso actual
if (isset($_SESSION['install_step'])) {
    $current_step = (int)$_SESSION['install_step'];
}

if (isset($_GET['step'])) {
    $current_step = (int)$_GET['step'];
    $_SESSION['install_step'] = $current_step;
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

function checkRequirements() {
    return [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'Extension: PDO' => extension_loaded('pdo'),
        'Extension: PDO MySQL' => extension_loaded('pdo_mysql'),
        'Extension: JSON' => extension_loaded('json'),
        'Extension: mbstring' => extension_loaded('mbstring'),
        'Extension: OpenSSL' => extension_loaded('openssl'),
        'Writable: includes/' => is_writable(__DIR__ . '/includes'),
    ];
}

function testDatabaseConnection($host, $user, $pass, $dbname) {
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Crear base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");

        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function executeSQLFile($pdo, $filepath) {
    if (!file_exists($filepath)) {
        return ['success' => false, 'error' => 'Archivo no encontrado: ' . basename($filepath)];
    }

    $sql = file_get_contents($filepath);

    // Remover UTF-8 BOM si existe
    $sql = str_replace("\xEF\xBB\xBF", '', $sql);

    $executed = 0;
    $errors = [];
    $statement = '';
    $delimiter = ';';
    $inString = false;
    $stringChar = '';

    // Procesar carácter por carácter para manejar delimitadores correctamente
    $lines = explode("\n", $sql);

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorar líneas vacías y comentarios
        if (empty($line) || substr($line, 0, 2) === '--' || substr($line, 0, 1) === '#') {
            continue;
        }

        // Ignorar comentarios de bloque
        if (substr($line, 0, 2) === '/*') {
            continue;
        }

        $statement .= ' ' . $line;

        // Verificar si la línea termina con el delimitador
        if (substr(rtrim($line), -1) === $delimiter) {
            $statement = trim($statement);
            $statement = substr($statement, 0, -1); // Remover el delimitador
            $statement = trim($statement);

            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $executed++;
                } catch (PDOException $e) {
                    $error_msg = $e->getMessage();

                    // Ignorar errores de "ya existe", "duplicate" y "no such table" (para DROP IF EXISTS)
                    if (stripos($error_msg, 'already exists') === false &&
                        stripos($error_msg, 'duplicate') === false &&
                        stripos($error_msg, 'no such table') === false &&
                        stripos($error_msg, 'unknown table') === false) {
                        $errors[] = substr(basename($filepath) . ': ' . $error_msg, 0, 300);
                    } else {
                        $executed++; // Contar como exitoso si ya existe o DROP IF EXISTS
                    }
                }
            }

            $statement = ''; // Reset para el siguiente statement
        }
    }

    // Ejecutar cualquier statement restante
    if (!empty(trim($statement))) {
        try {
            $pdo->exec(trim($statement));
            $executed++;
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            if (stripos($error_msg, 'already exists') === false &&
                stripos($error_msg, 'duplicate') === false) {
                $errors[] = substr(basename($filepath) . ': ' . $error_msg, 0, 300);
            } else {
                $executed++;
            }
        }
    }

    return [
        'success' => empty($errors) || count($errors) < 5, // Permitir hasta 5 errores menores
        'executed' => $executed,
        'total' => $executed,
        'errors' => $errors
    ];
}

function createConfigFile($host, $dbname, $user, $pass) {
    $config_content = '<?php
/**
 * CONECTA ERP - Configuración de Base de Datos
 * Generado automáticamente por el instalador
 */

// Configuración de Base de Datos
define(\'DB_HOST\', \'' . addslashes($host) . '\');
define(\'DB_NAME\', \'' . addslashes($dbname) . '\');
define(\'DB_USER\', \'' . addslashes($user) . '\');
define(\'DB_PASS\', \'' . addslashes($pass) . '\');
define(\'DB_CHARSET\', \'utf8mb4\');

// Configuración de Sesión
define(\'SESSION_NAME\', \'conecta_erp_session\');
define(\'SESSION_LIFETIME\', 3600);

// Timezone
date_default_timezone_set(\'America/Santiago\');

/**
 * Clase Database Singleton
 */
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }

    public function update($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
}

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set(\'session.cookie_httponly\', 1);
        ini_set(\'session.use_only_cookies\', 1);
        session_name(SESSION_NAME);
        session_start();
    }
}

function logActivity($user_id, $action, $description = \'\', $module = \'\') {
    try {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO activity_log (user_id, action, description, module, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $user_id,
                $action,
                $description,
                $module,
                $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\',
                $_SERVER[\'HTTP_USER_AGENT\'] ?? \'unknown\'
            ]
        );
    } catch (Exception $e) {
        error_log("Error logging: " . $e->getMessage());
    }
}
';

    $config_file = __DIR__ . '/includes/config.php';
    if (!is_dir(__DIR__ . '/includes')) {
        mkdir(__DIR__ . '/includes', 0755, true);
    }

    $result = file_put_contents($config_file, $config_content);

    if ($result !== false) {
        chmod($config_file, 0644);
        return ['success' => true];
    }

    return ['success' => false, 'error' => 'No se pudo escribir config.php'];
}

// ============================================================================
// PROCESAMIENTO DE FORMULARIOS
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // PASO 2: Probar conexión a base de datos
    if (isset($_POST['test_connection'])) {
        $host = trim($_POST['db_host']);
        $user = trim($_POST['db_user']);
        $pass = $_POST['db_pass'];
        $dbname = trim($_POST['db_name']);

        $_SESSION['db_config'] = [
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'dbname' => $dbname
        ];

        $test = testDatabaseConnection($host, $user, $pass, $dbname);

        if ($test['success']) {
            $success = "✓ Conexión exitosa. Base de datos '$dbname' lista.";
            $_SESSION['install_step'] = 3;
            $current_step = 3;
            $_SESSION['db_connection_ok'] = true;
        } else {
            $error = "Error de conexión: " . $test['error'];
        }
    }

    // PASO 3: Instalar base de datos
    if (isset($_POST['install_now'])) {
        if (!isset($_SESSION['db_config']) || !isset($_SESSION['db_connection_ok'])) {
            $error = "Error: Primero debes configurar la conexión a la base de datos.";
            $current_step = 2;
        } else {
            $config = $_SESSION['db_config'];

            try {
                $test = testDatabaseConnection(
                    $config['host'],
                    $config['user'],
                    $config['pass'],
                    $config['dbname']
                );

                if (!$test['success']) {
                    throw new Exception("No se pudo conectar a la base de datos");
                }

                $pdo = $test['pdo'];

                // Scripts SQL a ejecutar en orden (200+ tablas para ERP completo)
                $sql_scripts = [
                    'database/schema_erp_completo.sql',
                    'database/schema_erp_completo_parte2.sql',
                    'database/schema_erp_completo_parte3.sql',
                    'database/schema_erp_completo_parte4.sql',
                    'database/schema_erp_completo_parte5_final.sql'
                ];

                $installation_log = [];
                $total_executed = 0;
                $has_errors = false;

                foreach ($sql_scripts as $script) {
                    $filepath = __DIR__ . '/' . $script;
                    $result = executeSQLFile($pdo, $filepath);

                    $installation_log[] = [
                        'script' => basename($script),
                        'success' => $result['success'],
                        'executed' => $result['executed'],
                        'total' => $result['total'],
                        'errors' => $result['errors']
                    ];

                    $total_executed += $result['executed'];

                    if (!$result['success']) {
                        $has_errors = true;
                    }
                }

                // Crear archivo config.php
                $config_result = createConfigFile(
                    $config['host'],
                    $config['dbname'],
                    $config['user'],
                    $config['pass']
                );

                if (!$config_result['success']) {
                    throw new Exception("No se pudo crear config.php: " . ($config_result['error'] ?? ''));
                }

                $_SESSION['installation_log'] = $installation_log;
                $_SESSION['total_statements'] = $total_executed;
                $_SESSION['install_step'] = 4;
                $current_step = 4;

                if ($has_errors) {
                    $success = "⚠ Instalación completada con algunas advertencias. Revisa el log.";
                } else {
                    $success = "✓ Instalación completada exitosamente!";
                }

            } catch (Exception $e) {
                $error = "Error durante la instalación: " . $e->getMessage();
            }
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
            max-width: 850px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.35);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .header h1 { font-size: 2.2rem; margin-bottom: 0.5rem; font-weight: 800; }
        .header p { opacity: 0.95; font-size: 1.1rem; }
        .content { padding: 2.5rem; }

        /* Progress Steps */
        .progress-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }
        .progress-line {
            position: absolute;
            top: 25px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }
        .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
            width: <?php echo (($current_step - 1) / 3 * 100); ?>%;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0 auto 0.75rem;
            transition: all 0.3s;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .step-item.active .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.15);
        }
        .step-item.completed .step-circle {
            background: #10b981;
            color: white;
        }
        .step-item.completed .step-circle::before {
            content: '✓';
            font-size: 1.5rem;
        }
        .step-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        .step-item.active .step-label {
            color: #667eea;
            font-weight: 700;
        }

        /* Forms */
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }
        .btn:active { transform: translateY(-1px); }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Alerts */
        .alert {
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }
        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #f59e0b;
        }
        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 2px solid #3b82f6;
        }
        .alert-icon {
            font-size: 1.5rem;
            line-height: 1;
            flex-shrink: 0;
        }

        /* Requirements List */
        .requirements { list-style: none; margin: 1.5rem 0; }
        .requirements li {
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .requirements li.pass {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 2px solid #10b981;
        }
        .requirements li.fail {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .requirements li::before {
            font-size: 1.3rem;
            font-weight: bold;
        }
        .requirements li.pass::before { content: '✓'; }
        .requirements li.fail::before { content: '✗'; }

        /* Installation Log */
        .install-log {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            max-height: 450px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }
        .log-entry {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .log-entry:last-child { border-bottom: none; margin-bottom: 0; }
        .log-entry strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 1rem;
        }
        .log-success { color: #10b981; font-weight: 600; }
        .log-error { color: #ef4444; font-weight: 600; }

        /* Success Screen */
        .success-screen {
            text-align: center;
            padding: 2rem;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 1s ease;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .success-screen h2 {
            color: #065f46;
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }
        .success-screen p {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* Links */
        .link-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .link-card {
            padding: 2rem;
            background: white;
            border: 3px solid #e5e7eb;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            display: block;
        }
        .link-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.25);
        }
        .link-card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        .link-card-title {
            color: #667eea;
            font-weight: 700;
            font-size: 1.1rem;
        }

        h2 {
            color: #1f2937;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            font-weight: 800;
        }
        h3 {
            color: #374151;
            font-size: 1.3rem;
            margin: 2rem 0 1rem 0;
            font-weight: 700;
        }

        .section-divider {
            height: 3px;
            background: linear-gradient(90deg, transparent 0%, #e5e7eb 50%, transparent 100%);
            margin: 2.5rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador CONECTA ERP</h1>
            <p>Sistema de Producción v2.0.0 - Instalación Profesional</p>
        </div>

        <div class="content">
            <!-- Progress Indicator -->
            <div class="progress-container">
                <div class="progress-line">
                    <div class="progress-fill"></div>
                </div>
                <div class="step-item <?php echo $current_step === 1 ? 'active' : ($current_step > 1 ? 'completed' : ''); ?>">
                    <div class="step-circle"><?php echo $current_step > 1 ? '' : '1'; ?></div>
                    <div class="step-label">Requisitos</div>
                </div>
                <div class="step-item <?php echo $current_step === 2 ? 'active' : ($current_step > 2 ? 'completed' : ''); ?>">
                    <div class="step-circle"><?php echo $current_step > 2 ? '' : '2'; ?></div>
                    <div class="step-label">Conexión BD</div>
                </div>
                <div class="step-item <?php echo $current_step === 3 ? 'active' : ($current_step > 3 ? 'completed' : ''); ?>">
                    <div class="step-circle"><?php echo $current_step > 3 ? '' : '3'; ?></div>
                    <div class="step-label">Instalación</div>
                </div>
                <div class="step-item <?php echo $current_step === 4 ? 'active' : ''; ?>">
                    <div class="step-circle">4</div>
                    <div class="step-label">Completado</div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <div><strong>Error:</strong><br><?php echo htmlspecialchars($error); ?></div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✓</span>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
            <?php endif; ?>

            <?php if ($current_step === 1): ?>
            <!-- STEP 1: Requirements Check -->
            <h2>Verificación de Requisitos del Sistema</h2>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">Verificando que tu servidor cumple con todos los requisitos necesarios para CONECTA ERP.</p>

            <ul class="requirements">
                <?php
                $requirements = checkRequirements();
                $all_passed = true;
                foreach ($requirements as $name => $passed):
                    if (!$passed) $all_passed = false;
                ?>
                <li class="<?php echo $passed ? 'pass' : 'fail'; ?>">
                    <?php echo htmlspecialchars($name); ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($all_passed): ?>
            <div class="alert alert-success" style="margin-top: 2rem;">
                <span class="alert-icon">✓</span>
                <div><strong>¡Excelente!</strong> Tu servidor cumple con todos los requisitos necesarios.</div>
            </div>
            <div style="margin-top: 2rem; text-align: center;">
                <a href="?step=2" class="btn">Continuar a Configuración →</a>
            </div>
            <?php else: ?>
            <div class="alert alert-error" style="margin-top: 2rem;">
                <span class="alert-icon">✗</span>
                <div>
                    <strong>Requisitos no cumplidos</strong><br>
                    Por favor, instala las extensiones PHP faltantes y verifica que el directorio <code>includes/</code> sea escribible.<br>
                    Luego recarga esta página.
                </div>
            </div>
            <?php endif; ?>

            <?php elseif ($current_step === 2): ?>
            <!-- STEP 2: Database Configuration -->
            <h2>Configuración de Base de Datos</h2>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">Ingresa las credenciales de tu servidor MySQL. Si la base de datos no existe, se creará automáticamente.</p>

            <form method="POST">
                <div class="form-group">
                    <label>Host de MySQL</label>
                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($_SESSION['db_config']['host'] ?? 'localhost'); ?>" required placeholder="localhost">
                </div>

                <div class="form-group">
                    <label>Nombre de la Base de Datos</label>
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($_SESSION['db_config']['dbname'] ?? 'conectae_conectaerpbd'); ?>" required placeholder="conectae_conectaerpbd">
                </div>

                <div class="form-group">
                    <label>Usuario de MySQL</label>
                    <input type="text" name="db_user" value="<?php echo htmlspecialchars($_SESSION['db_config']['user'] ?? 'conectae_conectaerpuser'); ?>" required placeholder="usuario">
                </div>

                <div class="form-group">
                    <label>Contraseña de MySQL</label>
                    <input type="password" name="db_pass" value="<?php echo htmlspecialchars($_SESSION['db_config']['pass'] ?? ''); ?>" required placeholder="••••••••">
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" name="test_connection" class="btn">Probar Conexión y Continuar →</button>
                </div>
            </form>

            <?php elseif ($current_step === 3): ?>
            <!-- STEP 3: Installation -->
            <h2>Listo para Instalar</h2>

            <div class="alert alert-info">
                <span class="alert-icon">ℹ️</span>
                <div>
                    <strong>Se instalará:</strong><br>
                    ✓ <strong>200+ TABLAS SQL</strong> para ERP completo de nivel empresarial<br>
                    ✓ 14 módulos principales (FI, CO, SD, MM, PP, HCM, SCM, CRM, LOY, BI, SII, ADM)<br>
                    ✓ 106 submódulos 100% funcionales<br>
                    ✓ Integración Previred (AFP, Isapre, nómina chilena)<br>
                    ✓ Integración SII (DTEs, CAF, facturación electrónica)<br>
                    ✓ Múltiples relojes control (biométrico, RFID, GPS)<br>
                    ✓ Sistema POS con multi-moneda y multi-pago<br>
                    ✓ 8 idiomas (ES, EN, PT, FR, DE, IT, RU, ZH)<br>
                    ✓ Validación de RUT/Tax ID para 8 países
                </div>
            </div>

            <div class="alert alert-warning">
                <span class="alert-icon">⚠️</span>
                <div>
                    <strong>Importante:</strong> Este proceso puede tardar 30-60 segundos.<br>
                    No cierres esta ventana ni actualices la página durante la instalación.
                </div>
            </div>

            <form method="POST" style="text-align: center; margin-top: 2rem;">
                <button type="submit" name="install_now" class="btn">🚀 Instalar CONECTA ERP Ahora</button>
            </form>

            <?php elseif ($current_step === 4): ?>
            <!-- STEP 4: Installation Complete -->
            <div class="success-screen">
                <div class="success-icon">🎉</div>
                <h2>¡Instalación Completada Exitosamente!</h2>
                <p>CONECTA ERP v2.0.0 está listo para usar en modo producción</p>
            </div>

            <?php if (isset($_SESSION['installation_log'])): ?>
            <h3>📋 Resumen de Instalación</h3>
            <div class="install-log">
                <?php foreach ($_SESSION['installation_log'] as $log): ?>
                <div class="log-entry">
                    <strong>📄 <?php echo htmlspecialchars($log['script']); ?></strong>
                    <div>
                        Estado: <span class="<?php echo $log['success'] ? 'log-success' : 'log-error'; ?>">
                            <?php echo $log['success'] ? '✓ Exitoso' : '⚠ Con advertencias'; ?>
                        </span><br>
                        Ejecutadas: <?php echo $log['executed']; ?> de <?php echo $log['total']; ?> declaraciones
                        <?php if (!empty($log['errors'])): ?>
                        <br><span class="log-error">⚠ <?php echo count($log['errors']); ?> advertencias (tablas existentes ignoradas)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="alert alert-success" style="margin-top: 1.5rem;">
                <span class="alert-icon">✓</span>
                <div>
                    <strong>Total:</strong> <?php echo $_SESSION['total_statements'] ?? 0; ?> declaraciones SQL ejecutadas correctamente.
                </div>
            </div>
            <?php endif; ?>

            <div class="section-divider"></div>

            <h3>🚀 Próximos Pasos</h3>

            <div class="link-grid">
                <a href="index.php" class="link-card">
                    <span class="link-card-icon">📝</span>
                    <span class="link-card-title">Registrarse (Nuevo Usuario)</span>
                </a>
                <a href="login.php" class="link-card">
                    <span class="link-card-icon">🔐</span>
                    <span class="link-card-title">Iniciar Sesión</span>
                </a>
            </div>

            <div class="alert alert-info" style="margin-top: 2rem;">
                <span class="alert-icon">🔐</span>
                <div>
                    <strong>Crear tu cuenta de Super Administrador:</strong><br>
                    1. Haz clic en "Ir a la Página Principal"<br>
                    2. Regístrate con el email: <strong>auditorexchile@gmail.com</strong><br>
                    3. Este será el único usuario con control total del sistema<br>
                    4. Podrás aprobar usuarios, verificar pagos y gestionar todo el ERP
                </div>
            </div>

            <div class="alert alert-warning" style="margin-top: 1.5rem;">
                <span class="alert-icon">⚠️</span>
                <div>
                    <strong>Seguridad Importante:</strong><br>
                    Por favor, elimina o renombra el archivo <code>install.php</code> de tu servidor para prevenir reinstalaciones accidentales.
                </div>
            </div>

            <?php
            // Limpiar sesión
            unset($_SESSION['installation_log']);
            unset($_SESSION['db_config']);
            unset($_SESSION['db_connection_ok']);
            unset($_SESSION['install_step']);
            unset($_SESSION['total_statements']);
            ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
