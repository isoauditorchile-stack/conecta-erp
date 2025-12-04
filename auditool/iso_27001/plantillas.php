<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

$message = '';
$message_type = '';

// Procesar carga de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template_file'])) {
    $category = $_POST['category'];
    $description = $_POST['description'];

    $file = $_FILES['template_file'];
    $allowed_ext = ['xlsx', 'xls', 'docx', 'doc', 'pdf'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed_ext)) {
        $upload_dir = __DIR__ . '/plantillas/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = time() . '_' . basename($file['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Registrar en base de datos
            $sql = "INSERT INTO iso27001_templates (
                company_id, template_name, template_category, template_description,
                file_name, file_path, file_type, uploaded_by, uploaded_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssi",
                $company_id,
                $file['name'],
                $category,
                $description,
                $filename,
                $target_path,
                $file_ext,
                $user_id
            );

            if ($stmt->execute()) {
                $message = '✓ Plantilla cargada exitosamente';
                $message_type = 'success';
            } else {
                $message = '✗ Error al registrar en BD: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = '✗ Error al subir archivo';
            $message_type = 'error';
        }
    } else {
        $message = '✗ Formato de archivo no permitido';
        $message_type = 'error';
    }
}

// Eliminar plantilla
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $template_id = intval($_GET['id']);

    // Obtener info del archivo
    $sql = "SELECT file_path FROM iso27001_templates WHERE id = ? AND company_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $template_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Eliminar archivo físico
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }

        // Eliminar registro de BD
        $sql2 = "DELETE FROM iso27001_templates WHERE id = ? AND company_id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("ii", $template_id, $company_id);
        $stmt2->execute();
        $stmt2->close();

        $message = '✓ Plantilla eliminada';
        $message_type = 'success';
    }
    $stmt->close();
}

// Obtener plantillas cargadas
$sql = "SELECT * FROM iso27001_templates WHERE company_id = ? ORDER BY uploaded_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$uploaded_templates = $stmt->get_result();
$stmt->close();

// Contar por categoría
$sql = "SELECT template_category, COUNT(*) as count FROM iso27001_templates WHERE company_id = ? GROUP BY template_category";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$category_counts = [];
while ($row = $stmt->get_result()->fetch_assoc()) {
    $category_counts[$row['template_category']] = $row['count'];
}
$stmt->close();

// Categorías disponibles
$categories = [
    'formatos_excel' => 'Formatos Excel',
    'documentos_word' => 'Documentos Word',
    'anexos' => 'Anexos y Evidencias',
    'matrices' => 'Matrices y Justificaciones',
    'politicas' => 'Políticas Específicas',
    'otros' => 'Otros Documentos'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Plantillas ISO 27001 - AUDITOR PRO</title>
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
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 32px;
            color: #00994d;
            margin-bottom: 5px;
        }
        .header p { color: #666; font-size: 14px; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary { background: #0066cc; color: white; }
        .btn-success { background: #00994d; color: white; }
        .btn-danger { background: #cc0000; color: white; }
        .btn-secondary { background: #666; color: white; }
        .btn-small { padding: 6px 12px; font-size: 12px; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid #00994d;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #00994d;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
        }

        .upload-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .upload-header {
            background: #00994d;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin: -30px -30px 25px -30px;
            font-size: 20px;
            font-weight: bold;
        }
        .form-group { margin-bottom: 20px; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
        }
        textarea { min-height: 80px; resize: vertical; }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }

        .templates-viewer {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .viewer-header {
            background: #0066cc;
            color: white;
            padding: 20px 30px;
            font-size: 20px;
            font-weight: bold;
        }
        .templates-table {
            width: 100%;
            border-collapse: collapse;
        }
        .templates-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
        }
        .templates-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            color: #666;
            font-size: 13px;
        }
        .templates-table tr:hover {
            background: #f8f9fa;
        }
        .file-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .category-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            background: #e9ecef;
            color: #495057;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
    <script>
        function confirmDelete(id) {
            if (confirm('¿Está seguro de eliminar esta plantilla?')) {
                window.location.href = '?delete=true&id=' + id;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="header">
            <div>
                <h1>📚 Biblioteca de Plantillas ISO 27001</h1>
                <p>Gestione y organice todas las plantillas documentales del SGSI</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary">← Volver</a>
            </div>
        </div>

        <div class="stats-grid">
            <?php foreach ($categories as $key => $name): ?>
            <div class="stat-card">
                <div class="stat-value"><?php echo isset($category_counts[$key]) ? $category_counts[$key] : 0; ?></div>
                <div class="stat-label"><?php echo $name; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="upload-section">
            <div class="upload-header">📤 Cargar Nueva Plantilla</div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Categoría *</label>
                        <select name="category" required>
                            <option value="">Seleccione categoría...</option>
                            <?php foreach ($categories as $key => $name): ?>
                            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Archivo *</label>
                        <input type="file" name="template_file" accept=".xlsx,.xls,.docx,.doc,.pdf" required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Formatos: Excel (.xlsx, .xls), Word (.docx, .doc), PDF
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" placeholder="Descripción breve de la plantilla..."></textarea>
                </div>

                <button type="submit" class="btn btn-success">📤 Cargar Plantilla</button>
            </form>
        </div>

        <div class="templates-viewer">
            <div class="viewer-header">👁️ Plantillas Cargadas (<?php echo $uploaded_templates->num_rows; ?>)</div>

            <?php if ($uploaded_templates->num_rows > 0): ?>
            <table class="templates-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Fecha Carga</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($template = $uploaded_templates->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="file-icon">
                                <?php
                                if ($template['file_type'] == 'xlsx' || $template['file_type'] == 'xls') echo '📊';
                                elseif ($template['file_type'] == 'docx' || $template['file_type'] == 'doc') echo '📄';
                                else echo '📎';
                                ?>
                            </span>
                            <strong><?php echo htmlspecialchars($template['template_name']); ?></strong>
                        </td>
                        <td>
                            <span class="category-badge">
                                <?php echo $categories[$template['template_category']]; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($template['template_description']); ?></td>
                        <td><?php echo strtoupper($template['file_type']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($template['uploaded_date'])); ?></td>
                        <td>
                            <a href="plantillas/<?php echo $template['file_name']; ?>"
                               class="btn btn-success btn-small" download>
                                📥 Descargar
                            </a>
                            <button onclick="confirmDelete(<?php echo $template['id']; ?>)"
                                    class="btn btn-danger btn-small">
                                🗑️ Eliminar
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📂</div>
                <h3>No hay plantillas cargadas</h3>
                <p>Use el formulario superior para cargar sus primeras plantillas</p>
            </div>
            <?php endif; ?>
        </div>

        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-top: 30px;">
            <h3 style="margin-bottom: 15px;">ℹ️ Instrucciones de Uso</h3>
            <ul style="color: #666; line-height: 1.8; margin-left: 20px;">
                <li><strong>Carga de Plantillas:</strong> Suba archivos Excel, Word o PDF organizados por categoría</li>
                <li><strong>Gestión Centralizada:</strong> Todas las plantillas quedan almacenadas y disponibles para descarga</li>
                <li><strong>Control de Versiones:</strong> Cada plantilla incluye fecha de carga para rastreabilidad</li>
                <li><strong>Categorización:</strong> Organice por tipo de documento para fácil localización</li>
                <li><strong>Descarga:</strong> Descargue plantillas cuando las necesite para completar</li>
                <li><strong>Eliminación:</strong> Elimine plantillas obsoletas para mantener la biblioteca actualizada</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
