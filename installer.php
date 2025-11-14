<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador CONECTA ERP - Sistema Completo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .installer-container {
            max-width: 900px;
            margin: 50px auto;
        }

        .installer-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .installer-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .installer-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .installer-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .installer-body {
            padding: 40px;
        }

        .progress-step {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .progress-step.active {
            border-left-color: var(--primary-color);
            background: #eff6ff;
        }

        .progress-step.success {
            border-left-color: var(--success-color);
            background: #f0fdf4;
        }

        .progress-step.error {
            border-left-color: var(--danger-color);
            background: #fef2f2;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .progress-step.active .step-icon {
            background: var(--primary-color);
            color: white;
        }

        .progress-step.success .step-icon {
            background: var(--success-color);
            color: white;
        }

        .progress-step.error .step-icon {
            background: var(--danger-color);
            color: white;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .step-description {
            color: #64748b;
            font-size: 0.95rem;
        }

        .btn-install {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 15px 50px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .spinner-border-sm {
            width: 1.2rem;
            height: 1.2rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }

        .feature-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li i {
            color: var(--success-color);
            margin-right: 15px;
            font-size: 1.3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-radius: 12px;
            color: white;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-card">
            <div class="installer-header">
                <h1><i class="bi bi-box-seam"></i> CONECTA ERP</h1>
                <p>Instalador Completo del Sistema - Versión 1.0.0</p>
            </div>

            <div class="installer-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Bienvenido al Instalador de CONECTA ERP</strong><br>
                    Este instalador creará automáticamente todas las tablas necesarias para el funcionamiento completo del sistema.
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">14</div>
                        <div class="stat-label">Módulos Principales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">106</div>
                        <div class="stat-label">Submódulos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">9</div>
                        <div class="stat-label">Idiomas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">9</div>
                        <div class="stat-label">Países</div>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Características del Sistema:</h5>
                <ul class="feature-list">
                    <li><i class="bi bi-check-circle-fill"></i> Sistema Multiusuario</li>
                    <li><i class="bi bi-check-circle-fill"></i> Sistema Multiempresa</li>
                    <li><i class="bi bi-check-circle-fill"></i> Sistema Multipaís y Multimoneda</li>
                    <li><i class="bi bi-check-circle-fill"></i> Sistema Multiidioma (9 idiomas)</li>
                    <li><i class="bi bi-check-circle-fill"></i> Integración con SII y Previred (Chile)</li>
                    <li><i class="bi bi-check-circle-fill"></i> Actualización automática UF, USD, UTM</li>
                    <li><i class="bi bi-check-circle-fill"></i> Sistema de Planes y Facturación</li>
                    <li><i class="bi bi-check-circle-fill"></i> Dashboard Administrativo Completo</li>
                    <li><i class="bi bi-check-circle-fill"></i> 14 días de prueba gratuita</li>
                </ul>

                <div id="installation-progress">
                    <!-- Los pasos se llenarán dinámicamente -->
                </div>

                <div class="text-center mt-4">
                    <button id="btn-start-install" class="btn btn-install" onclick="startInstallation()">
                        <i class="bi bi-download me-2"></i> Iniciar Instalación
                    </button>
                </div>

                <div id="installation-result" class="mt-4" style="display: none;">
                    <!-- Resultado de la instalación -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function startInstallation() {
            const btn = document.getElementById('btn-start-install');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Instalando...';

            try {
                const response = await fetch('?action=install', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('installation-result').innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>¡Instalación completada con éxito!</strong><br>
                            ${result.message}<br><br>
                            <strong>Credenciales de Administrador:</strong><br>
                            Email: auditorexchile@gmail.com<br>
                            Usuario: auditorex chile<br>
                            Contraseña: admin123 (Cámbiala después del primer login)<br><br>
                            <a href="login.php" class="btn btn-success mt-2">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Ir al Login
                            </a>
                        </div>
                    `;
                } else {
                    document.getElementById('installation-result').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Error en la instalación:</strong><br>
                            ${result.message}
                        </div>
                    `;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Reintentar Instalación';
                }

                document.getElementById('installation-result').style.display = 'block';

            } catch (error) {
                document.getElementById('installation-result').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
                document.getElementById('installation-result').style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Reintentar Instalación';
            }
        }
    </script>
</body>
</html>

<?php
// Procesar instalación
if (isset($_GET['action']) && $_GET['action'] === 'install') {
    header('Content-Type: application/json');

    try {
        require_once __DIR__ . '/includes/config.php';

        $db = Database::getInstance()->getConnection();

        // Deshabilitar foreign key checks temporalmente
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Iniciar transacción
        $db->beginTransaction();

        // Incluir el script SQL completo
        $sqlFile = __DIR__ . '/database/install_complete.sql';

        if (!file_exists($sqlFile)) {
            // Crear el archivo SQL si no existe
            createCompleteInstallSQL();
        }

        $sql = file_get_contents($sqlFile);

        // Ejecutar el SQL por bloques
        $statements = explode(';', $sql);
        $executed = 0;

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $db->exec($statement);
                $executed++;
            }
        }

        // Commit
        $db->commit();

        // Rehabilitar foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        echo json_encode([
            'success' => true,
            'message' => "Instalación completada. Se ejecutaron $executed instrucciones SQL correctamente."
        ]);

    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }

        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }

    exit;
}

function createCompleteInstallSQL() {
    // Esta función será llamada para crear el SQL completo
    // Por ahora retorna true, el SQL será creado en el siguiente archivo
    return true;
}
?>
