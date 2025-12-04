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

    $sql = "INSERT INTO iso27001_assets (
        company_id, asset_id, asset_name, asset_type, asset_category,
        asset_owner, asset_custodian, asset_location, criticality,
        confidentiality, integrity, availability,
        description, dependencies, related_processes,
        hardware_details, software_details, data_classification,
        created_by, created_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssiiissssssi",
        $company_id,
        $_POST['asset_id'],
        $_POST['asset_name'],
        $_POST['asset_type'],
        $_POST['asset_category'],
        $_POST['asset_owner'],
        $_POST['asset_custodian'],
        $_POST['asset_location'],
        $_POST['criticality'],
        $_POST['confidentiality'],
        $_POST['integrity'],
        $_POST['availability'],
        $_POST['description'],
        $_POST['dependencies'],
        $_POST['related_processes'],
        $_POST['hardware_details'],
        $_POST['software_details'],
        $_POST['data_classification'],
        $user_id
    );

    if ($stmt->execute()) {
        $message = '✓ Activo registrado exitosamente';
        $message_type = 'success';
    } else {
        $message = '✗ Error al registrar activo: ' . $stmt->error;
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
    <title>Formulario Online - Inventario de Activos - ISO 27001</title>
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
            border-bottom: 3px solid #00994d;
        }
        .form-header h1 {
            color: #00994d;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .form-header p {
            color: #666;
            font-size: 14px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .section-title {
            background: #00994d;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
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
        label .required {
            color: #cc0000;
            margin-left: 3px;
        }
        input[type="text"],
        input[type="email"],
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
            border-color: #00994d;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .cia-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .cia-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        .cia-box label {
            margin-bottom: 10px;
        }
        .cia-select {
            width: 100%;
            padding: 8px;
            border: 2px solid #00994d;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
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
        .btn-primary {
            background: #00994d;
            color: white;
        }
        .btn-primary:hover {
            background: #007a3d;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #666;
            color: white;
        }
        .btn-secondary:hover {
            background: #555;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
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
                <h1>🗂️ Formulario Online - Inventario de Activos</h1>
                <p>Registre un nuevo activo de información en el inventario del SGSI</p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="create">

                <!-- Identificación del Activo -->
                <div class="form-section">
                    <div class="section-title">📌 Identificación del Activo</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Código del Activo <span class="required">*</span></label>
                            <input type="text" name="asset_id" required placeholder="Ej: ACT-001">
                            <div class="help-text">Código único del activo</div>
                        </div>

                        <div class="form-group">
                            <label>Nombre del Activo <span class="required">*</span></label>
                            <input type="text" name="asset_name" required placeholder="Ej: Servidor Base de Datos">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Tipo de Activo <span class="required">*</span></label>
                            <select name="asset_type" required>
                                <option value="">Seleccione...</option>
                                <option value="Hardware">Hardware</option>
                                <option value="Software">Software</option>
                                <option value="Datos">Datos/Información</option>
                                <option value="Servicios">Servicios</option>
                                <option value="Personas">Personas</option>
                                <option value="Instalaciones">Instalaciones</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Categoría <span class="required">*</span></label>
                            <select name="asset_category" required>
                                <option value="">Seleccione...</option>
                                <option value="Servidores">Servidores</option>
                                <option value="Equipos de Usuario">Equipos de Usuario</option>
                                <option value="Redes">Redes y Comunicaciones</option>
                                <option value="Aplicaciones">Aplicaciones</option>
                                <option value="Bases de Datos">Bases de Datos</option>
                                <option value="Documentos">Documentos</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Responsabilidades -->
                <div class="form-section">
                    <div class="section-title">👤 Responsabilidades</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Propietario del Activo <span class="required">*</span></label>
                            <input type="text" name="asset_owner" required placeholder="Nombre completo">
                            <div class="help-text">Responsable del activo</div>
                        </div>

                        <div class="form-group">
                            <label>Custodio del Activo</label>
                            <input type="text" name="asset_custodian" placeholder="Nombre completo">
                            <div class="help-text">Quien administra el activo</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ubicación <span class="required">*</span></label>
                        <input type="text" name="asset_location" required placeholder="Ej: Centro de Datos - Piso 2">
                    </div>
                </div>

                <!-- Clasificación CIA -->
                <div class="form-section">
                    <div class="section-title">🔒 Clasificación de Seguridad (CIA)</div>

                    <div class="form-group">
                        <label>Criticidad del Activo <span class="required">*</span></label>
                        <select name="criticality" required>
                            <option value="">Seleccione...</option>
                            <option value="Crítico">Crítico - Impacto severo si falla</option>
                            <option value="Alto">Alto - Impacto significativo</option>
                            <option value="Medio">Medio - Impacto moderado</option>
                            <option value="Bajo">Bajo - Impacto menor</option>
                        </select>
                    </div>

                    <div class="cia-grid">
                        <div class="cia-box">
                            <label>Confidencialidad</label>
                            <select name="confidentiality" class="cia-select" required>
                                <option value="1">1 - Pública</option>
                                <option value="2">2 - Interna</option>
                                <option value="3">3 - Confidencial</option>
                                <option value="4">4 - Muy Confidencial</option>
                                <option value="5">5 - Estrictamente Confidencial</option>
                            </select>
                        </div>

                        <div class="cia-box">
                            <label>Integridad</label>
                            <select name="integrity" class="cia-select" required>
                                <option value="1">1 - Baja</option>
                                <option value="2">2 - Media-Baja</option>
                                <option value="3">3 - Media</option>
                                <option value="4">4 - Media-Alta</option>
                                <option value="5">5 - Alta</option>
                            </select>
                        </div>

                        <div class="cia-box">
                            <label>Disponibilidad</label>
                            <select name="availability" class="cia-select" required>
                                <option value="1">1 - Baja</option>
                                <option value="2">2 - Media-Baja</option>
                                <option value="3">3 - Media</option>
                                <option value="4">4 - Media-Alta</option>
                                <option value="5">5 - Alta</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Detalles Adicionales -->
                <div class="form-section">
                    <div class="section-title">📝 Detalles Adicionales</div>

                    <div class="form-group">
                        <label>Descripción del Activo</label>
                        <textarea name="description" placeholder="Descripción detallada del activo..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Dependencias</label>
                        <textarea name="dependencies" placeholder="Otros activos de los que depende..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Procesos Relacionados</label>
                        <textarea name="related_processes" placeholder="Procesos de negocio que utilizan este activo..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Detalles de Hardware</label>
                            <input type="text" name="hardware_details" placeholder="Marca, modelo, especificaciones...">
                        </div>

                        <div class="form-group">
                            <label>Detalles de Software</label>
                            <input type="text" name="software_details" placeholder="Versión, licencia...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Clasificación de Datos</label>
                        <select name="data_classification">
                            <option value="">Seleccione si aplica...</option>
                            <option value="Pública">Pública</option>
                            <option value="Interna">Interna</option>
                            <option value="Confidencial">Confidencial</option>
                            <option value="Estrictamente Confidencial">Estrictamente Confidencial</option>
                        </select>
                    </div>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">💾 Guardar Activo</button>
                    <a href="../activos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <div style="text-align: center; color: white; margin-top: 20px;">
            <a href="../activos.php" style="color: white; text-decoration: none;">← Volver al Inventario de Activos</a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
