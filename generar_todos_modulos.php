<?php
/**
 * GENERADOR MASIVO DE MÓDULOS - CONECTA ERP
 * Genera automáticamente TODOS los módulos del sistema
 * Ejecutar: php generar_todos_modulos.php
 */

// Configuración de todos los módulos
$modulos = [
    // VENTAS (10 módulos)
    'ventas' => [
        ['nombre' => 'Dashboard Ventas', 'archivo' => 'dashboard_ventas.php', 'tipo' => 'dashboard', 'icono' => 'chart-line', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Facturación', 'archivo' => 'facturacion.php', 'tipo' => 'crud', 'tabla' => 'documentos_tributarios', 'icono' => 'file-invoice-dollar', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Cotizaciones', 'archivo' => 'cotizaciones.php', 'tipo' => 'crud', 'tabla' => 'cotizaciones', 'icono' => 'file-alt', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Pedidos', 'archivo' => 'pedidos.php', 'tipo' => 'crud', 'tabla' => 'ordenes_venta', 'icono' => 'shopping-bag', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Punto de Venta (POS)', 'archivo' => 'pos.php', 'tipo' => 'pos', 'icono' => 'cash-register', 'color' => '667eea,764ba2'],
        ['nombre' => 'Devoluciones', 'archivo' => 'devoluciones.php', 'tipo' => 'crud', 'tabla' => 'devoluciones', 'icono' => 'undo', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Promociones', 'archivo' => 'promociones.php', 'tipo' => 'crud', 'tabla' => 'promociones', 'icono' => 'tags', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Gestión de Precios', 'archivo' => 'precios.php', 'tipo' => 'crud', 'tabla' => 'listas_precios', 'icono' => 'dollar-sign', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Configuración Cajas', 'archivo' => 'config_cajas.php', 'tipo' => 'config', 'icono' => 'cog', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Análisis de Ventas', 'archivo' => 'analisis_ventas.php', 'tipo' => 'analytics', 'icono' => 'chart-pie', 'color' => 'fa709a,fee140'],
    ],

    // COMPRAS (1 módulo)
    'compras' => [
        ['nombre' => 'Órdenes de Compra', 'archivo' => 'ordenes_compra.php', 'tipo' => 'crud', 'tabla' => 'ordenes_compra', 'icono' => 'shopping-cart', 'color' => '4facfe,00f2fe'],
    ],

    // MATERIALES (8 módulos)
    'materiales' => [
        ['nombre' => 'Maestro de Materiales', 'archivo' => 'maestro_materiales.php', 'tipo' => 'crud', 'tabla' => 'productos', 'icono' => 'boxes', 'color' => '667eea,764ba2'],
        ['nombre' => 'Inventario', 'archivo' => 'inventario.php', 'tipo' => 'crud', 'tabla' => 'productos', 'icono' => 'warehouse', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Almacenes', 'archivo' => 'almacenes.php', 'tipo' => 'crud', 'tabla' => 'almacenes', 'icono' => 'building', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Compras MM', 'archivo' => 'compras_mm.php', 'tipo' => 'crud', 'tabla' => 'compras_materiales', 'icono' => 'truck', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Planificación MRP', 'archivo' => 'mrp.php', 'tipo' => 'analytics', 'icono' => 'project-diagram', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Evaluación Proveedores', 'archivo' => 'eval_proveedores.php', 'tipo' => 'analytics', 'icono' => 'star', 'color' => '667eea,764ba2'],
        ['nombre' => 'Control de Calidad', 'archivo' => 'control_calidad.php', 'tipo' => 'crud', 'tabla' => 'control_calidad', 'icono' => 'check-circle', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Verificación Facturas', 'archivo' => 'verificacion_facturas.php', 'tipo' => 'crud', 'tabla' => 'verificacion_facturas', 'icono' => 'file-invoice', 'color' => 'f093fb,f5576c'],
    ],

    // PRODUCCIÓN (10 módulos)
    'produccion' => [
        ['nombre' => 'Dashboard Producción', 'archivo' => 'dashboard_produccion.php', 'tipo' => 'dashboard', 'icono' => 'industry', 'color' => '667eea,764ba2'],
        ['nombre' => 'Órdenes de Fabricación', 'archivo' => 'ordenes_fabricacion.php', 'tipo' => 'crud', 'tabla' => 'ordenes_fabricacion', 'icono' => 'cogs', 'color' => '11998e,38ef7d'],
        ['nombre' => 'BOM (Lista Materiales)', 'archivo' => 'bom.php', 'tipo' => 'crud', 'tabla' => 'bom', 'icono' => 'list', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Rutas de Producción', 'archivo' => 'rutas_produccion.php', 'tipo' => 'crud', 'tabla' => 'rutas_produccion', 'icono' => 'route', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Centros de Trabajo', 'archivo' => 'centros_trabajo.php', 'tipo' => 'crud', 'tabla' => 'centros_trabajo', 'icono' => 'building', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Control de Planta', 'archivo' => 'control_planta.php', 'tipo' => 'dashboard', 'icono' => 'tachometer-alt', 'color' => '667eea,764ba2'],
        ['nombre' => 'Planificación Capacidad', 'archivo' => 'planificacion_capacidad.php', 'tipo' => 'analytics', 'icono' => 'chart-bar', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Costos de Producción', 'archivo' => 'costos_produccion.php', 'tipo' => 'analytics', 'icono' => 'dollar-sign', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Calidad Producción', 'archivo' => 'calidad_produccion.php', 'tipo' => 'crud', 'tabla' => 'calidad_produccion', 'icono' => 'award', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Mantenimiento', 'archivo' => 'mantenimiento.php', 'tipo' => 'crud', 'tabla' => 'mantenimiento', 'icono' => 'wrench', 'color' => 'fa709a,fee140'],
    ],

    // FINANZAS (11 módulos)
    'finanzas' => [
        ['nombre' => 'Contabilidad General', 'archivo' => 'contabilidad_general.php', 'tipo' => 'crud', 'tabla' => 'asientos_contables', 'icono' => 'calculator', 'color' => '667eea,764ba2'],
        ['nombre' => 'Cuentas por Cobrar', 'archivo' => 'cuentas_cobrar.php', 'tipo' => 'crud', 'tabla' => 'cuentas_cobrar', 'icono' => 'hand-holding-usd', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Cuentas por Pagar', 'archivo' => 'cuentas_pagar.php', 'tipo' => 'crud', 'tabla' => 'cuentas_pagar', 'icono' => 'money-bill-wave', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Tesorería', 'archivo' => 'tesoreria.php', 'tipo' => 'dashboard', 'icono' => 'university', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Activos Fijos', 'archivo' => 'activos_fijos.php', 'tipo' => 'crud', 'tabla' => 'activos_fijos', 'icono' => 'building', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Presupuestos', 'archivo' => 'presupuestos.php', 'tipo' => 'crud', 'tabla' => 'presupuestos', 'icono' => 'file-invoice-dollar', 'color' => '667eea,764ba2'],
        ['nombre' => 'Impuestos', 'archivo' => 'impuestos.php', 'tipo' => 'crud', 'tabla' => 'impuestos', 'icono' => 'percentage', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Consolidación', 'archivo' => 'consolidacion.php', 'tipo' => 'analytics', 'icono' => 'compress', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'IFRS', 'archivo' => 'ifrs.php', 'tipo' => 'analytics', 'icono' => 'globe', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Reporting Financiero', 'archivo' => 'reporting_financiero.php', 'tipo' => 'analytics', 'icono' => 'chart-line', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Comprobantes y Facturas', 'archivo' => 'comprobantes_facturas.php', 'tipo' => 'crud', 'tabla' => 'comprobantes', 'icono' => 'receipt', 'color' => '667eea,764ba2'],
    ],

    // CONTROLLING (8 módulos)
    'controlling' => [
        ['nombre' => 'Dashboard Controlling', 'archivo' => 'dashboard_controlling.php', 'tipo' => 'dashboard', 'icono' => 'tachometer-alt', 'color' => '667eea,764ba2'],
        ['nombre' => 'Centros de Costo', 'archivo' => 'centros_costo.php', 'tipo' => 'crud', 'tabla' => 'centros_costo', 'icono' => 'sitemap', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Control Presupuestario', 'archivo' => 'control_presupuestario.php', 'tipo' => 'analytics', 'icono' => 'chart-pie', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Costos de Productos', 'archivo' => 'costos_productos.php', 'tipo' => 'analytics', 'icono' => 'dollar-sign', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Análisis de Rentabilidad', 'archivo' => 'analisis_rentabilidad.php', 'tipo' => 'analytics', 'icono' => 'chart-line', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Análisis de Variaciones', 'archivo' => 'analisis_variaciones.php', 'tipo' => 'analytics', 'icono' => 'exchange-alt', 'color' => '667eea,764ba2'],
        ['nombre' => 'Indicadores KPI', 'archivo' => 'kpis.php', 'tipo' => 'dashboard', 'icono' => 'bullseye', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Reportes de Gestión', 'archivo' => 'reportes_gestion.php', 'tipo' => 'analytics', 'icono' => 'file-alt', 'color' => 'f093fb,f5576c'],
    ],

    // RECURSOS HUMANOS (11 módulos)
    'rrhh' => [
        ['nombre' => 'Dashboard RRHH', 'archivo' => 'dashboard_rrhh.php', 'tipo' => 'dashboard', 'icono' => 'users', 'color' => '667eea,764ba2'],
        ['nombre' => 'Asistencia', 'archivo' => 'asistencia.php', 'tipo' => 'crud', 'tabla' => 'marcajes', 'icono' => 'clock', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Nómina', 'archivo' => 'nomina.php', 'tipo' => 'crud', 'tabla' => 'liquidaciones', 'icono' => 'money-bill-wave', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Vacaciones', 'archivo' => 'vacaciones.php', 'tipo' => 'crud', 'tabla' => 'vacaciones', 'icono' => 'umbrella-beach', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Capacitación', 'archivo' => 'capacitacion.php', 'tipo' => 'crud', 'tabla' => 'capacitacion', 'icono' => 'graduation-cap', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Evaluación Desempeño', 'archivo' => 'evaluacion_desempeno.php', 'tipo' => 'crud', 'tabla' => 'evaluaciones', 'icono' => 'star', 'color' => '667eea,764ba2'],
        ['nombre' => 'Reclutamiento', 'archivo' => 'reclutamiento.php', 'tipo' => 'crud', 'tabla' => 'candidatos', 'icono' => 'user-plus', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Organigrama', 'archivo' => 'organigrama.php', 'tipo' => 'analytics', 'icono' => 'sitemap', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Beneficios', 'archivo' => 'beneficios.php', 'tipo' => 'crud', 'tabla' => 'beneficios', 'icono' => 'gift', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Documentos RRHH', 'archivo' => 'documentos_rrhh.php', 'tipo' => 'crud', 'tabla' => 'documentos_rrhh', 'icono' => 'folder', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Seguridad y Salud', 'archivo' => 'seguridad_salud.php', 'tipo' => 'crud', 'tabla' => 'seguridad_salud', 'icono' => 'shield-alt', 'color' => '667eea,764ba2'],
    ],

    // RELOJ CONTROL (4 módulos)
    'reloj' => [
        ['nombre' => 'Marcajes', 'archivo' => 'marcajes.php', 'tipo' => 'crud', 'tabla' => 'marcajes', 'icono' => 'fingerprint', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Dispositivos Biométricos', 'archivo' => 'dispositivos.php', 'tipo' => 'crud', 'tabla' => 'dispositivos_biometricos', 'icono' => 'mobile-alt', 'color' => '667eea,764ba2'],
        ['nombre' => 'Turnos', 'archivo' => 'turnos.php', 'tipo' => 'crud', 'tabla' => 'turnos', 'icono' => 'calendar-alt', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Horarios', 'archivo' => 'horarios.php', 'tipo' => 'crud', 'tabla' => 'horarios', 'icono' => 'clock', 'color' => 'f093fb,f5576c'],
    ],

    // CRM (8 módulos)
    'crm' => [
        ['nombre' => 'Dashboard CRM', 'archivo' => 'dashboard_crm.php', 'tipo' => 'dashboard', 'icono' => 'chart-line', 'color' => '667eea,764ba2'],
        ['nombre' => 'Cuentas', 'archivo' => 'cuentas.php', 'tipo' => 'crud', 'tabla' => 'clientes', 'icono' => 'building', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Contactos', 'archivo' => 'contactos.php', 'tipo' => 'crud', 'tabla' => 'contactos', 'icono' => 'address-book', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Oportunidades', 'archivo' => 'oportunidades.php', 'tipo' => 'crud', 'tabla' => 'oportunidades', 'icono' => 'handshake', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Pipeline', 'archivo' => 'pipeline.php', 'tipo' => 'analytics', 'icono' => 'funnel-dollar', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Actividades', 'archivo' => 'actividades.php', 'tipo' => 'crud', 'tabla' => 'actividades_crm', 'icono' => 'tasks', 'color' => '667eea,764ba2'],
        ['nombre' => 'Campañas', 'archivo' => 'campanas.php', 'tipo' => 'crud', 'tabla' => 'campanas', 'icono' => 'bullhorn', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Análisis CRM', 'archivo' => 'analisis_crm.php', 'tipo' => 'analytics', 'icono' => 'chart-pie', 'color' => 'f093fb,f5576c'],
    ],

    // SCM - Supply Chain Management (10 módulos)
    'scm' => [
        ['nombre' => 'Dashboard SCM', 'archivo' => 'dashboard_scm.php', 'tipo' => 'dashboard', 'icono' => 'truck', 'color' => '667eea,764ba2'],
        ['nombre' => 'Proveedores', 'archivo' => 'proveedores.php', 'tipo' => 'crud', 'tabla' => 'proveedores', 'icono' => 'handshake', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Contratos Proveedores', 'archivo' => 'contratos.php', 'tipo' => 'crud', 'tabla' => 'contratos_proveedores', 'icono' => 'file-contract', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Planificación Demanda', 'archivo' => 'planificacion_demanda.php', 'tipo' => 'analytics', 'icono' => 'chart-line', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Gestión Logística', 'archivo' => 'logistica.php', 'tipo' => 'crud', 'tabla' => 'logistica', 'icono' => 'shipping-fast', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Tracking Envíos', 'archivo' => 'tracking.php', 'tipo' => 'analytics', 'icono' => 'map-marked-alt', 'color' => '667eea,764ba2'],
        ['nombre' => 'Gestión Almacenes', 'archivo' => 'almacenes_scm.php', 'tipo' => 'crud', 'tabla' => 'almacenes', 'icono' => 'warehouse', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Cross-Docking', 'archivo' => 'cross_docking.php', 'tipo' => 'crud', 'tabla' => 'cross_docking', 'icono' => 'exchange-alt', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Rutas Distribución', 'archivo' => 'rutas.php', 'tipo' => 'crud', 'tabla' => 'rutas_distribucion', 'icono' => 'route', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Análisis Supply Chain', 'archivo' => 'analisis_scm.php', 'tipo' => 'analytics', 'icono' => 'chart-pie', 'color' => 'fa709a,fee140'],
    ],

    // PROYECTOS (3 módulos)
    'proyectos' => [
        ['nombre' => 'Gestión Proyectos', 'archivo' => 'gestion_proyectos.php', 'tipo' => 'crud', 'tabla' => 'proyectos', 'icono' => 'project-diagram', 'color' => '667eea,764ba2'],
        ['nombre' => 'Tareas Proyectos', 'archivo' => 'tareas.php', 'tipo' => 'crud', 'tabla' => 'tareas_proyectos', 'icono' => 'tasks', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Gantt Proyectos', 'archivo' => 'gantt.php', 'tipo' => 'analytics', 'icono' => 'chart-bar', 'color' => 'f093fb,f5576c'],
    ],

    // ECOMMERCE (5 módulos)
    'ecommerce' => [
        ['nombre' => 'Dashboard Ecommerce', 'archivo' => 'dashboard_ecommerce.php', 'tipo' => 'dashboard', 'icono' => 'shopping-cart', 'color' => '667eea,764ba2'],
        ['nombre' => 'Catálogo Productos', 'archivo' => 'catalogo.php', 'tipo' => 'crud', 'tabla' => 'productos', 'icono' => 'box', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Pedidos Online', 'archivo' => 'pedidos_online.php', 'tipo' => 'crud', 'tabla' => 'pedidos_online', 'icono' => 'shopping-bag', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Carrito Compras', 'archivo' => 'carrito.php', 'tipo' => 'crud', 'tabla' => 'carrito', 'icono' => 'cart-plus', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Integración Marketplaces', 'archivo' => 'marketplaces.php', 'tipo' => 'config', 'icono' => 'store', 'color' => 'fa709a,fee140'],
    ],

    // FIDELIZACIÓN (7 módulos)
    'fidelizacion' => [
        ['nombre' => 'Dashboard Fidelización', 'archivo' => 'dashboard_fidelizacion.php', 'tipo' => 'dashboard', 'icono' => 'heart', 'color' => '667eea,764ba2'],
        ['nombre' => 'Programas Lealtad', 'archivo' => 'programas_lealtad.php', 'tipo' => 'crud', 'tabla' => 'programas_lealtad', 'icono' => 'award', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Puntos Clientes', 'archivo' => 'puntos.php', 'tipo' => 'crud', 'tabla' => 'puntos_clientes', 'icono' => 'star', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Cupones Descuento', 'archivo' => 'cupones.php', 'tipo' => 'crud', 'tabla' => 'cupones', 'icono' => 'ticket-alt', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Recompensas', 'archivo' => 'recompensas.php', 'tipo' => 'crud', 'tabla' => 'recompensas', 'icono' => 'gift', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Segmentación Clientes', 'archivo' => 'segmentacion.php', 'tipo' => 'analytics', 'icono' => 'users', 'color' => '667eea,764ba2'],
        ['nombre' => 'Análisis Fidelización', 'archivo' => 'analisis_fidelizacion.php', 'tipo' => 'analytics', 'icono' => 'chart-line', 'color' => '11998e,38ef7d'],
    ],

    // BUSINESS INTELLIGENCE (15 módulos)
    'bi' => [
        ['nombre' => 'Dashboard BI', 'archivo' => 'dashboard_bi.php', 'tipo' => 'dashboard', 'icono' => 'chart-line', 'color' => '667eea,764ba2'],
        ['nombre' => 'Reportes Ventas', 'archivo' => 'reportes_ventas.php', 'tipo' => 'analytics', 'icono' => 'file-alt', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Reportes Financieros', 'archivo' => 'reportes_financieros.php', 'tipo' => 'analytics', 'icono' => 'calculator', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Análisis Clientes', 'archivo' => 'analisis_clientes.php', 'tipo' => 'analytics', 'icono' => 'users', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Análisis Productos', 'archivo' => 'analisis_productos.php', 'tipo' => 'analytics', 'icono' => 'box', 'color' => 'fa709a,fee140'],
        ['nombre' => 'KPIs Gerenciales', 'archivo' => 'kpis_gerenciales.php', 'tipo' => 'dashboard', 'icono' => 'bullseye', 'color' => '667eea,764ba2'],
        ['nombre' => 'Análisis Inventario', 'archivo' => 'analisis_inventario.php', 'tipo' => 'analytics', 'icono' => 'warehouse', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Análisis Compras', 'archivo' => 'analisis_compras.php', 'tipo' => 'analytics', 'icono' => 'shopping-cart', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Cuadro Mando Integral', 'archivo' => 'balanced_scorecard.php', 'tipo' => 'dashboard', 'icono' => 'tachometer-alt', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Análisis Tendencias', 'archivo' => 'tendencias.php', 'tipo' => 'analytics', 'icono' => 'chart-area', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Forecasting', 'archivo' => 'forecasting.php', 'tipo' => 'analytics', 'icono' => 'chart-line', 'color' => '667eea,764ba2'],
        ['nombre' => 'Análisis ABC', 'archivo' => 'analisis_abc.php', 'tipo' => 'analytics', 'icono' => 'sort-amount-down', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Dashboards Personalizados', 'archivo' => 'dashboards_custom.php', 'tipo' => 'dashboard', 'icono' => 'th-large', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Exportador Datos', 'archivo' => 'exportador.php', 'tipo' => 'config', 'icono' => 'file-export', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Data Mining', 'archivo' => 'data_mining.php', 'tipo' => 'analytics', 'icono' => 'database', 'color' => 'fa709a,fee140'],
    ],

    // BI AVANZADO (3 módulos)
    'bi_avanzado' => [
        ['nombre' => 'Machine Learning', 'archivo' => 'machine_learning.php', 'tipo' => 'analytics', 'icono' => 'brain', 'color' => '667eea,764ba2'],
        ['nombre' => 'Predictive Analytics', 'archivo' => 'predictive.php', 'tipo' => 'analytics', 'icono' => 'crystal-ball', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Big Data Analytics', 'archivo' => 'big_data.php', 'tipo' => 'analytics', 'icono' => 'server', 'color' => 'f093fb,f5576c'],
    ],

    // MARKETING (6 módulos)
    'marketing' => [
        ['nombre' => 'Dashboard Marketing', 'archivo' => 'dashboard_marketing.php', 'tipo' => 'dashboard', 'icono' => 'bullhorn', 'color' => '667eea,764ba2'],
        ['nombre' => 'Campañas Marketing', 'archivo' => 'campanas_marketing.php', 'tipo' => 'crud', 'tabla' => 'campanas_marketing', 'icono' => 'ad', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Email Marketing', 'archivo' => 'email_marketing.php', 'tipo' => 'crud', 'tabla' => 'email_marketing', 'icono' => 'envelope', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Marketing Automation', 'archivo' => 'automation.php', 'tipo' => 'config', 'icono' => 'robot', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Análisis ROI Marketing', 'archivo' => 'roi_marketing.php', 'tipo' => 'analytics', 'icono' => 'chart-line', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Lead Scoring', 'archivo' => 'lead_scoring.php', 'tipo' => 'analytics', 'icono' => 'star-half-alt', 'color' => '667eea,764ba2'],
    ],

    // CALIDAD (5 módulos)
    'calidad' => [
        ['nombre' => 'Dashboard Calidad', 'archivo' => 'dashboard_calidad.php', 'tipo' => 'dashboard', 'icono' => 'award', 'color' => '667eea,764ba2'],
        ['nombre' => 'Control Calidad', 'archivo' => 'control_calidad.php', 'tipo' => 'crud', 'tabla' => 'control_calidad', 'icono' => 'check-circle', 'color' => '11998e,38ef7d'],
        ['nombre' => 'No Conformidades', 'archivo' => 'no_conformidades.php', 'tipo' => 'crud', 'tabla' => 'no_conformidades', 'icono' => 'exclamation-triangle', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Auditorías', 'archivo' => 'auditorias.php', 'tipo' => 'crud', 'tabla' => 'auditorias', 'icono' => 'clipboard-check', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'ISO 9001', 'archivo' => 'iso_9001.php', 'tipo' => 'config', 'icono' => 'certificate', 'color' => 'fa709a,fee140'],
    ],

    // MANTENIMIENTO (4 módulos)
    'mantenimiento' => [
        ['nombre' => 'Dashboard Mantenimiento', 'archivo' => 'dashboard_mantenimiento.php', 'tipo' => 'dashboard', 'icono' => 'wrench', 'color' => '667eea,764ba2'],
        ['nombre' => 'Órdenes Trabajo', 'archivo' => 'ordenes_trabajo.php', 'tipo' => 'crud', 'tabla' => 'ordenes_trabajo', 'icono' => 'clipboard-list', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Mantenimiento Preventivo', 'archivo' => 'preventivo.php', 'tipo' => 'crud', 'tabla' => 'mantenimiento_preventivo', 'icono' => 'calendar-check', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Equipos Maquinaria', 'archivo' => 'equipos.php', 'tipo' => 'crud', 'tabla' => 'equipos', 'icono' => 'cogs', 'color' => '4facfe,00f2fe'],
    ],

    // SOPORTE (4 módulos)
    'soporte' => [
        ['nombre' => 'Dashboard Soporte', 'archivo' => 'dashboard_soporte.php', 'tipo' => 'dashboard', 'icono' => 'headset', 'color' => '667eea,764ba2'],
        ['nombre' => 'Tickets Soporte', 'archivo' => 'tickets.php', 'tipo' => 'crud', 'tabla' => 'tickets_soporte', 'icono' => 'ticket-alt', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Base Conocimiento', 'archivo' => 'base_conocimiento.php', 'tipo' => 'crud', 'tabla' => 'base_conocimiento', 'icono' => 'book', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'SLA Management', 'archivo' => 'sla.php', 'tipo' => 'analytics', 'icono' => 'stopwatch', 'color' => '4facfe,00f2fe'],
    ],

    // CONFIGURACIÓN (6 módulos)
    'configuracion' => [
        ['nombre' => 'Empresas', 'archivo' => 'empresas.php', 'tipo' => 'crud', 'tabla' => 'empresas', 'icono' => 'building', 'color' => '667eea,764ba2'],
        ['nombre' => 'Usuarios', 'archivo' => 'usuarios.php', 'tipo' => 'crud', 'tabla' => 'usuarios', 'icono' => 'users', 'color' => '11998e,38ef7d'],
        ['nombre' => 'Roles Permisos', 'archivo' => 'roles.php', 'tipo' => 'config', 'icono' => 'user-shield', 'color' => 'f093fb,f5576c'],
        ['nombre' => 'Parámetros Sistema', 'archivo' => 'parametros.php', 'tipo' => 'config', 'icono' => 'cog', 'color' => '4facfe,00f2fe'],
        ['nombre' => 'Logs Auditoría', 'archivo' => 'logs.php', 'tipo' => 'analytics', 'icono' => 'history', 'color' => 'fa709a,fee140'],
        ['nombre' => 'Backups', 'archivo' => 'backups.php', 'tipo' => 'config', 'icono' => 'database', 'color' => '667eea,764ba2'],
    ],
];

// Generar todos los módulos
$totalGenerados = 0;
$errores = [];

foreach ($modulos as $carpeta => $lista_modulos) {
    // Crear carpeta si no existe
    $rutaCarpeta = __DIR__ . '/modulos/' . $carpeta;
    if (!is_dir($rutaCarpeta)) {
        mkdir($rutaCarpeta, 0755, true);
    }

    foreach ($lista_modulos as $modulo) {
        try {
            $codigo = generarCodigoModulo($modulo, $carpeta);
            $rutaArchivo = $rutaCarpeta . '/' . $modulo['archivo'];

            file_put_contents($rutaArchivo, $codigo);
            $totalGenerados++;
            echo "✓ Generado: {$carpeta}/{$modulo['archivo']}\n";

        } catch (Exception $e) {
            $errores[] = "{$carpeta}/{$modulo['archivo']}: " . $e->getMessage();
            echo "✗ Error: {$carpeta}/{$modulo['archivo']}\n";
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "Total generados: $totalGenerados módulos\n";
echo "Errores: " . count($errores) . "\n";

if (!empty($errores)) {
    echo "\nErrores encontrados:\n";
    foreach ($errores as $error) {
        echo "  - $error\n";
    }
}

/**
 * Genera el código PHP completo para un módulo
 */
function generarCodigoModulo($modulo, $carpeta) {
    $nombre = $modulo['nombre'];
    $tipo = $modulo['tipo'];
    $tabla = $modulo['tabla'] ?? '';
    $icono = $modulo['icono'];
    $color = $modulo['color'];
    list($color1, $color2) = explode(',', $color);

    $codigo = "<?php\n";
    $codigo .= "/**\n";
    $codigo .= " * {$nombre} - CONECTA ERP\n";
    $codigo .= " * Sistema Multiempresa - Multiusuario\n";
    $codigo .= " */\n";
    $codigo .= "session_start();\n";
    $codigo .= "require_once '../../includes/config.php';\n";
    $codigo .= "require_once '../../includes/functions.php';\n\n";

    $codigo .= "if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['empresa_id'])) {\n";
    $codigo .= "    header('Location: /login.php');\n";
    $codigo .= "    exit;\n";
    $codigo .= "}\n\n";

    $codigo .= "\$empresa_id = (int)\$_SESSION['empresa_id'];\n";
    $codigo .= "\$usuario_id = (int)\$_SESSION['user_id'];\n\n";

    $codigo .= "// Obtener conexión a base de datos\n";
    $codigo .= "if (!isset(\$conn)) {\n";
    $codigo .= "    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n";
    $codigo .= "    if (\$conn->connect_error) {\n";
    $codigo .= "        die(\"Error de conexión: \" . \$conn->connect_error);\n";
    $codigo .= "    }\n";
    $codigo .= "    \$conn->set_charset('utf8mb4');\n";
    $codigo .= "}\n\n";

    // Generar lógica según tipo
    if ($tipo === 'crud') {
        $codigo .= generarLogicaCRUD($tabla);
    } elseif ($tipo === 'dashboard') {
        $codigo .= generarLogicaDashboard($carpeta);
    } elseif ($tipo === 'analytics') {
        $codigo .= generarLogicaAnalytics($tabla);
    }

    // HTML
    $codigo .= "?>\n";
    $codigo .= generarHTML($nombre, $icono, $color1, $color2, $tipo, $tabla);

    return $codigo;
}

function generarLogicaCRUD($tabla) {
    return "\$success = '';\n\$error = '';\n\n// TODO: Implementar lógica CRUD para tabla: {$tabla}\n\n";
}

function generarLogicaDashboard($carpeta) {
    return "// Dashboard {$carpeta}\n\$stats = [];\n\n";
}

function generarLogicaAnalytics($tabla) {
    return "// Analytics {$tabla}\n\$datos = [];\n\n";
}

function generarHTML($nombre, $icono, $color1, $color2, $tipo, $tabla) {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$nombre} | CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .gradient-header {
            background: linear-gradient(135deg, #{$color1} 0%, #{$color2} 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="gradient-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-{$icono}"></i> {$nombre}</h1>
                    <p class="mb-0 opacity-75">Sistema completo de gestión</p>
                </div>
                <div>
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#nuevoModal">
                        <i class="fas fa-plus-circle"></i> Nuevo
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Listado de Registros</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Módulo generado automáticamente. Implementa la lógica específica aquí.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo -->
    <div class="modal fade" id="nuevoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Nuevo Registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Campo 1</label>
                            <input type="text" class="form-control" name="campo1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

    return $html;
}

echo "\n¡Script completado!\n";
echo "Para generar los módulos, ejecuta: php generar_todos_modulos.php\n";
?>
