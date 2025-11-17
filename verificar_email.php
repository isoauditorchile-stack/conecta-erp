<?php
/**
 * VERIFICAR EMAIL - CONECTA ERP
 * Confirmación de cuenta de usuario
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$verificado = false;

// Verificar token
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Buscar usuario con este token
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, email_verificado
                            FROM usuarios
                            WHERE verificacion_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        if ($usuario['email_verificado'] == 1) {
            $success = 'Tu email ya ha sido verificado anteriormente.';
            $verificado = true;
        } else {
            // Verificar email
            $stmt = $conn->prepare("UPDATE usuarios
                                    SET email_verificado = 1,
                                        verificacion_token = NULL
                                    WHERE id = ?");
            $stmt->bind_param("i", $usuario['id']);

            if ($stmt->execute()) {
                $success = '¡Email verificado exitosamente! Ya puedes iniciar sesión.';
                $verificado = true;

                // Log de auditoría
                logAuditoria('email_verificado', 'usuarios', $usuario['id'], null, null,
                    "Email verificado: {$usuario['email']}");
            } else {
                $error = 'Error al verificar el email. Intenta nuevamente.';
            }
        }
    } else {
        $error = 'El enlace de verificación es inválido.';
    }

    $stmt->close();
} else {
    $error = 'Enlace de verificación inválido.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Email - CONECTA ERP</title>
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
        .verify-container {
            width: 100%;
            max-width: 550px;
            padding: 20px;
        }
        .verify-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .verify-header {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .verify-header.error {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
        }
        .verify-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .verify-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .verify-body {
            padding: 40px 30px;
            text-align: center;
        }
        .verify-icon {
            font-size: 80px;
            margin-bottom: 25px;
        }
        .success-icon {
            color: #48bb78;
        }
        .error-icon {
            color: #f56565;
        }
        .verify-message {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-verify {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
            text-align: left;
        }
        .info-box ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 8px 0;
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
    <div class="verify-container">
        <div class="verify-card">
            <?php if ($verificado): ?>
                <div class="verify-header">
                    <h1><i class="fas fa-check-circle"></i> ¡Verificado!</h1>
                    <p>Tu email ha sido confirmado</p>
                </div>

                <div class="verify-body">
                    <div class="verify-icon success-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>

                    <div class="verify-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>

                    <div class="info-box">
                        <strong><i class="fas fa-rocket"></i> ¿Qué sigue?</strong>
                        <ul>
                            <li>Inicia sesión con tus credenciales</li>
                            <li>Explora tu periodo de prueba de 14 días</li>
                            <li>Configura los datos de tu empresa</li>
                            <li>Invita a tu equipo de trabajo</li>
                        </ul>
                    </div>

                    <a href="login.php?verified=1" class="btn-verify">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a>
                </div>

            <?php else: ?>
                <div class="verify-header error">
                    <h1><i class="fas fa-exclamation-triangle"></i> Error</h1>
                    <p>No se pudo verificar tu email</p>
                </div>

                <div class="verify-body">
                    <div class="verify-icon error-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>

                    <div class="verify-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                    <div class="info-box">
                        <strong><i class="fas fa-question-circle"></i> ¿Qué hacer?</strong>
                        <ul>
                            <li>Verifica que el enlace esté completo</li>
                            <li>Solicita un nuevo email de verificación</li>
                            <li>Contacta a soporte si el problema persiste</li>
                        </ul>
                    </div>

                    <a href="login.php" class="btn-verify">
                        <i class="fas fa-arrow-left"></i> Volver al Login
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark_mode.js"></script>

    <!-- Footer Profesional -->
    <?php include 'user/includes/footer.php'; ?>

</body>
</html>
