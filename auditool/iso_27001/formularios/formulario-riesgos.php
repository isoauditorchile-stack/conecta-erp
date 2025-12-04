<?php
session_start();
require_once('../../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

$message = '';
$message_type = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {

    // Calcular nivel de riesgo
    $probability = intval($_POST['probability']);
    $impact = intval($_POST['impact']);
    $risk_score = $probability * $impact;

    if ($risk_score >= 15) $risk_level = 'Crítico';
    elseif ($risk_score >= 8) $risk_level = 'Alto';
    elseif ($risk_score >= 4) $risk_level = 'Medio';
    else $risk_level = 'Bajo';

    $sql = "INSERT INTO iso27001_risks (
        company_id, risk_id, risk_name, risk_description, risk_category,
        related_asset, threat, vulnerability,
        probability, impact, risk_score, risk_level,
        existing_controls, treatment_option, treatment_plan,
        treatment_responsible, treatment_deadline, treatment_status,
        residual_probability, residual_impact, residual_score, residual_level,
        created_by, created_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssiiisssssssiisi",
        $company_id,
        $_POST['risk_id'],
        $_POST['risk_name'],
        $_POST['risk_description'],
        $_POST['risk_category'],
        $_POST['related_asset'],
        $_POST['threat'],
        $_POST['vulnerability'],
        $probability,
        $impact,
        $risk_score,
        $risk_level,
        $_POST['existing_controls'],
        $_POST['treatment_option'],
        $_POST['treatment_plan'],
        $_POST['treatment_responsible'],
        $_POST['treatment_deadline'],
        $_POST['treatment_status'],
        $_POST['residual_probability'],
        $_POST['residual_impact'],
        $risk_score, // temporal
        $risk_level, // temporal
        $user_id
    );

    if ($stmt->execute()) {
        $message = '✓ Riesgo registrado exitosamente';
        $message_type = 'success';
    } else {
        $message = '✗ Error al registrar riesgo: ' . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario Online - Análisis de Riesgos - ISO 27001</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            margin-bottom: 20px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #cc0000;
        }
        .form-header h1 {
            color: #cc0000;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .form-header p { color: #666; font-size: 14px; }
        .form-section { margin-bottom: 30px; }
        .section-title {
            background: #cc0000;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
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
        label .required { color: #cc0000; margin-left: 3px; }
        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #cc0000;
        }
        textarea { min-height: 100px; resize: vertical; }
        .risk-matrix {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 15px;
        }
        .matrix-box {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .matrix-box select {
            margin-top: 10px;
            font-weight: bold;
            padding: 10px;
        }
        .risk-calc {
            background: #ffe6e6;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-top: 20px;
        }
        .risk-result {
            font-size: 48px;
            font-weight: bold;
            color: #cc0000;
            margin: 10px 0;
        }
        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #cc0000; color: white; }
        .btn-primary:hover {
            background: #aa0000;
            transform: translateY(-2px);
        }
        .btn-secondary { background: #666; color: white; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
    <script>
        function calculateRisk() {
            var prob = parseInt(document.getElementById('probability').value) || 0;
            var imp = parseInt(document.getElementById('impact').value) || 0;
            var score = prob * imp;
            var level = '';
            var color = '';

            if (score >= 15) {
                level = 'Crítico';
                color = '#cc0000';
            } else if (score >= 8) {
                level = 'Alto';
                color = '#ff6600';
            } else if (score >= 4) {
                level = 'Medio';
                color = '#ffaa00';
            } else {
                level = 'Bajo';
                color = '#00994d';
            }

            document.getElementById('risk-score').textContent = score;
            document.getElementById('risk-level').textContent = level;
            document.getElementById('risk-level').style.color = color;
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

        <div class="form-card">
            <div class="form-header">
                <h1>⚠️ Formulario Online - Análisis de Riesgos</h1>
                <p>Registre un nuevo riesgo de seguridad de la información</p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="create">

                <!-- Identificación del Riesgo -->
                <div class="form-section">
                    <div class="section-title">📌 Identificación del Riesgo</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Código del Riesgo <span class="required">*</span></label>
                            <input type="text" name="risk_id" required placeholder="Ej: R-001">
                        </div>

                        <div class="form-group">
                            <label>Categoría del Riesgo <span class="required">*</span></label>
                            <select name="risk_category" required>
                                <option value="">Seleccione...</option>
                                <option value="Operacional">Operacional</option>
                                <option value="Tecnológico">Tecnológico</option>
                                <option value="Seguridad Física">Seguridad Física</option>
                                <option value="Legal/Cumplimiento">Legal/Cumplimiento</option>
                                <option value="Recursos Humanos">Recursos Humanos</option>
                                <option value="Terceros">Terceros/Proveedores</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nombre del Riesgo <span class="required">*</span></label>
                        <input type="text" name="risk_name" required placeholder="Ej: Pérdida de datos por falla de hardware">
                    </div>

                    <div class="form-group">
                        <label>Descripción del Riesgo <span class="required">*</span></label>
                        <textarea name="risk_description" required placeholder="Describa el riesgo en detalle..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Activo Relacionado</label>
                        <input type="text" name="related_asset" placeholder="Ej: Servidor BD Principal">
                        <div class="help-text">Activo afectado por este riesgo</div>
                    </div>
                </div>

                <!-- Análisis del Riesgo -->
                <div class="form-section">
                    <div class="section-title">🔍 Análisis del Riesgo</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Amenaza <span class="required">*</span></label>
                            <textarea name="threat" required placeholder="Ej: Falla de disco duro"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Vulnerabilidad <span class="required">*</span></label>
                            <textarea name="vulnerability" required placeholder="Ej: No hay redundancia RAID"></textarea>
                        </div>
                    </div>

                    <div class="risk-matrix">
                        <div class="matrix-box">
                            <label>Probabilidad <span class="required">*</span></label>
                            <select id="probability" name="probability" required onchange="calculateRisk()">
                                <option value="">Seleccione...</option>
                                <option value="1">1 - Muy Baja (< 5%)</option>
                                <option value="2">2 - Baja (5-25%)</option>
                                <option value="3">3 - Media (25-50%)</option>
                                <option value="4">4 - Alta (50-75%)</option>
                                <option value="5">5 - Muy Alta (> 75%)</option>
                            </select>
                        </div>

                        <div class="matrix-box">
                            <label>Impacto <span class="required">*</span></label>
                            <select id="impact" name="impact" required onchange="calculateRisk()">
                                <option value="">Seleccione...</option>
                                <option value="1">1 - Insignificante</option>
                                <option value="2">2 - Menor</option>
                                <option value="3">3 - Moderado</option>
                                <option value="4">4 - Mayor</option>
                                <option value="5">5 - Catastrófico</option>
                            </select>
                        </div>
                    </div>

                    <div class="risk-calc">
                        <div style="font-size: 14px; color: #666;">Nivel de Riesgo Inherente</div>
                        <div class="risk-result">
                            <span id="risk-score">0</span>
                        </div>
                        <div style="font-size: 20px; font-weight: bold;">
                            <span id="risk-level">-</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Controles Existentes</label>
                        <textarea name="existing_controls" placeholder="Controles ya implementados..."></textarea>
                    </div>
                </div>

                <!-- Tratamiento del Riesgo -->
                <div class="form-section">
                    <div class="section-title">🛡️ Tratamiento del Riesgo</div>

                    <div class="form-group">
                        <label>Opción de Tratamiento <span class="required">*</span></label>
                        <select name="treatment_option" required>
                            <option value="">Seleccione...</option>
                            <option value="Mitigar">Mitigar - Implementar controles</option>
                            <option value="Transferir">Transferir - Seguros, outsourcing</option>
                            <option value="Aceptar">Aceptar - Asumir el riesgo</option>
                            <option value="Evitar">Evitar - Eliminar la actividad</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Plan de Tratamiento</label>
                        <textarea name="treatment_plan" placeholder="Acciones específicas a implementar..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Responsable del Tratamiento</label>
                            <input type="text" name="treatment_responsible" placeholder="Nombre completo">
                        </div>

                        <div class="form-group">
                            <label>Fecha Límite</label>
                            <input type="date" name="treatment_deadline">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estado del Tratamiento</label>
                        <select name="treatment_status">
                            <option value="Pendiente">Pendiente</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completado">Completado</option>
                        </select>
                    </div>
                </div>

                <!-- Riesgo Residual -->
                <div class="form-section">
                    <div class="section-title">📉 Riesgo Residual (Post-Tratamiento)</div>

                    <div class="risk-matrix">
                        <div class="matrix-box">
                            <label>Probabilidad Residual</label>
                            <select name="residual_probability">
                                <option value="1">1 - Muy Baja</option>
                                <option value="2">2 - Baja</option>
                                <option value="3">3 - Media</option>
                                <option value="4">4 - Alta</option>
                                <option value="5">5 - Muy Alta</option>
                            </select>
                        </div>

                        <div class="matrix-box">
                            <label>Impacto Residual</label>
                            <select name="residual_impact">
                                <option value="1">1 - Insignificante</option>
                                <option value="2">2 - Menor</option>
                                <option value="3">3 - Moderado</option>
                                <option value="4">4 - Mayor</option>
                                <option value="5">5 - Catastrófico</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">💾 Guardar Riesgo</button>
                    <a href="../riesgos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <div style="text-align: center; color: white; margin-top: 20px;">
            <a href="../riesgos.php" style="color: white; text-decoration: none;">← Volver a Análisis de Riesgos</a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
