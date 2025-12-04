<?php
session_start();

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'conectae_isogestionuser');
define('DB_PASS', 'pt125824caraud');
define('DB_NAME', 'conectae_isogestionbd');

// Conexion a base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Variables de sesion
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'es';

// Obtener estadisticas del modulo ISO 22301
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_processes WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_processes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_processes WHERE company_id = ? AND criticality IN ('Critico', 'Muy Critico')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$critical_processes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_bcp_plans WHERE company_id = ? AND status = 'activo'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$active_plans = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_tests WHERE company_id = ? AND test_result = 'exitoso'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$successful_tests = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_incidents WHERE company_id = ? AND status IN ('activo', 'en_curso')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$active_incidents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso22301_suppliers WHERE company_id = ? AND criticality = 'Critico'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$critical_suppliers = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'ISO 22301 - Gestion de Continuidad del Negocio',
        'subtitle' => 'Sistema de Gestion de Continuidad del Negocio',
        'dashboard' => 'Panel de Control',
        'statistics' => 'Estadisticas',
        'total_processes' => 'Procesos Criticos',
        'critical_processes' => 'Alta Prioridad',
        'active_plans' => 'Planes Activos',
        'successful_tests' => 'Pruebas Exitosas',
        'active_incidents' => 'Incidentes Activos',
        'critical_suppliers' => 'Proveedores Criticos',
        'modules' => 'Modulos de Gestion',
        'bia' => 'Analisis de Impacto al Negocio (BIA)',
        'risk_assessment' => 'Evaluacion de Riesgos',
        'strategies' => 'Estrategias de Continuidad',
        'bcp_plans' => 'Planes de Continuidad (BCP)',
        'drp_plans' => 'Planes de Recuperacion (DRP)',
        'tests' => 'Pruebas y Ejercicios',
        'incident_mgmt' => 'Gestion de Incidentes',
        'crisis_comm' => 'Comunicacion de Crisis',
        'documents' => 'Documentos y Formatos',
        'reports' => 'Reportes y Exportacion',
        'access' => 'Acceder',
        'back' => 'Volver al Menu Principal',
        'print' => 'Imprimir',
        'export_excel' => 'Exportar a Excel',
        'export_pdf' => 'Exportar a PDF'
    ]
];

$t = $texts[$language];
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - AUDITOR PRO</title>
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-left h1 {
            font-size: 32px;
            color: #ff6600;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #666;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #0066cc;
            color: white;
        }

        .btn-success {
            background: #00994d;
            color: white;
        }

        .btn-danger {
            background: #cc0000;
            color: white;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

        .stat-card.orange { border-color: #ff6600; }
        .stat-card.red { border-color: #cc0000; }
        .stat-card.blue { border-color: #0066cc; }
        .stat-card.green { border-color: #00994d; }
        .stat-card.purple { border-color: #9900cc; }
        .stat-card.yellow { border-color: #ffcc00; }

        .stat-value {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
        }

        .section-title {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            border-top: 4px solid #ff6600;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .module-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .module-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .module-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
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
            .action-buttons, .btn {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                text-align: center;
            }

            .action-buttons {
                margin-top: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    <h1>&#128295; <?php echo $t['title']; ?></h1>
                    <p>AUDITOR PRO - <?php echo $t['dashboard']; ?></p>
                </div>
                <div class="action-buttons">
                    <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                    <a href="export.php?format=excel&type=dashboard" class="btn btn-success">&#128202; <?php echo $t['export_excel']; ?></a>
                    <a href="export.php?format=pdf&type=dashboard" class="btn btn-danger">&#128196; <?php echo $t['export_pdf']; ?></a>
                    <a href="../index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card orange">
                <div class="stat-value"><?php echo $total_processes; ?></div>
                <div class="stat-label"><?php echo $t['total_processes']; ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?php echo $critical_processes; ?></div>
                <div class="stat-label"><?php echo $t['critical_processes']; ?></div>
            </div>
            <div class="stat-card blue">
                <div class="stat-value"><?php echo $active_plans; ?></div>
                <div class="stat-label"><?php echo $t['active_plans']; ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?php echo $successful_tests; ?></div>
                <div class="stat-label"><?php echo $t['successful_tests']; ?></div>
            </div>
            <div class="stat-card purple">
                <div class="stat-value"><?php echo $active_incidents; ?></div>
                <div class="stat-label"><?php echo $t['active_incidents']; ?></div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-value"><?php echo $critical_suppliers; ?></div>
                <div class="stat-label"><?php echo $t['critical_suppliers']; ?></div>
            </div>
        </div>

        <div class="section-title"><?php echo $t['modules']; ?></div>

        <div class="modules-grid">
            <div class="module-card">
                <div class="module-icon">&#128200;</div>
                <div class="module-title"><?php echo $t['bia']; ?></div>
                <div class="module-description">Identifica procesos criticos y determina impactos de interrupciones</div>
                <a href="bia.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#9888;</div>
                <div class="module-title"><?php echo $t['risk_assessment']; ?></div>
                <div class="module-description">Evalua amenazas y vulnerabilidades a la continuidad</div>
                <a href="riesgos.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128268;</div>
                <div class="module-title"><?php echo $t['strategies']; ?></div>
                <div class="module-description">Define estrategias para mantener operaciones criticas</div>
                <a href="estrategias.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128193;</div>
                <div class="module-title"><?php echo $t['bcp_plans']; ?></div>
                <div class="module-description">Planes de continuidad del negocio documentados</div>
                <a href="planes_bcp.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128295;</div>
                <div class="module-title"><?php echo $t['drp_plans']; ?></div>
                <div class="module-description">Planes de recuperacion ante desastres</div>
                <a href="planes_drp.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#9989;</div>
                <div class="module-title"><?php echo $t['tests']; ?></div>
                <div class="module-description">Pruebas, simulacros y ejercicios de continuidad</div>
                <a href="pruebas.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128293;</div>
                <div class="module-title"><?php echo $t['incident_mgmt']; ?></div>
                <div class="module-description">Gestion y respuesta a incidentes e interrupciones</div>
                <a href="incidentes.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128226;</div>
                <div class="module-title"><?php echo $t['crisis_comm']; ?></div>
                <div class="module-description">Comunicacion con partes interesadas en crisis</div>
                <a href="comunicacion.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128196;</div>
                <div class="module-title"><?php echo $t['documents']; ?></div>
                <div class="module-description">Documentos, formatos y plantillas ISO 22301</div>
                <a href="documentos.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128202;</div>
                <div class="module-title"><?php echo $t['reports']; ?></div>
                <div class="module-description">Reportes personalizados y exportacion</div>
                <a href="reportes.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2024 AUDITOR PRO - ISO 22301 Gestion de Continuidad del Negocio</p>
            <p>Sistema Integral de Gestion Multi-ISO | Version 1.0.0</p>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
