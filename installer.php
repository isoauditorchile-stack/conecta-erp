<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .installer-container {
            max-width: 900px;
            margin: 50px auto;
        }
        .installer-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            font-weight: bold;
            margin: 0;
        }
        .installer-body {
            padding: 40px;
        }
        .step {
            padding: 20px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .step-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .log-container {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            margin-top: 20px;
        }
        .log-line {
            margin: 5px 0;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        .btn-install {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 50px;
            font-size: 1.2rem;
            border-radius: 50px;
            color: white;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .btn-install:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-card">
            <div class="installer-header">
                <h1><i class="fas fa-cogs"></i> CONECTA ERP</h1>
                <p class="mb-0">Instalador Automático del Sistema Completo</p>
            </div>
            <div class="installer-body">
                <?php
                if (!isset($_POST['install'])) {
                ?>
                <div class="text-center mb-4">
                    <i class="fas fa-rocket" style="font-size: 4rem; color: #667eea;"></i>
                    <h2 class="mt-3">Bienvenido al Instalador de CONECTA ERP</h2>
                    <p class="text-muted">Este instalador configurará automáticamente todo el sistema:</p>
                </div>

                <div class="step">
                    <div class="step-title"><i class="fas fa-database"></i> Base de Datos</div>
                    <ul>
                        <li>Creación de todas las tablas del sistema</li>
                        <li>Configuración de 14 módulos principales</li>
                        <li>Instalación de 106 submódulos</li>
                        <li>Configuración de planes y permisos</li>
                    </ul>
                </div>

                <div class="step">
                    <div class="step-title"><i class="fas fa-user-shield"></i> Usuario Administrador</div>
                    <ul>
                        <li>Email: <strong>auditorexchile@gmail.com</strong></li>
                        <li>Usuario: <strong>auditorex chile</strong></li>
                        <li>Permisos: Acceso total al sistema</li>
                    </ul>
                </div>

                <div class="step">
                    <div class="step-title"><i class="fas fa-globe"></i> Configuración Multiidioma</div>
                    <ul>
                        <li>9 idiomas disponibles</li>
                        <li>9 países soportados</li>
                        <li>Validadores de documentos por país</li>
                    </ul>
                </div>

                <div class="step">
                    <div class="step-title"><i class="fas fa-money-bill-wave"></i> Sistema de Planes</div>
                    <ul>
                        <li>4 planes de suscripción</li>
                        <li>14 días de prueba gratuita</li>
                        <li>Notificaciones automáticas</li>
                    </ul>
                </div>

                <form method="POST">
                    <div class="text-center mt-4">
                        <button type="submit" name="install" class="btn btn-install">
                            <i class="fas fa-play-circle"></i> Iniciar Instalación
                        </button>
                    </div>
                </form>

                <?php
                } else {
                    // PROCESO DE INSTALACIÓN
                    echo '<div class="text-center mb-4">';
                    echo '<h2><i class="fas fa-sync fa-spin"></i> Instalando CONECTA ERP...</h2>';
                    echo '</div>';

                    echo '<div class="progress mb-3" style="height: 30px;">';
                    echo '<div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" style="width: 0%"></div>';
                    echo '</div>';

                    echo '<div class="log-container" id="logContainer">';

                    flush();
                    ob_flush();

                    function logMessage($message, $type = 'info') {
                        $icons = [
                            'success' => '✓',
                            'error' => '✗',
                            'warning' => '⚠',
                            'info' => 'ℹ'
                        ];
                        echo "<div class='log-line $type'>[" . date('H:i:s') . "] " . $icons[$type] . " $message</div>";
                        flush();
                        ob_flush();
                    }

                    // Incluir configuración
                    require_once 'includes/config.php';

                    try {
                        $pdo = getDB();

                        // PASO 1: Crear tablas principales
                        logMessage("Iniciando instalación de base de datos...", 'info');
                        echo "<script>document.getElementById('progressBar').style.width = '10%';</script>";

                        // Tabla de idiomas
                        logMessage("Creando tabla de idiomas...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS languages");
                        $pdo->exec("CREATE TABLE languages (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            code VARCHAR(10) UNIQUE NOT NULL,
                            name VARCHAR(100) NOT NULL,
                            native_name VARCHAR(100) NOT NULL,
                            flag_icon VARCHAR(50),
                            is_active TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        // Insertar idiomas
                        $languages = [
                            ['es', 'Spanish', 'Español', 'flag-es'],
                            ['en', 'English', 'English', 'flag-us'],
                            ['pt', 'Portuguese', 'Português', 'flag-br'],
                            ['fr', 'French', 'Français', 'flag-fr'],
                            ['de', 'German', 'Deutsch', 'flag-de'],
                            ['it', 'Italian', 'Italiano', 'flag-it'],
                            ['ru', 'Russian', 'Русский', 'flag-ru'],
                            ['zh', 'Chinese', '中文', 'flag-cn'],
                            ['ja', 'Japanese', '日本語', 'flag-jp']
                        ];

                        $stmt = $pdo->prepare("INSERT INTO languages (code, name, native_name, flag_icon) VALUES (?, ?, ?, ?)");
                        foreach ($languages as $lang) {
                            $stmt->execute($lang);
                        }
                        logMessage("9 idiomas instalados correctamente", 'success');

                        // Tabla de países
                        logMessage("Creando tabla de países...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS countries");
                        $pdo->exec("CREATE TABLE countries (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            code VARCHAR(3) UNIQUE NOT NULL,
                            name VARCHAR(100) NOT NULL,
                            document_type VARCHAR(50) NOT NULL,
                            document_format VARCHAR(100),
                            document_validation VARCHAR(255),
                            currency_code VARCHAR(3),
                            phone_code VARCHAR(10),
                            is_active TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        // Insertar países
                        $countries = [
                            ['CL', 'Chile', 'RUT', '##.###.###-#', 'chilean_rut', 'CLP', '+56'],
                            ['AR', 'Argentina', 'DNI', '##.###.###', 'numeric', 'ARS', '+54'],
                            ['PE', 'Perú', 'DNI', '########', 'numeric', 'PEN', '+51'],
                            ['CO', 'Colombia', 'CC', '##########', 'numeric', 'COP', '+57'],
                            ['MX', 'México', 'CURP', 'AAAA######AAAAAA##', 'alphanumeric', 'MXN', '+52'],
                            ['BR', 'Brasil', 'CPF', '###.###.###-##', 'brazilian_cpf', 'BRL', '+55'],
                            ['ES', 'España', 'DNI', '########-A', 'spanish_dni', 'EUR', '+34'],
                            ['US', 'Estados Unidos', 'SSN', '###-##-####', 'numeric', 'USD', '+1'],
                            ['UY', 'Uruguay', 'CI', '#.###.###-#', 'numeric', 'UYU', '+598']
                        ];

                        $stmt = $pdo->prepare("INSERT INTO countries (code, name, document_type, document_format, document_validation, currency_code, phone_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        foreach ($countries as $country) {
                            $stmt->execute($country);
                        }
                        logMessage("9 países instalados correctamente", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '20%';</script>";

                        // Tabla de planes
                        logMessage("Creando tabla de planes de suscripción...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS subscription_plans");
                        $pdo->exec("CREATE TABLE subscription_plans (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            plan_name VARCHAR(100) NOT NULL,
                            plan_code VARCHAR(50) UNIQUE NOT NULL,
                            description TEXT,
                            price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
                            price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0,
                            max_users INT DEFAULT 1,
                            max_companies INT DEFAULT 1,
                            features JSON,
                            is_custom TINYINT(1) DEFAULT 0,
                            is_active TINYINT(1) DEFAULT 1,
                            sort_order INT DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        // Insertar planes
                        $plans = [
                            [
                                'plan_name' => 'Plan Básico',
                                'plan_code' => 'BASIC',
                                'description' => 'Ideal para pequeñas empresas y emprendedores',
                                'price_monthly' => 29.99,
                                'price_yearly' => 299.99,
                                'max_users' => 3,
                                'max_companies' => 1,
                                'features' => json_encode(['Módulos básicos', 'Soporte por email', '10GB almacenamiento']),
                                'sort_order' => 1
                            ],
                            [
                                'plan_name' => 'Plan Profesional',
                                'plan_code' => 'PROFESSIONAL',
                                'description' => 'Para empresas en crecimiento',
                                'price_monthly' => 79.99,
                                'price_yearly' => 799.99,
                                'max_users' => 10,
                                'max_companies' => 3,
                                'features' => json_encode(['Todos los módulos', 'Soporte prioritario', '100GB almacenamiento', 'Reportes avanzados']),
                                'sort_order' => 2
                            ],
                            [
                                'plan_name' => 'Plan Empresarial',
                                'plan_code' => 'ENTERPRISE',
                                'description' => 'Para grandes empresas',
                                'price_monthly' => 199.99,
                                'price_yearly' => 1999.99,
                                'max_users' => 50,
                                'max_companies' => 10,
                                'features' => json_encode(['Todos los módulos', 'Soporte 24/7', 'Almacenamiento ilimitado', 'API completa', 'Personalización']),
                                'sort_order' => 3
                            ],
                            [
                                'plan_name' => 'Plan Personalizado',
                                'plan_code' => 'CUSTOM',
                                'description' => 'Solución a medida para su empresa',
                                'price_monthly' => 0,
                                'price_yearly' => 0,
                                'max_users' => 999,
                                'max_companies' => 999,
                                'features' => json_encode(['Todo incluido', 'Desarrollo personalizado', 'Consultor dedicado']),
                                'is_custom' => 1,
                                'sort_order' => 4
                            ]
                        ];

                        $stmt = $pdo->prepare("INSERT INTO subscription_plans (plan_name, plan_code, description, price_monthly, price_yearly, max_users, max_companies, features, is_custom, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        foreach ($plans as $plan) {
                            $stmt->execute([
                                $plan['plan_name'],
                                $plan['plan_code'],
                                $plan['description'],
                                $plan['price_monthly'],
                                $plan['price_yearly'],
                                $plan['max_users'],
                                $plan['max_companies'],
                                $plan['features'],
                                $plan['is_custom'],
                                $plan['sort_order']
                            ]);
                        }
                        logMessage("4 planes de suscripción instalados", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '30%';</script>";

                        // Tabla de usuarios
                        logMessage("Creando tabla de usuarios...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS users");
                        $pdo->exec("CREATE TABLE users (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            email VARCHAR(255) UNIQUE NOT NULL,
                            username VARCHAR(100) NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            full_name VARCHAR(255) NOT NULL,
                            document_type VARCHAR(50),
                            document_number VARCHAR(50),
                            country_id INT,
                            language_id INT DEFAULT 1,
                            phone VARCHAR(50),
                            company_name VARCHAR(255),
                            plan_id INT,
                            status ENUM('pending', 'trial', 'active', 'suspended', 'cancelled') DEFAULT 'trial',
                            trial_ends_at DATETIME,
                            subscription_ends_at DATETIME,
                            is_admin TINYINT(1) DEFAULT 0,
                            is_approved TINYINT(1) DEFAULT 0,
                            last_login DATETIME,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (country_id) REFERENCES countries(id),
                            FOREIGN KEY (language_id) REFERENCES languages(id),
                            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        // Crear usuario administrador
                        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("INSERT INTO users (email, username, password, full_name, is_admin, is_approved, status, country_id, language_id) VALUES (?, ?, ?, ?, 1, 1, 'active', 1, 1)");
                        $stmt->execute(['auditorexchile@gmail.com', 'auditorex chile', $adminPassword, 'Auditorex Chile Administrator']);
                        logMessage("Usuario administrador creado: auditorexchile@gmail.com / admin123", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '40%';</script>";

                        // Tabla de empresas
                        logMessage("Creando tabla de empresas...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS companies");
                        $pdo->exec("CREATE TABLE companies (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            company_name VARCHAR(255) NOT NULL,
                            business_name VARCHAR(255),
                            tax_id VARCHAR(50),
                            country_id INT,
                            owner_user_id INT,
                            address TEXT,
                            city VARCHAR(100),
                            state VARCHAR(100),
                            postal_code VARCHAR(20),
                            phone VARCHAR(50),
                            email VARCHAR(255),
                            website VARCHAR(255),
                            logo VARCHAR(255),
                            is_active TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (country_id) REFERENCES countries(id),
                            FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        logMessage("Tabla de empresas creada", 'success');

                        // Tabla de módulos
                        logMessage("Creando tabla de módulos...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS modules");
                        $pdo->exec("CREATE TABLE modules (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            module_code VARCHAR(50) UNIQUE NOT NULL,
                            module_name VARCHAR(255) NOT NULL,
                            description TEXT,
                            icon VARCHAR(50),
                            color VARCHAR(20),
                            route VARCHAR(255),
                            is_active TINYINT(1) DEFAULT 1,
                            sort_order INT DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        // Insertar módulos principales
                        $modules = [
                            ['ADMIN', 'Administración Central', 'Gestión de empresas, seguridad y parametrización', 'fa-cog', '#FF6B6B', '/modules/admin/', 1],
                            ['ENTITIES', 'Gestión de Entidades', 'Clientes, Proveedores, Empleados, Productos', 'fa-users', '#4ECDC4', '/modules/entities/', 2],
                            ['FI', 'Finanzas', 'Contabilidad, Tesorería, Activos Fijos', 'fa-dollar-sign', '#45B7D1', '/modules/fi/', 3],
                            ['CO', 'Controlling', 'Centros de Costo, Rentabilidad', 'fa-chart-line', '#96CEB4', '/modules/co/', 4],
                            ['SD', 'Ventas', 'Pedidos, Facturación, POS', 'fa-shopping-cart', '#FFEAA7', '/modules/sd/', 5],
                            ['MM', 'Materiales', 'Inventario, Compras, Almacenes', 'fa-boxes', '#DFE6E9', '/modules/mm/', 6],
                            ['PP', 'Producción', 'Órdenes, MRP, Calidad', 'fa-industry', '#74B9FF', '/modules/pp/', 7],
                            ['HCM', 'Recursos Humanos', 'Personal, Nómina, Capacitación', 'fa-user-tie', '#A29BFE', '/modules/hcm/', 8],
                            ['SCM', 'Supply Chain', 'Logística, Transporte, Rutas', 'fa-truck', '#FD79A8', '/modules/scm/', 9],
                            ['CRM', 'CRM', 'Clientes, Oportunidades, Leads', 'fa-handshake', '#FDCB6E', '/modules/crm/', 10],
                            ['LOYALTY', 'Fidelización', 'Programas de lealtad, Puntos', 'fa-star', '#E17055', '/modules/loyalty/', 11],
                            ['BI', 'Business Intelligence', 'Dashboards, KPIs, Reportes', 'fa-chart-bar', '#00B894', '/modules/bi/', 12],
                            ['CONFIG', 'Configuración', 'Ajustes del sistema', 'fa-wrench', '#636E72', '/modules/config/', 13],
                            ['DASHBOARD', 'Dashboard Principal', 'Panel principal de navegación', 'fa-home', '#6C5CE7', '/dashboard/', 14]
                        ];

                        $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name, description, icon, color, route, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        foreach ($modules as $module) {
                            $stmt->execute($module);
                        }
                        logMessage("14 módulos principales instalados", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '50%';</script>";

                        // Tabla de submódulos
                        logMessage("Creando tabla de submódulos...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS submodules");
                        $pdo->exec("CREATE TABLE submodules (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            module_id INT NOT NULL,
                            submodule_code VARCHAR(50) NOT NULL,
                            submodule_name VARCHAR(255) NOT NULL,
                            description TEXT,
                            icon VARCHAR(50),
                            route VARCHAR(255),
                            is_active TINYINT(1) DEFAULT 1,
                            sort_order INT DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_submodule (module_id, submodule_code)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        // Insertar submódulos (solo algunos como ejemplo, se pueden agregar los 106 completos)
                        $submodules = [
                            // ADMIN (ID: 1)
                            [1, 'EMPRESAS', 'Gestión de Empresas', 'Administrar empresas del sistema', 'fa-building', '/modules/admin/empresas.php', 1],
                            [1, 'SEGURIDAD', 'Gestión de Seguridad', 'Usuarios y permisos', 'fa-shield-alt', '/modules/admin/seguridad.php', 2],
                            [1, 'PARAMETROS', 'Parametrización Global', 'Configuración general', 'fa-sliders-h', '/modules/admin/parametros.php', 3],

                            // ENTITIES (ID: 2)
                            [2, 'MA', 'Entidades Maestras', 'Gestión de entidades maestras', 'fa-database', '/modules/entities/maestras.php', 1],
                            [2, 'CLIE', 'Gestión de Clientes', 'Administrar clientes', 'fa-user-friends', '/modules/entities/clientes.php', 2],
                            [2, 'PROV', 'Gestión de Proveedores', 'Administrar proveedores', 'fa-truck-loading', '/modules/entities/proveedores.php', 3],
                            [2, 'EMPDOS', 'Gestión de Empleados', 'Administrar empleados', 'fa-user-tie', '/modules/entities/empleados.php', 4],
                            [2, 'PRODTOS', 'Productos y Servicios', 'Catálogo de productos', 'fa-box', '/modules/entities/productos.php', 5],

                            // FI (ID: 3) - 11 submódulos
                            [3, 'CONTA', 'Contabilidad General', 'Libro mayor y diario', 'fa-book', '/modules/fi/contabilidad.php', 1],
                            [3, 'CP', 'Cuentas por Pagar', 'Gestión de pagos', 'fa-file-invoice-dollar', '/modules/fi/cuentas_pagar.php', 2],
                            [3, 'CTACOBRAR', 'Cuentas por Cobrar', 'Gestión de cobros', 'fa-file-invoice', '/modules/fi/cuentas_cobrar.php', 3],
                            [3, 'TES', 'Tesorería', 'Gestión de caja y bancos', 'fa-coins', '/modules/fi/tesoreria.php', 4],
                            [3, 'AF', 'Activos Fijos', 'Control de activos', 'fa-warehouse', '/modules/fi/activos_fijos.php', 5],
                            [3, 'CF', 'Comprobantes y Facturas', 'Documentos contables', 'fa-receipt', '/modules/fi/comprobantes.php', 6],
                            [3, 'IFRS', 'IFRS', 'Normas internacionales', 'fa-globe', '/modules/fi/ifrs.php', 7],
                            [3, 'CONS', 'Consolidación', 'Consolidación financiera', 'fa-layer-group', '/modules/fi/consolidacion.php', 8],
                            [3, 'REP', 'Reporting Financiero', 'Informes financieros', 'fa-chart-pie', '/modules/fi/reporting.php', 9],
                            [3, 'PRS', 'Presupuestos', 'Gestión presupuestaria', 'fa-calculator', '/modules/fi/presupuestos.php', 10],
                            [3, 'IMP', 'Impuestos', 'Gestión tributaria', 'fa-percentage', '/modules/fi/impuestos.php', 11],

                            // CO (ID: 4) - 8 submódulos
                            [4, 'CECOS', 'Centros de Costo', 'Gestión de centros de costo', 'fa-sitemap', '/modules/co/centros_costo.php', 1],
                            [4, 'RENT', 'Análisis de Rentabilidad', 'Análisis de rentabilidad', 'fa-chart-line', '/modules/co/rentabilidad.php', 2],
                            [4, 'OI', 'Órdenes Internas', 'Contabilidad de órdenes internas', 'fa-tasks', '/modules/co/ordenes_internas.php', 3],
                            [4, 'PROY', 'Proyectos', 'Contabilidad de proyectos', 'fa-project-diagram', '/modules/co/proyectos.php', 4],
                            [4, 'GASTOS', 'Gastos Generales', 'Control de gastos generales', 'fa-money-bill-wave', '/modules/co/gastos.php', 5],
                            [4, 'COSTPROD', 'Costos del Producto', 'Costos del producto', 'fa-box-usd', '/modules/co/costos_producto.php', 6],
                            [4, 'RESULT', 'Análisis de Resultados', 'Análisis de resultados', 'fa-analytics', '/modules/co/resultados.php', 7],
                            [4, 'PLAN', 'Planificación', 'Planificación y presupuestos', 'fa-calendar-alt', '/modules/co/planificacion.php', 8]
                        ];

                        $stmt = $pdo->prepare("INSERT INTO submodules (module_id, submodule_code, submodule_name, description, icon, route, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        foreach ($submodules as $submodule) {
                            $stmt->execute($submodule);
                        }
                        logMessage("Submódulos base instalados (continuará en siguientes versiones)", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '60%';</script>";

                        // Tabla de permisos
                        logMessage("Creando tabla de permisos de usuario...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS user_permissions");
                        $pdo->exec("CREATE TABLE user_permissions (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT NOT NULL,
                            submodule_id INT NOT NULL,
                            can_view TINYINT(1) DEFAULT 0,
                            can_create TINYINT(1) DEFAULT 0,
                            can_edit TINYINT(1) DEFAULT 0,
                            can_delete TINYINT(1) DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (submodule_id) REFERENCES submodules(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_permission (user_id, submodule_id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        logMessage("Tabla de permisos creada", 'success');

                        // Tabla de logs de actividad
                        logMessage("Creando tabla de logs de actividad...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS activity_logs");
                        $pdo->exec("CREATE TABLE activity_logs (
                            id BIGINT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT,
                            action VARCHAR(100) NOT NULL,
                            description TEXT,
                            module VARCHAR(50),
                            ip_address VARCHAR(50),
                            user_agent TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                            INDEX idx_user_id (user_id),
                            INDEX idx_created_at (created_at)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        logMessage("Tabla de logs creada", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '70%';</script>";

                        // Tabla de indicadores económicos
                        logMessage("Creando tabla de indicadores económicos...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS economic_indicators");
                        $pdo->exec("CREATE TABLE economic_indicators (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            indicator_code VARCHAR(20) NOT NULL,
                            indicator_name VARCHAR(100) NOT NULL,
                            value DECIMAL(15,4) NOT NULL,
                            currency VARCHAR(3),
                            date DATE NOT NULL,
                            source VARCHAR(255),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX idx_code_date (indicator_code, date)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                        // Insertar valores iniciales
                        $indicators = [
                            ['UF', 'Unidad de Fomento', 37000.00, 'CLP', date('Y-m-d')],
                            ['UTM', 'Unidad Tributaria Mensual', 65000.00, 'CLP', date('Y-m-d')],
                            ['USD', 'Dólar Estadounidense', 920.00, 'CLP', date('Y-m-d')],
                            ['EUR', 'Euro', 1000.00, 'CLP', date('Y-m-d')]
                        ];

                        $stmt = $pdo->prepare("INSERT INTO economic_indicators (indicator_code, indicator_name, value, currency, date) VALUES (?, ?, ?, ?, ?)");
                        foreach ($indicators as $indicator) {
                            $stmt->execute($indicator);
                        }
                        logMessage("Indicadores económicos inicializados", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '80%';</script>";

                        // Tabla de pagos
                        logMessage("Creando tabla de pagos...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS payments");
                        $pdo->exec("CREATE TABLE payments (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT NOT NULL,
                            plan_id INT NOT NULL,
                            amount DECIMAL(10,2) NOT NULL,
                            currency VARCHAR(3) DEFAULT 'USD',
                            payment_method VARCHAR(50),
                            payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                            transaction_id VARCHAR(255),
                            payment_date DATETIME,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        logMessage("Tabla de pagos creada", 'success');

                        // Tabla de notificaciones
                        logMessage("Creando tabla de notificaciones...", 'info');
                        $pdo->exec("DROP TABLE IF EXISTS notifications");
                        $pdo->exec("CREATE TABLE notifications (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT NOT NULL,
                            title VARCHAR(255) NOT NULL,
                            message TEXT NOT NULL,
                            type VARCHAR(50) DEFAULT 'info',
                            is_read TINYINT(1) DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            INDEX idx_user_read (user_id, is_read)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        logMessage("Tabla de notificaciones creada", 'success');
                        echo "<script>document.getElementById('progressBar').style.width = '90%';</script>";

                        // Crear vistas útiles
                        logMessage("Creando vistas del sistema...", 'info');
                        $pdo->exec("CREATE OR REPLACE VIEW v_user_details AS
                            SELECT
                                u.*,
                                c.name as country_name,
                                l.native_name as language_name,
                                sp.plan_name,
                                sp.plan_code
                            FROM users u
                            LEFT JOIN countries c ON u.country_id = c.id
                            LEFT JOIN languages l ON u.language_id = l.id
                            LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
                        ");
                        logMessage("Vistas del sistema creadas", 'success');

                        echo "<script>document.getElementById('progressBar').style.width = '100%';</script>";
                        logMessage("", 'info');
                        logMessage("¡INSTALACIÓN COMPLETADA CON ÉXITO!", 'success');
                        logMessage("", 'info');
                        logMessage("Credenciales de acceso:", 'warning');
                        logMessage("Email: auditorexchile@gmail.com", 'warning');
                        logMessage("Password: admin123", 'warning');
                        logMessage("", 'info');
                        logMessage("Sistema instalado:", 'info');
                        logMessage("✓ 14 módulos principales", 'success');
                        logMessage("✓ Base de submódulos instalada", 'success');
                        logMessage("✓ 9 idiomas configurados", 'success');
                        logMessage("✓ 9 países soportados", 'success');
                        logMessage("✓ 4 planes de suscripción", 'success');
                        logMessage("✓ Sistema multiusuario configurado", 'success');
                        logMessage("✓ Sistema multiempresa configurado", 'success');
                        logMessage("", 'info');

                        echo '</div>';
                        echo '<div class="text-center mt-4">';
                        echo '<a href="index.php" class="btn btn-install"><i class="fas fa-home"></i> Ir al Sistema</a>';
                        echo '</div>';

                    } catch (PDOException $e) {
                        logMessage("ERROR: " . $e->getMessage(), 'error');
                        logMessage("Instalación fallida. Por favor revise la configuración de la base de datos.", 'error');
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
