<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CONECTA ERP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php
require_once 'includes/config.php';

$error = '';

// Si ya está autenticado, redirigir
if (isAuthenticated()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Por favor ingrese email y contraseña';
    } else {
        try {
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT u.*, c.name as country_name, l.native_name as language_name, sp.plan_name, sp.plan_code
                 FROM users u
                 LEFT JOIN countries c ON u.country_id = c.id
                 LEFT JOIN languages l ON u.language_id = l.id
                 LEFT JOIN subscription_plans sp ON u.plan_id = sp.id
                 WHERE u.email = ?",
                [$email]
            );

            if ($user && password_verify($password, $user['password'])) {
                // Verificar si está aprobado
                if (!$user['is_approved'] && !$user['is_admin']) {
                    $error = 'Su cuenta aún no ha sido aprobada por el administrador. Recibirá un correo cuando sea aprobada.';
                }
                // Verificar estado
                elseif (!$user['is_admin'] && !in_array($user['status'], ['trial', 'active'])) {
                    $error = 'Su cuenta se encuentra ' . $user['status'] . '. Por favor contacte al administrador.';
                }
                // Verificar trial expirado
                elseif ($user['status'] === 'trial' && $user['trial_ends_at'] && strtotime($user['trial_ends_at']) < time()) {
                    $error = 'Su período de prueba ha expirado. Por favor seleccione un plan de suscripción.';
                }
                else {
                    // Login exitoso
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    $_SESSION['plan_code'] = $user['plan_code'];
                    $_SESSION['language_code'] = $user['language_name'];

                    // Actualizar último login
                    $db->update(
                        "UPDATE users SET last_login = NOW() WHERE id = ?",
                        [$user['id']]
                    );

                    // Log de actividad
                    logActivity($user['id'], 'login', 'Usuario inició sesión', 'auth');

                    // Verificar si está próximo a vencer el trial
                    if ($user['status'] === 'trial' && $user['trial_ends_at']) {
                        $daysLeft = ceil((strtotime($user['trial_ends_at']) - time()) / 86400);
                        if ($daysLeft <= 3) {
                            showAlert("Su período de prueba expira en $daysLeft día(s). Por favor seleccione un plan.", 'warning');
                        }
                    }

                    // Redirigir según tipo de usuario
                    if ($user['is_admin']) {
                        redirect('admin/dashboard.php');
                    } else {
                        redirect('user/dashboard.php');
                    }
                }
            } else {
                $error = 'Email o contraseña incorrectos';

                // Log de intento fallido
                if ($user) {
                    logActivity($user['id'], 'login_failed', 'Intento de inicio de sesión fallido', 'auth');
                }
            }
        } catch (Exception $e) {
            $error = 'Error al iniciar sesión. Por favor intente nuevamente.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>

    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Panel Izquierdo - Información -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="text-white text-center px-5">
                    <h1 class="display-3 fw-bold mb-4">
                        <i class="fas fa-chart-line"></i><br>CONECTA ERP
                    </h1>
                    <p class="lead mb-5" style="font-size: 1.5rem;">
                        Sistema ERP Profesional<br>
                        Multi-Empresa • Multi-Idioma • Multi-País
                    </p>

                    <div class="row g-4 text-center">
                        <div class="col-6">
                            <div class="bg-white bg-opacity-10 p-4 rounded-4">
                                <h2 class="display-4 fw-bold mb-2">14</h2>
                                <p class="mb-0">Módulos</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white bg-opacity-10 p-4 rounded-4">
                                <h2 class="display-4 fw-bold mb-2">106</h2>
                                <p class="mb-0">Submódulos</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white bg-opacity-10 p-4 rounded-4">
                                <h2 class="display-4 fw-bold mb-2">9</h2>
                                <p class="mb-0">Idiomas</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white bg-opacity-10 p-4 rounded-4">
                                <h2 class="display-4 fw-bold mb-2">9</h2>
                                <p class="mb-0">Países</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Derecho - Formulario de Login -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-5">
                <div class="w-100" style="max-width: 500px;">
                    <!-- Logo móvil -->
                    <div class="text-center d-lg-none mb-5">
                        <h1 class="display-5 fw-bold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            <i class="fas fa-chart-line"></i> CONECTA ERP
                        </h1>
                    </div>

                    <!-- Card de Login -->
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <h2 class="fw-bold mb-2">Bienvenido de vuelta</h2>
                            <p class="text-muted mb-4">Ingrese sus credenciales para continuar</p>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $alert = getAlert();
                            if ($alert):
                            ?>
                                <div class="alert alert-<?php echo $alert['type']; ?>">
                                    <i class="fas fa-info-circle"></i> <?php echo $alert['message']; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="mt-4">
                                <div class="form-group">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-envelope text-primary"></i> Email
                                    </label>
                                    <input type="email" name="email" class="form-control form-control-lg" required
                                           placeholder="ejemplo@empresa.com"
                                           value="<?php echo $_POST['email'] ?? ''; ?>"
                                           autofocus>
                                </div>

                                <div class="form-group">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-lock text-primary"></i> Contraseña
                                    </label>
                                    <div class="position-relative">
                                        <input type="password" name="password" id="password" class="form-control form-control-lg" required
                                               placeholder="Ingrese su contraseña">
                                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                        <label class="form-check-label" for="remember">
                                            Recordarme
                                        </label>
                                    </div>
                                    <a href="#" class="text-primary">¿Olvidó su contraseña?</a>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg btn-block w-100 mb-3">
                                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                                </button>

                                <div class="text-center">
                                    <p class="text-muted mb-0">
                                        ¿No tiene cuenta?
                                        <a href="register.php" class="text-primary fw-semibold">Registrarse gratis</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Acceso rápido de demostración -->
                    <div class="card border-0 shadow-sm mt-4" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-user-shield"></i> Acceso de Administrador
                            </h6>
                            <p class="mb-2"><strong>Email:</strong> auditorexchile@gmail.com</p>
                            <p class="mb-0"><strong>Password:</strong> admin123</p>
                        </div>
                    </div>

                    <!-- Links adicionales -->
                    <div class="text-center mt-4">
                        <a href="index_new.php" class="text-muted me-3">
                            <i class="fas fa-home"></i> Volver al inicio
                        </a>
                        <a href="mailto:auditorexchile@gmail.com" class="text-muted">
                            <i class="fas fa-envelope"></i> Contacto
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
