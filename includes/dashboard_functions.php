<?php
/**
 * CONECTA ERP - Dashboard Functions
 * Funciones para obtener datos del dashboard desde la base de datos
 * Sistema REAL - TODO desde SQL, sin datos hardcodeados
 */

if (!defined('DASHBOARD_FUNCTIONS')) {
    define('DASHBOARD_FUNCTIONS', true);
}

/**
 * Obtiene estadísticas principales del dashboard
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @return array Estadísticas principales
 */
function getDashboardStats($conn, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM clientes WHERE empresa_id = ?) as total_clientes,
            (SELECT COUNT(*) FROM productos WHERE empresa_id = ?) as total_productos,
            (SELECT COUNT(*) FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())) as facturas_mes,
            (SELECT COALESCE(SUM(total), 0) FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())) as ventas_mes
    ");

    $stmt->bind_param("iiii", $empresa_id, $empresa_id, $empresa_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result ? $result : [
        'total_clientes' => 0,
        'total_productos' => 0,
        'facturas_mes' => 0,
        'ventas_mes' => 0
    ];
}

/**
 * Obtiene tendencias mes a mes (comparación con mes anterior)
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @return array Tendencias y porcentajes de cambio
 */
function getDashboardTrends($conn, $empresa_id) {
    // Clientes: mes actual vs mes anterior
    $stmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM clientes WHERE empresa_id = ? AND MONTH(fecha_creacion) = MONTH(CURDATE()) AND YEAR(fecha_creacion) = YEAR(CURDATE())) as clientes_mes_actual,
            (SELECT COUNT(*) FROM clientes WHERE empresa_id = ? AND MONTH(fecha_creacion) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha_creacion) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))) as clientes_mes_anterior
    ");
    $stmt->bind_param("ii", $empresa_id, $empresa_id);
    $stmt->execute();
    $clientes = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Productos: mes actual vs mes anterior
    $stmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM productos WHERE empresa_id = ? AND MONTH(fecha_creacion) = MONTH(CURDATE()) AND YEAR(fecha_creacion) = YEAR(CURDATE())) as productos_mes_actual,
            (SELECT COUNT(*) FROM productos WHERE empresa_id = ? AND MONTH(fecha_creacion) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha_creacion) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))) as productos_mes_anterior
    ");
    $stmt->bind_param("ii", $empresa_id, $empresa_id);
    $stmt->execute();
    $productos = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Facturas: mes actual vs mes anterior
    $stmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())) as facturas_mes_actual,
            (SELECT COUNT(*) FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))) as facturas_mes_anterior
    ");
    $stmt->bind_param("ii", $empresa_id, $empresa_id);
    $stmt->execute();
    $facturas = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Ventas: mes actual vs mes anterior
    $stmt = $conn->prepare("
        SELECT
            (SELECT COALESCE(SUM(total), 0) FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE())) as ventas_mes_actual,
            (SELECT COALESCE(SUM(total), 0) FROM facturas WHERE empresa_id = ? AND MONTH(fecha_emision) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))) as ventas_mes_anterior
    ");
    $stmt->bind_param("ii", $empresa_id, $empresa_id);
    $stmt->execute();
    $ventas = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Calcular porcentajes de cambio
    return [
        'clientes_change' => calcularPorcentajeCambio($clientes['clientes_mes_anterior'], $clientes['clientes_mes_actual']),
        'productos_change' => calcularPorcentajeCambio($productos['productos_mes_anterior'], $productos['productos_mes_actual']),
        'facturas_change' => calcularPorcentajeCambio($facturas['facturas_mes_anterior'], $facturas['facturas_mes_actual']),
        'ventas_change' => calcularPorcentajeCambio($ventas['ventas_mes_anterior'], $ventas['ventas_mes_actual'])
    ];
}

