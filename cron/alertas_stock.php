<?php
/**
 * CRON JOB - ALERTAS DE STOCK MÍNIMO
 * Ejecutar cada 6 horas
 * Crontab: 0 */6 * * * /usr/bin/php /path/to/conecta-erp/cron/alertas_stock.php
 */

require_once __DIR__ . '/../includes/config.php';

// Buscar productos con stock bajo
$stmt = $conn->prepare("SELECT p.id, p.nombre, p.codigo, p.stock_minimo, e.id as empresa_id, e.nombre as empresa_nombre,
    COALESCE(SUM(i.cantidad), 0) as stock_actual
    FROM productos p
    INNER JOIN empresas e ON p.empresa_id = e.id
    LEFT JOIN inventario i ON p.id = i.producto_id
    WHERE p.activo = 1
    GROUP BY p.id, p.nombre, p.codigo, p.stock_minimo, e.id, e.nombre
    HAVING stock_actual < p.stock_minimo");

$stmt->execute();
$productos_bajos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($productos_bajos)) {
    echo "No hay productos con stock bajo\n";
    $conn->close();
    exit;
}

// Agrupar por empresa
$alertas_por_empresa = [];
foreach ($productos_bajos as $producto) {
    $empresa_id = $producto['empresa_id'];
    if (!isset($alertas_por_empresa[$empresa_id])) {
        $alertas_por_empresa[$empresa_id] = [
            'empresa_nombre' => $producto['empresa_nombre'],
            'productos' => []
        ];
    }
    $alertas_por_empresa[$empresa_id]['productos'][] = $producto;
}

// Crear notificaciones
foreach ($alertas_por_empresa as $empresa_id => $alerta) {
    $total_productos = count($alerta['productos']);

    $mensaje = "ALERTA: {$total_productos} producto(s) con stock bajo del mínimo:\n\n";
    foreach ($alerta['productos'] as $prod) {
        $mensaje .= "- {$prod['nombre']} ({$prod['codigo']}): Stock actual {$prod['stock_actual']}, Mínimo {$prod['stock_minimo']}\n";
    }

    // Notificar a usuarios con permiso de inventario
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje, url)
        SELECT u.id, ?, 'warning', 'Alerta de Stock Mínimo', ?, '../modulos/materiales/inventario.php'
        FROM usuarios u
        WHERE u.empresa_id = ? AND u.activo = 1
        AND (u.rol = 'admin' OR JSON_CONTAINS(u.permisos, '\"inventario\"'))");

    $stmt->bind_param("isi", $empresa_id, $mensaje, $empresa_id);
    $stmt->execute();
    $stmt->close();

    echo "Alertas creadas para empresa {$empresa_id}: {$total_productos} productos\n";
}

echo "Total: " . count($productos_bajos) . " productos con stock bajo\n";

$conn->close();
