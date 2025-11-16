<?php
session_start();
require_once 'includes/config.php';

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['es_super_admin'] == 1) ? 'admin/dashboard_admin.php' : 'user/dashboard_user.php';
    header("Location: $redirect");
    exit();
}

$errors = [];
$success = false;

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos requeridos
    $required_fields = ['nombre', 'apellido', 'email', 'username', 'password', 'confirm_password', 'nombre_empresa', 'razon_social', 'rut_empresa', 'pais_id', 'idioma_preferido'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "El campo " . str_replace('_', ' ', $field) . " es obligatorio";
        }
    }

    // Validar email
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no es válido";
    }

    // Validar contraseñas
    if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres";
    }

    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Las contraseñas no coinciden";
    }

    // Validar RUT (si es Chile)
    if ($_POST['pais_id'] == 1 && !empty($_POST['rut_empresa'])) {
        if (!validarRutChileno($_POST['rut_empresa'])) {
            $errors[] = "El RUT de la empresa no es válido";
        }
    }

    if ($_POST['pais_id'] == 1 && !empty($_POST['rut'])) {
        if (!validarRutChileno($_POST['rut'])) {
            $errors[] = "El RUT personal no es válido";
        }
    }

    // Verificar si el email ya existe
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $_POST['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El email ya está registrado";
        }
        $stmt->close();
    }

    // Verificar si el username ya existe
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $_POST['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El nombre de usuario ya está en uso";
        }
        $stmt->close();
    }

    // Verificar si el RUT de empresa ya existe
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM empresas WHERE rut = ?");
        $stmt->bind_param("s", $_POST['rut_empresa']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "El RUT de la empresa ya está registrado";
        }
        $stmt->close();
    }

    // Si no hay errores, proceder con el registro
    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // 1. Crear la empresa
            $stmt = $conn->prepare("INSERT INTO empresas (nombre_empresa, razon_social, rut, pais_id, direccion, ciudad, telefono, email, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')");
            $stmt->bind_param("sssisss",
                $_POST['nombre_empresa'],
                $_POST['razon_social'],
                $_POST['rut_empresa'],
                $_POST['pais_id'],
                $_POST['direccion'] ?? null,
                $_POST['ciudad'] ?? null,
                $_POST['telefono_empresa'] ?? null,
                $_POST['email']
            );
            $stmt->execute();
            $empresa_id = $conn->insert_id;
            $stmt->close();

            // 2. Crear el usuario con periodo de prueba de 14 días
            $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $fecha_inicio_trial = date('Y-m-d H:i:s');
            $fecha_fin_trial = date('Y-m-d H:i:s', strtotime('+14 days'));

            // Plan por defecto: Básico (id = 1)
            $plan_id = 1;

            $stmt = $conn->prepare("INSERT INTO usuarios (empresa_id, nombre, apellido, email, username, password, rut, telefono, idioma_preferido, plan_id, es_admin, es_super_admin, estado, en_periodo_prueba, fecha_inicio_trial, fecha_fin_trial, suscripcion_activa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'activo', 1, ?, ?, 1)");
            $stmt->bind_param("issssssssis",
                $empresa_id,
                $_POST['nombre'],
                $_POST['apellido'],
                $_POST['email'],
                $_POST['username'],
                $password_hash,
                $_POST['rut'] ?? null,
                $_POST['telefono'] ?? null,
                $_POST['idioma_preferido'],
                $plan_id,
                $fecha_inicio_trial,
                $fecha_fin_trial
            );
            $stmt->execute();
            $usuario_id = $conn->insert_id;
            $stmt->close();

            // 3. Crear suscripción en periodo de prueba
            $stmt = $conn->prepare("INSERT INTO suscripciones (usuario_id, empresa_id, plan_id, estado, fecha_inicio, fecha_fin, es_trial, auto_renovar, monto_mensual) VALUES (?, ?, ?, 'trial', ?, ?, 1, 1, 0.00)");
            $stmt->bind_param("iiiss",
                $usuario_id,
                $empresa_id,
                $plan_id,
                $fecha_inicio_trial,
                $fecha_fin_trial
            );
            $stmt->execute();
            $stmt->close();

            // 4. Asignar rol de Usuario (id = 3)
            $rol_usuario = 3;
            $stmt = $conn->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $usuario_id, $rol_usuario);
            $stmt->execute();
            $stmt->close();

            // 5. Registrar en logs de acceso
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, email, accion, exitoso, mensaje, ip_address, user_agent) VALUES (?, ?, 'registro', 1, 'Usuario registrado exitosamente', ?, ?)");
            $stmt->bind_param("isss", $usuario_id, $_POST['email'], $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();

            // 6. Crear notificación de bienvenida
            $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, 'exito', 'Bienvenido a CONECTA ERP', 'Tu cuenta ha sido creada exitosamente. Tienes 14 días de prueba gratis.')");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            // Auto-login después del registro
            $_SESSION['user_id'] = $usuario_id;
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['email'] = $_POST['email'];
            $_SESSION['nombre'] = $_POST['nombre'];
            $_SESSION['apellido'] = $_POST['apellido'];
            $_SESSION['empresa_id'] = $empresa_id;
            $_SESSION['es_admin'] = 0;
            $_SESSION['es_super_admin'] = 0;
            $_SESSION['idioma_preferido'] = $_POST['idioma_preferido'];
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - CONECTA ERP</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .register-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .register-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .register-body {
            padding: 40px;
        }

        .section-title {
            font-size: 1.3rem;
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
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
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

        .rut-example {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
        }

        .input-group .form-control {
            border-left: none;
        }

        @media (max-width: 768px) {
            .register-header h1 {
                font-size: 2rem;
            }

            .register-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-rocket"></i> CONECTA ERP</h1>
            <p>Crea tu cuenta y comienza tu prueba gratuita de 14 días</p>
        </div>

        <div class="register-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Errores en el formulario:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <!-- Datos de la Empresa -->
                <div class="section-title">
                    <i class="fas fa-building"></i> Datos de la Empresa
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">País <span class="text-danger">*</span></label>
                        <select name="pais_id" id="pais_id" class="form-select" required>
                            <option value="">Seleccione un país</option>
                            <?php while ($pais = $paises->fetch_assoc()): ?>
                                <option value="<?php echo $pais['id']; ?>"
                                        data-tipo-doc="<?php echo htmlspecialchars($pais['tipo_documento']); ?>"
                                        data-formato="<?php echo htmlspecialchars($pais['formato_documento']); ?>"
                                        data-moneda="<?php echo htmlspecialchars($pais['moneda_simbolo']); ?>"
                                        <?php echo (isset($_POST['pais_id']) && $_POST['pais_id'] == $pais['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pais['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Idioma Preferido <span class="text-danger">*</span></label>
                        <select name="idioma_preferido" id="idioma_preferido" class="form-select" required>
                            <?php while ($idioma = $idiomas->fetch_assoc()): ?>
                                <option value="<?php echo $idioma['codigo']; ?>" <?php echo (isset($_POST['idioma_preferido']) && $_POST['idioma_preferido'] == $idioma['codigo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($idioma['nombre_nativo']); ?> (<?php echo htmlspecialchars($idioma['nombre']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_empresa" class="form-control" required value="<?php echo htmlspecialchars($_POST['nombre_empresa'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="razon_social" class="form-control" required value="<?php echo htmlspecialchars($_POST['razon_social'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label"><span id="doc_tipo_empresa">RUT</span> de la Empresa <span class="text-danger">*</span></label>
                        <input type="text" name="rut_empresa" id="rut_empresa" class="form-control" required value="<?php echo htmlspecialchars($_POST['rut_empresa'] ?? ''); ?>">
                        <div class="rut-example" id="doc_ejemplo_empresa">Ejemplo: 12.345.678-9</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email de la Empresa <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control" value="<?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-control" value="<?php echo htmlspecialchars($_POST['ciudad'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono Empresa</label>
                        <input type="tel" name="telefono_empresa" class="form-control" value="<?php echo htmlspecialchars($_POST['telefono_empresa'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Datos del Usuario -->
                <div class="section-title">
                    <i class="fas fa-user"></i> Datos del Usuario Administrador
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apellido <span class="text-danger">*</span></label>
                        <input type="text" name="apellido" class="form-control" required value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label"><span id="doc_tipo_personal">RUT</span> Personal</label>
                        <input type="text" name="rut" id="rut_personal" class="form-control" value="<?php echo htmlspecialchars($_POST['rut'] ?? ''); ?>">
                        <div class="rut-example" id="doc_ejemplo_personal">Ejemplo: 12.345.678-9</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono Personal</label>
                        <input type="tel" name="telefono" class="form-control" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Credenciales -->
                <div class="section-title">
                    <i class="fas fa-lock"></i> Credenciales de Acceso
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8">
                        </div>
                        <div class="password-strength" id="password-strength"></div>
                        <small class="text-muted">Mínimo 8 caracteres</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
                        </div>
                        <div id="password-match-message"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-register">
                    <i class="fas fa-rocket"></i> Crear Cuenta y Comenzar Prueba Gratuita
                </button>

                <div class="login-link">
                    ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // RUT Chileno - Validador y Formateador
        function limpiarRut(rut) {
            return rut.replace(/[^0-9kK]/g, '');
        }

        function formatearRut(rut) {
            rut = limpiarRut(rut);
            if (rut.length < 2) return rut;

            let dv = rut.slice(-1);
            let numero = rut.slice(0, -1);

            // Formatear con puntos
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

        // Auto-formateador de RUT en tiempo real
        document.getElementById('rut_empresa').addEventListener('input', function(e) {
            let paisId = document.getElementById('pais_id').value;
            if (paisId == 1) { // Chile
                let cursorPos = e.target.selectionStart;
                let oldLength = e.target.value.length;
                e.target.value = formatearRut(e.target.value);
                let newLength = e.target.value.length;
                e.target.setSelectionRange(cursorPos + (newLength - oldLength), cursorPos + (newLength - oldLength));
            }
        });

        document.getElementById('rut_personal').addEventListener('input', function(e) {
            let paisId = document.getElementById('pais_id').value;
            if (paisId == 1) { // Chile
                let cursorPos = e.target.selectionStart;
                let oldLength = e.target.value.length;
                e.target.value = formatearRut(e.target.value);
                let newLength = e.target.value.length;
                e.target.setSelectionRange(cursorPos + (newLength - oldLength), cursorPos + (newLength - oldLength));
            }
        });

        // Cambio dinámico del tipo de documento según país
        document.getElementById('pais_id').addEventListener('change', function() {
            let selectedOption = this.options[this.selectedIndex];
            let tipoDoc = selectedOption.dataset.tipoDoc || 'RUT';
            let formato = selectedOption.dataset.formato || '##.###.###-#';

            // Actualizar etiquetas
            document.getElementById('doc_tipo_empresa').textContent = tipoDoc;
            document.getElementById('doc_tipo_personal').textContent = tipoDoc;

            // Actualizar ejemplos
            let ejemploEmpresa = 'Ejemplo: ' + formato.replace(/#/g, function(match, offset) {
                return String(offset % 10);
            });
            let ejemploPersonal = 'Ejemplo: ' + formato.replace(/#/g, function(match, offset) {
                return String(offset % 10);
            });

            document.getElementById('doc_ejemplo_empresa').textContent = ejemploEmpresa;
            document.getElementById('doc_ejemplo_personal').textContent = ejemploPersonal;

            // Limpiar valores si cambia de país
            document.getElementById('rut_empresa').value = '';
            document.getElementById('rut_personal').value = '';
        });

        // Validador de fortaleza de contraseña
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

        // Validador de coincidencia de contraseñas
        document.getElementById('confirm_password').addEventListener('input', function() {
            let password = document.getElementById('password').value;
            let confirmPassword = this.value;
            let message = document.getElementById('password-match-message');

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

            // Si es Chile, validar RUT
            if (paisId == 1) {
                let rutEmpresa = document.getElementById('rut_empresa').value;
                if (!validarRutChileno(rutEmpresa)) {
                    e.preventDefault();
                    alert('El RUT de la empresa no es válido');
                    document.getElementById('rut_empresa').focus();
                    return false;
                }
            }

            // Validar contraseñas
            let password = document.getElementById('password').value;
            let confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                document.getElementById('confirm_password').focus();
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres');
                document.getElementById('password').focus();
                return false;
            }
        });
    </script>
</body>
</html>
<?php
// Función de validación de RUT (PHP)
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
