<?php
/**
 * CONECTA ERP - Sistema de Registro
 * Multiempresa - Permite empresa o persona natural
 */
require_once __DIR__ . '/includes/config.php';
initSession();

// Si ya tiene sesión, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: /user/dashboard_user.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    try {
        $conn = getMysqliConnection();
        $conn->begin_transaction();

        // Datos de empresa/persona
        $tipo_empresa = $_POST['tipo_empresa'] ?? 'empresa';
        $nombre_empresa = trim($_POST['nombre_empresa']);
        $rut_empresa = trim($_POST['rut_empresa']);
        $giro = trim($_POST['giro'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $pais = $_POST['pais'] ?? 'Chile';

        // Datos de usuario
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido'] ?? '');
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        // Validaciones
        if (empty($nombre_empresa) || empty($rut_empresa) || empty($nombre) || empty($email) || empty($password)) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }

        if ($password !== $password_confirm) {
            throw new Exception("Las contraseñas no coinciden");
        }

        if (strlen($password) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres");
        }

        // Verificar que no exista la empresa
        $stmt = $conn->prepare("SELECT id FROM empresas WHERE rut = ?");
        $stmt->bind_param('s', $rut_empresa);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Ya existe una empresa registrada con ese RUT");
        }

        // Verificar que no exista el email
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Ya existe un usuario registrado con ese email");
        }

        // Crear empresa
        $stmt = $conn->prepare("
            INSERT INTO empresas
            (nombre, rut, tipo_empresa, giro, direccion, ciudad, pais, plan, estado, fecha_inicio)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'basico', 'activa', NOW())
        ");
        $stmt->bind_param('sssssss',
            $nombre_empresa, $rut_empresa, $tipo_empresa, $giro, $direccion, $ciudad, $pais
        );
        $stmt->execute();
        $empresa_id = $conn->insert_id;

        // Crear usuario administrador
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO usuarios
            (empresa_id, nombre, apellido, email, password, rol, es_superadmin, estado)
            VALUES (?, ?, ?, ?, ?, 'admin', 0, 'activo')
        ");
        $stmt->bind_param('issss',
            $empresa_id, $nombre, $apellido, $email, $password_hash
        );
        $stmt->execute();
        $usuario_id = $conn->insert_id;

        $conn->commit();

        // Auto-login
        $_SESSION['user_id'] = $usuario_id;
        $_SESSION['empresa_id'] = $empresa_id;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email'] = $email;
        $_SESSION['rol'] = 'admin';

        header('Location: /user/dashboard_user.php');
        exit;

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $error = "Error al crear la cuenta: " . $e->getMessage();
        error_log("Register error: " . $error);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .register-body {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <h1><i class="fas fa-building"></i> CONECTA ERP</h1>
            <p class="mb-0">Crea tu cuenta - Sistema Multiempresa</p>
        </div>

        <div class="register-body">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST">
                <h5 class="mb-3"><i class="fas fa-building text-primary"></i> Datos de Empresa/Persona</h5>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo_empresa" class="form-select" required>
                            <option value="empresa">Empresa</option>
                            <option value="persona_natural">Persona Natural</option>
                        </select>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nombre Empresa / Razón Social *</label>
                        <input type="text" name="nombre_empresa" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">RUT *</label>
                        <input type="text" name="rut_empresa" class="form-control" placeholder="12345678-9" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Giro / Actividad</label>
                        <input type="text" name="giro" class="form-control">
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3"><i class="fas fa-user text-primary"></i> Datos de Usuario Administrador</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="apellido" class="form-control">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <input type="password" name="password_confirm" class="form-control" minlength="6" required>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="register" class="btn btn-primary btn-lg">
                        <i class="fas fa-check-circle"></i> Crear Cuenta
                    </button>
                    <a href="/login.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Ya tengo cuenta - Iniciar Sesión
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
