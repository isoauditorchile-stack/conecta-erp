<?php
/**
 * CONECTA ERP - Panel de Super Administrador
 * SOLO para: auditorexchile@gmail.com
 */

require_once __DIR__ . '/../includes/config.php';
initSession();

// SOLO auditorexchile@gmail.com puede acceder
if (!isset($_SESSION['user_id']) || $_SESSION['email'] !== 'auditorexchile@gmail.com') {
    header('Location: /index.php');
    exit;
}

$db = Database::getInstance();
$msg = '';
$msg_type = 'success';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($action === 'aprobar' && $user_id > 0) {
        try {
            $db->update(
                "UPDATE users SET status = 'active', trial_ends_at = DATE_ADD(NOW(), INTERVAL 14 DAY) WHERE id = ?",
                [$user_id]
            );
            logActivity($_SESSION['user_id'], 'approve_user', "Usuario ID $user_id aprobado", 'admin');
            $msg = '✅ Usuario aprobado. Trial de 14 días activado.';
        } catch (Exception $e) {
            $msg = '❌ Error: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }

    if ($action === 'rechazar' && $user_id > 0) {
        try {
            $db->update("UPDATE users SET status = 'rejected' WHERE id = ?", [$user_id]);
            logActivity($_SESSION['user_id'], 'reject_user', "Usuario ID $user_id rechazado", 'admin');
            $msg = '✅ Usuario rechazado.';
        } catch (Exception $e) {
            $msg = '❌ Error: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Estadísticas
$stats = [
    'pendientes' => $db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'pending_approval'")['total'] ?? 0,
    'activos' => $db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status IN ('active', 'trial')")['total'] ?? 0,
    'total' => $db->fetchOne("SELECT COUNT(*) as total FROM users")['total'] ?? 0
];

// Usuarios pendientes
$usuarios_pendientes = $db->fetchAll("
    SELECT u.*, c.company_name, c.tax_id
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    WHERE u.status = 'pending_approval'
    ORDER BY u.created_at DESC
");

// Todos los usuarios
$todos_usuarios = $db->fetchAll("
    SELECT u.*, c.company_name
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    ORDER BY u.created_at DESC
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - CONECTA ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f7fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); }
        .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.95; }
        .nav { margin-top: 1rem; }
        .nav a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: rgba(255,255,255,0.2); border-radius: 6px; margin-right: 0.75rem; display: inline-block; transition: all 0.3s; }
        .nav a:hover { background: rgba(255,255,255,0.3); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card h3 { font-size: 2.5rem; color: #667eea; margin: 0.5rem 0; font-weight: 800; }
        .stat-card p { color: #6b7280; font-size: 0.95rem; }
        .stat-card i { color: #667eea; opacity: 0.5; }
        .section { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .section h2 { margin-bottom: 1.5rem; color: #1f2937; border-bottom: 3px solid #667eea; padding-bottom: 0.75rem; font-size: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f9fafb; padding: 1rem; text-align: left; font-weight: 700; border-bottom: 2px solid #e5e7eb; color: #374151; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        table td { padding: 1rem; border-bottom: 1px solid #f3f4f6; color: #4b5563; }
        table tr:hover { background: #f9fafb; }
        .badge { padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: inline-block; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; color: white; transition: all 0.3s; font-size: 0.9rem; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; transform: translateY(-1px); }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-1px); }
        .alert { padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 2px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 2px solid #ef4444; }
        .empty-state { text-align: center; padding: 3rem; color: #9ca3af; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-crown"></i> Panel de Super Administrador</h1>
            <p><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
            <div class="nav">
                <a href="/user/dashboard_user.php"><i class="fas fa-tachometer-alt"></i> Mi Dashboard</a>
                <a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>">
            <?php echo $msg; ?>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-user-clock fa-2x"></i>
                <h3><?php echo $stats['pendientes']; ?></h3>
                <p>Usuarios Pendientes</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle fa-2x"></i>
                <h3><?php echo $stats['activos']; ?></h3>
                <p>Usuarios Activos</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users fa-2x"></i>
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Usuarios</p>
            </div>
        </div>

        <!-- Usuarios Pendientes -->
        <?php if (!empty($usuarios_pendientes)): ?>
        <div class="section">
            <h2><i class="fas fa-user-clock"></i> Usuarios Pendientes de Aprobación (<?php echo count($usuarios_pendientes); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha Registro</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Empresa</th>
                        <th>RUT/Tax ID</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios_pendientes as $u): ?>
                    <tr>
                        <td><strong>#<?php echo $u['id']; ?></strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['tax_id']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="aprobar">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-success" onclick="return confirm('¿Aprobar este usuario y activar trial de 14 días?')">
                                    <i class="fas fa-check"></i> Aprobar
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="rechazar">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Rechazar este usuario?')">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="section">
            <h2><i class="fas fa-user-clock"></i> Usuarios Pendientes de Aprobación</h2>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <p>No hay usuarios pendientes de aprobación</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Todos los Usuarios -->
        <div class="section">
            <h2><i class="fas fa-users"></i> Todos los Usuarios (Últimos 100)</h2>
            <?php if (!empty($todos_usuarios)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Empresa</th>
                        <th>Estado</th>
                        <th>Trial Expira</th>
                        <th>Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todos_usuarios as $u): ?>
                    <tr>
                        <td><strong>#<?php echo $u['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($u['firstname'] . ' ' . $u['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['company_name'] ?? '-'); ?></td>
                        <td>
                            <?php
                            $badges = [
                                'pending_approval' => '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>',
                                'active' => '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Activo</span>',
                                'trial' => '<span class="badge badge-info"><i class="fas fa-vial"></i> Trial</span>',
                                'rejected' => '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rechazado</span>'
                            ];
                            echo $badges[$u['status']] ?? '<span class="badge">' . $u['status'] . '</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($u['trial_ends_at']) {
                                $dias_restantes = floor((strtotime($u['trial_ends_at']) - time()) / 86400);
                                echo date('d/m/Y', strtotime($u['trial_ends_at']));
                                echo '<br><small style="color:#6b7280;">(' . $dias_restantes . ' días)</small>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No hay usuarios registrados</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
