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
            $stmt = $conn->prepare("INSERT INTO iso27001_incidents (company_id, incident_id, incident_type, incident_category, severity, priority, reported_by, reported_date, detected_date, description, affected_assets, affected_systems, affected_data, impact_confidentiality, impact_integrity, impact_availability, business_impact, estimated_cost, root_cause, immediate_actions, containment_actions, eradication_actions, recovery_actions, preventive_actions, assigned_to, status, resolution_date, lessons_learned, evidence_collected, external_notification, regulatory_notification, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("isssssssssssssssssssssssssssssi",
                $company_id,
                $_POST['incident_id'],
                $_POST['incident_type'],
                $_POST['incident_category'],
                $_POST['severity'],
                $_POST['priority'],
                $_POST['reported_by'],
                $_POST['reported_date'],
                $_POST['detected_date'],
                $_POST['description'],
                $_POST['affected_assets'],
                $_POST['affected_systems'],
                $_POST['affected_data'],
                $_POST['impact_confidentiality'],
                $_POST['impact_integrity'],
                $_POST['impact_availability'],
                $_POST['business_impact'],
                $_POST['estimated_cost'],
                $_POST['root_cause'],
                $_POST['immediate_actions'],
                $_POST['containment_actions'],
                $_POST['eradication_actions'],
                $_POST['recovery_actions'],
                $_POST['preventive_actions'],
                $_POST['assigned_to'],
                $_POST['status'],
                $_POST['resolution_date'],
                $_POST['lessons_learned'],
                $_POST['evidence_collected'],
                $_POST['external_notification'],
                $_POST['regulatory_notification'],
                $user_id
            );

            if ($stmt->execute()) {
                $message = 'Incidente registrado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al registrar incidente: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $conn->prepare("UPDATE iso27001_incidents SET incident_type = ?, incident_category = ?, severity = ?, priority = ?, description = ?, affected_assets = ?, affected_systems = ?, affected_data = ?, impact_confidentiality = ?, impact_integrity = ?, impact_availability = ?, business_impact = ?, estimated_cost = ?, root_cause = ?, immediate_actions = ?, containment_actions = ?, eradication_actions = ?, recovery_actions = ?, preventive_actions = ?, assigned_to = ?, status = ?, resolution_date = ?, lessons_learned = ?, evidence_collected = ?, external_notification = ?, regulatory_notification = ?, updated_by = ?, updated_date = NOW() WHERE id = ? AND company_id = ?");

            $stmt->bind_param("sssssssssssssssssssssssssiii",
                $_POST['incident_type'],
                $_POST['incident_category'],
                $_POST['severity'],
                $_POST['priority'],
                $_POST['description'],
                $_POST['affected_assets'],
                $_POST['affected_systems'],
                $_POST['affected_data'],
                $_POST['impact_confidentiality'],
                $_POST['impact_integrity'],
                $_POST['impact_availability'],
                $_POST['business_impact'],
                $_POST['estimated_cost'],
                $_POST['root_cause'],
                $_POST['immediate_actions'],
                $_POST['containment_actions'],
                $_POST['eradication_actions'],
                $_POST['recovery_actions'],
                $_POST['preventive_actions'],
                $_POST['assigned_to'],
                $_POST['status'],
                $_POST['resolution_date'],
                $_POST['lessons_learned'],
                $_POST['evidence_collected'],
                $_POST['external_notification'],
                $_POST['regulatory_notification'],
                $user_id,
                $_POST['id'],
                $company_id
            );

            if ($stmt->execute()) {
                $message = 'Incidente actualizado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar incidente: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM iso27001_incidents WHERE id = ? AND company_id = ?");
            $stmt->bind_param("ii", $_POST['id'], $company_id);

            if ($stmt->execute()) {
                $message = 'Incidente eliminado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar incidente: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Obtener filtros
$filter_severity = isset($_GET['filter_severity']) ? $_GET['filter_severity'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir query con filtros
$query = "SELECT * FROM iso27001_incidents WHERE company_id = ?";
$params = [$company_id];
$types = "i";

if ($filter_severity) {
    $query .= " AND severity = ?";
    $params[] = $filter_severity;
    $types .= "s";
}

if ($filter_status) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_type) {
    $query .= " AND incident_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($search) {
    $query .= " AND (incident_id LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY reported_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$incidents_result = $stmt->get_result();
$stmt->close();

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_incidents WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_incidents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_incidents WHERE company_id = ? AND status IN ('abierto', 'en_investigacion', 'contenido')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$open_incidents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_incidents WHERE company_id = ? AND severity IN ('Critica', 'Alta')");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$critical_incidents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Calcular tiempo promedio de resolucion (ultimos 30 dias)
$stmt = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, reported_date, resolution_date)) as avg_resolution FROM iso27001_incidents WHERE company_id = ? AND resolution_date IS NOT NULL AND reported_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$avg_resolution = $stmt->get_result()->fetch_assoc()['avg_resolution'];
$stmt->close();
$avg_resolution = round($avg_resolution, 1);

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'Gestion de Incidentes de Seguridad',
        'subtitle' => 'ISO 27001 - Registro y Seguimiento de Incidentes',
        'total_incidents' => 'Total de Incidentes',
        'open_incidents' => 'Incidentes Abiertos',
        'critical_incidents' => 'Incidentes Criticos',
        'avg_resolution' => 'Tiempo Promedio Resolucion (hrs)',
        'add_incident' => 'Registrar Incidente',
        'filter' => 'Filtrar',
        'search' => 'Buscar',
        'severity' => 'Severidad',
        'status' => 'Estado',
        'type' => 'Tipo',
        'all' => 'Todos',
        'incident_id' => 'ID Incidente',
        'incident_type' => 'Tipo de Incidente',
        'incident_category' => 'Categoria',
        'reported_by' => 'Reportado Por',
        'reported_date' => 'Fecha Reporte',
        'detected_date' => 'Fecha Deteccion',
        'description' => 'Descripcion',
        'affected_assets' => 'Activos Afectados',
        'affected_systems' => 'Sistemas Afectados',
        'affected_data' => 'Datos Afectados',
        'impact' => 'Impacto',
        'business_impact' => 'Impacto en el Negocio',
        'estimated_cost' => 'Costo Estimado',
        'root_cause' => 'Causa Raiz',
        'actions' => 'Acciones',
        'immediate_actions' => 'Acciones Inmediatas',
        'containment_actions' => 'Acciones de Contencion',
        'eradication_actions' => 'Acciones de Erradicacion',
        'recovery_actions' => 'Acciones de Recuperacion',
        'preventive_actions' => 'Acciones Preventivas',
        'assigned_to' => 'Asignado a',
        'priority' => 'Prioridad',
        'resolution_date' => 'Fecha Resolucion',
        'lessons_learned' => 'Lecciones Aprendidas',
        'evidence_collected' => 'Evidencias Recolectadas',
        'external_notification' => 'Notificacion Externa',
        'regulatory_notification' => 'Notificacion Regulatoria',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'view' => 'Ver',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'back' => 'Volver',
        'print' => 'Imprimir',
        'export_excel' => 'Exportar Excel',
        'export_pdf' => 'Exportar PDF'
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
        .header-title h1 { font-size: 28px; color: #cc3333; margin-bottom: 5px; }
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
            border-left: 4px solid #cc3333;
        }
        .stat-value { font-size: 32px; font-weight: bold; color: #333; }
        .stat-label { color: #666; font-size: 13px; margin-top: 5px; }
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
        .form-group { display: flex; flex-direction: column; }
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
        .form-group textarea { resize: vertical; min-height: 80px; }
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .message-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
        .badge-critical { background: #cc0000; color: white; }
        .badge-high { background: #ff6600; color: white; }
        .badge-medium { background: #ffcc00; color: #333; }
        .badge-low { background: #00994d; color: white; }
        .badge-open { background: #cc0000; color: white; }
        .badge-investigating { background: #ff6600; color: white; }
        .badge-contained { background: #ffcc00; color: #333; }
        .badge-resolved { background: #00994d; color: white; }
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
        .modal-header h2 { color: #333; font-size: 24px; }
        .close-modal {
            font-size: 32px;
            cursor: pointer;
            color: #999;
            border: none;
            background: none;
        }
        .close-modal:hover { color: #333; }
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
            body { background: white; }
            .action-buttons, .btn, .filters-section, .modal { display: none !important; }
        }
        @media (max-width: 768px) {
            .header-top { flex-direction: column; text-align: center; }
            .action-buttons { justify-content: center; margin-top: 15px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>&#128293; <?php echo $t['title']; ?></h1>
                    <p><?php echo $t['subtitle']; ?></p>
                </div>
                <div class="action-buttons">
                    <button onclick="openModal('addModal')" class="btn btn-danger">+ <?php echo $t['add_incident']; ?></button>
                    <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                    <a href="export.php?format=excel&type=incidents" class="btn btn-success">&#128202; <?php echo $t['export_excel']; ?></a>
                    <a href="export.php?format=pdf&type=incidents" class="btn btn-danger">&#128196; <?php echo $t['export_pdf']; ?></a>
                    <a href="index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_incidents; ?></div>
                    <div class="stat-label"><?php echo $t['total_incidents']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $open_incidents; ?></div>
                    <div class="stat-label"><?php echo $t['open_incidents']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $critical_incidents; ?></div>
                    <div class="stat-label"><?php echo $t['critical_incidents']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $avg_resolution ? $avg_resolution : 'N/A'; ?></div>
                    <div class="stat-label"><?php echo $t['avg_resolution']; ?></div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message message-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="filters-section">
            <form method="GET">
                <div class="filters-row">
                    <div class="form-group">
                        <label><?php echo $t['search']; ?></label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $t['search']; ?>...">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['severity']; ?></label>
                        <select name="filter_severity">
                            <option value=""><?php echo $t['all']; ?></option>
                            <option value="Critica" <?php echo $filter_severity == 'Critica' ? 'selected' : ''; ?>>Critica</option>
                            <option value="Alta" <?php echo $filter_severity == 'Alta' ? 'selected' : ''; ?>>Alta</option>
                            <option value="Media" <?php echo $filter_severity == 'Media' ? 'selected' : ''; ?>>Media</option>
                            <option value="Baja" <?php echo $filter_severity == 'Baja' ? 'selected' : ''; ?>>Baja</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['status']; ?></label>
                        <select name="filter_status">
                            <option value=""><?php echo $t['all']; ?></option>
                            <option value="abierto" <?php echo $filter_status == 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                            <option value="en_investigacion" <?php echo $filter_status == 'en_investigacion' ? 'selected' : ''; ?>>En Investigacion</option>
                            <option value="contenido" <?php echo $filter_status == 'contenido' ? 'selected' : ''; ?>>Contenido</option>
                            <option value="erradicado" <?php echo $filter_status == 'erradicado' ? 'selected' : ''; ?>>Erradicado</option>
                            <option value="resuelto" <?php echo $filter_status == 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                            <option value="cerrado" <?php echo $filter_status == 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
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
                            <th><?php echo $t['incident_id']; ?></th>
                            <th><?php echo $t['incident_type']; ?></th>
                            <th><?php echo $t['severity']; ?></th>
                            <th><?php echo $t['priority']; ?></th>
                            <th><?php echo $t['reported_by']; ?></th>
                            <th><?php echo $t['reported_date']; ?></th>
                            <th><?php echo $t['status']; ?></th>
                            <th><?php echo $t['assigned_to']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($incident = $incidents_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($incident['incident_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($incident['incident_type']); ?></td>
                            <td>
                                <?php
                                $severity = $incident['severity'];
                                $badge_class = 'badge-medium';
                                if ($severity == 'Critica') $badge_class = 'badge-critical';
                                elseif ($severity == 'Alta') $badge_class = 'badge-high';
                                elseif ($severity == 'Baja') $badge_class = 'badge-low';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $severity; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($incident['priority']); ?></td>
                            <td><?php echo htmlspecialchars($incident['reported_by']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($incident['reported_date'])); ?></td>
                            <td>
                                <?php
                                $status = $incident['status'];
                                $status_badge = 'badge-open';
                                if ($status == 'resuelto' || $status == 'cerrado') $status_badge = 'badge-resolved';
                                elseif ($status == 'contenido') $status_badge = 'badge-contained';
                                elseif ($status == 'en_investigacion') $status_badge = 'badge-investigating';
                                ?>
                                <span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($incident['assigned_to']); ?></td>
                            <td>
                                <button class="btn btn-primary btn-small"><?php echo $t['edit']; ?></button>
                                <button onclick='confirmDelete(<?php echo $incident['id']; ?>)' class="btn btn-danger btn-small"><?php echo $t['delete']; ?></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Incidente -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $t['add_incident']; ?></h2>
                <button class="close-modal" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-section-title">Informacion Basica</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['incident_id']; ?> *</label>
                        <input type="text" name="incident_id" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['incident_type']; ?> *</label>
                        <select name="incident_type" required>
                            <option value="Acceso No Autorizado">Acceso No Autorizado</option>
                            <option value="Malware">Malware</option>
                            <option value="Phishing">Phishing</option>
                            <option value="Fuga de Datos">Fuga de Datos</option>
                            <option value="Denegacion de Servicio">Denegacion de Servicio</option>
                            <option value="Perdida de Dispositivo">Perdida de Dispositivo</option>
                            <option value="Violacion de Politica">Violacion de Politica</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['incident_category']; ?></label>
                        <input type="text" name="incident_category">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['severity']; ?> *</label>
                        <select name="severity" required>
                            <option value="Baja">Baja</option>
                            <option value="Media">Media</option>
                            <option value="Alta">Alta</option>
                            <option value="Critica">Critica</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['priority']; ?> *</label>
                        <select name="priority" required>
                            <option value="Baja">Baja</option>
                            <option value="Media">Media</option>
                            <option value="Alta">Alta</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['reported_by']; ?> *</label>
                        <input type="text" name="reported_by" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['reported_date']; ?> *</label>
                        <input type="datetime-local" name="reported_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['detected_date']; ?></label>
                        <input type="datetime-local" name="detected_date">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['assigned_to']; ?></label>
                        <input type="text" name="assigned_to">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['status']; ?> *</label>
                        <select name="status" required>
                            <option value="abierto">Abierto</option>
                            <option value="en_investigacion">En Investigacion</option>
                            <option value="contenido">Contenido</option>
                            <option value="erradicado">Erradicado</option>
                            <option value="resuelto">Resuelto</option>
                            <option value="cerrado">Cerrado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['resolution_date']; ?></label>
                        <input type="datetime-local" name="resolution_date">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['estimated_cost']; ?></label>
                        <input type="number" step="0.01" name="estimated_cost">
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $t['description']; ?> *</label>
                    <textarea name="description" required></textarea>
                </div>

                <div class="form-section-title">Activos y Sistemas Afectados</div>
                <div class="form-group">
                    <label><?php echo $t['affected_assets']; ?></label>
                    <textarea name="affected_assets"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['affected_systems']; ?></label>
                    <textarea name="affected_systems"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['affected_data']; ?></label>
                    <textarea name="affected_data"></textarea>
                </div>

                <div class="form-section-title">Impacto</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Impacto en Confidencialidad</label>
                        <select name="impact_confidentiality">
                            <option value="Ninguno">Ninguno</option>
                            <option value="Bajo">Bajo</option>
                            <option value="Medio">Medio</option>
                            <option value="Alto">Alto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Impacto en Integridad</label>
                        <select name="impact_integrity">
                            <option value="Ninguno">Ninguno</option>
                            <option value="Bajo">Bajo</option>
                            <option value="Medio">Medio</option>
                            <option value="Alto">Alto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Impacto en Disponibilidad</label>
                        <select name="impact_availability">
                            <option value="Ninguno">Ninguno</option>
                            <option value="Bajo">Bajo</option>
                            <option value="Medio">Medio</option>
                            <option value="Alto">Alto</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $t['business_impact']; ?></label>
                    <textarea name="business_impact"></textarea>
                </div>

                <div class="form-section-title">Analisis y Acciones</div>
                <div class="form-group">
                    <label><?php echo $t['root_cause']; ?></label>
                    <textarea name="root_cause"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['immediate_actions']; ?></label>
                    <textarea name="immediate_actions"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['containment_actions']; ?></label>
                    <textarea name="containment_actions"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['eradication_actions']; ?></label>
                    <textarea name="eradication_actions"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['recovery_actions']; ?></label>
                    <textarea name="recovery_actions"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['preventive_actions']; ?></label>
                    <textarea name="preventive_actions"></textarea>
                </div>

                <div class="form-section-title">Documentacion</div>
                <div class="form-group">
                    <label><?php echo $t['lessons_learned']; ?></label>
                    <textarea name="lessons_learned"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['evidence_collected']; ?></label>
                    <textarea name="evidence_collected"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['external_notification']; ?></label>
                        <select name="external_notification">
                            <option value="No">No</option>
                            <option value="Si">Si</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['regulatory_notification']; ?></label>
                        <select name="regulatory_notification">
                            <option value="No">No</option>
                            <option value="Si">Si</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo $t['save']; ?></button>
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
            if (confirm('Esta seguro de que desea eliminar este incidente?')) {
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
