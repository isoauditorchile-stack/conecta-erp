<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - CONECTA ERP</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-gradient-start: #667eea;
            --bg-gradient-end: #764ba2;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            max-width: 900px;
            width: 100%;
        }

        .register-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 40px;
            text-align: center;
            color: white;
        }

        .register-logo {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .register-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .register-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .register-body {
            padding: 40px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: block;
        }

        .form-control, .form-select {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .form-control.is-invalid {
            border-color: var(--danger-color);
        }

        .form-control.is-valid {
            border-color: var(--success-color);
        }

        .invalid-feedback {
            display: block;
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .valid-feedback {
            display: block;
            color: var(--success-color);
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            padding: 12px 16px;
            color: var(--text-secondary);
        }

        .input-group .form-control {
            border-left: none;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-size: 1.125rem;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-secondary);
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .plan-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .plan-option {
            position: relative;
        }

        .plan-option input[type="radio"] {
            display: none;
        }

        .plan-label {
            display: block;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .plan-option input[type="radio"]:checked + .plan-label {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .plan-name {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .plan-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
        }

        .plan-price small {
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--text-secondary);
        }

        .doc-type-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 10px;
            font-size: 0.875rem;
            color: #1e40af;
        }

        .doc-type-info strong {
            display: block;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .register-body {
                padding: 30px 20px;
            }

            .plan-selector {
                grid-template-columns: 1fr;
            }
        }

        .spinner-border-sm {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 2px;
        }

        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <h1 class="register-title">CONECTA ERP</h1>
                <p class="register-subtitle">Crea tu cuenta y comienza a transformar tu negocio</p>
            </div>

            <div class="register-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form id="registerForm" method="POST" action="" autocomplete="off">
                    <!-- Selección de Plan -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-star-fill me-2"></i> Selecciona tu Plan
                        </div>
                        <div class="plan-selector">
                            <div class="plan-option">
                                <input type="radio" id="plan_basic" name="plan_id" value="1" checked>
                                <label class="plan-label" for="plan_basic">
                                    <div class="plan-name">Básico</div>
                                    <div class="plan-price">$29<small>/mes</small></div>
                                </label>
                            </div>
                            <div class="plan-option">
                                <input type="radio" id="plan_professional" name="plan_id" value="2">
                                <label class="plan-label" for="plan_professional">
                                    <div class="plan-name">Profesional</div>
                                    <div class="plan-price">$79<small>/mes</small></div>
                                </label>
                            </div>
                            <div class="plan-option">
                                <input type="radio" id="plan_enterprise" name="plan_id" value="3">
                                <label class="plan-label" for="plan_enterprise">
                                    <div class="plan-name">Empresarial</div>
                                    <div class="plan-price">$199<small>/mes</small></div>
                                </label>
                            </div>
                            <div class="plan-option">
                                <input type="radio" id="plan_custom" name="plan_id" value="4">
                                <label class="plan-label" for="plan_custom">
                                    <div class="plan-name">Personalizado</div>
                                    <div class="plan-price">Consultar</div>
                                </label>
                            </div>
                        </div>
                        <div class="alert alert-info" style="background: #dbeafe; color: #1e40af;">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>¡14 días de prueba gratuita!</strong> No se requiere tarjeta de crédito.
                        </div>
                    </div>

                    <!-- Información Personal -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-person-fill me-2"></i> Información Personal
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Nombre Completo *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="country" class="form-label">País *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                    <select class="form-select" id="country" name="country_id" required>
                                        <option value="">Seleccionar país...</option>
                                        <option value="1" data-doc-type="RUT" data-doc-format="##.###.###-#" data-doc-desc="RUT (Rol Único Tributario) - Formato: 15.895.771-K">Chile</option>
                                        <option value="2" data-doc-type="CUIT/CUIL" data-doc-format="##-########-#" data-doc-desc="CUIT/CUIL - Formato: 20-12345678-9">Argentina</option>
                                        <option value="3" data-doc-type="DNI" data-doc-format="########" data-doc-desc="DNI (Documento Nacional de Identidad) - 8 dígitos">Perú</option>
                                        <option value="4" data-doc-type="NIT" data-doc-format="##########" data-doc-desc="NIT (Número de Identificación Tributaria) - 10 dígitos">Colombia</option>
                                        <option value="5" data-doc-type="RFC" data-doc-format="############" data-doc-desc="RFC (Registro Federal de Contribuyentes) - 12-13 caracteres">México</option>
                                        <option value="6" data-doc-type="CPF" data-doc-format="###.###.###-##" data-doc-desc="CPF (Cadastro de Pessoas Físicas) - Formato: 123.456.789-00">Brasil</option>
                                        <option value="7" data-doc-type="SSN" data-doc-format="###-##-####" data-doc-desc="SSN (Social Security Number) - Formato: 123-45-6789">Estados Unidos</option>
                                        <option value="8" data-doc-type="DNI/NIE" data-doc-format="########-#" data-doc-desc="DNI/NIE - Formato: 12345678-A">España</option>
                                        <option value="9" data-doc-type="CI" data-doc-format="#.###.###-#" data-doc-desc="CI (Cédula de Identidad) - Formato: 1.234.567-8">Uruguay</option>
                                    </select>
                                </div>
                                <div id="doc-type-info" class="doc-type-info" style="display: none;"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="tax_id" class="form-label"><span id="tax-id-label">RUT</span> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                    <input type="text" class="form-control" id="tax_id" name="tax_id" placeholder="Ej: 15895771k o 15.895.771-k" required>
                                </div>
                                <div class="invalid-feedback"></div>
                                <div class="valid-feedback"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="language" class="form-label">Idioma *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-translate"></i></span>
                                    <select class="form-select" id="language" name="language_code" required>
                                        <option value="">Seleccionar idioma...</option>
                                        <option value="es" selected>Español</option>
                                        <option value="en">English</option>
                                        <option value="pt">Português</option>
                                        <option value="fr">Français</option>
                                        <option value="de">Deutsch</option>
                                        <option value="it">Italiano</option>
                                        <option value="ru">Русский</option>
                                        <option value="zh">中文</option>
                                        <option value="ja">日本語</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Teléfono</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="+56 9 1234 5678">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credenciales de Acceso -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-shield-lock me-2"></i> Credenciales de Acceso
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="username" class="form-label">Nombre de Usuario *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-at"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <div class="password-strength-text" id="passwordStrengthText"></div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Términos y condiciones -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            Acepto los <a href="#" target="_blank">Términos y Condiciones</a> y la <a href="#" target="_blank">Política de Privacidad</a>
                        </label>
                    </div>

                    <!-- Botón de registro -->
                    <button type="submit" class="btn-register" id="btnSubmit">
                        <i class="bi bi-rocket-takeoff me-2"></i>
                        Crear Cuenta Gratis
                    </button>
                </form>

                <div class="login-link">
                    ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // =========================
        // VALIDACIÓN DE RUT CHILENO
        // =========================
        function cleanRUT(rut) {
            return rut.replace(/[^0-9kK]/g, '').toUpperCase();
        }

        function formatRUT(rut) {
            rut = cleanRUT(rut);
            if (rut.length < 2) return rut;

            const body = rut.slice(0, -1);
            const dv = rut.slice(-1);

            // Formatear con puntos
            let formattedBody = '';
            for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                if (j > 0 && j % 3 === 0) {
                    formattedBody = '.' + formattedBody;
                }
                formattedBody = body[i] + formattedBody;
            }

            return formattedBody + '-' + dv;
        }

        function validateChileanRUT(rut) {
            rut = cleanRUT(rut);

            if (rut.length < 8 || rut.length > 9) return false;

            const body = rut.slice(0, -1);
            const dv = rut.slice(-1);

            if (!/^\d+$/.test(body)) return false;

            let sum = 0;
            let multiplier = 2;

            for (let i = body.length - 1; i >= 0; i--) {
                sum += parseInt(body[i]) * multiplier;
                multiplier = multiplier < 7 ? multiplier + 1 : 2;
            }

            const expectedDV = 11 - (sum % 11);
            const calculatedDV = expectedDV === 11 ? '0' : (expectedDV === 10 ? 'K' : expectedDV.toString());

            return dv === calculatedDV;
        }

        // =========================
        // MANEJO DEL FORMULARIO
        // =========================
        const taxIdInput = document.getElementById('tax_id');
        const countrySelect = document.getElementById('country');
        const taxIdLabel = document.getElementById('tax-id-label');
        const docTypeInfo = document.getElementById('doc-type-info');

        // Cambio de país
        countrySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const docType = selectedOption.dataset.docType || 'RUT';
            const docDesc = selectedOption.dataset.docDesc || '';

            taxIdLabel.textContent = docType;
            taxIdInput.value = '';
            taxIdInput.classList.remove('is-valid', 'is-invalid');

            if (docDesc) {
                docTypeInfo.innerHTML = '<strong>' + docType + ':</strong> ' + docDesc;
                docTypeInfo.style.display = 'block';
            } else {
                docTypeInfo.style.display = 'none';
            }
        });

        // Formateo automático de RUT (solo para Chile)
        taxIdInput.addEventListener('input', function() {
            const country = countrySelect.value;

            // Solo formatear si es Chile (país 1)
            if (country === '1') {
                const cursorPosition = this.selectionStart;
                const oldLength = this.value.length;
                this.value = formatRUT(this.value);
                const newLength = this.value.length;

                // Ajustar posición del cursor
                const diff = newLength - oldLength;
                this.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
            }
        });

        // Validación en tiempo real
        taxIdInput.addEventListener('blur', function() {
            const country = countrySelect.value;
            const value = this.value.trim();

            if (!value) return;

            if (country === '1') { // Chile - Validar RUT
                if (validateChileanRUT(value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    this.nextElementSibling.textContent = '✓ RUT válido';
                    this.nextElementSibling.classList.remove('invalid-feedback');
                    this.nextElementSibling.classList.add('valid-feedback');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'RUT inválido. Verifica el dígito verificador.';
                    this.nextElementSibling.classList.remove('valid-feedback');
                    this.nextElementSibling.classList.add('invalid-feedback');
                }
            }
        });

        // =========================
        // FORTALEZA DE CONTRASEÑA
        // =========================
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            const percentage = (strength / 6) * 100;
            strengthBar.style.width = percentage + '%';

            if (strength <= 2) {
                strengthBar.style.backgroundColor = '#ef4444';
                strengthText.textContent = 'Débil';
                strengthText.style.color = '#ef4444';
            } else if (strength <= 4) {
                strengthBar.style.backgroundColor = '#f59e0b';
                strengthText.textContent = 'Media';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthBar.style.backgroundColor = '#10b981';
                strengthText.textContent = 'Fuerte';
                strengthText.style.color = '#10b981';
            }
        });

        // =========================
        // MOSTRAR/OCULTAR CONTRASEÑA
        // =========================
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // =========================
        // VALIDACIÓN DEL FORMULARIO
        // =========================
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            const btnSubmit = document.getElementById('btnSubmit');

            // Validar que las contraseñas coincidan
            if (password !== passwordConfirm) {
                document.getElementById('password_confirm').classList.add('is-invalid');
                document.getElementById('password_confirm').nextElementSibling.textContent = 'Las contraseñas no coinciden';
                return;
            }

            // Validar RUT si es Chile
            if (countrySelect.value === '1') {
                if (!validateChileanRUT(taxIdInput.value)) {
                    taxIdInput.classList.add('is-invalid');
                    taxIdInput.nextElementSibling.textContent = 'RUT inválido';
                    return;
                }
            }

            // Deshabilitar botón
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando cuenta...';

            // Enviar formulario
            this.submit();
        });
    </script>
