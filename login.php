<?php
session_start();
require_once 'includes/config.php';

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['es_super_admin'] == 1) ? 'admin/dashboard_admin.php' : 'user/dashboard_user.php';
    header("Location: $redirect");
    exit();
}

$error = '';
$success = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = trim($_POST['email_or_username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email_or_username) || empty($password)) {
        $error = 'Por favor ingresa tus credenciales';
    } else {
        // Buscar usuario por email o username
        $stmt = $conn->prepare("SELECT u.*, e.nombre_empresa, e.estado as empresa_estado
                                FROM usuarios u
                                LEFT JOIN empresas e ON u.empresa_id = e.id
                                WHERE u.email = ? OR u.username = ?");
        $stmt->bind_param("ss", $email_or_username, $email_or_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verificar contraseña
            if (password_verify($password, $user['password'])) {

                // Verificar estado del usuario
                if ($user['estado'] !== 'activo') {
                    $error = 'Tu cuenta está ' . $user['estado'] . '. Contacta al administrador.';
                }
                // Verificar si es super admin (bypass trial check)
                elseif ($user['es_super_admin'] == 1) {
                    // SUPER ADMIN - acceso completo sin restricciones
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['apellido'] = $user['apellido'];
                    $_SESSION['empresa_id'] = $user['empresa_id'];
                    $_SESSION['es_admin'] = 1;
                    $_SESSION['es_super_admin'] = 1;
                    $_SESSION['idioma_preferido'] = $user['idioma_preferido'];
                    $_SESSION['avatar'] = $user['avatar'];
                    $_SESSION['en_periodo_prueba'] = 0;

                    // Actualizar último acceso
                    $update_stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();

                    // Registrar en logs
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                    $log_stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, ?, 'login', 1, 'Super Admin - Login exitoso', ?, ?)");
                    $log_stmt->bind_param("isss", $user['id'], $user['email'], $ip_address, $user_agent);
                    $log_stmt->execute();
                    $log_stmt->close();

                    // Crear sesión
                    $token = bin2hex(random_bytes(32));
                    $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $session_stmt = $conn->prepare("INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, activo, fecha_expiracion) VALUES (?, ?, ?, ?, 1, ?)");
                    $session_stmt->bind_param("issss", $user['id'], $token, $ip_address, $user_agent, $fecha_expiracion);
                    $session_stmt->execute();
                    $session_stmt->close();

                    $_SESSION['session_token'] = $token;

                    header("Location: admin/dashboard_admin.php");
                    exit();

                }
                // Usuario normal - verificar trial y suscripción
                else {
                    // Verificar periodo de prueba
                    if ($user['en_periodo_prueba'] == 1) {
                        $fecha_fin_trial = strtotime($user['fecha_fin_trial']);
                        $hoy = time();

                        if ($hoy > $fecha_fin_trial) {
                            $error = 'Tu periodo de prueba ha expirado. Por favor actualiza tu plan para continuar.';

                            // Actualizar estado
                            $update_stmt = $conn->prepare("UPDATE usuarios SET estado = 'suspendido', suscripcion_activa = 0 WHERE id = ?");
                            $update_stmt->bind_param("i", $user['id']);
                            $update_stmt->execute();
                            $update_stmt->close();

                            // Registrar en logs
                            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            $log_stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, ?, 'login', 0, 'Trial expirado - Acceso denegado', ?, ?)");
                            $log_stmt->bind_param("isss", $user['id'], $user['email'], $ip_address, $user_agent);
                            $log_stmt->execute();
                            $log_stmt->close();

                        } else {
                            // Trial activo - calcular días restantes
                            $dias_restantes = ceil(($fecha_fin_trial - $hoy) / 86400);

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['nombre'] = $user['nombre'];
                            $_SESSION['apellido'] = $user['apellido'];
                            $_SESSION['empresa_id'] = $user['empresa_id'];
                            $_SESSION['nombre_empresa'] = $user['nombre_empresa'];
                            $_SESSION['es_admin'] = $user['es_admin'];
                            $_SESSION['es_super_admin'] = 0;
                            $_SESSION['idioma_preferido'] = $user['idioma_preferido'];
                            $_SESSION['avatar'] = $user['avatar'];
                            $_SESSION['en_periodo_prueba'] = 1;
                            $_SESSION['dias_restantes_trial'] = $dias_restantes;

                            // Actualizar último acceso
                            $update_stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                            $update_stmt->bind_param("i", $user['id']);
                            $update_stmt->execute();
                            $update_stmt->close();

                            // Registrar en logs
                            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            $log_stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, ?, 'login', 1, 'Login exitoso - Trial activo', ?, ?)");
                            $log_stmt->bind_param("isss", $user['id'], $user['email'], $ip_address, $user_agent);
                            $log_stmt->execute();
                            $log_stmt->close();

                            // Crear sesión
                            $token = bin2hex(random_bytes(32));
                            $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+7 days'));
                            $session_stmt = $conn->prepare("INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, activo, fecha_expiracion) VALUES (?, ?, ?, ?, 1, ?)");
                            $session_stmt->bind_param("issss", $user['id'], $token, $ip_address, $user_agent, $fecha_expiracion);
                            $session_stmt->execute();
                            $session_stmt->close();

                            $_SESSION['session_token'] = $token;

                            header("Location: user/dashboard_user.php");
                            exit();
                        }
                    }
                    // Verificar suscripción activa
                    elseif ($user['suscripcion_activa'] == 1) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['nombre'] = $user['nombre'];
                        $_SESSION['apellido'] = $user['apellido'];
                        $_SESSION['empresa_id'] = $user['empresa_id'];
                        $_SESSION['nombre_empresa'] = $user['nombre_empresa'];
                        $_SESSION['es_admin'] = $user['es_admin'];
                        $_SESSION['es_super_admin'] = 0;
                        $_SESSION['idioma_preferido'] = $user['idioma_preferido'];
                        $_SESSION['avatar'] = $user['avatar'];
                        $_SESSION['en_periodo_prueba'] = 0;

                        // Actualizar último acceso
                        $update_stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                        $update_stmt->bind_param("i", $user['id']);
                        $update_stmt->execute();
                        $update_stmt->close();

                        // Registrar en logs
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                        $log_stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, ?, 'login', 1, 'Login exitoso - Suscripción activa', ?, ?)");
                        $log_stmt->bind_param("isss", $user['id'], $user['email'], $ip_address, $user_agent);
                        $log_stmt->execute();
                        $log_stmt->close();

                        // Crear sesión
                        $token = bin2hex(random_bytes(32));
                        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+7 days'));
                        $session_stmt = $conn->prepare("INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, activo, fecha_expiracion) VALUES (?, ?, ?, ?, 1, ?)");
                        $session_stmt->bind_param("issss", $user['id'], $token, $ip_address, $user_agent, $fecha_expiracion);
                        $session_stmt->execute();
                        $session_stmt->close();

                        $_SESSION['session_token'] = $token;

                        header("Location: user/dashboard_user.php");
                        exit();
                    } else {
                        $error = 'Tu suscripción no está activa. Por favor contacta al administrador.';
                    }
                }

            } else {
                $error = 'Credenciales incorrectas';

                // Registrar intento fallido
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $log_stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, ?, 'intento_fallido', 0, 'Contraseña incorrecta', ?, ?)");
                $log_stmt->bind_param("isss", $user['id'], $email_or_username, $ip_address, $user_agent);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } else {
            $error = 'Credenciales incorrectas';

            // Registrar intento fallido
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $log_stmt = $conn->prepare("INSERT INTO logs_acceso (email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, 'intento_fallido', 0, 'Usuario no encontrado', ?, ?)");
            $log_stmt->bind_param("sss", $email_or_username, $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
        }

        $stmt->close();
    }
}

// Mensaje de bienvenida si viene del registro
if (isset($_GET['registered'])) {
    $success = '¡Registro exitoso! Por favor inicia sesión.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .login-body {
            padding: 40px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px 12px 45px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.2rem;
            z-index: 10;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-bottom: 20px;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 576px) {
            .login-header h1 {
                font-size: 2rem;
            }

            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div style="max-width: 500px; width: 100%;">
        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
        </div>

        <div class="login-container">
            <div class="login-header">
                <h1><i class="fas fa-rocket"></i> CONECTA ERP</h1>
                <p>Inicia sesión en tu cuenta</p>
            </div>

            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Email o Usuario</label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="email_or_username" class="form-control" required autofocus value="<?php echo htmlspecialchars($_POST['email_or_username'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>

                    <div class="forgot-password">
                        <a href="forgot-password.php">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>

                    <div class="register-link">
                        ¿No tienes una cuenta? <a href="register.php">Regístrate gratis</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
