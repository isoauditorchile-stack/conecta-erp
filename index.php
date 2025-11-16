<?php
session_start();

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['es_super_admin'] == 1) ? 'admin/dashboard_admin.php' : 'user/dashboard_user.php';
    header("Location: $redirect");
    exit();
}

require_once 'includes/config.php';

$errors = [];
$success = false;
$login_error = '';

// Procesar LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email_or_username = trim($_POST['email_or_username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email_or_username) || empty($password)) {
        $login_error = 'Por favor ingresa tus credenciales';
    } else {
        $stmt = $conn->prepare("SELECT u.*, e.nombre_empresa, e.estado as empresa_estado
                                FROM usuarios u
                                LEFT JOIN empresas e ON u.empresa_id = e.id
                                WHERE u.email = ? OR u.username = ?");
        $stmt->bind_param("ss", $email_or_username, $email_or_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                if ($user['estado'] !== 'activo') {
                    $login_error = 'Tu cuenta está ' . $user['estado'];
                } elseif ($user['es_super_admin'] == 1) {
                    // SUPER ADMIN
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['apellido'] = $user['apellido'];
                    $_SESSION['empresa_id'] = $user['empresa_id'];
                    $_SESSION['es_admin'] = 1;
                    $_SESSION['es_super_admin'] = 1;
                    $_SESSION['idioma_preferido'] = $user['idioma_preferido'];
                    $_SESSION['en_periodo_prueba'] = 0;

                    header("Location: admin/dashboard_admin.php");
                    exit();
                } else {
                    // Usuario normal
                    if ($user['en_periodo_prueba'] == 1) {
                        $fecha_fin_trial = strtotime($user['fecha_fin_trial']);
                        $hoy = time();

                        if ($hoy > $fecha_fin_trial) {
                            $login_error = 'Tu periodo de prueba ha expirado';
                        } else {
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
                            $_SESSION['en_periodo_prueba'] = 1;
                            $_SESSION['dias_restantes_trial'] = $dias_restantes;

                            header("Location: user/dashboard_user.php");
                            exit();
                        }
                    } elseif ($user['suscripcion_activa'] == 1) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['nombre'] = $user['nombre'];
                        $_SESSION['apellido'] = $user['apellido'];
                        $_SESSION['empresa_id'] = $user['empresa_id'];
                        $_SESSION['nombre_empresa'] = $user['nombre_empresa'];
                        $_SESSION['es_admin'] = $user['es_admin'];
                        $_SESSION['idioma_preferido'] = $user['idioma_preferido'];
                        $_SESSION['en_periodo_prueba'] = 0;

                        header("Location: user/dashboard_user.php");
                        exit();
                    } else {
                        $login_error = 'Tu suscripción no está activa';
                    }
                }
            } else {
                $login_error = 'Credenciales incorrectas';
            }
        } else {
            $login_error = 'Credenciales incorrectas';
        }
        $stmt->close();
    }
}

