<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

$message = '';
$message_type = '';

// Crear/Editar NC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' || $_POST['action'] === 'edit') {

        if ($_POST['action'] === 'create') {
            $sql = "INSERT INTO iso27001_nonconformities (
                company_id, nc_id, nc_type, nc_title, nc_description,
                related_clause, detected_in, detected_date, responsible,
                root_cause, corrective_action, preventive_action,
                action_responsible, action_deadline, nc_status,
                created_by, created_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssssssssssi",
                $company_id,
                $_POST['nc_id'],
                $_POST['nc_type'],
                $_POST['nc_title'],
                $_POST['nc_description'],
                $_POST['related_clause'],
                $_POST['detected_in'],
                $_POST['detected_date'],
                $_POST['responsible'],
                $_POST['root_cause'],
                $_POST['corrective_action'],
                $_POST['preventive_action'],
                $_POST['action_responsible'],
                $_POST['action_deadline'],
                $_POST['nc_status'],
                $user_id
            );

            if ($stmt->execute()) {
                $message = '✓ No Conformidad registrada exitosamente';
                $message_type = 'success';
            } else {
                $message = '✗ Error al registrar: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
    elseif ($_POST['action'] === 'delete') {
        $nc_id = intval($_POST['nc_id']);
        $sql = "DELETE FROM iso27001_nonconformities WHERE id = ? AND company_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $nc_id, $company_id);

        if ($stmt->execute()) {
            $message = '✓ No Conformidad eliminada';
            $message_type = 'success';
        } else {
            $message = '✗ Error al eliminar';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Obtener todas las NC
$sql = "SELECT * FROM iso27001_nonconformities WHERE company_id = ? ORDER BY detected_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$nonconformities = $stmt->get_result();
$stmt->close();

// Obtener estadísticas
$sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN nc_type = 'Mayor' THEN 1 ELSE 0 END) as mayor,
    SUM(CASE WHEN nc_type = 'Menor' THEN 1 ELSE 0 END) as menor,
    SUM(CASE WHEN nc_type = 'Observación' THEN 1 ELSE 0 END) as observacion,
    SUM(CASE WHEN nc_status IN ('Abierta', 'En Análisis') THEN 1 ELSE 0 END) as abiertas,
    SUM(CASE WHEN nc_status = 'Cerrada' THEN 1 ELSE 0 END) as cerradas
    FROM iso27001_nonconformities WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Conformidades - ISO 27001 - AUDITOR PRO</title>
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
            color: #ff6600;
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid;
        }
        .stat-card.blue { border-color: #0066cc; }
        .stat-card.red { border-color: #cc0000; }
        .stat-card.orange { border-color: #ff6600; }
        .stat-card.yellow { border-color: #ffaa00; }
        .stat-card.green { border-color: #00994d; }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label { color: #666; font-size: 13px; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-header {
            background: #ff6600;
            color: white;
            padding: 20px 30px;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            color: #666;
            font-size: 13px;
        }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-mayor { background: #cc0000; color: white; }
        .badge-menor { background: #ff6600; color: white; }
        .badge-observacion { background: #ffaa00; color: #333; }
        .badge-abierta { background: #cc0000; color: white; }
        .badge-analisis { background: #ff6600; color: white; }
        .badge-correccion { background: #0066cc; color: white; }
        .badge-verificacion { background: #9900cc; color: white; }
        .badge-cerrada { background: #00994d; color: white; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            margin: 30px auto;
            padding: 40px;
            border-radius: 15px;
            max-width: 1000px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .modal-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #ff6600;
        }
        .modal-header h2 {
            color: #ff6600;
            font-size: 24px;
        }
        .close {
            float: right;
            font-size: 32px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            line-height: 20px;
        }
        .close:hover { color: #333; }
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
        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
        }
        textarea { min-height: 100px; resize: vertical; }
    </style>
    <script>
        function openNCModal() {
            document.getElementById('ncModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('ncModal').style.display = 'none';
        }

        function deleteNC(id) {
            if (confirm('¿Está seguro de eliminar esta No Conformidad?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                                '<input type="hidden" name="nc_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            var modal = document.getElementById('ncModal');
            if (event.target == modal) {
                modal.style.display = 'none';
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
                <h1>⚠️ Gestión de No Conformidades</h1>
                <p>Registro y seguimiento de hallazgos de auditoría ISO 27001</p>
            </div>
            <div>
                <button onclick="openNCModal()" class="btn btn-primary">➕ Nueva NC</button>
                <a href="export.php?type=no_conformidades" class="btn btn-success">📥 Exportar</a>
                <a href="index.php" class="btn btn-secondary">← Volver</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de NC</div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?php echo $stats['mayor']; ?></div>
                <div class="stat-label">No Conformidades Mayor</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?php echo $stats['menor']; ?></div>
                <div class="stat-label">No Conformidades Menor</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-value"><?php echo $stats['observacion']; ?></div>
                <div class="stat-label">Observaciones</div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?php echo $stats['abiertas']; ?></div>
                <div class="stat-label">Abiertas/En Análisis</div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?php echo $stats['cerradas']; ?></div>
                <div class="stat-label">Cerradas</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <span>📋 Registro de No Conformidades</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>Cláusula Relacionada</th>
                        <th>Detectado en</th>
                        <th>Fecha</th>
                        <th>Responsable</th>
                        <th>Fecha Límite</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($nc = $nonconformities->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $nc['nc_id']; ?></strong></td>
                        <td>
                            <?php
                            $type_class = 'observacion';
                            if ($nc['nc_type'] == 'Mayor') $type_class = 'mayor';
                            elseif ($nc['nc_type'] == 'Menor') $type_class = 'menor';
                            ?>
                            <span class="badge badge-<?php echo $type_class; ?>">
                                <?php echo $nc['nc_type']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($nc['nc_title']); ?></td>
                        <td><?php echo $nc['related_clause']; ?></td>
                        <td><?php echo $nc['detected_in']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($nc['detected_date'])); ?></td>
                        <td><?php echo $nc['responsible']; ?></td>
                        <td>
                            <?php
                            if ($nc['action_deadline']) {
                                echo date('d/m/Y', strtotime($nc['action_deadline']));
                                if ($nc['action_deadline'] < date('Y-m-d') && $nc['nc_status'] != 'Cerrada') {
                                    echo ' <span class="badge badge-abierta">VENCIDO</span>';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $status_class = 'abierta';
                            if ($nc['nc_status'] == 'Cerrada') $status_class = 'cerrada';
                            elseif ($nc['nc_status'] == 'En Análisis') $status_class = 'analisis';
                            elseif ($nc['nc_status'] == 'En Corrección') $status_class = 'correccion';
                            elseif ($nc['nc_status'] == 'En Verificación') $status_class = 'verificacion';
                            ?>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo $nc['nc_status']; ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="deleteNC(<?php echo $nc['id']; ?>)" class="btn btn-danger btn-small">
                                🗑️ Eliminar
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nueva NC -->
    <div id="ncModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>📝 Registrar Nueva No Conformidad</h2>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-row">
                    <div class="form-group">
                        <label>ID de la NC *</label>
                        <input type="text" name="nc_id" required placeholder="Ej: NC-001">
                    </div>
                    <div class="form-group">
                        <label>Tipo de NC *</label>
                        <select name="nc_type" required>
                            <option value="">Seleccione...</option>
                            <option value="Mayor">No Conformidad Mayor</option>
                            <option value="Menor">No Conformidad Menor</option>
                            <option value="Observación">Observación</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Título de la NC *</label>
                    <input type="text" name="nc_title" required placeholder="Título breve de la no conformidad">
                </div>

                <div class="form-group">
                    <label>Descripción Detallada *</label>
                    <textarea name="nc_description" required placeholder="Describa la no conformidad en detalle..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Cláusula ISO Relacionada *</label>
                        <input type="text" name="related_clause" required placeholder="Ej: 6.1.2, A.5.1">
                    </div>
                    <div class="form-group">
                        <label>Detectado en *</label>
                        <select name="detected_in" required>
                            <option value="">Seleccione...</option>
                            <option value="Auditoría Interna">Auditoría Interna</option>
                            <option value="Auditoría Externa">Auditoría Externa</option>
                            <option value="Auditoría de Certificación">Auditoría de Certificación</option>
                            <option value="Revisión por la Dirección">Revisión por la Dirección</option>
                            <option value="Autoevaluación">Autoevaluación</option>
                            <option value="Incidente">Análisis de Incidente</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Detección *</label>
                        <input type="date" name="detected_date" required>
                    </div>
                    <div class="form-group">
                        <label>Responsable de la NC *</label>
                        <input type="text" name="responsible" required placeholder="Nombre del responsable">
                    </div>
                </div>

                <h3 style="margin: 25px 0 15px 0; color: #333;">🔍 Análisis de Causa Raíz</h3>

                <div class="form-group">
                    <label>Causa Raíz *</label>
                    <textarea name="root_cause" required placeholder="Análisis de la causa raíz (5 Porqués, Ishikawa, etc.)..."></textarea>
                </div>

                <h3 style="margin: 25px 0 15px 0; color: #333;">✅ Plan de Acción</h3>

                <div class="form-group">
                    <label>Acción Correctiva *</label>
                    <textarea name="corrective_action" required placeholder="Acciones para corregir la no conformidad..."></textarea>
                </div>

                <div class="form-group">
                    <label>Acción Preventiva</label>
                    <textarea name="preventive_action" placeholder="Acciones para prevenir recurrencia..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Responsable de la Acción *</label>
                        <input type="text" name="action_responsible" required placeholder="Quien ejecutará las acciones">
                    </div>
                    <div class="form-group">
                        <label>Fecha Límite *</label>
                        <input type="date" name="action_deadline" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Estado *</label>
                    <select name="nc_status" required>
                        <option value="Abierta">Abierta</option>
                        <option value="En Análisis">En Análisis</option>
                        <option value="En Corrección">En Corrección</option>
                        <option value="En Verificación">En Verificación</option>
                        <option value="Cerrada">Cerrada</option>
                    </select>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Guardar No Conformidad</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
