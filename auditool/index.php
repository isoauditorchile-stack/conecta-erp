<?php
session_start();

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionbd');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionuser');

// Conexion a base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Variables de sesion y configuracion
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'es';
$country = isset($_SESSION['country']) ? $_SESSION['country'] : 'CL';

// Obtener informacion del usuario
$stmt = $conn->prepare("SELECT username, email, full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Obtener informacion de la empresa
$stmt = $conn->prepare("SELECT company_name, rut, industry, country FROM companies WHERE id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company_data = $result->fetch_assoc();
$stmt->close();

// Obtener estadisticas globales de ISOs
$stmt = $conn->prepare("SELECT iso_code, COUNT(*) as total FROM iso_implementations WHERE company_id = ? GROUP BY iso_code");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$iso_stats = [];
while ($row = $result->fetch_assoc()) {
    $iso_stats[$row['iso_code']] = $row['total'];
}
$stmt->close();

// Obtener auditorias pendientes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM audits WHERE company_id = ? AND status = 'pendiente'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$audits_pending = $result->fetch_assoc()['total'];
$stmt->close();

// Obtener no conformidades abiertas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM non_conformities WHERE company_id = ? AND status IN ('abierta', 'en_proceso')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$nc_open = $result->fetch_assoc()['total'];
$stmt->close();

// Obtener acciones correctivas pendientes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM corrective_actions WHERE company_id = ? AND status IN ('pendiente', 'en_proceso')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$actions_pending = $result->fetch_assoc()['total'];
$stmt->close();

// Obtener documentos por aprobar
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM documents WHERE company_id = ? AND approval_status = 'pendiente'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$docs_pending = $result->fetch_assoc()['total'];
$stmt->close();

// Catalogo completo de ISOs disponibles
$iso_catalog = [
    'ISO_9001' => [
        'code' => 'ISO 9001',
        'name_es' => 'Gestion de Calidad',
        'name_en' => 'Quality Management',
        'name_pt' => 'Gestao da Qualidade',
        'description_es' => 'Sistema de Gestion de Calidad para mejorar la satisfaccion del cliente',
        'color' => '#0066cc',
        'icon' => '&#9733;',
        'module' => 'iso_9001'
    ],
    'ISO_14001' => [
        'code' => 'ISO 14001',
        'name_es' => 'Gestion Ambiental',
        'name_en' => 'Environmental Management',
        'name_pt' => 'Gestao Ambiental',
        'description_es' => 'Sistema de Gestion Ambiental para reducir impacto ecologico',
        'color' => '#00994d',
        'icon' => '&#127793;',
        'module' => 'iso_14001'
    ],
    'ISO_27001' => [
        'code' => 'ISO 27001',
        'name_es' => 'Seguridad de la Informacion',
        'name_en' => 'Information Security',
        'name_pt' => 'Seguranca da Informacao',
        'description_es' => 'Sistema de Gestion de Seguridad de la Informacion',
        'color' => '#cc0000',
        'icon' => '&#128274;',
        'module' => 'iso_27001'
    ],
    'ISO_22301' => [
        'code' => 'ISO 22301',
        'name_es' => 'Continuidad del Negocio',
        'name_en' => 'Business Continuity',
        'name_pt' => 'Continuidade de Negocios',
        'description_es' => 'Sistema de Gestion de Continuidad del Negocio',
        'color' => '#ff6600',
        'icon' => '&#128295;',
        'module' => 'iso_22301'
    ],
    'ISO_37001' => [
        'code' => 'ISO 37001',
        'name_es' => 'Gestion Antisoborno',
        'name_en' => 'Anti-Bribery Management',
        'name_pt' => 'Gestao Antissuborno',
        'description_es' => 'Sistema de Gestion Antisoborno para prevenir corrupcion',
        'color' => '#8b4513',
        'icon' => '&#9878;',
        'module' => 'iso_37001'
    ],
    'ISO_45001' => [
        'code' => 'ISO 45001',
        'name_es' => 'Seguridad y Salud en el Trabajo',
        'name_en' => 'Occupational Health and Safety',
        'name_pt' => 'Seguranca e Saude no Trabalho',
        'description_es' => 'Sistema de Gestion de Seguridad y Salud en el Trabajo',
        'color' => '#ff9900',
        'icon' => '&#9762;',
        'module' => 'iso_45001'
    ],
    'ISO_31000' => [
        'code' => 'ISO 31000',
        'name_es' => 'Gestion de Riesgos',
        'name_en' => 'Risk Management',
        'name_pt' => 'Gestao de Riscos',
        'description_es' => 'Directrices para la Gestion de Riesgos',
        'color' => '#9900cc',
        'icon' => '&#9888;',
        'module' => 'iso_31000'
    ],
    'ISO_50001' => [
        'code' => 'ISO 50001',
        'name_es' => 'Gestion de Energia',
        'name_en' => 'Energy Management',
        'name_pt' => 'Gestao de Energia',
        'description_es' => 'Sistema de Gestion de la Energia',
        'color' => '#ffcc00',
        'icon' => '&#9889;',
        'module' => 'iso_50001'
    ],
    'ISO_20000' => [
        'code' => 'ISO 20000',
        'name_es' => 'Gestion de Servicios TI',
        'name_en' => 'IT Service Management',
        'name_pt' => 'Gestao de Servicos de TI',
        'description_es' => 'Sistema de Gestion de Servicios de Tecnologia de la Informacion',
        'color' => '#0099cc',
        'icon' => '&#128187;',
        'module' => 'iso_20000'
    ],
    'ISO_22000' => [
        'code' => 'ISO 22000',
        'name_es' => 'Seguridad Alimentaria',
        'name_en' => 'Food Safety',
        'name_pt' => 'Seguranca Alimentar',
        'description_es' => 'Sistema de Gestion de Seguridad Alimentaria',
        'color' => '#66cc00',
        'icon' => '&#127828;',
        'module' => 'iso_22000'
    ],
    'ISO_27017' => [
        'code' => 'ISO 27017',
        'name_es' => 'Seguridad en la Nube',
        'name_en' => 'Cloud Security',
        'name_pt' => 'Seguranca na Nuvem',
        'description_es' => 'Controles de Seguridad para Servicios en la Nube',
        'color' => '#3399ff',
        'icon' => '&#9729;',
        'module' => 'iso_27017'
    ],
    'ISO_27701' => [
        'code' => 'ISO 27701',
        'name_es' => 'Gestion de Privacidad',
        'name_en' => 'Privacy Management',
        'name_pt' => 'Gestao de Privacidade',
        'description_es' => 'Sistema de Gestion de Privacidad de la Informacion',
        'color' => '#cc00cc',
        'icon' => '&#128065;',
        'module' => 'iso_27701'
    ],
    'ISO_13485' => [
        'code' => 'ISO 13485',
        'name_es' => 'Dispositivos Medicos',
        'name_en' => 'Medical Devices',
        'name_pt' => 'Dispositivos Medicos',
        'description_es' => 'Sistema de Gestion de Calidad para Dispositivos Medicos',
        'color' => '#cc3333',
        'icon' => '&#9877;',
        'module' => 'iso_13485'
    ],
    'ISO_28000' => [
        'code' => 'ISO 28000',
        'name_es' => 'Seguridad en Cadena de Suministro',
        'name_en' => 'Supply Chain Security',
        'name_pt' => 'Seguranca na Cadeia de Suprimentos',
        'description_es' => 'Sistema de Gestion de Seguridad en la Cadena de Suministro',
        'color' => '#996633',
        'icon' => '&#128666;',
        'module' => 'iso_28000'
    ]
];

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'AUDITOR PRO',
        'subtitle' => 'Sistema Integral de Gestion Multi-ISO',
        'welcome' => 'Bienvenido',
        'dashboard' => 'Panel de Control',
        'statistics' => 'Estadisticas Generales',
        'select_iso' => 'Seleccionar Sistema ISO',
        'audits' => 'Auditorias Pendientes',
        'non_conformities' => 'No Conformidades Abiertas',
        'actions' => 'Acciones Correctivas',
        'documents' => 'Documentos por Aprobar',
        'modules' => 'Modulos Transversales',
        'audit_system' => 'Sistema de Auditorias',
        'nc_system' => 'No Conformidades',
        'action_system' => 'Acciones Correctivas',
        'kpi_system' => 'Indicadores KPI',
        'doc_system' => 'Gestion Documental',
        'reports' => 'Reportes y Exportacion',
        'company' => 'Empresa',
        'user' => 'Usuario',
        'logout' => 'Cerrar Sesion',
        'settings' => 'Configuracion',
        'access' => 'Acceder'
    ],
    'en' => [
        'title' => 'AUDITOR PRO',
        'subtitle' => 'Comprehensive Multi-ISO Management System',
        'welcome' => 'Welcome',
        'dashboard' => 'Dashboard',
        'statistics' => 'General Statistics',
        'select_iso' => 'Select ISO System',
        'audits' => 'Pending Audits',
        'non_conformities' => 'Open Non-Conformities',
        'actions' => 'Corrective Actions',
        'documents' => 'Documents to Approve',
        'modules' => 'Cross-cutting Modules',
        'audit_system' => 'Audit System',
        'nc_system' => 'Non-Conformities',
        'action_system' => 'Corrective Actions',
        'kpi_system' => 'KPI Indicators',
        'doc_system' => 'Document Management',
        'reports' => 'Reports and Export',
        'company' => 'Company',
        'user' => 'User',
        'logout' => 'Logout',
        'settings' => 'Settings',
        'access' => 'Access'
    ],
    'pt' => [
        'title' => 'AUDITOR PRO',
        'subtitle' => 'Sistema Integral de Gestao Multi-ISO',
        'welcome' => 'Bem-vindo',
        'dashboard' => 'Painel de Controle',
        'statistics' => 'Estatisticas Gerais',
        'select_iso' => 'Selecionar Sistema ISO',
        'audits' => 'Auditorias Pendentes',
        'non_conformities' => 'Nao Conformidades Abertas',
        'actions' => 'Acoes Corretivas',
        'documents' => 'Documentos para Aprovar',
        'modules' => 'Modulos Transversais',
        'audit_system' => 'Sistema de Auditorias',
        'nc_system' => 'Nao Conformidades',
        'action_system' => 'Acoes Corretivas',
        'kpi_system' => 'Indicadores KPI',
        'doc_system' => 'Gestao Documental',
        'reports' => 'Relatorios e Exportacao',
        'company' => 'Empresa',
        'user' => 'Usuario',
        'logout' => 'Sair',
        'settings' => 'Configuracao',
        'access' => 'Acessar'
    ]
];

