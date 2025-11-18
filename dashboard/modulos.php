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

// Obtener conexión a la base de datos
$db = Database::getInstance();
$pdo = getDB();

// ============================================
// CONSULTAS SQL PARA OBTENER DATOS REALES
// ============================================

// 1. VENTAS DEL MES ACTUAL
try {
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(total), 0) as total_ventas,
            COUNT(*) as num_facturas
        FROM facturas
        WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE())
        AND YEAR(fecha_emision) = YEAR(CURRENT_DATE())
        AND empresa_id = ?
        AND estado IN ('emitida', 'pagada')
    ");
    $stmt->execute([$empresa_id]);
    $ventas_mes = $stmt->fetch();
} catch (PDOException $e) {
    $ventas_mes = ['total_ventas' => 0, 'num_facturas' => 0];
}

// Calcular variación vs mes anterior
try {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as total_anterior
        FROM facturas
        WHERE MONTH(fecha_emision) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        AND empresa_id = ?
        AND estado IN ('emitida', 'pagada')
    ");
    $stmt->execute([$empresa_id]);
    $ventas_anterior = $stmt->fetch();

    $variacion_ventas = 0;
    if ($ventas_anterior['total_anterior'] > 0) {
        $variacion_ventas = (($ventas_mes['total_ventas'] - $ventas_anterior['total_anterior']) / $ventas_anterior['total_anterior']) * 100;
    }
} catch (PDOException $e) {
    $variacion_ventas = 0;
}

// 2. CLIENTES ACTIVOS
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT id) as total_clientes
        FROM clientes
        WHERE estado = 'activo'
        AND empresa_id = ?
    ");
    $stmt->execute([$empresa_id]);
    $clientes = $stmt->fetch();
} catch (PDOException $e) {
    $clientes = ['total_clientes' => 0];
}

// Variación clientes (últimos 30 días)
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as nuevos_clientes
        FROM clientes
        WHERE fecha_registro >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        AND empresa_id = ?
    ");
    $stmt->execute([$empresa_id]);
    $nuevos = $stmt->fetch();
    $variacion_clientes = $clientes['total_clientes'] > 0 ?
        ($nuevos['nuevos_clientes'] / $clientes['total_clientes']) * 100 : 0;
} catch (PDOException $e) {
    $variacion_clientes = 0;
}

// 3. PRODUCTOS EN STOCK
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_productos,
               SUM(stock_actual) as stock_total
        FROM productos
        WHERE empresa_id = ?
        AND estado = 'activo'
    ");
    $stmt->execute([$empresa_id]);
    $productos = $stmt->fetch();
} catch (PDOException $e) {
    $productos = ['total_productos' => 0, 'stock_total' => 0];
}

// Productos con stock bajo
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as stock_bajo
        FROM productos
        WHERE stock_actual <= stock_minimo
        AND empresa_id = ?
        AND estado = 'activo'
    ");
    $stmt->execute([$empresa_id]);
    $stock_bajo = $stmt->fetch();
    $variacion_stock = $productos['total_productos'] > 0 ?
        -($stock_bajo['stock_bajo'] / $productos['total_productos']) * 100 : 0;
} catch (PDOException $e) {
    $variacion_stock = 0;
}

// 4. FACTURAS PENDIENTES
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as num_pendientes,
               COALESCE(SUM(total), 0) as monto_pendiente
        FROM facturas
        WHERE estado IN ('pendiente', 'emitida')
        AND empresa_id = ?
        AND fecha_vencimiento >= CURRENT_DATE()
    ");
    $stmt->execute([$empresa_id]);
    $facturas_pendientes = $stmt->fetch();
} catch (PDOException $e) {
    $facturas_pendientes = ['num_pendientes' => 0, 'monto_pendiente' => 0];
}

