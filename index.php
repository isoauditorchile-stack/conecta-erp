<?php
/**
 * CONECTA ERP - Página Principal Profesional
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/i18n.php';
initSession();

// Traducciones para la landing page
$lang = currentLanguage();
$t = [
    'es' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => 'Sistema ERP Empresarial Completo - Somos el Mejor ERP del Mundo',
        'hero_description' => '14 Módulos • 106 Submódulos • Multi-país • Multi-moneda • Multi-idioma',
        'view_plans' => 'Ver Planes',
        'register_free' => 'Registrarse Gratis',
        'register' => 'Registro',
        'login' => 'Iniciar Sesión',
        'plans_title' => 'Planes y Módulos',
        'plans_subtitle' => 'Selecciona el plan que mejor se adapte a tu empresa. Todos los planes incluyen Multi-usuario, Multi-empresa, Multi-moneda y Multi-idioma.',
        'per_month' => '/mes',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Custom',
        'most_popular' => 'Más Popular',
        'ideal_for_small' => 'Ideal para pequeñas empresas',
        'for_growing' => 'Para empresas en crecimiento',
        'for_large' => 'Para grandes empresas',
        'custom_needs' => 'Adaptado a tus necesidades',
        'start_free' => 'Comenzar Gratis',
        'start_now' => 'Comenzar Ahora',
        'contact_sales' => 'Contactar Ventas',
        'personalized' => 'Personalizado',
    ],
    'en' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => 'Complete Enterprise ERP System - We Are the Best ERP in the World',
        'hero_description' => '14 Modules • 106 Submodules • Multi-country • Multi-currency • Multi-language',
        'view_plans' => 'View Plans',
        'register_free' => 'Register Free',
        'register' => 'Register',
        'login' => 'Login',
        'plans_title' => 'Plans and Modules',
        'plans_subtitle' => 'Choose the plan that best fits your company. All plans include Multi-user, Multi-company, Multi-currency and Multi-language.',
        'per_month' => '/month',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Custom',
        'most_popular' => 'Most Popular',
        'ideal_for_small' => 'Ideal for small businesses',
        'for_growing' => 'For growing companies',
        'for_large' => 'For large enterprises',
        'custom_needs' => 'Adapted to your needs',
        'start_free' => 'Start Free',
        'start_now' => 'Start Now',
        'contact_sales' => 'Contact Sales',
        'personalized' => 'Personalized',
    ],
    'pt' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => 'Sistema ERP Empresarial Completo - Somos o Melhor ERP do Mundo',
        'hero_description' => '14 Módulos • 106 Submódulos • Multi-país • Multi-moeda • Multi-idioma',
        'view_plans' => 'Ver Planos',
        'register_free' => 'Registrar Grátis',
        'register' => 'Registro',
        'login' => 'Entrar',
        'plans_title' => 'Planos e Módulos',
        'plans_subtitle' => 'Escolha o plano que melhor se adapta à sua empresa. Todos os planos incluem Multi-usuário, Multi-empresa, Multi-moeda e Multi-idioma.',
        'per_month' => '/mês',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Personalizado',
        'most_popular' => 'Mais Popular',
        'ideal_for_small' => 'Ideal para pequenas empresas',
        'for_growing' => 'Para empresas em crescimento',
        'for_large' => 'Para grandes empresas',
        'custom_needs' => 'Adaptado às suas necessidades',
        'start_free' => 'Começar Grátis',
        'start_now' => 'Começar Agora',
        'contact_sales' => 'Contatar Vendas',
        'personalized' => 'Personalizado',
    ],
    'fr' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => 'Système ERP d\'Entreprise Complet - Nous sommes le Meilleur ERP au Monde',
        'hero_description' => '14 Modules • 106 Sous-modules • Multi-pays • Multi-devise • Multi-langue',
        'view_plans' => 'Voir les Plans',
        'register_free' => 'S\'inscrire Gratuitement',
        'register' => 'Inscription',
        'login' => 'Connexion',
        'plans_title' => 'Plans et Modules',
        'plans_subtitle' => 'Choisissez le plan qui convient le mieux à votre entreprise. Tous les plans incluent Multi-utilisateur, Multi-entreprise, Multi-devise et Multi-langue.',
        'per_month' => '/mois',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Personnalisé',
        'most_popular' => 'Le Plus Populaire',
        'ideal_for_small' => 'Idéal pour les petites entreprises',
        'for_growing' => 'Pour les entreprises en croissance',
        'for_large' => 'Pour les grandes entreprises',
        'custom_needs' => 'Adapté à vos besoins',
        'start_free' => 'Commencer Gratuitement',
        'start_now' => 'Commencer Maintenant',
        'contact_sales' => 'Contacter les Ventes',
        'personalized' => 'Personnalisé',
    ],
    'de' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => 'Vollständiges Unternehmens-ERP-System - Wir sind das Beste ERP der Welt',
        'hero_description' => '14 Module • 106 Submodule • Multi-Land • Multi-Währung • Multi-Sprache',
        'view_plans' => 'Pläne Ansehen',
        'register_free' => 'Kostenlos Registrieren',
        'register' => 'Registrieren',
        'login' => 'Anmelden',
        'plans_title' => 'Pläne und Module',
        'plans_subtitle' => 'Wählen Sie den Plan, der am besten zu Ihrem Unternehmen passt. Alle Pläne beinhalten Multi-Benutzer, Multi-Unternehmen, Multi-Währung und Multi-Sprache.',
        'per_month' => '/Monat',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Individuell',
        'most_popular' => 'Am Beliebtesten',
        'ideal_for_small' => 'Ideal für kleine Unternehmen',
        'for_growing' => 'Für wachsende Unternehmen',
        'for_large' => 'Für große Unternehmen',
        'custom_needs' => 'Angepasst an Ihre Bedürfnisse',
        'start_free' => 'Kostenlos Starten',
        'start_now' => 'Jetzt Starten',
        'contact_sales' => 'Vertrieb Kontaktieren',
        'personalized' => 'Individualisiert',
    ],
    'it' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => 'Sistema ERP Aziendale Completo - Siamo il Miglior ERP al Mondo',
        'hero_description' => '14 Moduli • 106 Sottomoduli • Multi-paese • Multi-valuta • Multi-lingua',
        'view_plans' => 'Visualizza Piani',
        'register_free' => 'Registrati Gratis',
        'register' => 'Registrazione',
        'login' => 'Accedi',
        'plans_title' => 'Piani e Moduli',
        'plans_subtitle' => 'Scegli il piano più adatto alla tua azienda. Tutti i piani includono Multi-utente, Multi-azienda, Multi-valuta e Multi-lingua.',
        'per_month' => '/mese',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Personalizzato',
        'most_popular' => 'Più Popolare',
        'ideal_for_small' => 'Ideale per piccole imprese',
        'for_growing' => 'Per aziende in crescita',
        'for_large' => 'Per grandi aziende',
        'custom_needs' => 'Adattato alle tue esigenze',
        'start_free' => 'Inizia Gratis',
        'start_now' => 'Inizia Ora',
        'contact_sales' => 'Contatta Vendite',
        'personalized' => 'Personalizzato',
    ],
    'ru' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => 'Полная корпоративная ERP-система - Мы лучшая ERP в мире',
        'hero_description' => '14 модулей • 106 подмодулей • Мультистрана • Мультивалюта • Мультиязык',
        'view_plans' => 'Посмотреть Планы',
        'register_free' => 'Зарегистрироваться Бесплатно',
        'register' => 'Регистрация',
        'login' => 'Войти',
        'plans_title' => 'Планы и Модули',
        'plans_subtitle' => 'Выберите план, который лучше всего подходит вашей компании. Все планы включают Мультипользователь, Мультикомпания, Мультивалюта и Мультиязык.',
        'per_month' => '/месяц',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Индивидуальный',
        'most_popular' => 'Самый Популярный',
        'ideal_for_small' => 'Идеально для малого бизнеса',
        'for_growing' => 'Для растущих компаний',
        'for_large' => 'Для крупных предприятий',
        'custom_needs' => 'Адаптировано под ваши нужды',
        'start_free' => 'Начать Бесплатно',
        'start_now' => 'Начать Сейчас',
        'contact_sales' => 'Связаться с Отделом Продаж',
        'personalized' => 'Персонализированный',
    ],
    'zh' => [
        'hero_title' => '🚀 CONECTA ERP',
        'hero_subtitle' => '完整的企业ERP系统 - 我们是世界上最好的ERP',
        'hero_description' => '14个模块 • 106个子模块 • 多国家 • 多货币 • 多语言',
        'view_plans' => '查看计划',
        'register_free' => '免费注册',
        'register' => '注册',
        'login' => '登录',
        'plans_title' => '计划和模块',
        'plans_subtitle' => '选择最适合您公司的计划。所有计划包括多用户、多公司、多货币和多语言。',
        'per_month' => '/月',
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => '定制',
        'most_popular' => '最受欢迎',
        'ideal_for_small' => '适合小型企业',
        'for_growing' => '适合成长型企业',
        'for_large' => '适合大型企业',
        'custom_needs' => '适应您的需求',
        'start_free' => '免费开始',
        'start_now' => '立即开始',
        'contact_sales' => '联系销售',
        'personalized' => '个性化',
    ],
];
$tr = $t[$lang] ?? $t['es']; // Fallback to Spanish

// Si ya tiene sesión, redirigir
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['email'] === 'auditorexchile@gmail.com') {
        header('Location: /admin/panel_super_admin.php');
    } else {
        header('Location: /user/dashboard_user.php');
    }
    exit;
}

$error = '';
$success = '';

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $company_name = trim($_POST['company_name']);
    $phone = trim($_POST['phone']);
    $country = $_POST['country'];
    $tax_id = trim($_POST['tax_id']);

    try {
        $db = Database::getInstance();

        // Verificar si el email ya existe
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $error = 'Este email ya está registrado';
        } else {
            $is_super_admin = ($email === 'auditorexchile@gmail.com');
            $status = $is_super_admin ? 'active' : 'pending_approval';

            $db->getConnection()->beginTransaction();

            // Crear empresa (sin owner_user_id por ahora)
            $company_id = $db->insert(
                "INSERT INTO companies (company_name, legal_name, tax_id, country_id, owner_user_id, created_at) VALUES (?, ?, ?, ?, NULL, NOW())",
                [$company_name, $company_name, $tax_id, 1]
            );

            // Crear usuario
            $user_id = $db->insert(
                "INSERT INTO users (firstname, lastname, email, username, password, company_id, phone, country, tax_id, status, is_admin, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $firstname,
                    $lastname,
                    $email,
                    $email,
                    password_hash($password, PASSWORD_BCRYPT),
                    $company_id,
                    $phone,
                    $country,
                    $tax_id,
                    $status,
                    $is_super_admin ? 1 : 0
                ]
            );

            // Actualizar empresa con el owner_user_id
            $db->update(
                "UPDATE companies SET owner_user_id = ? WHERE id = ?",
                [$user_id, $company_id]
            );

            $db->getConnection()->commit();

            // Mostrar mensaje de éxito (NO redireccionar automáticamente)
            if ($is_super_admin) {
                $success = '¡Registro exitoso! Tu cuenta de super administrador ha sido creada. Puedes <a href="/login.php">iniciar sesión aquí</a>.';
            } else {
                $success = '¡Registro exitoso! Tu cuenta está pendiente de aprobación. Te notificaremos por email.';
            }
        }
    } catch (Exception $e) {
        if ($db && $db->getConnection()->inTransaction()) {
            $db->getConnection()->rollBack();
        }
        $error = 'Error al registrar. Por favor, intenta nuevamente.';
        error_log("Registration error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONECTA ERP - Sistema ERP Profesional | Competimos con SAP y Softland</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- RUT Validator CSS -->
    <link rel="stylesheet" href="/assets/css/rut-validator.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
        }

        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --accent: #f093fb;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 5%;
            z-index: 1000;
            transition: all 0.3s;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
        }

        .logo i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 0.5rem;
            border-radius: 10px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: white;
        }

        .nav-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-login {
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-login:hover {
            border-color: var(--primary);
            background: rgba(102, 126, 234, 0.1);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .lang-selector {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .lang-selector:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
        }

        .lang-selector:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .lang-selector option {
            background: #1e293b;
            color: white;
            padding: 0.5rem;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8rem 5% 4rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.2), transparent);
            top: -200px;
            right: -200px;
            animation: pulse 8s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.7; }
        }

        .hero-content {
            max-width: 1200px;
            text-align: center;
            z-index: 1;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, white, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.5rem;
            color: #cbd5e1;
            margin-bottom: 3rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary);
        }

        /* Sections */
        .section {
            padding: 6rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            color: #cbd5e1;
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 4rem;
        }

        /* Registration Section */
        .registration-section {
            background: rgba(30, 41, 59, 0.5);
            padding: 6rem 5%;
        }

        .registration-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #f1f5f9;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--error);
            color: #fecaca;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--success);
            color: #a7f3d0;
        }

        /* Footer */
        .footer {
            background: rgba(15, 23, 42, 0.95);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 4rem 5% 2rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 0.75rem;
        }

        .footer-section a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        /* Pricing Cards */
        .pricing-card {
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
        }

        .pricing-card-featured {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
            border: 2px solid var(--primary);
            transform: scale(1.05);
        }

        .pricing-card-featured:hover {
            transform: scale(1.08) translateY(-10px);
        }

        .pricing-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .pricing-header h3 {
            font-size: 1.8rem;
            margin: 1rem 0;
            color: white;
        }

        .pricing-price {
            margin: 1rem 0;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .pricing-features li {
            padding: 0.75rem 0;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .pricing-features li i {
            font-size: 1.2rem;
            color: var(--success);
        }

        .pricing-features li i.fa-times-circle {
            color: #64748b;
        }

        /* Module Cards */
        .module-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .module-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            transform: translateY(-5px);
        }

        .module-card h4 {
            color: white;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .module-card h4 i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .module-card p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: #1e293b;
            margin: 3% auto;
            padding: 0;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
            animation: slideDown 0.4s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
            color: white;
        }

        .modal-header p {
            margin: 0;
            opacity: 0.95;
            font-size: 0.95rem;
        }

        .modal-header a {
            color: white;
            text-decoration: underline;
        }

        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1;
            transition: all 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal .registration-container {
            padding: 2rem;
            background: #1e293b;
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1.2rem; }
            .nav-links { display: none; }
            .pricing-card-featured {
                transform: scale(1);
            }
            .pricing-card-featured:hover {
                transform: translateY(-10px);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="logo">
                <i class="fas fa-network-wired"></i>
                <span>CONECTA ERP</span>
            </a>
            <div class="nav-links">
                <!-- Language Selector -->
                <select class="lang-selector" onchange="changeLanguage(this.value)">
                    <option value="es" <?php echo currentLanguage() === 'es' ? 'selected' : ''; ?>>🇪🇸 Español</option>
                    <option value="en" <?php echo currentLanguage() === 'en' ? 'selected' : ''; ?>>🇬🇧 English</option>
                    <option value="pt" <?php echo currentLanguage() === 'pt' ? 'selected' : ''; ?>>🇵🇹 Português</option>
                    <option value="fr" <?php echo currentLanguage() === 'fr' ? 'selected' : ''; ?>>🇫🇷 Français</option>
                    <option value="de" <?php echo currentLanguage() === 'de' ? 'selected' : ''; ?>>🇩🇪 Deutsch</option>
                    <option value="it" <?php echo currentLanguage() === 'it' ? 'selected' : ''; ?>>🇮🇹 Italiano</option>
                    <option value="ru" <?php echo currentLanguage() === 'ru' ? 'selected' : ''; ?>>🇷🇺 Русский</option>
                    <option value="zh" <?php echo currentLanguage() === 'zh' ? 'selected' : ''; ?>>🇨🇳 中文</option>
                </select>
                <a href="javascript:void(0)" onclick="openModal()"><?php echo $tr['register']; ?></a>
                <a href="login.php" class="nav-btn btn-login"><?php echo $tr['login']; ?></a>
                <a href="javascript:void(0)" onclick="openModal()" class="nav-btn btn-register"><?php echo $tr['register']; ?></a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1><?php echo $tr['hero_title']; ?></h1>
            <p><?php echo $tr['hero_subtitle']; ?></p>
            <p style="font-size: 1.1rem; margin-bottom: 2rem;"><?php echo $tr['hero_description']; ?></p>
            <div class="hero-buttons">
                <a href="#planes" class="btn btn-primary">
                    <?php echo $tr['view_plans']; ?> <i class="fas fa-arrow-right"></i>
                </a>
                <a href="javascript:void(0)" onclick="openModal()" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> <?php echo $tr['register_free']; ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Pricing Plans Section -->
    <section id="planes" style="padding: 6rem 5%; background: #1e293b;">
        <div style="max-width: 1400px; margin: 0 auto;">
            <div class="section-title" style="color: white;"><?php echo $tr['plans_title']; ?></div>
            <div class="section-subtitle" style="color: #94a3b8; max-width: 800px; margin: 0 auto 4rem;">
                <?php echo $tr['plans_subtitle']; ?>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <!-- Plan Starter -->
                <div class="pricing-card">
                    <div class="pricing-header">
                        <i class="fas fa-rocket" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"></i>
                        <h3><?php echo $tr['starter']; ?></h3>
                        <div class="pricing-price">
                            <span style="font-size: 3rem; font-weight: 800;">$299</span>
                            <span style="font-size: 1.2rem; color: #94a3b8;"><?php echo $tr['per_month']; ?></span>
                        </div>
                        <p style="color: #94a3b8; margin-top: 1rem;"><?php echo $tr['ideal_for_small']; ?></p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check-circle"></i> <strong>Administración Central</strong></li>
                        <li><i class="fas fa-check-circle"></i> <strong>Gestión de Entidades</strong> (Clientes, Proveedores)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Finanzas Básicas</strong> (Contabilidad, Bancos)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Ventas</strong> (CRM, Cotizaciones, POS)</li>
                        <li><i class="fas fa-check-circle"></i> Dashboard principal</li>
                        <li style="color: #64748b;"><i class="fas fa-times-circle"></i> Materiales e Inventario</li>
                        <li style="color: #64748b;"><i class="fas fa-times-circle"></i> Producción</li>
                        <li style="color: #64748b;"><i class="fas fa-times-circle"></i> RRHH avanzado</li>
                    </ul>
                    <a href="javascript:void(0)" onclick="openModal()" class="btn btn-secondary" style="width: 100%; justify-content: center; margin-top: 2rem;">
                        <?php echo $tr['start_free']; ?>
                    </a>
                </div>

                <!-- Plan Professional -->
                <div class="pricing-card pricing-card-featured">
                    <div class="pricing-badge"><?php echo $tr['most_popular']; ?></div>
                    <div class="pricing-header">
                        <i class="fas fa-briefcase" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"></i>
                        <h3><?php echo $tr['professional']; ?></h3>
                        <div class="pricing-price">
                            <span style="font-size: 3rem; font-weight: 800;">$699</span>
                            <span style="font-size: 1.2rem; color: #94a3b8;"><?php echo $tr['per_month']; ?></span>
                        </div>
                        <p style="color: #94a3b8; margin-top: 1rem;"><?php echo $tr['for_growing']; ?></p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check-circle"></i> <strong>Todo en Starter</strong></li>
                        <li><i class="fas fa-check-circle"></i> <strong>Materiales</strong> (Inventario, Compras, Warehouses)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>RRHH Completo</strong> (Nómina, Previred, Relojes)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Controlling</strong> (Costos, Presupuestos)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>CRM Avanzado</strong></li>
                        <li><i class="fas fa-check-circle"></i> <strong>Integración SII</strong> (DTEs, Facturación electrónica)</li>
                        <li style="color: #64748b;"><i class="fas fa-times-circle"></i> Producción</li>
                        <li style="color: #64748b;"><i class="fas fa-times-circle"></i> SCM</li>
                    </ul>
                    <a href="javascript:void(0)" onclick="openModal()" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 2rem;">
                        <?php echo $tr['start_now']; ?>
                    </a>
                </div>

                <!-- Plan Enterprise -->
                <div class="pricing-card">
                    <div class="pricing-header">
                        <i class="fas fa-building" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"></i>
                        <h3><?php echo $tr['enterprise']; ?></h3>
                        <div class="pricing-price">
                            <span style="font-size: 3rem; font-weight: 800;">$1,499</span>
                            <span style="font-size: 1.2rem; color: #94a3b8;"><?php echo $tr['per_month']; ?></span>
                        </div>
                        <p style="color: #94a3b8; margin-top: 1rem;"><?php echo $tr['for_large']; ?></p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check-circle"></i> <strong>Todo en Professional</strong></li>
                        <li><i class="fas fa-check-circle"></i> <strong>Producción</strong> (MRP, BOM, Ruteo, QA)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>SCM</strong> (Supply Chain Management)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Fidelización</strong> (Loyalty, Promociones)</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Business Intelligence</strong> (15 submódulos)</li>
                        <li><i class="fas fa-check-circle"></i> API y Webhooks</li>
                        <li><i class="fas fa-check-circle"></i> Soporte prioritario 24/7</li>
                        <li><i class="fas fa-check-circle"></i> Usuarios ilimitados</li>
                    </ul>
                    <a href="javascript:void(0)" onclick="openModal()" class="btn btn-secondary" style="width: 100%; justify-content: center; margin-top: 2rem;">
                        <?php echo $tr['start_now']; ?>
                    </a>
                </div>

                <!-- Plan Custom -->
                <div class="pricing-card">
                    <div class="pricing-header">
                        <i class="fas fa-crown" style="font-size: 2.5rem; color: #f59e0b; margin-bottom: 1rem;"></i>
                        <h3><?php echo $tr['custom']; ?></h3>
                        <div class="pricing-price">
                            <span style="font-size: 2rem; font-weight: 800;"><?php echo $tr['personalized']; ?></span>
                        </div>
                        <p style="color: #94a3b8; margin-top: 1rem;"><?php echo $tr['custom_needs']; ?></p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check-circle"></i> <strong>Todos los módulos</strong></li>
                        <li><i class="fas fa-check-circle"></i> Módulos personalizados</li>
                        <li><i class="fas fa-check-circle"></i> Integraciones a medida</li>
                        <li><i class="fas fa-check-circle"></i> Instalación on-premise</li>
                        <li><i class="fas fa-check-circle"></i> Capacitación personalizada</li>
                        <li><i class="fas fa-check-circle"></i> Soporte dedicado</li>
                        <li><i class="fas fa-check-circle"></i> SLA garantizado</li>
                        <li><i class="fas fa-check-circle"></i> Account Manager</li>
                    </ul>
                    <a href="javascript:void(0)" onclick="openModal()" class="btn btn-secondary" style="width: 100%; justify-content: center; margin-top: 2rem; background: #f59e0b; border-color: #f59e0b;">
                        <?php echo $tr['contact_sales']; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Modal (Hidden by default) -->
    <div id="registroModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2>Registro de Cuenta</h2>
                <p>Completa el formulario para crear tu cuenta. Si ya tienes una cuenta, <a href="login.php" style="color: var(--primary);">inicia sesión aquí</a>.</p>
            </div>

            <div class="registration-container">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="firstname" required placeholder="Juan">
                </div>

                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="lastname" required placeholder="Pérez">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="juan@empresa.com">
                </div>

                <div class="form-group">
                    <label>Contraseña</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
                        <button type="button" onclick="togglePassword()" style="position: absolute; right: 70px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary); cursor: pointer; font-size: 1.2rem;">
                            <i id="eyeIcon" class="fas fa-eye"></i>
                        </button>
                        <button type="button" onclick="generatePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: var(--primary); border: none; color: white; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600;">
                            <i class="fas fa-key"></i> Generar
                        </button>
                    </div>
                    <small style="color: #94a3b8; font-size: 0.85rem; margin-top: 0.5rem; display: block;">Mínimo 6 caracteres. Se recomienda usar el generador.</small>
                </div>

                <div class="form-group">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="company_name" required placeholder="Mi Empresa S.A.">
                </div>

                <div class="form-group">
                    <label>País</label>
                    <select name="country" id="country" required onchange="updateTaxIdPlaceholder()">
                        <option value="CL">🇨🇱 Chile</option>
                        <option value="AR">🇦🇷 Argentina</option>
                        <option value="PE">🇵🇪 Perú</option>
                        <option value="CO">🇨🇴 Colombia</option>
                        <option value="MX">🇲🇽 México</option>
                        <option value="BR">🇧🇷 Brasil</option>
                        <option value="US">🇺🇸 Estados Unidos</option>
                        <option value="ES">🇪🇸 España</option>
                    </select>
                </div>

                <div class="form-group">
                    <label id="taxIdLabel">RUT Empresa</label>
                    <input type="text" id="tax_id" name="tax_id" required placeholder="15.895.771-k" maxlength="20">
                    <small id="taxIdHint" style="color: #94a3b8; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                        Formato: XX.XXX.XXX-X (ej: 15.895.771-k)
                    </small>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="phone" required placeholder="+56 9 1234 5678">
                </div>

                <button type="submit" name="register" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Crear Cuenta <i class="fas fa-arrow-right"></i>
                </button>

                <p style="text-align: center; margin-top: 1.5rem; color: #94a3b8; font-size: 0.9rem;">
                    Al registrarte, aceptas nuestros <a href="#" style="color: var(--primary);">Términos y Condiciones</a>
                </p>
            </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3><i class="fas fa-network-wired"></i> CONECTA ERP</h3>
                <p style="color: #94a3b8; margin-bottom: 1rem;">Sistema ERP profesional para empresas modernas. Compite con SAP y Softland.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h3>Producto</h3>
                <ul>
                    <li><a href="#">Características</a></li>
                    <li><a href="#">Módulos</a></li>
                    <li><a href="#">Precios</a></li>
                    <li><a href="#">Demo</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Soporte</h3>
                <ul>
                    <li><a href="#">Documentación</a></li>
                    <li><a href="#">Centro de Ayuda</a></li>
                    <li><a href="#">API</a></li>
                    <li><a href="#">Contacto</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Empresa</h3>
                <ul>
                    <li><a href="#">Nosotros</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Casos de Éxito</a></li>
                    <li><a href="#">Trabaja con Nosotros</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 CONECTA ERP. Todos los derechos reservados. | Sistema de Producción v2.0.0</p>
            <p style="margin-top: 0.5rem;">Admin: auditorexchile@gmail.com</p>
        </div>
    </footer>

    <!-- RUT Validator JS Library -->
    <script src="/assets/js/rut-validator.js"></script>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('registroModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeModal() {
            document.getElementById('registroModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('registroModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        // Auto-open modal if there's an error or success message
        <?php if ($error || $success): ?>
        window.addEventListener('DOMContentLoaded', function() {
            openModal();
        });
        <?php endif; ?>

        // Language Selector
        function changeLanguage(lang) {
            // Redirect to same page with language parameter
            window.location.href = '?lang=' + lang;
        }

        function generatePassword() {
            const length = 16;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
            let password = "";

            // Asegurar al menos un carácter de cada tipo
            const lower = "abcdefghijklmnopqrstuvwxyz";
            const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            const numbers = "0123456789";
            const special = "!@#$%^&*()_+-=";

            password += lower[Math.floor(Math.random() * lower.length)];
            password += upper[Math.floor(Math.random() * upper.length)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += special[Math.floor(Math.random() * special.length)];

            // Completar el resto de la contraseña
            for (let i = password.length; i < length; i++) {
                password += charset[Math.floor(Math.random() * charset.length)];
            }

            // Mezclar los caracteres
            password = password.split('').sort(() => Math.random() - 0.5).join('');

            // Establecer la contraseña y mostrarla
            const passwordInput = document.getElementById('password');
            passwordInput.type = 'text';
            passwordInput.value = password;

            // Cambiar icono a visible
            document.getElementById('eyeIcon').classList.remove('fa-eye');
            document.getElementById('eyeIcon').classList.add('fa-eye-slash');

            // Copiar al portapapeles
            navigator.clipboard.writeText(password).then(() => {
                // Mostrar notificación
                const notification = document.createElement('div');
                notification.textContent = '✓ Contraseña generada y copiada al portapapeles';
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, var(--success), #059669); color: white; padding: 1rem 2rem; border-radius: 10px; z-index: 10000; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.3);';
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.transition = 'opacity 0.5s';
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 500);
                }, 3000);
            });
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Initialize RUT Validator (now using global library)
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize RUT validation on the tax_id input
            RUTValidator.init('#tax_id', '#country');
        });
    </script>
</body>
</html>
