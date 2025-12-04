<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

$message = '';
$message_type = '';

// Crear/Editar Auditoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $sql = "INSERT INTO iso27001_audits (
            company_id, audit_id, audit_type, audit_scope, audit_date_from, audit_date_to,
            lead_auditor, audit_team, audited_areas, audit_standard,
            audit_objective, audit_status, audit_result, findings_summary,
            opportunities_improvement, created_by, created_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssssssssssi",
            $company_id,
            $_POST['audit_id'],
            $_POST['audit_type'],
            $_POST['audit_scope'],
            $_POST['audit_date_from'],
            $_POST['audit_date_to'],
            $_POST['lead_auditor'],
            $_POST['audit_team'],
            $_POST['audited_areas'],
            $_POST['audit_standard'],
            $_POST['audit_objective'],
            $_POST['audit_status'],
            $_POST['audit_result'],
            $_POST['findings_summary'],
            $_POST['opportunities_improvement'],
            $user_id
        );

        if ($stmt->execute()) {
            $message = '✓ Auditoría registrada exitosamente';
            $message_type = 'success';
        } else {
            $message = '✗ Error al registrar: ' . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
    elseif ($_POST['action'] === 'delete') {
        $audit_id = intval($_POST['audit_id']);
        $sql = "DELETE FROM iso27001_audits WHERE id = ? AND company_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $audit_id, $company_id);

        if ($stmt->execute()) {
            $message = '✓ Auditoría eliminada';
            $message_type = 'success';
        } else {
            $message = '✗ Error al eliminar';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Obtener todas las auditorías
$sql = "SELECT * FROM iso27001_audits WHERE company_id = ? ORDER BY audit_date_from DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$audits = $stmt->get_result();
$stmt->close();

// Obtener estadísticas
$sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN audit_type = 'Interna' THEN 1 ELSE 0 END) as internas,
    SUM(CASE WHEN audit_type = 'Externa' THEN 1 ELSE 0 END) as externas,
    SUM(CASE WHEN audit_type = 'Certificación' THEN 1 ELSE 0 END) as certificacion,
    SUM(CASE WHEN audit_status = 'Completada' THEN 1 ELSE 0 END) as completadas,
    SUM(CASE WHEN audit_status = 'Planificada' THEN 1 ELSE 0 END) as planificadas
    FROM iso27001_audits WHERE company_id = ?";
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
    <title>Gestión de Auditorías - ISO 27001 - AUDITOR PRO</title>
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
            color: #9900cc;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-card.purple { border-color: #9900cc; }
        .stat-card.blue { border-color: #0066cc; }
        .stat-card.orange { border-color: #ff6600; }
        .stat-card.green { border-color: #00994d; }
        .stat-card.yellow { border-color: #ffaa00; }
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
            background: #9900cc;
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
        .badge-interna { background: #0066cc; color: white; }
        .badge-externa { background: #ff6600; color: white; }
        .badge-certificacion { background: #9900cc; color: white; }
        .badge-planificada { background: #ffaa00; color: #333; }
        .badge-proceso { background: #0066cc; color: white; }
        .badge-completada { background: #00994d; color: white; }
        .badge-conforme { background: #00994d; color: white; }
        .badge-hallazgos { background: #ff6600; color: white; }
        .badge-noconforme { background: #cc0000; color: white; }
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
            border-bottom: 3px solid #9900cc;
        }
        .modal-header h2 {
            color: #9900cc;
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
        function openAuditModal() {
            document.getElementById('auditModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('auditModal').style.display = 'none';
        }

        function deleteAudit(id) {
            if (confirm('¿Está seguro de eliminar esta auditoría?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                                '<input type="hidden" name="audit_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            var modal = document.getElementById('auditModal');
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
                <h1>🔍 Gestión de Auditorías</h1>
                <p>Planificación y seguimiento de auditorías del SGSI ISO 27001</p>
            </div>
            <div>
                <button onclick="openAuditModal()" class="btn btn-primary">➕ Nueva Auditoría</button>
                <a href="export.php?type=auditorias" class="btn btn-success">📥 Exportar</a>
                <a href="index.php" class="btn btn-secondary">← Volver</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de Auditorías</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-value"><?php echo $stats['internas']; ?></div>
                <div class="stat-label">Auditorías Internas</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?php echo $stats['externas']; ?></div>
                <div class="stat-label">Auditorías Externas</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-value"><?php echo $stats['certificacion']; ?></div>
                <div class="stat-label">Certificación</div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?php echo $stats['completadas']; ?></div>
                <div class="stat-label">Completadas</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-value"><?php echo $stats['planificadas']; ?></div>
                <div class="stat-label">Planificadas</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <span>📋 Programa de Auditorías</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Alcance</th>
                        <th>Fecha Desde</th>
                        <th>Fecha Hasta</th>
                        <th>Auditor Líder</th>
                        <th>Áreas Auditadas</th>
                        <th>Estado</th>
                        <th>Resultado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($audit = $audits->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $audit['audit_id']; ?></strong></td>
                        <td>
                            <?php
                            $type_class = 'interna';
                            if ($audit['audit_type'] == 'Externa') $type_class = 'externa';
                            elseif ($audit['audit_type'] == 'Certificación') $type_class = 'certificacion';
                            ?>
                            <span class="badge badge-<?php echo $type_class; ?>">
                                <?php echo $audit['audit_type']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($audit['audit_scope']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($audit['audit_date_from'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($audit['audit_date_to'])); ?></td>
                        <td><?php echo $audit['lead_auditor']; ?></td>
                        <td><?php echo htmlspecialchars($audit['audited_areas']); ?></td>
                        <td>
                            <?php
                            $status_class = 'planificada';
                            if ($audit['audit_status'] == 'Completada') $status_class = 'completada';
                            elseif ($audit['audit_status'] == 'En Proceso') $status_class = 'proceso';
                            ?>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo $audit['audit_status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($audit['audit_result']): ?>
                                <?php
                                $result_class = 'conforme';
                                if ($audit['audit_result'] == 'No Conforme') $result_class = 'noconforme';
                                elseif ($audit['audit_result'] == 'Conforme con Hallazgos') $result_class = 'hallazgos';
                                ?>
                                <span class="badge badge-<?php echo $result_class; ?>">
                                    <?php echo $audit['audit_result']; ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="deleteAudit(<?php echo $audit['id']; ?>)" class="btn btn-danger btn-small">
                                🗑️ Eliminar
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nueva Auditoría -->
    <div id="auditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>📝 Registrar Nueva Auditoría</h2>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-row">
                    <div class="form-group">
                        <label>ID de Auditoría *</label>
                        <input type="text" name="audit_id" required placeholder="Ej: AUD-2024-001">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Auditoría *</label>
                        <select name="audit_type" required>
                            <option value="">Seleccione...</option>
                            <option value="Interna">Auditoría Interna</option>
                            <option value="Externa">Auditoría Externa</option>
                            <option value="Certificación">Auditoría de Certificación</option>
                            <option value="Seguimiento">Auditoría de Seguimiento</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Alcance de la Auditoría *</label>
                    <textarea name="audit_scope" required placeholder="Describa el alcance de la auditoría..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Desde *</label>
                        <input type="date" name="audit_date_from" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Hasta *</label>
                        <input type="date" name="audit_date_to" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Auditor Líder *</label>
                        <input type="text" name="lead_auditor" required placeholder="Nombre del auditor líder">
                    </div>
                    <div class="form-group">
                        <label>Equipo Auditor</label>
                        <input type="text" name="audit_team" placeholder="Nombres de auditores separados por coma">
                    </div>
                </div>

                <div class="form-group">
                    <label>Áreas a Auditar *</label>
                    <textarea name="audited_areas" required placeholder="Liste las áreas o procesos a auditar..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Norma/Estándar *</label>
                        <input type="text" name="audit_standard" required value="ISO/IEC 27001:2022">
                    </div>
                    <div class="form-group">
                        <label>Estado *</label>
                        <select name="audit_status" required>
                            <option value="Planificada">Planificada</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Objetivo de la Auditoría</label>
                    <textarea name="audit_objective" placeholder="Describa los objetivos de la auditoría..."></textarea>
                </div>

                <h3 style="margin: 25px 0 15px 0; color: #333;">📊 Resultados (Completar después de la auditoría)</h3>

                <div class="form-group">
                    <label>Resultado de la Auditoría</label>
                    <select name="audit_result">
                        <option value="">Pendiente</option>
                        <option value="Conforme">Conforme</option>
                        <option value="Conforme con Hallazgos">Conforme con Hallazgos</option>
                        <option value="No Conforme">No Conforme</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Resumen de Hallazgos</label>
                    <textarea name="findings_summary" placeholder="Resumen de las no conformidades y observaciones encontradas..."></textarea>
                </div>

                <div class="form-group">
                    <label>Oportunidades de Mejora</label>
                    <textarea name="opportunities_improvement" placeholder="Oportunidades de mejora identificadas..."></textarea>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Guardar Auditoría</button>
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
