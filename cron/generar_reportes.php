<?php
/**
 * CRON JOB - GENERACIÓN AUTOMÁTICA DE REPORTES
 * Ejecutar primer día de cada mes a las 06:00 AM
 * Crontab: 0 6 1 * * /usr/bin/php /path/to/conecta-erp/cron/generar_reportes.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$periodo = date('Y-m', strtotime('first day of last month'));
$fecha_inicio = date('Y-m-01', strtotime('first day of last month'));
$fecha_fin = date('Y-m-t', strtotime('first day of last month'));

echo "Generando reportes para período: {$periodo}\n";

// Obtener todas las empresas activas
$stmt = $conn->prepare("SELECT id, nombre FROM empresas WHERE activa = 1");
$stmt->execute();
$empresas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($empresas as $empresa) {
    $empresa_id = $empresa['id'];
    $empresa_nombre = $empresa['nombre'];

    echo "\n=== Generando reportes para: {$empresa_nombre} ===\n";

    // 1. REPORTE DE VENTAS
    $stmt = $conn->prepare("SELECT
        COUNT(*) as total_facturas,
        COALESCE(SUM(total), 0) as total_ventas,
        COALESCE(SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END), 0) as total_cobrado,
        COALESCE(SUM(CASE WHEN estado = 'emitida' THEN total ELSE 0 END), 0) as total_pendiente,
        COUNT(DISTINCT cliente_id) as clientes_unicos,
        COALESCE(AVG(total), 0) as ticket_promedio
        FROM facturas
        WHERE empresa_id = ?
        AND fecha_emision BETWEEN ? AND ?
        AND estado != 'anulada'");

    $stmt->bind_param("iss", $empresa_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $reporte_ventas = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 2. REPORTE DE COMPRAS
    $stmt = $conn->prepare("SELECT
        COUNT(*) as total_ordenes,
        COALESCE(SUM(total), 0) as total_compras,
        COUNT(DISTINCT proveedor_id) as proveedores_unicos
        FROM ordenes_compra
        WHERE empresa_id = ?
        AND fecha_orden BETWEEN ? AND ?
        AND estado != 'cancelada'");

    $stmt->bind_param("iss", $empresa_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $reporte_compras = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 3. REPORTE DE INVENTARIO
    $stmt = $conn->prepare("SELECT
        COUNT(DISTINCT p.id) as total_productos,
        COUNT(CASE WHEN stock_actual < p.stock_minimo THEN 1 END) as productos_stock_bajo,
        COALESCE(SUM(i.cantidad * p.precio_costo), 0) as valor_inventario
        FROM productos p
        LEFT JOIN inventario i ON p.id = i.producto_id
        WHERE p.empresa_id = ? AND p.activo = 1");

    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $reporte_inventario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 4. REPORTE FINANCIERO
    $stmt = $conn->prepare("SELECT
        (SELECT COALESCE(SUM(total), 0) FROM facturas
         WHERE empresa_id = ? AND fecha_emision BETWEEN ? AND ? AND estado != 'anulada') as ingresos,
        (SELECT COALESCE(SUM(total), 0) FROM ordenes_compra
         WHERE empresa_id = ? AND fecha_orden BETWEEN ? AND ? AND estado != 'cancelada') as egresos");

    $stmt->bind_param("ississ", $empresa_id, $fecha_inicio, $fecha_fin,
                               $empresa_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $reporte_financiero = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $utilidad = $reporte_financiero['ingresos'] - $reporte_financiero['egresos'];
    $margen = $reporte_financiero['ingresos'] > 0 ?
              ($utilidad / $reporte_financiero['ingresos'] * 100) : 0;

    // Guardar reporte en base de datos
    $stmt = $conn->prepare("INSERT INTO reportes_mensuales
        (empresa_id, periodo, total_facturas, total_ventas, total_cobrado, total_pendiente,
         clientes_unicos, ticket_promedio, total_compras, proveedores_unicos,
         total_productos, productos_stock_bajo, valor_inventario,
         ingresos, egresos, utilidad, margen_porcentaje, fecha_generacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param("isidddiididdddd", $empresa_id, $periodo,
        $reporte_ventas['total_facturas'], $reporte_ventas['total_ventas'],
        $reporte_ventas['total_cobrado'], $reporte_ventas['total_pendiente'],
        $reporte_ventas['clientes_unicos'], $reporte_ventas['ticket_promedio'],
        $reporte_compras['total_compras'], $reporte_compras['proveedores_unicos'],
        $reporte_inventario['total_productos'], $reporte_inventario['productos_stock_bajo'],
        $reporte_inventario['valor_inventario'],
        $reporte_financiero['ingresos'], $reporte_financiero['egresos'],
        $utilidad, $margen);

    $stmt->execute();
    $stmt->close();

    // Crear notificación
    $mensaje = "Reporte mensual de {$periodo} generado:\n\n" .
               "💰 Ventas: " . formatearMoneda($reporte_ventas['total_ventas']) . "\n" .
               "📦 Compras: " . formatearMoneda($reporte_compras['total_compras']) . "\n" .
               "💵 Utilidad: " . formatearMoneda($utilidad) . " (" . number_format($margen, 2) . "%)\n" .
               "📊 Margen: " . number_format($margen, 2) . "%";

    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje, url)
        SELECT u.id, ?, 'info', 'Reporte Mensual Generado', ?, '../modulos/bi/reportes_personalizados.php'
        FROM usuarios u
        WHERE u.empresa_id = ? AND u.activo = 1 AND u.rol IN ('admin', 'gerente')");

    $stmt->bind_param("isi", $empresa_id, $mensaje, $empresa_id);
    $stmt->execute();
    $stmt->close();

    echo "✓ Reporte generado para {$empresa_nombre}\n";
    echo "  Ventas: " . formatearMoneda($reporte_ventas['total_ventas']) . "\n";
    echo "  Utilidad: " . formatearMoneda($utilidad) . "\n";
}

echo "\n=== Proceso completado ===\n";
echo "Total empresas procesadas: " . count($empresas) . "\n";

$conn->close();
