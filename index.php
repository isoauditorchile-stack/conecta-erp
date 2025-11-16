<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONECTA ERP - Sistema de Gestión Empresarial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

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
            margin-bottom: 1rem;
            opacity: 0.95;
        }

        .hero .description {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero .stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .hero .stat-item {
            text-align: center;
        }

        .hero .stat-number {
            font-size: 3rem;
            font-weight: 800;
            display: block;
        }

        .hero .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            color: #667eea;
        }

        .btn-secondary-custom {
            background: transparent;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid white;
            transition: all 0.3s;
        }

        .btn-secondary-custom:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        /* Plans Section */
        .plans-section {
            padding: 100px 20px;
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: #1e293b;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.3rem;
            color: #64748b;
            margin-bottom: 4rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .plan-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .plan-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .plan-card.featured {
            border: 3px solid #667eea;
            transform: scale(1.05);
        }

        .plan-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .plan-name {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .plan-price small {
            font-size: 1.2rem;
            color: #64748b;
            font-weight: 400;
        }

        .plan-description {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .plan-features li {
            padding: 10px 0;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-features li i {
            color: #10b981;
            font-size: 1.2rem;
        }

        .plan-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .plan-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        /* Features Section */
        .features-section {
            padding: 100px 20px;
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-item {
            text-align: center;
            padding: 30px;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .feature-description {
            color: #64748b;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: #1e293b;
            color: white;
            padding: 60px 20px 20px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #667eea;
        }

        .footer-bottom {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero .subtitle {
                font-size: 1.2rem;
            }

            .hero .stats {
                gap: 2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .plan-card.featured {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>🚀 CONECTA ERP</h1>
            <p class="subtitle">Sistema de Gestión Empresarial Completo</p>
            <p class="description">
                La solución ERP más completa del mercado con 14 módulos principales y 106 submódulos especializados.
                <br>Multiusuario • Multiempresa • Multipais • Multimoneda • Multiidioma
            </p>

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
                <a href="register.php" class="btn-primary-custom">
                    <i class="fas fa-rocket"></i> Comenzar Gratis - 14 Días
                </a>
                <a href="login.php" class="btn-secondary-custom">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </a>
            </div>
        </div>
    </section>

    <!-- Plans Section -->
    <section class="plans-section" id="planes">
        <h2 class="section-title">Planes y Precios</h2>
        <p class="section-subtitle">
            Elige el plan que mejor se adapte a las necesidades de tu empresa.
            Todos incluyen 14 días de prueba gratis sin necesidad de tarjeta de crédito.
        </p>

        <div class="plans-container">
            <!-- Plan Básico -->
            <div class="plan-card">
                <h3 class="plan-name">Básico</h3>
                <div class="plan-price">$49.990<small>/mes</small></div>
                <p class="plan-description">Ideal para pequeñas empresas que están comenzando</p>
                <ul class="plan-features">
                    <li><i class="fas fa-check-circle"></i> Hasta 5 usuarios</li>
                    <li><i class="fas fa-check-circle"></i> 6 módulos básicos</li>
                    <li><i class="fas fa-check-circle"></i> 1 empresa</li>
                    <li><i class="fas fa-check-circle"></i> Soporte email</li>
                    <li><i class="fas fa-check-circle"></i> Actualizaciones incluidas</li>
                </ul>
                <button class="plan-button" onclick="window.location.href='register.php?plan=1'">
                    Comenzar Ahora
                </button>
            </div>

            <!-- Plan Profesional -->
            <div class="plan-card featured">
                <div class="plan-badge">MÁS POPULAR</div>
                <h3 class="plan-name">Profesional</h3>
                <div class="plan-price">$99.990<small>/mes</small></div>
                <p class="plan-description">Para empresas en crecimiento que necesitan más poder</p>
                <ul class="plan-features">
                    <li><i class="fas fa-check-circle"></i> Hasta 25 usuarios</li>
                    <li><i class="fas fa-check-circle"></i> 12 módulos completos</li>
                    <li><i class="fas fa-check-circle"></i> 3 empresas</li>
                    <li><i class="fas fa-check-circle"></i> Soporte prioritario</li>
                    <li><i class="fas fa-check-circle"></i> Reportes avanzados</li>
                    <li><i class="fas fa-check-circle"></i> Integraciones SII/Previred</li>
                </ul>
                <button class="plan-button" onclick="window.location.href='register.php?plan=2'">
                    Comenzar Ahora
                </button>
            </div>

            <!-- Plan Empresarial -->
            <div class="plan-card">
                <h3 class="plan-name">Empresarial</h3>
                <div class="plan-price">$199.990<small>/mes</small></div>
                <p class="plan-description">Para grandes empresas con necesidades avanzadas</p>
                <ul class="plan-features">
                    <li><i class="fas fa-check-circle"></i> Usuarios ilimitados</li>
                    <li><i class="fas fa-check-circle"></i> 14 módulos + 106 submódulos</li>
                    <li><i class="fas fa-check-circle"></i> Empresas ilimitadas</li>
                    <li><i class="fas fa-check-circle"></i> Soporte 24/7</li>
                    <li><i class="fas fa-check-circle"></i> Business Intelligence</li>
                    <li><i class="fas fa-check-circle"></i> API completa</li>
                    <li><i class="fas fa-check-circle"></i> Capacitación incluida</li>
                </ul>
                <button class="plan-button" onclick="window.location.href='register.php?plan=3'">
                    Comenzar Ahora
                </button>
            </div>

            <!-- Plan Personalizado -->
            <div class="plan-card">
                <h3 class="plan-name">Personalizado</h3>
                <div class="plan-price">A medida</div>
                <p class="plan-description">Soluciones diseñadas específicamente para tu negocio</p>
                <ul class="plan-features">
                    <li><i class="fas fa-check-circle"></i> Todo de Empresarial</li>
                    <li><i class="fas fa-check-circle"></i> Desarrollo a medida</li>
                    <li><i class="fas fa-check-circle"></i> Módulos personalizados</li>
                    <li><i class="fas fa-check-circle"></i> Servidor dedicado</li>
                    <li><i class="fas fa-check-circle"></i> Gerente de cuenta</li>
                    <li><i class="fas fa-check-circle"></i> SLA garantizado</li>
                </ul>
                <button class="plan-button" onclick="window.location.href='register.php?plan=4'">
                    Contactar Ventas
                </button>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <h2 class="section-title">Características Principales</h2>
        <p class="section-subtitle">
            Un sistema ERP completo con todo lo que tu empresa necesita para crecer
        </p>

        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <h3 class="feature-title">Multipaís y Multiidioma</h3>
                <p class="feature-description">
                    Opera en 9 países diferentes con soporte para 8 idiomas. El sistema se adapta automáticamente a las leyes y regulaciones de cada país.
                </p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3 class="feature-title">Multiempresa</h3>
                <p class="feature-description">
                    Gestiona múltiples empresas desde una sola cuenta. Ideal para holdings y grupos empresariales.
                </p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">Seguridad Avanzada</h3>
                <p class="feature-description">
                    Sistema de permisos granular, autenticación de dos factores, encriptación de datos y cumplimiento GDPR.
                </p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="feature-title">Business Intelligence</h3>
                <p class="feature-description">
                    Dashboards interactivos, reportes personalizados, KPIs en tiempo real y análisis predictivo con IA.
                </p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-plug"></i>
                </div>
                <h3 class="feature-title">Integraciones</h3>
                <p class="feature-description">
                    Conecta con SII, Previred, bancos, y más de 100 aplicaciones de terceros mediante API REST.
                </p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="feature-title">Responsive Design</h3>
                <p class="feature-description">
                    Accede desde cualquier dispositivo: computador, tablet o smartphone. 100% responsive y optimizado.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fas fa-network-wired"></i> CONECTA ERP
            </div>
            <p>El sistema ERP más completo del mercado</p>

            <div class="footer-links">
                <a href="#planes">Planes</a>
                <a href="#">Características</a>
                <a href="#">Documentación</a>
                <a href="#">Soporte</a>
                <a href="#">Contacto</a>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 CONECTA ERP. Todos los derechos reservados.</p>
                <p>Sistema desarrollado para empresas chilenas y latinoamericanas</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
