<?php
/**
 * INSTALADOR WEB: Tablas de Catalogos para Quick Add
 * Sistema: CONECTA ERP
 * Modulo: Maestro de Clientes
 *
 * INSTRUCCIONES:
 * 1. Copie este archivo a la raiz del proyecto web
 * 2. Acceda via navegador: http://su-dominio/WEB_INSTALLER.php
 * 3. El script creara todas las tablas necesarias
 * 4. ELIMINE este archivo despues de la instalacion por seguridad
 */

session_start();

// Conexion a base de datos
$host = 'localhost';
$dbname = 'conectae_conectaerpbd';
$username = 'conectae_conectaerpuser';
$password = 'pt125824caraud';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador de Tablas - CONECTA ERP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #3b82f6;
        }
        .btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .log {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        .log div {
            margin: 5px 0;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Instalador de Tablas de Catalogos</h1>
        <p class="subtitle">CONECTA ERP - Maestro de Clientes</p>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo '<div class="log">';
        echo '<div class="info">▶ Iniciando instalacion...</div>';

        // Array de comandos SQL
        $sql_commands = [
            // Tabla: Tipos de Cliente
            "CREATE TABLE IF NOT EXISTS cat_tipos_cliente (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(50) NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                company_id INT NOT NULL DEFAULT 1,
                pais_id INT NOT NULL DEFAULT 1,
                activo TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
                INDEX idx_company (company_id),
                INDEX idx_pais (pais_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT IGNORE INTO cat_tipos_cliente (codigo, nombre, company_id, pais_id) VALUES
            ('NACIONAL', 'Cliente Nacional', 1, 1),
            ('INTERNACIONAL', 'Cliente Internacional', 1, 1),
            ('GOBIERNO', 'Cliente Gubernamental', 1, 1),
            ('CORPORATIVO', 'Cliente Corporativo', 1, 1)",

            // Tabla: Categorias de Cliente
            "CREATE TABLE IF NOT EXISTS cat_categorias_cliente (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(50) NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                company_id INT NOT NULL DEFAULT 1,
                pais_id INT NOT NULL DEFAULT 1,
                activo TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
                INDEX idx_company (company_id),
                INDEX idx_pais (pais_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT IGNORE INTO cat_categorias_cliente (codigo, nombre, company_id, pais_id) VALUES
            ('A', 'Categoria A - Premium', 1, 1),
            ('B', 'Categoria B - Estandar', 1, 1),
            ('C', 'Categoria C - Basico', 1, 1),
            ('VIP', 'Categoria VIP', 1, 1)",

            // Tabla: Grupos de Cliente
            "CREATE TABLE IF NOT EXISTS cat_grupos_cliente (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(50) NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                company_id INT NOT NULL DEFAULT 1,
                pais_id INT NOT NULL DEFAULT 1,
                activo TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
                INDEX idx_company (company_id),
                INDEX idx_pais (pais_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT IGNORE INTO cat_grupos_cliente (codigo, nombre, company_id, pais_id) VALUES
            ('RETAIL', 'Clientes Retail', 1, 1),
            ('MAYORISTA', 'Clientes Mayoristas', 1, 1),
            ('CORPORATIVO', 'Clientes Corporativos', 1, 1),
            ('DISTRIBUIDOR', 'Distribuidores', 1, 1)",

            // Tabla: Condiciones de Pago
            "CREATE TABLE IF NOT EXISTS cat_condiciones_pago (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(50) NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                dias INT DEFAULT 0,
                descripcion TEXT,
                company_id INT NOT NULL DEFAULT 1,
                pais_id INT NOT NULL DEFAULT 1,
                activo TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
                INDEX idx_company (company_id),
                INDEX idx_pais (pais_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT IGNORE INTO cat_condiciones_pago (codigo, nombre, dias, company_id, pais_id) VALUES
            ('CONTADO', 'Contado', 0, 1, 1),
            ('30DIAS', '30 Dias', 30, 1, 1),
            ('60DIAS', '60 Dias', 60, 1, 1),
            ('90DIAS', '90 Dias', 90, 1, 1),
            ('15DIAS', '15 Dias', 15, 1, 1),
            ('45DIAS', '45 Dias', 45, 1, 1)",

            // Tabla: Listas de Precio
            "CREATE TABLE IF NOT EXISTS cat_listas_precio (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(50) NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                descripcion TEXT,
                margen_porcentaje DECIMAL(10,2) DEFAULT 0,
                company_id INT NOT NULL DEFAULT 1,
                pais_id INT NOT NULL DEFAULT 1,
                activo TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_codigo_company_pais (codigo, company_id, pais_id),
                INDEX idx_company (company_id),
                INDEX idx_pais (pais_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT IGNORE INTO cat_listas_precio (codigo, nombre, margen_porcentaje, company_id, pais_id) VALUES
            ('GENERAL', 'Lista General', 0, 1, 1),
            ('MAYORISTA', 'Lista Mayorista', 15, 1, 1),
            ('RETAIL', 'Lista Retail', 30, 1, 1),
            ('VIP', 'Lista VIP', -10, 1, 1)"
        ];

        $success_count = 0;
        $error_count = 0;

        foreach ($sql_commands as $index => $sql) {
            try {
                $pdo->exec($sql);
                $success_count++;

                if (stripos($sql, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $sql, $matches);
                    $table = $matches[1] ?? "comando $index";
                    echo "<div class='success'>✓ Tabla creada: $table</div>";
                } elseif (stripos($sql, 'INSERT') !== false) {
                    preg_match('/INSERT.*?INTO\s+`?(\w+)`?/i', $sql, $matches);
                    $table = $matches[1] ?? "tabla";
                    echo "<div class='success'>✓ Datos insertados en: $table</div>";
                }
            } catch (PDOException $e) {
                $error_count++;
                echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        echo '<div class="info">▶ Verificando tablas...</div>';

        $tables = ['cat_tipos_cliente', 'cat_categorias_cliente', 'cat_grupos_cliente', 'cat_condiciones_pago', 'cat_listas_precio'];

        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "<div class='success'>✓ $table - OK ($count registros)</div>";
        }

        echo "<div class='info'>════════════════════════════════════════</div>";
        echo "<div class='info'>Comandos exitosos: $success_count</div>";
        echo "<div class='info'>Errores: $error_count</div>";
        echo "<div class='success'>✓ INSTALACION COMPLETADA</div>";
        echo '</div>';

        echo '<div class="alert alert-success">';
        echo '<strong>✓ Instalacion completada exitosamente</strong><br>';
        echo 'Todas las tablas han sido creadas y cargadas con datos iniciales.';
        echo '</div>';

        echo '<div class="alert alert-error">';
        echo '<strong>⚠ IMPORTANTE:</strong> Por seguridad, elimine este archivo (WEB_INSTALLER.php) despues de la instalacion.';
        echo '</div>';

    } catch (PDOException $e) {
        echo '<div class="alert alert-error">';
        echo '<strong>Error de conexion:</strong><br>';
        echo htmlspecialchars($e->getMessage());
        echo '</div>';
    }
} else {
?>
        <div class="alert alert-info">
            <strong>Este instalador creara las siguientes tablas:</strong>
            <ul>
                <li>cat_tipos_cliente</li>
                <li>cat_categorias_cliente</li>
                <li>cat_grupos_cliente</li>
                <li>cat_condiciones_pago</li>
                <li>cat_listas_precio</li>
            </ul>
            <p>Las tablas se crearan con datos iniciales para el funcionamiento del sistema Quick Add.</p>
        </div>

        <form method="POST">
            <button type="submit" name="install" class="btn">🚀 Instalar Tablas</button>
        </form>

        <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
            <h3 style="margin-top: 0;">Configuracion de Conexion:</h3>
            <table style="width: 100%;">
                <tr><td><strong>Host:</strong></td><td><?php echo htmlspecialchars($host); ?></td></tr>
                <tr><td><strong>Base de Datos:</strong></td><td><?php echo htmlspecialchars($dbname); ?></td></tr>
                <tr><td><strong>Usuario:</strong></td><td><?php echo htmlspecialchars($username); ?></td></tr>
            </table>
        </div>
<?php
}
?>
    </div>
</body>
</html>
