<?php
/**
 * MI EMPRESA - Gestión de información de la empresa
 */
session_start();
require_once '../includes/config.php';

// Verificar sesión
requireLogin();

$usuario_id = $_SESSION['user_id'];
$success_message = '';
$errors = [];

// Obtener información del usuario y empresa
$stmt = $conn->prepare("SELECT u.*, e.*
                        FROM usuarios u
                        LEFT JOIN empresas e ON u.empresa_id = e.id
                        WHERE u.id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

$empresa_id = $usuario['empresa_id'];

// Obtener países para el select
$paises = $conn->query("SELECT * FROM paises WHERE activo = 1 ORDER BY nombre");

// Procesar actualización de empresa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_empresa'])) {
    $nombre_empresa = trim($_POST['nombre_empresa']);
    $razon_social = trim($_POST['razon_social']);
    $rut = trim($_POST['rut']);
    $pais_id = (int)$_POST['pais_id'];
    $direccion = trim($_POST['direccion']);
    $ciudad = trim($_POST['ciudad']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $sitio_web = trim($_POST['sitio_web']);

    // Validaciones
    if (empty($nombre_empresa)) {
        $errors[] = "El nombre de la empresa es obligatorio";
    }
    if (empty($rut)) {
        $errors[] = "El RUT es obligatorio";
    }
    if (empty($pais_id)) {
        $errors[] = "Debe seleccionar un país";
    }

    if (empty($errors)) {
        if ($empresa_id) {
            // Actualizar empresa existente
            $stmt = $conn->prepare("UPDATE empresas SET
                nombre_empresa = ?, razon_social = ?, rut = ?, pais_id = ?,
                direccion = ?, ciudad = ?, telefono = ?, email = ?, sitio_web = ?
                WHERE id = ?");
            $stmt->bind_param("sssississi",
                $nombre_empresa, $razon_social, $rut, $pais_id,
                $direccion, $ciudad, $telefono, $email, $sitio_web,
                $empresa_id
            );

            if ($stmt->execute()) {
                $success_message = "Información de la empresa actualizada correctamente";

                // Log auditoría
                logAuditoria('actualizar_empresa', 'empresas', $empresa_id, null, null,
                    'Usuario actualizó información de empresa');

                // Recargar datos
                $stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
                $stmt->bind_param("i", $empresa_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $empresa = $result->fetch_assoc();
                $usuario = array_merge($usuario, $empresa);
            } else {
                $errors[] = "Error al actualizar: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // Crear nueva empresa
            $stmt = $conn->prepare("INSERT INTO empresas
                (nombre_empresa, razon_social, rut, pais_id, direccion, ciudad, telefono, email, sitio_web)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss",
                $nombre_empresa, $razon_social, $rut, $pais_id,
                $direccion, $ciudad, $telefono, $email, $sitio_web
            );

            if ($stmt->execute()) {
                $empresa_id = $stmt->insert_id;

                // Asociar empresa al usuario
                $stmt2 = $conn->prepare("UPDATE usuarios SET empresa_id = ? WHERE id = ?");
                $stmt2->bind_param("ii", $empresa_id, $usuario_id);
                $stmt2->execute();
                $stmt2->close();

                $success_message = "Empresa creada correctamente";

                // Log auditoría
                logAuditoria('crear_empresa', 'empresas', $empresa_id, null, null,
                    'Usuario creó nueva empresa');

                // Recargar datos
                $stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
                $stmt->bind_param("i", $empresa_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $empresa = $result->fetch_assoc();
                $usuario = array_merge($usuario, $empresa);
            } else {
                $errors[] = "Error al crear empresa: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Obtener el país seleccionado para mostrar formato de documento
$pais_seleccionado = null;
if (!empty($usuario['pais_id'])) {
    $stmt = $conn->prepare("SELECT * FROM paises WHERE id = ?");
    $stmt->bind_param("i", $usuario['pais_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pais_seleccionado = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Empresa - CONECTA ERP</title>
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

        .company-logo-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f7fafc;
            margin-bottom: 15px;
        }

        .company-logo-preview i {
            font-size: 3rem;
            color: #cbd5e0;
        }

        .info-help {
            font-size: 0.875rem;
            color: #718096;
            margin-top: 5px;
        }

        .company-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .company-status.activo {
            background: #c6f6d5;
            color: #22543d;
        }

        .company-status.inactivo {
            background: #fed7d7;
            color: #742a2a;
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
                    <a href="mi-empresa.php" class="active">
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
                    <a href="configuracion.php">
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
                    <h1><i class="fas fa-building"></i> Mi Empresa</h1>
                    <p>Gestiona la información de tu empresa</p>
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

                <!-- Información de la Empresa -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Información de la Empresa
                        <?php if ($empresa_id): ?>
                            <span class="float-end">
                                <span class="company-status <?php echo $usuario['estado']; ?>">
                                    <?php echo ucfirst($usuario['estado']); ?>
                                </span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <!-- Logo de la empresa -->
                                <div class="col-md-12 mb-4">
                                    <label class="form-label">Logo de la Empresa</label>
                                    <div class="company-logo-preview">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <input type="file" class="form-control" accept="image/*" disabled>
                                    <div class="info-help">Próximamente: Carga de logo empresarial</div>
                                </div>

                                <!-- Información básica -->
                                <div class="col-md-6 mb-3">
                                    <label for="nombre_empresa" class="form-label">Nombre de la Empresa *</label>
                                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa"
                                           value="<?php echo htmlspecialchars($usuario['nombre_empresa'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="razon_social" class="form-label">Razón Social</label>
                                    <input type="text" class="form-control" id="razon_social" name="razon_social"
                                           value="<?php echo htmlspecialchars($usuario['razon_social'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="pais_id" class="form-label">País *</label>
                                    <select class="form-select" id="pais_id" name="pais_id" required>
                                        <option value="">Seleccione un país</option>
                                        <?php
                                        $paises->data_seek(0);
                                        while ($pais = $paises->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $pais['id']; ?>"
                                                    <?php echo ($usuario['pais_id'] ?? '') == $pais['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pais['nombre']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="rut" class="form-label">
                                        <?php echo $pais_seleccionado ? htmlspecialchars($pais_seleccionado['tipo_documento']) : 'RUT'; ?> *
                                    </label>
                                    <input type="text" class="form-control" id="rut" name="rut"
                                           value="<?php echo htmlspecialchars($usuario['rut'] ?? ''); ?>"
                                           placeholder="<?php echo $pais_seleccionado ? htmlspecialchars($pais_seleccionado['formato_documento']) : '12.345.678-9'; ?>"
                                           required>
                                    <?php if ($pais_seleccionado): ?>
                                        <div class="info-help">Formato: <?php echo htmlspecialchars($pais_seleccionado['formato_documento']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Contacto -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Corporativo</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono"
                                           value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                                </div>

                                <!-- Dirección -->
                                <div class="col-md-12 mb-3">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion"
                                           value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="ciudad" class="form-label">Ciudad</label>
                                    <input type="text" class="form-control" id="ciudad" name="ciudad"
                                           value="<?php echo htmlspecialchars($usuario['ciudad'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sitio_web" class="form-label">Sitio Web</label>
                                    <input type="url" class="form-control" id="sitio_web" name="sitio_web"
                                           value="<?php echo htmlspecialchars($usuario['sitio_web'] ?? ''); ?>"
                                           placeholder="https://www.ejemplo.com">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="info-help">* Campos obligatorios</div>
                                <button type="submit" name="actualizar_empresa" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $empresa_id ? 'Actualizar Información' : 'Crear Empresa'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($empresa_id): ?>
                <!-- Estadísticas de la empresa -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt" style="font-size: 2.5rem; color: #667eea; margin-bottom: 15px;"></i>
                                <h3 style="color: #2d3748; margin-bottom: 5px;">
                                    <?php
                                    $fecha_registro = new DateTime($usuario['fecha_registro']);
                                    echo $fecha_registro->format('d/m/Y');
                                    ?>
                                </h3>
                                <p style="color: #718096; margin: 0;">Fecha de Registro</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-users" style="font-size: 2.5rem; color: #48bb78; margin-bottom: 15px;"></i>
                                <h3 style="color: #2d3748; margin-bottom: 5px;">1</h3>
                                <p style="color: #718096; margin: 0;">Usuarios</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-globe" style="font-size: 2.5rem; color: #ed8936; margin-bottom: 15px;"></i>
                                <h3 style="color: #2d3748; margin-bottom: 5px;">
                                    <?php echo $pais_seleccionado ? htmlspecialchars($pais_seleccionado['nombre']) : 'N/A'; ?>
                                </h3>
                                <p style="color: #718096; margin: 0;">País</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }

        // Auto-formatear RUT según país seleccionado
        document.getElementById('pais_id').addEventListener('change', function() {
            const paisId = this.value;
            const rutInput = document.getElementById('rut');

            // Aquí se podría agregar lógica de formateo automático según el país
        });
    </script>
</body>
</html>
