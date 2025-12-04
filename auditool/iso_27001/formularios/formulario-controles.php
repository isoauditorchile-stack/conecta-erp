<?php
session_start();
require_once('../../config/config.php');
$conn = getDBConnection();
$user_id = $_SESSION['user_id'] ?? 1;
$company_id = $_SESSION['company_id'] ?? 1;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $sql = "INSERT INTO iso27001_controls (company_id, control_code, control_name, control_category, control_type, applicability, implementation_status, implementation_percentage, responsible, implementation_date, verification_date, evidence, cost, effectiveness, observations, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssississssi", $company_id, $_POST['control_code'], $_POST['control_name'], $_POST['control_category'], $_POST['control_type'], $_POST['applicability'], $_POST['implementation_status'], $_POST['implementation_percentage'], $_POST['responsible'], $_POST['implementation_date'], $_POST['verification_date'], $_POST['evidence'], $_POST['cost'], $_POST['effectiveness'], $_POST['observations'], $user_id);
    $message = $stmt->execute() ? '✓ Control registrado' : '✗ Error: ' . $stmt->error;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario Online - Evaluación de Controles</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .form-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 40px; }
        h1 { color: #9900cc; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 14px; }
        textarea { min-height: 80px; }
        .btn { padding: 15px 40px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin: 10px; }
        .btn-primary { background: #9900cc; color: white; }
        .btn-secondary { background: #666; color: white; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>
        <div class="form-card">
            <h1>⚙️ Formulario - Evaluación de Controles Anexo A</h1>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <div class="form-group">
                        <label>Código Control *</label>
                        <input type="text" name="control_code" required placeholder="A.5.1">
                    </div>
                    <div class="form-group">
                        <label>Categoría *</label>
                        <select name="control_category" required>
                            <option value="Organizacional">Organizacional (A.5)</option>
                            <option value="Personas">Personas (A.6)</option>
                            <option value="Físico">Físico (A.7)</option>
                            <option value="Tecnológico">Tecnológico (A.8)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nombre del Control *</label>
                    <input type="text" name="control_name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="control_type" required>
                            <option value="Preventivo">Preventivo</option>
                            <option value="Detectivo">Detectivo</option>
                            <option value="Correctivo">Correctivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Aplicabilidad *</label>
                        <select name="applicability" required>
                            <option value="Aplicable">Aplicable</option>
                            <option value="No Aplicable">No Aplicable</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Estado Implementación *</label>
                        <select name="implementation_status" required>
                            <option value="No Implementado">No Implementado</option>
                            <option value="Parcial">Parcial</option>
                            <option value="Implementado">Implementado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>% Implementación *</label>
                        <input type="number" name="implementation_percentage" required min="0" max="100">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Responsable *</label>
                        <input type="text" name="responsible" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Implementación</label>
                        <input type="date" name="implementation_date">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Verificación</label>
                        <input type="date" name="verification_date">
                    </div>
                    <div class="form-group">
                        <label>Costo Implementación</label>
                        <input type="text" name="cost" placeholder="$">
                    </div>
                </div>
                <div class="form-group">
                    <label>Evidencia</label>
                    <textarea name="evidence" placeholder="Documentos, registros que evidencian la implementación..."></textarea>
                </div>
                <div class="form-group">
                    <label>Efectividad</label>
                    <select name="effectiveness">
                        <option value="Alta">Alta</option>
                        <option value="Media">Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observations"></textarea>
                </div>
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-primary">💾 Guardar Control</button>
                    <a href="../controles.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
