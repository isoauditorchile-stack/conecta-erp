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

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            // Calcular nivel de riesgo
            $probability = intval($_POST['probability']);
            $impact = intval($_POST['impact']);
            $risk_score = $probability * $impact;

            $risk_level = 'Bajo';
            if ($risk_score >= 20) $risk_level = 'Critico';
            elseif ($risk_score >= 12) $risk_level = 'Alto';
            elseif ($risk_score >= 6) $risk_level = 'Medio';

            $stmt = $conn->prepare("INSERT INTO iso27001_risks (company_id, risk_id, risk_name, risk_description, risk_category, threat_source, vulnerability, existing_controls, probability, impact, risk_score, risk_level, treatment_option, treatment_description, treatment_responsible, treatment_deadline, residual_probability, residual_impact, residual_score, residual_level, treatment_status, review_frequency, last_review, next_review, related_assets, legal_requirements, business_impact, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            // Calcular riesgo residual
            $residual_prob = intval($_POST['residual_probability']);
            $residual_imp = intval($_POST['residual_impact']);
            $residual_score = $residual_prob * $residual_imp;

            $residual_level = 'Bajo';
            if ($residual_score >= 20) $residual_level = 'Critico';
            elseif ($residual_score >= 12) $residual_level = 'Alto';
            elseif ($residual_score >= 6) $residual_level = 'Medio';

            $stmt->bind_param("issssssiiisssssissssssssssi",
                $company_id,
                $_POST['risk_id'],
                $_POST['risk_name'],
                $_POST['risk_description'],
                $_POST['risk_category'],
                $_POST['threat_source'],
                $_POST['vulnerability'],
                $_POST['existing_controls'],
                $probability,
                $impact,
                $risk_score,
                $risk_level,
                $_POST['treatment_option'],
                $_POST['treatment_description'],
                $_POST['treatment_responsible'],
                $_POST['treatment_deadline'],
                $residual_prob,
                $residual_imp,
                $residual_score,
                $residual_level,
                $_POST['treatment_status'],
                $_POST['review_frequency'],
                $_POST['last_review'],
                $_POST['next_review'],
                $_POST['related_assets'],
                $_POST['legal_requirements'],
                $_POST['business_impact'],
                $user_id
            );

            if ($stmt->execute()) {
                $message = 'Riesgo creado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al crear riesgo: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $probability = intval($_POST['probability']);
            $impact = intval($_POST['impact']);
            $risk_score = $probability * $impact;

            $risk_level = 'Bajo';
            if ($risk_score >= 20) $risk_level = 'Critico';
            elseif ($risk_score >= 12) $risk_level = 'Alto';
            elseif ($risk_score >= 6) $risk_level = 'Medio';

            $residual_prob = intval($_POST['residual_probability']);
            $residual_imp = intval($_POST['residual_impact']);
            $residual_score = $residual_prob * $residual_imp;

            $residual_level = 'Bajo';
            if ($residual_score >= 20) $residual_level = 'Critico';
            elseif ($residual_score >= 12) $residual_level = 'Alto';
            elseif ($residual_score >= 6) $residual_level = 'Medio';

            $stmt = $conn->prepare("UPDATE iso27001_risks SET risk_name = ?, risk_description = ?, risk_category = ?, threat_source = ?, vulnerability = ?, existing_controls = ?, probability = ?, impact = ?, risk_score = ?, risk_level = ?, treatment_option = ?, treatment_description = ?, treatment_responsible = ?, treatment_deadline = ?, residual_probability = ?, residual_impact = ?, residual_score = ?, residual_level = ?, treatment_status = ?, review_frequency = ?, last_review = ?, next_review = ?, related_assets = ?, legal_requirements = ?, business_impact = ?, updated_by = ?, updated_date = NOW() WHERE id = ? AND company_id = ?");

            $stmt->bind_param("ssssssiisssssisissssssssii",
                $_POST['risk_name'],
                $_POST['risk_description'],
                $_POST['risk_category'],
                $_POST['threat_source'],
                $_POST['vulnerability'],
                $_POST['existing_controls'],
                $probability,
                $impact,
                $risk_score,
                $risk_level,
                $_POST['treatment_option'],
                $_POST['treatment_description'],
                $_POST['treatment_responsible'],
                $_POST['treatment_deadline'],
                $residual_prob,
                $residual_imp,
                $residual_score,
                $residual_level,
                $_POST['treatment_status'],
                $_POST['review_frequency'],
                $_POST['last_review'],
                $_POST['next_review'],
                $_POST['related_assets'],
                $_POST['legal_requirements'],
                $_POST['business_impact'],
                $user_id,
                $_POST['id'],
                $company_id
            );

            if ($stmt->execute()) {
                $message = 'Riesgo actualizado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar riesgo: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM iso27001_risks WHERE id = ? AND company_id = ?");
            $stmt->bind_param("ii", $_POST['id'], $company_id);

            if ($stmt->execute()) {
                $message = 'Riesgo eliminado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar riesgo: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Obtener filtros
$filter_level = isset($_GET['filter_level']) ? $_GET['filter_level'] : '';
$filter_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir query con filtros
$query = "SELECT * FROM iso27001_risks WHERE company_id = ?";
$params = [$company_id];
$types = "i";

if ($filter_level) {
    $query .= " AND risk_level = ?";
    $params[] = $filter_level;
    $types .= "s";
}

if ($filter_category) {
    $query .= " AND risk_category = ?";
    $params[] = $filter_category;
    $types .= "s";
}

if ($filter_status) {
    $query .= " AND treatment_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($search) {
    $query .= " AND (risk_name LIKE ? OR risk_id LIKE ? OR risk_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY risk_score DESC, risk_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$risks_result = $stmt->get_result();
$stmt->close();

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_risks WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_risks = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_risks WHERE company_id = ? AND risk_level IN ('Critico', 'Alto')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$critical_risks = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_risks WHERE company_id = ? AND treatment_status IN ('pendiente', 'en_proceso')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$pending_treatment = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'Analisis y Gestion de Riesgos',
        'subtitle' => 'ISO 27001 - Evaluacion de Riesgos de Seguridad de la Informacion',
        'total_risks' => 'Total de Riesgos',
        'critical_risks' => 'Riesgos Criticos/Altos',
        'pending_treatment' => 'Tratamientos Pendientes',
        'add_risk' => 'Agregar Riesgo',
        'risk_matrix' => 'Matriz de Riesgos',
        'search' => 'Buscar',
        'filter' => 'Filtrar',
        'level' => 'Nivel',
        'category' => 'Categoria',
        'status' => 'Estado',
        'all' => 'Todos',
        'risk_id' => 'ID Riesgo',
        'risk_name' => 'Nombre del Riesgo',
        'description' => 'Descripcion',
        'risk_category' => 'Categoria de Riesgo',
        'threat_source' => 'Fuente de Amenaza',
        'vulnerability' => 'Vulnerabilidad',
        'existing_controls' => 'Controles Existentes',
        'probability' => 'Probabilidad',
        'impact' => 'Impacto',
        'risk_score' => 'Puntuacion',
        'risk_level' => 'Nivel de Riesgo',
        'treatment_option' => 'Opcion de Tratamiento',
        'treatment_description' => 'Descripcion del Tratamiento',
        'treatment_responsible' => 'Responsable del Tratamiento',
        'treatment_deadline' => 'Fecha Limite',
        'residual_probability' => 'Probabilidad Residual',
        'residual_impact' => 'Impacto Residual',
        'residual_score' => 'Puntuacion Residual',
        'residual_level' => 'Nivel Residual',
        'treatment_status' => 'Estado del Tratamiento',
        'review_frequency' => 'Frecuencia de Revision',
        'last_review' => 'Ultima Revision',
        'next_review' => 'Proxima Revision',
        'related_assets' => 'Activos Relacionados',
        'legal_requirements' => 'Requisitos Legales',
        'business_impact' => 'Impacto en el Negocio',
        'actions' => 'Acciones',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'view' => 'Ver',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'back' => 'Volver',
        'print' => 'Imprimir',
        'export_excel' => 'Exportar Excel',
        'export_pdf' => 'Exportar PDF',
        'download_template' => 'Descargar Plantilla',
        'accept' => 'Aceptar',
        'mitigate' => 'Mitigar',
        'transfer' => 'Transferir',
        'avoid' => 'Evitar',
        'pending' => 'Pendiente',
        'in_process' => 'En Proceso',
        'completed' => 'Completado',
        'low' => 'Bajo',
        'medium' => 'Medio',
        'high' => 'Alto',
        'critical' => 'Critico',
        'monthly' => 'Mensual',
        'quarterly' => 'Trimestral',
        'semiannual' => 'Semestral',
        'annual' => 'Anual'
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
            max-width: 1600px;
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

        .header-title h1 {
            font-size: 28px;
            color: #cc0000;
            margin-bottom: 5px;
        }

        .header-title p {
            color: #666;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            font-size: 14px;
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

        .btn-warning {
            background: #ff6600;
            color: white;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #cc0000;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }

        .matrix-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .matrix-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .risk-matrix {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            gap: 10px;
            margin: 20px 0;
        }

        .matrix-label {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #333;
        }

        .matrix-cell {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: bold;
            color: white;
            font-size: 18px;
        }

        .matrix-cell.critical {
            background: #cc0000;
        }

        .matrix-cell.high {
            background: #ff6600;
        }

        .matrix-cell.medium {
            background: #ffcc00;
            color: #333;
        }

        .matrix-cell.low {
            background: #00994d;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
            white-space: nowrap;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            color: #666;
            font-size: 13px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .badge-critical {
            background: #cc0000;
            color: white;
        }

        .badge-high {
            background: #ff6600;
            color: white;
        }

        .badge-medium {
            background: #ffcc00;
            color: #333;
        }

        .badge-low {
            background: #00994d;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 15px;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
        }

        .modal-header h2 {
            color: #333;
            font-size: 24px;
        }

        .close-modal {
            font-size: 32px;
            cursor: pointer;
            color: #999;
            border: none;
            background: none;
        }

        .close-modal:hover {
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-section-title {
            grid-column: 1 / -1;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-top: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }

        @media print {
            body {
                background: white;
            }
            .action-buttons, .btn, .filters-section, .modal {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                text-align: center;
            }

            .action-buttons {
                justify-content: center;
                margin-top: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .risk-matrix {
                grid-template-columns: 60px repeat(5, 1fr);
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>&#9888; <?php echo $t['title']; ?></h1>
                    <p><?php echo $t['subtitle']; ?></p>
                </div>
                <div class="action-buttons">
                    <button onclick="openModal('addModal')" class="btn btn-success">+ <?php echo $t['add_risk']; ?></button>
                    <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                    <a href="export.php?format=excel&type=risks" class="btn btn-success">&#128202; <?php echo $t['export_excel']; ?></a>
                    <a href="export.php?format=pdf&type=risks" class="btn btn-danger">&#128196; <?php echo $t['export_pdf']; ?></a>
                    <a href="templates/plantilla_riesgos.xlsx" download class="btn btn-warning">&#128229; <?php echo $t['download_template']; ?></a>
                    <a href="index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_risks; ?></div>
                    <div class="stat-label"><?php echo $t['total_risks']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $critical_risks; ?></div>
                    <div class="stat-label"><?php echo $t['critical_risks']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $pending_treatment; ?></div>
                    <div class="stat-label"><?php echo $t['pending_treatment']; ?></div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message message-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="matrix-container">
            <div class="matrix-title"><?php echo $t['risk_matrix']; ?></div>
            <div class="risk-matrix">
                <div class="matrix-label"></div>
                <div class="matrix-label">1</div>
                <div class="matrix-label">2</div>
                <div class="matrix-label">3</div>
                <div class="matrix-label">4</div>
                <div class="matrix-label">5</div>

                <div class="matrix-label">5</div>
                <div class="matrix-cell medium">5</div>
                <div class="matrix-cell medium">10</div>
                <div class="matrix-cell high">15</div>
                <div class="matrix-cell critical">20</div>
                <div class="matrix-cell critical">25</div>

                <div class="matrix-label">4</div>
                <div class="matrix-cell low">4</div>
                <div class="matrix-cell medium">8</div>
                <div class="matrix-cell high">12</div>
                <div class="matrix-cell high">16</div>
                <div class="matrix-cell critical">20</div>

                <div class="matrix-label">3</div>
                <div class="matrix-cell low">3</div>
                <div class="matrix-cell medium">6</div>
                <div class="matrix-cell medium">9</div>
                <div class="matrix-cell high">12</div>
                <div class="matrix-cell high">15</div>

                <div class="matrix-label">2</div>
                <div class="matrix-cell low">2</div>
                <div class="matrix-cell low">4</div>
                <div class="matrix-cell medium">6</div>
                <div class="matrix-cell medium">8</div>
                <div class="matrix-cell medium">10</div>

                <div class="matrix-label">1</div>
                <div class="matrix-cell low">1</div>
                <div class="matrix-cell low">2</div>
                <div class="matrix-cell low">3</div>
                <div class="matrix-cell low">4</div>
                <div class="matrix-cell medium">5</div>
            </div>
            <div style="margin-top: 20px; font-size: 13px; color: #666;">
                <strong><?php echo $t['probability']; ?>:</strong> 1=Muy Baja, 2=Baja, 3=Media, 4=Alta, 5=Muy Alta |
                <strong><?php echo $t['impact']; ?>:</strong> 1=Muy Bajo, 2=Bajo, 3=Medio, 4=Alto, 5=Muy Alto
            </div>
        </div>

        <div class="filters-section">
            <form method="GET">
                <div class="filters-row">
                    <div class="form-group">
                        <label><?php echo $t['search']; ?></label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $t['search']; ?>...">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['level']; ?></label>
                        <select name="filter_level">
                            <option value=""><?php echo $t['all']; ?></option>
                            <option value="Critico" <?php echo $filter_level == 'Critico' ? 'selected' : ''; ?>><?php echo $t['critical']; ?></option>
                            <option value="Alto" <?php echo $filter_level == 'Alto' ? 'selected' : ''; ?>><?php echo $t['high']; ?></option>
                            <option value="Medio" <?php echo $filter_level == 'Medio' ? 'selected' : ''; ?>><?php echo $t['medium']; ?></option>
                            <option value="Bajo" <?php echo $filter_level == 'Bajo' ? 'selected' : ''; ?>><?php echo $t['low']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['status']; ?></label>
                        <select name="filter_status">
                            <option value=""><?php echo $t['all']; ?></option>
                            <option value="pendiente" <?php echo $filter_status == 'pendiente' ? 'selected' : ''; ?>><?php echo $t['pending']; ?></option>
                            <option value="en_proceso" <?php echo $filter_status == 'en_proceso' ? 'selected' : ''; ?>><?php echo $t['in_process']; ?></option>
                            <option value="completado" <?php echo $filter_status == 'completado' ? 'selected' : ''; ?>><?php echo $t['completed']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $t['filter']; ?></button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo $t['risk_id']; ?></th>
                            <th><?php echo $t['risk_name']; ?></th>
                            <th><?php echo $t['category']; ?></th>
                            <th><?php echo $t['probability']; ?></th>
                            <th><?php echo $t['impact']; ?></th>
                            <th><?php echo $t['risk_score']; ?></th>
                            <th><?php echo $t['risk_level']; ?></th>
                            <th><?php echo $t['treatment_option']; ?></th>
                            <th><?php echo $t['treatment_status']; ?></th>
                            <th><?php echo $t['residual_level']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($risk = $risks_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($risk['risk_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($risk['risk_name']); ?></td>
                            <td><?php echo htmlspecialchars($risk['risk_category']); ?></td>
                            <td><?php echo $risk['probability']; ?></td>
                            <td><?php echo $risk['impact']; ?></td>
                            <td><strong><?php echo $risk['risk_score']; ?></strong></td>
                            <td>
                                <?php
                                $level = $risk['risk_level'];
                                $badge_class = 'badge-medium';
                                if ($level == 'Critico') $badge_class = 'badge-critical';
                                elseif ($level == 'Alto') $badge_class = 'badge-high';
                                elseif ($level == 'Bajo') $badge_class = 'badge-low';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $level; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($risk['treatment_option']); ?></td>
                            <td><?php echo htmlspecialchars($risk['treatment_status']); ?></td>
                            <td>
                                <?php
                                $residual = $risk['residual_level'];
                                $badge_class = 'badge-medium';
                                if ($residual == 'Critico') $badge_class = 'badge-critical';
                                elseif ($residual == 'Alto') $badge_class = 'badge-high';
                                elseif ($residual == 'Bajo') $badge_class = 'badge-low';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $residual; ?></span>
                            </td>
                            <td>
                                <button onclick='openEditModal(<?php echo json_encode($risk); ?>)' class="btn btn-primary btn-small"><?php echo $t['edit']; ?></button>
                                <button onclick='confirmDelete(<?php echo $risk['id']; ?>)' class="btn btn-danger btn-small"><?php echo $t['delete']; ?></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Agregar (similar structure to activos.php, but with risk-specific fields) -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $t['add_risk']; ?></h2>
                <button class="close-modal" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-section-title">Identificacion del Riesgo</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['risk_id']; ?> *</label>
                        <input type="text" name="risk_id" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['risk_name']; ?> *</label>
                        <input type="text" name="risk_name" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['risk_category']; ?> *</label>
                        <input type="text" name="risk_category" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['threat_source']; ?></label>
                        <input type="text" name="threat_source">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['vulnerability']; ?></label>
                        <input type="text" name="vulnerability">
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $t['description']; ?> *</label>
                    <textarea name="risk_description" required></textarea>
                </div>

                <div class="form-group">
                    <label><?php echo $t['existing_controls']; ?></label>
                    <textarea name="existing_controls"></textarea>
                </div>

                <div class="form-section-title">Evaluacion del Riesgo</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['probability']; ?> (1-5) *</label>
                        <select name="probability" required>
                            <option value="1">1 - Muy Baja</option>
                            <option value="2">2 - Baja</option>
                            <option value="3">3 - Media</option>
                            <option value="4">4 - Alta</option>
                            <option value="5">5 - Muy Alta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['impact']; ?> (1-5) *</label>
                        <select name="impact" required>
                            <option value="1">1 - Muy Bajo</option>
                            <option value="2">2 - Bajo</option>
                            <option value="3">3 - Medio</option>
                            <option value="4">4 - Alto</option>
                            <option value="5">5 - Muy Alto</option>
                        </select>
                    </div>
                </div>

                <div class="form-section-title">Tratamiento del Riesgo</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['treatment_option']; ?> *</label>
                        <select name="treatment_option" required>
                            <option value="Mitigar"><?php echo $t['mitigate']; ?></option>
                            <option value="Transferir"><?php echo $t['transfer']; ?></option>
                            <option value="Aceptar"><?php echo $t['accept']; ?></option>
                            <option value="Evitar"><?php echo $t['avoid']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['treatment_responsible']; ?></label>
                        <input type="text" name="treatment_responsible">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['treatment_deadline']; ?></label>
                        <input type="date" name="treatment_deadline">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['treatment_status']; ?> *</label>
                        <select name="treatment_status" required>
                            <option value="pendiente"><?php echo $t['pending']; ?></option>
                            <option value="en_proceso"><?php echo $t['in_process']; ?></option>
                            <option value="completado"><?php echo $t['completed']; ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $t['treatment_description']; ?></label>
                    <textarea name="treatment_description"></textarea>
                </div>

                <div class="form-section-title">Riesgo Residual</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['residual_probability']; ?> (1-5) *</label>
                        <select name="residual_probability" required>
                            <option value="1">1 - Muy Baja</option>
                            <option value="2">2 - Baja</option>
                            <option value="3">3 - Media</option>
                            <option value="4">4 - Alta</option>
                            <option value="5">5 - Muy Alta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['residual_impact']; ?> (1-5) *</label>
                        <select name="residual_impact" required>
                            <option value="1">1 - Muy Bajo</option>
                            <option value="2">2 - Bajo</option>
                            <option value="3">3 - Medio</option>
                            <option value="4">4 - Alto</option>
                            <option value="5">5 - Muy Alto</option>
                        </select>
                    </div>
                </div>

                <div class="form-section-title">Informacion Adicional</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['review_frequency']; ?></label>
                        <select name="review_frequency">
                            <option value="Mensual"><?php echo $t['monthly']; ?></option>
                            <option value="Trimestral"><?php echo $t['quarterly']; ?></option>
                            <option value="Semestral"><?php echo $t['semiannual']; ?></option>
                            <option value="Anual"><?php echo $t['annual']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['last_review']; ?></label>
                        <input type="date" name="last_review" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['next_review']; ?></label>
                        <input type="date" name="next_review">
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $t['related_assets']; ?></label>
                    <textarea name="related_assets"></textarea>
                </div>

                <div class="form-group">
                    <label><?php echo $t['legal_requirements']; ?></label>
                    <textarea name="legal_requirements"></textarea>
                </div>

                <div class="form-group">
                    <label><?php echo $t['business_impact']; ?></label>
                    <textarea name="business_impact"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn btn-success"><?php echo $t['save']; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form para eliminar -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function confirmDelete(id) {
            if (confirm('Esta seguro de que desea eliminar este riesgo?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