// Variación facturas pendientes
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pendientes_anterior
        FROM facturas
        WHERE estado IN ('pendiente', 'emitida')
        AND empresa_id = ?
        AND fecha_emision >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        AND fecha_emision < CURRENT_DATE()
    ");
    $stmt->execute([$empresa_id]);
    $pendientes_ant = $stmt->fetch();
    $variacion_facturas = $facturas_pendientes['num_pendientes'] > 0 && $pendientes_ant['pendientes_anterior'] > 0 ?
        (($facturas_pendientes['num_pendientes'] - $pendientes_ant['pendientes_anterior']) / $pendientes_ant['pendientes_anterior']) * 100 : 0;
} catch (PDOException $e) {
    $variacion_facturas = 0;
}

// 5. ÚLTIMAS TRANSACCIONES
try {
    $stmt = $pdo->prepare("
        SELECT
            f.id,
            f.numero_factura,
            c.nombre as cliente_nombre,
            c.razon_social,
            f.total,
            f.estado,
            f.fecha_emision,
            GROUP_CONCAT(DISTINCT p.nombre SEPARATOR ', ') as productos
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN facturas_detalle fd ON f.id = fd.factura_id
        LEFT JOIN productos p ON fd.producto_id = p.id
        WHERE f.empresa_id = ?
        GROUP BY f.id, f.numero_factura, c.nombre, c.razon_social, f.total, f.estado, f.fecha_emision
        ORDER BY f.fecha_emision DESC
        LIMIT 10
    ");
    $stmt->execute([$empresa_id]);
    $ultimas_transacciones = $stmt->fetchAll();
} catch (PDOException $e) {
    $ultimas_transacciones = [];
}

// 6. PRODUCTOS MÁS VENDIDOS
try {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.nombre,
            p.categoria,
            SUM(fd.cantidad) as unidades_vendidas,
            SUM(fd.subtotal) as revenue,
            COUNT(DISTINCT f.id) as num_ventas
        FROM productos p
        INNER JOIN facturas_detalle fd ON p.id = fd.producto_id
        INNER JOIN facturas f ON fd.factura_id = f.id
        WHERE f.empresa_id = ?
        AND MONTH(f.fecha_emision) = MONTH(CURRENT_DATE())
        AND YEAR(f.fecha_emision) = YEAR(CURRENT_DATE())
        AND f.estado IN ('emitida', 'pagada')
        GROUP BY p.id, p.nombre, p.categoria
        ORDER BY unidades_vendidas DESC
        LIMIT 5
    ");
    $stmt->execute([$empresa_id]);
    $productos_mas_vendidos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos_mas_vendidos = [];
}

// 7. DATOS PARA GRÁFICO DE VENTAS MENSUALES (últimos 12 meses)
try {
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
            MONTHNAME(fecha_emision) as mes_nombre,
            SUM(total) as total_mes
        FROM facturas
        WHERE empresa_id = ?
        AND fecha_emision >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
        AND estado IN ('emitida', 'pagada')
        GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m'), MONTHNAME(fecha_emision)
        ORDER BY mes ASC
    ");
    $stmt->execute([$empresa_id]);
    $ventas_mensuales = $stmt->fetchAll();

    // Preparar arrays para Chart.js
    $meses_labels = [];
    $meses_valores = [];

    foreach ($ventas_mensuales as $vm) {
        $meses_labels[] = $vm['mes_nombre'] ?? substr($vm['mes'], 5);
        $meses_valores[] = (float)$vm['total_mes'];
    }
} catch (PDOException $e) {
    $meses_labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $meses_valores = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
}

// 8. DISTRIBUCIÓN POR CATEGORÍA (para gráfico de dona)
try {
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(p.categoria, 'Sin categoría') as categoria,
            SUM(fd.subtotal) as total_categoria
        FROM facturas_detalle fd
        INNER JOIN productos p ON fd.producto_id = p.id
        INNER JOIN facturas f ON fd.factura_id = f.id
        WHERE f.empresa_id = ?
        AND MONTH(f.fecha_emision) = MONTH(CURRENT_DATE())
        AND YEAR(f.fecha_emision) = YEAR(CURRENT_DATE())
        AND f.estado IN ('emitida', 'pagada')
        GROUP BY p.categoria
        ORDER BY total_categoria DESC
        LIMIT 6
    ");
    $stmt->execute([$empresa_id]);
    $categorias_data = $stmt->fetchAll();

    $categorias_labels = [];
    $categorias_valores = [];

    foreach ($categorias_data as $cat) {
        $categorias_labels[] = $cat['categoria'];
        $categorias_valores[] = (float)$cat['total_categoria'];
    }
} catch (PDOException $e) {
    $categorias_labels = ['Sin datos'];
    $categorias_valores = [0];
}

// 9. COMPARATIVA TRIMESTRAL (año actual vs anterior)
try {
    // Año actual
    $stmt = $pdo->prepare("
        SELECT
            QUARTER(fecha_emision) as trimestre,
            SUM(total) as total_trimestre
        FROM facturas
        WHERE empresa_id = ?
        AND YEAR(fecha_emision) = YEAR(CURRENT_DATE())
        AND estado IN ('emitida', 'pagada')
        GROUP BY QUARTER(fecha_emision)
        ORDER BY trimestre
    ");
    $stmt->execute([$empresa_id]);
    $trimestres_actual = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Año anterior
    $stmt = $pdo->prepare("
        SELECT
            QUARTER(fecha_emision) as trimestre,
            SUM(total) as total_trimestre
        FROM facturas
        WHERE empresa_id = ?
        AND YEAR(fecha_emision) = YEAR(CURRENT_DATE()) - 1
        AND estado IN ('emitida', 'pagada')
        GROUP BY QUARTER(fecha_emision)
        ORDER BY trimestre
    ");
    $stmt->execute([$empresa_id]);
    $trimestres_anterior = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $trimestres_actual_valores = [];
    $trimestres_anterior_valores = [];

    for ($i = 1; $i <= 4; $i++) {
        $trimestres_actual_valores[] = isset($trimestres_actual[$i]) ? (float)$trimestres_actual[$i] : 0;
        $trimestres_anterior_valores[] = isset($trimestres_anterior[$i]) ? (float)$trimestres_anterior[$i] : 0;
    }
} catch (PDOException $e) {
    $trimestres_actual_valores = [0, 0, 0, 0];
    $trimestres_anterior_valores = [0, 0, 0, 0];
}

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

// Función helper para formatear moneda
function formatearMoneda($monto) {
    return '$' . number_format($monto, 0, ',', '.');
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

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
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
            <button class="btn btn-sm btn-outline-primary position-relative">
                <i class="fas fa-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    3
                </span>
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
                <div class="stat-value"><?= formatearMoneda($ventas_mes['total_ventas']) ?></div>
                <div class="stat-label">Ventas del Mes (<?= $ventas_mes['num_facturas'] ?> facturas)</div>
                <span class="stat-change <?= $variacion_ventas >= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-arrow-<?= $variacion_ventas >= 0 ? 'up' : 'down' ?>"></i>
                    <?= abs(number_format($variacion_ventas, 1)) ?>%
                </span>
            </div>

            <div class="stat-card animate-fade-in" style="animation-delay: 0.2s;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= number_format($clientes['total_clientes'], 0, ',', '.') ?></div>
                <div class="stat-label">Clientes Activos</div>
                <span class="stat-change <?= $variacion_clientes >= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-arrow-<?= $variacion_clientes >= 0 ? 'up' : 'down' ?>"></i>
                    <?= abs(number_format($variacion_clientes, 1)) ?>%
                </span>
            </div>

            <div class="stat-card animate-fade-in" style="animation-delay: 0.3s;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-value"><?= number_format($productos['total_productos'], 0, ',', '.') ?></div>
                <div class="stat-label">Productos en Stock (<?= number_format($productos['stock_total'], 0, ',', '.') ?> unidades)</div>
                <span class="stat-change <?= $variacion_stock >= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-arrow-<?= $variacion_stock >= 0 ? 'up' : 'down' ?>"></i>
                    <?= abs(number_format($variacion_stock, 1)) ?>%
                </span>
            </div>

            <div class="stat-card animate-fade-in" style="animation-delay: 0.4s;">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="stat-value"><?= $facturas_pendientes['num_pendientes'] ?></div>
                <div class="stat-label">Facturas Pendientes (<?= formatearMoneda($facturas_pendientes['monto_pendiente']) ?>)</div>
                <span class="stat-change <?= $variacion_facturas <= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-arrow-<?= $variacion_facturas <= 0 ? 'down' : 'up' ?>"></i>
                    <?= abs(number_format($variacion_facturas, 1)) ?>%
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
                <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Comparativa Trimestral</h3>
                <div class="chart-actions">
                    <button class="chart-btn"><?= date('Y') - 1 ?></button>
                    <button class="chart-btn active"><?= date('Y') ?></button>
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
            <?php if (count($ultimas_transacciones) > 0): ?>
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
                    <?php foreach ($ultimas_transacciones as $trans): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($trans['numero_factura']) ?></td>
                        <td><?= htmlspecialchars($trans['cliente_nombre'] ?? $trans['razon_social'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars(substr($trans['productos'] ?? 'Varios', 0, 30)) ?>...</td>
                        <td><?= formatearMoneda($trans['total']) ?></td>
                        <td>
                            <?php
                            $badge_class = 'badge-info';
                            if ($trans['estado'] == 'pagada') $badge_class = 'badge-success';
                            elseif ($trans['estado'] == 'pendiente') $badge_class = 'badge-warning';
                            elseif ($trans['estado'] == 'anulada') $badge_class = 'badge-danger';
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= ucfirst($trans['estado']) ?></span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($trans['fecha_emision'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="alert alert-info">No hay transacciones registradas</div>
            <?php endif; ?>
        </div>

        <!-- Additional Table -->
        <div class="table-card animate-fade-in" style="animation-delay: 0.9s;">
            <div class="table-header">
                <h3 class="chart-title"><i class="fas fa-star"></i> Productos Más Vendidos</h3>
                <button class="chart-btn"><i class="fas fa-download"></i> Exportar</button>
            </div>
            <?php if (count($productos_mas_vendidos) > 0): ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Unidades Vendidas</th>
                        <th>Revenue</th>
                        <th>N° Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $contador = 1; foreach ($productos_mas_vendidos as $prod): ?>
                    <tr>
                        <td><?= $contador++ ?></td>
                        <td><?= htmlspecialchars($prod['nombre']) ?></td>
                        <td><?= htmlspecialchars($prod['categoria'] ?? 'Sin categoría') ?></td>
                        <td><?= number_format($prod['unidades_vendidas'], 0, ',', '.') ?></td>
                        <td><?= formatearMoneda($prod['revenue']) ?></td>
                        <td><span class="badge badge-info"><?= $prod['num_ventas'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="alert alert-info">No hay datos de productos vendidos este mes</div>
            <?php endif; ?>
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

        // Datos desde PHP
        const mesesLabels = <?= json_encode($meses_labels) ?>;
        const mesesValores = <?= json_encode($meses_valores) ?>;
        const categoriasLabels = <?= json_encode($categorias_labels) ?>;
        const categoriasValores = <?= json_encode($categorias_valores) ?>;
        const trimestresActual = <?= json_encode($trimestres_actual_valores) ?>;
        const trimestresAnterior = <?= json_encode($trimestres_anterior_valores) ?>;

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: mesesLabels,
                datasets: [{
                    label: 'Ventas <?= date("Y") ?>',
                    data: mesesValores,
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
                labels: categoriasLabels,
                datasets: [{
                    data: categoriasValores,
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
                        label: '<?= date("Y") - 1 ?>',
                        data: trimestresAnterior,
                        backgroundColor: 'rgba(102, 126, 234, 0.5)',
                        borderColor: '#667eea',
                        borderWidth: 2
                    },
                    {
                        label: '<?= date("Y") ?>',
                        data: trimestresActual,
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
