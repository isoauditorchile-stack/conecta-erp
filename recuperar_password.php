<?php
/**
 * RECUPERAR CONTRASEÑA - CONECTA ERP
 * Solicitud de restablecimiento de contraseña
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/email_functions.php';

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: user/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recuperar'])) {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Por favor ingresa tu email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        // Buscar usuario
        $stmt = $conn->prepare("SELECT id, nombre, apellido, email FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();

            // Generar token de recuperación
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Guardar token en BD
            $stmt = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expira, $usuario['id']);
            $stmt->execute();

            // Enviar email
            if (enviarEmailRecuperarPassword($conn, $usuario, $token)) {
                $success = 'Te hemos enviado un email con las instrucciones para restablecer tu contraseña.';

                // Log de auditoría
                logAuditoria('solicitud_reset_password', 'usuarios', $usuario['id'], null, null,
                    "Solicitud de recuperación de contraseña para {$email}");
            } else {
                $error = 'Error al enviar el email. Intenta nuevamente.';
            }
        } else {
            // Por seguridad, mostrar el mismo mensaje aunque el usuario no exista
            $success = 'Si el email existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña.';
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
    <title>Recuperar Contraseña - CONECTA ERP</title>
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
        .recovery-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        .recovery-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .recovery-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .recovery-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .recovery-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .recovery-body {
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
        .btn-recovery {
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
        .btn-recovery:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 14px;
            color: #4a5568;
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
    <div class="recovery-container">
        <div class="recovery-card">
            <div class="recovery-header">
                <h1><i class="fas fa-key"></i> Recuperar Contraseña</h1>
                <p>Te ayudaremos a recuperar el acceso</p>
            </div>

            <div class="recovery-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <div class="info-box">
                        <strong><i class="fas fa-info-circle"></i> Revisa tu email</strong><br>
                        Hemos enviado un enlace de recuperación a tu correo. El enlace es válido por 24 horas.
                    </div>
                <?php else: ?>
                    <div class="info-box">
                        <strong><i class="fas fa-info-circle"></i> ¿Olvidaste tu contraseña?</strong><br>
                        No te preocupes, ingresa tu email y te enviaremos instrucciones para crear una nueva.
                    </div>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="tucorreo@empresa.cl" required autofocus
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <button type="submit" name="recuperar" class="btn btn-recovery">
                            <i class="fas fa-paper-plane"></i> Enviar Instrucciones
                        </button>
                    </form>
                <?php endif; ?>

                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Volver a Iniciar Sesión
                    </a>
                </div>
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
</body>
</html>
