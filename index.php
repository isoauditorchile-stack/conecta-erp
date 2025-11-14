<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONECTA ERP - Sistema ERP Empresarial Completo</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Colores principales */
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #3b82f6;
            --secondary-color: #7c3aed;
            --secondary-dark: #6d28d9;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;

            /* Colores de fondo */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-dark: #0f172a;
            --bg-gradient-start: #667eea;
            --bg-gradient-end: #764ba2;

            /* Colores de texto */
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-light: #94a3b8;
            --text-white: #ffffff;

            /* Sombras */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

            /* Bordes */
            --border-radius-sm: 0.375rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 0.75rem;
            --border-radius-xl: 1rem;
            --border-radius-2xl: 1.5rem;

            /* Transiciones */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            line-height: 1.2;
        }

        /* =========================
           NAVEGACIÓN
        ========================= */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all var(--transition-base);
        }

        .navbar-custom.scrolled {
            box-shadow: var(--shadow-lg);
            padding: 0.5rem 0;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand i {
            font-size: 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            color: var(--text-primary);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-md);
            transition: all var(--transition-fast);
        }

        .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(37, 99, 235, 0.1);
        }

        .btn-login {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            font-weight: 600;
            transition: all var(--transition-base);
        }

        .btn-login:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            font-weight: 600;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* =========================
           HERO SECTION
        ========================= */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 180px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .hero-description {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            max-width: 1000px;
            margin: 3rem auto 0;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            text-align: center;
            transition: all var(--transition-base);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-hero {
            padding: 1rem 3rem;
            border-radius: var(--border-radius-xl);
            font-size: 1.125rem;
            font-weight: 600;
            transition: all var(--transition-base);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-hero-primary {
            background: white;
            color: var(--primary-color);
            box-shadow: var(--shadow-xl);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.35);
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        /* =========================
           SECCIONES
        ========================= */
        .section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .section-subtitle {
            font-size: 1.25rem;
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* =========================
           CARDS DE CARACTERÍSTICAS
        ========================= */
        .feature-card {
            background: white;
            border-radius: var(--border-radius-2xl);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-2xl);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: var(--border-radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.7;
        }

        /* =========================
           PLANES
        ========================= */
        .plans-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-top: 3rem;
        }

        .plan-card {
            background: white;
            border-radius: var(--border-radius-2xl);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-2xl);
            border-color: var(--primary-color);
        }

        .plan-card.featured {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-xl);
        }

        .plan-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--accent-color), #dc2626);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-md);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .plan-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .plan-price small {
            font-size: 1.25rem;
            font-weight: 400;
            color: var(--text-secondary);
        }

        .plan-period {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .plan-features li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .plan-features li i {
            color: var(--success-color);
            font-size: 1.25rem;
        }

        .btn-plan {
            width: 100%;
            padding: 1rem;
            border-radius: var(--border-radius-lg);
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition-base);
        }

        .btn-plan-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-plan-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-plan-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 2px solid var(--primary-color);
        }

        .btn-plan-secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        /* =========================
           MÓDULOS
        ========================= */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .module-card {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
            cursor: pointer;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .module-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .module-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .module-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .module-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .module-count {
            background: var(--bg-secondary);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-md);
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* =========================
           FOOTER
        ========================= */
        .footer {
            background: var(--bg-dark);
            color: rgba(255, 255, 255, 0.7);
            padding: 60px 0 30px;
        }

        .footer-title {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 3rem;
            padding-top: 2rem;
            text-align: center;
        }

        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 3rem;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .plans-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.25rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .plans-container {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn-hero {
                width: 100%;
            }
        }

        /* =========================
           ANIMACIONES
        ========================= */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- NAVEGACIÓN -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-box-seam-fill"></i>
                CONECTA ERP
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#caracteristicas">Características</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#planes">Planes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#modulos">Módulos</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-login" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-register" href="register.php">
                            <i class="bi bi-person-plus me-1"></i> Registrarse
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="inicio" class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title animate-fade-in-up">
                    CONECTA ERP
                </h1>
                <p class="hero-subtitle animate-fade-in-up">
                    La solución empresarial completa que tu negocio necesita
                </p>
                <p class="hero-description animate-fade-in-up">
                    Sistema ERP profesional y robusto diseñado para empresas modernas.
                    Gestiona todos los aspectos de tu negocio desde una sola plataforma:
                    finanzas, ventas, inventario, producción, recursos humanos y más.
                </p>

                <div class="hero-buttons">
                    <button class="btn-hero btn-hero-primary" onclick="window.location.href='register.php'">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                        Comienza Gratis - 14 días de prueba
                    </button>
                    <button class="btn-hero btn-hero-secondary" onclick="document.getElementById('planes').scrollIntoView({behavior: 'smooth'})">
                        <i class="bi bi-eye-fill"></i>
                        Ver Planes y Precios
                    </button>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-number">14</div>
                        <div class="stat-label">Módulos Principales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">106</div>
                        <div class="stat-label">Submódulos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">9</div>
                        <div class="stat-label">Idiomas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">9</div>
                        <div class="stat-label">Países</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CARACTERÍSTICAS -->
    <section id="caracteristicas" class="section bg-white">
        <div class="container">
            <h2 class="section-title">Características Principales</h2>
            <p class="section-subtitle">
                Un sistema completo y profesional diseñado para impulsar tu negocio
            </p>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-globe"></i>
                        </div>
                        <h3 class="feature-title">Multipaís y Multimoneda</h3>
                        <p class="feature-description">
                            Opera en 9 países diferentes con soporte completo para múltiples monedas,
                            tipos de cambio automáticos y regulaciones locales. Cada país con su tipo de documento (RUT, DNI, RFC, etc.).
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-translate"></i>
                        </div>
                        <h3 class="feature-title">9 Idiomas Disponibles</h3>
                        <p class="feature-description">
                            Interfaz completamente traducida en Español, English, Português, Français,
                            Deutsch, Italiano, Русский, 中文 y 日本語. Cambia de idioma en cualquier momento.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <h3 class="feature-title">Multiempresa</h3>
                        <p class="feature-description">
                            Gestiona múltiples empresas desde una sola cuenta. Ideal para holdings,
                            consultoras y grupos empresariales. Datos completamente segregados.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="feature-title">Seguridad Avanzada</h3>
                        <p class="feature-description">
                            Control de acceso por roles, autenticación de dos factores, cifrado de datos,
                            auditoría completa y backups automáticos. Tu información siempre protegida.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h3 class="feature-title">Business Intelligence</h3>
                        <p class="feature-description">
                            Dashboards interactivos, KPIs en tiempo real, reportes personalizados,
                            análisis predictivo y herramientas de visualización de datos avanzadas.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-link-45deg"></i>
                        </div>
                        <h3 class="feature-title">Integraciones</h3>
                        <p class="feature-description">
                            Conexión directa con SII Chile, Previred, actualización automática de UF,
                            USD, UTM, relojes control de asistencia y APIs abiertas para extensiones.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PLANES Y PRECIOS -->
    <section id="planes" class="section">
        <div class="container">
            <h2 class="section-title">Planes y Precios</h2>
            <p class="section-subtitle">
                Elige el plan perfecto para tu empresa. Todos con 14 días de prueba gratuita.
            </p>

            <div class="plans-container">
                <!-- Plan 1: Básico -->
                <div class="plan-card">
                    <div class="plan-name">Básico</div>
                    <div class="plan-description">
                        Ideal para pequeñas empresas y emprendedores
                    </div>
                    <div class="plan-price">
                        $29.99
                        <small>/mes</small>
                    </div>
                    <div class="plan-period">
                        o $299.90/año (ahorra $60)
                    </div>
                    <ul class="plan-features">
                        <li><i class="bi bi-check-circle-fill"></i> Hasta 5 usuarios</li>
                        <li><i class="bi bi-check-circle-fill"></i> 1 empresa</li>
                        <li><i class="bi bi-check-circle-fill"></i> 10 GB almacenamiento</li>
                        <li><i class="bi bi-check-circle-fill"></i> Módulos: Finanzas, Ventas, Inventario</li>
                        <li><i class="bi bi-check-circle-fill"></i> Soporte por email</li>
                        <li><i class="bi bi-check-circle-fill"></i> 14 días de prueba</li>
                    </ul>
                    <button class="btn-plan btn-plan-secondary" onclick="window.location.href='register.php?plan=basic'">
                        Comenzar Ahora
                    </button>
                </div>

                <!-- Plan 2: Profesional (Featured) -->
                <div class="plan-card featured">
                    <div class="plan-badge">POPULAR</div>
                    <div class="plan-name">Profesional</div>
                    <div class="plan-description">
                        Completo para empresas en crecimiento
                    </div>
                    <div class="plan-price">
                        $79.99
                        <small>/mes</small>
                    </div>
                    <div class="plan-period">
                        o $799.90/año (ahorra $160)
                    </div>
                    <ul class="plan-features">
                        <li><i class="bi bi-check-circle-fill"></i> Hasta 20 usuarios</li>
                        <li><i class="bi bi-check-circle-fill"></i> 3 empresas</li>
                        <li><i class="bi bi-check-circle-fill"></i> 50 GB almacenamiento</li>
                        <li><i class="bi bi-check-circle-fill"></i> Todos los módulos básicos + Producción + RRHH</li>
                        <li><i class="bi bi-check-circle-fill"></i> Soporte email + teléfono</li>
                        <li><i class="bi bi-check-circle-fill"></i> Integraciones SII/Previred</li>
                        <li><i class="bi bi-check-circle-fill"></i> 14 días de prueba</li>
                    </ul>
                    <button class="btn-plan btn-plan-primary" onclick="window.location.href='register.php?plan=professional'">
                        Comenzar Ahora
                    </button>
                </div>

                <!-- Plan 3: Empresarial -->
                <div class="plan-card">
                    <div class="plan-name">Empresarial</div>
                    <div class="plan-description">
                        Avanzado con todas las funcionalidades
                    </div>
                    <div class="plan-price">
                        $199.99
                        <small>/mes</small>
                    </div>
                    <div class="plan-period">
                        o $1,999.90/año (ahorra $400)
                    </div>
                    <ul class="plan-features">
                        <li><i class="bi bi-check-circle-fill"></i> Hasta 100 usuarios</li>
                        <li><i class="bi bi-check-circle-fill"></i> 10 empresas</li>
                        <li><i class="bi bi-check-circle-fill"></i> 200 GB almacenamiento</li>
                        <li><i class="bi bi-check-circle-fill"></i> TODOS los módulos (14 módulos completos)</li>
                        <li><i class="bi bi-check-circle-fill"></i> Soporte 24/7</li>
                        <li><i class="bi bi-check-circle-fill"></i> Módulos personalizados</li>
                        <li><i class="bi bi-check-circle-fill"></i> API completa</li>
                        <li><i class="bi bi-check-circle-fill"></i> 14 días de prueba</li>
                    </ul>
                    <button class="btn-plan btn-plan-secondary" onclick="window.location.href='register.php?plan=enterprise'">
                        Comenzar Ahora
                    </button>
                </div>

                <!-- Plan 4: Personalizado -->
                <div class="plan-card">
                    <div class="plan-name">Personalizado</div>
                    <div class="plan-description">
                        Diseñado según tus necesidades específicas
                    </div>
                    <div class="plan-price">
                        Consultar
                    </div>
                    <div class="plan-period">
                        Precio según requerimientos
                    </div>
                    <ul class="plan-features">
                        <li><i class="bi bi-check-circle-fill"></i> Usuarios ilimitados</li>
                        <li><i class="bi bi-check-circle-fill"></i> Empresas ilimitadas</li>
                        <li><i class="bi bi-check-circle-fill"></i> Almacenamiento ilimitado</li>
                        <li><i class="bi bi-check-circle-fill"></i> Desarrollo a medida</li>
                        <li><i class="bi bi-check-circle-fill"></i> Soporte dedicado 24/7</li>
                        <li><i class="bi bi-check-circle-fill"></i> Capacitación incluida</li>
                        <li><i class="bi bi-check-circle-fill"></i> SLA garantizado</li>
                        <li><i class="bi bi-check-circle-fill"></i> Consultoría estratégica</li>
                    </ul>
                    <button class="btn-plan btn-plan-secondary" onclick="window.location.href='mailto:auditorexchile@gmail.com?subject=Consulta Plan Personalizado'">
                        Contactar Ventas
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- MÓDULOS -->
    <section id="modulos" class="section bg-white">
        <div class="container">
            <h2 class="section-title">14 Módulos Completos</h2>
            <p class="section-subtitle">
                106 submódulos diseñados para cubrir todas las necesidades de tu empresa
            </p>

            <div class="modules-grid">
                <!-- Módulo 1 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">Administración Central</div>
                            <span class="module-count">3 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Gestión de empresas, seguridad, usuarios, roles y parametrización global del sistema.
                    </p>
                </div>

                <!-- Módulo 2 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">Gestión de Entidades</div>
                            <span class="module-count">5 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Maestros de clientes, proveedores, empleados, productos y servicios.
                    </p>
                </div>

                <!-- Módulo 3 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <div class="module-title">Finanzas (FI)</div>
                            <span class="module-count">11 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Contabilidad general, cuentas por pagar/cobrar, tesorería, activos fijos, IFRS, presupuestos e impuestos.
                    </p>
                </div>

                <!-- Módulo 4 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div>
                            <div class="module-title">Controlling (CO)</div>
                            <span class="module-count">8 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Centros de costo, rentabilidad, control de gastos, costos de producto y análisis de resultados.
                    </p>
                </div>

                <!-- Módulo 5 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="bi bi-cart-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">Ventas (SD)</div>
                            <span class="module-count">9 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Pedidos, facturación, punto de venta, precios, promociones, devoluciones y análisis de ventas.
                    </p>
                </div>

                <!-- Módulo 6 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">Materiales (MM)</div>
                            <span class="module-count">8 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Inventario, compras, almacenes, valoración, planificación de necesidades y control de calidad.
                    </p>
                </div>

                <!-- Módulo 7 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                        <div>
                            <div class="module-title">Producción (PP)</div>
                            <span class="module-count">10 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Órdenes de producción, MRP, planificación, control de planta, calidad, mantenimiento y trazabilidad.
                    </p>
                </div>

                <!-- Módulo 8 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #14b8a6, #0d9488);">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">Recursos Humanos (HCM)</div>
                            <span class="module-count">11 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Personal, nómina, reclutamiento, evaluación, capacitación, asistencia con relojes control, vacaciones y salud ocupacional.
                    </p>
                </div>

                <!-- Módulo 9 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div>
                            <div class="module-title">Supply Chain (SCM)</div>
                            <span class="module-count">10 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Logística, transporte, rutas, entregas, flotilla, optimización y trazabilidad de envíos.
                    </p>
                </div>

                <!-- Módulo 10 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">CRM</div>
                            <span class="module-count">8 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Gestión de clientes, oportunidades, leads, pipeline de ventas, contactos, campañas y cotizaciones.
                    </p>
                </div>

                <!-- Módulo 11 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #eab308, #ca8a04);">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">Fidelización</div>
                            <span class="module-count">7 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Programas de lealtad, puntos, recompensas, campañas personalizadas y segmentación de clientes.
                    </p>
                </div>

                <!-- Módulo 12 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #a855f7, #9333ea);">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <div>
                            <div class="module-title">Business Intelligence</div>
                            <span class="module-count">15 submódulos</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Dashboards, KPIs, informes ejecutivos, análisis predictivo y reportes personalizados para todos los módulos.
                    </p>
                </div>

                <!-- Módulo 13 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #64748b, #475569);">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <div>
                            <div class="module-title">Configuración</div>
                            <span class="module-count">1 submódulo</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Configuración general del sistema, parámetros y ajustes globales.
                    </p>
                </div>

                <!-- Módulo 14 -->
                <div class="module-card">
                    <div class="module-header">
                        <div class="module-icon" style="background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div>
                            <div class="module-title">Dashboard Principal</div>
                            <span class="module-count">Panel de navegación</span>
                        </div>
                    </div>
                    <p class="module-description">
                        Panel principal con estadísticas, accesos rápidos y navegación a todos los módulos del sistema.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h3 class="footer-title">CONECTA ERP</h3>
                    <p style="color: rgba(255, 255, 255, 0.7);">
                        Sistema ERP empresarial completo y profesional.
                        Gestiona tu negocio desde una sola plataforma.
                    </p>
                    <div class="mt-3">
                        <a href="mailto:auditorexchile@gmail.com" style="color: rgba(255, 255, 255, 0.7); text-decoration: none;">
                            <i class="bi bi-envelope me-2"></i> auditorexchile@gmail.com
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h4 class="footer-title">Producto</h4>
                    <ul class="footer-links">
                        <li><a href="#caracteristicas">Características</a></li>
                        <li><a href="#planes">Planes</a></li>
                        <li><a href="#modulos">Módulos</a></li>
                        <li><a href="register.php">Prueba Gratis</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h4 class="footer-title">Empresa</h4>
                    <ul class="footer-links">
                        <li><a href="#">Acerca de</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Carreras</a></li>
                        <li><a href="#">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h4 class="footer-title">Soporte</h4>
                    <ul class="footer-links">
                        <li><a href="#">Centro de Ayuda</a></li>
                        <li><a href="#">Documentación</a></li>
                        <li><a href="#">API</a></li>
                        <li><a href="#">Estado del Sistema</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h4 class="footer-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Términos de Servicio</a></li>
                        <li><a href="#">Privacidad</a></li>
                        <li><a href="#">Seguridad</a></li>
                        <li><a href="#">Cookies</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© 2024 CONECTA ERP by Auditorex Chile. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
