<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONECTA ERP - Sistema ERP Profesional Multi-Empresa</title>
    <meta name="description" content="Sistema ERP completo con 14 módulos, multiempresa, multiidioma y multipais. La solución profesional para su negocio.">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">

    <style>
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            color: var(--primary-color);
        }

        .module-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary-color);
        }

        .module-card:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .module-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-right: 15px;
        }

        .country-flag {
            width: 30px;
            height: 20px;
            object-fit: cover;
            border-radius: 3px;
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">
                        <i class="fas fa-chart-line"></i> CONECTA ERP
                    </h1>
                    <p class="hero-subtitle">
                        Sistema ERP Profesional Multi-Empresa
                    </p>
                    <p class="hero-description">
                        La solución completa para gestionar tu empresa con 14 módulos profesionales,
                        106 submódulos especializados, soporte multiidioma en 9 idiomas y operación en 9 países.
                        Todo lo que necesitas en una sola plataforma.
                    </p>
                    <div class="hero-buttons">
                        <a href="#registro" class="btn btn-lg btn-primary">
                            <i class="fas fa-rocket"></i> Comenzar Prueba Gratis (14 días)
                        </a>
                        <a href="#planes" class="btn btn-lg btn-secondary ms-3">
                            <i class="fas fa-tags"></i> Ver Planes
                        </a>
                    </div>

                    <div class="mt-5 d-flex gap-4">
                        <div>
                            <h3 class="mb-0" style="color: white; font-size: 2.5rem; font-weight: 800;">14</h3>
                            <p style="color: rgba(255,255,255,0.9);">Módulos</p>
                        </div>
                        <div>
                            <h3 class="mb-0" style="color: white; font-size: 2.5rem; font-weight: 800;">106</h3>
                            <p style="color: rgba(255,255,255,0.9);">Submódulos</p>
                        </div>
                        <div>
                            <h3 class="mb-0" style="color: white; font-size: 2.5rem; font-weight: 800;">9</h3>
                            <p style="color: rgba(255,255,255,0.9);">Idiomas</p>
                        </div>
                        <div>
                            <h3 class="mb-0" style="color: white; font-size: 2.5rem; font-weight: 800;">9</h3>
                            <p style="color: rgba(255,255,255,0.9);">Países</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="text-center">
                        <img src="https://via.placeholder.com/600x500/667eea/ffffff?text=CONECTA+ERP" alt="CONECTA ERP Dashboard" class="img-fluid" style="border-radius: 30px; box-shadow: 0 30px 60px rgba(0,0,0,0.3); animation: float 3s ease-in-out infinite;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CARACTERÍSTICAS PRINCIPALES -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    ¿Por qué elegir CONECTA ERP?
                </h2>
                <p class="lead text-muted">Una solución completa y profesional para tu empresa</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h4 class="fw-bold">Multi-País</h4>
                        <p class="text-muted">Soporta 9 países con validación de documentos locales (RUT, DNI, CURP, CPF, etc.)</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-language"></i>
                        </div>
                        <h4 class="fw-bold">Multi-Idioma</h4>
                        <p class="text-muted">Interfaz completa en 9 idiomas: Español, English, Português, Français, Deutsch, Italiano, Русский, 中文, 日本語</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-building"></i>
                        </div>
                        <h4 class="fw-bold">Multi-Empresa</h4>
                        <p class="text-muted">Gestiona múltiples empresas desde una sola cuenta con control total de permisos</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="fw-bold">Seguridad Avanzada</h4>
                        <p class="text-muted">Control de acceso basado en roles, autenticación segura y logs de auditoría</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h4 class="fw-bold">Actualización Automática</h4>
                        <p class="text-muted">UF, Dólar, UTM y otros indicadores se actualizan automáticamente</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4 class="fw-bold">Integración SII & Previred</h4>
                        <p class="text-muted">Conexión directa con servicios de impuestos internos y previsión social</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MÓDULOS DEL SISTEMA -->
    <section class="py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    14 Módulos Profesionales
                </h2>
                <p class="lead text-muted">106 Submódulos Especializados para Gestión Completa</p>
            </div>

            <div class="row">
                <!-- Módulo 1: Administración Central -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #FF6B6B;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #FF6B6B;">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Administración Central</h5>
                                <p class="text-muted mb-2">3 Submódulos</p>
                                <small class="text-muted">Gestión de Empresas • Seguridad • Parametrización Global</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 2: Gestión de Entidades -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #4ECDC4;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #4ECDC4;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Gestión de Entidades</h5>
                                <p class="text-muted mb-2">5 Submódulos</p>
                                <small class="text-muted">Clientes • Proveedores • Empleados • Productos • Maestras</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 3: Finanzas -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #45B7D1;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #45B7D1;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Finanzas (FI)</h5>
                                <p class="text-muted mb-2">11 Submódulos</p>
                                <small class="text-muted">Contabilidad • Tesorería • Activos Fijos • Cuentas por Pagar/Cobrar • IFRS • Impuestos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 4: Controlling -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #96CEB4;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #96CEB4;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Controlling (CO)</h5>
                                <p class="text-muted mb-2">8 Submódulos</p>
                                <small class="text-muted">Centros de Costo • Rentabilidad • Proyectos • Costos • Presupuestos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 5: Ventas -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #FFEAA7;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #FFEAA7; color: #333;">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Ventas (SD)</h5>
                                <p class="text-muted mb-2">9 Submódulos</p>
                                <small class="text-muted">Pedidos • Facturación • POS • Cajas • Precios • Devoluciones</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 6: Materiales -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #DFE6E9;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #DFE6E9; color: #333;">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Materiales (MM)</h5>
                                <p class="text-muted mb-2">8 Submódulos</p>
                                <small class="text-muted">Inventario • Compras • Almacenes • Calidad • Valoración</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 7: Producción -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #74B9FF;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #74B9FF;">
                                <i class="fas fa-industry"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Producción (PP)</h5>
                                <p class="text-muted mb-2">10 Submódulos</p>
                                <small class="text-muted">Órdenes • MRP • Planificación • Control de Planta • Mantenimiento</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 8: RRHH -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #A29BFE;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #A29BFE;">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Recursos Humanos (HCM)</h5>
                                <p class="text-muted mb-2">11 Submódulos</p>
                                <small class="text-muted">Personal • Nómina • Reclutamiento • Capacitación • Beneficios</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 9: SCM -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #FD79A8;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #FD79A8;">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Supply Chain (SCM)</h5>
                                <p class="text-muted mb-2">10 Submódulos</p>
                                <small class="text-muted">Logística • Transporte • Rutas • Flotilla • Trazabilidad</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 10: CRM -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #FDCB6E;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #FDCB6E; color: #333;">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">CRM</h5>
                                <p class="text-muted mb-2">8 Submódulos</p>
                                <small class="text-muted">Clientes • Oportunidades • Leads • Pipeline • Campañas</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 11: Fidelización -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #E17055;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #E17055;">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Fidelización</h5>
                                <p class="text-muted mb-2">7 Submódulos</p>
                                <small class="text-muted">Programas • Puntos • Recompensas • Campañas • Segmentación</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 12: BI -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #00B894;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #00B894;">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Business Intelligence</h5>
                                <p class="text-muted mb-2">15 Submódulos</p>
                                <small class="text-muted">Dashboards • KPIs • Reportes • Análisis Predictivo • Informes Ejecutivos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 13: Configuración -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #636E72;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #636E72;">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Configuración</h5>
                                <p class="text-muted mb-2">Ajustes del Sistema</p>
                                <small class="text-muted">Parámetros • Preferencias • Personalización</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Módulo 14: Dashboard -->
                <div class="col-lg-6">
                    <div class="module-card" style="border-color: #6C5CE7;">
                        <div class="d-flex">
                            <div class="module-icon" style="background: #6C5CE7;">
                                <i class="fas fa-home"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Dashboard Principal</h5>
                                <p class="text-muted mb-2">Panel Central</p>
                                <small class="text-muted">Navegación • Estadísticas • Accesos Rápidos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PLANES Y PRECIOS -->
    <section class="py-5 bg-white" id="planes">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Planes y Precios
                </h2>
                <p class="lead text-muted">Comienza con 14 días de prueba gratis • Sin tarjeta de crédito</p>
            </div>

            <div class="row g-4">
                <!-- Plan Básico -->
                <div class="col-lg-3">
                    <div class="card plan-card h-100">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Plan Básico</h4>
                            <div class="plan-price">
                                <span class="currency">$</span>29<span class="period">/mes</span>
                            </div>
                            <p class="text-muted">Ideal para pequeñas empresas</p>

                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Hasta 3 usuarios</li>
                                <li><i class="fas fa-check"></i> 1 empresa</li>
                                <li><i class="fas fa-check"></i> Módulos básicos</li>
                                <li><i class="fas fa-check"></i> 10GB almacenamiento</li>
                                <li><i class="fas fa-check"></i> Soporte por email</li>
                                <li><i class="fas fa-check"></i> 14 días prueba gratis</li>
                            </ul>

                            <a href="#registro" class="btn btn-primary btn-block mt-4">
                                <i class="fas fa-rocket"></i> Comenzar Ahora
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Plan Profesional -->
                <div class="col-lg-3">
                    <div class="card plan-card h-100 featured">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Plan Profesional</h4>
                            <div class="plan-price">
                                <span class="currency">$</span>79<span class="period">/mes</span>
                            </div>
                            <p class="text-muted">Para empresas en crecimiento</p>

                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Hasta 10 usuarios</li>
                                <li><i class="fas fa-check"></i> 3 empresas</li>
                                <li><i class="fas fa-check"></i> Todos los módulos</li>
                                <li><i class="fas fa-check"></i> 100GB almacenamiento</li>
                                <li><i class="fas fa-check"></i> Soporte prioritario</li>
                                <li><i class="fas fa-check"></i> Reportes avanzados</li>
                            </ul>

                            <a href="#registro" class="btn btn-primary btn-block mt-4">
                                <i class="fas fa-rocket"></i> Comenzar Ahora
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Plan Empresarial -->
                <div class="col-lg-3">
                    <div class="card plan-card h-100">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Plan Empresarial</h4>
                            <div class="plan-price">
                                <span class="currency">$</span>199<span class="period">/mes</span>
                            </div>
                            <p class="text-muted">Para grandes empresas</p>

                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Hasta 50 usuarios</li>
                                <li><i class="fas fa-check"></i> 10 empresas</li>
                                <li><i class="fas fa-check"></i> Todos los módulos</li>
                                <li><i class="fas fa-check"></i> Almacenamiento ilimitado</li>
                                <li><i class="fas fa-check"></i> Soporte 24/7</li>
                                <li><i class="fas fa-check"></i> API completa</li>
                            </ul>

                            <a href="#registro" class="btn btn-primary btn-block mt-4">
                                <i class="fas fa-rocket"></i> Comenzar Ahora
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Plan Personalizado -->
                <div class="col-lg-3">
                    <div class="card plan-card h-100">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Plan Personalizado</h4>
                            <div class="plan-price">
                                Contactar
                            </div>
                            <p class="text-muted">Solución a medida</p>

                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Usuarios ilimitados</li>
                                <li><i class="fas fa-check"></i> Empresas ilimitadas</li>
                                <li><i class="fas fa-check"></i> Todo incluido</li>
                                <li><i class="fas fa-check"></i> Sin límites</li>
                                <li><i class="fas fa-check"></i> Desarrollo personalizado</li>
                                <li><i class="fas fa-check"></i> Consultor dedicado</li>
                            </ul>

                            <a href="mailto:auditorexchile@gmail.com" class="btn btn-secondary btn-block mt-4">
                                <i class="fas fa-envelope"></i> Contactar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-5" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white;">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h3 class="fw-bold mb-3">CONECTA ERP</h3>
                    <p>Sistema ERP profesional multi-empresa, multiidioma y multipais. La solución completa para gestionar tu negocio.</p>
                    <div class="mt-3">
                        <a href="#" class="btn btn-outline-light btn-sm me-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm me-2"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-lg-3 mb-4">
                    <h5 class="fw-bold mb-3">Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#planes" class="text-white">Planes y Precios</a></li>
                        <li class="mb-2"><a href="#registro" class="text-white">Registrarse</a></li>
                        <li class="mb-2"><a href="login.php" class="text-white">Iniciar Sesión</a></li>
                        <li class="mb-2"><a href="mailto:auditorexchile@gmail.com" class="text-white">Contacto</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 mb-4">
                    <h5 class="fw-bold mb-3">Soporte</h5>
                    <p><i class="fas fa-envelope me-2"></i> auditorexchile@gmail.com</p>
                    <p><i class="fas fa-phone me-2"></i> +56 9 XXXX XXXX</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i> Santiago, Chile</p>
                </div>
            </div>

            <hr style="border-color: rgba(255,255,255,0.2);" class="my-4">

            <div class="text-center">
                <p class="mb-0">&copy; 2025 CONECTA ERP. Todos los derechos reservados. Desarrollado por Auditorex Chile.</p>
            </div>
        </div>
    </footer>

    <!-- Botón flotante de Login -->
    <a href="login.php" class="position-fixed bottom-0 end-0 m-4 btn btn-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); z-index: 1000;">
        <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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