/**
 * Calcula el porcentaje de cambio entre dos valores
 * @param float $valor_anterior Valor del periodo anterior
 * @param float $valor_actual Valor del periodo actual
 * @return array ['porcentaje' => float, 'direccion' => 'up'|'down'|'neutral']
 */
function calcularPorcentajeCambio($valor_anterior, $valor_actual) {
    if ($valor_anterior == 0) {
        if ($valor_actual > 0) {
            return ['porcentaje' => 100, 'direccion' => 'up'];
        }
        return ['porcentaje' => 0, 'direccion' => 'neutral'];
    }

    $cambio = (($valor_actual - $valor_anterior) / $valor_anterior) * 100;

    return [
        'porcentaje' => abs(round($cambio, 1)),
        'direccion' => $cambio > 0 ? 'up' : ($cambio < 0 ? 'down' : 'neutral')
    ];
}

/**
 * Obtiene actividad reciente del usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param int $empresa_id ID de la empresa
 * @param int $limit Número de registros a obtener
 * @return array Actividades recientes
 */
function getActividadReciente($conn, $usuario_id, $empresa_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT
            accion,
            tabla,
            descripcion,
            DATE_FORMAT(fecha_accion, '%d/%m/%Y %H:%i') as fecha_formateada,
            fecha_accion
        FROM logs_auditoria
        WHERE usuario_id = ? AND empresa_id = ?
        ORDER BY fecha_accion DESC
        LIMIT ?
    ");

    $stmt->bind_param("iii", $usuario_id, $empresa_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $actividades = [];
    while ($row = $result->fetch_assoc()) {
        $actividades[] = $row;
    }

    $stmt->close();
    return $actividades;
}

/**
 * Obtiene notificaciones pendientes del usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param int $empresa_id ID de la empresa
 * @return array Notificaciones pendientes
 */
function getNotificacionesPendientes($conn, $usuario_id, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT
            id,
            tipo,
            titulo,
            mensaje,
            leida,
            DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formateada
        FROM notificaciones
        WHERE usuario_id = ? AND empresa_id = ? AND leida = 0
        ORDER BY fecha_creacion DESC
        LIMIT 10
    ");

    $stmt->bind_param("ii", $usuario_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notificaciones = [];
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
    }

    $stmt->close();
    return $notificaciones;
}

/**
 * Obtiene contador de notificaciones no leídas
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param int $empresa_id ID de la empresa
 * @return int Número de notificaciones no leídas
 */
function getContadorNotificaciones($conn, $usuario_id, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM notificaciones
        WHERE usuario_id = ? AND empresa_id = ? AND leida = 0
    ");

    $stmt->bind_param("ii", $usuario_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result['total'] ?? 0;
}

/**
 * Obtiene datos para gráfico de ventas mensuales (últimos 6 meses)
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @return array Datos para Chart.js
 */
function getGraficoVentasMensuales($conn, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT
            DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
            DATE_FORMAT(fecha_emision, '%b %Y') as mes_label,
            COALESCE(SUM(total), 0) as total_ventas
        FROM facturas
        WHERE empresa_id = ?
        AND fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m')
        ORDER BY mes ASC
    ");

    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['mes_label'];
        $data[] = (float)$row['total_ventas'];
    }

    $stmt->close();

    return [
        'labels' => $labels,
        'data' => $data
    ];
}

/**
 * Obtiene top 5 clientes por ventas (mes actual)
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @return array Top clientes
 */
function getTopClientes($conn, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT
            c.razon_social,
            COUNT(f.id) as num_facturas,
            COALESCE(SUM(f.total), 0) as total_compras
        FROM clientes c
        INNER JOIN facturas f ON c.id = f.cliente_id
        WHERE c.empresa_id = ?
        AND MONTH(f.fecha_emision) = MONTH(CURDATE())
        AND YEAR(f.fecha_emision) = YEAR(CURDATE())
        GROUP BY c.id, c.razon_social
        ORDER BY total_compras DESC
        LIMIT 5
    ");

    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }

    $stmt->close();
    return $clientes;
}