</body>
</html>

<?php
// =========================
// PROCESAMIENTO DEL FORMULARIO
// =========================
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/rut_validator.php';

    $db = Database::getInstance();

    try {
        // Sanitizar datos
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $country_id = (int) $_POST['country_id'];
        $language_code = sanitize($_POST['language_code']);
        $tax_id = trim($_POST['tax_id']);
        $phone = sanitize($_POST['phone'] ?? '');
        $plan_id = (int) $_POST['plan_id'];

        // Validaciones
        if ($password !== $password_confirm) {
            throw new Exception('Las contraseñas no coinciden');
        }

        if (strlen($password) < 8) {
            throw new Exception('La contraseña debe tener al menos 8 caracteres');
        }

        if (!isValidEmail($email)) {
            throw new Exception('Email inválido');
        }

        // Validar RUT si es Chile
        if ($country_id === 1) {
            if (!validateChileanRUT($tax_id)) {
                throw new Exception('RUT chileno inválido');
            }
            $tax_id = cleanRUT($tax_id);
        }

        // Verificar si el email ya existe
        $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            throw new Exception('El email ya está registrado');
        }

        // Verificar si el username ya existe
        $existingUsername = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existingUsername) {
            throw new Exception('El nombre de usuario ya está en uso');
        }

        // Hash de la contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Calcular fecha de fin de trial (14 días)
        $trialEndsAt = date('Y-m-d H:i:s', strtotime('+14 days'));

        // Insertar usuario
        $userId = $db->insert(
            "INSERT INTO users (username, email, password, full_name, tax_id, country_id, language_code, phone, status, trial_ends_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'trial', ?, NOW())",
            [$username, $email, $hashedPassword, $full_name, $tax_id, $country_id, $language_code, $phone, $trialEndsAt]
        );

        // Log de actividad
        logActivity($userId, 'user_registered', 'Usuario registrado en el sistema', 'AUTH');

        // Mensaje de éxito
        $_SESSION['success'] = '¡Cuenta creada exitosamente! Ya puedes iniciar sesión.';
        header('Location: login.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>