$t = $texts[$language];

?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - <?php echo $t['subtitle']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            flex: 1;
        }

        .header-title {
            font-size: 42px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .header-subtitle {
            color: #666;
            font-size: 16px;
        }

        .header-right {
            text-align: right;
        }

        .user-info {
            background: #f8f9fa;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .user-info strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        .user-info span {
            color: #666;
            font-size: 14px;
        }

        .language-selector {
            margin-top: 10px;
        }

        .language-selector select {
            padding: 8px 15px;
            border: 2px solid #667eea;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .stat-card.blue { border-color: #0066cc; }
        .stat-card.red { border-color: #cc0000; }
        .stat-card.orange { border-color: #ff6600; }
        .stat-card.green { border-color: #00994d; }

        .stat-value {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .section-title {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .iso-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .iso-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .iso-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--iso-color);
        }

        .iso-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
        }

        .iso-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .iso-code {
            font-size: 24px;
            font-weight: bold;
            color: var(--iso-color);
            margin-bottom: 8px;
        }

        .iso-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .iso-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .iso-button {
            display: inline-block;
            padding: 12px 30px;
            background: var(--iso-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }

        .iso-button:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .module-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .module-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .module-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .module-button {
            display: inline-block;
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .module-button:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        .footer {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: #666;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        @media print {
            body {
                background: white;
            }
            .iso-button, .module-button {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .header-right {
                margin-top: 20px;
                text-align: center;
            }

            .iso-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="header">
            <div class="header-left">
                <h1 class="header-title"><?php echo $t['title']; ?></h1>
                <p class="header-subtitle"><?php echo $t['subtitle']; ?></p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($user_data['full_name'] ?? $user_data['username']); ?></strong>
                    <span><?php echo htmlspecialchars($company_data['company_name']); ?></span>
                </div>
                <div class="language-selector">
                    <select onchange="window.location.href='?lang='+this.value">
                        <option value="es" <?php echo $language == 'es' ? 'selected' : ''; ?>>Espanol</option>
                        <option value="en" <?php echo $language == 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="pt" <?php echo $language == 'pt' ? 'selected' : ''; ?>>Portugues</option>
                    </select>
                </div>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-value"><?php echo $audits_pending; ?></div>
                <div class="stat-label"><?php echo $t['audits']; ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?php echo $nc_open; ?></div>
                <div class="stat-label"><?php echo $t['non_conformities']; ?></div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?php echo $actions_pending; ?></div>
                <div class="stat-label"><?php echo $t['actions']; ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?php echo $docs_pending; ?></div>
                <div class="stat-label"><?php echo $t['documents']; ?></div>
            </div>
        </div>

        <div class="section-title"><?php echo $t['select_iso']; ?></div>

        <div class="iso-grid">
            <?php foreach ($iso_catalog as $iso_key => $iso): ?>
                <div class="iso-card" style="--iso-color: <?php echo $iso['color']; ?>">
                    <div class="iso-icon"><?php echo $iso['icon']; ?></div>
                    <div class="iso-code"><?php echo $iso['code']; ?></div>
                    <div class="iso-name"><?php echo $iso['name_' . $language]; ?></div>
                    <div class="iso-description"><?php echo $iso['description_' . $language]; ?></div>
                    <a href="<?php echo $iso['module']; ?>/index.php" class="iso-button">
                        <?php echo $t['access']; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section-title"><?php echo $t['modules']; ?></div>

        <div class="modules-grid">
            <div class="module-card">
                <div class="module-icon">&#128197;</div>
                <div class="module-title"><?php echo $t['audit_system']; ?></div>
                <a href="common/audits.php" class="module-button"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#9940;</div>
                <div class="module-title"><?php echo $t['nc_system']; ?></div>
                <a href="common/non_conformities.php" class="module-button"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#9989;</div>
                <div class="module-title"><?php echo $t['action_system']; ?></div>
                <a href="common/corrective_actions.php" class="module-button"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128202;</div>
                <div class="module-title"><?php echo $t['kpi_system']; ?></div>
                <a href="common/kpi.php" class="module-button"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128196;</div>
                <div class="module-title"><?php echo $t['doc_system']; ?></div>
                <a href="common/documents.php" class="module-button"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128438;</div>
                <div class="module-title"><?php echo $t['reports']; ?></div>
                <a href="reports/index.php" class="module-button"><?php echo $t['access']; ?></a>
            </div>
        </div>

        <footer class="footer">
            <p>&copy; 2024 AUDITOR PRO - Sistema Integral de Gestion Multi-ISO</p>
            <p>Powered by ConectaERP | Version 1.0.0</p>
        </footer>
    </div>
</body>
</html>
<?php
$conn->close();
?>
