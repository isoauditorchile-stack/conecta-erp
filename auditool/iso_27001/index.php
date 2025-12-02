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

// Variables de sesion
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'es';

// Obtener estadisticas del modulo ISO 27001
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_assets WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_assets = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_risks WHERE company_id = ? AND risk_level IN ('alto', 'critico')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$critical_risks = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_controls WHERE company_id = ? AND implementation_status = 'implementado'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$controls_implemented = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_incidents WHERE company_id = ? AND status IN ('abierto', 'en_investigacion')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$open_incidents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ? AND approval_status = 'aprobado'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$approved_policies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_audits WHERE company_id = ? AND audit_status = 'completada'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$completed_audits = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Obtener ultimos 10 activos criticos
$stmt = $conn->prepare("SELECT asset_name, asset_type, criticality, last_review FROM iso27001_assets WHERE company_id = ? ORDER BY criticality DESC, last_review DESC LIMIT 10");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$recent_assets = $stmt->get_result();
$stmt->close();

// Obtener ultimos 10 riesgos
$stmt = $conn->prepare("SELECT risk_id, risk_name, risk_level, probability, impact, treatment_status FROM iso27001_risks WHERE company_id = ? ORDER BY created_date DESC LIMIT 10");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$recent_risks = $stmt->get_result();
$stmt->close();

// Obtener ultimos incidentes
$stmt = $conn->prepare("SELECT incident_id, incident_type, severity, reported_date, status FROM iso27001_incidents WHERE company_id = ? ORDER BY reported_date DESC LIMIT 10");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$recent_incidents = $stmt->get_result();
$stmt->close();

