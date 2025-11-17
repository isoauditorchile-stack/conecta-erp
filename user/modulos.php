<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id, nombre FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$empresa_id = $user['empresa_id'];

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
    <title>Todos los Módulos - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-bottom: 50px; }
        .module-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .category-header {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .search-box {
            border-radius: 50px;
            padding: 12px 24px;
            border: 2px solid #e0e0e0;
        }
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-small {
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-5">
    <!-- Header -->
    <div class="header-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-3">
                    <i class="fas fa-th-large text-primary"></i>
                    Todos los Módulos del Sistema
                </h1>
                <p class="lead text-muted mb-0">
                    Sistema ERP completo con <strong><?= $total_categorias ?> categorías</strong> y
                    <strong><?= $total_modulos ?> módulos profesionales</strong>
                </p>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="mb-0"><i class="fas fa-check-circle"></i> <?= $total_modulos ?></h2>
                    <small>Módulos Activos</small>
                    <div class="stat-small">
                        <i class="fas fa-layer-group"></i> <?= $total_categorias ?> Categorías
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <input type="text" id="searchModules" class="form-control search-box"
                   placeholder="🔍 Buscar módulo por nombre o descripción...">
        </div>

        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
            <button class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync"></i> Recargar
            </button>
        </div>
    </div>

    <!-- Módulos por Categoría -->
    <div id="modulesContainer">
    <?php foreach ($modulos_sistema as $categoria => $datos): ?>
        <div class="categoria-section mb-5" data-categoria="<?= strtolower($categoria) ?>">
            <div class="category-header">
                <h3 class="mb-0">
                    <i class="fas <?= $datos['icon'] ?> text-<?= $datos['color'] ?>"></i>
                    <?= $categoria ?>
                    <span class="badge bg-<?= $datos['color'] ?> float-end"><?= count($datos['modulos']) ?></span>
                </h3>
            </div>

            <div class="row g-3">
                <?php foreach ($datos['modulos'] as $modulo): ?>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 modulo-item"
                     data-nombre="<?= strtolower($modulo['nombre']) ?>"
                     data-desc="<?= strtolower($modulo['desc']) ?>">
                    <div class="card module-card" onclick="window.location.href='<?= $modulo['url'] ?>'">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas <?= $modulo['icono'] ?> fa-3x text-<?= $datos['color'] ?>"></i>
                            </div>
                            <h6 class="card-title"><?= $modulo['nombre'] ?></h6>
                            <p class="card-text text-muted small"><?= $modulo['desc'] ?></p>
                            <a href="<?= $modulo['url'] ?>" class="btn btn-sm btn-<?= $datos['color'] ?>">
                                Abrir <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- No results -->
    <div id="noResults" class="text-center text-white" style="display: none;">
        <i class="fas fa-search fa-5x mb-3 opacity-50"></i>
        <h3>No se encontraron módulos</h3>
        <p>Intenta con otros términos de búsqueda</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Búsqueda en tiempo real
document.getElementById('searchModules').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const categorias = document.querySelectorAll('.categoria-section');
    const modulos = document.querySelectorAll('.modulo-item');
    let hasResults = false;

    if (searchTerm === '') {
        // Mostrar todo
        categorias.forEach(cat => cat.style.display = 'block');
        modulos.forEach(mod => mod.style.display = 'block');
        document.getElementById('noResults').style.display = 'none';
        return;
    }

    // Filtrar módulos
    modulos.forEach(modulo => {
        const nombre = modulo.dataset.nombre;
        const desc = modulo.dataset.desc;

        if (nombre.includes(searchTerm) || desc.includes(searchTerm)) {
            modulo.style.display = 'block';
            hasResults = true;
        } else {
            modulo.style.display = 'none';
        }
    });

    // Mostrar/ocultar categorías vacías
    categorias.forEach(cat => {
        const visibleModulos = cat.querySelectorAll('.modulo-item[style="display: block;"]').length;
        cat.style.display = visibleModulos > 0 ? 'block' : 'none';
    });

    // Mostrar mensaje de no resultados
    document.getElementById('noResults').style.display = hasResults ? 'none' : 'block';
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
