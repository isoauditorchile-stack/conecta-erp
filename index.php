<?php
/**
 * CONECTA ERP - Página Principal Profesional
 */

require_once __DIR__ . '/includes/config.php';
initSession();

// Si ya tiene sesión, redirigir
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['email'] === 'auditorexchile@gmail.com') {
        header('Location: /admin/panel_super_admin.php');
    } else {
        header('Location: /user/dashboard_user.php');
    }
    exit;
}

$error = '';
$success = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $company_name = trim($_POST['company_name']);
    $phone = trim($_POST['phone']);
    $country = $_POST['country'];

    try {
        $db = Database::getInstance();

        // Verificar si el email ya existe
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $error = 'Este email ya está registrado';
        } else {
            $is_super_admin = ($email === 'auditorexchile@gmail.com');
            $status = $is_super_admin ? 'active' : 'pending_approval';

            $db->getConnection()->beginTransaction();

            // Crear empresa
            $company_id = $db->insert(
                "INSERT INTO companies (company_name, legal_name, country_id, created_at) VALUES (?, ?, ?, NOW())",
                [$company_name, $company_name, 1]
            );

            // Crear usuario
            $user_id = $db->insert(
                "INSERT INTO users (firstname, lastname, email, username, password, company_id, phone, country, status, is_admin, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $firstname,
                    $lastname,
                    $email,
                    $email,
                    password_hash($password, PASSWORD_BCRYPT),
                    $company_id,
                    $phone,
                    $country,
                    $status,
                    $is_super_admin ? 1 : 0
                ]
            );

            $db->getConnection()->commit();

            if ($is_super_admin) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;
                $_SESSION['is_admin'] = 1;
                $_SESSION['username'] = $email;
                header('Location: /admin/panel_super_admin.php');
                exit;
            } else {
                $success = '¡Registro exitoso! Tu cuenta está pendiente de aprobación. Te notificaremos por email.';
            }
        }
    } catch (Exception $e) {
        $error = 'Error al registrar. Por favor, intenta nuevamente.';
        error_log("Registration error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONECTA ERP - Sistema ERP Profesional | Competimos con SAP y Softland</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --accent: #f093fb;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 5%;
            z-index: 1000;
            transition: all 0.3s;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }

        .logo i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 0.5rem;
            border-radius: 10px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: white;
        }

        .nav-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-login {
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-login:hover {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.1);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .lang-selector {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8rem 5% 4rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.2), transparent);
            top: -200px;
            right: -200px;
            animation: pulse 8s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.7; }
        }

        .hero-content {
            max-width: 1200px;
            text-align: center;
            z-index: 1;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, white, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.5rem;
            color: #cbd5e1;
            margin-bottom: 3rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary);
        }

        /* Sections */
        .section {
            padding: 6rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            color: #cbd5e1;
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 4rem;
        }

        /* Registration Section */
        .registration-section {
            background: rgba(30, 41, 59, 0.5);
            padding: 6rem 5%;
        }

        .registration-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #f1f5f9;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--error);
            color: #fecaca;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--success);
            color: #a7f3d0;
        }

        /* Footer */
        .footer {
            background: rgba(15, 23, 42, 0.95);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 4rem 5% 2rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 0.75rem;
        }

        .footer-section a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1.2rem; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">
                <i class="fas fa-network-wired"></i>
                <span>CONECTA ERP</span>
            </a>
            <div class="nav-links">
                <a href="#registro">Registro</a>
                <a href="login.php" class="nav-btn btn-login">Iniciar Sesión</a>
                <a href="#registro" class="nav-btn btn-register">Registrarse</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>🚀 CONECTA ERP</h1>
            <p>Sistema ERP Empresarial Completo - Competimos con SAP y Softland</p>
            <p style="font-size: 1.1rem; margin-bottom: 2rem;">14 Módulos • 106 Submódulos • Multi-país • Multi-moneda • Multi-idioma</p>
            <div class="hero-buttons">
                <a href="#registro" class="btn btn-primary">
                    Empezar Ahora <i class="fas fa-arrow-right"></i>
                </a>
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </a>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section id="registro" class="registration-section">
        <div class="section-title">Registro de Cuenta</div>
        <div class="section-subtitle">Completa el formulario para crear tu cuenta. Si ya tienes una cuenta, <a href="login.php" style="color: var(--primary);">inicia sesión aquí</a>.</div>

        <div class="registration-container">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="firstname" required placeholder="Juan">
                </div>

                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="lastname" required placeholder="Pérez">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="juan@empresa.com">
                </div>

                <div class="form-group">
                    <label>Contraseña</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
                        <button type="button" onclick="togglePassword()" style="position: absolute; right: 70px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary); cursor: pointer; font-size: 1.2rem;">
                            <i id="eyeIcon" class="fas fa-eye"></i>
                        </button>
                        <button type="button" onclick="generatePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: var(--primary); border: none; color: white; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600;">
                            <i class="fas fa-key"></i> Generar
                        </button>
                    </div>
                    <small style="color: #94a3b8; font-size: 0.85rem; margin-top: 0.5rem; display: block;">Mínimo 6 caracteres. Se recomienda usar el generador.</small>
                </div>

                <div class="form-group">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="company_name" required placeholder="Mi Empresa S.A.">
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="phone" required placeholder="+56 9 1234 5678">
                </div>

                <div class="form-group">
                    <label>País</label>
                    <select name="country" required>
                        <option value="CL">🇨🇱 Chile</option>
                        <option value="AR">🇦🇷 Argentina</option>
                        <option value="PE">🇵🇪 Perú</option>
                        <option value="CO">🇨🇴 Colombia</option>
                        <option value="MX">🇲🇽 México</option>
                        <option value="BR">🇧🇷 Brasil</option>
                        <option value="US">🇺🇸 Estados Unidos</option>
                        <option value="ES">🇪🇸 España</option>
                    </select>
                </div>

                <button type="submit" name="register" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Crear Cuenta <i class="fas fa-arrow-right"></i>
                </button>

                <p style="text-align: center; margin-top: 1.5rem; color: #94a3b8; font-size: 0.9rem;">
                    Al registrarte, aceptas nuestros <a href="#" style="color: var(--primary);">Términos y Condiciones</a>
                </p>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3><i class="fas fa-network-wired"></i> CONECTA ERP</h3>
                <p style="color: #94a3b8; margin-bottom: 1rem;">Sistema ERP profesional para empresas modernas. Compite con SAP y Softland.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h3>Producto</h3>
                <ul>
                    <li><a href="#">Características</a></li>
                    <li><a href="#">Módulos</a></li>
                    <li><a href="#">Precios</a></li>
                    <li><a href="#">Demo</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Soporte</h3>
                <ul>
                    <li><a href="#">Documentación</a></li>
                    <li><a href="#">Centro de Ayuda</a></li>
                    <li><a href="#">API</a></li>
                    <li><a href="#">Contacto</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Empresa</h3>
                <ul>
                    <li><a href="#">Nosotros</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Casos de Éxito</a></li>
                    <li><a href="#">Trabaja con Nosotros</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 CONECTA ERP. Todos los derechos reservados. | Sistema de Producción v2.0.0</p>
            <p style="margin-top: 0.5rem;">Admin: auditorexchile@gmail.com</p>
        </div>
    </footer>

    <script>
        function generatePassword() {
            const length = 16;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
            let password = "";

            // Asegurar al menos un carácter de cada tipo
            const lower = "abcdefghijklmnopqrstuvwxyz";
            const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            const numbers = "0123456789";
            const special = "!@#$%^&*()_+-=";

            password += lower[Math.floor(Math.random() * lower.length)];
            password += upper[Math.floor(Math.random() * upper.length)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += special[Math.floor(Math.random() * special.length)];

            // Completar el resto de la contraseña
            for (let i = password.length; i < length; i++) {
                password += charset[Math.floor(Math.random() * charset.length)];
            }

            // Mezclar los caracteres
            password = password.split('').sort(() => Math.random() - 0.5).join('');

            // Establecer la contraseña y mostrarla
            const passwordInput = document.getElementById('password');
            passwordInput.type = 'text';
            passwordInput.value = password;

            // Cambiar icono a visible
            document.getElementById('eyeIcon').classList.remove('fa-eye');
            document.getElementById('eyeIcon').classList.add('fa-eye-slash');

            // Copiar al portapapeles
            navigator.clipboard.writeText(password).then(() => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.textContent = '✓ Contraseña generada y copiada al portapapeles';
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, var(--success), #059669); color: white; padding: 1rem 2rem; border-radius: 10px; z-index: 10000; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.3);';
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.transition = 'opacity 0.5s';
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 500);
                }, 3000);
            });
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
