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
            $stmt = $conn->prepare("INSERT INTO iso27001_policies (company_id, policy_code, policy_name, policy_category, policy_type, version, effective_date, review_date, next_review, approval_date, approved_by, policy_owner, policy_scope, policy_objectives, policy_content, related_controls, related_procedures, distribution_list, approval_status, revision_history, compliance_requirements, training_required, acknowledgment_required, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("isssssssssssssssssssssi",
                $company_id,
                $_POST['policy_code'],
                $_POST['policy_name'],
                $_POST['policy_category'],
                $_POST['policy_type'],
                $_POST['version'],
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

            $stmt = $conn->prepare("UPDATE iso27001_policies SET policy_name = ?, policy_category = ?, policy_type = ?, version = ?, effective_date = ?, review_date = ?, next_review = ?, approval_date = ?, approved_by = ?, policy_owner = ?, policy_scope = ?, policy_objectives = ?, policy_content = ?, related_controls = ?, related_procedures = ?, distribution_list = ?, approval_status = ?, revision_history = ?, compliance_requirements = ?, training_required = ?, acknowledgment_required = ?, updated_by = ?, updated_date = NOW() WHERE id = ? AND company_id = ?");

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
    $query .= " AND (policy_code LIKE ? OR policy_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY policy_category, policy_code ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$policies_result = $stmt->get_result();
$stmt->close();

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_policies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ? AND approval_status = 'aprobado'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$approved_policies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_policies WHERE company_id = ? AND approval_status IN ('borrador', 'en_revision')");
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
        @media (max-width: 768px) {
            .header-top { flex-direction: column; text-align: center; }
            .action-buttons { justify-content: center; margin-top: 15px; }
            .templates-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>&#128220; <?php echo $t['title']; ?></h1>
                    <p><?php echo $t['subtitle']; ?></p>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-success">+ <?php echo $t['add_policy']; ?></button>
                    <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                    <a href="export.php?format=excel&type=policies" class="btn btn-success">&#128202; <?php echo $t['export_excel']; ?></a>
                    <a href="export.php?format=pdf&type=policies" class="btn btn-danger">&#128196; <?php echo $t['export_pdf']; ?></a>
                    <a href="index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
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
        <div style="background: <?php echo $message_type == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type == 'success' ? '#155724' : '#721c24'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;">
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
                            &#128196; Descargar Todas (<?php echo count($category['policies']); ?>)
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
                        <?php while ($policy = $policies_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($policy['policy_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($policy['policy_name']); ?></td>
                            <td>v<?php echo $policy['version']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($policy['effective_date'])); ?></td>
                            <td>
                                <?php
                                $status = $policy['approval_status'];
                                $badge_class = 'badge-draft';
                                if ($status == 'aprobado') $badge_class = 'badge-approved';
                                elseif ($status == 'en_revision') $badge_class = 'badge-review';
                                elseif ($status == 'obsoleto') $badge_class = 'badge-obsolete';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($policy['policy_owner']); ?></td>
                            <td>
                                <?php
                                $next_review = strtotime($policy['next_review']);
                                $today = time();
                                $color = $next_review < $today ? 'color: #cc0000; font-weight: bold;' : '';
                                ?>
                                <span style="<?php echo $color; ?>"><?php echo date('d/m/Y', $next_review); ?></span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-small"><?php echo $t['view']; ?></button>
                                <button class="btn btn-success btn-small"><?php echo $t['download']; ?></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
