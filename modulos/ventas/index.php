<?php
/**
 * ===============================================
 * MÓDULO DE VENTAS - DASHBOARD
 * ===============================================
 * Ejemplo de implementación del middleware de control de acceso
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/middleware_acceso.php';

// ===============================================
// VALIDAR ACCESO AL MÓDULO DE VENTAS
// ===============================================
// Esta línea valida automáticamente:
// - Suscripción activa
// - Plan incluye módulo "ventas"
// - Usuario tiene permisos
// Si no cumple, muestra pantalla de bloqueo y termina
requiereModulo('ventas');

// Si llegó aquí, el usuario TIENE ACCESO ✅
$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['user_id'];
$plan_nombre = $_SESSION['plan_nombre'];
$acceso_completo = $_SESSION['modulo_acceso_completo'] ?? 1;
$limite_registros = $_SESSION['modulo_limite_registros'] ?? null;

// Obtener estadísticas del módulo
$stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_facturas,
        SUM(total) as total_ventas
    FROM facturas
    WHERE empresa_id = ?
");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Ventas - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 30px;
        }
        .plan-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .limited-access-banner {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-shopping-cart"></i> Módulo de Ventas</h1>
            <p>Plan actual: <span class="plan-badge"><?php echo htmlspecialchars($plan_nombre); ?></span></p>
            <?php if ($_SESSION['es_trial'] ?? false): ?>
                <small>⏱️ Trial - <?php echo $_SESSION['dias_trial_restantes'] ?? 0; ?> días restantes</small>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <!-- Banner de acceso limitado si aplica -->
        <?php if ($acceso_completo == 0): ?>
        <div class="limited-access-banner">
            <strong><i class="fas fa-info-circle"></i> Acceso Limitado</strong><br>
            Estás usando la versión limitada de este módulo.
            <?php if ($limite_registros): ?>
                Límite: <?php echo $limite_registros; ?> facturas por mes.
            <?php endif; ?>
            <a href="/index.php#planes">Mejora tu plan</a> para acceso completo.
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><i class="fas fa-file-invoice"></i> Total Facturas</h3>
                    <h1><?php echo number_format($stats['total_facturas'] ?? 0); ?></h1>
                    <?php if ($limite_registros && $stats['total_facturas'] >= $limite_registros): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i> Has alcanzado el límite de tu plan (<?php echo $limite_registros; ?> facturas).
                            <a href="/index.php#planes">Mejora tu plan</a> para continuar.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><i class="fas fa-dollar-sign"></i> Total Ventas</h3>
                    <h1>$<?php echo number_format($stats['total_ventas'] ?? 0, 0, ',', '.'); ?></h1>
                </div>
            </div>
        </div>

        <!-- Acciones disponibles -->
        <div class="stats-card">
            <h3><i class="fas fa-tools"></i> Acciones Disponibles</h3>
            <div class="d-flex gap-2 flex-wrap">
                <a href="cotizaciones.php" class="btn btn-primary">
                    <i class="fas fa-file-alt"></i> Cotizaciones
                </a>
                <a href="facturas.php" class="btn btn-success">
                    <i class="fas fa-file-invoice"></i> Facturas
                </a>
                <a href="clientes.php" class="btn btn-info">
                    <i class="fas fa-users"></i> Clientes
                </a>
                <?php if ($acceso_completo == 1): ?>
                    <a href="reportes.php" class="btn btn-warning">
                        <i class="fas fa-chart-bar"></i> Reportes Avanzados
                    </a>
                    <a href="dte.php" class="btn btn-secondary">
                        <i class="fas fa-file-contract"></i> Facturación Electrónica
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-lock"></i> Reportes Avanzados (Requiere upgrade)
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="/user/dashboard_user.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    /**
     * Ejemplo de validación desde JavaScript usando la API
     */
    async function validarAccesoModulo(moduloSlug) {
        try {
            const response = await fetch(`/api/validar_acceso.php?modulo=${moduloSlug}`);
            const data = await response.json();

            if (!data.tiene_acceso) {
                if (data.datos.accion_requerida === 'upgrade_plan') {
                    alert(`Este módulo requiere upgrade. Plan actual: ${data.datos.plan_actual}`);
                    window.location.href = '/index.php#planes';
                } else if (data.datos.accion_requerida === 'renovar_suscripcion') {
                    alert('Tu suscripción ha expirado');
                    window.location.href = '/index.php#planes';
                }
                return false;
            }

            // Mostrar advertencia si es trial
            if (data.datos.es_trial && data.datos.dias_restantes <= 3) {
                console.log(`⚠️ Trial expira en ${data.datos.dias_restantes} días`);
            }

            return true;
        } catch (error) {
            console.error('Error validando acceso:', error);
            return false;
        }
    }

    // Ejemplo de uso
    // validarAccesoModulo('ventas').then(tieneAcceso => {
    //     if (tieneAcceso) {
    //         console.log('✅ Acceso permitido');
    //     }
    // });
    </script>
</body>
</html>
