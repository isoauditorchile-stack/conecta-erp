<?php
/**
 * CONFIGURACIÓN - Preferencias del usuario
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión
requireLogin();

$usuario_id = $_SESSION['user_id'];
$success_message = '';
$errors = [];

// Obtener información del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

// Obtener idiomas disponibles
$idiomas = $conn->query("SELECT * FROM idiomas WHERE activo = 1 ORDER BY nombre");

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_configuracion'])) {
    $idioma_preferido = $_POST['idioma_preferido'];
    $notif_email = isset($_POST['notif_email']) ? 1 : 0;
    $notif_sistema = isset($_POST['notif_sistema']) ? 1 : 0;
    $tema = $_POST['tema'] ?? 'claro';

    // Actualizar preferencias
    $stmt = $conn->prepare("UPDATE usuarios SET idioma_preferido = ? WHERE id = ?");
    $stmt->bind_param("si", $idioma_preferido, $usuario_id);

    if ($stmt->execute()) {
        $_SESSION['idioma'] = $idioma_preferido;
        $success_message = "Configuración actualizada correctamente";

        // Log auditoría
        logAuditoria('actualizar_configuracion', 'usuarios', $usuario_id, null, null,
            'Usuario actualizó sus preferencias');

        // Recargar datos
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
    } else {
        $errors[] = "Error al actualizar configuración: " . $stmt->error;
    }
    $stmt->close();
}

// Procesar cambio de tema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_tema'])) {
    // Aquí se guardará la preferencia del tema cuando se implemente dark mode
    $success_message = "Tema actualizado correctamente";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - CONECTA ERP</title>
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

        /* Sidebar */
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Topbar */
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

        /* Content Area */
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

        .page-header p {
            color: #718096;
            margin: 0;
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

        .config-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .config-section:last-child {
            border-bottom: none;
        }

        .config-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .form-switch {
            padding-left: 2.5em;
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .theme-selector {
            display: flex;
            gap: 20px;
        }

        .theme-option {
            flex: 1;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-option:hover {
            border-color: #667eea;
            background: #f7faff;
        }

        .theme-option.selected {
            border-color: #667eea;
            background: #f7faff;
        }

        .theme-option i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }

        .theme-option.light i {
            color: #fbbf24;
        }

        .theme-option.dark i {
            color: #1e293b;
        }

        .theme-option.auto i {
            color: #667eea;
        }

        .info-help {
            font-size: 0.875rem;
            color: #718096;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    <span>CONECTA ERP</span>
                </h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard_user.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="mi-empresa.php">
                        <i class="fas fa-building"></i>
                        <span>Mi Empresa</span>
                    </a>
                </li>
                <li>
                    <a href="perfil.php">
                        <i class="fas fa-user"></i>
                        <span>Mi Perfil</span>
                    </a>
                </li>
                <li>
                    <a href="suscripcion.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Suscripción</span>
                    </a>
                </li>
                <li>
                    <a href="notificaciones.php">
                        <i class="fas fa-bell"></i>
                        <span>Notificaciones</span>
                    </a>
                </li>
                <li>
                    <a href="configuracion.php" class="active">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                <li>
                    <a href="soporte.php">
                        <i class="fas fa-headset"></i>
                        <span>Soporte</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
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
                        <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>
                        <div style="font-size: 0.875rem; color: #718096;">Usuario</div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1)); ?>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <div class="page-header">
                    <h1><i class="fas fa-cog"></i> Configuración</h1>
                    <p>Personaliza tu experiencia en CONECTA ERP</p>
                </div>

                <!-- Mensajes -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Configuración General -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-globe"></i> Preferencias Generales
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <!-- Idioma -->
                            <div class="config-section">
                                <div class="config-section-title">
                                    <i class="fas fa-language"></i> Idioma del Sistema
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="idioma_preferido" class="form-label">Selecciona tu idioma</label>
                                        <select class="form-select" id="idioma_preferido" name="idioma_preferido">
                                            <?php while ($idioma = $idiomas->fetch_assoc()): ?>
                                                <option value="<?php echo $idioma['codigo']; ?>"
                                                        <?php echo $usuario['idioma_preferido'] == $idioma['codigo'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($idioma['nombre_nativo']); ?>
                                                    (<?php echo htmlspecialchars($idioma['nombre']); ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="info-help">El idioma se aplicará en toda la interfaz del sistema</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Zona Horaria -->
                            <div class="config-section">
                                <div class="config-section-title">
                                    <i class="fas fa-clock"></i> Zona Horaria
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="zona_horaria" class="form-label">Zona Horaria</label>
                                        <select class="form-select" id="zona_horaria" name="zona_horaria">
                                            <option value="America/Santiago" selected>Santiago (GMT-3/GMT-4)</option>
                                            <option value="America/Buenos_Aires">Buenos Aires (GMT-3)</option>
                                            <option value="America/Sao_Paulo">São Paulo (GMT-3)</option>
                                            <option value="America/Lima">Lima (GMT-5)</option>
                                            <option value="America/Bogota">Bogotá (GMT-5)</option>
                                            <option value="America/Mexico_City">Ciudad de México (GMT-6)</option>
                                        </select>
                                        <div class="info-help">Se usa para mostrar fechas y horas</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="guardar_configuracion" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Configuración
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Apariencia -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-palette"></i> Apariencia
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="config-section">
                                <div class="config-section-title">
                                    <i class="fas fa-moon"></i> Tema del Sistema
                                </div>
                                <div class="theme-selector">
                                    <div class="theme-option light selected" onclick="selectTheme('light')">
                                        <i class="fas fa-sun"></i>
                                        <div><strong>Claro</strong></div>
                                        <div class="info-help">Tema con fondo blanco</div>
                                    </div>
                                    <div class="theme-option dark" onclick="selectTheme('dark')">
                                        <i class="fas fa-moon"></i>
                                        <div><strong>Oscuro</strong></div>
                                        <div class="info-help">Tema con fondo oscuro</div>
                                    </div>
                                    <div class="theme-option auto" onclick="selectTheme('auto')">
                                        <i class="fas fa-adjust"></i>
                                        <div><strong>Automático</strong></div>
                                        <div class="info-help">Según sistema operativo</div>
                                    </div>
                                </div>
                                <input type="hidden" name="tema" id="tema_input" value="claro">
                                <div class="info-help mt-3">
                                    <i class="fas fa-info-circle"></i> Próximamente: El modo oscuro estará disponible
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="cambiar_tema" class="btn btn-primary" disabled>
                                    <i class="fas fa-palette"></i> Cambiar Tema
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Notificaciones -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bell"></i> Notificaciones
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="config-section">
                                <div class="config-section-title">
                                    <i class="fas fa-envelope"></i> Notificaciones por Email
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_email" name="notif_email" checked>
                                    <label class="form-check-label" for="notif_email">
                                        Recibir notificaciones por email
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_pago" name="notif_pago" checked>
                                    <label class="form-check-label" for="notif_pago">
                                        Notificarme sobre pagos y suscripción
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_trial" name="notif_trial" checked>
                                    <label class="form-check-label" for="notif_trial">
                                        Avisos de periodo de prueba
                                    </label>
                                </div>
                            </div>

                            <div class="config-section">
                                <div class="config-section-title">
                                    <i class="fas fa-desktop"></i> Notificaciones del Sistema
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_sistema" name="notif_sistema" checked>
                                    <label class="form-check-label" for="notif_sistema">
                                        Mostrar notificaciones en el sistema
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_sonido" name="notif_sonido">
                                    <label class="form-check-label" for="notif_sonido">
                                        Reproducir sonido para notificaciones
                                    </label>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="guardar_notificaciones" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Preferencias
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Seguridad -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-shield-alt"></i> Seguridad
                    </div>
                    <div class="card-body">
                        <div class="config-section">
                            <div class="config-section-title">
                                <i class="fas fa-key"></i> Contraseña
                            </div>
                            <p>Última modificación: No disponible</p>
                            <a href="perfil.php" class="btn btn-outline-primary">
                                <i class="fas fa-edit"></i> Cambiar Contraseña
                            </a>
                        </div>

                        <div class="config-section">
                            <div class="config-section-title">
                                <i class="fas fa-mobile-alt"></i> Autenticación de Dos Factores
                            </div>
                            <p class="text-muted">Próximamente: Mejora la seguridad con autenticación de dos factores</p>
                            <button class="btn btn-outline-primary" disabled>
                                <i class="fas fa-lock"></i> Activar 2FA
                            </button>
                        </div>

                        <div class="config-section">
                            <div class="config-section-title">
                                <i class="fas fa-history"></i> Sesiones Activas
                            </div>
                            <p class="text-muted">Próximamente: Ver y cerrar sesiones activas en otros dispositivos</p>
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

        function selectTheme(theme) {
            // Remover clase selected de todas las opciones
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Agregar clase selected a la opción seleccionada
            event.target.closest('.theme-option').classList.add('selected');

            // Actualizar el input hidden
            document.getElementById('tema_input').value = theme;

            // Próximamente: Aplicar el tema
            console.log('Tema seleccionado:', theme);
        }
    </script>
</body>
</html>
