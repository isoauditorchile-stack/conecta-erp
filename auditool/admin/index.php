<?php
/**
 * AUDITOR PRO - Sistema Multi-ISO de Gestión
 * Panel de Administración - Solo para AuditorEx Chile
 *
 * @version 1.0
 * @author AUDITOR PRO
 */

session_start();
require_once('../config/config.php');

// Verificar sesión y rol de superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../login.php');
    exit;
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Obtener estadísticas globales
$stats = [];

// Total de empresas registradas
$sql = "SELECT COUNT(*) as total FROM companies WHERE status = 'active'";
$result = $conn->query($sql);
$stats['total_companies'] = $result->fetch_assoc()['total'];

// Total de usuarios registrados
$sql = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$result = $conn->query($sql);
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total de suscripciones activas
$sql = "SELECT COUNT(*) as total FROM subscriptions WHERE status IN ('active', 'trial')";
$result = $conn->query($sql);
$stats['active_subscriptions'] = $result->fetch_assoc()['total'];

// Ingresos mensuales totales
$sql = "SELECT SUM(plan_price) as total FROM subscriptions WHERE status = 'active'";
$result = $conn->query($sql);
$stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Obtener lista de empresas recientes
$sql = "SELECT c.*, s.plan_name, s.status as sub_status, s.plan_price,
        (SELECT COUNT(*) FROM users WHERE company_id = c.id) as total_users
        FROM companies c
        LEFT JOIN subscriptions s ON c.id = s.company_id
        ORDER BY c.created_date DESC
        LIMIT 10";
$recent_companies = $conn->query($sql);

// Obtener lista de usuarios recientes
$sql = "SELECT u.*, c.company_name
        FROM users u
        JOIN companies c ON u.company_id = c.id
        ORDER BY u.created_date DESC
        LIMIT 10";
$recent_users = $conn->query($sql);

// Obtener suscripciones pendientes de aprobación
$sql = "SELECT s.*, c.company_name, c.company_email
        FROM subscriptions s
        JOIN companies c ON s.company_id = c.id
        WHERE s.status = 'pending'
        ORDER BY s.created_date DESC";
$pending_subscriptions = $conn->query($sql);

// Obtener actividad reciente del sistema
$sql = "SELECT al.*, u.full_name, u.email, c.company_name
        FROM activity_logs al
        JOIN users u ON al.user_id = u.id
        JOIN companies c ON u.company_id = c.id
        ORDER BY al.created_at DESC
        LIMIT 20";
