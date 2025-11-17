<?php
/**
 * RESET PASSWORD - CONECTA ERP
 * Formulario para establecer nueva contraseña
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: user/dashboard.php');
    exit();
}

$error = '';
$success = '';
$token_valido = false;
$usuario = null;

// Verificar token
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Buscar usuario con este token válido
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email FROM usuarios
                            WHERE reset_token = ?
                            AND reset_token_expira > NOW()
                            AND activo = 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $token_valido = true;
        $usuario = $result->fetch_assoc();
    } else {
        $error = 'El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.';
    }

    $stmt->close();
} else {
    $error = 'Enlace de recuperación inválido.';
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $token_valido) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($password) || empty($password_confirm)) {
        $error = 'Por favor complete todos los campos';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Hashear nueva contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Actualizar contraseña y limpiar token
        $stmt = $conn->prepare("UPDATE usuarios
                                SET password = ?,
                                    reset_token = NULL,
                                    reset_token_expira = NULL
                                WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $usuario['id']);

        if ($stmt->execute()) {
            $success = true;

            // Log de auditoría
            logAuditoria('reset_password', 'usuarios', $usuario['id'], null, null,
                "Contraseña restablecida exitosamente");

            // Invalidar todas las sesiones activas por seguridad
            $stmt = $conn->prepare("UPDATE sesiones SET activo = 0 WHERE usuario_id = ?");
            $stmt->bind_param("i", $usuario['id']);
            $stmt->execute();
        } else {
            $error = 'Error al actualizar la contraseña. Intenta nuevamente.';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dark_mode.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .reset-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .reset-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .reset-body {
            padding: 40px 30px;
        }
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .password-strength {
            margin-top: 10px;
            font-size: 13px;
        }
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            margin-top: 5px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            transition: all 0.3s;
            width: 0%;
        }
        .strength-weak { background: #f56565; width: 33%; }
        .strength-medium { background: #ed8936; width: 66%; }
        .strength-strong { background: #48bb78; width: 100%; }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #718096;
        }
        .password-wrapper {
            position: relative;
        }
        .requirements {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 14px;
        }
        .requirements ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .requirements li {
            margin: 5px 0;
            color: #4a5568;
        }
        .success-box {
            text-align: center;
            padding: 20px;
        }
        .success-icon {
            font-size: 64px;
            color: #48bb78;
            margin-bottom: 20px;
        }
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        .back-home a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h1><i class="fas fa-shield-alt"></i> Nueva Contraseña</h1>
                <p>Crea una contraseña segura</p>
            </div>

            <div class="reset-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <?php if (strpos($error, 'expirado') !== false): ?>
                        <div class="text-center mt-3">
                            <a href="recuperar_password.php" class="btn btn-outline-primary">
                                <i class="fas fa-redo"></i> Solicitar Nuevo Enlace
                            </a>
                        </div>
                    <?php endif; ?>

                <?php elseif ($success): ?>
                    <div class="success-box">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 style="color: #48bb78; margin-bottom: 15px;">¡Contraseña Actualizada!</h3>
                        <p style="color: #4a5568; margin-bottom: 25px;">
                            Tu contraseña ha sido cambiada exitosamente. Ya puedes iniciar sesión con tu nueva contraseña.
                        </p>
                        <a href="login.php?reset=1" class="btn btn-reset">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </a>
                    </div>

                <?php elseif ($token_valido): ?>
                    <div class="requirements">
                        <strong><i class="fas fa-info-circle"></i> Requisitos de contraseña:</strong>
                        <ul>
                            <li>Mínimo 8 caracteres</li>
                            <li>Combina letras mayúsculas y minúsculas</li>
                            <li>Incluye números y símbolos</li>
                            <li>No uses información personal</li>
                        </ul>
                    </div>

                    <form method="POST" action="" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Nueva Contraseña
                            </label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Ingresa tu nueva contraseña" required minlength="8">
                                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span id="strengthText"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">
                                <i class="fas fa-lock"></i> Confirmar Contraseña
                            </label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                       placeholder="Repite tu nueva contraseña" required minlength="8">
                                <i class="fas fa-eye password-toggle" id="togglePasswordConfirm"></i>
                            </div>
                            <div id="matchMessage" class="mt-2" style="font-size: 13px;"></div>
                        </div>

                        <button type="submit" name="reset" class="btn btn-reset" id="submitBtn" disabled>
                            <i class="fas fa-save"></i> Restablecer Contraseña
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark_mode.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        document.getElementById('togglePasswordConfirm')?.addEventListener('click', function() {
            const password = document.getElementById('password_confirm');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Password strength meter
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');

            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[@$!%*?&]/.test(password)) strength++;

            strengthFill.className = 'strength-fill';
            if (strength <= 1) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Débil';
                strengthText.style.color = '#f56565';
            } else if (strength <= 3) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Media';
                strengthText.style.color = '#ed8936';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Fuerte';
                strengthText.style.color = '#48bb78';
            }

            checkPasswordMatch();
        });

        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password')?.value;
            const confirm = document.getElementById('password_confirm')?.value;
            const matchMessage = document.getElementById('matchMessage');
            const submitBtn = document.getElementById('submitBtn');

            if (confirm.length > 0) {
                if (password === confirm) {
                    matchMessage.innerHTML = '<i class="fas fa-check-circle" style="color: #48bb78;"></i> Las contraseñas coinciden';
                    matchMessage.style.color = '#48bb78';
                    if (password.length >= 8) {
                        submitBtn.disabled = false;
                    }
                } else {
                    matchMessage.innerHTML = '<i class="fas fa-times-circle" style="color: #f56565;"></i> Las contraseñas no coinciden';
                    matchMessage.style.color = '#f56565';
                    submitBtn.disabled = true;
                }
            } else {
                matchMessage.innerHTML = '';
                submitBtn.disabled = true;
            }
        }

        document.getElementById('password_confirm')?.addEventListener('input', checkPasswordMatch);
    </script>

    <!-- Footer Profesional -->
    <?php include 'user/includes/footer.php'; ?>

</body>
</html>
