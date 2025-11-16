<?php
/**
 * CRON JOB - RECORDATORIOS DE FACTURAS POR VENCER
 * Ejecutar diariamente a las 08:00 AM
 * Crontab: 0 8 * * * /usr/bin/php /path/to/conecta-erp/cron/recordatorios_facturas.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Buscar facturas por vencer (próximos 7 días)
$stmt = $conn->prepare("SELECT f.id, f.numero_factura, f.total, f.fecha_vencimiento,
    c.razon_social as cliente, c.email as cliente_email, c.telefono as cliente_telefono,
    e.id as empresa_id, e.nombre as empresa_nombre,
    DATEDIFF(f.fecha_vencimiento, CURDATE()) as dias_restantes
    FROM facturas f
    INNER JOIN clientes c ON f.cliente_id = c.id
    INNER JOIN empresas e ON f.empresa_id = e.id
    WHERE f.estado = 'emitida'
    AND f.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY f.fecha_vencimiento");

$stmt->execute();
$facturas_por_vencer = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($facturas_por_vencer)) {
    echo "No hay facturas por vencer en los próximos 7 días\n";
    $conn->close();
    exit;
}

foreach ($facturas_por_vencer as $factura) {
    $dias = $factura['dias_restantes'];
    $urgencia = $dias <= 3 ? 'error' : 'warning';

    $mensaje = "RECORDATORIO: La factura {$factura['numero_factura']} de {$factura['cliente']} " .
               "vence en {$dias} día(s). Monto: " . formatearMoneda($factura['total']);

    // Notificar a usuarios de finanzas
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje, url)
        SELECT u.id, ?, ?, 'Factura por Vencer', ?, '../modulos/finanzas/cuentas_por_cobrar.php'
        FROM usuarios u
        WHERE u.empresa_id = ? AND u.activo = 1
        AND (u.rol = 'admin' OR JSON_CONTAINS(u.permisos, '\"finanzas\"'))");

    $stmt->bind_param("issi", $factura['empresa_id'], $urgencia, $mensaje, $factura['empresa_id']);
    $stmt->execute();
    $stmt->close();

    echo "Recordatorio creado para factura {$factura['numero_factura']} ({$dias} días)\n";
}

// Buscar facturas vencidas
$stmt = $conn->prepare("UPDATE facturas
    SET estado = 'vencida'
    WHERE estado = 'emitida' AND fecha_vencimiento < CURDATE()");
$stmt->execute();
$facturas_vencidas = $stmt->affected_rows;
$stmt->close();

if ($facturas_vencidas > 0) {
    echo "Marcadas {$facturas_vencidas} facturas como vencidas\n";

    // Notificar facturas vencidas
    $stmt = $conn->prepare("SELECT f.id, f.numero_factura, f.total, f.fecha_vencimiento,
        c.razon_social as cliente, e.id as empresa_id,
        DATEDIFF(CURDATE(), f.fecha_vencimiento) as dias_vencida
        FROM facturas f
        INNER JOIN clientes c ON f.cliente_id = c.id
        INNER JOIN empresas e ON f.empresa_id = e.id
        WHERE f.estado = 'vencida' AND DATE(f.fecha_modificacion) = CURDATE()");

    $stmt->execute();
    $facturas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($facturas as $factura) {
        $mensaje = "URGENTE: Factura {$factura['numero_factura']} de {$factura['cliente']} " .
                   "vencida hace {$factura['dias_vencida']} día(s). Monto: " . formatearMoneda($factura['total']);

        $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, empresa_id, tipo, titulo, mensaje, url)
            SELECT u.id, ?, 'error', 'Factura Vencida', ?, '../modulos/finanzas/cuentas_por_cobrar.php'
            FROM usuarios u
            WHERE u.empresa_id = ? AND u.activo = 1
            AND (u.rol = 'admin' OR JSON_CONTAINS(u.permisos, '\"finanzas\"'))");

        $stmt->bind_param("isi", $factura['empresa_id'], $mensaje, $factura['empresa_id']);
        $stmt->execute();
        $stmt->close();
    }
}

echo "Proceso completado: " . count($facturas_por_vencer) . " recordatorios, {$facturas_vencidas} vencidas\n";

$conn->close();
