<?php
/**
 * API REST - ÓRDENES DE COMPRA
 * Endpoint para gestión de órdenes de compra
 * Métodos: GET, POST, PUT, DELETE
 */

require_once 'config.php';

$auth = validarAPIKey();
$empresa_id = $auth['empresa_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $oc_id = $_GET['id'] ?? null;

    if ($oc_id) {
        $stmt = $conn->prepare("SELECT oc.*, p.razon_social as proveedor_nombre
            FROM ordenes_compra oc
            INNER JOIN proveedores p ON oc.proveedor_id = p.id
            WHERE oc.id = ? AND oc.empresa_id = ?");
        $stmt->bind_param("ii", $oc_id, $empresa_id);
        $stmt->execute();
        $oc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$oc) {
            enviarError(404, 'Orden de compra no encontrada', 'OC_NOT_FOUND');
        }

        // Obtener detalle
        $stmt = $conn->prepare("SELECT ocd.*, p.nombre as producto_nombre
            FROM orden_compra_detalle ocd
            INNER JOIN productos p ON ocd.producto_id = p.id
            WHERE ocd.orden_compra_id = ?");
        $stmt->bind_param("i", $oc_id);
        $stmt->execute();
        $oc['detalle'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        enviarExito($oc);
    } else {
        $paginacion = obtenerParametrosPaginacion();
        $estado = $_GET['estado'] ?? null;
        $proveedor_id = $_GET['proveedor_id'] ?? null;

        $where = ["oc.empresa_id = ?"];
        $params = [$empresa_id];
        $types = "i";

        if ($estado) {
            $where[] = "oc.estado = ?";
            $params[] = $estado;
            $types .= "s";
        }

        if ($proveedor_id) {
            $where[] = "oc.proveedor_id = ?";
            $params[] = $proveedor_id;
            $types .= "i";
        }

        $where_sql = implode(' AND ', $where);

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ordenes_compra oc WHERE {$where_sql}");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT oc.*, p.razon_social as proveedor_nombre
            FROM ordenes_compra oc
            INNER JOIN proveedores p ON oc.proveedor_id = p.id
            WHERE {$where_sql}
            ORDER BY oc.fecha_orden DESC
            LIMIT ? OFFSET ?");

        $params[] = $paginacion['per_page'];
        $params[] = $paginacion['offset'];
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $ordenes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $respuesta = respuestaPaginada($ordenes, $total, $paginacion);
        enviarExito($respuesta);
    }
}

elseif ($metodo === 'POST') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['proveedor_id', 'items']);

    $proveedor_id = intval($datos['proveedor_id']);
    $items = $datos['items'];
    $observaciones = $datos['observaciones'] ?? '';

    // Generar número OC
    $stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_orden, 3) AS UNSIGNED)), 0) + 1 as siguiente
        FROM ordenes_compra WHERE empresa_id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $siguiente = $stmt->get_result()->fetch_assoc()['siguiente'];
    $stmt->close();

    $numero_orden = sprintf("OC%06d", $siguiente);

    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += floatval($item['cantidad']) * floatval($item['precio_unitario']);
    }

    $impuestos = $subtotal * 0.19;
    $total = $subtotal + $impuestos;

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO ordenes_compra
            (empresa_id, proveedor_id, numero_orden, fecha_orden, subtotal, impuestos, total, estado, observaciones)
            VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 'pendiente', ?)");

        $stmt->bind_param("iisddds", $empresa_id, $proveedor_id, $numero_orden, $subtotal, $impuestos, $total, $observaciones);
        $stmt->execute();
        $oc_id = $conn->insert_id;
        $stmt->close();

        // Insertar detalle
        $stmt = $conn->prepare("INSERT INTO orden_compra_detalle
            (orden_compra_id, producto_id, cantidad, precio_unitario, total_linea)
            VALUES (?, ?, ?, ?, ?)");

        foreach ($items as $item) {
            $producto_id = intval($item['producto_id']);
            $cantidad = floatval($item['cantidad']);
            $precio_unitario = floatval($item['precio_unitario']);
            $total_linea = $cantidad * $precio_unitario;

            $stmt->bind_param("iiddd", $oc_id, $producto_id, $cantidad, $precio_unitario, $total_linea);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();

        $stmt = $conn->prepare("SELECT * FROM ordenes_compra WHERE id = ?");
        $stmt->bind_param("i", $oc_id);
        $stmt->execute();
        $oc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($oc, 'Orden de compra creada exitosamente', 201);

    } catch (Exception $e) {
        $conn->rollback();
        enviarError(500, 'Error al crear orden: ' . $e->getMessage(), 'DATABASE_ERROR');
    }
}

elseif ($metodo === 'PUT') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['id', 'estado']);

    $oc_id = intval($datos['id']);
    $estado = $datos['estado']; // pendiente, aprobada, recibida, cancelada

    $stmt = $conn->prepare("UPDATE ordenes_compra SET estado = ? WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("sii", $estado, $oc_id, $empresa_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM ordenes_compra WHERE id = ?");
        $stmt->bind_param("i", $oc_id);
        $stmt->execute();
        $oc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($oc, 'Orden actualizada exitosamente');
    } else {
        enviarError(404, 'Orden no encontrada', 'OC_NOT_FOUND');
    }
}

elseif ($metodo === 'DELETE') {
    $oc_id = $_GET['id'] ?? null;

    if (!$oc_id) {
        enviarError(400, 'ID requerido', 'MISSING_ID');
    }

    $stmt = $conn->prepare("UPDATE ordenes_compra SET estado = 'cancelada' WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $oc_id, $empresa_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        enviarExito(['id' => $oc_id, 'estado' => 'cancelada'], 'Orden cancelada');
    } else {
        enviarError(404, 'Orden no encontrada', 'OC_NOT_FOUND');
    }
}

else {
    enviarError(405, 'Método no permitido', 'METHOD_NOT_ALLOWED');
}
