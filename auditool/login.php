<?php
session_start();
require_once('config/config.php');

$error = '';
$success = '';

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $conn = getDBConnection();

        $sql = "SELECT u.*, c.company_name, c.status as company_status, c.active_isos
                FROM users u
                JOIN companies c ON u.company_id = c.id
                WHERE u.email = ? AND u.status = 'active'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verificar password
            if (password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['company_name'] = $user['company_name'];
                $_SESSION['language'] = $user['language'];
                $_SESSION['active_isos'] = $user['active_isos'];
                $_SESSION['last_activity'] = time();

                // Actualizar last_login
                $update_sql = "UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();

                // Log activity
                logActivity($user['id'], 'login', 'authentication', 'Usuario inició sesión');

                $stmt->close();
                $conn->close();

                // Redirigir según rol
                if ($user['role'] === 'superadmin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                // Password incorrecto - incrementar intentos
                $update_sql = "UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("s", $email);
                $update_stmt->execute();
                $update_stmt->close();

                $error = 'Email o contraseña incorrectos';
            }
        } else {
            $error = 'Email o contraseña incorrectos';
        }

        $stmt->close();
        $conn->close();
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-left h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        .login-left p {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .feature-list {
            list-style: none;
        }
        .feature-list li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .feature-list li:before {
            content: "✓";
            background: white;
            color: #667eea;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .login-right {
            padding: 60px 40px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        .login-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        .login-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .divider {
            text-align: center;
            margin: 30px 0;
            color: #999;
            position: relative;
        }
        .divider:before,
        .divider:after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #e0e0e0;
        }
        .divider:before { left: 0; }
        .divider:after { right: 0; }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        .back-home a {
            color: #999;
            text-decoration: none;
            font-size: 14px;
        }
        .back-home a:hover {
            color: #667eea;
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            .login-left {
                padding: 40px 30px;
            }
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>🔐 AUDITOR PRO</h1>
            <p>Plataforma profesional de gestión Multi-ISO para empresas de todo el mundo.</p>
            <ul class="feature-list">
                <li>14 Normas ISO soportadas</li>
                <li>Multi-empresa y Multi-usuario</li>
                <li>Multi-idioma (ES/EN/PT)</li>
                <li>Multi-moneda y Multi-país</li>
                <li>Reportes y Matrices automáticas</li>
                <li>Soporte profesional 24/7</li>
            </ul>
        </div>

        <div class="login-right">
            <div class="logo">🔐 AUDITOR PRO</div>
            <h2 class="login-title">Iniciar Sesión</h2>
            <p class="login-subtitle">Ingrese sus credenciales para acceder</p>

            <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
            <div class="alert alert-success">
                ✓ Registro exitoso. Por favor inicie sesión.
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-error">
                ⏱️ Su sesión ha expirado. Por favor inicie sesión nuevamente.
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="correo@ejemplo.com"
                        required
                        autofocus
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <button type="submit" class="btn">
                    Iniciar Sesión
                </button>
            </form>

            <div class="forgot-password">
                <a href="forgot-password.php">¿Olvidó su contraseña?</a>
            </div>

            <div class="divider">o</div>

            <div class="register-link">
                ¿No tiene una cuenta? <a href="register.php">Registrarse gratis</a>
            </div>

            <div class="back-home">
                <a href="landing.php">← Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>