$recent_activity = $conn->query($sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }

        /* Top Bar */
        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .btn {
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .stat-icon {
            font-size: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-info h3 {
            font-size: 36px;
            color: #333;
            font-weight: bold;
        }
        .stat-info p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Sections */
        .section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .section-title {
            font-size: 24px;
            color: #333;
            font-weight: bold;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            color: #666;
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }

        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-trial { background: #fff3cd; color: #856404; }
        .badge-pending { background: #f8d7da; color: #721c24; }
        .badge-superadmin { background: #dc3545; color: white; }
        .badge-admin { background: #007bff; color: white; }
        .badge-user { background: #6c757d; color: white; }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-view { background: #17a2b8; color: white; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-approve { background: #28a745; color: white; }
        .btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

        /* Grid Layout */
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        /* Activity Log */
        .activity-item {
            padding: 15px;
            border-left: 3px solid #667eea;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .activity-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .activity-user {
            font-weight: 600;
            color: #333;
        }
        .activity-time {
            font-size: 12px;
            color: #999;
        }
        .activity-details {
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .grid-2col {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="topbar">
        <div>
            <span class="logo">🔐 AUDITOR PRO</span>
            <span class="admin-badge">🔧 ADMINISTRADOR</span>
        </div>
        <div class="topbar-right">
            <span style="color: #333; font-weight: 600;">
                👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </span>
            <a href="../dashboard.php" class="btn btn-success">👥 Panel Usuario</a>
            <a href="../logout.php" class="btn btn-secondary">Cerrar Sesión</a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_companies']; ?></h3>
                    <p>Empresas Activas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Usuarios Registrados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💎</div>
                <div class="stat-info">
                    <h3><?php echo $stats['active_subscriptions']; ?></h3>
                    <p>Suscripciones Activas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($stats['monthly_revenue'], 0); ?></h3>
                    <p>Ingresos Mensuales (USD)</p>
                </div>
            </div>
        </div>

        <!-- Suscripciones Pendientes -->
        <?php if ($pending_subscriptions->num_rows > 0): ?>
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">⚠️ Suscripciones Pendientes de Aprobación</h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Fecha Registro</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sub = $pending_subscriptions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($sub['company_email']); ?></td>
                        <td><strong><?php echo strtoupper($sub['plan_name']); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($sub['created_date'])); ?></td>
                        <td><span class="badge badge-pending">Pendiente</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-sm btn-approve" onclick="approveSubscription(<?php echo $sub['id']; ?>)">✓ Aprobar</button>
                                <button class="btn-sm btn-delete" onclick="rejectSubscription(<?php echo $sub['id']; ?>)">✗ Rechazar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="grid-2col">
            <!-- Empresas Recientes -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">🏢 Empresas Recientes</h2>
                    <a href="companies.php" class="btn btn-primary btn-sm">Ver Todas</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Usuarios</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($company = $recent_companies->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($company['company_name']); ?></strong><br>
                                <small style="color: #999;"><?php echo htmlspecialchars($company['company_email']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo strtoupper($company['plan_name'] ?? 'N/A'); ?></strong><br>
                                <small style="color: #999;">$<?php echo $company['plan_price'] ?? 0; ?> USD/mes</small>
                            </td>
                            <td><?php echo $company['total_users']; ?></td>
                            <td>
                                <?php if ($company['sub_status'] === 'active'): ?>
                                    <span class="badge badge-active">Activa</span>
                                <?php elseif ($company['sub_status'] === 'trial'): ?>
                                    <span class="badge badge-trial">Trial</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Usuarios Recientes -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">👥 Usuarios Recientes</h2>
                    <a href="users.php" class="btn btn-primary btn-sm">Ver Todos</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Empresa</th>
                            <th>Rol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                <small style="color: #999;"><?php echo htmlspecialchars($user['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($user['company_name']); ?></td>
                            <td>
                                <?php if ($user['role'] === 'superadmin'): ?>
                                    <span class="badge badge-superadmin">Superadmin</span>
                                <?php elseif ($user['role'] === 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user"><?php echo ucfirst($user['role']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actividad Reciente del Sistema -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📊 Actividad Reciente del Sistema</h2>
            </div>
            <div style="max-height: 500px; overflow-y: auto;">
                <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-header">
                        <div class="activity-user">
                            <?php echo htmlspecialchars($activity['full_name']); ?>
                            <small style="color: #999; font-weight: normal;">
                                (<?php echo htmlspecialchars($activity['company_name']); ?>)
                            </small>
                        </div>
                        <div class="activity-time">
                            <?php
                            $date = new DateTime($activity['created_at']);
                            echo $date->format('d/m/Y H:i:s');
                            ?>
                        </div>
                    </div>
                    <div class="activity-details">
                        <strong><?php echo htmlspecialchars($activity['action']); ?></strong> en
                        <em><?php echo htmlspecialchars($activity['module']); ?></em>
                        <?php if (!empty($activity['details'])): ?>
                            - <?php echo htmlspecialchars($activity['details']); ?>
                        <?php endif; ?>
                        <br>
                        <small style="color: #999;">IP: <?php echo htmlspecialchars($activity['ip_address']); ?></small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        function approveSubscription(subId) {
            if (confirm('¿Está seguro de aprobar esta suscripción?')) {
                window.location.href = 'actions/approve_subscription.php?id=' + subId;
            }
        }

        function rejectSubscription(subId) {
            if (confirm('¿Está seguro de rechazar esta suscripción?')) {
                window.location.href = 'actions/reject_subscription.php?id=' + subId;
            }
        }

        // Auto-refresh cada 60 segundos
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
