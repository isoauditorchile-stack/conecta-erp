<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

$message = '';
$message_type = '';

// Crear/Actualizar plan de tratamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_treatment') {
        $risk_id = intval($_POST['risk_id']);

        $sql = "UPDATE iso27001_risks SET
                treatment_option = ?,
                treatment_plan = ?,
                treatment_responsible = ?,
                treatment_deadline = ?,
                treatment_status = ?,
                treatment_cost = ?,
                residual_probability = ?,
                residual_impact = ?
                WHERE id = ? AND company_id = ?";

        $stmt = $conn->prepare($sql);

        $res_prob = intval($_POST['residual_probability']);
        $res_imp = intval($_POST['residual_impact']);

        $stmt->bind_param("sssssdiii",
            $_POST['treatment_option'],
            $_POST['treatment_plan'],
            $_POST['treatment_responsible'],
            $_POST['treatment_deadline'],
            $_POST['treatment_status'],
            $_POST['treatment_cost'],
            $res_prob,
            $res_imp,
            $risk_id,
            $company_id
        );

        if ($stmt->execute()) {
            // Recalcular riesgo residual
            $residual_score = $res_prob * $res_imp;
            if ($residual_score >= 15) $residual_level = 'Crítico';
            elseif ($residual_score >= 8) $residual_level = 'Alto';
            elseif ($residual_score >= 4) $residual_level = 'Medio';
            else $residual_level = 'Bajo';

            $sql2 = "UPDATE iso27001_risks SET residual_score = ?, residual_level = ? WHERE id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("isi", $residual_score, $residual_level, $risk_id);
            $stmt2->execute();
            $stmt2->close();

            $message = '✓ Plan de tratamiento actualizado exitosamente';
            $message_type = 'success';
        } else {
            $message = '✗ Error al actualizar: ' . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Obtener todos los riesgos con su plan de tratamiento
$sql = "SELECT * FROM iso27001_risks WHERE company_id = ? ORDER BY risk_score DESC, risk_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$risks = $stmt->get_result();
$stmt->close();

// Obtener estadísticas del plan
$sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN treatment_status = 'Completado' THEN 1 ELSE 0 END) as completados,
    SUM(CASE WHEN treatment_status = 'En Proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN treatment_status = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN treatment_deadline < CURDATE() AND treatment_status != 'Completado' THEN 1 ELSE 0 END) as vencidos
    FROM iso27001_risks WHERE company_id = ?";
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
    <title>Plan de Tratamiento de Riesgos - ISO 27001 - AUDITOR PRO</title>
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
            color: #cc0000;
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
        .stat-card.blue { border-color: #0066cc; }
        .stat-card.green { border-color: #00994d; }
        .stat-card.orange { border-color: #ff6600; }
        .stat-card.red { border-color: #cc0000; }
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
            margin-bottom: 30px;
        }
        .table-header {
            background: #cc0000;
            color: white;
            padding: 20px 30px;
            font-size: 20px;
            font-weight: bold;
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
        .badge-critico { background: #cc0000; color: white; }
        .badge-alto { background: #ff6600; color: white; }
        .badge-medio { background: #ffaa00; color: #333; }
        .badge-bajo { background: #00994d; color: white; }
        .badge-completado { background: #00994d; color: white; }
        .badge-proceso { background: #0066cc; color: white; }
        .badge-pendiente { background: #ffaa00; color: #333; }
        .badge-vencido { background: #cc0000; color: white; }
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
            max-width: 900px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .modal-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #cc0000;
        }
        .modal-header h2 {
            color: #cc0000;
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
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
        }
        textarea { min-height: 100px; resize: vertical; }
        .risk-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .risk-info-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
        }
        .info-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
    </style>
    <script>
        function openTreatmentModal(riskId, riskName, currentLevel, probability, impact) {
            document.getElementById('modal_risk_id').value = riskId;
            document.getElementById('modal_risk_name').textContent = riskName;
            document.getElementById('modal_current_level').textContent = currentLevel;
            document.getElementById('modal_probability').textContent = probability;
            document.getElementById('modal_impact').textContent = impact;
            document.getElementById('treatmentModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('treatmentModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('treatmentModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function calculateResidual() {
            var prob = parseInt(document.getElementById('residual_probability').value) || 0;
            var imp = parseInt(document.getElementById('residual_impact').value) || 0;
            var score = prob * imp;
            var level = '';

            if (score >= 15) level = 'Crítico';
            else if (score >= 8) level = 'Alto';
            else if (score >= 4) level = 'Medio';
            else level = 'Bajo';

            document.getElementById('residual_result').innerHTML =
                '<strong>Score:</strong> ' + score + ' | <strong>Nivel:</strong> ' + level;
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
                <h1>🛡️ Plan de Tratamiento de Riesgos</h1>
                <p>Gestión de estrategias y acciones para el tratamiento de riesgos ISO 27001</p>
            </div>
            <div>
                <a href="export.php?type=plan_tratamiento" class="btn btn-success">📥 Exportar Plan</a>
                <a href="index.php" class="btn btn-secondary">← Volver</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de Riesgos</div>
            </div>
            <div class="stat-card green">
                <div class="stat-value"><?php echo $stats['completados']; ?></div>
                <div class="stat-label">Tratamientos Completados</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?php echo $stats['en_proceso']; ?></div>
                <div class="stat-label">En Proceso</div>
            </div>
            <div class="stat-card red">
                <div class="stat-value"><?php echo $stats['vencidos']; ?></div>
                <div class="stat-label">Vencidos</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">📋 Plan de Tratamiento de Riesgos</div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Riesgo</th>
                        <th>Nivel Inherente</th>
                        <th>Opción de Tratamiento</th>
                        <th>Responsable</th>
                        <th>Fecha Límite</th>
                        <th>Estado</th>
                        <th>Nivel Residual</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($risk = $risks->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $risk['risk_id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($risk['risk_name']); ?></td>
                        <td>
                            <?php
                            $level_class = 'medio';
                            if ($risk['risk_level'] == 'Crítico') $level_class = 'critico';
                            elseif ($risk['risk_level'] == 'Alto') $level_class = 'alto';
                            elseif ($risk['risk_level'] == 'Bajo') $level_class = 'bajo';
                            ?>
                            <span class="badge badge-<?php echo $level_class; ?>">
                                <?php echo $risk['risk_level']; ?> (<?php echo $risk['risk_score']; ?>)
                            </span>
                        </td>
                        <td><?php echo $risk['treatment_option'] ? $risk['treatment_option'] : 'No definido'; ?></td>
                        <td><?php echo $risk['treatment_responsible'] ? $risk['treatment_responsible'] : '-'; ?></td>
                        <td>
                            <?php
                            if ($risk['treatment_deadline']) {
                                echo date('d/m/Y', strtotime($risk['treatment_deadline']));
                                if ($risk['treatment_deadline'] < date('Y-m-d') && $risk['treatment_status'] != 'Completado') {
                                    echo ' <span class="badge badge-vencido">VENCIDO</span>';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $status_class = 'pendiente';
                            if ($risk['treatment_status'] == 'Completado') $status_class = 'completado';
                            elseif ($risk['treatment_status'] == 'En Proceso') $status_class = 'proceso';
                            ?>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo $risk['treatment_status'] ? $risk['treatment_status'] : 'Pendiente'; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $res_level_class = 'medio';
                            if ($risk['residual_level'] == 'Crítico') $res_level_class = 'critico';
                            elseif ($risk['residual_level'] == 'Alto') $res_level_class = 'alto';
                            elseif ($risk['residual_level'] == 'Bajo') $res_level_class = 'bajo';
                            ?>
                            <span class="badge badge-<?php echo $res_level_class; ?>">
                                <?php echo $risk['residual_level']; ?> (<?php echo $risk['residual_score']; ?>)
                            </span>
                        </td>
                        <td>
                            <button onclick="openTreatmentModal(
                                <?php echo $risk['id']; ?>,
                                '<?php echo addslashes($risk['risk_name']); ?>',
                                '<?php echo $risk['risk_level']; ?>',
                                <?php echo $risk['probability']; ?>,
                                <?php echo $risk['impact']; ?>
                            )" class="btn btn-primary btn-small">
                                ✏️ Gestionar
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Tratamiento -->
    <div id="treatmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>📝 Gestionar Plan de Tratamiento</h2>
            </div>

            <div class="risk-info">
                <h3 id="modal_risk_name" style="color: #cc0000; margin-bottom: 10px;"></h3>
                <div class="risk-info-row">
                    <div class="info-item">
                        <div class="info-label">Nivel Actual</div>
                        <div class="info-value" id="modal_current_level"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Probabilidad</div>
                        <div class="info-value" id="modal_probability"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Impacto</div>
                        <div class="info-value" id="modal_impact"></div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_treatment">
                <input type="hidden" name="risk_id" id="modal_risk_id">

                <div class="form-group">
                    <label>Opción de Tratamiento</label>
                    <select name="treatment_option" required>
                        <option value="">Seleccione...</option>
                        <option value="Mitigar">Mitigar - Implementar controles</option>
                        <option value="Transferir">Transferir - Seguros, outsourcing</option>
                        <option value="Aceptar">Aceptar - Asumir el riesgo</option>
                        <option value="Evitar">Evitar - Eliminar la actividad</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Plan de Tratamiento (Acciones Específicas)</label>
                    <textarea name="treatment_plan" required placeholder="Describa las acciones específicas a implementar..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Responsable</label>
                        <input type="text" name="treatment_responsible" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Límite</label>
                        <input type="date" name="treatment_deadline" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="treatment_status" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completado">Completado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Costo Estimado (USD)</label>
                        <input type="number" name="treatment_cost" step="0.01" placeholder="0.00">
                    </div>
                </div>

                <h3 style="margin: 25px 0 15px 0; color: #333;">📉 Riesgo Residual (Post-Tratamiento)</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Probabilidad Residual</label>
                        <select name="residual_probability" id="residual_probability" onchange="calculateResidual()" required>
                            <option value="1">1 - Muy Baja (< 5%)</option>
                            <option value="2">2 - Baja (5-25%)</option>
                            <option value="3">3 - Media (25-50%)</option>
                            <option value="4">4 - Alta (50-75%)</option>
                            <option value="5">5 - Muy Alta (> 75%)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Impacto Residual</label>
                        <select name="residual_impact" id="residual_impact" onchange="calculateResidual()" required>
                            <option value="1">1 - Insignificante</option>
                            <option value="2">2 - Menor</option>
                            <option value="3">3 - Moderado</option>
                            <option value="4">4 - Mayor</option>
                            <option value="5">5 - Catastrófico</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                        <div id="residual_result" style="font-size: 16px; color: #333;">
                            Seleccione probabilidad e impacto residual
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Guardar Plan de Tratamiento</button>
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
