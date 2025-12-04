<?php
/**
 * AUDITOR PRO - Panel de Administración
 * Gestión de Todas las Empresas
 */

session_start();
require_once('../config/config.php');

// Verificar sesión y rol de superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../login.php');
    exit;
}

$conn = getDBConnection();

// Obtener todas las empresas
$sql = "SELECT c.*, s.plan_name, s.status as sub_status, s.plan_price,
        s.trial_end_date, s.next_billing_date,
        (SELECT COUNT(*) FROM users WHERE company_id = c.id) as total_users
        FROM companies c
        LEFT JOIN subscriptions s ON c.id = s.company_id
        ORDER BY c.created_date DESC";
$companies = $conn->query($sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - AUDITOR PRO Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }
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
            display: inline-block;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .container {
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .section-title {
            font-size: 28px;
            color: #333;
            font-weight: bold;
        }
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
            font-size: 14px;
        }
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            color: #666;
            font-size: 14px;
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-trial { background: #fff3cd; color: #856404; }
        .badge-suspended { background: #f8d7da; color: #721c24; }
        .badge-inactive { background: #e2e3e5; color: #383d41; }
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
            <a href="index.php" class="btn btn-secondary">← Volver al Panel</a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">🏢 Todas las Empresas Registradas</h2>
                <div>
                    <strong>Total: <?php echo $companies->num_rows; ?> empresas</strong>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>RUT</th>
                        <th>Email</th>
                        <th>País</th>
                        <th>Plan</th>
                        <th>Precio</th>
                        <th>Usuarios</th>
                        <th>Suscripción</th>
                        <th>Fecha Trial</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($company = $companies->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $company['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($company['company_name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($company['company_rut'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($company['company_email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($company['company_country'] ?? 'N/A'); ?></td>
                        <td><strong><?php echo strtoupper($company['plan_name'] ?? 'N/A'); ?></strong></td>
                        <td>$<?php echo number_format($company['plan_price'] ?? 0, 0); ?></td>
                        <td><?php echo $company['total_users']; ?></td>
                        <td>
                            <?php if ($company['sub_status'] === 'active'): ?>
                                <span class="badge badge-active">Activa</span>
                            <?php elseif ($company['sub_status'] === 'trial'): ?>
                                <span class="badge badge-trial">Trial</span>
                            <?php elseif ($company['sub_status'] === 'suspended'): ?>
                                <span class="badge badge-suspended">Suspendida</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ($company['trial_end_date']) {
                                echo date('d/m/Y', strtotime($company['trial_end_date']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($company['status'] === 'active'): ?>
                                <span class="badge badge-active">Activa</span>
                            <?php else: ?>
                                <span class="badge badge-inactive"><?php echo ucfirst($company['status']); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
