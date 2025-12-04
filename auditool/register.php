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

// Obtener plan si viene de landing
$selected_plan = isset($_GET['plan']) ? $_GET['plan'] : 'professional';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name']);
    $company_rut = trim($_POST['company_rut']);
    $company_country = $_POST['company_country'];
    $company_email = trim($_POST['company_email']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $plan = $_POST['plan'];
    $language = $_POST['language'];

    // Validaciones
    if (empty($company_name) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Por favor complete todos los campos obligatorios';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        $conn = getDBConnection();

        // Verificar si email ya existe
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'El email ya está registrado';
            $check_stmt->close();
        } else {
            $check_stmt->close();

            // Crear empresa
            $company_sql = "INSERT INTO companies (
                company_name, company_rut, company_email, company_country,
                active_isos, status, created_date
            ) VALUES (?, ?, ?, ?, ?, 'active', NOW())";

            $active_isos_json = json_encode(['iso_27001']); // Por defecto ISO 27001

            $company_stmt = $conn->prepare($company_sql);
            $company_stmt->bind_param("sssss",
                $company_name,
                $company_rut,
                $company_email,
                $company_country,
                $active_isos_json
            );

            if ($company_stmt->execute()) {
                $company_id = $conn->insert_id;
                $company_stmt->close();

                // Crear usuario admin
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $username = strtolower(str_replace(' ', '', $full_name));

                $user_sql = "INSERT INTO users (
                    company_id, username, email, password, full_name,
                    role, language, status, created_date
                ) VALUES (?, ?, ?, ?, ?, 'admin', ?, 'active', NOW())";

                $user_stmt = $conn->prepare($user_sql);
                $user_stmt->bind_param("isssss",
                    $company_id,
                    $username,
                    $email,
                    $password_hash,
                    $full_name,
                    $language
                );

                if ($user_stmt->execute()) {
                    $user_id = $conn->insert_id;
                    $user_stmt->close();

                    // Crear subscripción con trial de 5 días
                    $sub_sql = "INSERT INTO subscriptions (
                        company_id, plan_name, plan_price, plan_currency,
                        billing_cycle, status, trial_start_date, trial_end_date,
                        max_users, max_companies, max_isos, created_date
                    ) VALUES (?, ?, ?, 'USD', 'monthly', 'trial', NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), ?, ?, ?, NOW())";

                    $plan_details = [
                        'basic' => ['price' => 99, 'users' => 5, 'companies' => 1, 'isos' => 3],
                        'professional' => ['price' => 299, 'users' => 25, 'companies' => 5, 'isos' => 10],
                        'enterprise' => ['price' => 799, 'users' => 999, 'companies' => 999, 'isos' => 14]
                    ];

                    $plan_info = $plan_details[$plan];

                    $sub_stmt = $conn->prepare($sub_sql);
                    $sub_stmt->bind_param("isdiii",
                        $company_id,
                        $plan,
                        $plan_info['price'],
                        $plan_info['users'],
                        $plan_info['companies'],
                        $plan_info['isos']
                    );

                    $sub_stmt->execute();
                    $sub_stmt->close();

                    // Log activity
                    logActivity($user_id, 'register', 'authentication', 'Nueva empresa registrada: ' . $company_name);

                    $conn->close();

                    // Redirigir a login
                    header('Location: login.php?registered=success');
                    exit;
                } else {
                    $error = 'Error al crear usuario: ' . $user_stmt->error;
                    $user_stmt->close();
                }
            } else {
                $error = 'Error al crear empresa: ' . $company_stmt->error;
                $company_stmt->close();
            }
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - AUDITOR PRO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            margin: 40px auto;
            padding: 60px;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin-bottom: 10px;
        }
        .register-title {
            font-size: 32px;
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        .register-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 40px;
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
        .section-title {
            font-size: 18px;
            color: #667eea;
            font-weight: bold;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        label .required {
            color: #c33;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .plan-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .plan-option {
            position: relative;
        }
        .plan-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .plan-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .plan-option input:checked + .plan-card {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .plan-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .plan-price {
            font-size: 24px;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .plan-features {
            font-size: 12px;
            color: #666;
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
            margin-top: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .back-home {
            text-align: center;
            margin-top: 15px;
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
            .register-container {
                padding: 40px 30px;
            }
            .form-row,
            .plan-selector {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">🔐 AUDITOR PRO</div>
        <h2 class="register-title">Crear Cuenta</h2>
        <p class="register-subtitle">Comience su prueba gratuita de 30 días</p>

        <?php if ($error): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="section-title">📋 Información de la Empresa</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nombre de la Empresa <span class="required">*</span></label>
                    <input type="text" name="company_name" required
                           value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>RUT / ID Fiscal</label>
                    <input type="text" name="company_rut"
                           value="<?php echo isset($_POST['company_rut']) ? htmlspecialchars($_POST['company_rut']) : ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email de la Empresa</label>
                    <input type="email" name="company_email"
                           value="<?php echo isset($_POST['company_email']) ? htmlspecialchars($_POST['company_email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>País <span class="required">*</span></label>
                    <select name="company_country" required>
                        <option value="CL">Chile</option>
                        <option value="AR">Argentina</option>
                        <option value="PE">Perú</option>
                        <option value="CO">Colombia</option>
                        <option value="MX">México</option>
                        <option value="BR">Brasil</option>
                        <option value="ES">España</option>
                        <option value="US">Estados Unidos</option>
                    </select>
                </div>
            </div>

            <div class="section-title">👤 Información del Administrador</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nombre Completo <span class="required">*</span></label>
                    <input type="text" name="full_name" required
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contraseña <span class="required">*</span></label>
                    <input type="password" name="password" required minlength="8">
                </div>

                <div class="form-group">
                    <label>Confirmar Contraseña <span class="required">*</span></label>
                    <input type="password" name="password_confirm" required minlength="8">
                </div>
            </div>

            <div class="form-group">
                <label>Idioma</label>
                <select name="language">
                    <option value="es">Español</option>
                    <option value="en">English</option>
                    <option value="pt">Português</option>
                </select>
            </div>

            <div class="section-title">💳 Seleccione su Plan</div>

            <div class="plan-selector">
                <div class="plan-option">
                    <input type="radio" name="plan" id="plan_basic" value="basic"
                           <?php echo $selected_plan === 'basic' ? 'checked' : ''; ?>>
                    <label for="plan_basic" class="plan-card">
                        <div class="plan-name">Básico</div>
                        <div class="plan-price">$99</div>
                        <div class="plan-features">
                            5 Usuarios<br>
                            1 Empresa<br>
                            3 ISOs
                        </div>
                    </label>
                </div>

                <div class="plan-option">
                    <input type="radio" name="plan" id="plan_professional" value="professional"
                           <?php echo $selected_plan === 'professional' ? 'checked' : ''; ?>>
                    <label for="plan_professional" class="plan-card">
                        <div class="plan-name">Profesional</div>
                        <div class="plan-price">$299</div>
                        <div class="plan-features">
                            25 Usuarios<br>
                            5 Empresas<br>
                            10 ISOs
                        </div>
                    </label>
                </div>

                <div class="plan-option">
                    <input type="radio" name="plan" id="plan_enterprise" value="enterprise"
                           <?php echo $selected_plan === 'enterprise' ? 'checked' : ''; ?>>
                    <label for="plan_enterprise" class="plan-card">
                        <div class="plan-name">Enterprise</div>
                        <div class="plan-price">$799</div>
                        <div class="plan-features">
                            Ilimitado<br>
                            Ilimitado<br>
                            14 ISOs
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn">
                Crear Cuenta - Prueba Gratis 30 Días
            </button>
        </form>

        <div class="login-link">
            ¿Ya tiene una cuenta? <a href="login.php">Iniciar Sesión</a>
        </div>

        <div class="back-home">
            <a href="landing.php">← Volver al inicio</a>
        </div>
    </div>
</body>
</html>