// Procesar REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $required_fields = ['nombre', 'apellido', 'email', 'username', 'password', 'confirm_password', 'nombre_empresa', 'razon_social', 'rut_empresa', 'pais_id', 'idioma_preferido'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "El campo " . str_replace('_', ' ', $field) . " es obligatorio";
        }
    }

    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no es válido";
    }

    if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres";
    }

    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Las contraseñas no coinciden";
    }

    // Validar RUT si es Chile
    if ($_POST['pais_id'] == 1 && !empty($_POST['rut_empresa'])) {
        if (!validarRutChileno($_POST['rut_empresa'])) {
            $errors[] = "El RUT de la empresa no es válido";
        }
    }

    // Verificar email reservado del super admin
    if (empty($errors)) {
        if (strtolower($_POST['email']) === strtolower(SUPER_ADMIN_EMAIL)) {
            $errors[] = "Este email está reservado para el administrador del sistema";
        }
    }

    // Verificar username reservado del super admin
    if (empty($errors)) {
        if (strtolower($_POST['username']) === strtolower(SUPER_ADMIN_USERNAME)) {
            $errors[] = "Este nombre de usuario está reservado";
        }
    }

    // Verificar email único
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $_POST['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El email ya está registrado";
        }
        $stmt->close();
    }

    // Verificar username único
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $_POST['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El nombre de usuario ya está en uso";
        }
        $stmt->close();
    }

    // Verificar RUT empresa único
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM empresas WHERE rut = ?");
        $stmt->bind_param("s", $_POST['rut_empresa']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El RUT de la empresa ya está registrado";
        }
        $stmt->close();
    }

    // Si no hay errores, registrar
    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // 1. Crear empresa - Preparar variables
            $nombre_empresa = $_POST['nombre_empresa'];
            $razon_social = $_POST['razon_social'];
            $rut_empresa = $_POST['rut_empresa'];
            $pais_id = $_POST['pais_id'];
            $direccion = $_POST['direccion'] ?? null;
            $ciudad = $_POST['ciudad'] ?? null;
            $telefono_empresa = $_POST['telefono_empresa'] ?? null;
            $email_empresa = $_POST['email'];

            $stmt = $conn->prepare("INSERT INTO empresas (nombre_empresa, razon_social, rut, pais_id, direccion, ciudad, telefono, email, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')");

            if (!$stmt) {
                throw new Exception("Error al preparar consulta empresa: " . $conn->error);
            }

            $stmt->bind_param("sssissss",
                $nombre_empresa,
                $razon_social,
                $rut_empresa,
                $pais_id,
                $direccion,
                $ciudad,
                $telefono_empresa,
                $email_empresa
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al crear empresa: " . $stmt->error);
            }

            $empresa_id = $conn->insert_id;
            $stmt->close();

            // 2. Crear usuario - Preparar variables
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $rut_personal = $_POST['rut'] ?? null;
            $telefono_personal = $_POST['telefono'] ?? null;
            $idioma_preferido = $_POST['idioma_preferido'];
            $fecha_inicio_trial = date('Y-m-d H:i:s');
            $fecha_fin_trial = date('Y-m-d H:i:s', strtotime('+14 days'));
            $plan_id = 1;

            $stmt = $conn->prepare("INSERT INTO usuarios (empresa_id, nombre, apellido, email, username, password, rut, telefono, idioma_preferido, plan_id, es_admin, es_super_admin, estado, en_periodo_prueba, fecha_inicio_trial, fecha_fin_trial, suscripcion_activa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'activo', 1, ?, ?, 1)");

            if (!$stmt) {
                throw new Exception("Error al preparar consulta usuario: " . $conn->error);
            }

            $stmt->bind_param("issssssssiss",
                $empresa_id,
                $nombre,
                $apellido,
                $email,
                $username,
                $password_hash,
                $rut_personal,
                $telefono_personal,
                $idioma_preferido,
                $plan_id,
                $fecha_inicio_trial,
                $fecha_fin_trial
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al crear usuario: " . $stmt->error);
            }

            $usuario_id = $conn->insert_id;
            $stmt->close();

            // 3. Crear suscripción
            $stmt = $conn->prepare("INSERT INTO suscripciones (usuario_id, empresa_id, plan_id, estado, fecha_inicio, fecha_fin, es_trial, auto_renovar, monto_mensual) VALUES (?, ?, ?, 'trial', ?, ?, 1, 1, 0.00)");

            if (!$stmt) {
                throw new Exception("Error al preparar consulta suscripción: " . $conn->error);
            }

            $stmt->bind_param("iiiss",
                $usuario_id,
                $empresa_id,
                $plan_id,
                $fecha_inicio_trial,
                $fecha_fin_trial
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al crear suscripción: " . $stmt->error);
            }

            $stmt->close();

            // 4. Asignar rol
            $rol_usuario = 3;
            $stmt = $conn->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)");

            if (!$stmt) {
                throw new Exception("Error al preparar consulta rol: " . $conn->error);
            }

            $stmt->bind_param("ii", $usuario_id, $rol_usuario);

            if (!$stmt->execute()) {
                throw new Exception("Error al asignar rol: " . $stmt->error);
            }

            $stmt->close();

            $conn->commit();

            // Auto-login
            $_SESSION['user_id'] = $usuario_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['apellido'] = $apellido;
            $_SESSION['empresa_id'] = $empresa_id;
            $_SESSION['es_admin'] = 0;
            $_SESSION['es_super_admin'] = 0;
            $_SESSION['idioma_preferido'] = $idioma_preferido;
            $_SESSION['en_periodo_prueba'] = 1;
            $_SESSION['dias_restantes_trial'] = 14;

            header("Location: user/dashboard_user.php?welcome=1");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error al crear la cuenta: " . $e->getMessage();
        }
    }
}

