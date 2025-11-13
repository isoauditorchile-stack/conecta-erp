<?php
/**
 * CONECTA ERP - Página Principal Mejorada
 * Sistema Multipaís, Multiidioma, Multiempresa, Multiusuario
 * Con validación de RUT/DNI/CUIT por país y integración Previred/SII
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/i18n.php';

// Inicializar i18n
$i18n = I18n::getInstance();

// Detectar idioma
if (isset($_GET['lang'])) {
    $i18n->setLanguage($_GET['lang']);
} else if (isset($_SESSION['language'])) {
    $i18n->setLanguage($_SESSION['language']);
}

session_start();

$message = '';
$message_type = '';

// PROCESAR REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    try {
        $db = Database::getInstance();

        // Datos personales
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = strtolower(trim($_POST['email']));
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        // Datos empresa
        $company_name = trim($_POST['company_name']);
        $legal_name = trim($_POST['legal_name'] ?? $company_name);
        $tax_id = trim($_POST['tax_id']);
        $country = $_POST['country'];
        $industry = trim($_POST['industry'] ?? '');

        // Ubicación
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state_province = trim($_POST['state_province'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone']);
        $website = trim($_POST['website'] ?? '');

        // Configuración
        $currency = $_POST['currency'];
        $language = $_POST['language'];
        $timezone = $_POST['timezone'] ?? 'America/Santiago';
        $employees = $_POST['employees'];
        $annual_revenue = $_POST['annual_revenue'] ?? '';
        $fiscal_year_start = $_POST['fiscal_year_start'] ?? '01-01';

        // Integr aciones
        $previred_enabled = isset($_POST['previred_enabled']) ? 1 : 0;
        $previred_rut = trim($_POST['previred_rut'] ?? '');
        $sii_enabled = isset($_POST['sii_enabled']) ? 1 : 0;
        $sii_rut = trim($_POST['sii_rut'] ?? '');

        // Validar RUT/Tax ID según país
        $country_config = $db->fetchOne("SELECT * FROM countries WHERE code = ?", [$country]);
        if (!$country_config) {
            throw new Exception(__('country_not_found'));
        }

        // Admin especial
        $is_admin = ($email === 'auditorexchile@gmail.com');
        $status = $is_admin ? 'active' : 'trial';
        $trial_days = $is_admin ? 0 : 14;
        $trial_ends_at = $is_admin ? null : date('Y-m-d H:i:s', strtotime('+14 days'));

        // Iniciar transacción
        $db->getConnection()->beginTransaction();

        // Insertar usuario
        $sql_user = "INSERT INTO users (
            firstname, lastname, email, username, password,
            company_name, tax_id, tax_id_type,
            position, industry, phone, website,
            address, city, state_province, postal_code,
            country, currency, language, preferred_language, timezone,
            employees, is_admin, status, trial_days, trial_ends_at,
            requires_approval, approved_by_admin,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $user_id = $db->insert($sql_user, [
            $firstname, $lastname, $email, $username, $password,
            $company_name, $tax_id, $country_config['tax_id_name'],
            $_POST['position'] ?? 'Director', $industry, $phone, $website,
            $address, $city, $state_province, $postal_code,
            $country, $currency, $language, $language, $timezone,
            $employees, $is_admin ? 1 : 0, $status, $trial_days, $trial_ends_at,
            $is_admin ? 0 : 1, $is_admin ? 1 : 0
        ]);

        // Obtener country_id
        $country_id = $country_config['id'];

        // Insertar empresa
        $sql_company = "INSERT INTO companies (
            owner_user_id, company_name, legal_name, tax_id, country_id,
            industry, address, city, state_province, postal_code,
            phone, email, website, fiscal_year_start, employees_count, annual_revenue,
            previred_enabled, previred_rut, sii_enabled, sii_rut,
            is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";

        $company_id = $db->insert($sql_company, [
            $user_id, $company_name, $legal_name, $tax_id, $country_id,
            $industry, $address, $city, $state_province, $postal_code,
            $phone, $email, $website, $fiscal_year_start, $employees, $annual_revenue,
            $previred_enabled, $previred_rut, $sii_enabled, $sii_rut
        ]);

        // Actualizar user con company_id
        $db->update("UPDATE users SET company_id = ? WHERE id = ?", [$company_id, $user_id]);

        // Asociar usuario a empresa
        $db->insert("INSERT INTO company_users (company_id, user_id, role, joined_at) VALUES (?, ?, 'owner', NOW())", [$company_id, $user_id]);

        // Si tiene Previred habilitado, crear configuración
        if ($previred_enabled && !empty($previred_rut)) {
            $db->insert("INSERT INTO previred_config (company_id, enabled, rut_empleador, environment, created_at) VALUES (?, 1, ?, 'sandbox', NOW())", [$company_id, $previred_rut]);
        }

        // Si tiene SII habilitado, crear configuración
        if ($sii_enabled && !empty($sii_rut)) {
            $db->insert("INSERT INTO sii_config (company_id, enabled, rut_empresa, razon_social, environment, created_at) VALUES (?, 1, ?, ?, 'certificacion', NOW())", [$company_id, $sii_rut, $legal_name]);
        }

        // Commit transacción
        $db->getConnection()->commit();

        // Loguear actividad
        logActivity($user_id, 'register', 'Usuario registrado exitosamente');

        if ($is_admin) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['is_admin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['company_id'] = $company_id;
            $_SESSION['language'] = $language;
            header('Location: /admin/dashboard_admin.php');
            exit;
        } else {
            $message = __('registration_success_trial');
            $message_type = 'success';
        }

    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->getConnection()->rollBack();
        }
        $message = __('error') . ": " . $e->getMessage();
        $message_type = 'error';
        error_log("Registration error: " . $e->getMessage());
    }
}

// PROCESAR LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    try {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['language'] = $user['preferred_language'] ?? 'es';

            // Actualizar último login
            $db->update("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            // Log actividad
            logActivity($user['id'], 'login', 'Usuario inició sesión');

            if ($user['is_admin']) {
                header('Location: /admin/dashboard_admin.php');
            } else {
                header('Location: /user/dashboard_user.php');
            }
            exit;
        } else {
            $message = __('invalid_credentials');
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = __('error') . ": " . $e->getMessage();
        $message_type = 'error';
    }
}

// Obtener países activos
$countries = getActiveCountries($i18n->getLanguage());
?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->getLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_name'); ?> - <?php echo __('enterprise_management_system'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/forms-enhanced.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #6366f1; --primary-dark: #4f46e5; --secondary: #8b5cf6; --accent: #ec4899;
            --dark: #0f172a; --dark-light: #1e293b; --text: #f8fafc; --text-muted: #cbd5e1;
            --success: #10b981; --warning: #f59e0b; --error: #ef4444; --info: #3b82f6;
        }
        body { font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); overflow-x: hidden; }

        /* Idioma Selector Fixed */
        .language-selector-fixed {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: rgba(30, 41, 59, 0.95);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .language-selector-fixed select {
            background: transparent;
            border: none;
            color: var(--text);
            font-weight: 600;
            cursor: pointer;
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .language-selector-fixed select option {
            background: var(--dark-light);
            color: var(--text);
        }

        /* Resto de estilos del index.php original... */
        .animated-bg { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; overflow: hidden; }
        .animated-bg::before, .animated-bg::after { content: ''; position: absolute; width: 800px; height: 800px; border-radius: 50%; filter: blur(120px); opacity: 0.15; animation: float 25s infinite ease-in-out; }
        .animated-bg::before { background: linear-gradient(135deg, var(--primary), var(--secondary)); top: -300px; left: -300px; }
        .animated-bg::after { background: linear-gradient(135deg, var(--accent), var(--secondary)); bottom: -300px; right: -300px; animation-delay: 12s; }
        @keyframes float { 0%, 100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(100px, -80px) scale(1.1); } 66% { transform: translate(-60px, 60px) scale(0.9); } }

        nav { position: fixed; top: 0; width: 100%; padding: 1.5rem 5%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.08); z-index: 1000; transition: all 0.3s ease; }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 1rem; font-size: 1.8rem; font-weight: 900; text-decoration: none; color: var(--text); }
        .logo i { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 0.6rem; border-radius: 12px; color: white; box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4); }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px); z-index: 2000; align-items: center; justify-content: center; animation: fadeIn 0.3s; padding: 2rem; overflow-y: auto; }
        .modal.active { display: flex; }
        .modal-content { background: var(--dark-light); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; max-width: 900px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 3rem; position: relative; animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); }

        .btn { padding: 1rem 2.5rem; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 1rem; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; box-shadow: 0 5px 20px rgba(99, 102, 241, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(99, 102, 241, 0.6); }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text); font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 1rem; background: var(--dark); border: 2px solid rgba(255, 255, 255, 0.1); border-radius: 10px; color: var(--text); font-size: 1rem; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .form-section:last-child { border-bottom: none; }
        .form-section-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--primary); display: flex; align-items: center; gap: 0.75rem; }
        .checkbox-group { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: rgba(99, 102, 241, 0.05); border-radius: 8px; border: 1px solid rgba(99, 102, 241, 0.2); }
        .checkbox-group input[type="checkbox"] { width: auto; height: 20px; width: 20px; cursor: pointer; }
        .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; z-index: 1; padding: 8rem 5% 5rem; }
        .hero-content { max-width: 1400px; text-align: center; }
        .hero h1 { font-size: 4.5rem; font-weight: 900; margin-bottom: 1.5rem; background: linear-gradient(135deg, white 0%, var(--text-muted) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { font-size: 1.5rem; color: var(--text-muted); margin-bottom: 3rem; max-width: 800px; margin-left: auto; margin-right: auto; }
        .close-modal { position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(255, 255, 255, 0.1); border: none; color: var(--text); font-size: 1.5rem; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: all 0.3s; }
        .close-modal:hover { background: var(--error); transform: rotate(90deg); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .tax-id-hint { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem; font-style: italic; }
    </style>
</head>
<body>
    <!-- Selector de Idioma Fijo -->
    <div class="language-selector-fixed">
        <select id="languageSelectTop" onchange="changeLanguage(this.value)">
            <option value="es" <?php echo $i18n->getLanguage() === 'es' ? 'selected' : ''; ?>>🇪🇸 Español</option>
            <option value="en" <?php echo $i18n->getLanguage() === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
            <option value="pt" <?php echo $i18n->getLanguage() === 'pt' ? 'selected' : ''; ?>>🇧🇷 Português</option>
            <option value="fr" <?php echo $i18n->getLanguage() === 'fr' ? 'selected' : ''; ?>>🇫🇷 Français</option>
            <option value="de" <?php echo $i18n->getLanguage() === 'de' ? 'selected' : ''; ?>>🇩🇪 Deutsch</option>
            <option value="it" <?php echo $i18n->getLanguage() === 'it' ? 'selected' : ''; ?>>🇮🇹 Italiano</option>
            <option value="ru" <?php echo $i18n->getLanguage() === 'ru' ? 'selected' : ''; ?>>🇷🇺 Русский</option>
            <option value="zh" <?php echo $i18n->getLanguage() === 'zh' ? 'selected' : ''; ?>>🇨🇳 中文</option>
        </select>
    </div>

    <?php if ($message): ?>
    <div style="position:fixed;top:80px;right:20px;z-index:10000;padding:1.5rem 2rem;background:<?php echo $message_type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'; ?>;color:white;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.3);font-weight:600;display:flex;align-items:center;gap:1rem;max-width:400px;">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?>" style="font-size:1.5rem;"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <div class="animated-bg"></div>

    <!-- Navegación -->
    <nav>
        <div class="nav-container">
            <a href="/" class="logo">
                <i class="fas fa-network-wired"></i>
                <span>CONECTA ERP</span>
            </a>
            <div style="display:flex;gap:1rem;">
                <button class="btn btn-primary" onclick="openModal('loginModal')"><?php echo __('login'); ?></button>
                <button class="btn btn-primary" onclick="openModal('registerModal')"><?php echo __('register'); ?></button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1><?php echo __('app_name'); ?></h1>
            <p><?php echo __('hero_description'); ?></p>
            <button class="btn btn-primary" style="font-size:1.2rem;padding:1.5rem 3rem;" onclick="openModal('registerModal')">
                <?php echo __('start_free_trial'); ?> <i class="fas fa-arrow-right" style="margin-left:0.5rem;"></i>
            </button>
        </div>
    </section>

    <!-- Modal de Login -->
    <div id="loginModal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <button class="close-modal" onclick="closeModal('loginModal')">&times;</button>
            <h2 style="font-size:2rem;margin-bottom:2rem;text-align:center;"><?php echo __('login'); ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label><?php echo __('email'); ?></label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label><?php echo __('password'); ?></label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1rem;">
                    <i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Modal de Registro COMPLETO con TODOS los campos -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('registerModal')">&times;</button>
            <h2 style="font-size:2rem;margin-bottom:2rem;text-align:center;"><?php echo __('create_account'); ?></h2>

            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="action" value="register">

                <!-- SECCIÓN 1: Datos Personales -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user"></i> <?php echo __('personal_information'); ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('firstname'); ?> *</label>
                            <input type="text" name="firstname" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('lastname'); ?> *</label>
                            <input type="text" name="lastname" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('email'); ?> *</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('username'); ?> *</label>
                            <input type="text" name="username" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('password'); ?> *</label>
                            <input type="password" name="password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('position'); ?></label>
                            <input type="text" name="position" value="Director" placeholder="CEO, Director, Gerente...">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: Información de la Empresa -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-building"></i> <?php echo __('company_information'); ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('company_name'); ?> *</label>
                            <input type="text" name="company_name" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('legal_name'); ?></label>
                            <input type="text" name="legal_name" placeholder="<?php echo __('legal_name_hint'); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('country'); ?> *</label>
                            <select name="country" id="country" required onchange="updateTaxIdFormat(this.value)">
                                <option value=""><?php echo __('select_country'); ?></option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country['code']; ?>" data-taxid="<?php echo $country['tax_id_name']; ?>">
                                    <?php echo $country['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label id="taxIdLabel"><?php echo __('tax_id'); ?> *</label>
                            <input type="text" name="tax_id" id="tax_id" required>
                            <div class="tax-id-hint" id="taxIdHint"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('industry'); ?></label>
                            <input type="text" name="industry" placeholder="<?php echo __('industry_hint'); ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('employees_count'); ?></label>
                            <select name="employees">
                                <option value="1-10">1-10</option>
                                <option value="11-50">11-50</option>
                                <option value="51-200">51-200</option>
                                <option value="201-500">201-500</option>
                                <option value="500+">500+</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('phone'); ?> *</label>
                            <input type="tel" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('website'); ?></label>
                            <input type="url" name="website" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: Ubicación -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i> <?php echo __('location'); ?>
                    </div>
                    <div class="form-group">
                        <label><?php echo __('address'); ?></label>
                        <input type="text" name="address">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('city'); ?></label>
                            <input type="text" name="city">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('state_province'); ?></label>
                            <input type="text" name="state_province">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('postal_code'); ?></label>
                            <input type="text" name="postal_code">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 4: Configuración Regional -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-globe"></i> <?php echo __('regional_settings'); ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('currency'); ?> *</label>
                            <select name="currency" required>
                                <option value="USD">USD - Dólar Estadounidense</option>
                                <option value="CLP" selected>CLP - Peso Chileno</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="ARS">ARS - Peso Argentino</option>
                                <option value="PEN">PEN - Sol Peruano</option>
                                <option value="COP">COP - Peso Colombiano</option>
                                <option value="MXN">MXN - Peso Mexicano</option>
                                <option value="BRL">BRL - Real Brasileño</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('language'); ?> *</label>
                            <select name="language" required>
                                <option value="es" selected>Español</option>
                                <option value="en">English</option>
                                <option value="pt">Português</option>
                                <option value="fr">Français</option>
                                <option value="de">Deutsch</option>
                                <option value="it">Italiano</option>
                                <option value="ru">Русский</option>
                                <option value="zh">中文</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('timezone'); ?></label>
                            <select name="timezone">
                                <option value="America/Santiago" selected>América/Santiago (Chile)</option>
                                <option value="America/Argentina/Buenos_Aires">América/Buenos Aires</option>
                                <option value="America/Lima">América/Lima</option>
                                <option value="America/Bogota">América/Bogotá</option>
                                <option value="America/Mexico_City">América/Ciudad de México</option>
                                <option value="America/Sao_Paulo">América/São Paulo</option>
                                <option value="America/New_York">América/Nueva York</option>
                                <option value="Europe/Madrid">Europa/Madrid</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('fiscal_year_start'); ?></label>
                            <input type="text" name="fiscal_year_start" value="01-01" placeholder="MM-DD">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('annual_revenue'); ?></label>
                            <input type="text" name="annual_revenue" placeholder="<?php echo __('annual_revenue_hint'); ?>">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 5: Integraciones (Chile) -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-plug"></i> <?php echo __('integrations'); ?> (<?php echo __('chile_only'); ?>)
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="previred_enabled" id="previred_enabled" onchange="togglePreviredFields()">
                        <label for="previred_enabled" style="margin:0;">
                            <strong>Previred</strong> - <?php echo __('previred_description'); ?>
                        </label>
                    </div>
                    <div id="previredFields" style="display:none;margin-top:1rem;">
                        <div class="form-group">
                            <label><?php echo __('previred_rut'); ?></label>
                            <input type="text" name="previred_rut" placeholder="12.345.678-9">
                        </div>
                    </div>

                    <div class="checkbox-group" style="margin-top:1rem;">
                        <input type="checkbox" name="sii_enabled" id="sii_enabled" onchange="toggleSIIFields()">
                        <label for="sii_enabled" style="margin:0;">
                            <strong>SII</strong> - <?php echo __('sii_description'); ?>
                        </label>
                    </div>
                    <div id="siiFields" style="display:none;margin-top:1rem;">
                        <div class="form-group">
                            <label><?php echo __('sii_rut'); ?></label>
                            <input type="text" name="sii_rut" placeholder="12.345.678-9">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;font-size:1.1rem;padding:1.2rem;">
                    <i class="fas fa-rocket"></i> <?php echo __('create_account'); ?>
                </button>
            </form>
        </div>
    </div>

    <script src="/assets/js/tax-id-validator.js"></script>
    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function togglePreviredFields() {
            const fields = document.getElementById('previredFields');
            fields.style.display = document.getElementById('previred_enabled').checked ? 'block' : 'none';
        }

        function toggleSIIFields() {
            const fields = document.getElementById('siiFields');
            fields.style.display = document.getElementById('sii_enabled').checked ? 'block' : 'none';
        }

        function updateTaxIdFormat(countryCode) {
            const taxIdInput = document.getElementById('tax_id');
            const taxIdLabel = document.getElementById('taxIdLabel');
            const taxIdHint = document.getElementById('taxIdHint');
            const countrySelect = document.getElementById('country');
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];

            if (countryCode && selectedOption) {
                const taxIdName = selectedOption.getAttribute('data-taxid');
                taxIdLabel.textContent = taxIdName + ' *';

                // Inicializar validador de RUT
                if (typeof TaxIDValidator !== 'undefined') {
                    TaxIDValidator.init(taxIdInput, countryCode);

                    // Mostrar formato esperado
                    const config = TaxIDValidator.config[countryCode];
                    if (config) {
                        taxIdHint.textContent = '<?php echo __("format"); ?>: ' + config.format;
                    }
                }
            }
        }

        // Inicializar al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const countrySelect = document.getElementById('country');
            if (countrySelect) {
                // Si hay un país por defecto (Chile), inicializar
                const defaultCountry = 'CL';
                countrySelect.value = defaultCountry;
                updateTaxIdFormat(defaultCountry);
            }
        });

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
