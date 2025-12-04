<?php
session_start();
require_once('../../config/config.php');
$conn = getDBConnection();
$user_id = $_SESSION['user_id'] ?? 1;
$company_id = $_SESSION['company_id'] ?? 1;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $sql = "INSERT INTO iso27001_nonconformities (company_id, nc_id, nc_type, nc_source, detection_date, detected_by, requirement, nc_description, area, responsible, root_cause, corrective_action, preventive_action, due_date, completion_date, status, verification, effectiveness, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssssssssssssi", $company_id, $_POST['nc_id'], $_POST['nc_type'], $_POST['nc_source'], $_POST['detection_date'], $_POST['detected_by'], $_POST['requirement'], $_POST['nc_description'], $_POST['area'], $_POST['responsible'], $_POST['root_cause'], $_POST['corrective_action'], $_POST['preventive_action'], $_POST['due_date'], $_POST['completion_date'], $_POST['status'], $_POST['verification'], $_POST['effectiveness'], $user_id);
    $message = $stmt->execute() ? '✓ No Conformidad registrada' : '✗ Error: ' . $stmt->error;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario Online - No Conformidades</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .form-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 40px; }
        h1 { color: #ff6600; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 14px; }
        textarea { min-height: 80px; }
        .btn { padding: 15px 40px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin: 10px; }
        .btn-primary { background: #ff6600; color: white; }
        .btn-secondary { background: #666; color: white; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>
        <div class="form-card">
            <h1>⚠️ Formulario - Registro de No Conformidad</h1>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <div class="form-group">
                        <label>ID No Conformidad *</label>
                        <input type="text" name="nc_id" required placeholder="NC-001">
                    </div>
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="nc_type" required>
                            <option value="Mayor">Mayor</option>
                            <option value="Menor">Menor</option>
                            <option value="Observación">Observación</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Origen *</label>
                        <select name="nc_source" required>
                            <option value="Auditoría Interna">Auditoría Interna</option>
                            <option value="Auditoría Externa">Auditoría Externa</option>
                            <option value="Revisión Gerencia">Revisión Gerencia</option>
                            <option value="Proceso Operativo">Proceso Operativo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha Detección *</label>
                        <input type="date" name="detection_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Detectado Por *</label>
                        <input type="text" name="detected_by" required>
                    </div>
                    <div class="form-group">
                        <label>Requisito ISO *</label>
                        <input type="text" name="requirement" required placeholder="Ej: Cláusula 9.2">
                    </div>
                </div>
                <div class="form-group">
                    <label>Descripción de la No Conformidad *</label>
                    <textarea name="nc_description" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Área Afectada *</label>
                        <input type="text" name="area" required>
                    </div>
                    <div class="form-group">
                        <label>Responsable *</label>
                        <input type="text" name="responsible" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Análisis de Causa Raíz</label>
                    <textarea name="root_cause"></textarea>
                </div>
                <div class="form-group">
                    <label>Acción Correctiva</label>
                    <textarea name="corrective_action"></textarea>
                </div>
                <div class="form-group">
                    <label>Acción Preventiva</label>
                    <textarea name="preventive_action"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Compromiso *</label>
                        <input type="date" name="due_date" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Completado</label>
                        <input type="date" name="completion_date">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Estado *</label>
                        <select name="status" required>
                            <option value="Abierta">Abierta</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Cerrada">Cerrada</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Verificación</label>
                        <select name="verification">
                            <option value="">Pendiente</option>
                            <option value="Verificada">Verificada</option>
                            <option value="No Efectiva">No Efectiva</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Efectividad de la Acción</label>
                    <select name="effectiveness">
                        <option value="">Por evaluar</option>
                        <option value="Efectiva">Efectiva</option>
                        <option value="Parcial">Parcial</option>
                        <option value="No Efectiva">No Efectiva</option>
                    </select>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-primary">💾 Registrar NC</button>
                    <a href="../index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