// Calcular porcentaje de cumplimiento de controles
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_controls WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_controls = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$compliance_percentage = $total_controls > 0 ? round(($controls_implemented / $total_controls) * 100, 2) : 0;

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'ISO 27001 - Seguridad de la Informacion',
        'dashboard' => 'Panel de Control',
        'statistics' => 'Estadisticas',
        'total_assets' => 'Activos de Informacion',
        'critical_risks' => 'Riesgos Criticos/Altos',
        'controls_impl' => 'Controles Implementados',
        'open_incidents' => 'Incidentes Abiertos',
        'policies' => 'Politicas Aprobadas',
        'audits' => 'Auditorias Completadas',
        'compliance' => 'Cumplimiento de Controles',
        'modules' => 'Modulos de Gestion',
        'assets' => 'Activos de Informacion',
        'risks' => 'Analisis de Riesgos',
        'controls' => 'Controles de Seguridad',
        'policies_mod' => 'Politicas y Procedimientos',
        'incidents' => 'Incidentes de Seguridad',
        'treatment_plan' => 'Plan de Tratamiento',
        'documents' => 'Documentos y Formatos',
        'reports' => 'Reportes y Exportacion',
        'access' => 'Acceder',
        'recent_assets' => 'Activos Recientes',
        'recent_risks' => 'Riesgos Recientes',
        'recent_incidents' => 'Incidentes Recientes',
        'asset_name' => 'Nombre del Activo',
        'type' => 'Tipo',
        'criticality' => 'Criticidad',
        'last_review' => 'Ultima Revision',
        'risk_name' => 'Nombre del Riesgo',
        'level' => 'Nivel',
        'probability' => 'Probabilidad',
        'impact' => 'Impacto',
        'treatment' => 'Tratamiento',
        'incident_type' => 'Tipo de Incidente',
        'severity' => 'Severidad',
        'date' => 'Fecha',
        'status' => 'Estado',
        'back' => 'Volver al Menu Principal',
        'print' => 'Imprimir',
        'export_excel' => 'Exportar a Excel',
        'export_pdf' => 'Exportar a PDF',
        'actions' => 'Acciones'
    ],
    'en' => [
        'title' => 'ISO 27001 - Information Security',
        'dashboard' => 'Dashboard',
        'statistics' => 'Statistics',
        'total_assets' => 'Information Assets',
        'critical_risks' => 'Critical/High Risks',
        'controls_impl' => 'Implemented Controls',
        'open_incidents' => 'Open Incidents',
        'policies' => 'Approved Policies',
        'audits' => 'Completed Audits',
        'compliance' => 'Controls Compliance',
        'modules' => 'Management Modules',
        'assets' => 'Information Assets',
        'risks' => 'Risk Analysis',
        'controls' => 'Security Controls',
        'policies_mod' => 'Policies and Procedures',
        'incidents' => 'Security Incidents',
        'treatment_plan' => 'Treatment Plan',
        'documents' => 'Documents and Forms',
        'reports' => 'Reports and Export',
        'access' => 'Access',
        'recent_assets' => 'Recent Assets',
        'recent_risks' => 'Recent Risks',
        'recent_incidents' => 'Recent Incidents',
        'asset_name' => 'Asset Name',
        'type' => 'Type',
        'criticality' => 'Criticality',
        'last_review' => 'Last Review',
        'risk_name' => 'Risk Name',
        'level' => 'Level',
        'probability' => 'Probability',
        'impact' => 'Impact',
        'treatment' => 'Treatment',
        'incident_type' => 'Incident Type',
        'severity' => 'Severity',
        'date' => 'Date',
        'status' => 'Status',
        'back' => 'Back to Main Menu',
        'print' => 'Print',
        'export_excel' => 'Export to Excel',
        'export_pdf' => 'Export to PDF',
        'actions' => 'Actions'
    ],
    'pt' => [
        'title' => 'ISO 27001 - Seguranca da Informacao',
        'dashboard' => 'Painel de Controle',
        'statistics' => 'Estatisticas',
        'total_assets' => 'Ativos de Informacao',
        'critical_risks' => 'Riscos Criticos/Altos',
        'controls_impl' => 'Controles Implementados',
        'open_incidents' => 'Incidentes Abertos',
        'policies' => 'Politicas Aprovadas',
        'audits' => 'Auditorias Concluidas',
        'compliance' => 'Conformidade de Controles',
        'modules' => 'Modulos de Gestao',
        'assets' => 'Ativos de Informacao',
        'risks' => 'Analise de Riscos',
        'controls' => 'Controles de Seguranca',
        'policies_mod' => 'Politicas e Procedimentos',
        'incidents' => 'Incidentes de Seguranca',
        'treatment_plan' => 'Plano de Tratamento',
        'documents' => 'Documentos e Formatos',
        'reports' => 'Relatorios e Exportacao',
        'access' => 'Acessar',
        'recent_assets' => 'Ativos Recentes',
        'recent_risks' => 'Riscos Recentes',
        'recent_incidents' => 'Incidentes Recentes',
        'asset_name' => 'Nome do Ativo',
        'type' => 'Tipo',
        'criticality' => 'Criticidade',
        'last_review' => 'Ultima Revisao',
        'risk_name' => 'Nome do Risco',
        'level' => 'Nivel',
        'probability' => 'Probabilidade',
        'impact' => 'Impacto',
        'treatment' => 'Tratamento',
        'incident_type' => 'Tipo de Incidente',
        'severity' => 'Severidade',
        'date' => 'Data',
        'status' => 'Estado',
        'back' => 'Voltar ao Menu Principal',
        'print' => 'Imprimir',
        'export_excel' => 'Exportar para Excel',
        'export_pdf' => 'Exportar para PDF',
        'actions' => 'Acoes'
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            font-size: 32px;
            color: #cc0000;
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

        .stat-card.red { border-color: #cc0000; }
        .stat-card.blue { border-color: #0066cc; }
        .stat-card.green { border-color: #00994d; }
        .stat-card.orange { border-color: #ff6600; }
        .stat-card.purple { border-color: #9900cc; }
        .stat-card.yellow { border-color: #ffcc00; }

        .stat-value {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
        }

        .compliance-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .compliance-bar {
            width: 100%;
            height: 40px;
            background: #f0f0f0;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            margin-top: 15px;
        }

        .compliance-fill {
            height: 100%;
            background: linear-gradient(90deg, #00994d 0%, #66cc00 100%);
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
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
            border-top: 4px solid #cc0000;
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

        .table-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            color: #666;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-critical { background: #cc0000; color: white; }
        .badge-high { background: #ff6600; color: white; }
        .badge-medium { background: #ffcc00; color: #333; }
        .badge-low { background: #00994d; color: white; }

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
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header {
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
            <div class="header-left">
                <h1>&#128274; <?php echo $t['title']; ?></h1>
                <p>AUDITOR PRO - <?php echo $t['dashboard']; ?></p>
            </div>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                <a href="export.php?format=excel&type=dashboard" class="btn btn-success">&#128202; <?php echo $t['export_excel']; ?></a>
                <a href="export.php?format=pdf&type=dashboard" class="btn btn-danger">&#128196; <?php echo $t['export_pdf']; ?></a>
                <a href="../index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-value"><?php echo $total_assets; ?></div>
                <div class="stat-label"><?php echo $t['total_assets']; ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?php echo $critical_risks; ?></div>
                <div class="stat-label"><?php echo $t['critical_risks']; ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?php echo $controls_implemented; ?></div>
                <div class="stat-label"><?php echo $t['controls_impl']; ?></div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?php echo $open_incidents; ?></div>
                <div class="stat-label"><?php echo $t['open_incidents']; ?></div>
            </div>
            <div class="stat-card purple">
                <div class="stat-value"><?php echo $approved_policies; ?></div>
                <div class="stat-label"><?php echo $t['policies']; ?></div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-value"><?php echo $completed_audits; ?></div>
                <div class="stat-label"><?php echo $t['audits']; ?></div>
            </div>
        </div>

        <div class="compliance-card">
            <h3><?php echo $t['compliance']; ?></h3>
            <div class="compliance-bar">
                <div class="compliance-fill" style="width: <?php echo $compliance_percentage; ?>%">
                    <?php echo $compliance_percentage; ?>%
                </div>
            </div>
        </div>

        <div class="section-title"><?php echo $t['modules']; ?></div>

        <div class="modules-grid">
            <div class="module-card">
                <div class="module-icon">&#128190;</div>
                <div class="module-title"><?php echo $t['assets']; ?></div>
                <div class="module-description">Inventario y clasificacion de activos de informacion</div>
                <a href="activos.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#9888;</div>
                <div class="module-title"><?php echo $t['risks']; ?></div>
                <div class="module-description">Identificacion y analisis de riesgos de seguridad</div>
                <a href="riesgos.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#9881;</div>
                <div class="module-title"><?php echo $t['controls']; ?></div>
                <div class="module-description">Controles de seguridad del Anexo A</div>
                <a href="controles.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128214;</div>
                <div class="module-title"><?php echo $t['policies_mod']; ?></div>
                <div class="module-description">Politicas y procedimientos de seguridad</div>
                <a href="politicas.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128293;</div>
                <div class="module-title"><?php echo $t['incidents']; ?></div>
                <div class="module-description">Gestion de incidentes de seguridad</div>
                <a href="incidentes.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128203;</div>
                <div class="module-title"><?php echo $t['treatment_plan']; ?></div>
                <div class="module-description">Plan de tratamiento de riesgos</div>
                <a href="plan_tratamiento.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128196;</div>
                <div class="module-title"><?php echo $t['documents']; ?></div>
                <div class="module-description">Documentos, formatos y plantillas</div>
                <a href="documentos.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>

            <div class="module-card">
                <div class="module-icon">&#128202;</div>
                <div class="module-title"><?php echo $t['reports']; ?></div>
                <div class="module-description">Reportes personalizados y exportacion</div>
                <a href="reportes.php" class="btn btn-primary"><?php echo $t['access']; ?></a>
            </div>
        </div>

        <div class="table-container">
            <h3><?php echo $t['recent_assets']; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php echo $t['asset_name']; ?></th>
                        <th><?php echo $t['type']; ?></th>
                        <th><?php echo $t['criticality']; ?></th>
                        <th><?php echo $t['last_review']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($asset = $recent_assets->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                        <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                        <td>
                            <?php
                            $criticality = strtolower($asset['criticality']);
                            $badge_class = 'badge-medium';
                            if ($criticality == 'critica') $badge_class = 'badge-critical';
                            elseif ($criticality == 'alta') $badge_class = 'badge-high';
                            elseif ($criticality == 'baja') $badge_class = 'badge-low';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($asset['criticality']); ?></span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($asset['last_review'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3><?php echo $t['recent_risks']; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo $t['risk_name']; ?></th>
                        <th><?php echo $t['level']; ?></th>
                        <th><?php echo $t['probability']; ?></th>
                        <th><?php echo $t['impact']; ?></th>
                        <th><?php echo $t['treatment']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($risk = $recent_risks->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($risk['risk_id']); ?></td>
                        <td><?php echo htmlspecialchars($risk['risk_name']); ?></td>
                        <td>
                            <?php
                            $level = strtolower($risk['risk_level']);
                            $badge_class = 'badge-medium';
                            if ($level == 'critico') $badge_class = 'badge-critical';
                            elseif ($level == 'alto') $badge_class = 'badge-high';
                            elseif ($level == 'bajo') $badge_class = 'badge-low';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($risk['risk_level']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($risk['probability']); ?></td>
                        <td><?php echo htmlspecialchars($risk['impact']); ?></td>
                        <td><?php echo htmlspecialchars($risk['treatment_status']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3><?php echo $t['recent_incidents']; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo $t['incident_type']; ?></th>
                        <th><?php echo $t['severity']; ?></th>
                        <th><?php echo $t['date']; ?></th>
                        <th><?php echo $t['status']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($incident = $recent_incidents->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($incident['incident_id']); ?></td>
                        <td><?php echo htmlspecialchars($incident['incident_type']); ?></td>
                        <td>
                            <?php
                            $severity = strtolower($incident['severity']);
                            $badge_class = 'badge-medium';
                            if ($severity == 'critica') $badge_class = 'badge-critical';
                            elseif ($severity == 'alta') $badge_class = 'badge-high';
                            elseif ($severity == 'baja') $badge_class = 'badge-low';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($incident['severity']); ?></span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($incident['reported_date'])); ?></td>
                        <td><?php echo htmlspecialchars($incident['status']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>&copy; 2024 AUDITOR PRO - ISO 27001 Seguridad de la Informacion</p>
            <p>Sistema Integral de Gestion Multi-ISO | Version 1.0.0</p>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
