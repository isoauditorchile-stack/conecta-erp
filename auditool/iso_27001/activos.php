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

// Procesar acciones (crear, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $stmt = $conn->prepare("INSERT INTO iso27001_assets (company_id, asset_id, asset_name, asset_type, asset_category, asset_owner, asset_custodian, asset_location, asset_description, confidentiality, integrity, availability, criticality, acquisition_date, last_review, next_review, estimated_value, replacement_cost, recovery_time, legal_requirements, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->bind_param("isssssssssssssssddssi",
                $company_id,
                $_POST['asset_id'],
                $_POST['asset_name'],
                $_POST['asset_type'],
                $_POST['asset_category'],
                $_POST['asset_owner'],
                $_POST['asset_custodian'],
                $_POST['asset_location'],
                $_POST['asset_description'],
                $_POST['confidentiality'],
                $_POST['integrity'],
                $_POST['availability'],
                $_POST['criticality'],
                $_POST['acquisition_date'],
                $_POST['last_review'],
                $_POST['next_review'],
                $_POST['estimated_value'],
                $_POST['replacement_cost'],
                $_POST['recovery_time'],
                $_POST['legal_requirements'],
                $user_id
            );

            if ($stmt->execute()) {
                $message = 'Activo creado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al crear activo: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $conn->prepare("UPDATE iso27001_assets SET asset_name = ?, asset_type = ?, asset_category = ?, asset_owner = ?, asset_custodian = ?, asset_location = ?, asset_description = ?, confidentiality = ?, integrity = ?, availability = ?, criticality = ?, acquisition_date = ?, last_review = ?, next_review = ?, estimated_value = ?, replacement_cost = ?, recovery_time = ?, legal_requirements = ?, updated_by = ?, updated_date = NOW() WHERE id = ? AND company_id = ?");

            $stmt->bind_param("ssssssssssssssddsii",
                $_POST['asset_name'],
                $_POST['asset_type'],
                $_POST['asset_category'],
                $_POST['asset_owner'],
                $_POST['asset_custodian'],
                $_POST['asset_location'],
                $_POST['asset_description'],
                $_POST['confidentiality'],
                $_POST['integrity'],
                $_POST['availability'],
                $_POST['criticality'],
                $_POST['acquisition_date'],
                $_POST['last_review'],
                $_POST['next_review'],
                $_POST['estimated_value'],
                $_POST['replacement_cost'],
                $_POST['recovery_time'],
                $_POST['legal_requirements'],
                $user_id,
                $_POST['id'],
                $company_id
            );

            if ($stmt->execute()) {
                $message = 'Activo actualizado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar activo: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $conn->prepare("DELETE FROM iso27001_assets WHERE id = ? AND company_id = ?");
            $stmt->bind_param("ii", $_POST['id'], $company_id);

            if ($stmt->execute()) {
                $message = 'Activo eliminado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar activo: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Obtener filtros
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_criticality = isset($_GET['filter_criticality']) ? $_GET['filter_criticality'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir query con filtros
$query = "SELECT * FROM iso27001_assets WHERE company_id = ?";
$params = [$company_id];
$types = "i";

if ($filter_type) {
    $query .= " AND asset_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($filter_criticality) {
    $query .= " AND criticality = ?";
    $params[] = $filter_criticality;
    $types .= "s";
}

if ($search) {
    $query .= " AND (asset_name LIKE ? OR asset_id LIKE ? OR asset_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY criticality DESC, asset_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$assets_result = $stmt->get_result();
$stmt->close();

// Obtener estadisticas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_assets WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$total_assets = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM iso27001_assets WHERE company_id = ? AND criticality = 'Critica'");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$critical_assets = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Textos multiidioma
$texts = [
    'es' => [
        'title' => 'Gestion de Activos de Informacion',
        'subtitle' => 'ISO 27001 - Inventario y Clasificacion de Activos',
        'total_assets' => 'Total de Activos',
        'critical_assets' => 'Activos Criticos',
        'add_asset' => 'Agregar Activo',
        'search' => 'Buscar',
        'filter' => 'Filtrar',
        'type' => 'Tipo',
        'criticality' => 'Criticidad',
        'all' => 'Todos',
        'asset_id' => 'ID Activo',
        'asset_name' => 'Nombre del Activo',
        'asset_type' => 'Tipo de Activo',
        'category' => 'Categoria',
        'owner' => 'Propietario',
        'custodian' => 'Custodio',
        'location' => 'Ubicacion',
        'description' => 'Descripcion',
        'confidentiality' => 'Confidencialidad',
        'integrity' => 'Integridad',
        'availability' => 'Disponibilidad',
        'acquisition_date' => 'Fecha Adquisicion',
        'last_review' => 'Ultima Revision',
        'next_review' => 'Proxima Revision',
        'value' => 'Valor Estimado',
        'replacement_cost' => 'Costo Reemplazo',
        'recovery_time' => 'Tiempo Recuperacion (hrs)',
        'legal_requirements' => 'Requisitos Legales',
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
        'upload_file' => 'Cargar Archivo',
        'information' => 'Informacion',
        'hardware' => 'Hardware',
        'software' => 'Software',
        'services' => 'Servicios',
        'personnel' => 'Personal',
        'facilities' => 'Instalaciones',
        'documents' => 'Documentos',
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'critical' => 'Critica'
    ]
];

$t = $texts[$language];

// Obtener tipos unicos para el filtro
$stmt = $conn->prepare("SELECT DISTINCT asset_type FROM iso27001_assets WHERE company_id = ? ORDER BY asset_type");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$types_result = $stmt->get_result();
$stmt->close();
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
            max-width: 900px;
            margin: 50px auto;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>&#128190; <?php echo $t['title']; ?></h1>
                    <p><?php echo $t['subtitle']; ?></p>
                </div>
                <div class="action-buttons">
                    <button onclick="openModal('addModal')" class="btn btn-success">+ <?php echo $t['add_asset']; ?></button>
                    <button onclick="window.print()" class="btn btn-primary">&#128424; <?php echo $t['print']; ?></button>
                    <a href="export.php?format=excel&type=assets" class="btn btn-success">&#128202; <?php echo $t['export_excel']; ?></a>
                    <a href="export.php?format=pdf&type=assets" class="btn btn-danger">&#128196; <?php echo $t['export_pdf']; ?></a>
                    <a href="templates/plantilla_activos.xlsx" download class="btn btn-warning">&#128229; <?php echo $t['download_template']; ?></a>
                    <a href="index.php" class="btn btn-secondary">&#8592; <?php echo $t['back']; ?></a>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_assets; ?></div>
                    <div class="stat-label"><?php echo $t['total_assets']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $critical_assets; ?></div>
                    <div class="stat-label"><?php echo $t['critical_assets']; ?></div>
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
                        <label><?php echo $t['type']; ?></label>
                        <select name="filter_type">
                            <option value=""><?php echo $t['all']; ?></option>
                            <?php while ($type_row = $types_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($type_row['asset_type']); ?>" <?php echo $filter_type == $type_row['asset_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type_row['asset_type']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['criticality']; ?></label>
                        <select name="filter_criticality">
                            <option value=""><?php echo $t['all']; ?></option>
                            <option value="Critica" <?php echo $filter_criticality == 'Critica' ? 'selected' : ''; ?>><?php echo $t['critical']; ?></option>
                            <option value="Alta" <?php echo $filter_criticality == 'Alta' ? 'selected' : ''; ?>><?php echo $t['high']; ?></option>
                            <option value="Media" <?php echo $filter_criticality == 'Media' ? 'selected' : ''; ?>><?php echo $t['medium']; ?></option>
                            <option value="Baja" <?php echo $filter_criticality == 'Baja' ? 'selected' : ''; ?>><?php echo $t['low']; ?></option>
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
                            <th><?php echo $t['asset_id']; ?></th>
                            <th><?php echo $t['asset_name']; ?></th>
                            <th><?php echo $t['type']; ?></th>
                            <th><?php echo $t['category']; ?></th>
                            <th><?php echo $t['owner']; ?></th>
                            <th><?php echo $t['criticality']; ?></th>
                            <th><?php echo $t['confidentiality']; ?></th>
                            <th><?php echo $t['integrity']; ?></th>
                            <th><?php echo $t['availability']; ?></th>
                            <th><?php echo $t['last_review']; ?></th>
                            <th><?php echo $t['actions']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($asset = $assets_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($asset['asset_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                            <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                            <td><?php echo htmlspecialchars($asset['asset_category']); ?></td>
                            <td><?php echo htmlspecialchars($asset['asset_owner']); ?></td>
                            <td>
                                <?php
                                $criticality = $asset['criticality'];
                                $badge_class = 'badge-medium';
                                if ($criticality == 'Critica') $badge_class = 'badge-critical';
                                elseif ($criticality == 'Alta') $badge_class = 'badge-high';
                                elseif ($criticality == 'Baja') $badge_class = 'badge-low';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $criticality; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($asset['confidentiality']); ?></td>
                            <td><?php echo htmlspecialchars($asset['integrity']); ?></td>
                            <td><?php echo htmlspecialchars($asset['availability']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($asset['last_review'])); ?></td>
                            <td>
                                <button onclick='openEditModal(<?php echo json_encode($asset); ?>)' class="btn btn-primary btn-small"><?php echo $t['edit']; ?></button>
                                <button onclick='confirmDelete(<?php echo $asset['id']; ?>)' class="btn btn-danger btn-small"><?php echo $t['delete']; ?></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Agregar -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $t['add_asset']; ?></h2>
                <button class="close-modal" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['asset_id']; ?> *</label>
                        <input type="text" name="asset_id" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['asset_name']; ?> *</label>
                        <input type="text" name="asset_name" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['asset_type']; ?> *</label>
                        <select name="asset_type" required>
                            <option value="Informacion"><?php echo $t['information']; ?></option>
                            <option value="Hardware"><?php echo $t['hardware']; ?></option>
                            <option value="Software"><?php echo $t['software']; ?></option>
                            <option value="Servicios"><?php echo $t['services']; ?></option>
                            <option value="Personal"><?php echo $t['personnel']; ?></option>
                            <option value="Instalaciones"><?php echo $t['facilities']; ?></option>
                            <option value="Documentos"><?php echo $t['documents']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['category']; ?> *</label>
                        <input type="text" name="asset_category" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['owner']; ?> *</label>
                        <input type="text" name="asset_owner" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['custodian']; ?></label>
                        <input type="text" name="asset_custodian">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['location']; ?></label>
                        <input type="text" name="asset_location">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['confidentiality']; ?> *</label>
                        <select name="confidentiality" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['integrity']; ?> *</label>
                        <select name="integrity" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['availability']; ?> *</label>
                        <select name="availability" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['criticality']; ?> *</label>
                        <select name="criticality" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['acquisition_date']; ?></label>
                        <input type="date" name="acquisition_date">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['last_review']; ?></label>
                        <input type="date" name="last_review" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['next_review']; ?></label>
                        <input type="date" name="next_review">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['value']; ?></label>
                        <input type="number" step="0.01" name="estimated_value">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['replacement_cost']; ?></label>
                        <input type="number" step="0.01" name="replacement_cost">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['recovery_time']; ?></label>
                        <input type="number" step="0.5" name="recovery_time">
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $t['description']; ?></label>
                    <textarea name="asset_description"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['legal_requirements']; ?></label>
                    <textarea name="legal_requirements"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn btn-success"><?php echo $t['save']; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $t['edit']; ?></h2>
                <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label><?php echo $t['asset_id']; ?> *</label>
                        <input type="text" id="edit_asset_id" readonly>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['asset_name']; ?> *</label>
                        <input type="text" name="asset_name" id="edit_asset_name" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['asset_type']; ?> *</label>
                        <select name="asset_type" id="edit_asset_type" required>
                            <option value="Informacion"><?php echo $t['information']; ?></option>
                            <option value="Hardware"><?php echo $t['hardware']; ?></option>
                            <option value="Software"><?php echo $t['software']; ?></option>
                            <option value="Servicios"><?php echo $t['services']; ?></option>
                            <option value="Personal"><?php echo $t['personnel']; ?></option>
                            <option value="Instalaciones"><?php echo $t['facilities']; ?></option>
                            <option value="Documentos"><?php echo $t['documents']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['category']; ?> *</label>
                        <input type="text" name="asset_category" id="edit_asset_category" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['owner']; ?> *</label>
                        <input type="text" name="asset_owner" id="edit_asset_owner" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['custodian']; ?></label>
                        <input type="text" name="asset_custodian" id="edit_asset_custodian">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['location']; ?></label>
                        <input type="text" name="asset_location" id="edit_asset_location">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['confidentiality']; ?> *</label>
                        <select name="confidentiality" id="edit_confidentiality" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['integrity']; ?> *</label>
                        <select name="integrity" id="edit_integrity" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['availability']; ?> *</label>
                        <select name="availability" id="edit_availability" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['criticality']; ?> *</label>
                        <select name="criticality" id="edit_criticality" required>
                            <option value="Baja"><?php echo $t['low']; ?></option>
                            <option value="Media"><?php echo $t['medium']; ?></option>
                            <option value="Alta"><?php echo $t['high']; ?></option>
                            <option value="Critica"><?php echo $t['critical']; ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['acquisition_date']; ?></label>
                        <input type="date" name="acquisition_date" id="edit_acquisition_date">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['last_review']; ?></label>
                        <input type="date" name="last_review" id="edit_last_review">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['next_review']; ?></label>
                        <input type="date" name="next_review" id="edit_next_review">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['value']; ?></label>
                        <input type="number" step="0.01" name="estimated_value" id="edit_estimated_value">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['replacement_cost']; ?></label>
                        <input type="number" step="0.01" name="replacement_cost" id="edit_replacement_cost">
                    </div>
                    <div class="form-group">
                        <label><?php echo $t['recovery_time']; ?></label>
                        <input type="number" step="0.5" name="recovery_time" id="edit_recovery_time">
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $t['description']; ?></label>
                    <textarea name="asset_description" id="edit_asset_description"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $t['legal_requirements']; ?></label>
                    <textarea name="legal_requirements" id="edit_legal_requirements"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary"><?php echo $t['cancel']; ?></button>
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

        function openEditModal(asset) {
            document.getElementById('edit_id').value = asset.id;
            document.getElementById('edit_asset_id').value = asset.asset_id;
            document.getElementById('edit_asset_name').value = asset.asset_name;
            document.getElementById('edit_asset_type').value = asset.asset_type;
            document.getElementById('edit_asset_category').value = asset.asset_category;
            document.getElementById('edit_asset_owner').value = asset.asset_owner;
            document.getElementById('edit_asset_custodian').value = asset.asset_custodian || '';
            document.getElementById('edit_asset_location').value = asset.asset_location || '';
            document.getElementById('edit_confidentiality').value = asset.confidentiality;
            document.getElementById('edit_integrity').value = asset.integrity;
            document.getElementById('edit_availability').value = asset.availability;
            document.getElementById('edit_criticality').value = asset.criticality;
            document.getElementById('edit_acquisition_date').value = asset.acquisition_date || '';
            document.getElementById('edit_last_review').value = asset.last_review || '';
            document.getElementById('edit_next_review').value = asset.next_review || '';
            document.getElementById('edit_estimated_value').value = asset.estimated_value || '';
            document.getElementById('edit_replacement_cost').value = asset.replacement_cost || '';
            document.getElementById('edit_recovery_time').value = asset.recovery_time || '';
            document.getElementById('edit_asset_description').value = asset.asset_description || '';
            document.getElementById('edit_legal_requirements').value = asset.legal_requirements || '';

            openModal('editModal');
        }

        function confirmDelete(id) {
            if (confirm('Esta seguro de que desea eliminar este activo?')) {
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