/**
 * Obtiene productos con stock bajo
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @param int $limit Número de productos a obtener
 * @return array Productos con stock bajo
 */
function getProductosStockBajo($conn, $empresa_id, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT
            nombre,
            sku,
            stock_actual,
            stock_minimo,
            (stock_minimo - stock_actual) as deficit
        FROM productos
        WHERE empresa_id = ?
        AND stock_actual < stock_minimo
        ORDER BY deficit DESC
        LIMIT ?
    ");

    $stmt->bind_param("ii", $empresa_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }

    $stmt->close();
    return $productos;
}

/**
 * Obtiene cuentas por cobrar vencidas
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @return array Resumen de cuentas por cobrar
 */
function getCuentasPorCobrarVencidas($conn, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) as total_cuentas,
            COALESCE(SUM(saldo_pendiente), 0) as monto_total,
            COALESCE(AVG(dias_vencidos), 0) as promedio_dias_vencidos
        FROM cuentas_por_cobrar
        WHERE empresa_id = ?
        AND estado = 'vencida'
        AND saldo_pendiente > 0
    ");

    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result ? $result : [
        'total_cuentas' => 0,
        'monto_total' => 0,
        'promedio_dias_vencidos' => 0
    ];
}

/**
 * Obtiene cuentas por pagar próximas a vencer (próximos 7 días)
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @return array Resumen de cuentas por pagar
 */
function getCuentasPorPagarProximas($conn, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) as total_cuentas,
            COALESCE(SUM(saldo_pendiente), 0) as monto_total
        FROM cuentas_por_pagar
        WHERE empresa_id = ?
        AND estado IN ('pendiente', 'pagada_parcial')
        AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");

    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result ? $result : [
        'total_cuentas' => 0,
        'monto_total' => 0
    ];
}

/**
 * Obtiene información de la empresa
 * @param mysqli $conn Conexión a la base de datos
 * @param int $empresa_id ID de la empresa
 * @return array Datos de la empresa
 */
function getEmpresaInfo($conn, $empresa_id) {
    $stmt = $conn->prepare("
        SELECT
            nombre,
            razon_social,
            rut,
            logo,
            estado
        FROM empresas
        WHERE id = ?
    ");

    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result;
}

/**
 * Formatea número como moneda chilena
 * @param float $monto Monto a formatear
 * @return string Monto formateado
 */
function formatearMoneda($monto) {
    return '$' . number_format($monto, 0, ',', '.');
}

/**
 * Obtiene accesos rápidos del usuario (módulos favoritos)
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @return array Módulos favoritos
 */
function getAccesosRapidos($conn, $usuario_id) {
    $stmt = $conn->prepare("
        SELECT
            modulo,
            url,
            icono,
            titulo
        FROM usuario_favoritos
        WHERE usuario_id = ?
        ORDER BY orden ASC
        LIMIT 6
    ");

    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $favoritos = [];
    while ($row = $result->fetch_assoc()) {
        $favoritos[] = $row;
    }

    $stmt->close();

    // Si no hay favoritos, retornar accesos por defecto
    if (empty($favoritos)) {
        return [
            ['modulo' => 'clientes', 'url' => '../modulos/entidades/gestion_clientes.php', 'icono' => 'users', 'titulo' => 'Clientes'],
            ['modulo' => 'facturas', 'url' => '../modulos/finanzas/comprobantes_facturas.php', 'icono' => 'file-invoice', 'titulo' => 'Facturas'],
            ['modulo' => 'productos', 'url' => '../modulos/entidades/productos_servicios.php', 'icono' => 'box', 'titulo' => 'Productos'],
            ['modulo' => 'inventario', 'url' => '../modulos/materiales/gestion_inventario.php', 'icono' => 'warehouse', 'titulo' => 'Inventario']
        ];
    }

    return $favoritos;
}
