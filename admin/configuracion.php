<?php
/**
 * CONFIGURACIÓN SISTEMA - CONECTA ERP
 * Configuración completa del sistema
 */
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Verificar que sea super admin
if (!isSuperAdmin()) {
    header('Location: /user/dashboard_user.php');
    exit;
}

$success = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_config'])) {
        try {
            foreach ($_POST as $key => $value) {
                if ($key === 'guardar_config' || $key === 'csrf_token') continue;

                // Actualizar o insertar configuración
                $stmt = $conn->prepare("INSERT INTO configuracion_sistema (clave, valor, updated_at)
                                       VALUES (?, ?, NOW())
                                       ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()");
                $stmt->bind_param("ss", $key, $value);
                $stmt->execute();
            }

            $success = 'Configuración guardada correctamente';
            logAuditoria('actualizar_configuracion', 'configuracion_sistema', null, null, null, 'Configuración del sistema actualizada');

        } catch (Exception $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

// Cargar configuración actual
$config = [];
$result = $conn->query("SELECT * FROM configuracion_sistema ORDER BY grupo, clave");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $config[$row['clave']] = $row['valor'];
    }
}

// Si no hay configuración, crear valores por defecto
if (empty($config)) {
    $defaults = [
        'nombre_empresa' => 'CONECTA ERP',
        'email_sistema' => 'soporte@conectaerp.com',
        'moneda_defecto' => 'CLP',
        'idioma_defecto' => 'es',
        'zona_horaria' => 'America/Santiago',
        'sii_ambiente' => 'certificacion',
        'previred_activo' => '1',
        'mantenimiento' => '0'
    ];

    foreach ($defaults as $key => $value) {
        $config[$key] = $value;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }
        body {
            background: #f5f7fa;
        }
        .header-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .config-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .config-card .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1rem 1.5rem;
        }
    </style>
</head>
<body>
    <div class="header-gradient">
        <div class="container">
            <h1><i class="fas fa-cog"></i> Configuración del Sistema</h1>
            <p class="mb-0">Panel de administración global</p>
        </div>
    </div>

    <div class="container pb-5">
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <!-- Configuración General -->
            <div class="card config-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-building"></i> Configuración General</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre del Sistema</label>
                            <input type="text" name="nombre_empresa" class="form-control"
                                   value="<?= htmlspecialchars($config['nombre_empresa'] ?? 'CONECTA ERP') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email del Sistema</label>
                            <input type="email" name="email_sistema" class="form-control"
                                   value="<?= htmlspecialchars($config['email_sistema'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Moneda por Defecto</label>
                            <select name="moneda_defecto" class="form-select">
                                <option value="CLP" <?= ($config['moneda_defecto'] ?? '') === 'CLP' ? 'selected' : '' ?>>CLP - Peso Chileno</option>
                                <option value="USD" <?= ($config['moneda_defecto'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - Dólar</option>
                                <option value="EUR" <?= ($config['moneda_defecto'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Idioma por Defecto</label>
                            <select name="idioma_defecto" class="form-select">
                                <option value="es" <?= ($config['idioma_defecto'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                                <option value="en" <?= ($config['idioma_defecto'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="pt" <?= ($config['idioma_defecto'] ?? '') === 'pt' ? 'selected' : '' ?>>Português</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Zona Horaria</label>
                            <select name="zona_horaria" class="form-select">
                                <option value="America/Santiago" <?= ($config['zona_horaria'] ?? '') === 'America/Santiago' ? 'selected' : '' ?>>Santiago</option>
                                <option value="America/Argentina/Buenos_Aires" <?= ($config['zona_horaria'] ?? '') === 'America/Argentina/Buenos_Aires' ? 'selected' : '' ?>>Buenos Aires</option>
                                <option value="America/Sao_Paulo" <?= ($config['zona_horaria'] ?? '') === 'America/Sao_Paulo' ? 'selected' : '' ?>>São Paulo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SII -->
            <div class="card config-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Integración SII Chile</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ambiente SII</label>
                            <select name="sii_ambiente" class="form-select">
                                <option value="certificacion" <?= ($config['sii_ambiente'] ?? '') === 'certificacion' ? 'selected' : '' ?>>Certificación (Pruebas)</option>
                                <option value="produccion" <?= ($config['sii_ambiente'] ?? '') === 'produccion' ? 'selected' : '' ?>>Producción (Real)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado SII</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="sii_activo" value="1"
                                       <?= ($config['sii_activo'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label">Integración SII Activada</label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> El sistema se conecta AUTOMÁTICAMENTE al SII para descargar folios.
                        Configure los certificados digitales en la sección de empresas.
                    </div>
                </div>
            </div>

            <!-- Previred -->
            <div class="card config-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-usd"></i> Integración Previred</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="previred_activo" value="1"
                               <?= ($config['previred_activo'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label">Módulo Previred Activado</label>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> El sistema genera archivos .REM automáticamente con las cotizaciones.
                        Los cálculos incluyen AFP, Salud, SIS, AFC según legislación 2025.
                    </div>
                </div>
            </div>

            <!-- Sistema -->
            <div class="card config-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Mantenimiento</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="mantenimiento" value="1"
                               <?= ($config['mantenimiento'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label">Modo Mantenimiento (bloquea acceso a usuarios)</label>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" name="guardar_config" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-save"></i> Guardar Configuración
                </button>
                <a href="dashboard_admin.php" class="btn btn-secondary btn-lg px-5">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../user/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
