<?php
// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión ANTES de cualquier cosa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Cargar configuración
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Obtener datos del usuario de forma segura
$usuario_id = $_SESSION['user_id'];
$usuario_nombre = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
$empresa_id = $_SESSION['empresa_id'] ?? 1;

// Definición completa de TODOS los módulos del sistema
$modulos_sistema = [
    'Ventas' => [
        'icon' => 'fa-shopping-cart',
        'color' => 'primary',
        'modulos' => [
            ['nombre' => 'Dashboard Ventas', 'url' => '../modulos/ventas/dashboard_ventas.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Panel de control de ventas'],
            ['nombre' => 'Facturación', 'url' => '../modulos/ventas/facturacion.php', 'icono' => 'fa-file-invoice-dollar', 'desc' => 'Gestión de facturas de venta'],
            ['nombre' => 'Cotizaciones', 'url' => '../modulos/ventas/cotizaciones.php', 'icono' => 'fa-file-alt', 'desc' => 'Crear y gestionar cotizaciones'],
            ['nombre' => 'Pedidos', 'url' => '../modulos/ventas/pedidos.php', 'icono' => 'fa-shopping-bag', 'desc' => 'Pedidos de venta'],
            ['nombre' => 'Punto de Venta (POS)', 'url' => '../modulos/ventas/pos.php', 'icono' => 'fa-cash-register', 'desc' => 'Sistema punto de venta'],
            ['nombre' => 'Devoluciones', 'url' => '../modulos/ventas/devoluciones.php', 'icono' => 'fa-undo', 'desc' => 'Gestión de devoluciones'],
            ['nombre' => 'Promociones', 'url' => '../modulos/ventas/promociones.php', 'icono' => 'fa-gift', 'desc' => 'Promociones y descuentos'],
            ['nombre' => 'Gestión de Precios', 'url' => '../modulos/ventas/precios.php', 'icono' => 'fa-tags', 'desc' => 'Listas de precios'],
            ['nombre' => 'Configuración Cajas', 'url' => '../modulos/ventas/config_cajas.php', 'icono' => 'fa-cogs', 'desc' => 'Configurar cajas registradoras'],
            ['nombre' => 'Análisis de Ventas', 'url' => '../modulos/ventas/analisis_ventas.php', 'icono' => 'fa-chart-line', 'desc' => 'Análisis y reportes'],
        ]
    ],
    'Compras' => [
        'icon' => 'fa-box',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'Órdenes de Compra', 'url' => '../modulos/compras/ordenes_compra.php', 'icono' => 'fa-shopping-bag', 'desc' => 'Gestión de compras a proveedores'],
        ]
    ],
    'Materiales (MM)' => [
        'icon' => 'fa-cubes',
        'color' => 'warning',
        'modulos' => [
            ['nombre' => 'Maestro de Materiales', 'url' => '../modulos/materiales/maestro_materiales.php', 'icono' => 'fa-database', 'desc' => 'Catálogo de materiales'],
            ['nombre' => 'Inventario', 'url' => '../modulos/materiales/inventario.php', 'icono' => 'fa-boxes', 'desc' => 'Gestión de inventario'],
            ['nombre' => 'Almacenes', 'url' => '../modulos/materiales/almacenes.php', 'icono' => 'fa-warehouse', 'desc' => 'Gestión de almacenes'],
            ['nombre' => 'Compras MM', 'url' => '../modulos/materiales/compras.php', 'icono' => 'fa-shopping-cart', 'desc' => 'Gestión de compras'],
            ['nombre' => 'Planificación MRP', 'url' => '../modulos/materiales/planificacion.php', 'icono' => 'fa-calendar-alt', 'desc' => 'Planificación de requerimientos'],
            ['nombre' => 'Evaluación Proveedores', 'url' => '../modulos/materiales/evaluacion_proveedores.php', 'icono' => 'fa-star', 'desc' => 'Evaluación y calificación'],
            ['nombre' => 'Control de Calidad', 'url' => '../modulos/materiales/calidad.php', 'icono' => 'fa-check-circle', 'desc' => 'Control de calidad de materiales'],
            ['nombre' => 'Verificación Facturas', 'url' => '../modulos/materiales/verificacion_facturas.php', 'icono' => 'fa-file-invoice', 'desc' => 'Verificación de facturas'],
        ]
    ],
    'Producción (PP)' => [
        'icon' => 'fa-industry',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Dashboard Producción', 'url' => '../modulos/produccion/dashboard_produccion.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Panel de producción'],
            ['nombre' => 'Órdenes de Fabricación', 'url' => '../modulos/produccion/ordenes_fabricacion.php', 'icono' => 'fa-tasks', 'desc' => 'Órdenes de producción'],
            ['nombre' => 'BOM (Lista Materiales)', 'url' => '../modulos/produccion/bom.php', 'icono' => 'fa-list', 'desc' => 'Bill of Materials'],
            ['nombre' => 'Rutas de Producción', 'url' => '../modulos/produccion/rutas_produccion.php', 'icono' => 'fa-route', 'desc' => 'Rutas y procesos'],
            ['nombre' => 'Centros de Trabajo', 'url' => '../modulos/produccion/centros_trabajo.php', 'icono' => 'fa-tools', 'desc' => 'Gestión de centros'],
            ['nombre' => 'Control de Planta', 'url' => '../modulos/produccion/control_planta.php', 'icono' => 'fa-building', 'desc' => 'Control de planta'],
            ['nombre' => 'Planificación Capacidad', 'url' => '../modulos/produccion/capacidad.php', 'icono' => 'fa-chart-bar', 'desc' => 'Capacidad productiva'],
            ['nombre' => 'Costos de Producción', 'url' => '../modulos/produccion/costos_produccion.php', 'icono' => 'fa-dollar-sign', 'desc' => 'Análisis de costos'],
            ['nombre' => 'Calidad Producción', 'url' => '../modulos/produccion/calidad_produccion.php', 'icono' => 'fa-certificate', 'desc' => 'Control de calidad'],
            ['nombre' => 'Mantenimiento', 'url' => '../modulos/produccion/mantenimiento.php', 'icono' => 'fa-wrench', 'desc' => 'Mantenimiento de equipos'],
        ]
    ],
    'Finanzas (FI)' => [
        'icon' => 'fa-dollar-sign',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'Contabilidad General', 'url' => '../modulos/finanzas/contabilidad_general.php', 'icono' => 'fa-book', 'desc' => 'Contabilidad general'],
            ['nombre' => 'Cuentas por Cobrar', 'url' => '../modulos/finanzas/cuentas_por_cobrar.php', 'icono' => 'fa-hand-holding-usd', 'desc' => 'Gestión de cobranzas'],
            ['nombre' => 'Cuentas por Pagar', 'url' => '../modulos/finanzas/cuentas_por_pagar.php', 'icono' => 'fa-money-check-alt', 'desc' => 'Gestión de pagos'],
            ['nombre' => 'Tesorería', 'url' => '../modulos/finanzas/tesoreria.php', 'icono' => 'fa-university', 'desc' => 'Gestión de tesorería'],
            ['nombre' => 'Activos Fijos', 'url' => '../modulos/finanzas/activos_fijos.php', 'icono' => 'fa-building', 'desc' => 'Gestión de activos'],
            ['nombre' => 'Presupuestos', 'url' => '../modulos/finanzas/presupuestos.php', 'icono' => 'fa-calculator', 'desc' => 'Control presupuestario'],
            ['nombre' => 'Impuestos', 'url' => '../modulos/finanzas/impuestos.php', 'icono' => 'fa-percentage', 'desc' => 'Gestión tributaria'],
            ['nombre' => 'Consolidación', 'url' => '../modulos/finanzas/consolidacion.php', 'icono' => 'fa-compress', 'desc' => 'Consolidación financiera'],
            ['nombre' => 'IFRS', 'url' => '../modulos/finanzas/ifrs.php', 'icono' => 'fa-globe', 'desc' => 'Normativa internacional'],
            ['nombre' => 'Reporting Financiero', 'url' => '../modulos/finanzas/reporting_financiero.php', 'icono' => 'fa-file-alt', 'desc' => 'Informes financieros'],
            ['nombre' => 'Comprobantes y Facturas', 'url' => '../modulos/finanzas/comprobantes_facturas.php', 'icono' => 'fa-receipt', 'desc' => 'Gestión de documentos'],
        ]
    ],
    'Controlling (CO)' => [
        'icon' => 'fa-chart-pie',
        'color' => 'info',
        'modulos' => [
            ['nombre' => 'Dashboard Controlling', 'url' => '../modulos/controlling/dashboard_controlling.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Panel de control'],
            ['nombre' => 'Centros de Costo', 'url' => '../modulos/controlling/centros_costo.php', 'icono' => 'fa-sitemap', 'desc' => 'Gestión de centros'],
            ['nombre' => 'Control Presupuestario', 'url' => '../modulos/controlling/control_presupuestario.php', 'icono' => 'fa-balance-scale', 'desc' => 'Control de presupuestos'],
            ['nombre' => 'Costos de Productos', 'url' => '../modulos/controlling/costos_productos.php', 'icono' => 'fa-box-open', 'desc' => 'Cálculo de costos'],
            ['nombre' => 'Análisis de Rentabilidad', 'url' => '../modulos/controlling/analisis_rentabilidad.php', 'icono' => 'fa-chart-line', 'desc' => 'Análisis de rentabilidad'],
            ['nombre' => 'Análisis de Variaciones', 'url' => '../modulos/controlling/analisis_variaciones.php', 'icono' => 'fa-chart-bar', 'desc' => 'Variaciones presupuestarias'],
            ['nombre' => 'Indicadores KPI', 'url' => '../modulos/controlling/indicadores_kpi.php', 'icono' => 'fa-signal', 'desc' => 'Indicadores clave'],
            ['nombre' => 'Reportes de Gestión', 'url' => '../modulos/controlling/reportes_gestion.php', 'icono' => 'fa-file-pdf', 'desc' => 'Reportes gerenciales'],
        ]
    ],
    'Recursos Humanos (HCM)' => [
        'icon' => 'fa-user-friends',
        'color' => 'primary',
        'modulos' => [
            ['nombre' => 'Dashboard RRHH', 'url' => '../modulos/rrhh/dashboard_rrhh.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Panel de RRHH'],
            ['nombre' => 'Asistencia', 'url' => '../modulos/rrhh/asistencia.php', 'icono' => 'fa-calendar-check', 'desc' => 'Control de asistencia'],
            ['nombre' => 'Nómina', 'url' => '../modulos/rrhh/nomina.php', 'icono' => 'fa-money-bill-wave', 'desc' => 'Gestión de nómina'],
            ['nombre' => 'Vacaciones', 'url' => '../modulos/rrhh/vacaciones.php', 'icono' => 'fa-umbrella-beach', 'desc' => 'Gestión de vacaciones'],
            ['nombre' => 'Capacitación', 'url' => '../modulos/rrhh/capacitacion.php', 'icono' => 'fa-graduation-cap', 'desc' => 'Planes de capacitación'],
            ['nombre' => 'Evaluación Desempeño', 'url' => '../modulos/rrhh/evaluacion_desempeno.php', 'icono' => 'fa-star', 'desc' => 'Evaluación de personal'],
            ['nombre' => 'Reclutamiento', 'url' => '../modulos/rrhh/reclutamiento.php', 'icono' => 'fa-user-plus', 'desc' => 'Selección de personal'],
            ['nombre' => 'Organigrama', 'url' => '../modulos/rrhh/organigrama.php', 'icono' => 'fa-sitemap', 'desc' => 'Estructura organizacional'],
            ['nombre' => 'Beneficios', 'url' => '../modulos/rrhh/beneficios.php', 'icono' => 'fa-gift', 'desc' => 'Beneficios corporativos'],
            ['nombre' => 'Documentos RRHH', 'url' => '../modulos/rrhh/documentos_rrhh.php', 'icono' => 'fa-folder', 'desc' => 'Gestión documental'],
            ['nombre' => 'Seguridad y Salud', 'url' => '../modulos/rrhh/seguridad_salud.php', 'icono' => 'fa-medkit', 'desc' => 'Seguridad laboral'],
        ]
    ],
    'Reloj Control' => [
        'icon' => 'fa-clock',
        'color' => 'info',
        'modulos' => [
            ['nombre' => 'Marcajes', 'url' => '../modulos/reloj/marcajes.php', 'icono' => 'fa-fingerprint', 'desc' => 'Control de marcajes'],
            ['nombre' => 'Dispositivos Biométricos', 'url' => '../modulos/reloj/dispositivos.php', 'icono' => 'fa-tablet-alt', 'desc' => 'Gestión de dispositivos'],
            ['nombre' => 'Turnos', 'url' => '../modulos/reloj/turnos.php', 'icono' => 'fa-user-clock', 'desc' => 'Gestión de turnos'],
            ['nombre' => 'Horarios', 'url' => '../modulos/reloj/horarios.php', 'icono' => 'fa-calendar-alt', 'desc' => 'Horarios de trabajo'],
        ]
    ],
    'CRM' => [
        'icon' => 'fa-handshake',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'Dashboard CRM', 'url' => '../modulos/crm/dashboard_crm.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Panel CRM'],
            ['nombre' => 'Cuentas', 'url' => '../modulos/crm/cuentas.php', 'icono' => 'fa-building', 'desc' => 'Gestión de cuentas'],
            ['nombre' => 'Contactos', 'url' => '../modulos/crm/contactos.php', 'icono' => 'fa-users', 'desc' => 'Base de contactos'],
            ['nombre' => 'Oportunidades', 'url' => '../modulos/crm/oportunidades.php', 'icono' => 'fa-bullseye', 'desc' => 'Gestión de oportunidades'],
            ['nombre' => 'Pipeline', 'url' => '../modulos/crm/pipeline.php', 'icono' => 'fa-funnel-dollar', 'desc' => 'Embudo de ventas'],
            ['nombre' => 'Actividades', 'url' => '../modulos/crm/actividades.php', 'icono' => 'fa-tasks', 'desc' => 'Seguimiento de actividades'],
            ['nombre' => 'Campañas', 'url' => '../modulos/crm/campanas.php', 'icono' => 'fa-bullhorn', 'desc' => 'Campañas de marketing'],
            ['nombre' => 'Análisis CRM', 'url' => '../modulos/crm/analisis_crm.php', 'icono' => 'fa-chart-line', 'desc' => 'Análisis y reportes'],
        ]
    ],
    'SCM (Supply Chain)' => [
        'icon' => 'fa-truck',
        'color' => 'warning',
        'modulos' => [
            ['nombre' => 'Dashboard SCM', 'url' => '../modulos/scm/dashboard_scm.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Panel de cadena suministro'],
            ['nombre' => 'Planificación Demanda', 'url' => '../modulos/scm/planificacion_demanda.php', 'icono' => 'fa-chart-area', 'desc' => 'Forecast de demanda'],
            ['nombre' => 'Inventario SCM', 'url' => '../modulos/scm/gestion_inventario_scm.php', 'icono' => 'fa-boxes', 'desc' => 'Gestión de inventarios'],
            ['nombre' => 'Logística', 'url' => '../modulos/scm/logistica.php', 'icono' => 'fa-shipping-fast', 'desc' => 'Gestión logística'],
            ['nombre' => 'Distribución', 'url' => '../modulos/scm/distribucion.php', 'icono' => 'fa-route', 'desc' => 'Gestión de distribución'],
            ['nombre' => 'Transporte', 'url' => '../modulos/scm/transporte.php', 'icono' => 'fa-truck-moving', 'desc' => 'Gestión de transporte'],
            ['nombre' => 'Trazabilidad', 'url' => '../modulos/scm/trazabilidad.php', 'icono' => 'fa-search-location', 'desc' => 'Trazabilidad de productos'],
            ['nombre' => 'Integración Proveedores', 'url' => '../modulos/scm/integracion_proveedores.php', 'icono' => 'fa-link', 'desc' => 'Colaboración con proveedores'],
            ['nombre' => 'Colaboración Cadena', 'url' => '../modulos/scm/colaboracion_cadena.php', 'icono' => 'fa-network-wired', 'desc' => 'Colaboración en cadena'],
            ['nombre' => 'KPI SCM', 'url' => '../modulos/scm/kpi_scm.php', 'icono' => 'fa-chart-line', 'desc' => 'Indicadores SCM'],
        ]
    ],
    'Proyectos' => [
        'icon' => 'fa-project-diagram',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Proyectos', 'url' => '../modulos/proyectos/proyectos.php', 'icono' => 'fa-folder-open', 'desc' => 'Gestión de proyectos'],
            ['nombre' => 'Tareas', 'url' => '../modulos/proyectos/tareas.php', 'icono' => 'fa-tasks', 'desc' => 'Kanban/Gantt de tareas'],
            ['nombre' => 'Hitos', 'url' => '../modulos/proyectos/hitos.php', 'icono' => 'fa-flag-checkered', 'desc' => 'Hitos y entregables'],
        ]
    ],
    'Ecommerce' => [
        'icon' => 'fa-store',
        'color' => 'primary',
        'modulos' => [
            ['nombre' => 'Tienda Online', 'url' => '../modulos/ecommerce/tienda.php', 'icono' => 'fa-shopping-bag', 'desc' => 'Gestión de tienda'],
            ['nombre' => 'Pedidos Web', 'url' => '../modulos/ecommerce/pedidos.php', 'icono' => 'fa-shopping-cart', 'desc' => 'Pedidos online'],
            ['nombre' => 'Carritos Abandonados', 'url' => '../modulos/ecommerce/carritos.php', 'icono' => 'fa-cart-plus', 'desc' => 'Recuperar ventas'],
            ['nombre' => 'Métodos de Pago', 'url' => '../modulos/ecommerce/metodos_pago.php', 'icono' => 'fa-credit-card', 'desc' => 'Configurar pagos'],
            ['nombre' => 'Métodos de Envío', 'url' => '../modulos/ecommerce/envios.php', 'icono' => 'fa-shipping-fast', 'desc' => 'Chilexpress, Correos'],
        ]
    ],
    'Fidelización' => [
        'icon' => 'fa-heart',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Sistema de Puntos', 'url' => '../modulos/fidelizacion/sistema_puntos.php', 'icono' => 'fa-coins', 'desc' => 'Programa de puntos'],
            ['nombre' => 'Programas de Lealtad', 'url' => '../modulos/fidelizacion/programas_lealtad.php', 'icono' => 'fa-award', 'desc' => 'Programas de fidelización'],
            ['nombre' => 'Recompensas', 'url' => '../modulos/fidelizacion/recompensas.php', 'icono' => 'fa-gift', 'desc' => 'Catálogo de premios'],
            ['nombre' => 'Segmentación', 'url' => '../modulos/fidelizacion/segmentacion.php', 'icono' => 'fa-users-cog', 'desc' => 'Segmentación de clientes'],
            ['nombre' => 'Campañas Personalizadas', 'url' => '../modulos/fidelizacion/campanas_personalizadas.php', 'icono' => 'fa-bullhorn', 'desc' => 'Marketing dirigido'],
            ['nombre' => 'Comentarios', 'url' => '../modulos/fidelizacion/comentarios.php', 'icono' => 'fa-comments', 'desc' => 'Feedback de clientes'],
            ['nombre' => 'Análisis Fidelización', 'url' => '../modulos/fidelizacion/analisis_fidelizacion.php', 'icono' => 'fa-chart-pie', 'desc' => 'Análisis de programas'],
        ]
    ],
    'Business Intelligence' => [
        'icon' => 'fa-chart-bar',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'Cuadro de Mando', 'url' => '../modulos/bi/cuadro_mando.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Balanced Scorecard'],
            ['nombre' => 'Dashboards BI', 'url' => '../modulos/bi/dashboards.php', 'icono' => 'fa-chart-line', 'desc' => 'Dashboards ejecutivos'],
            ['nombre' => 'KPIs Empresariales', 'url' => '../modulos/bi/kpis_empresariales.php', 'icono' => 'fa-signal', 'desc' => 'Indicadores clave'],
            ['nombre' => 'Análisis de Ventas', 'url' => '../modulos/bi/analisis_ventas_bi.php', 'icono' => 'fa-shopping-cart', 'desc' => 'Analytics de ventas'],
            ['nombre' => 'Análisis de Compras', 'url' => '../modulos/bi/analisis_compras.php', 'icono' => 'fa-box', 'desc' => 'Analytics de compras'],
            ['nombre' => 'Análisis de Clientes', 'url' => '../modulos/bi/analisis_clientes.php', 'icono' => 'fa-users', 'desc' => 'Segmentación y RFM'],
            ['nombre' => 'Análisis de Productos', 'url' => '../modulos/bi/analisis_productos.php', 'icono' => 'fa-barcode', 'desc' => 'Analytics de productos'],
            ['nombre' => 'Análisis de Inventario', 'url' => '../modulos/bi/analisis_inventario.php', 'icono' => 'fa-boxes', 'desc' => 'Analytics de stock'],
            ['nombre' => 'Análisis Financiero', 'url' => '../modulos/bi/analisis_financiero.php', 'icono' => 'fa-dollar-sign', 'desc' => 'Ratios financieros'],
            ['nombre' => 'Análisis de Tendencias', 'url' => '../modulos/bi/analisis_tendencias.php', 'icono' => 'fa-chart-area', 'desc' => 'Tendencias y patrones'],
            ['nombre' => 'Forecasting', 'url' => '../modulos/bi/forecasting.php', 'icono' => 'fa-crystal-ball', 'desc' => 'Predicción de demanda'],
            ['nombre' => 'Data Mining', 'url' => '../modulos/bi/data_mining.php', 'icono' => 'fa-database', 'desc' => 'Minería de datos'],
            ['nombre' => 'OLAP', 'url' => '../modulos/bi/olap.php', 'icono' => 'fa-cube', 'desc' => 'Análisis multidimensional'],
            ['nombre' => 'Reportes Personalizados', 'url' => '../modulos/bi/reportes_personalizados.php', 'icono' => 'fa-file-alt', 'desc' => 'Constructor de reportes'],
            ['nombre' => 'Exportación Datos', 'url' => '../modulos/bi/exportacion_datos.php', 'icono' => 'fa-download', 'desc' => 'Export Excel/CSV'],
        ]
    ],
    'BI Avanzado' => [
        'icon' => 'fa-brain',
        'color' => 'info',
        'modulos' => [
            ['nombre' => 'KPIs', 'url' => '../modulos/bi_avanzado/kpis.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Indicadores con metas'],
            ['nombre' => 'Métricas Tiempo Real', 'url' => '../modulos/bi_avanzado/metricas.php', 'icono' => 'fa-chart-line', 'desc' => 'Métricas en vivo'],
            ['nombre' => 'Dashboards Personalizados', 'url' => '../modulos/bi_avanzado/dashboards_personalizados.php', 'icono' => 'fa-chart-pie', 'desc' => 'Dashboards custom'],
        ]
    ],
    'Contabilidad' => [
        'icon' => 'fa-calculator',
        'color' => 'secondary',
        'modulos' => [
            ['nombre' => 'Plan de Cuentas', 'url' => '../modulos/contabilidad/plan_cuentas.php', 'icono' => 'fa-list-ol', 'desc' => 'Plan contable chileno'],
            ['nombre' => 'Asientos Contables', 'url' => '../modulos/contabilidad/asientos.php', 'icono' => 'fa-book', 'desc' => 'Registro de asientos'],
        ]
    ],
    'Facturación Electrónica' => [
        'icon' => 'fa-file-invoice',
        'color' => 'primary',
        'modulos' => [
            ['nombre' => 'Gestión de Folios', 'url' => '../modulos/facturacion/folios.php', 'icono' => 'fa-hashtag', 'desc' => 'Folios DTE'],
            ['nombre' => 'Cargar CAF', 'url' => '../modulos/facturacion/cargar_caf.php', 'icono' => 'fa-upload', 'desc' => 'Archivo de autorización'],
            ['nombre' => 'Estado Folios API', 'url' => '../modulos/facturacion/api/estado_folios.php', 'icono' => 'fa-check-circle', 'desc' => 'Consulta estado'],
        ]
    ],
    'Inventario' => [
        'icon' => 'fa-boxes',
        'color' => 'warning',
        'modulos' => [
            ['nombre' => 'Productos', 'url' => '../modulos/inventario/productos.php', 'icono' => 'fa-barcode', 'desc' => 'Catálogo de productos'],
        ]
    ],
    'Entidades Maestras' => [
        'icon' => 'fa-database',
        'color' => 'info',
        'modulos' => [
            ['nombre' => 'Entidades Maestras', 'url' => '../modulos/entidades/entidades_maestras.php', 'icono' => 'fa-server', 'desc' => 'Maestros del sistema'],
            ['nombre' => 'Gestión de Clientes', 'url' => '../modulos/entidades/gestion_clientes.php', 'icono' => 'fa-user-tie', 'desc' => 'Base de clientes'],
            ['nombre' => 'Gestión de Proveedores', 'url' => '../modulos/entidades/gestion_proveedores.php', 'icono' => 'fa-building', 'desc' => 'Base de proveedores'],
            ['nombre' => 'Gestión de Empleados', 'url' => '../modulos/entidades/gestion_empleados.php', 'icono' => 'fa-id-badge', 'desc' => 'Base de empleados'],
            ['nombre' => 'Productos y Servicios', 'url' => '../modulos/entidades/productos_servicios.php', 'icono' => 'fa-shopping-basket', 'desc' => 'Catálogo general'],
        ]
    ],
    'Calidad' => [
        'icon' => 'fa-certificate',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'Control de Calidad', 'url' => '../modulos/calidad/control.php', 'icono' => 'fa-clipboard-check', 'desc' => 'Inspecciones ISO'],
        ]
    ],
    'Mantenimiento' => [
        'icon' => 'fa-tools',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Órdenes de Trabajo', 'url' => '../modulos/mantenimiento/ordenes.php', 'icono' => 'fa-wrench', 'desc' => 'Preventivo/Correctivo'],
        ]
    ],
    'API & Integraciones' => [
        'icon' => 'fa-plug',
        'color' => 'secondary',
        'modulos' => [
            ['nombre' => 'Tokens API', 'url' => '../modulos/api/tokens.php', 'icono' => 'fa-key', 'desc' => 'Tokens de acceso REST'],
            ['nombre' => 'Webhooks', 'url' => '../modulos/api/webhooks.php', 'icono' => 'fa-link', 'desc' => 'Webhooks de eventos'],
        ]
    ],
    'Reportes' => [
        'icon' => 'fa-file-pdf',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Reportes de Ventas', 'url' => '../modulos/reportes/ventas.php', 'icono' => 'fa-chart-line', 'desc' => 'Informes de ventas'],
            ['nombre' => 'Reportes de Compras', 'url' => '../modulos/reportes/compras.php', 'icono' => 'fa-shopping-bag', 'desc' => 'Informes de compras'],
            ['nombre' => 'Reportes Personalizados', 'url' => '../modulos/reportes/personalizados.php', 'icono' => 'fa-file-alt', 'desc' => 'Reportes SQL custom'],
        ]
    ],
    'Tributario (Chile)' => [
        'icon' => 'fa-landmark',
        'color' => 'primary',
        'modulos' => [
            ['nombre' => 'Gestión de Folios', 'url' => '../modulos/tributario/folios.php', 'icono' => 'fa-hashtag', 'desc' => 'Folios SII'],
        ]
    ],
    'Configuración' => [
        'icon' => 'fa-cog',
        'color' => 'dark',
        'modulos' => [
            ['nombre' => 'Configuración Empresa', 'url' => '../modulos/configuracion/empresa.php', 'icono' => 'fa-building', 'desc' => 'Datos fiscales y SII'],
            ['nombre' => 'Sucursales', 'url' => '../modulos/configuracion/sucursales.php', 'icono' => 'fa-map-marker-alt', 'desc' => 'Sucursales y locales'],
            ['nombre' => 'Tipos de Cambio', 'url' => '../modulos/configuracion/tipos_cambio.php', 'icono' => 'fa-exchange-alt', 'desc' => 'USD, EUR, UF, UTM'],
            ['nombre' => 'Monedas', 'url' => '../modulos/configuracion/monedas.php', 'icono' => 'fa-coins', 'desc' => 'Monedas del sistema'],
            ['nombre' => 'Impuestos', 'url' => '../modulos/configuracion/impuestos.php', 'icono' => 'fa-percentage', 'desc' => 'IVA y retenciones'],
            ['nombre' => 'Sistema', 'url' => '../modulos/configuracion/sistema.php', 'icono' => 'fa-sliders-h', 'desc' => 'Configuración general'],
        ]
    ],
    'Administración' => [
        'icon' => 'fa-user-shield',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Gestión de Empresas', 'url' => '../modulos/administracion/gestion_empresas.php', 'icono' => 'fa-building', 'desc' => 'Multi-empresa'],
            ['nombre' => 'Seguridad', 'url' => '../modulos/administracion/gestion_seguridad.php', 'icono' => 'fa-shield-alt', 'desc' => 'Usuarios y permisos'],
            ['nombre' => 'Parametrización Global', 'url' => '../modulos/administracion/parametrizacion_global.php', 'icono' => 'fa-cogs', 'desc' => 'Parámetros del sistema'],
        ]
    ],
];

