<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - CONECTA ERP</title>

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
$success = '';

// Obtener países y idiomas
$db = Database::getInstance();
$countries = $db->fetchAll("SELECT * FROM countries WHERE is_active = 1 ORDER BY name");
$languages = $db->fetchAll("SELECT * FROM languages WHERE is_active = 1 ORDER BY name");
$plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active = 1 AND is_custom = 0 ORDER BY sort_order");

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = sanitize($_POST['full_name'] ?? '');
    $document_type = sanitize($_POST['document_type'] ?? '');
    $document_number = sanitize($_POST['document_number'] ?? '');
    $country_id = intval($_POST['country_id'] ?? 0);
    $language_id = intval($_POST['language_id'] ?? 1);
    $phone = sanitize($_POST['phone'] ?? '');
    $company_name = sanitize($_POST['company_name'] ?? '');
    $plan_id = intval($_POST['plan_id'] ?? 1);

    // Validaciones
    if (empty($email) || !isValidEmail($email)) {
        $error = 'Por favor ingrese un email válido';
    } elseif (empty($username)) {
        $error = 'Por favor ingrese un nombre de usuario';
    } elseif (empty($password) || strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } elseif (empty($full_name)) {
        $error = 'Por favor ingrese su nombre completo';
    } elseif (empty($document_number)) {
        $error = 'Por favor ingrese su documento de identidad';
    } elseif ($country_id <= 0) {
        $error = 'Por favor seleccione un país';
    } else {
        // Verificar si el email ya existe
        $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            $error = 'Este email ya está registrado';
        } else {
            try {
                // Hash de la contraseña
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Calcular fecha de fin de trial (14 días)
                $trialEndsAt = date('Y-m-d H:i:s', strtotime('+14 days'));

                // Insertar usuario
                $userId = $db->insert(
                    "INSERT INTO users (email, username, password, full_name, document_type, document_number, country_id, language_id, phone, company_name, plan_id, status, trial_ends_at, is_approved, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'trial', ?, 0, NOW())",
                    [
                        $email,
                        $username,
                        $hashedPassword,
                        $full_name,
                        $document_type,
                        $document_number,
                        $country_id,
                        $language_id,
                        $phone,
                        $company_name,
                        $plan_id,
                        $trialEndsAt
                    ]
                );

                if ($userId) {
                    // Log de actividad
                    logActivity($userId, 'registro', 'Usuario registrado en el sistema', 'auth');

                    $success = 'Registro exitoso. Su cuenta está pendiente de aprobación por el administrador. Recibirá un correo cuando sea aprobada.';

                    // Aquí se puede enviar email de confirmación
                    // sendEmail($email, 'Registro en CONECTA ERP', 'Su cuenta ha sido creada y está pendiente de aprobación...');
                }
            } catch (Exception $e) {
                $error = 'Error al registrar usuario: ' . $e->getMessage();
            }
        }
    }
}
?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="text-center mb-5">
                    <h1 class="display-4 fw-bold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <i class="fas fa-rocket"></i> CONECTA ERP
                    </h1>
                    <p class="lead text-muted">Crear nueva cuenta - 14 días de prueba gratis</p>
                </div>

                <!-- Card de Registro -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-user-plus"></i> Formulario de Registro</h3>
                    </div>

                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Ir a Iniciar Sesión
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>

                        <form method="POST" id="registerForm">
                            <!-- Información de Cuenta -->
                            <h5 class="fw-bold mb-3 mt-4">
                                <i class="fas fa-lock"></i> Información de Cuenta
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-envelope"></i> Email *
                                        </label>
                                        <input type="email" name="email" class="form-control" required
                                               placeholder="ejemplo@empresa.com" value="<?php echo $_POST['email'] ?? ''; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i> Nombre de Usuario *
                                        </label>
                                        <input type="text" name="username" class="form-control" required
                                               placeholder="usuario123" value="<?php echo $_POST['username'] ?? ''; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-key"></i> Contraseña *
                                        </label>
                                        <input type="password" name="password" class="form-control" required
                                               placeholder="Mínimo 8 caracteres" minlength="8">
                                        <small class="form-text">Mínimo 8 caracteres</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-key"></i> Confirmar Contraseña *
                                        </label>
                                        <input type="password" name="password_confirm" class="form-control" required
                                               placeholder="Repita su contraseña" minlength="8">
                                    </div>
                                </div>
                            </div>

                            <!-- Información Personal -->
                            <h5 class="fw-bold mb-3 mt-4">
                                <i class="fas fa-id-card"></i> Información Personal
                            </h5>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-user-circle"></i> Nombre Completo *
                                        </label>
                                        <input type="text" name="full_name" class="form-control" required
                                               placeholder="Juan Pérez García" value="<?php echo $_POST['full_name'] ?? ''; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-globe"></i> País *
                                        </label>
                                        <select name="country_id" id="countrySelect" class="form-control" required>
                                            <option value="">Seleccione un país...</option>
                                            <?php foreach ($countries as $country): ?>
                                                <option value="<?php echo $country['id']; ?>"
                                                        data-doc-type="<?php echo $country['document_type']; ?>"
                                                        data-doc-format="<?php echo $country['document_format']; ?>"
                                                        data-doc-validation="<?php echo $country['document_validation']; ?>"
                                                        <?php echo (isset($_POST['country_id']) && $_POST['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $country['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-language"></i> Idioma *
                                        </label>
                                        <select name="language_id" class="form-control" required>
                                            <?php foreach ($languages as $lang): ?>
                                                <option value="<?php echo $lang['id']; ?>"
                                                        <?php echo (isset($_POST['language_id']) && $_POST['language_id'] == $lang['id']) ? 'selected' : ($lang['code'] == 'es' ? 'selected' : ''); ?>>
                                                    <?php echo $lang['native_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" id="documentLabel">
                                            <i class="fas fa-id-card-alt"></i> Documento de Identidad *
                                        </label>
                                        <input type="hidden" name="document_type" id="documentType" value="RUT">
                                        <input type="text" name="document_number" id="documentNumber" class="form-control" required
                                               placeholder="12.345.678-9" value="<?php echo $_POST['document_number'] ?? ''; ?>">
                                        <small class="form-text" id="documentHelp">Ejemplo: 12.345.678-9</small>
                                        <div id="documentError" class="form-error" style="display: none;"></div>
                                        <div id="documentSuccess" style="color: green; font-size: 0.875rem; margin-top: 0.25rem; display: none;">
                                            <i class="fas fa-check-circle"></i> Documento válido
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-phone"></i> Teléfono
                                        </label>
                                        <input type="tel" name="phone" class="form-control"
                                               placeholder="+56 9 1234 5678" value="<?php echo $_POST['phone'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Información de Empresa -->
                            <h5 class="fw-bold mb-3 mt-4">
                                <i class="fas fa-building"></i> Información de Empresa
                            </h5>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-building"></i> Nombre de la Empresa
                                        </label>
                                        <input type="text" name="company_name" class="form-control"
                                               placeholder="Mi Empresa S.A." value="<?php echo $_POST['company_name'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Selección de Plan -->
                            <h5 class="fw-bold mb-3 mt-4">
                                <i class="fas fa-tags"></i> Seleccione su Plan
                            </h5>

                            <div class="row">
                                <?php foreach ($plans as $plan): ?>
                                    <div class="col-md-4">
                                        <div class="card mb-3" style="cursor: pointer;" onclick="selectPlan(<?php echo $plan['id']; ?>)">
                                            <div class="card-body text-center">
                                                <input type="radio" name="plan_id" value="<?php echo $plan['id']; ?>"
                                                       id="plan_<?php echo $plan['id']; ?>"
                                                       <?php echo (isset($_POST['plan_id']) && $_POST['plan_id'] == $plan['id']) || $plan['sort_order'] == 1 ? 'checked' : ''; ?>>
                                                <label for="plan_<?php echo $plan['id']; ?>" style="cursor: pointer; width: 100%;">
                                                    <h6 class="fw-bold mt-2"><?php echo $plan['plan_name']; ?></h6>
                                                    <p class="text-primary fw-bold mb-1">
                                                        $<?php echo number_format($plan['price_monthly'], 0); ?>/mes
                                                    </p>
                                                    <small class="text-muted">
                                                        <?php echo $plan['max_users']; ?> usuarios •
                                                        <?php echo $plan['max_companies']; ?> empresa(s)
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Términos y Condiciones -->
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Acepto los <a href="#">términos y condiciones</a> y la <a href="#">política de privacidad</a>
                                    </label>
                                </div>
                            </div>

                            <!-- Botón de Registro -->
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-user-plus"></i> Crear Cuenta - 14 días gratis
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <p class="text-muted">
                                    ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
                                </p>
                            </div>
                        </form>

                        <?php endif; ?>
                    </div>
                </div>

                <!-- Beneficios del Trial -->
                <?php if (!$success): ?>
                <div class="card mt-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-gift"></i> Beneficios del Período de Prueba
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check"></i> 14 días de prueba gratis</li>
                                    <li class="mb-2"><i class="fas fa-check"></i> Acceso a todos los módulos</li>
                                    <li class="mb-2"><i class="fas fa-check"></i> Sin tarjeta de crédito</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check"></i> Soporte técnico incluido</li>
                                    <li class="mb-2"><i class="fas fa-check"></i> Cancelación en cualquier momento</li>
                                    <li class="mb-2"><i class="fas fa-check"></i> Migración de datos asistida</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Validador de RUT Chileno
        function validarRUT(rut) {
            // Limpiar RUT
            rut = rut.replace(/\./g, '').replace(/-/g, '');

            if (rut.length < 2) return false;

            const cuerpo = rut.slice(0, -1);
            const dv = rut.slice(-1).toUpperCase();

            // Calcular dígito verificador
            let suma = 0;
            let multiplo = 2;

            for (let i = cuerpo.length - 1; i >= 0; i--) {
                suma += parseInt(cuerpo.charAt(i)) * multiplo;
                multiplo = multiplo < 7 ? multiplo + 1 : 2;
            }

            const dvEsperado = 11 - (suma % 11);
            let dvCalculado = dvEsperado === 11 ? '0' : dvEsperado === 10 ? 'K' : dvEsperado.toString();

            return dv === dvCalculado;
        }

        // Formatear RUT Chileno
        function formatearRUT(rut) {
            // Limpiar
            rut = rut.replace(/\./g, '').replace(/-/g, '');

            // Solo números y K
            rut = rut.replace(/[^0-9kK]/g, '');

            if (rut.length <= 1) return rut;

            const cuerpo = rut.slice(0, -1);
            const dv = rut.slice(-1);

            // Formatear con puntos
            let cuerpoFormateado = '';
            let contador = 0;

            for (let i = cuerpo.length - 1; i >= 0; i--) {
                if (contador === 3) {
                    cuerpoFormateado = '.' + cuerpoFormateado;
                    contador = 0;
                }
                cuerpoFormateado = cuerpo.charAt(i) + cuerpoFormateado;
                contador++;
            }

            return cuerpoFormateado + '-' + dv;
        }

        // Cambio de país
        document.getElementById('countrySelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const docType = selectedOption.dataset.docType || 'RUT';
            const docFormat = selectedOption.dataset.docFormat || '##.###.###-#';
            const docValidation = selectedOption.dataset.docValidation || 'chilean_rut';

            document.getElementById('documentType').value = docType;
            document.getElementById('documentLabel').innerHTML = '<i class="fas fa-id-card-alt"></i> ' + docType + ' *';
            document.getElementById('documentNumber').placeholder = docFormat;
            document.getElementById('documentHelp').textContent = 'Ejemplo: ' + docFormat;
        });

        // Validación en tiempo real del documento
        document.getElementById('documentNumber').addEventListener('input', function() {
            const countrySelect = document.getElementById('countrySelect');
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            const docValidation = selectedOption.dataset.docValidation;
            const docType = selectedOption.dataset.docType || 'RUT';

            let value = this.value;

            // Si es RUT chileno, formatear y validar
            if (docValidation === 'chilean_rut') {
                // Formatear
                value = formatearRUT(value);
                this.value = value;

                // Validar
                const isValid = validarRUT(value);

                if (value.length >= 3) {
                    if (isValid) {
                        document.getElementById('documentError').style.display = 'none';
                        document.getElementById('documentSuccess').style.display = 'block';
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        document.getElementById('documentError').textContent = 'RUT inválido';
                        document.getElementById('documentError').style.display = 'block';
                        document.getElementById('documentSuccess').style.display = 'none';
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    }
                } else {
                    document.getElementById('documentError').style.display = 'none';
                    document.getElementById('documentSuccess').style.display = 'none';
                    this.classList.remove('is-invalid', 'is-valid');
                }
            }
        });

        // Seleccionar plan
        function selectPlan(planId) {
            document.getElementById('plan_' + planId).checked = true;
        }

        // Validación de formulario antes de enviar
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('[name="password"]').value;
            const passwordConfirm = document.querySelector('[name="password_confirm"]').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }

            const countrySelect = document.getElementById('countrySelect');
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            const docValidation = selectedOption.dataset.docValidation;
            const documentNumber = document.getElementById('documentNumber').value;

            if (docValidation === 'chilean_rut' && documentNumber) {
                if (!validarRUT(documentNumber)) {
                    e.preventDefault();
                    alert('El RUT ingresado no es válido');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
