<?php
/**
 * 2FA - AUTENTICACIÓN DE DOS FACTORES - CONECTA ERP
 * Verificación de código de dos factores
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Si no hay sesión temporal de 2FA, redirigir al login
if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$usuario_id = $_SESSION['2fa_user_id'];

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT id, nombre, apellido, email, codigo_2fa, codigo_2fa_expira
                        FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header('Location: login.php');
    exit();
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Procesar verificación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verificar'])) {
    $codigo = trim($_POST['codigo']);

    if (empty($codigo)) {
        $error = 'Por favor ingresa el código';
    } elseif (strlen($codigo) !== 6 || !ctype_digit($codigo)) {
        $error = 'El código debe tener 6 dígitos';
    } else {
        // Verificar si el código no ha expirado (válido por 10 minutos)
        if (strtotime($usuario['codigo_2fa_expira']) < time()) {
            $error = 'El código ha expirado. Solicita uno nuevo.';
        } elseif ($codigo === $usuario['codigo_2fa']) {
            // Código correcto - completar login

            // Crear sesión completa
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['apellido'] = $usuario['apellido'];
            $_SESSION['email'] = $usuario['email'];

            // Obtener datos adicionales
            $stmt = $conn->prepare("SELECT empresa_id, plan_id, es_super_admin, en_periodo_prueba, fecha_fin_trial
                                    FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $datos = $result->fetch_assoc();

            $_SESSION['empresa_id'] = $datos['empresa_id'];
            $_SESSION['plan_id'] = $datos['plan_id'];
            $_SESSION['es_super_admin'] = $datos['es_super_admin'];
            $_SESSION['en_periodo_prueba'] = $datos['en_periodo_prueba'];
            $_SESSION['fecha_fin_trial'] = $datos['fecha_fin_trial'];

            // Limpiar sesión temporal
            unset($_SESSION['2fa_user_id']);

            // Limpiar código 2FA usado
            $stmt = $conn->prepare("UPDATE usuarios SET codigo_2fa = NULL, codigo_2fa_expira = NULL WHERE id = ?");
            $stmt->bind_param("i", $usuario['id']);
            $stmt->execute();

            // Log de acceso exitoso
            logAcceso($usuario['id'], 'login_2fa', 'exitoso', $_SERVER['REMOTE_ADDR']);

            // Redirigir según rol
            if ($datos['es_super_admin'] == 1) {
                header('Location: admin/dashboard_admin.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit();
        } else {
            $error = 'Código incorrecto. Intenta nuevamente.';

            // Log de intento fallido
            logAcceso($usuario['id'], 'login_2fa', 'fallido', $_SERVER['REMOTE_ADDR'], 'Código 2FA incorrecto');
        }
    }
}

// Reenviar código
if (isset($_GET['reenviar'])) {
    // Generar nuevo código
    $nuevo_codigo = sprintf('%06d', mt_rand(0, 999999));
    $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $conn->prepare("UPDATE usuarios SET codigo_2fa = ?, codigo_2fa_expira = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nuevo_codigo, $expira, $usuario['id']);
    $stmt->execute();

    // Enviar por email
    require_once 'includes/email_functions.php';
    // TODO: Crear función enviarEmail2FA($conn, $usuario, $codigo)

    $_SESSION['mensaje_exito'] = 'Nuevo código enviado a tu email';
    header('Location: 2fa.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA - CONECTA ERP</title>
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
        .twofa-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        .twofa-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .twofa-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .twofa-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .twofa-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .twofa-body {
            padding: 40px 30px;
        }
        .code-input {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 10px;
            border: 3px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            width: 100%;
            transition: all 0.3s;
        }
        .code-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        .btn-verify {
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
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
        .info-box strong {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
        }
        .resend-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .resend-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .resend-link a:hover {
            text-decoration: underline;
        }
        .security-icon {
            font-size: 64px;
            color: #667eea;
            text-align: center;
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
    <div class="twofa-container">
        <div class="twofa-card">
            <div class="twofa-header">
                <h1><i class="fas fa-shield-alt"></i> Verificación 2FA</h1>
                <p>Autenticación de dos factores</p>
            </div>

            <div class="twofa-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['mensaje_exito']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_exito']); ?>
                <?php endif; ?>

                <div class="security-icon">
                    <i class="fas fa-lock"></i>
                </div>

                <div class="info-box">
                    <strong><i class="fas fa-envelope"></i> Código de seguridad enviado</strong>
                    Hemos enviado un código de 6 dígitos a tu email:<br>
                    <strong><?php echo htmlspecialchars($usuario['email']); ?></strong>
                </div>

                <form method="POST" action="" id="verifyForm">
                    <div class="mb-3">
                        <label for="codigo" class="form-label text-center d-block" style="font-weight: 600;">
                            Ingresa el código
                        </label>
                        <input type="text" class="code-input" id="codigo" name="codigo"
                               placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                               required autofocus autocomplete="off">
                    </div>

                    <button type="submit" name="verificar" class="btn btn-verify">
                        <i class="fas fa-check-circle"></i> Verificar Código
                    </button>
                </form>

                <div class="info-box mt-3" style="background: #fffaf0; border-left-color: #ed8936;">
                    <strong><i class="fas fa-clock"></i> El código expira en 10 minutos</strong>
                    Si no recibes el email, revisa tu carpeta de spam.
                </div>

                <div class="resend-link">
                    ¿No recibiste el código?
                    <a href="?reenviar=1">
                        <i class="fas fa-redo"></i> Reenviar código
                    </a>
                </div>
            </div>
        </div>

        <div class="back-home">
            <a href="login.php" onclick="return confirm('¿Estás seguro? Deberás iniciar sesión nuevamente.');">
                <i class="fas fa-arrow-left"></i> Cancelar y Volver
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark_mode.js"></script>
    <script>
        // Auto-submit cuando se ingresan 6 dígitos
        document.getElementById('codigo').addEventListener('input', function(e) {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');

            // Auto-submit cuando llega a 6 dígitos
            if (this.value.length === 6) {
                document.getElementById('verifyForm').submit();
            }
        });

        // Focus automático
        document.getElementById('codigo').focus();
    </script>

    <!-- Footer Profesional -->
    <?php include 'user/includes/footer.php'; ?>

</body>
</html>
