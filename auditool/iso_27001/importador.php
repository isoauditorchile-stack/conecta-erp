<?php
session_start();
require_once('../config/config.php');

$conn = getDBConnection();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;

$message = '';
$message_type = '';
$import_results = [];

// Procesar importación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file']) && isset($_POST['import_type'])) {
    $import_type = $_POST['import_type'];
    $file = $_FILES['import_file'];

    if ($file['error'] == 0) {
        $filename = $file['tmp_name'];

        // Leer archivo CSV o Excel (en formato CSV)
        if (($handle = fopen($filename, "r")) !== FALSE) {
            $row_count = 0;
            $success_count = 0;
            $error_count = 0;
            $errors = [];

            // Saltar encabezado
            $header = fgetcsv($handle, 10000, ",");

            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                $row_count++;

                try {
                    if ($import_type == 'activos') {
                        // Importar activos
                        // Formato: asset_id, asset_name, asset_type, asset_category, asset_owner, asset_custodian, asset_location, criticality, confidentiality, integrity, availability, description

                        if (count($data) >= 12) {
                            $sql = "INSERT INTO iso27001_assets (
                                company_id, asset_id, asset_name, asset_type, asset_category,
                                asset_owner, asset_custodian, asset_location, criticality,
                                confidentiality, integrity, availability, description,
                                created_by, created_date
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("issssssssiissi",
                                $company_id,
                                $data[0], // asset_id
                                $data[1], // asset_name
                                $data[2], // asset_type
                                $data[3], // asset_category
                                $data[4], // asset_owner
                                $data[5], // asset_custodian
                                $data[6], // asset_location
                                $data[7], // criticality
                                $data[8], // confidentiality
                                $data[9], // integrity
                                $data[10], // availability
                                $data[11], // description
                                $user_id
                            );

                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Fila $row_count: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error_count++;
                            $errors[] = "Fila $row_count: Datos incompletos";
                        }
                    }

                    elseif ($import_type == 'riesgos') {
                        // Importar riesgos
                        // Formato: risk_id, risk_name, risk_category, related_asset, threat, vulnerability, probability, impact, treatment_option, treatment_responsible

                        if (count($data) >= 10) {
                            $probability = intval($data[6]);
                            $impact = intval($data[7]);
                            $risk_score = $probability * $impact;

                            if ($risk_score >= 15) $risk_level = 'Crítico';
                            elseif ($risk_score >= 8) $risk_level = 'Alto';
                            elseif ($risk_score >= 4) $risk_level = 'Medio';
                            else $risk_level = 'Bajo';

                            $sql = "INSERT INTO iso27001_risks (
                                company_id, risk_id, risk_name, risk_category, related_asset,
                                threat, vulnerability, probability, impact, risk_score, risk_level,
                                treatment_option, treatment_responsible, treatment_status,
                                residual_probability, residual_impact, residual_score, residual_level,
                                created_by, created_date
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', 1, 1, 1, 'Bajo', ?, NOW())";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("issssssiisssi",
                                $company_id,
                                $data[0], // risk_id
                                $data[1], // risk_name
                                $data[2], // risk_category
                                $data[3], // related_asset
                                $data[4], // threat
                                $data[5], // vulnerability
                                $probability,
                                $impact,
                                $risk_score,
                                $risk_level,
                                $data[8], // treatment_option
                                $data[9], // treatment_responsible
                                $user_id
                            );

                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Fila $row_count: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error_count++;
                            $errors[] = "Fila $row_count: Datos incompletos";
                        }
                    }

                    elseif ($import_type == 'controles') {
                        // Importar controles
                        // Formato: control_id, control_name, applicable, implementation_status, responsible, effectiveness

                        if (count($data) >= 6) {
                            $sql = "INSERT INTO iso27001_controls (
                                company_id, control_id, control_name, applicable,
                                implementation_status, responsible, effectiveness,
                                created_by, created_date
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE
                                applicable = VALUES(applicable),
                                implementation_status = VALUES(implementation_status),
                                responsible = VALUES(responsible),
                                effectiveness = VALUES(effectiveness)";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("issssssi",
                                $company_id,
                                $data[0], // control_id
                                $data[1], // control_name
                                $data[2], // applicable
                                $data[3], // implementation_status
                                $data[4], // responsible
                                $data[5], // effectiveness
                                $user_id
                            );

                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Fila $row_count: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error_count++;
                            $errors[] = "Fila $row_count: Datos incompletos";
                        }
                    }

                    elseif ($import_type == 'incidentes') {
                        // Importar incidentes
                        // Formato: incident_id, incident_title, incident_date, severity, incident_type, reported_by, description

                        if (count($data) >= 7) {
                            $sql = "INSERT INTO iso27001_incidents (
                                company_id, incident_id, incident_title, incident_date, severity,
                                incident_type, reported_by, description, incident_status,
                                created_by, created_date
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Abierto', ?, NOW())";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("isssssssi",
                                $company_id,
                                $data[0], // incident_id
                                $data[1], // incident_title
                                $data[2], // incident_date
                                $data[3], // severity
                                $data[4], // incident_type
                                $data[5], // reported_by
                                $data[6], // description
                                $user_id
                            );

                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Fila $row_count: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error_count++;
                            $errors[] = "Fila $row_count: Datos incompletos";
                        }
                    }

                } catch (Exception $e) {
                    $error_count++;
                    $errors[] = "Fila $row_count: " . $e->getMessage();
                }
            }

            fclose($handle);

            $import_results = [
                'total' => $row_count,
                'success' => $success_count,
                'errors' => $error_count,
                'error_details' => $errors
            ];

            if ($error_count == 0) {
                $message = "✓ Importación exitosa: $success_count registros importados";
                $message_type = 'success';
            } else {
                $message = "⚠ Importación parcial: $success_count exitosos, $error_count errores";
                $message_type = 'warning';
            }
        } else {
            $message = "✗ Error al leer el archivo";
            $message_type = 'error';
        }
    } else {
        $message = "✗ Error al subir el archivo";
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Datos - ISO 27001 - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            color: #0066cc;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .import-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        select, input[type="file"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        select:focus {
            outline: none;
            border-color: #0066cc;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-label {
            background: #f8f9fa;
            border: 2px dashed #0066cc;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-label:hover {
            background: #e9ecef;
            border-color: #0052a3;
        }
        .file-label .icon {
            font-size: 48px;
            color: #0066cc;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-primary {
            background: #0066cc;
            color: white;
            width: 100%;
        }
        .btn-primary:hover {
            background: #0052a3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #666;
            color: white;
            margin-bottom: 20px;
        }
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: bold;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeeba;
        }
        .results-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .result-stat {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .result-stat:last-child {
            border-bottom: none;
        }
        .stat-label {
            font-weight: bold;
            color: #555;
        }
        .stat-value {
            color: #0066cc;
            font-weight: bold;
        }
        .error-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        .error-item {
            padding: 8px;
            background: #f8d7da;
            border-left: 4px solid #cc0000;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .template-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #dee2e6;
        }
        .template-card h4 {
            color: #333;
            margin-bottom: 10px;
        }
        .template-card p {
            color: #666;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .info-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .info-box h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .info-box ul {
            color: #666;
            line-height: 1.8;
            margin-left: 20px;
        }
    </style>
    <script>
        function updateFileName() {
            var input = document.getElementById('import_file');
            var label = document.querySelector('.file-label');
            if (input.files.length > 0) {
                label.innerHTML = '<div class="icon">✓</div><strong>' + input.files[0].name + '</strong><br><small>Archivo cargado - Click para cambiar</small>';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-secondary">← Volver al Dashboard ISO 27001</a>

        <div class="header">
            <h1>📤 Importador de Datos Masivos</h1>
            <p>Importe datos desde archivos CSV/Excel a la base de datos del SGSI</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($import_results)): ?>
        <div class="results-box">
            <h3 style="margin-bottom: 15px;">📊 Resultados de la Importación</h3>
            <div class="result-stat">
                <span class="stat-label">Total de filas procesadas:</span>
                <span class="stat-value"><?php echo $import_results['total']; ?></span>
            </div>
            <div class="result-stat">
                <span class="stat-label">Registros importados exitosamente:</span>
                <span class="stat-value" style="color: #00994d;"><?php echo $import_results['success']; ?></span>
            </div>
            <div class="result-stat">
                <span class="stat-label">Errores encontrados:</span>
                <span class="stat-value" style="color: #cc0000;"><?php echo $import_results['errors']; ?></span>
            </div>

            <?php if (!empty($import_results['error_details'])): ?>
            <div class="error-list">
                <strong>Detalle de errores:</strong>
                <?php foreach ($import_results['error_details'] as $error): ?>
                <div class="error-item"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="import-card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Tipo de Importación</label>
                    <select name="import_type" required>
                        <option value="">Seleccione el tipo de datos...</option>
                        <option value="activos">🗂️ Activos de Información</option>
                        <option value="riesgos">⚠️ Análisis de Riesgos</option>
                        <option value="controles">⚙️ Controles del Anexo A</option>
                        <option value="incidentes">🚨 Incidentes de Seguridad</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Archivo CSV/Excel</label>
                    <div class="file-input-wrapper">
                        <label class="file-label" for="import_file">
                            <div class="icon">📁</div>
                            <strong>Click para seleccionar archivo</strong>
                            <br>
                            <small>Formatos aceptados: CSV, XLS, XLSX</small>
                        </label>
                        <input type="file" id="import_file" name="import_file" accept=".csv,.xls,.xlsx" required onchange="updateFileName()">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    📥 Importar Datos
                </button>
            </form>
        </div>

        <div class="info-box">
            <h3>ℹ️ Instrucciones de Importación</h3>
            <ul>
                <li><strong>Formato de Archivo:</strong> El archivo debe estar en formato CSV (valores separados por comas)</li>
                <li><strong>Primera Fila:</strong> Debe contener los encabezados de las columnas</li>
                <li><strong>Codificación:</strong> Use UTF-8 para evitar problemas con caracteres especiales</li>
                <li><strong>Campos Requeridos:</strong> Cada tipo de importación tiene campos obligatorios específicos</li>
                <li><strong>Validación:</strong> El sistema validará los datos antes de insertarlos</li>
            </ul>

            <h3 style="margin-top: 25px;">📋 Formatos de Columnas por Tipo</h3>

            <div class="templates-grid">
                <div class="template-card">
                    <h4>🗂️ Activos</h4>
                    <p style="text-align: left; font-size: 12px;">
                        1. asset_id<br>
                        2. asset_name<br>
                        3. asset_type<br>
                        4. asset_category<br>
                        5. asset_owner<br>
                        6. asset_custodian<br>
                        7. asset_location<br>
                        8. criticality<br>
                        9. confidentiality (1-5)<br>
                        10. integrity (1-5)<br>
                        11. availability (1-5)<br>
                        12. description
                    </p>
                </div>

                <div class="template-card">
                    <h4>⚠️ Riesgos</h4>
                    <p style="text-align: left; font-size: 12px;">
                        1. risk_id<br>
                        2. risk_name<br>
                        3. risk_category<br>
                        4. related_asset<br>
                        5. threat<br>
                        6. vulnerability<br>
                        7. probability (1-5)<br>
                        8. impact (1-5)<br>
                        9. treatment_option<br>
                        10. treatment_responsible
                    </p>
                </div>

                <div class="template-card">
                    <h4>⚙️ Controles</h4>
                    <p style="text-align: left; font-size: 12px;">
                        1. control_id (ej: 5.1)<br>
                        2. control_name<br>
                        3. applicable (Si/No)<br>
                        4. implementation_status<br>
                        5. responsible<br>
                        6. effectiveness
                    </p>
                </div>

                <div class="template-card">
                    <h4>🚨 Incidentes</h4>
                    <p style="text-align: left; font-size: 12px;">
                        1. incident_id<br>
                        2. incident_title<br>
                        3. incident_date (YYYY-MM-DD)<br>
                        4. severity<br>
                        5. incident_type<br>
                        6. reported_by<br>
                        7. description
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
