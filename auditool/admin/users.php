<?php
/**
 * AUDITOR PRO - Panel de Administración
 * Gestión de Todos los Usuarios
 */

session_start();
require_once('../config/config.php');

// Verificar sesión y rol de superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../login.php');
    exit;
}

$conn = getDBConnection();

// Obtener todos los usuarios
$sql = "SELECT u.*, c.company_name
        FROM users u
        JOIN companies c ON u.company_id = c.id
        ORDER BY u.created_date DESC";
$users = $conn->query($sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - AUDITOR PRO Admin</title>
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
        .badge-superadmin { background: #dc3545; color: white; }
        .badge-admin { background: #007bff; color: white; }
        .badge-auditor { background: #17a2b8; color: white; }
        .badge-user { background: #6c757d; color: white; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
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
                <h2 class="section-title">👥 Todos los Usuarios Registrados</h2>
                <div>
                    <strong>Total: <?php echo $users->num_rows; ?> usuarios</strong>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Empresa</th>
                        <th>Rol</th>
                        <th>Idioma</th>
                        <th>Último Login</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['company_name']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'superadmin'): ?>
                                <span class="badge badge-superadmin">Superadmin</span>
                            <?php elseif ($user['role'] === 'admin'): ?>
                                <span class="badge badge-admin">Admin</span>
                            <?php elseif ($user['role'] === 'auditor'): ?>
                                <span class="badge badge-auditor">Auditor</span>
                            <?php else: ?>
                                <span class="badge badge-user"><?php echo ucfirst($user['role']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo strtoupper($user['language']); ?></td>
                        <td>
                            <?php
                            if ($user['last_login']) {
                                echo date('d/m/Y H:i', strtotime($user['last_login']));
                            } else {
                                echo 'Nunca';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge badge-active">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-inactive"><?php echo ucfirst($user['status']); ?></span>
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
