<?php
/**
 * CONECTA ERP - Inicio de Sesión
 */

require_once __DIR__ . '/includes/config.php';
initSession();

$error = '';

// Si ya tiene sesión activa, redirigir al dashboard correspondiente
if (isset($_SESSION['user_id'])) {
    // SOLO auditorexchile@gmail.com tiene acceso al panel admin
    if ($_SESSION['email'] === 'auditorexchile@gmail.com') {
        header('Location: /admin/panel_super_admin.php');
    } else {
        // Todos los demás usuarios van al dashboard de usuario
        header('Location: /user/dashboard_user.php');
    }
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor, ingresa tu email y contraseña';
    } else {
        try {
            $db = Database::getInstance();
            $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

            if ($user && password_verify($password, $user['password'])) {
                // Verificar estado del usuario
                if ($user['status'] === 'pending_approval') {
                    $error = 'Tu cuenta está pendiente de aprobación por el administrador. Te notificaremos por email cuando sea aprobada.';
                } else if ($user['status'] === 'rejected') {
                    $error = 'Tu cuenta ha sido rechazada. Contacta al administrador para más información.';
                } else if ($user['status'] === 'expired') {
                    $error = 'Tu período de trial ha expirado. Por favor, realiza un pago para continuar usando el sistema.';
                } else if ($user['status'] === 'suspended') {
                    $error = 'Tu cuenta ha sido suspendida. Contacta al administrador.';
                } else {
                    // Login exitoso
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['firstname'] = $user['firstname'];
                    $_SESSION['lastname'] = $user['lastname'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    $_SESSION['company_id'] = $user['company_id'];
                    $_SESSION['language'] = $user['language'];
                    $_SESSION['last_activity'] = time();

                    // Registrar actividad
                    logActivity($user['id'], 'login', 'Inicio de sesión exitoso', 'auth');

                    // Redirigir según tipo de usuario
                    // SOLO auditorexchile@gmail.com puede acceder al panel admin
                    if ($user['email'] === 'auditorexchile@gmail.com') {
                        header('Location: /admin/panel_super_admin.php');
                    } else {
                        // Todos los demás usuarios van al dashboard de usuario
                        header('Location: /user/dashboard_user.php');
                    }
                    exit;
                }
            } else {
                $error = 'Email o contraseña incorrectos';
            }
        } catch (Exception $e) {
            $error = 'Error al iniciar sesión. Por favor, intenta nuevamente.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CONECTA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }
        .login-header p {
            opacity: 0.95;
            font-size: 1rem;
        }
        .login-body {
            padding: 2.5rem 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .btn:active {
            transform: translateY(0);
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
            font-size: 0.9rem;
        }
        .divider {
            text-align: center;
            margin: 2rem 0;
            color: #6b7280;
            position: relative;
        }
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e5e7eb;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6b7280;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .info-box {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #1e40af;
        }
        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🚀 CONECTA ERP</h1>
            <p>Sistema de Gestión Empresarial</p>
        </div>

        <div class="login-body">
            <h2 style="color: #1f2937; margin-bottom: 1.5rem; font-size: 1.5rem;">Iniciar Sesión</h2>

            <?php if ($error): ?>
            <div class="alert">
                <strong>⚠️ Error</strong><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="tu@email.com" autocomplete="email">
                </div>

                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                </div>

                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>

            <div class="divider">o</div>

            <div class="register-link">
                ¿No tienes una cuenta? <a href="index.php">Regístrate aquí</a>
            </div>

            <div class="info-box" style="margin-top: 2rem;">
                <strong>ℹ️ Nota Importante:</strong>
                Si acabas de registrarte, tu cuenta debe ser aprobada por el administrador antes de poder iniciar sesión. Recibirás un email cuando tu cuenta sea aprobada.
            </div>
        </div>
    </div>
</body>
</html>