// Contar módulos totales
$total_modulos = 0;
$total_categorias = count($modulos_sistema);
foreach ($modulos_sistema as $categoria => $datos) {
    $total_modulos += count($datos['modulos']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ERP - CONECTA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --header-height: 60px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-bg: #1a1d29;
            --sidebar-hover: #2d3142;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s;
        }

        .toggle-sidebar:hover {
            transform: scale(1.1);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--sidebar-bg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 999;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }

        .sidebar-search {
            padding: 15px;
            display: block;
        }

        .sidebar.collapsed .sidebar-search {
            display: none;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 14px;
        }

        .search-input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .search-input:focus {
            outline: none;
            background: rgba(255,255,255,0.15);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .menu-category {
            margin-bottom: 5px;
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
        }

        .category-header:hover {
            background: var(--sidebar-hover);
        }

        .category-icon {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .sidebar.collapsed .category-text {
            display: none;
        }

        .category-arrow {
            transition: transform 0.3s;
            font-size: 12px;
        }

        .sidebar.collapsed .category-arrow {
            display: none;
        }

        .menu-category.active .category-arrow {
            transform: rotate(180deg);
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: rgba(0,0,0,0.2);
        }

        .menu-category.active .submenu {
            max-height: 1000px;
        }

        .sidebar.collapsed .submenu {
            position: absolute;
            left: var(--sidebar-collapsed-width);
            width: 250px;
            background: var(--sidebar-bg);
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            border-radius: 0 8px 8px 0;
            max-height: none !important;
            display: none;
        }

        .sidebar.collapsed .menu-category:hover .submenu {
            display: block;
        }

        .submenu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px 10px 50px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 13px;
        }

        .sidebar.collapsed .submenu-item {
            padding: 10px 20px;
        }

        .submenu-item:hover {
            background: var(--sidebar-hover);
            color: white;
            padding-left: 55px;
        }

        .sidebar.collapsed .submenu-item:hover {
            padding-left: 25px;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 30px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Dashboard Cards */
        .dashboard-header {
            margin-bottom: 30px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-change {
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .stat-change.positive {
            background: #d4edda;
            color: #155724;
        }

        .stat-change.negative {
            background: #f8d7da;
            color: #721c24;
        }

        /* Charts Section */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-btn {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 12px;
        }

        .chart-btn:hover {
            background: #f7fafc;
            border-color: var(--primary-color);
        }

        /* Tables */
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table thead th {
            background: #f7fafc;
            padding: 12px 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }

        .custom-table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #2d3748;
        }

        .custom-table tbody tr:hover {
            background: #f7fafc;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show-mobile {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-link"></i>
                </div>
                <div>
                    <h5 class="mb-0" style="color: var(--primary-color); font-weight: bold;">CONECTA ERP</h5>
                    <small class="text-muted" style="font-size: 11px;">Sistema Integral de Gestión</small>
                </div>
            </div>
        </div>
        <div class="header-right">
            <button class="btn btn-sm btn-outline-primary">
                <i class="fas fa-bell"></i>
                <span class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 10px;">3</span>
            </button>
            <div class="user-info">
                <div class="text-end">
                    <div style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($usuario_nombre) ?></div>
                    <small class="text-muted" style="font-size: 11px;">Anaconda Web S.A.</small>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr($usuario_nombre, 0, 1)) ?>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle p-0" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Perfil</a></li>
                        <li><a class="dropdown-item" href="../modulos/configuracion/empresa.php"><i class="fas fa-cog"></i> Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-search">
            <input type="text" class="search-input" id="sidebarSearch" placeholder="Buscar módulo...">
        </div>
        <ul class="sidebar-menu">
            <?php foreach ($modulos_sistema as $categoria => $datos): ?>
            <li class="menu-category" data-categoria="<?= htmlspecialchars(strtolower($categoria)) ?>">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas <?= $datos['icon'] ?>"></i>
                        <span class="category-text"><?= $categoria ?></span>
                    </div>
                    <i class="fas fa-chevron-down category-arrow"></i>
                </div>
                <div class="submenu">
                    <?php foreach ($datos['modulos'] as $modulo): ?>
                    <a href="<?= $modulo['url'] ?>" class="submenu-item"
                       data-nombre="<?= htmlspecialchars(strtolower($modulo['nombre'])) ?>"
                       title="<?= $modulo['desc'] ?>">
                        <i class="fas <?= $modulo['icono'] ?>"></i>
                        <span><?= $modulo['nombre'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="dashboard-header animate-fade-in">
            <h2><i class="fas fa-tachometer-alt" style="color: var(--primary-color);"></i> Dashboard General</h2>
            <p class="text-muted">Bienvenido al panel de control de CONECTA ERP</p>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card animate-fade-in" style="animation-delay: 0.1s;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value">$2,847,250</div>
                <div class="stat-label">Ventas del Mes</div>
                <span class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +12.5%
                </span>
            </div>

            <div class="stat-card animate-fade-in" style="animation-delay: 0.2s;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">1,249</div>
                <div class="stat-label">Clientes Activos</div>
                <span class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +8.2%
                </span>
            </div>

            <div class="stat-card animate-fade-in" style="animation-delay: 0.3s;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-value">3,847</div>
                <div class="stat-label">Productos en Stock</div>
                <span class="stat-change negative">
                    <i class="fas fa-arrow-down"></i> -2.3%
                </span>
            </div>

            <div class="stat-card animate-fade-in" style="animation-delay: 0.4s;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="stat-value">142</div>
                <div class="stat-label">Facturas Pendientes</div>
                <span class="stat-change positive">
                    <i class="fas fa-arrow-down"></i> -15.7%
                </span>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card animate-fade-in" style="animation-delay: 0.5s;">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-line"></i> Ventas Mensuales</h3>
                    <div class="chart-actions">
                        <button class="chart-btn"><i class="fas fa-download"></i> Exportar</button>
                    </div>
                </div>
                <canvas id="salesChart"></canvas>
            </div>

            <div class="chart-card animate-fade-in" style="animation-delay: 0.6s;">
                <div class="chart-header">
                    <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Distribución por Categoría</h3>
                    <div class="chart-actions">
                        <button class="chart-btn"><i class="fas fa-download"></i> Exportar</button>
                    </div>
                </div>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Additional Chart -->
        <div class="chart-card animate-fade-in" style="animation-delay: 0.7s;">
            <div class="chart-header">
                <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Comparativa Anual</h3>
                <div class="chart-actions">
                    <button class="chart-btn">2023</button>
                    <button class="chart-btn active">2024</button>
                    <button class="chart-btn"><i class="fas fa-download"></i></button>
                </div>
            </div>
            <canvas id="comparisonChart"></canvas>
        </div>

        <!-- Tables -->
        <div class="table-card animate-fade-in" style="animation-delay: 0.8s;">
            <div class="table-header">
                <h3 class="chart-title"><i class="fas fa-clipboard-list"></i> Últimas Transacciones</h3>
                <button class="chart-btn"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#10234</td>
                        <td>Empresa ABC Ltda.</td>
                        <td>Sistema ERP Premium</td>
                        <td>$1,250,000</td>
                        <td><span class="badge badge-success">Completado</span></td>
                        <td>2024-01-15</td>
                    </tr>
                    <tr>
                        <td>#10235</td>
                        <td>Comercial XYZ S.A.</td>
                        <td>Módulo Inventario</td>
                        <td>$450,000</td>
                        <td><span class="badge badge-warning">Pendiente</span></td>
                        <td>2024-01-14</td>
                    </tr>
                    <tr>
                        <td>#10236</td>
                        <td>Distribuidora 123</td>
                        <td>Licencia Anual</td>
                        <td>$890,000</td>
                        <td><span class="badge badge-success">Completado</span></td>
                        <td>2024-01-14</td>
                    </tr>
                    <tr>
                        <td>#10237</td>
                        <td>Retail Store S.A.</td>
                        <td>Sistema POS</td>
                        <td>$2,100,000</td>
                        <td><span class="badge badge-warning">En Proceso</span></td>
                        <td>2024-01-13</td>
                    </tr>
                    <tr>
                        <td>#10238</td>
                        <td>Industrias Chile</td>
                        <td>Módulo Producción</td>
                        <td>$1,750,000</td>
                        <td><span class="badge badge-danger">Rechazado</span></td>
                        <td>2024-01-13</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Additional Table -->
        <div class="table-card animate-fade-in" style="animation-delay: 0.9s;">
            <div class="table-header">
                <h3 class="chart-title"><i class="fas fa-star"></i> Productos Más Vendidos</h3>
                <button class="chart-btn"><i class="fas fa-download"></i> Exportar</button>
            </div>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Unidades Vendidas</th>
                        <th>Revenue</th>
                        <th>Tendencia</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Sistema ERP Completo</td>
                        <td>Software</td>
                        <td>145</td>
                        <td>$12,450,000</td>
                        <td><span class="stat-change positive"><i class="fas fa-arrow-up"></i> +23%</span></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Módulo Finanzas</td>
                        <td>Software</td>
                        <td>89</td>
                        <td>$4,230,000</td>
                        <td><span class="stat-change positive"><i class="fas fa-arrow-up"></i> +15%</span></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Sistema POS</td>
                        <td>Hardware + Software</td>
                        <td>67</td>
                        <td>$8,920,000</td>
                        <td><span class="stat-change positive"><i class="fas fa-arrow-up"></i> +8%</span></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Módulo RRHH</td>
                        <td>Software</td>
                        <td>52</td>
                        <td>$2,180,000</td>
                        <td><span class="stat-change negative"><i class="fas fa-arrow-down"></i> -3%</span></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>Licencia Enterprise</td>
                        <td>Licenciamiento</td>
                        <td>34</td>
                        <td>$6,540,000</td>
                        <td><span class="stat-change positive"><i class="fas fa-arrow-up"></i> +12%</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // Toggle Category
        document.querySelectorAll('.category-header').forEach(header => {
            header.addEventListener('click', function() {
                const category = this.parentElement;
                const isCollapsed = document.getElementById('sidebar').classList.contains('collapsed');

                if (!isCollapsed) {
                    category.classList.toggle('active');
                }
            });
        });

        // Search Functionality
        document.getElementById('sidebarSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const categories = document.querySelectorAll('.menu-category');

            categories.forEach(category => {
                const categoryName = category.dataset.categoria;
                const submenuItems = category.querySelectorAll('.submenu-item');
                let hasVisibleItems = false;

                submenuItems.forEach(item => {
                    const itemName = item.dataset.nombre;
                    if (itemName.includes(searchTerm) || searchTerm === '') {
                        item.style.display = 'flex';
                        hasVisibleItems = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (hasVisibleItems || searchTerm === '') {
                    category.style.display = 'block';
                    if (searchTerm !== '') {
                        category.classList.add('active');
                    }
                } else {
                    category.style.display = 'none';
                }
            });
        });

        // Charts
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Ventas 2024',
                    data: [1200000, 1900000, 1500000, 2200000, 1800000, 2400000, 2100000, 2600000, 2300000, 2800000, 2500000, 2847250],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ventas', 'Finanzas', 'RRHH', 'Producción', 'Inventario', 'Otros'],
                datasets: [{
                    data: [30, 25, 15, 12, 10, 8],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Comparison Chart
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: ['T1', 'T2', 'T3', 'T4'],
                datasets: [
                    {
                        label: '2023',
                        data: [4500000, 5200000, 4800000, 6100000],
                        backgroundColor: 'rgba(102, 126, 234, 0.5)',
                        borderColor: '#667eea',
                        borderWidth: 2
                    },
                    {
                        label: '2024',
                        data: [5100000, 5900000, 5400000, 6800000],
                        backgroundColor: 'rgba(118, 75, 162, 0.5)',
                        borderColor: '#764ba2',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });

        // Mobile responsiveness
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('mainContent').classList.add('expanded');
        }
    </script>
</body>
</html>
