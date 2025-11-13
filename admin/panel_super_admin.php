<?php
/**
 * CONECTA ERP - Panel de Super Administrador
 * Solo para: auditorexchile@gmail.com
 * Sistema de aprobación manual de usuarios y gestión de pagos
 */

require_once __DIR__ . '/../includes/config.php';
initSession();

// Verificar que sea el super admin
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'auditorexchile@gmail.com') {
    header('Location: /index.php');
    exit;
}

$db = Database::getInstance();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'aprobar_usuario') {
        $user_id = (int)$_POST['user_id'];
        $notas = trim($_POST['notas'] ?? '');

        try {
            $db->getConnection()->beginTransaction();

            // Actualizar usuario
            $db->update(
                "UPDATE users SET status = 'trial', approved_by_admin = 1, requires_approval = 0 WHERE id = ?",
                [$user_id]
            );

            // Registrar aprobación
            $db->update(
                "UPDATE aprobaciones_usuario SET estado = 'aprobado', fecha_decision = NOW(), admin_id = ?, notas_admin = ? WHERE user_id = ? AND estado = 'pendiente'",
                [$_SESSION['user_id'], $notas, $user_id]
            );

            // Crear suscripción de trial
            $plan = $db->fetchOne("SELECT * FROM planes WHERE codigo = 'TRIAL'");
            $fecha_fin_trial = date('Y-m-d H:i:s', strtotime('+14 days'));

            $suscripcion_id = $db->insert(
                "INSERT INTO suscripciones (
                    user_id, company_id, plan_id, estado, fecha_inicio, fecha_fin_trial,
                    dias_trial, es_trial, precio_pactado, moneda, aprobado_por_admin,
                    admin_aprobador_id, fecha_aprobacion
                ) VALUES (?, ?, ?, 'trial', NOW(), ?, 14, 1, 0, 'USD', 1, ?, NOW())",
                [
                    $user_id,
                    $db->fetchOne("SELECT company_id FROM users WHERE id = ?", [$user_id])['company_id'],
                    $plan['id'],
                    $fecha_fin_trial,
                    $_SESSION['user_id']
                ]
            );

            // Actualizar trial_ends_at en users
            $db->update("UPDATE users SET trial_ends_at = ? WHERE id = ?", [$fecha_fin_trial, $user_id]);

            // Log actividad
            logActivity($_SESSION['user_id'], 'approve_user', "Usuario ID $user_id aprobado", 'admin');

            $db->getConnection()->commit();

            showAlert('Usuario aprobado exitosamente. Trial de 14 días iniciado.', 'success');
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            showAlert('Error al aprobar usuario: ' . $e->getMessage(), 'error');
        }
    }

    if ($action === 'rechazar_usuario') {
        $user_id = (int)$_POST['user_id'];
        $razon = trim($_POST['razon'] ?? 'No especificada');

        try {
            $db->getConnection()->beginTransaction();

            // Actualizar usuario
            $db->update(
                "UPDATE users SET status = 'rejected' WHERE id = ?",
                [$user_id]
            );

            // Registrar rechazo
            $db->update(
                "UPDATE aprobaciones_usuario SET estado = 'rechazado', fecha_decision = NOW(), admin_id = ?, razon_rechazo = ? WHERE user_id = ? AND estado = 'pendiente'",
                [$_SESSION['user_id'], $razon, $user_id]
            );

            // Log actividad
            logActivity($_SESSION['user_id'], 'reject_user', "Usuario ID $user_id rechazado: $razon", 'admin');

            $db->getConnection()->commit();

            showAlert('Usuario rechazado.', 'success');
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            showAlert('Error al rechazar usuario: ' . $e->getMessage(), 'error');
        }
    }

    if ($action === 'verificar_pago') {
        $pago_id = (int)$_POST['pago_id'];
        $notas = trim($_POST['notas'] ?? '');

        try {
            $pago = $db->fetchOne("SELECT * FROM pagos WHERE id = ?", [$pago_id]);

            $db->getConnection()->beginTransaction();

            // Verificar pago
            $db->update(
                "UPDATE pagos SET estado = 'completado', fecha_verificacion = NOW(), verificado_por_admin = 1, admin_verificador_id = ?, notas_admin = ? WHERE id = ?",
                [$_SESSION['user_id'], $notas, $pago_id]
            );

            // Activar suscripción
            $fecha_inicio = date('Y-m-d H:i:s');
            $suscripcion = $db->fetchOne("SELECT * FROM suscripciones WHERE id = ?", [$pago['suscripcion_id']]);

            if ($suscripcion['ciclo_pago'] === 'mensual') {
                $fecha_fin = date('Y-m-d H:i:s', strtotime('+30 days'));
            } else {
                $fecha_fin = date('Y-m-d H:i:s', strtotime('+365 days'));
            }

            $db->update(
                "UPDATE suscripciones SET estado = 'activa', es_trial = 0, fecha_inicio = ?, fecha_fin = ? WHERE id = ?",
                [$fecha_inicio, $fecha_fin, $pago['suscripcion_id']]
            );

            // Actualizar usuario
            $db->update(
                "UPDATE users SET status = 'active', trial_ends_at = NULL WHERE id = ?",
                [$pago['user_id']]
            );

            logActivity($_SESSION['user_id'], 'verify_payment', "Pago ID $pago_id verificado", 'admin');

            $db->getConnection()->commit();

            showAlert('Pago verificado y suscripción activada.', 'success');
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            showAlert('Error al verificar pago: ' . $e->getMessage(), 'error');
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Obtener estadísticas
$stats = [
    'usuarios_pendientes' => $db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'pending_approval'")['total'] ?? 0,
    'usuarios_trial' => $db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'trial'")['total'] ?? 0,
    'usuarios_activos' => $db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'active'")['total'] ?? 0,
    'pagos_pendientes' => $db->fetchOne("SELECT COUNT(*) as total FROM pagos WHERE estado = 'pendiente'")['total'] ?? 0,
    'ingresos_mes' => $db->fetchOne("SELECT COALESCE(SUM(monto), 0) as total FROM pagos WHERE estado = 'completado' AND MONTH(fecha_pago) = MONTH(NOW())")['total'] ?? 0
];

// Usuarios pendientes de aprobación
$usuarios_pendientes = $db->fetchAll("
    SELECT u.*, c.company_name, c.tax_id, c.country_id, co.name_es as country_name,
           a.fecha_solicitud, a.ip_registro
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    LEFT JOIN countries co ON c.country_id = co.id
    LEFT JOIN aprobaciones_usuario a ON u.id = a.user_id AND a.estado = 'pendiente'
    WHERE u.status = 'pending_approval'
    ORDER BY u.created_at DESC
");

// Usuarios en trial próximos a vencer
$usuarios_trial = $db->fetchAll("
    SELECT u.*, c.company_name,
           DATEDIFF(u.trial_ends_at, NOW()) as dias_restantes
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    WHERE u.status = 'trial' AND u.trial_ends_at IS NOT NULL
    ORDER BY u.trial_ends_at ASC
    LIMIT 20
");

// Pagos pendientes de verificación
$pagos_pendientes = $db->fetchAll("
    SELECT p.*, u.firstname, u.lastname, u.email, c.company_name, pl.nombre_es as plan_nombre
    FROM pagos p
    INNER JOIN users u ON p.user_id = u.id
    LEFT JOIN companies c ON p.company_id = c.id
    INNER JOIN planes pl ON p.plan_id = pl.id
    WHERE p.estado = 'pendiente'
    ORDER BY p.created_at DESC
");

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - CONECTA ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/global.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 12px; color: white; }
        .stat-card h3 { font-size: 2.5rem; margin: 0.5rem 0; }
        .stat-card p { opacity: 0.9; }
        .section { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .section h2 { margin-bottom: 1.5rem; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        table td { padding: 1rem; border-bottom: 1px solid #dee2e6; }
        table tr:hover { background: #f8f9fa; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .dias-restantes { font-weight: 700; font-size: 1.1rem; }
        .dias-restantes.critico { color: #dc3545; }
        .dias-restantes.advertencia { color: #ffc107; }
        .dias-restantes.ok { color: #28a745; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-crown"></i>
                <span>SUPER ADMIN</span>
            </div>
            <nav class="menu">
                <a href="#pendientes" class="menu-item active">
                    <i class="fas fa-user-clock"></i> Usuarios Pendientes
                </a>
                <a href="#trial" class="menu-item">
                    <i class="fas fa-hourglass-half"></i> Usuarios en Trial
                </a>
                <a href="#pagos" class="menu-item">
                    <i class="fas fa-money-bill-wave"></i> Pagos Pendientes
                </a>
                <a href="/admin/dashboard_admin.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Admin
                </a>
                <a href="/logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Panel de Super Administrador</h1>
                <div class="user-info">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </header>

            <?php if ($alert): ?>
            <div class="alert alert-<?php echo $alert['type']; ?>">
                <?php echo htmlspecialchars($alert['message']); ?>
            </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-user-clock fa-2x"></i>
                    <h3><?php echo $stats['usuarios_pendientes']; ?></h3>
                    <p>Usuarios Pendientes de Aprobación</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-hourglass-half fa-2x"></i>
                    <h3><?php echo $stats['usuarios_trial']; ?></h3>
                    <p>Usuarios en Trial</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-users fa-2x"></i>
                    <h3><?php echo $stats['usuarios_activos']; ?></h3>
                    <p>Usuarios Activos (Pagando)</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                    <h3>$<?php echo number_format($stats['ingresos_mes'], 2); ?></h3>
                    <p>Ingresos Este Mes</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                    <i class="fas fa-bell fa-2x"></i>
                    <h3><?php echo $stats['pagos_pendientes']; ?></h3>
                    <p>Pagos Pendientes de Verificación</p>
                </div>
            </div>

            <!-- Usuarios Pendientes de Aprobación -->
            <div class="section" id="pendientes">
                <h2><i class="fas fa-user-clock"></i> Usuarios Pendientes de Aprobación</h2>
                <?php if (empty($usuarios_pendientes)): ?>
                    <p style="text-align:center;color:#999;padding:2rem;">No hay usuarios pendientes de aprobación</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha Registro</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Empresa</th>
                            <th>País</th>
                            <th>RUT/Tax ID</th>
                            <th>IP Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_pendientes as $user): ?>
                        <tr>
                            <td><?php echo formatDate($user['created_at'], 'd/m/Y H:i'); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['country_name'] ?? $user['country']); ?></td>
                            <td><?php echo htmlspecialchars($user['tax_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['ip_registro'] ?? 'N/A'); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Aprobar este usuario y activar su trial de 14 días?');">
                                    <input type="hidden" name="action" value="aprobar_usuario">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="notas" value="Aprobado desde panel super admin">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                </form>
                                <button class="btn btn-danger" onclick="rechazarUsuario(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Usuarios en Trial -->
            <div class="section" id="trial">
                <h2><i class="fas fa-hourglass-half"></i> Usuarios en Trial (Próximos a Vencer)</h2>
                <?php if (empty($usuarios_trial)): ?>
                    <p style="text-align:center;color:#999;padding:2rem;">No hay usuarios en trial actualmente</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Empresa</th>
                            <th>Fecha Fin Trial</th>
                            <th>Días Restantes</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_trial as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['company_name']); ?></td>
                            <td><?php echo formatDate($user['trial_ends_at'], 'd/m/Y H:i'); ?></td>
                            <td>
                                <span class="dias-restantes <?php
                                    if ($user['dias_restantes'] <= 1) echo 'critico';
                                    elseif ($user['dias_restantes'] <= 3) echo 'advertencia';
                                    else echo 'ok';
                                ?>">
                                    <?php echo $user['dias_restantes']; ?> días
                                </span>
                            </td>
                            <td>
                                <?php if ($user['dias_restantes'] <= 1): ?>
                                    <span class="badge badge-danger">Crítico - Vence Hoy/Mañana</span>
                                <?php elseif ($user['dias_restantes'] <= 3): ?>
                                    <span class="badge badge-warning">Advertencia</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Pagos Pendientes -->
            <div class="section" id="pagos">
                <h2><i class="fas fa-money-bill-wave"></i> Pagos Pendientes de Verificación</h2>
                <?php if (empty($pagos_pendientes)): ?>
                    <p style="text-align:center;color:#999;padding:2rem;">No hay pagos pendientes de verificación</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos_pendientes as $pago): ?>
                        <tr>
                            <td><?php echo formatDate($pago['created_at'], 'd/m/Y H:i'); ?></td>
                            <td><?php echo htmlspecialchars($pago['firstname'] . ' ' . $pago['lastname']); ?><br>
                                <small><?php echo htmlspecialchars($pago['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($pago['company_name']); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($pago['plan_nombre']); ?></span></td>
                            <td><strong><?php echo $pago['moneda']; ?> <?php echo number_format($pago['monto'], 2); ?></strong></td>
                            <td><?php echo strtoupper($pago['metodo_pago']); ?></td>
                            <td><?php echo htmlspecialchars($pago['referencia_pago'] ?? 'Pendiente'); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Verificar este pago y activar la suscripción?');">
                                    <input type="hidden" name="action" value="verificar_pago">
                                    <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                    <input type="hidden" name="notas" value="Pago verificado manualmente">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check-circle"></i> Verificar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function rechazarUsuario(userId) {
            const razon = prompt('Ingrese la razón del rechazo:');
            if (razon !== null && razon.trim() !== '') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="rechazar_usuario">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="razon" value="${razon}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
