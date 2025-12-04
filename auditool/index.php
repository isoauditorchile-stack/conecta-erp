<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUDITOR PRO - Plataforma de Gestión Multi-ISO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Header/Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-links a:hover { color: #667eea; }
        .btn {
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-outline {
            border: 2px solid #667eea;
            color: #667eea;
            background: white;
        }
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 100px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        .hero h1 {
            font-size: 56px;
            margin-bottom: 20px;
            animation: fadeInUp 1s;
        }
        .hero p {
            font-size: 24px;
            margin-bottom: 40px;
            opacity: 0.95;
            animation: fadeInUp 1s 0.2s both;
        }
        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            animation: fadeInUp 1s 0.4s both;
        }
        .hero-buttons .btn {
            font-size: 18px;
            padding: 15px 40px;
        }

        /* Features Section */
        .features {
            padding: 100px 20px;
            background: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .section-title {
            text-align: center;
            font-size: 42px;
            margin-bottom: 20px;
            color: #333;
        }
        .section-subtitle {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 60px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .feature-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        .feature-description {
            color: #666;
            line-height: 1.6;
        }

        /* ISO Standards Section */
        .iso-standards {
            padding: 100px 20px;
            background: white;
        }
        .iso-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }
        .iso-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            color: white;
            text-align: center;
            transition: transform 0.3s;
        }
        .iso-card:hover {
            transform: scale(1.05);
        }
        .iso-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .iso-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .iso-description {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Pricing Section */
        .pricing {
            padding: 100px 20px;
            background: #f8f9fa;
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }
        .pricing-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        .pricing-card.featured {
            border: 3px solid #667eea;
            transform: scale(1.05);
        }
        .pricing-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .pricing-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        .pricing-price {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .pricing-period {
            color: #666;
            margin-bottom: 30px;
        }
        .pricing-features {
            text-align: left;
            margin-bottom: 30px;
        }
        .pricing-feature {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #666;
        }
        .pricing-feature:last-child {
            border-bottom: none;
        }

        /* CTA Section */
        .cta {
            padding: 100px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        .cta h2 {
            font-size: 42px;
            margin-bottom: 20px;
        }
        .cta p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        /* Footer */
        .footer {
            padding: 60px 20px 30px;
            background: #2c3e50;
            color: white;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-section h3 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        .footer-section a {
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            margin-bottom: 10px;
            transition: color 0.3s;
        }
        .footer-section a:hover {
            color: white;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
        }

        /* Animations */
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

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 36px; }
            .hero p { font-size: 18px; }
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            .section-title { font-size: 32px; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">🔐 AUDITOR PRO</div>
            <div class="nav-links">
                <a href="#features">Características</a>
                <a href="#isos">ISOs</a>
                <a href="#pricing">Precios</a>
                <a href="login.php" class="btn btn-outline">Iniciar Sesión</a>
                <a href="register.php" class="btn btn-primary">Registrarse</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Plataforma de Gestión Multi-ISO</h1>
            <p>La solución profesional para gestionar todos sus sistemas de gestión ISO en un solo lugar</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary" style="background: white; color: #667eea;">Comenzar Gratis - 5 Días Trial</a>
                <a href="#pricing" class="btn btn-outline" style="border-color: white; color: white;">Ver Planes</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">¿Por qué elegir AUDITOR PRO?</h2>
            <p class="section-subtitle">La plataforma más completa para la gestión de normativas ISO</p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3 class="feature-title">Multi-País</h3>
                    <p class="feature-description">Soporte para 8 países con regulaciones locales específicas y normativas adaptadas.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🏢</div>
                    <h3 class="feature-title">Multi-Empresa</h3>
                    <p class="feature-description">Gestione múltiples empresas desde una sola cuenta con datos completamente aislados.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3 class="feature-title">Multi-Usuario</h3>
                    <p class="feature-description">Sistema de roles y permisos granulares para equipos de cualquier tamaño.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3 class="feature-title">Multi-Idioma</h3>
                    <p class="feature-description">Interfaz disponible en Español, Inglés y Portugués con traducción automática.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3 class="feature-title">Multi-Moneda</h3>
                    <p class="feature-description">Soporte para USD, EUR, CLP, ARS, PEN, COP, MXN y BRL.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">14 Normas ISO</h3>
                    <p class="feature-description">Gestione hasta 14 normas ISO diferentes desde una misma plataforma integrada.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ISO Standards Section -->
    <section class="iso-standards" id="isos">
        <div class="container">
            <h2 class="section-title">Normas ISO Soportadas</h2>
            <p class="section-subtitle">Cobertura completa de las principales normas internacionales</p>

            <div class="iso-grid">
                <div class="iso-card">
                    <div class="iso-icon">🔐</div>
                    <div class="iso-name">ISO 27001</div>
                    <div class="iso-description">Seguridad de la Información</div>
                </div>

                <div class="iso-card">
                    <div class="iso-icon">🔄</div>
                    <div class="iso-name">ISO 22301</div>
                    <div class="iso-description">Continuidad del Negocio</div>
                </div>

                <div class="iso-card">
                    <div class="iso-icon">⚖️</div>
                    <div class="iso-name">ISO 37001</div>
                    <div class="iso-description">Antisoborno</div>
                </div>

                <div class="iso-card">
                    <div class="iso-icon">✓</div>
                    <div class="iso-name">ISO 9001</div>
                    <div class="iso-description">Gestión de Calidad</div>
                </div>

                <div class="iso-card">
                    <div class="iso-icon">🌱</div>
                    <div class="iso-name">ISO 14001</div>
                    <div class="iso-description">Gestión Ambiental</div>
                </div>

                <div class="iso-card">
                    <div class="iso-icon">👷</div>
                    <div class="iso-name">ISO 45001</div>
                    <div class="iso-description">Seguridad y Salud</div>
                </div>

                <div class="iso-card">
                    <div class="iso-icon">📊</div>
                    <div class="iso-name">ISO 31000</div>
                    <div class="iso-description">Gestión de Riesgos</div>
                </div>

                <div class="iso-card">
                    <div class="iso-icon">⚡</div>
                    <div class="iso-name">ISO 50001</div>
                    <div class="iso-description">Gestión Energética</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="container">
            <h2 class="section-title">Planes y Precios</h2>
            <p class="section-subtitle">Elija el plan que mejor se adapte a sus necesidades. ¡5 Días de Prueba Gratis!</p>

            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-name">Básico</div>
                    <div class="pricing-price">$99</div>
                    <div class="pricing-period">USD / mes</div>
                    <div class="pricing-features">
                        <div class="pricing-feature">✓ 1 Empresa</div>
                        <div class="pricing-feature">✓ 5 Usuarios</div>
                        <div class="pricing-feature">✓ 3 Normas ISO</div>
                        <div class="pricing-feature">✓ Soporte Email</div>
                        <div class="pricing-feature">✓ Actualizaciones</div>
                    </div>
                    <a href="register.php?plan=basic" class="btn btn-primary">Comenzar</a>
                </div>

                <div class="pricing-card featured">
                    <div class="pricing-badge">MÁS POPULAR</div>
                    <div class="pricing-name">Profesional</div>
                    <div class="pricing-price">$299</div>
                    <div class="pricing-period">USD / mes</div>
                    <div class="pricing-features">
                        <div class="pricing-feature">✓ 5 Empresas</div>
                        <div class="pricing-feature">✓ 25 Usuarios</div>
                        <div class="pricing-feature">✓ 10 Normas ISO</div>
                        <div class="pricing-feature">✓ Soporte Prioritario</div>
                        <div class="pricing-feature">✓ Backup Automático</div>
                        <div class="pricing-feature">✓ API Access</div>
                    </div>
                    <a href="register.php?plan=professional" class="btn btn-primary">Comenzar</a>
                </div>

                <div class="pricing-card">
                    <div class="pricing-name">Enterprise</div>
                    <div class="pricing-price">$799</div>
                    <div class="pricing-period">USD / mes</div>
                    <div class="pricing-features">
                        <div class="pricing-feature">✓ Empresas Ilimitadas</div>
                        <div class="pricing-feature">✓ Usuarios Ilimitados</div>
                        <div class="pricing-feature">✓ 14 Normas ISO</div>
                        <div class="pricing-feature">✓ Soporte 24/7</div>
                        <div class="pricing-feature">✓ Onboarding Personalizado</div>
                        <div class="pricing-feature">✓ Servidor Dedicado</div>
                    </div>
                    <a href="register.php?plan=enterprise" class="btn btn-primary">Contactar</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>¿Listo para comenzar?</h2>
            <p>Únase a cientos de empresas que ya confían en AUDITOR PRO</p>
            <a href="register.php" class="btn btn-primary" style="background: white; color: #667eea;">Prueba Gratuita 5 Días</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>AUDITOR PRO</h3>
                    <p>La plataforma líder en gestión de sistemas ISO para empresas de todo el mundo.</p>
                </div>

                <div class="footer-section">
                    <h3>Producto</h3>
                    <a href="#features">Características</a>
                    <a href="#isos">Normas ISO</a>
                    <a href="#pricing">Precios</a>
                    <a href="#">Documentación</a>
                </div>

                <div class="footer-section">
                    <h3>Empresa</h3>
                    <a href="#">Acerca de</a>
                    <a href="#">Blog</a>
                    <a href="#">Contacto</a>
                    <a href="#">Soporte</a>
                </div>

                <div class="footer-section">
                    <h3>Legal</h3>
                    <a href="#">Términos de Servicio</a>
                    <a href="#">Política de Privacidad</a>
                    <a href="#">Condiciones de Uso</a>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 AUDITOR PRO by AuditorEx Chile. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>
