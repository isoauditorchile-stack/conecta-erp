<?php
/**
 * CONECTA ERP - Página Principal con Planes de Pago
 * Sistema REAL de Producción
 * auditorexchile@gmail.com tiene control total
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

// Obtener planes activos
try {
    $db = Database::getInstance();
    $planes = $db->fetchAll("SELECT * FROM planes WHERE activo = 1 AND codigo != 'TRIAL' ORDER BY orden_visualizacion");
} catch (Exception $e) {
    // Si no hay conexión a BD, usar planes por defecto
    $planes = [];
}

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

        // Plan seleccionado
        $plan_codigo = $_POST['plan'] ?? 'BASICO';

        // Datos empresa
        $company_name = trim($_POST['company_name']);
        $legal_name = trim($_POST['legal_name'] ?? $company_name);
        $tax_id = trim($_POST['tax_id']);
        $country = $_POST['country'];
        $industry = trim($_POST['industry'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state_province = trim($_POST['state_province'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone']);
        $website = trim($_POST['website'] ?? '');
        $currency = $_POST['currency'];
        $language = $_POST['language'];
        $timezone = $_POST['timezone'] ?? 'America/Santiago';
        $employees = $_POST['employees'];
        $annual_revenue = $_POST['annual_revenue'] ?? '';
        $fiscal_year_start = $_POST['fiscal_year_start'] ?? '01-01';
        $previred_enabled = isset($_POST['previred_enabled']) ? 1 : 0;
        $previred_rut = trim($_POST['previred_rut'] ?? '');
        $sii_enabled = isset($_POST['sii_enabled']) ? 1 : 0;
        $sii_rut = trim($_POST['sii_rut'] ?? '');

        // Validar país
        $country_config = $db->fetchOne("SELECT * FROM countries WHERE code = ?", [$country]);
        if (!$country_config) {
            throw new Exception(__('country_not_found'));
        }

        // Determinar si es super admin
        $is_super_admin = ($email === 'auditorexchile@gmail.com');

        // Para usuarios normales: estado pendiente de aprobación
        // Para super admin: activo inmediatamente
        $status = $is_super_admin ? 'active' : 'pending_approval';
        $is_admin = $is_super_admin ? 1 : 0;
        $requires_approval = $is_super_admin ? 0 : 1;
        $approved_by_admin = $is_super_admin ? 1 : 0;
        $trial_ends_at = $is_super_admin ? null : date('Y-m-d H:i:s', strtotime('+14 days'));

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
            $employees, $is_admin, $status, $is_super_admin ? 0 : 14, $trial_ends_at,
            $requires_approval, $approved_by_admin
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

        // Integraciones
        if ($previred_enabled && !empty($previred_rut)) {
            $db->insert("INSERT INTO previred_config (company_id, enabled, rut_empleador, environment, created_at) VALUES (?, 1, ?, 'sandbox', NOW())", [$company_id, $previred_rut]);
        }

        if ($sii_enabled && !empty($sii_rut)) {
            $db->insert("INSERT INTO sii_config (company_id, enabled, rut_empresa, razon_social, environment, created_at) VALUES (?, 1, ?, ?, 'certificacion', NOW())", [$company_id, $sii_rut, $legal_name]);
        }

        // Registrar solicitud de aprobación (para usuarios normales)
        if (!$is_super_admin) {
            $db->insert(
                "INSERT INTO aprobaciones_usuario (
                    user_id, estado, fecha_solicitud, ip_registro, user_agent,
                    datos_empresa
                ) VALUES (?, 'pendiente', NOW(), ?, ?, ?)",
                [
                    $user_id,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    json_encode([
                        'company_name' => $company_name,
                        'tax_id' => $tax_id,
                        'country' => $country,
                        'plan_seleccionado' => $plan_codigo
                    ])
                ]
            );
        }

        // Commit transacción
        $db->getConnection()->commit();

        // Loguear actividad
        logActivity($user_id, 'register', 'Usuario registrado - Pendiente de aprobación');

        if ($is_super_admin) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['is_admin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['company_id'] = $company_id;
            $_SESSION['language'] = $language;
            header('Location: /admin/panel_super_admin.php');
            exit;
        } else {
            $message = '✅ Registro exitoso! Tu solicitud ha sido enviada al administrador para aprobación. Recibirás un email cuando tu cuenta sea activada.';
            $message_type = 'success';
        }

    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->getConnection()->rollBack();
        }
        $message = 'Error: ' . $e->getMessage();
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
            // Verificar estado
            if ($user['status'] === 'pending_approval') {
                $message = '⏳ Tu cuenta está pendiente de aprobación por el administrador. Por favor espera.';
                $message_type = 'warning';
            } elseif ($user['status'] === 'rejected') {
                $message = '❌ Tu solicitud de cuenta fue rechazada. Contacta al soporte.';
                $message_type = 'error';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['language'] = $user['preferred_language'] ?? 'es';

                // Actualizar último login
                $db->update("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                logActivity($user['id'], 'login', 'Usuario inició sesión');

                if ($user['is_admin']) {
                    header('Location: /admin/panel_super_admin.php');
                } else {
                    header('Location: /user/dashboard_user.php');
                }
                exit;
            }
        } else {
            $message = '❌ Credenciales inválidas';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
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
    <title>CONECTA ERP - Sistema ERP Profesional</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/forms-enhanced.css">
    <style>
        /* Estilos previos + nuevos estilos para planes */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #6366f1; --primary-dark: #4f46e5; --secondary: #8b5cf6; --accent: #ec4899;
            --dark: #0f172a; --dark-light: #1e293b; --text: #f8fafc; --text-muted: #cbd5e1;
            --success: #10b981; --warning: #f59e0b; --error: #ef4444; --info: #3b82f6;
        }
        body { font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); overflow-x: hidden; }

        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 3rem 0; max-width: 1400px; }
        .plan-card { background: rgba(30, 41, 59, 0.95); border: 2px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 2rem; text-align: center; transition: all 0.3s; position: relative; }
        .plan-card:hover { transform: translateY(-10px); border-color: var(--primary); box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3); }
        .plan-card.popular { border-color: var(--accent); background: linear-gradient(135deg, rgba(236, 72, 153, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%); }
        .plan-card.popular::before { content: '⭐ MÁS POPULAR'; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: var(--accent); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 700; font-size: 0.85rem; }
        .plan-name { font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; }
        .plan-price { font-size: 3rem; font-weight: 900; color: var(--primary); margin: 1rem 0; }
        .plan-price small { font-size: 1rem; color: var(--text-muted); }
        .plan-features { list-style: none; text-align: left; margin: 2rem 0; }
        .plan-features li { padding: 0.75rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; gap: 0.75rem; }
        .plan-features li i { color: var(--success); font-size: 1.2rem; }
        .btn-select-plan { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 1rem 2rem; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; width: 100%; font-size: 1.1rem; transition: all 0.3s; }
        .btn-select-plan:hover { transform: scale(1.05); box-shadow: 0 10px 30px rgba(99, 102, 241, 0.5); }
        .trial-info { background: rgba(16, 185, 129, 0.1); border: 2px solid var(--success); border-radius: 12px; padding: 1.5rem; margin: 2rem 0; text-align: center; }
        .trial-info h3 { color: var(--success); font-size: 1.5rem; margin-bottom: 0.5rem; }

        /* Resto de estilos anteriores */
        .animated-bg { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; overflow: hidden; }
        .animated-bg::before, .animated-bg::after { content: ''; position: absolute; width: 800px; height: 800px; border-radius: 50%; filter: blur(120px); opacity: 0.15; animation: float 25s infinite ease-in-out; }
        .animated-bg::before { background: linear-gradient(135deg, var(--primary), var(--secondary)); top: -300px; left: -300px; }
        .animated-bg::after { background: linear-gradient(135deg, var(--accent), var(--secondary)); bottom: -300px; right: -300px; animation-delay: 12s; }
        @keyframes float { 0%, 100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(100px, -80px) scale(1.1); } 66% { transform: translate(-60px, 60px) scale(0.9); } }

        nav { position: fixed; top: 0; width: 100%; padding: 1.5rem 5%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.08); z-index: 1000; transition: all 0.3s ease; }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 1rem; font-size: 1.8rem; font-weight: 900; text-decoration: none; color: var(--text); }
        .logo i { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 0.6rem; border-radius: 12px; color: white; box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4); }

        .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; z-index: 1; padding: 8rem 5% 5rem; }
        .hero-content { max-width: 1400px; text-align: center; }
        .hero h1 { font-size: 4.5rem; font-weight: 900; margin-bottom: 1.5rem; background: linear-gradient(135deg, white 0%, var(--text-muted) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { font-size: 1.5rem; color: var(--text-muted); margin-bottom: 3rem; max-width: 800px; margin-left: auto; margin-right: auto; }

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

        .btn { padding: 1rem 2.5rem; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 1rem; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; box-shadow: 0 5px 20px rgba(99, 102, 241, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(99, 102, 241, 0.6); }

        .section { background: rgba(30, 41, 59, 0.5); padding: 5rem 5%; position: relative; z-index: 1; }
        .section h2 { text-align: center; font-size: 3rem; font-weight: 800; margin-bottom: 1rem; }
        .section p { text-align: center; color: var(--text-muted); max-width: 600px; margin: 0 auto 3rem; font-size: 1.2rem; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px); z-index: 2000; align-items: center; justify-content: center; animation: fadeIn 0.3s; padding: 2rem; overflow-y: auto; }
        .modal.active { display: flex; }
        .modal-content { background: var(--dark-light); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; max-width: 900px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 3rem; position: relative; animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); }
        .close-modal { position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(255, 255, 255, 0.1); border: none; color: var(--text); font-size: 1.5rem; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: all 0.3s; }
        .close-modal:hover { background: var(--error); transform: rotate(90deg); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

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
    <div style="position:fixed;top:80px;right:20px;z-index:10000;padding:1.5rem 2rem;background:<?php echo $message_type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : ($message_type === 'warning' ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #ef4444, #dc2626)'); ?>;color:white;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.3);font-weight:600;display:flex;align-items:center;gap:1rem;max-width:500px;">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : ($message_type === 'warning' ? 'fa-clock' : 'fa-times-circle'); ?>" style="font-size:1.5rem;"></i>
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
                <button class="btn btn-primary" onclick="openModal('loginModal')">Iniciar Sesión</button>
                <button class="btn btn-primary" onclick="scrollToPlanes()">Ver Planes</button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>CONECTA ERP</h1>
            <p>Sistema ERP Empresarial Completo - Competimos con SAP y Softland</p>
            <button class="btn btn-primary" style="font-size:1.2rem;padding:1.5rem 3rem;" onclick="scrollToPlanes()">
                Ver Planes y Precios <i class="fas fa-arrow-down" style="margin-left:0.5rem;"></i>
            </button>
            <div class="trial-info" style="max-width:600px;margin:3rem auto;">
                <h3>🎁 14 Días de Prueba GRATIS</h3>
                <p>Todos los usuarios nuevos obtienen 14 días de acceso completo</p>
                <p style="font-size:0.9rem;margin-top:1rem;">📧 <strong>auditorexchile@gmail.com</strong> es quien aprueba tu acceso</p>
            </div>
        </div>
    </section>

    <!-- Sección de Planes -->
    <section class="section" id="planes">
        <h2>Planes y Precios</h2>
        <p>Elige el plan perfecto para tu empresa. Todos incluyen 14 días de prueba gratis.</p>

        <div class="plans-grid" style="margin:0 auto;">
            <?php if (empty($planes)): ?>
            <!-- Planes por defecto si no hay conexión a BD -->
            <div class="plan-card">
                <div class="plan-name">Básico</div>
                <div class="plan-price">$49<small>/mes</small></div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> Hasta 5 usuarios</li>
                    <li><i class="fas fa-check"></i> 1 empresa</li>
                    <li><i class="fas fa-check"></i> 10GB almacenamiento</li>
                    <li><i class="fas fa-check"></i> Soporte básico</li>
                    <li><i class="fas fa-check"></i> Módulos FI, CO, SD</li>
                </ul>
                <button class="btn-select-plan" onclick="openRegisterModal('BASICO')">Seleccionar Plan</button>
            </div>

            <div class="plan-card popular">
                <div class="plan-name">Profesional</div>
                <div class="plan-price">$99<small>/mes</small></div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> Hasta 15 usuarios</li>
                    <li><i class="fas fa-check"></i> 3 empresas</li>
                    <li><i class="fas fa-check"></i> 50GB almacenamiento</li>
                    <li><i class="fas fa-check"></i> Soporte prioritario</li>
                    <li><i class="fas fa-check"></i> Todos los módulos</li>
                </ul>
                <button class="btn-select-plan" onclick="openRegisterModal('PROFESIONAL')">Seleccionar Plan</button>
            </div>

            <div class="plan-card">
                <div class="plan-name">Empresarial</div>
                <div class="plan-price">$199<small>/mes</small></div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> Hasta 50 usuarios</li>
                    <li><i class="fas fa-check"></i> 10 empresas</li>
                    <li><i class="fas fa-check"></i> 200GB almacenamiento</li>
                    <li><i class="fas fa-check"></i> Soporte VIP</li>
                    <li><i class="fas fa-check"></i> Personalización</li>
                </ul>
                <button class="btn-select-plan" onclick="openRegisterModal('EMPRESARIAL')">Seleccionar Plan</button>
            </div>
            <?php else: ?>
            <!-- Planes desde BD -->
            <?php foreach ($planes as $plan): ?>
            <div class="plan-card <?php echo $plan['es_popular'] ? 'popular' : ''; ?>">
                <div class="plan-name"><?php echo htmlspecialchars($plan['nombre_es']); ?></div>
                <div class="plan-price">$<?php echo number_format($plan['precio_mensual_usd'], 0); ?><small>/mes</small></div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> <?php echo $plan['usuarios_maximos'] ?? 'Ilimitados'; ?> usuarios</li>
                    <li><i class="fas fa-check"></i> <?php echo $plan['empresas_maximas'] ?? 'Ilimitadas'; ?> empresas</li>
                    <li><i class="fas fa-check"></i> <?php echo $plan['almacenamiento_gb']; ?>GB almacenamiento</li>
                    <li><i class="fas fa-check"></i> Soporte <?php echo $plan['soporte_prioridad']; ?></li>
                </ul>
                <button class="btn-select-plan" onclick="openRegisterModal('<?php echo $plan['codigo']; ?>')">Seleccionar Plan</button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal Login (igual que antes) -->
    <div id="loginModal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <button class="close-modal" onclick="closeModal('loginModal')">&times;</button>
            <h2 style="font-size:2rem;margin-bottom:2rem;text-align:center;">Iniciar Sesión</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1rem;">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Registro (con campo de plan) -->
    <?php include __DIR__ . '/includes/modal_registro_completo.php'; ?>

    <script src="/assets/js/tax-id-validator.js"></script>
    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }

        function scrollToPlanes() {
            document.getElementById('planes').scrollIntoView({ behavior: 'smooth' });
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openRegisterModal(planCode) {
            document.getElementById('selected_plan').value = planCode;
            openModal('registerModal');
        }

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