// Obtener países e idiomas
$paises = $conn->query("SELECT * FROM paises WHERE activo = 1 ORDER BY nombre");
$idiomas = $conn->query("SELECT * FROM idiomas WHERE activo = 1 ORDER BY nombre");

// Función validar RUT
function validarRutChileno($rut) {
    $rut = preg_replace('/[^0-9kK]/', '', $rut);
    if (strlen($rut) < 2) return false;
    $dv = strtoupper(substr($rut, -1));
    $numero = intval(substr($rut, 0, -1));
    $suma = 0;
    $multiplo = 2;
    while ($numero > 0) {
        $suma += ($numero % 10) * $multiplo;
        $numero = intval($numero / 10);
        $multiplo = $multiplo === 7 ? 2 : $multiplo + 1;
    }
    $dvEsperado = 11 - ($suma % 11);
    if ($dvEsperado === 11) $dvEsperado = '0';
    else if ($dvEsperado === 10) $dvEsperado = 'K';
    else $dvEsperado = strval($dvEsperado);
    return $dv === $dvEsperado;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONECTA ERP - Sistema de Gestión Empresarial Completo</title>
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
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: drift 30s linear infinite;
        }

        @keyframes drift {
            from { transform: translate(0, 0); }
            to { transform: translate(-50%, -50%); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 900px;
            padding: 20px;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero .subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .hero .stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin: 3rem 0;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            display: block;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-login {
            background: white;
            color: #667eea;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,255,255,0.3);
        }

        .btn-register {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-register:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        /* Modales personalizados */
        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
        }

        .modal-header .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
        }

        .input-group .form-control {
            border-left: none;
        }

        .btn-submit {
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

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero .subtitle {
                font-size: 1.2rem;
            }

            .stat-number {
                font-size: 2rem;
            }
        }
    </style>

        /* Sección de Planes */
        .pricing-section {
            padding: 80px 20px;
            background: #f8f9fa;
        }

        .pricing-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .pricing-title h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .pricing-title p {
            font-size: 1.2rem;
            color: #718096;
        }

        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            text-align: center;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .pricing-card.popular {
            border: 3px solid #667eea;
            transform: scale(1.05);
        }

        .pricing-card.popular::before {
            content: '⭐ MÁS POPULAR';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 700;
        }

        .plan-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 10px;
        }

        .plan-price small {
            font-size: 1rem;
            color: #718096;
            font-weight: normal;
        }

        .plan-description {
            color: #718096;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 30px 0;
            text-align: left;
        }

        .plan-features li {
            padding: 10px 0;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-features li i {
            color: #48bb78;
            font-size: 1.2rem;
        }

        .plan-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .plan-btn.btn-basic {
            background: #e2e8f0;
            color: #2d3748;
        }

        .plan-btn.btn-basic:hover {
            background: #cbd5e0;
        }

        .plan-btn.btn-professional {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .plan-btn.btn-professional:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .plan-btn.btn-enterprise {
            background: #2d3748;
            color: white;
        }

        .plan-btn.btn-enterprise:hover {
            background: #1a202c;
        }

        .plan-btn.btn-custom {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
        }

        .plan-btn.btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(237, 137, 54, 0.4);
        }

        @media (max-width: 768px) {
            .pricing-cards {
                grid-template-columns: 1fr;
            }

            .pricing-card.popular {
                transform: scale(1);
            }
        }
</head>
<body>
    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content">
            <h1><i class="fas fa-rocket"></i> CONECTA ERP</h1>
            <p class="subtitle">Sistema de Gestión Empresarial Completo para LATAM</p>

            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number">14</span>
                    <span class="stat-label">Módulos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">106</span>
                    <span class="stat-label">Submódulos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">8</span>
                    <span class="stat-label">Idiomas</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">9</span>
                    <span class="stat-label">Países</span>
                </div>
            </div>

            <div class="cta-buttons">
                <button class="btn-hero btn-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="fas fa-sign-in-alt"></i> INICIAR SESIÓN
                </button>
                <button class="btn-hero btn-register" data-bs-toggle="modal" data-bs-target="#registerModal">
                    <i class="fas fa-user-plus"></i> REGISTRARSE GRATIS
                </button>
            </div>
        </div>
    </div>
    <!-- Sección de Planes de Pricing -->
    <section class="pricing-section" id="planes">
        <div class="container">
            <div class="pricing-title">
                <h2>Planes y Precios</h2>
                <p>Elige el plan perfecto para tu empresa. Todos incluyen 14 días de prueba gratis.</p>
            </div>

            <div class="pricing-cards">
                <!-- Plan Básico -->
                <div class="pricing-card">
                    <div class="plan-name">Básico</div>
                    <div class="plan-price">
                        $49,990 <small>/mes</small>
                    </div>
                    <div class="plan-description">
                        Ideal para pequeñas empresas que están comenzando
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check-circle"></i> Hasta 5 usuarios</li>
                        <li><i class="fas fa-check-circle"></i> 1 empresa</li>
                        <li><i class="fas fa-check-circle"></i> 6 módulos básicos</li>
                        <li><i class="fas fa-check-circle"></i> Soporte por email</li>
                        <li><i class="fas fa-check-circle"></i> Actualizaciones incluidas</li>
                    </ul>
                    <button class="plan-btn btn-basic" data-bs-toggle="modal" data-bs-target="#registerModal">
                        Empezar Gratis
                    </button>
                </div>

                <!-- Plan Profesional (Popular) -->
                <div class="pricing-card popular">
                    <div class="plan-name">Profesional</div>
                    <div class="plan-price">
                        $99,990 <small>/mes</small>
                    </div>
                    <div class="plan-description">
                        Para empresas en crecimiento que necesitan más poder
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check-circle"></i> Hasta 25 usuarios</li>
                        <li><i class="fas fa-check-circle"></i> 3 empresas</li>
                        <li><i class="fas fa-check-circle"></i> 12 módulos completos</li>
                        <li><i class="fas fa-check-circle"></i> Soporte prioritario</li>
                        <li><i class="fas fa-check-circle"></i> Reportes avanzados</li>
                        <li><i class="fas fa-check-circle"></i> Integraciones SII/Previred</li>
                    </ul>
                    <button class="plan-btn btn-professional" data-bs-toggle="modal" data-bs-target="#registerModal">
                        Empezar Gratis
                    </button>
                </div>

                <!-- Plan Empresarial -->
                <div class="pricing-card">
                    <div class="plan-name">Empresarial</div>
                    <div class="plan-price">
                        $199,990 <small>/mes</small>
                    </div>
                    <div class="plan-description">
                        Para grandes empresas con necesidades avanzadas
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check-circle"></i> Usuarios ilimitados</li>
                        <li><i class="fas fa-check-circle"></i> Empresas ilimitadas</li>
                        <li><i class="fas fa-check-circle"></i> 14 módulos + 106 submódulos</li>
                        <li><i class="fas fa-check-circle"></i> Soporte 24/7</li>
                        <li><i class="fas fa-check-circle"></i> Business Intelligence</li>
                        <li><i class="fas fa-check-circle"></i> API completa</li>
                        <li><i class="fas fa-check-circle"></i> Capacitación incluida</li>
                    </ul>
                    <button class="plan-btn btn-enterprise" data-bs-toggle="modal" data-bs-target="#registerModal">
                        Empezar Gratis
                    </button>
                </div>

                <!-- Plan Personalizado -->
                <div class="pricing-card">
                    <div class="plan-name">Personalizado</div>
                    <div class="plan-price">
                        Cotizar
                    </div>
                    <div class="plan-description">
                        Soluciones diseñadas específicamente para tu negocio
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check-circle"></i> Todo de Empresarial</li>
                        <li><i class="fas fa-check-circle"></i> Desarrollo a medida</li>
                        <li><i class="fas fa-check-circle"></i> Módulos personalizados</li>
                        <li><i class="fas fa-check-circle"></i> Servidor dedicado</li>
                        <li><i class="fas fa-check-circle"></i> Gerente de cuenta</li>
                        <li><i class="fas fa-check-circle"></i> SLA garantizado</li>
                    </ul>
                    <button class="plan-btn btn-custom" data-bs-toggle="modal" data-bs-target="#registerModal">
                        Contactar Ventas
                    </button>
                </div>
            </div>
        </div>
    </section>


    <!-- Modal LOGIN -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($login_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($login_error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Email o Usuario</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="email_or_username" class="form-control" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" name="login" class="btn-submit">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>

                        <div class="text-center mt-3">
                            <small>¿No tienes cuenta? <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal">Regístrate gratis</a></small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal REGISTRO -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Registro - 14 Días de Prueba Gratis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Errores:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="registerForm">
                        <!-- Configuración Inicial -->
                        <div class="section-title">
                            <i class="fas fa-globe"></i> Configuración Inicial
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">País <span class="text-danger">*</span></label>
                                <select name="pais_id" id="pais_id" class="form-select" required>
                                    <option value="">Seleccione un país</option>
                                    <?php
                                    $paises->data_seek(0);
                                    while ($pais = $paises->fetch_assoc()): ?>
                                        <option value="<?php echo $pais['id']; ?>"
                                                data-tipo-doc="<?php echo htmlspecialchars($pais['tipo_documento']); ?>"
                                                data-formato="<?php echo htmlspecialchars($pais['formato_documento']); ?>">
                                            <?php echo htmlspecialchars($pais['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Idioma Preferido <span class="text-danger">*</span></label>
                                <select name="idioma_preferido" class="form-select" required>
                                    <?php
                                    $idiomas->data_seek(0);
                                    while ($idioma = $idiomas->fetch_assoc()): ?>
                                        <option value="<?php echo $idioma['codigo']; ?>">
                                            <?php echo htmlspecialchars($idioma['nombre_nativo']); ?> (<?php echo htmlspecialchars($idioma['nombre']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Datos de la Empresa -->
                        <div class="section-title">
                            <i class="fas fa-building"></i> Datos de la Empresa
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_empresa" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="razon_social" class="form-control" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label"><span id="doc_tipo_empresa">RUT</span> Empresa <span class="text-danger">*</span></label>
                                <input type="text" name="rut_empresa" id="rut_empresa" class="form-control" required>
                                <small class="text-muted" id="doc_ejemplo_empresa">Ejemplo: 12.345.678-9</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Empresa <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Teléfono Empresa</label>
                                <input type="tel" name="telefono_empresa" class="form-control">
                            </div>
                        </div>

                        <!-- Datos del Usuario Administrador -->
                        <div class="section-title">
                            <i class="fas fa-user-tie"></i> Datos del Usuario Administrador
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label"><span id="doc_tipo_personal">RUT</span> Personal</label>
                                <input type="text" name="rut" id="rut_personal" class="form-control">
                                <small class="text-muted" id="doc_ejemplo_personal">Ejemplo: 12.345.678-9</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono Personal</label>
                                <input type="tel" name="telefono" class="form-control">
                            </div>
                        </div>

                        <!-- Credenciales de Acceso -->
                        <div class="section-title">
                            <i class="fas fa-key"></i> Credenciales de Acceso
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                                </div>
                                <div class="password-strength" id="password-strength"></div>
                                <small class="text-muted">Mínimo 8 caracteres</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
                                </div>
                                <div id="password-match"></div>
                            </div>
                        </div>

                        <button type="submit" name="register" class="btn-submit">
                            <i class="fas fa-rocket"></i> Crear Cuenta y Comenzar Prueba Gratis (14 Días)
                        </button>

                        <div class="text-center mt-3">
                            <small>¿Ya tienes cuenta? <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal">Inicia sesión</a></small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-abrir modales si hay errores
        <?php if (!empty($login_error)): ?>
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            new bootstrap.Modal(document.getElementById('registerModal')).show();
        <?php endif; ?>

        // RUT Validator y Formatter
        function limpiarRut(rut) {
            return rut.replace(/[^0-9kK]/g, '');
        }

        function formatearRut(rut) {
            rut = limpiarRut(rut);
            if (rut.length < 2) return rut;
            let dv = rut.slice(-1);
            let numero = rut.slice(0, -1);
            numero = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return numero + '-' + dv;
        }

        function validarRutChileno(rut) {
            rut = limpiarRut(rut);
            if (rut.length < 2) return false;
            let dv = rut.slice(-1).toUpperCase();
            let numero = parseInt(rut.slice(0, -1));
            let suma = 0;
            let multiplo = 2;
            while (numero > 0) {
                suma += (numero % 10) * multiplo;
                numero = Math.floor(numero / 10);
                multiplo = multiplo === 7 ? 2 : multiplo + 1;
            }
            let dvEsperado = 11 - (suma % 11);
            if (dvEsperado === 11) dvEsperado = '0';
            else if (dvEsperado === 10) dvEsperado = 'K';
            else dvEsperado = dvEsperado.toString();
            return dv === dvEsperado;
        }

        // Auto-formato RUT
        document.getElementById('rut_empresa').addEventListener('input', function(e) {
            let paisId = document.getElementById('pais_id').value;
            if (paisId == 1) {
                let cursorPos = e.target.selectionStart;
                let oldLength = e.target.value.length;
                e.target.value = formatearRut(e.target.value);
                let newLength = e.target.value.length;
                e.target.setSelectionRange(cursorPos + (newLength - oldLength), cursorPos + (newLength - oldLength));
            }
        });

        document.getElementById('rut_personal').addEventListener('input', function(e) {
            let paisId = document.getElementById('pais_id').value;
            if (paisId == 1) {
                let cursorPos = e.target.selectionStart;
                let oldLength = e.target.value.length;
                e.target.value = formatearRut(e.target.value);
                let newLength = e.target.value.length;
                e.target.setSelectionRange(cursorPos + (newLength - oldLength), cursorPos + (newLength - oldLength));
            }
        });

        // Cambio dinámico de tipo documento
        document.getElementById('pais_id').addEventListener('change', function() {
            let selectedOption = this.options[this.selectedIndex];
            let tipoDoc = selectedOption.dataset.tipoDoc || 'RUT';
            let formato = selectedOption.dataset.formato || '##.###.###-#';

            document.getElementById('doc_tipo_empresa').textContent = tipoDoc;
            document.getElementById('doc_tipo_personal').textContent = tipoDoc;
            document.getElementById('doc_ejemplo_empresa').textContent = 'Ejemplo: ' + formato;
            document.getElementById('doc_ejemplo_personal').textContent = 'Ejemplo: ' + formato;
        });

        // Password strength
        document.getElementById('password').addEventListener('input', function() {
            let password = this.value;
            let strength = document.getElementById('password-strength');
            if (password.length === 0) {
                strength.className = 'password-strength';
                return;
            }
            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;

            if (score <= 2) {
                strength.className = 'password-strength strength-weak';
            } else if (score <= 4) {
                strength.className = 'password-strength strength-medium';
            } else {
                strength.className = 'password-strength strength-strong';
            }
        });

        // Password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            let password = document.getElementById('password').value;
            let confirmPassword = this.value;
            let message = document.getElementById('password-match');
            if (confirmPassword.length === 0) {
                message.innerHTML = '';
                return;
            }
            if (password === confirmPassword) {
                message.innerHTML = '<small class="text-success"><i class="fas fa-check"></i> Las contraseñas coinciden</small>';
            } else {
                message.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> Las contraseñas no coinciden</small>';
            }
        });

        // Validación antes de enviar
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let paisId = document.getElementById('pais_id').value;
            if (paisId == 1) {
                let rutEmpresa = document.getElementById('rut_empresa').value;
                if (!validarRutChileno(rutEmpresa)) {
                    e.preventDefault();
                    alert('El RUT de la empresa no es válido');
                    return false;
                }
            }
            let password = document.getElementById('password').value;
            let confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
        });
    </script>
</body>
</html>
