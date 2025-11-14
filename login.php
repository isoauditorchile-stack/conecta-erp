<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CONECTA ERP</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-gradient-start: #667eea;
            --bg-gradient-end: #764ba2;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
        }

        .login-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 50px 40px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .login-logo {
            font-size: 4rem;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .login-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .login-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        .login-body {
            padding: 40px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            padding: 14px 18px;
            color: var(--text-secondary);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .input-group .form-control {
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-size: 1.125rem;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
            color: var(--text-secondary);
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .form-check {
            margin: 15px 0;
        }

        .form-check-label {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .spinner-border-sm {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 2px;
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .admin-info {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .admin-info strong {
            color: #92400e;
            display: block;
            margin-bottom: 8px;
        }

        .admin-info code {
            background: white;
            padding: 3px 8px;
            border-radius: 4px;
            color: #92400e;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <h1 class="login-title">CONECTA ERP</h1>
                <p class="login-subtitle">Bienvenido de vuelta</p>
            </div>

            <div class="login-body">
                <?php
                session_start();

                if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['demo']) && $_GET['demo'] === 'admin'): ?>
                    <div class="admin-info">
                        <strong><i class="bi bi-info-circle-fill me-2"></i> Acceso de Administrador:</strong>
                        <div>Email: <code>auditorexchile@gmail.com</code></div>
                        <div>Usuario: <code>auditorex chile</code></div>
                        <div>Contraseña: <code>admin123</code></div>
                    </div>
                <?php endif; ?>

                <form id="loginForm" method="POST" action="">
                    <div class="mb-3">
                        <label for="login" class="form-label">Email o Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-circle"></i>
                            </span>
                            <input type="text"
                                   class="form-control"
                                   id="login"
                                   name="login"
                                   placeholder="auditorexchile@gmail.com o auditorex chile"
                                   required
                                   autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="••••••••"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Recordarme
                            </label>
                        </div>

                        <div class="forgot-password">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="btnSubmit">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Iniciar Sesión
                    </button>
                </form>

                <div class="register-link">
                    ¿No tienes una cuenta?
                    <a href="register.php">Regístrate gratis</a>
                </div>
            </div>
        </div>

        <div class="back-home">
            <a href="index.php">
                <i class="bi bi-arrow-left"></i>
                Volver al Inicio
            </a>
        </div>
    </div>

    <!-- Modal Recuperar Contraseña -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border: none;">
                    <h5 class="modal-title"><i class="bi bi-key-fill me-2"></i> Recuperar Contraseña</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 30px;">
                    <p class="text-secondary mb-3">
                        Ingresa tu email y te enviaremos instrucciones para restablecer tu contraseña.
                    </p>
                    <form id="forgotPasswordForm">
                        <div class="mb-3">
                            <label for="recovery_email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="recovery_email" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-login">
                            <i class="bi bi-send me-2"></i>
                            Enviar Instrucciones
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Manejo del formulario de login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btnSubmit = document.getElementById('btnSubmit');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando sesión...';
        });

        // Recuperar contraseña
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Se han enviado las instrucciones a tu email (Función en desarrollo)');
            bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
        });
    </script>
</body>
</html>

<?php
// =========================
// PROCESAMIENTO DEL LOGIN
// =========================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/config.php';

    $db = Database::getInstance();

    try {
        $login = trim($_POST['login']); // Puede ser email o username
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        if (empty($login) || empty($password)) {
            throw new Exception('Email/Usuario y contraseña son requeridos');
        }

        // Buscar usuario por email o username
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1",
            [$login, $login]
        );

        if (!$user) {
            throw new Exception('Credenciales incorrectas');
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            throw new Exception('Credenciales incorrectas');
        }

        // Verificar si está activo
        if (!$user['is_active']) {
            throw new Exception('Tu cuenta está desactivada. Contacta al administrador.');
        }

        // Verificar trial para usuarios no admin
        if (!$user['is_admin']) {
            if ($user['status'] === 'trial') {
                $trialEnds = strtotime($user['trial_ends_at']);
                if ($trialEnds < time()) {
                    throw new Exception('Tu período de prueba ha expirado. Por favor, selecciona un plan para continuar.');
                }

                // Verificar si quedan 3 días o menos
                $daysLeft = ceil(($trialEnds - time()) / 86400);
                if ($daysLeft <= 3) {
                    $_SESSION['trial_warning'] = "Tu período de prueba expira en $daysLeft días. Elige un plan para continuar.";
                }
            } elseif ($user['status'] === 'suspended') {
                throw new Exception('Tu cuenta está suspendida. Por favor, contacta a soporte.');
            } elseif ($user['status'] === 'cancelled') {
                throw new Exception('Tu cuenta ha sido cancelada.');
            }
        }

        // Crear sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = (bool) $user['is_admin'];
        $_SESSION['language'] = $user['language_code'];
        $_SESSION['country_id'] = $user['country_id'];
        $_SESSION['tax_id'] = $user['tax_id'];
        $_SESSION['company_id'] = $user['company_id'];

        // Actualizar último login
        $db->update(
            "UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?",
            [$_SERVER['REMOTE_ADDR'] ?? 'unknown', $user['id']]
        );

        // Log de actividad
        logActivity($user['id'], 'user_login', 'Usuario inició sesión', 'AUTH');

        // Cookie "Recordarme" (30 días)
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            // TODO: Guardar token en base de datos para validación
        }

        // Redirigir según el tipo de usuario
        if ($user['is_admin']) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: login.php');
        exit;
    }
}
?>
