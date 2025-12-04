<?php
session_start();
require_once('../../config/config.php');
$conn = getDBConnection();
$user_id = $_SESSION['user_id'] ?? 1;
$company_id = $_SESSION['company_id'] ?? 1;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $sql = "INSERT INTO iso27001_incidents (company_id, incident_id, incident_title, incident_type, severity, detected_date, reported_by, assigned_to, incident_description, affected_systems, impact_description, containment_actions, eradication_actions, recovery_actions, root_cause, lessons_learned, status, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssssssssssi", $company_id, $_POST['incident_id'], $_POST['incident_title'], $_POST['incident_type'], $_POST['severity'], $_POST['detected_date'], $_POST['reported_by'], $_POST['assigned_to'], $_POST['incident_description'], $_POST['affected_systems'], $_POST['impact_description'], $_POST['containment_actions'], $_POST['eradication_actions'], $_POST['recovery_actions'], $_POST['root_cause'], $_POST['lessons_learned'], $_POST['status'], $user_id);
    $message = $stmt->execute() ? '✓ Incidente registrado' : '✗ Error: ' . $stmt->error;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario Online - Incidentes de Seguridad</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .form-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 40px; }
        h1 { color: #cc3333; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 14px; }
        textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 15px 40px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin: 10px; }
        .btn-primary { background: #cc3333; color: white; }
        .btn-secondary { background: #666; color: white; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>
        <div class="form-card">
            <h1>🚨 Formulario - Registro de Incidente de Seguridad</h1>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>ID Incidente *</label>
                    <input type="text" name="incident_id" required placeholder="INC-2025-001">
                </div>
                <div class="form-group">
                    <label>Título del Incidente *</label>
                    <input type="text" name="incident_title" required>
                </div>
                <div class="form-group">
                    <label>Tipo de Incidente *</label>
                    <select name="incident_type" required>
                        <option value="Malware">Malware</option>
                        <option value="Phishing">Phishing</option>
                        <option value="Acceso No Autorizado">Acceso No Autorizado</option>
                        <option value="Pérdida de Datos">Pérdida de Datos</option>
                        <option value="Denegación de Servicio">Denegación de Servicio</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Severidad *</label>
                    <select name="severity" required>
                        <option value="Crítica">Crítica</option>
                        <option value="Alta">Alta</option>
                        <option value="Media">Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha de Detección *</label>
                    <input type="datetime-local" name="detected_date" required>
                </div>
                <div class="form-group">
                    <label>Reportado Por *</label>
                    <input type="text" name="reported_by" required>
                </div>
                <div class="form-group">
                    <label>Asignado A</label>
                    <input type="text" name="assigned_to">
                </div>
                <div class="form-group">
                    <label>Descripción del Incidente *</label>
                    <textarea name="incident_description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Sistemas Afectados</label>
                    <textarea name="affected_systems"></textarea>
                </div>
                <div class="form-group">
                    <label>Descripción del Impacto</label>
                    <textarea name="impact_description"></textarea>
                </div>
                <div class="form-group">
                    <label>Acciones de Contención</label>
                    <textarea name="containment_actions"></textarea>
                </div>
                <div class="form-group">
                    <label>Acciones de Erradicación</label>
                    <textarea name="eradication_actions"></textarea>
                </div>
                <div class="form-group">
                    <label>Acciones de Recuperación</label>
                    <textarea name="recovery_actions"></textarea>
                </div>
                <div class="form-group">
                    <label>Causa Raíz</label>
                    <textarea name="root_cause"></textarea>
                </div>
                <div class="form-group">
                    <label>Lecciones Aprendidas</label>
                    <textarea name="lessons_learned"></textarea>
                </div>
                <div class="form-group">
                    <label>Estado *</label>
                    <select name="status" required>
                        <option value="Nuevo">Nuevo</option>
                        <option value="En Investigación">En Investigación</option>
                        <option value="Contenido">Contenido</option>
                        <option value="Resuelto">Resuelto</option>
                        <option value="Cerrado">Cerrado</option>
                    </select>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-primary">💾 Registrar Incidente</button>
                    <a href="../incidentes.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
