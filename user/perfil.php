<?php
session_start();
require_once '../includes/config.php';

// Verificar autenticación
requireLogin();

$message = '';
$message_type = '';

// Obtener datos del usuario
$usuario_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.*, e.nombre_empresa, e.razon_social, e.rut as empresa_rut, e.direccion as empresa_direccion, e.ciudad as empresa_ciudad, e.telefono as empresa_telefono, e.email as empresa_email, p.nombre as pais_nombre
                        FROM usuarios u
                        LEFT JOIN empresas e ON u.empresa_id = e.id
                        LEFT JOIN paises p ON e.pais_id = p.id
                        WHERE u.id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']) ?: null;
    $rut = trim($_POST['rut']) ?: null;

    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?, rut = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $nombre, $apellido, $telefono, $rut, $usuario_id);

    if ($stmt->execute()) {
        $_SESSION['nombre'] = $nombre;
        $_SESSION['apellido'] = $apellido;
        $message = "Perfil actualizado exitosamente";
        $message_type = "success";

        // Recargar datos
        $stmt2 = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt2->bind_param("i", $usuario_id);
        $stmt2->execute();
        $usuario = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
    } else {
        $message = "Error al actualizar perfil";
        $message_type = "danger";
    }
    $stmt->close();
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];

    if (password_verify($password_actual, $usuario['password'])) {
        if ($password_nueva === $password_confirmar) {
            if (strlen($password_nueva) >= 8) {
                $password_hash = password_hash($password_nueva, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $password_hash, $usuario_id);

                if ($stmt->execute()) {
                    $message = "Contraseña actualizada exitosamente";
                    $message_type = "success";
                } else {
                    $message = "Error al actualizar contraseña";
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "La nueva contraseña debe tener al menos 8 caracteres";
                $message_type = "warning";
            }
        } else {
            $message = "Las contraseñas nuevas no coinciden";
            $message_type = "warning";
        }
    } else {
        $message = "La contraseña actual es incorrecta";
        $message_type = "danger";
    }
}

// Obtener idiomas disponibles
$idiomas = $conn->query("SELECT * FROM idiomas WHERE activo = 1 ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - CONECTA ERP</title>
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

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .profile-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
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

        .info-group {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-size: 1.1rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
            <li><a href="perfil.php" class="active"><i class="fas fa-user"></i> <span>Mi Perfil</span></a></li>
            <li><a href="mi-empresa.php"><i class="fas fa-building"></i> <span>Mi Empresa</span></a></li>
            <li><a href="suscripcion.php"><i class="fas fa-credit-card"></i> <span>Suscripción</span></a></li>
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
                <div style="font-size: 0.85rem; color: #6c757d;"><?php echo htmlspecialchars($usuario['nombre_empresa'] ?? 'Usuario'); ?></div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 style="margin-bottom: 30px; color: #333;">
            <i class="fas fa-user"></i> Mi Perfil
        </h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Información General -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                </div>
                <div>
                    <h2 style="margin: 0; color: #333;"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></h2>
                    <p style="margin: 5px 0; color: #6c757d;">@<?php echo htmlspecialchars($usuario['username']); ?></p>
                    <p style="margin: 5px 0;"><span class="badge bg-info"><?php echo htmlspecialchars($usuario['nombre_empresa'] ?? 'Sin empresa'); ?></span></p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['telefono'] ?? 'No registrado'); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">RUT</div>
                        <div class="info-value"><?php echo htmlspecialchars($usuario['rut'] ?? 'No registrado'); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Idioma Preferido</div>
                        <div class="info-value"><?php echo strtoupper($usuario['idioma_preferido']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Estado</div>
                        <div class="info-value"><span class="badge bg-success"><?php echo ucfirst($usuario['estado']); ?></span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Fecha de Registro</div>
                        <div class="info-value"><?php echo formatDate($usuario['fecha_registro'], 'd/m/Y'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editar Perfil -->
        <div class="profile-card">
            <div class="section-title">
                <i class="fas fa-edit"></i> Editar Información Personal
            </div>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">RUT</label>
                        <input type="text" name="rut" class="form-control" value="<?php echo htmlspecialchars($usuario['rut'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="actualizar_perfil" class="btn-primary-custom">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Cambiar Contraseña -->
        <div class="profile-card">
            <div class="section-title">
                <i class="fas fa-lock"></i> Cambiar Contraseña
            </div>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" name="password_actual" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="password_nueva" class="form-control" minlength="8" required>
                        <small class="text-muted">Mínimo 8 caracteres</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmar" class="form-control" minlength="8" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="cambiar_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </button>
                    </div>
                </div>
            </form>
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
