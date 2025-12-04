<?php
session_start();
require_once('config/config.php');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

// Obtener información del usuario y empresa
$sql = "SELECT u.*, c.company_name, c.active_isos, s.plan_name, s.status as sub_status, s.max_isos
        FROM users u
        JOIN companies c ON u.company_id = c.id
        LEFT JOIN subscriptions s ON c.id = s.company_id AND s.status IN ('active', 'trial')
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$active_isos = json_decode($user_data['active_isos'], true) ?: [];
$all_isos = json_decode(AVAILABLE_ISOS, true);
$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-info {
            text-align: right;
        }
        .user-name {
            font-weight: bold;
            color: #333;
        }
        .user-role {
            font-size: 12px;
            color: #666;
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
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        .btn:hover {
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Welcome Section */
        .welcome {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .welcome h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        .welcome p {
            color: #666;
            font-size: 16px;
        }
        .welcome-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        /* ISO Grid */
        .section-title {
            font-size: 28px;
            color: white;
            margin-bottom: 30px;
            text-align: center;
        }
        .iso-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        .iso-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .iso-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .iso-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .iso-card.disabled:hover {
            transform: none;
        }
        .iso-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .iso-icon {
            font-size: 40px;
        }
        .iso-info h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }
        .iso-info p {
            font-size: 14px;
            color: #666;
        }
        .iso-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .iso-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-locked {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-access {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.3s;
        }
        .btn-access.enabled {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-access.disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .quick-actions h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .action-btn {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        .action-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                gap: 15px;
            }
            .iso-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="topbar">
        <div class="logo">🔐 AUDITOR PRO</div>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="user-role">
                    <?php echo htmlspecialchars($_SESSION['company_name']); ?> •
                    <?php echo ucfirst($_SESSION['role']); ?>
                </div>
            </div>
            <?php if ($_SESSION['role'] === 'superadmin'): ?>
            <a href="admin/index.php" class="btn btn-primary">🔧 Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-secondary">Cerrar Sesión</a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome">
            <h1>👋 Bienvenido, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
            <p>Seleccione la norma ISO que desea gestionar</p>

            <div class="welcome-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($active_isos); ?></div>
                    <div class="stat-label">ISOs Activas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo ucfirst($user_data['sub_status'] ?? 'N/A'); ?></div>
                    <div class="stat-label">Estado Suscripción</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo strtoupper($user_data['plan_name'] ?? 'N/A'); ?></div>
                    <div class="stat-label">Plan Actual</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $user_data['max_isos'] ?? 0; ?></div>
                    <div class="stat-label">ISOs Disponibles</div>
                </div>
            </div>
        </div>

        <!-- ISO Grid -->
        <h2 class="section-title">📋 Sistemas de Gestión Disponibles</h2>

        <div class="iso-grid">
            <?php foreach ($all_isos as $iso_key => $iso_info): ?>
                <?php $is_active = in_array($iso_key, $active_isos); ?>
                <div class="iso-card <?php echo !$is_active ? 'disabled' : ''; ?>">
                    <span class="iso-badge <?php echo $is_active ? 'badge-active' : 'badge-locked'; ?>">
                        <?php echo $is_active ? '✓ ACTIVA' : '🔒 BLOQUEADA'; ?>
                    </span>

                    <div class="iso-header">
                        <div class="iso-icon"><?php echo $iso_info['icon']; ?></div>
                        <div class="iso-info">
                            <h3><?php echo $iso_info['name']; ?></h3>
                            <p><?php echo $iso_info['title']; ?></p>
                        </div>
                    </div>

                    <div class="iso-description">
                        Sistema completo de gestión con módulos integrados para cumplimiento normativo.
                    </div>

                    <?php if ($is_active): ?>
                        <a href="<?php echo $iso_key; ?>/index.php" class="btn-access enabled">
                            Acceder al Sistema
                        </a>
                    <?php else: ?>
                        <a href="#" class="btn-access disabled" onclick="return false;">
                            No Disponible en su Plan
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>⚡ Acciones Rápidas</h2>
            <div class="actions-grid">
                <a href="#" class="action-btn">
                    <span>👤</span> Mi Perfil
                </a>
                <a href="#" class="action-btn">
                    <span>🏢</span> Gestionar Empresa
                </a>
                <a href="#" class="action-btn">
                    <span>👥</span> Usuarios
                </a>
                <a href="#" class="action-btn">
                    <span>💳</span> Suscripción
                </a>
                <a href="#" class="action-btn">
                    <span>📊</span> Reportes
                </a>
                <a href="#" class="action-btn">
                    <span>⚙️</span> Configuración
                </a>
                <a href="#" class="action-btn">
                    <span>📚</span> Documentación
                </a>
                <a href="#" class="action-btn">
                    <span>💬</span> Soporte
                </a>
            </div>
        </div>
    </div>
</body>
</html>
