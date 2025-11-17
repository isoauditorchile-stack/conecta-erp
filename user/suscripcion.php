<?php
session_start();
require_once '../includes/config.php';

// Verificar autenticación
requireLogin();

$message = '';
$message_type = '';

$usuario_id = $_SESSION['user_id'];

// Obtener información del usuario y suscripción
$stmt = $conn->prepare("SELECT u.*, s.*, p.nombre_plan as plan_nombre, p.precio_mensual, p.descripcion as plan_descripcion, p.caracteristicas as plan_caracteristicas
                        FROM usuarios u
                        LEFT JOIN suscripciones s ON s.usuario_id = u.id AND s.estado IN ('trial', 'activo')
                        LEFT JOIN planes p ON u.plan_id = p.id
                        WHERE u.id = ?
                        ORDER BY s.id DESC
                        LIMIT 1");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calcular días restantes
$dias_restantes = 0;
if ($info['en_periodo_prueba'] == 1) {
    $dias_restantes = getDiasRestantesTrial($info['fecha_fin_trial']);
}

// Obtener todos los planes disponibles
$planes = $conn->query("SELECT * FROM planes WHERE id != 4 ORDER BY precio_mensual ASC");

// Obtener historial de pagos
$pagos_query = "SELECT * FROM pagos WHERE usuario_id = ? ORDER BY fecha_creacion DESC LIMIT 10";
$stmt = $conn->prepare($pagos_query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$pagos = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Suscripción - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 70px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h2 .text {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .sidebar-menu a i {
            font-size: 1.2rem;
            min-width: 30px;
        }

        .sidebar-menu a span {
            margin-left: 10px;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        .topbar {
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .sidebar.collapsed ~ .topbar {
            left: var(--sidebar-collapsed-width);
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            transition: all 0.3s ease;
            min-height: calc(100vh - var(--topbar-height));
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .subscription-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .current-plan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .trial-alert {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .plan-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .plan-card.featured {
            border-color: #667eea;
            border-width: 3px;
            background: linear-gradient(to bottom, rgba(102, 126, 234, 0.05), white);
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #667eea;
            margin: 20px 0;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            text-align: left;
        }

        .plan-features li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .btn-select-plan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-select-plan:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-rocket"></i> <span class="text">CONECTA ERP</span></h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard_user.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="perfil.php"><i class="fas fa-user"></i> <span>Mi Perfil</span></a></li>
            <li><a href="mi-empresa.php"><i class="fas fa-building"></i> <span>Mi Empresa</span></a></li>
            <li><a href="suscripcion.php" class="active"><i class="fas fa-credit-card"></i> <span>Suscripción</span></a></li>
            <li><a href="notificaciones.php"><i class="fas fa-bell"></i> <span>Notificaciones</span></a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> <span>Configuración</span></a></li>
            <li><a href="soporte.php"><i class="fas fa-headset"></i> <span>Soporte</span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="d-flex align-items-center gap-3">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></div>
                <div style="font-size: 0.85rem; color: #6c757d;">Usuario</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 style="margin-bottom: 30px; color: #333;">
            <i class="fas fa-credit-card"></i> Mi Suscripción
        </h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Trial Alert -->
        <?php if ($info['en_periodo_prueba'] == 1): ?>
            <div class="trial-alert">
                <div>
                    <h3 style="margin: 0;"><i class="fas fa-clock"></i> Periodo de Prueba</h3>
                    <p style="margin: 5px 0 0 0;">Te quedan <strong><?php echo $dias_restantes; ?> días</strong> de prueba gratuita</p>
                </div>
                <div>
                    <?php if ($dias_restantes <= 3): ?>
                        <strong style="font-size: 1.2rem;">¡Actualiza Ahora!</strong>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Plan Actual -->
        <div class="current-plan">
            <h2 style="margin-bottom: 20px;"><i class="fas fa-star"></i> Tu Plan Actual</h2>
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 style="font-size: 2rem; margin: 0;"><?php echo htmlspecialchars($info['plan_nombre'] ?? 'Sin Plan'); ?></h3>
                    <p style="margin: 10px 0; opacity: 0.9;">
                        <?php echo htmlspecialchars($info['plan_descripcion'] ?? ''); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div style="font-size: 3rem; font-weight: 800;">
                        <?php echo formatCurrency($info['precio_mensual'] ?? 0); ?>
                    </div>
                    <div style="font-size: 1.2rem; opacity: 0.9;">por mes</div>
                </div>
            </div>

            <?php if ($info['en_periodo_prueba'] == 1): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div style="font-size: 1.5rem; font-weight: 700;"><?php echo formatDate($info['fecha_inicio_trial'], 'd/m/Y'); ?></div>
                            <div style="opacity: 0.9;">Inicio Trial</div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $dias_restantes; ?> días</div>
                            <div style="opacity: 0.9;">Restantes</div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size: 1.5rem; font-weight: 700;"><?php echo formatDate($info['fecha_fin_trial'], 'd/m/Y'); ?></div>
                            <div style="opacity: 0.9;">Fin Trial</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <div class="row text-center">
                        <div class="col-md-6">
                            <div style="font-size: 1.5rem; font-weight: 700;">
                                <span class="badge bg-success" style="font-size: 1.2rem;">ACTIVO</span>
                            </div>
                            <div style="opacity: 0.9;">Estado de Suscripción</div>
                        </div>
                        <div class="col-md-6">
                            <div style="font-size: 1.5rem; font-weight: 700;">
                                <?php echo formatDate($info['fecha_fin'] ?? date('Y-m-d'), 'd/m/Y'); ?>
                            </div>
                            <div style="opacity: 0.9;">Próxima Renovación</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Planes Disponibles -->
        <div class="subscription-card">
            <div class="section-title">
                <i class="fas fa-rocket"></i> Planes Disponibles
            </div>

            <div class="row g-4">
                <?php while ($plan = $planes->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="plan-card <?php echo $plan['id'] == 2 ? 'featured' : ''; ?>">
                            <?php if ($plan['id'] == 2): ?>
                                <div style="background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; display: inline-block; margin-bottom: 10px; font-size: 0.85rem; font-weight: 600;">
                                    MÁS POPULAR
                                </div>
                            <?php endif; ?>

                            <div class="plan-name"><?php echo htmlspecialchars($plan['nombre_plan']); ?></div>
                            <div class="plan-price"><?php echo formatCurrency($plan['precio_mensual']); ?></div>
                            <p style="color: #6c757d; margin-bottom: 20px;">
                                <?php echo htmlspecialchars($plan['descripcion']); ?>
                            </p>

                            <?php if ($plan['caracteristicas']): ?>
                                <ul class="plan-features">
                                    <?php
                                    $caracteristicas = explode("\n", $plan['caracteristicas']);
                                    foreach ($caracteristicas as $caract):
                                        if (trim($caract)):
                                    ?>
                                        <li><i class="fas fa-check text-success"></i> <?php echo htmlspecialchars(trim($caract)); ?></li>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </ul>
                            <?php endif; ?>

                            <?php if ($plan['id'] == $info['plan_id']): ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-check"></i> Plan Actual
                                </button>
                            <?php else: ?>
                                <button class="btn-select-plan" onclick="seleccionarPlan(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['nombre_plan']); ?>')">
                                    <i class="fas fa-arrow-up"></i> Seleccionar Plan
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Historial de Pagos -->
        <div class="subscription-card">
            <div class="section-title">
                <i class="fas fa-history"></i> Historial de Pagos
            </div>

            <?php if ($pagos->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pago = $pagos->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $pago['id']; ?></td>
                                    <td><strong><?php echo $pago['moneda']; ?> <?php echo number_format($pago['monto'], 0, ',', '.'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($pago['metodo_pago'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = match($pago['estado']) {
                                            'aprobado' => 'success',
                                            'pendiente' => 'warning',
                                            'rechazado' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst($pago['estado']); ?></span>
                                    </td>
                                    <td><?php echo formatDate($pago['fecha_creacion'], 'd/m/Y H:i'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay pagos registrados aún
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }

        function seleccionarPlan(planId, planNombre) {
            if (confirm('¿Deseas cambiar al plan ' + planNombre + '?')) {
                alert('Funcionalidad de pago en desarrollo. El super admin puede cambiar tu plan desde el panel de administración.');
            }
        }
    </script>
</body>
</html>
