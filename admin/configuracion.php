<?php
/**
 * CONFIGURACIÓN DEL SISTEMA - Super Administrador
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión y permisos de super admin
requireLogin();
requireSuperAdmin();

$success_message = '';
$errors = [];

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $conn->begin_transaction();

    try {
        foreach ($_POST as $key => $value) {
            if ($key === 'guardar_config') continue;

            // Actualizar cada configuración
            $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = ? AND es_editable = 1");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $success_message = "Configuración actualizada correctamente";
        logAuditoria('actualizar_configuracion', 'configuracion', null, null, null, 'Super admin actualizó configuración del sistema');

    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Error al actualizar configuración: " . $e->getMessage();
    }
}

// Obtener todas las configuraciones agrupadas
$configuraciones = [];
$result = $conn->query("SELECT * FROM configuracion ORDER BY grupo, clave");
while ($row = $result->fetch_assoc()) {
    $configuraciones[$row['grupo']][] = $row;
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
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-header h3 span {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left: 4px solid white;
        }

        .sidebar-menu a i {
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .content-area {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: #2d3748;
        }

        .card-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-control:disabled {
            background: #f7fafc;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .config-group {
            margin-bottom: 40px;
        }

        .config-group-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .config-item {
            margin-bottom: 20px;
        }

        .config-help {
            font-size: 0.875rem;
            color: #718096;
            margin-top: 5px;
        }

        .config-readonly {
            background: #f7fafc;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-user-shield"></i>
                    <span>ADMIN</span>
                </h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i><span>Usuarios</span></a></li>
                <li><a href="empresas.php"><i class="fas fa-building"></i><span>Empresas</span></a></li>
                <li><a href="pagos.php"><i class="fas fa-dollar-sign"></i><span>Pagos</span></a></li>
                <li><a href="suscripciones.php"><i class="fas fa-credit-card"></i><span>Suscripciones</span></a></li>
                <li><a href="configuracion.php" class="active"><i class="fas fa-cog"></i><span>Configuración</span></a></li>
                <li><a href="reportes.php"><i class="fas fa-chart-bar"></i><span>Reportes</span></a></li>
                <li><a href="auditoria.php"><i class="fas fa-history"></i><span>Auditoría</span></a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <button class="toggle-sidebar" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-info">
                    <div>
                        <strong>Super Administrador</strong>
                        <div style="font-size: 0.875rem; color: #718096;">Administrador</div>
                    </div>
                    <div class="user-avatar">SA</div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="page-header">
                    <h1><i class="fas fa-cog"></i> Configuración del Sistema</h1>
                    <p>Administra los parámetros globales de CONECTA ERP</p>
                </div>

                <!-- Mensajes -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php foreach ($configuraciones as $grupo => $configs): ?>
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-<?php echo $grupo === 'general' ? 'sliders-h' : ($grupo === 'email' ? 'envelope' : ($grupo === 'suscripcion' ? 'credit-card' : 'cog')); ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $grupo)); ?>
                            </div>
                            <div class="card-body">
                                <div class="config-group">
                                    <div class="row">
                                        <?php foreach ($configs as $config): ?>
                                            <div class="col-md-6 config-item">
                                                <label class="form-label">
                                                    <?php echo htmlspecialchars($config['descripcion'] ?? ucfirst(str_replace('_', ' ', $config['clave']))); ?>
                                                    <?php if ($config['es_editable'] == 0): ?>
                                                        <span class="badge bg-secondary">Solo lectura</span>
                                                    <?php endif; ?>
                                                </label>

                                                <?php if ($config['es_editable'] == 1): ?>
                                                    <?php if ($config['tipo'] === 'boolean'): ?>
                                                        <select name="<?php echo $config['clave']; ?>" class="form-select">
                                                            <option value="1" <?php echo $config['valor'] == '1' ? 'selected' : ''; ?>>Sí</option>
                                                            <option value="0" <?php echo $config['valor'] == '0' ? 'selected' : ''; ?>>No</option>
                                                        </select>
                                                    <?php elseif ($config['tipo'] === 'number'): ?>
                                                        <input type="number" name="<?php echo $config['clave']; ?>"
                                                               value="<?php echo htmlspecialchars($config['valor']); ?>"
                                                               class="form-control">
                                                    <?php else: ?>
                                                        <input type="text" name="<?php echo $config['clave']; ?>"
                                                               value="<?php echo htmlspecialchars($config['valor']); ?>"
                                                               class="form-control">
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="config-readonly">
                                                        <?php echo htmlspecialchars($config['valor']); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="config-help">
                                                    Clave: <code><?php echo $config['clave']; ?></code>
                                                    | Tipo: <code><?php echo $config['tipo']; ?></code>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-end">
                        <button type="submit" name="guardar_config" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>

                <!-- Información adicional -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Información del Sistema
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Información del Servidor</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>PHP Version:</strong></td>
                                        <td><?php echo phpversion(); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>MySQL Version:</strong></td>
                                        <td><?php echo $conn->server_info; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Server:</strong></td>
                                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Estadísticas</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Total Usuarios:</strong></td>
                                        <td><?php echo $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Empresas:</strong></td>
                                        <td><?php echo $conn->query("SELECT COUNT(*) as total FROM empresas")->fetch_assoc()['total']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Suscripciones Activas:</strong></td>
                                        <td><?php echo $conn->query("SELECT COUNT(*) as total FROM suscripciones WHERE estado = 'activo'")->fetch_assoc()['total']; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
