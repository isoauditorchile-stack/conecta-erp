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

$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $stmt = $conn->prepare("INSERT INTO iso27001_policies (company_id, policy_id, policy_name, policy_category, policy_type, version, effective_date, review_date, next_review_date, approval_date, approved_by, responsible, scope, objective, policy_description, related_controls, related_procedures, distribution_list, approval_status, revision_history, compliance_requirements, training_required, acknowledgment_required, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("isssssssssssssssssssssi",
                $company_id,
                $_POST['policy_id'],          // era policy_code
                $_POST['policy_name'],
                $_POST['policy_category'],
                $_POST['policy_type'],
                $_POST['version'],
                $_POST['effective_date'],
                $_POST['review_date'],
                $_POST['next_review_date'],    // era next_review
                $_POST['approval_date'],
                $_POST['approved_by'],
                $_POST['responsible'],         // era policy_owner
                $_POST['scope'],               // era policy_scope
                $_POST['objective'],           // era policy_objectives
                $_POST['policy_description'],  // era policy_content
                $_POST['related_controls'],
                $_POST['related_procedures'],
                $_POST['distribution_list'],
                $_POST['approval_status'],
                $_POST['revision_history'],
                $_POST['compliance_requirements'],
                $_POST['training_required'],
                $_POST['acknowledgment_required'],
                $user_id
            );

            if ($stmt->execute()) {
                $message = 'Politica creada exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al crear politica: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            // Actualizar version automaticamente
            $new_version = floatval($_POST['current_version']) + 0.1;

            $stmt = $conn->prepare("UPDATE iso27001_policies SET policy_name = ?, policy_category = ?, policy_type = ?, version = ?, effective_date = ?, review_date = ?, next_review_date = ?, approval_date = ?, approved_by = ?, responsible = ?, scope = ?, objective = ?, policy_description = ?, related_controls = ?, related_procedures = ?, distribution_list = ?, approval_status = ?, revision_history = ?, compliance_requirements = ?, training_required = ?, acknowledgment_required = ?, updated_by = ? WHERE id = ? AND company_id = ?");

            $stmt->bind_param("ssssssssssssssssssssiii",
                $_POST['policy_name'],
                $_POST['policy_category'],
                $_POST['policy_type'],
                $new_version,
                $_POST['effective_date'],
                $_POST['review_date'],
                $_POST['next_review'],
                $_POST['approval_date'],
                $_POST['approved_by'],
                $_POST['policy_owner'],
                $_POST['policy_scope'],
                $_POST['policy_objectives'],
                $_POST['policy_content'],
                $_POST['related_controls'],
                $_POST['related_procedures'],
                $_POST['distribution_list'],
                $_POST['approval_status'],
                $_POST['revision_history'],
                $_POST['compliance_requirements'],
                $_POST['training_required'],
                $_POST['acknowledgment_required'],
                $user_id,
                $_POST['id'],
                $company_id
            );

            if ($stmt->execute()) {
                $message = 'Politica actualizada exitosamente (Version: ' . $new_version . ')';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar politica: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM iso27001_policies WHERE id = ? AND company_id = ?");
            $stmt->bind_param("ii", $_POST['id'], $company_id);

            if ($stmt->execute()) {
                $message = 'Politica eliminada exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar politica: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Obtener filtros
$filter_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir query con filtros
$query = "SELECT * FROM iso27001_policies WHERE company_id = ?";
$params = [$company_id];
$types = "i";

if ($filter_category) {
    $query .= " AND policy_category = ?";
    $params[] = $filter_category;
    $types .= "s";
}

if ($filter_status) {
    $query .= " AND approval_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($search) {
    $query .= " AND (policy_id LIKE ? OR policy_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY policy_category, policy_id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$policies_result = $stmt->get_result();

// Guardar resultados en array para usarlo en JavaScript
$policies_array = [];
while ($policy = $policies_result->fetch_assoc()) {
    $policies_array[] = $policy;
}
$stmt->close();

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_policies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ? AND approval_status = 'Aprobado'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$approved_policies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ? AND approval_status IN ('Borrador', 'En Revisión')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$pending_policies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ? AND next_review < CURDATE()");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$overdue_reviews = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Catalogo de politicas estandar ISO 27001
$policy_templates = [
    'Politicas_Fundamentales' => [
        'name' => 'Politicas Fundamentales',
        'policies' => [
            ['code' => 'POL-SI-001', 'name' => 'Politica de Seguridad de la Informacion', 'description' => 'Politica general que establece el marco de seguridad de la informacion'],
            ['code' => 'POL-SI-002', 'name' => 'Politica de Control de Acceso', 'description' => 'Reglas para gestion y control de accesos a sistemas y datos'],
            ['code' => 'POL-SI-003', 'name' => 'Politica de Clasificacion de la Informacion', 'description' => 'Lineamientos para clasificar y etiquetar la informacion'],
            ['code' => 'POL-SI-004', 'name' => 'Politica de Uso Aceptable', 'description' => 'Reglas de uso aceptable de recursos tecnologicos']
        ]
    ],
    'Politicas_Operacionales' => [
        'name' => 'Politicas Operacionales',
        'policies' => [
            ['code' => 'POL-SI-005', 'name' => 'Politica de Escritorio Limpio y Pantalla Limpia', 'description' => 'Lineamientos para mantener seguridad fisica y digital'],
            ['code' => 'POL-SI-006', 'name' => 'Politica de Transferencia de Informacion', 'description' => 'Controles para transferencia segura de informacion'],
            ['code' => 'POL-SI-007', 'name' => 'Politica de Gestion de Contrasenas', 'description' => 'Requisitos para creacion y gestion de contrasenas'],
            ['code' => 'POL-SI-008', 'name' => 'Politica de Respaldo y Recuperacion', 'description' => 'Procedimientos para respaldo y recuperacion de datos']
        ]
    ],
    'Politicas_Tecnicas' => [
        'name' => 'Politicas Tecnicas',
        'policies' => [
            ['code' => 'POL-SI-009', 'name' => 'Politica de Criptografia', 'description' => 'Uso de controles criptograficos para proteger informacion'],
            ['code' => 'POL-SI-010', 'name' => 'Politica de Gestion de Vulnerabilidades', 'description' => 'Identificacion y gestion de vulnerabilidades tecnicas'],
            ['code' => 'POL-SI-011', 'name' => 'Politica de Desarrollo Seguro', 'description' => 'Lineamientos para desarrollo seguro de software'],
            ['code' => 'POL-SI-012', 'name' => 'Politica de Seguridad en la Nube', 'description' => 'Controles para uso seguro de servicios cloud']
        ]
    ],
    'Politicas_Continuidad' => [
        'name' => 'Politicas de Continuidad',
        'policies' => [
            ['code' => 'POL-SI-013', 'name' => 'Politica de Continuidad del Negocio', 'description' => 'Marco para continuidad y recuperacion de operaciones'],
            ['code' => 'POL-SI-014', 'name' => 'Politica de Gestion de Incidentes', 'description' => 'Respuesta y escalamiento de incidentes de seguridad'],
            ['code' => 'POL-SI-015', 'name' => 'Politica de Gestion de Crisis', 'description' => 'Manejo de situaciones de crisis de seguridad']
        ]
    ],
    'Politicas_Cumplimiento' => [
        'name' => 'Politicas de Cumplimiento',
        'policies' => [
            ['code' => 'POL-SI-016', 'name' => 'Politica de Cumplimiento Legal y Regulatorio', 'description' => 'Cumplimiento de requisitos legales y regulatorios'],
            ['code' => 'POL-SI-017', 'name' => 'Politica de Privacidad y Proteccion de Datos', 'description' => 'Proteccion de datos personales y privacidad'],
            ['code' => 'POL-SI-018', 'name' => 'Politica de Propiedad Intelectual', 'description' => 'Proteccion de derechos de propiedad intelectual']
        ]
    ],
    'Politicas_RRHH' => [
        'name' => 'Politicas de Recursos Humanos',
        'policies' => [
            ['code' => 'POL-SI-019', 'name' => 'Politica de Seleccion y Contratacion', 'description' => 'Verificaciones de antecedentes y proceso de contratacion'],
            ['code' => 'POL-SI-020', 'name' => 'Politica de Capacitacion y Concientizacion', 'description' => 'Programa de capacitacion en seguridad'],
            ['code' => 'POL-SI-021', 'name' => 'Politica de Trabajo Remoto', 'description' => 'Controles de seguridad para teletrabajo'],
            ['code' => 'POL-SI-022', 'name' => 'Politica de Terminacion de Empleo', 'description' => 'Proceso de salida y devolucion de activos']
        ]
    ]
];

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'Gestion de Politicas y Procedimientos',
        'subtitle' => 'ISO 27001 - Marco Normativo del SGSI',
        'total_policies' => 'Total de Politicas',
        'approved' => 'Aprobadas',
        'pending' => 'Pendientes',
        'overdue_reviews' => 'Revisiones Vencidas',
        'add_policy' => 'Nueva Politica',
        'filter' => 'Filtrar',
        'search' => 'Buscar',
        'category' => 'Categoria',
        'status' => 'Estado',
        'all' => 'Todas',
        'policy_code' => 'Codigo',
        'policy_name' => 'Nombre de la Politica',
        'version' => 'Version',
        'effective_date' => 'Fecha Vigencia',
        'approval_status' => 'Estado de Aprobacion',
        'policy_owner' => 'Responsable',
        'next_review' => 'Proxima Revision',
        'actions' => 'Acciones',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'view' => 'Ver',
        'download' => 'Descargar',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'back' => 'Volver',
        'print' => 'Imprimir',
        'export_excel' => 'Exportar Excel',
        'export_pdf' => 'Exportar PDF',
        'policy_templates' => 'Plantillas de Politicas'
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }
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
        .header-title h1 { font-size: 28px; color: #0066cc; margin-bottom: 5px; }
        .header-title p { color: #666; font-size: 14px; }
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
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
        .btn-primary { background: #0066cc; color: white; }
        .btn-success { background: #00994d; color: white; }
        .btn-danger { background: #cc0000; color: white; }
        .btn-warning { background: #ff6600; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-secondary { background: #666; color: white; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #0066cc;
        }
        .stat-value { font-size: 32px; font-weight: bold; color: #333; }
        .stat-label { color: #666; font-size: 13px; margin-top: 5px; }
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
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
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }
        .badge-approved { background: #00994d; color: white; }
        .badge-draft { background: #999; color: white; }
        .badge-review { background: #ffcc00; color: #333; }
        .badge-obsolete { background: #cc0000; color: white; }
        .templates-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .template-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        .template-card:hover {
            border-color: #0066cc;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        .template-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .template-list {
            list-style: none;
        }
        .template-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            color: #666;
        }
        .template-item:last-child {
            border-bottom: none;
        }
        .template-code {
            background: #0066cc;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 8px;
            font-size: 11px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0066cc;
        }
        .modal-header h2 { color: #0066cc; font-size: 24px; }
        .close {
            font-size: 35px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            line-height: 20px;
        }
        .close:hover { color: #333; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .header-top { flex-direction: column; text-align: center; }
            .action-buttons { justify-content: center; margin-top: 15px; }
            .templates-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .info-row { grid-template-columns: 1fr; gap: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>📄 <?php echo $t['title']; ?></h1>
                    <p><?php echo $t['subtitle']; ?></p>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="openCreateModal()">+ <?php echo $t['add_policy']; ?></button>
                    <button onclick="window.print()" class="btn btn-primary">🖨️ <?php echo $t['print']; ?></button>
                    <a href="export.php?format=excel&type=policies" class="btn btn-success">📊 <?php echo $t['export_excel']; ?></a>
                    <a href="export.php?format=pdf&type=policies" class="btn btn-danger">📄 <?php echo $t['export_pdf']; ?></a>
                    <a href="index.php" class="btn btn-secondary">← <?php echo $t['back']; ?></a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_policies; ?></div>
                    <div class="stat-label"><?php echo $t['total_policies']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $approved_policies; ?></div>
                    <div class="stat-label"><?php echo $t['approved']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $pending_policies; ?></div>
                    <div class="stat-label"><?php echo $t['pending']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $overdue_reviews; ?></div>
                    <div class="stat-label"><?php echo $t['overdue_reviews']; ?></div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="templates-section">
            <h2 style="color: #333; margin-bottom: 10px;"><?php echo $t['policy_templates']; ?></h2>
            <p style="color: #666; margin-bottom: 20px;">Plantillas predefinidas de politicas para implementar ISO 27001</p>

            <div class="templates-grid">
                <?php foreach ($policy_templates as $category_key => $category): ?>
                <div class="template-card">
                    <div class="template-title"><?php echo $category['name']; ?></div>
                    <ul class="template-list">
                        <?php foreach ($category['policies'] as $policy): ?>
                        <li class="template-item">
                            <span class="template-code"><?php echo $policy['code']; ?></span>
                            <strong><?php echo $policy['name']; ?></strong>
                            <p style="margin-top: 5px; font-size: 12px; color: #999;"><?php echo $policy['description']; ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #dee2e6;">
                        <a href="export.php?format=docx&category=<?php echo $category_key; ?>" class="btn btn-primary btn-small" style="width: 100%; text-align: center;">
                            📄 Descargar Todas (<?php echo count($category['policies']); ?>)
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo $t['policy_code']; ?></th>
                            <th><?php echo $t['policy_name']; ?></th>
                            <th><?php echo $t['version']; ?></th>
                            <th><?php echo $t['effective_date']; ?></th>
                            <th><?php echo $t['approval_status']; ?></th>
                            <th><?php echo $t['policy_owner']; ?></th>
                            <th><?php echo $t['next_review']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($policies_array as $policy): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($policy['policy_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($policy['policy_name']); ?></td>
                            <td>v<?php echo $policy['policy_version']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($policy['effective_date'])); ?></td>
                            <td>
                                <?php
                                $status = $policy['approval_status'];
                                $badge_class = 'badge-draft';
                                if ($status == 'Aprobado') $badge_class = 'badge-approved';
                                elseif ($status == 'En Revisión') $badge_class = 'badge-review';
                                elseif ($status == 'Obsoleto') $badge_class = 'badge-obsolete';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($policy['approved_by']); ?></td>
                            <td>
                                <?php
                                $next_review = strtotime($policy['next_review']);
                                $today = time();
                                $color = $next_review < $today ? 'color: #cc0000; font-weight: bold;' : '';
                                ?>
                                <span style="<?php echo $color; ?>"><?php echo date('d/m/Y', $next_review); ?></span>
                            </td>
                            <td>
                                <button class="btn btn-info btn-small" onclick="viewPolicy(<?php echo $policy['id']; ?>)"><?php echo $t['view']; ?></button>
                                <button class="btn btn-primary btn-small" onclick="editPolicy(<?php echo $policy['id']; ?>)"><?php echo $t['edit']; ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Vista -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="viewModalTitle">Detalles de la Politica</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div id="viewModalContent">
                <div class="info-row">
                    <div class="info-label">Codigo:</div>
                    <div class="info-value" id="view_policy_code"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nombre:</div>
                    <div class="info-value" id="view_policy_name"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Categoria:</div>
                    <div class="info-value" id="view_policy_category"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tipo:</div>
                    <div class="info-value" id="view_policy_type"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Version:</div>
                    <div class="info-value" id="view_version"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado:</div>
                    <div class="info-value" id="view_approval_status"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Responsable:</div>
                    <div class="info-value" id="view_policy_owner"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha Vigencia:</div>
                    <div class="info-value" id="view_effective_date"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Proxima Revision:</div>
                    <div class="info-value" id="view_next_review"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Alcance:</div>
                    <div class="info-value" id="view_policy_scope"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Objetivos:</div>
                    <div class="info-value" id="view_policy_objectives"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contenido:</div>
                    <div class="info-value" id="view_policy_content"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Controles Relacionados:</div>
                    <div class="info-value" id="view_related_controls"></div>
                </div>
            </div>
            <div style="margin-top: 30px; text-align: right;">
                <button class="btn btn-secondary" onclick="closeViewModal()">Cerrar</button>
                <button class="btn btn-primary" onclick="openEditFromView()">Editar Politica</button>
            </div>
        </div>
    </div>

    <!-- Modal de Edicion -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="editModalTitle">Editar Politica</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="current_version" id="edit_current_version">

                <div class="form-row">
                    <div class="form-group">
                        <label>Codigo *</label>
                        <input type="text" id="edit_policy_code" readonly style="background: #f0f0f0;">
                    </div>
                    <div class="form-group">
                        <label>Version (se incrementara automaticamente)</label>
                        <input type="text" id="edit_version" readonly style="background: #f0f0f0;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Nombre de la Politica *</label>
                    <input type="text" name="policy_name" id="edit_policy_name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Categoria *</label>
                        <select name="policy_category" id="edit_policy_category" required>
                            <option value="Politicas_Fundamentales">Politicas Fundamentales</option>
                            <option value="Politicas_Operacionales">Politicas Operacionales</option>
                            <option value="Politicas_Tecnicas">Politicas Tecnicas</option>
                            <option value="Politicas_Continuidad">Politicas de Continuidad</option>
                            <option value="Politicas_Cumplimiento">Politicas de Cumplimiento</option>
                            <option value="Politicas_RRHH">Politicas de RRHH</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="policy_type" id="edit_policy_type" required>
                            <option value="politica">Politica</option>
                            <option value="procedimiento">Procedimiento</option>
                            <option value="directriz">Directriz</option>
                            <option value="estandar">Estandar</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Estado de Aprobacion *</label>
                        <select name="approval_status" id="edit_approval_status" required>
                            <option value="Borrador">Borrador</option>
                            <option value="En Revisión">En Revision</option>
                            <option value="Aprobado">Aprobado</option>
                            <option value="Obsoleto">Obsoleto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Responsable *</label>
                        <input type="text" name="policy_owner" id="edit_policy_owner" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Vigencia *</label>
                        <input type="date" name="effective_date" id="edit_effective_date" required>
                    </div>
                    <div class="form-group">
                        <label>Proxima Revision *</label>
                        <input type="date" name="next_review" id="edit_next_review" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Revision</label>
                        <input type="date" name="review_date" id="edit_review_date">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Aprobacion</label>
                        <input type="date" name="approval_date" id="edit_approval_date">
                    </div>
                </div>

                <div class="form-group">
                    <label>Aprobado Por</label>
                    <input type="text" name="approved_by" id="edit_approved_by">
                </div>

                <div class="form-group">
                    <label>Alcance de la Politica</label>
                    <textarea name="policy_scope" id="edit_policy_scope"></textarea>
                </div>

                <div class="form-group">
                    <label>Objetivos</label>
                    <textarea name="policy_objectives" id="edit_policy_objectives"></textarea>
                </div>

                <div class="form-group">
                    <label>Contenido de la Politica</label>
                    <textarea name="policy_content" id="edit_policy_content" style="min-height: 200px;"></textarea>
                </div>

                <div class="form-group">
                    <label>Controles Relacionados</label>
                    <input type="text" name="related_controls" id="edit_related_controls" placeholder="Ej: A.5.1, A.5.2">
                </div>

                <div class="form-group">
                    <label>Procedimientos Relacionados</label>
                    <input type="text" name="related_procedures" id="edit_related_procedures">
                </div>

                <div class="form-group">
                    <label>Lista de Distribucion</label>
                    <input type="text" name="distribution_list" id="edit_distribution_list">
                </div>

                <div class="form-group">
                    <label>Historial de Revisiones</label>
                    <textarea name="revision_history" id="edit_revision_history"></textarea>
                </div>

                <div class="form-group">
                    <label>Requisitos de Cumplimiento</label>
                    <textarea name="compliance_requirements" id="edit_compliance_requirements"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Requiere Capacitacion</label>
                        <select name="training_required" id="edit_training_required">
                            <option value="no">No</option>
                            <option value="si">Si</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Requiere Reconocimiento</label>
                        <select name="acknowledgment_required" id="edit_acknowledgment_required">
                            <option value="no">No</option>
                            <option value="si">Si</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Creacion -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nueva Politica</h2>
                <span class="close" onclick="closeCreateModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">

                <div class="form-row">
                    <div class="form-group">
                        <label>Codigo *</label>
                        <input type="text" name="policy_id" required placeholder="POL-SI-001">
                    </div>
                    <div class="form-group">
                        <label>Version *</label>
                        <input type="text" name="version" value="1.0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nombre de la Politica *</label>
                    <input type="text" name="policy_name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Categoria *</label>
                        <select name="policy_category" required>
                            <option value="Politicas_Fundamentales">Politicas Fundamentales</option>
                            <option value="Politicas_Operacionales">Politicas Operacionales</option>
                            <option value="Politicas_Tecnicas">Politicas Tecnicas</option>
                            <option value="Politicas_Continuidad">Politicas de Continuidad</option>
                            <option value="Politicas_Cumplimiento">Politicas de Cumplimiento</option>
                            <option value="Politicas_RRHH">Politicas de RRHH</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="policy_type" required>
                            <option value="politica">Politica</option>
                            <option value="procedimiento">Procedimiento</option>
                            <option value="directriz">Directriz</option>
                            <option value="estandar">Estandar</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Estado de Aprobacion *</label>
                        <select name="approval_status" required>
                            <option value="Borrador">Borrador</option>
                            <option value="En Revisión">En Revision</option>
                            <option value="Aprobado">Aprobado</option>
                            <option value="Obsoleto">Obsoleto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Responsable *</label>
                        <input type="text" name="responsible" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Vigencia *</label>
                        <input type="date" name="effective_date" required>
                    </div>
                    <div class="form-group">
                        <label>Proxima Revision *</label>
                        <input type="date" name="next_review_date" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Revision</label>
                        <input type="date" name="review_date">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Aprobacion</label>
                        <input type="date" name="approval_date">
                    </div>
                </div>

                <div class="form-group">
                    <label>Aprobado Por</label>
                    <input type="text" name="approved_by">
                </div>

                <div class="form-group">
                    <label>Alcance de la Politica</label>
                    <textarea name="scope"></textarea>
                </div>

                <div class="form-group">
                    <label>Objetivos</label>
                    <textarea name="objective"></textarea>
                </div>

                <div class="form-group">
                    <label>Contenido de la Politica</label>
                    <textarea name="policy_description" style="min-height: 200px;"></textarea>
                </div>

                <div class="form-group">
                    <label>Controles Relacionados</label>
                    <input type="text" name="related_controls" placeholder="Ej: A.5.1, A.5.2">
                </div>

                <div class="form-group">
                    <label>Procedimientos Relacionados</label>
                    <input type="text" name="related_procedures">
                </div>

                <div class="form-group">
                    <label>Lista de Distribucion</label>
                    <input type="text" name="distribution_list">
                </div>

                <div class="form-group">
                    <label>Historial de Revisiones</label>
                    <textarea name="revision_history"></textarea>
                </div>

                <div class="form-group">
                    <label>Requisitos de Cumplimiento</label>
                    <textarea name="compliance_requirements"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Requiere Capacitacion</label>
                        <select name="training_required">
                            <option value="no">No</option>
                            <option value="si">Si</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Requiere Reconocimiento</label>
                        <select name="acknowledgment_required">
                            <option value="no">No</option>
                            <option value="si">Si</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Politica</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const policiesData = <?php echo json_encode($policies_array); ?>;
        let currentPolicyId = null;

        // Modal de VISTA
        function viewPolicy(id) {
            const policy = policiesData.find(p => p.id == id);
            if (!policy) return;

            currentPolicyId = id;

            document.getElementById('view_policy_code').textContent = policy.policy_id;
            document.getElementById('view_policy_name').textContent = policy.policy_name;
            document.getElementById('view_policy_category').textContent = policy.policy_category;
            document.getElementById('view_policy_type').textContent = policy.policy_type || 'N/A';
            document.getElementById('view_version').textContent = 'v' + policy.policy_version;
            document.getElementById('view_approval_status').innerHTML = '<span class="badge badge-approved">' + policy.approval_status + '</span>';
            document.getElementById('view_policy_owner').textContent = policy.approved_by || 'No asignado';
            document.getElementById('view_effective_date').textContent = policy.effective_date || 'No especificada';
            document.getElementById('view_next_review').textContent = policy.next_review || 'No programada';
            document.getElementById('view_policy_scope').textContent = policy.policy_scope || 'No especificado';
            document.getElementById('view_policy_objectives').textContent = policy.policy_objective || 'No especificados';
            document.getElementById('view_policy_content').textContent = policy.policy_content || 'Sin contenido';
            document.getElementById('view_related_controls').textContent = policy.related_controls || 'Ninguno';
            document.getElementById('viewModalTitle').textContent = 'Detalles: ' + policy.policy_id;

            document.getElementById('viewModal').style.display = 'block';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function openEditFromView() {
            closeViewModal();
            editPolicy(currentPolicyId);
        }

        // Modal de EDICION
        function editPolicy(id) {
            const policy = policiesData.find(p => p.id == id);
            if (!policy) return;

            currentPolicyId = id;

            document.getElementById('edit_id').value = policy.id;
            document.getElementById('edit_policy_code').value = policy.policy_id;
            document.getElementById('edit_current_version').value = policy.policy_version;
            document.getElementById('edit_version').value = 'v' + policy.policy_version + ' → v' + (parseFloat(policy.policy_version) + 0.1).toFixed(1);
            document.getElementById('edit_policy_name').value = policy.policy_name;
            document.getElementById('edit_policy_category').value = policy.policy_category;
            document.getElementById('edit_policy_type').value = policy.policy_type || 'politica';
            document.getElementById('edit_approval_status').value = policy.approval_status;
            document.getElementById('edit_policy_owner').value = policy.approved_by || '';
            document.getElementById('edit_effective_date').value = policy.effective_date || '';
            document.getElementById('edit_next_review').value = policy.next_review || '';
            document.getElementById('edit_review_date').value = policy.last_review || '';
            document.getElementById('edit_approval_date').value = policy.approval_date || '';
            document.getElementById('edit_approved_by').value = policy.approved_by || '';
            document.getElementById('edit_policy_scope').value = policy.policy_scope || '';
            document.getElementById('edit_policy_objectives').value = policy.policy_objective || '';
            document.getElementById('edit_policy_content').value = policy.policy_content || '';
            document.getElementById('edit_related_controls').value = policy.related_controls || '';
            document.getElementById('edit_related_procedures').value = policy.related_procedures || '';
            document.getElementById('edit_distribution_list').value = policy.distribution_list || '';
            document.getElementById('edit_revision_history').value = policy.revision_history || '';
            document.getElementById('edit_compliance_requirements').value = policy.compliance_requirements || '';
            document.getElementById('edit_training_required').value = policy.training_required || 'no';
            document.getElementById('edit_acknowledgment_required').value = policy.acknowledgment_required || 'no';
            document.getElementById('editModalTitle').textContent = 'Editar: ' + policy.policy_id;

            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Modal de CREACION
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewModal');
            const editModal = document.getElementById('editModal');
            const createModal = document.getElementById('createModal');
            if (event.target == viewModal) {
                closeViewModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == createModal) {
                closeCreateModal();
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
