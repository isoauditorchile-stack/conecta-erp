<?php
/**
 * API REST - FACTURAS
 * Endpoint para gestión de facturas y documentos tributarios
 * Métodos: GET, POST, PUT, DELETE
 */

require_once 'config.php';

$auth = validarAPIKey();
$empresa_id = $auth['empresa_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

// =====================================================
// GET - Listar facturas o obtener una específica
// =====================================================
if ($metodo === 'GET') {
    $factura_id = $_GET['id'] ?? null;

    if ($factura_id) {
        // Obtener factura específica con detalle
        $stmt = $conn->prepare("SELECT f.*, c.razon_social as cliente_nombre,
            c.rut as cliente_rut, c.direccion as cliente_direccion,
            u.nombre_completo as vendedor_nombre
            FROM facturas f
            INNER JOIN clientes c ON f.cliente_id = c.id
            LEFT JOIN usuarios u ON f.vendedor_id = u.id
            WHERE f.id = ? AND f.empresa_id = ?");
        $stmt->bind_param("ii", $factura_id, $empresa_id);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$factura) {
            enviarError(404, 'Factura no encontrada', 'INVOICE_NOT_FOUND');
        }

        // Obtener detalle de la factura
        $stmt = $conn->prepare("SELECT fd.*, p.nombre as producto_nombre,
            p.codigo as producto_codigo
            FROM factura_detalle fd
            INNER JOIN productos p ON fd.producto_id = p.id
            WHERE fd.factura_id = ?
            ORDER BY fd.linea");
        $stmt->bind_param("i", $factura_id);
        $stmt->execute();
        $factura['detalle'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        enviarExito($factura, 'Factura encontrada');

    } else {
        // Listar facturas con filtros
        $paginacion = obtenerParametrosPaginacion();
        $cliente_id = $_GET['cliente_id'] ?? null;
        $estado = $_GET['estado'] ?? null;
        $fecha_desde = $_GET['fecha_desde'] ?? null;
        $fecha_hasta = $_GET['fecha_hasta'] ?? null;
        $numero_factura = $_GET['numero'] ?? null;

        $where = ["f.empresa_id = ?"];
        $params = [$empresa_id];
        $types = "i";

        if ($cliente_id) {
            $where[] = "f.cliente_id = ?";
            $params[] = $cliente_id;
            $types .= "i";
        }

        if ($estado) {
            $where[] = "f.estado = ?";
            $params[] = $estado;
            $types .= "s";
        }

        if ($fecha_desde) {
            $where[] = "f.fecha_emision >= ?";
            $params[] = $fecha_desde;
            $types .= "s";
        }

        if ($fecha_hasta) {
            $where[] = "f.fecha_emision <= ?";
            $params[] = $fecha_hasta;
            $types .= "s";
        }

        if ($numero_factura) {
            $where[] = "f.numero_factura LIKE ?";
            $numero_param = "%{$numero_factura}%";
            $params[] = $numero_param;
            $types .= "s";
        }

        $where_sql = implode(' AND ', $where);

        // Contar total
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM facturas f WHERE {$where_sql}");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Obtener datos paginados
        $stmt = $conn->prepare("SELECT f.id, f.numero_factura, f.tipo_documento,
            f.fecha_emision, f.fecha_vencimiento, f.estado,
            f.subtotal, f.impuestos, f.total,
            c.razon_social as cliente_nombre, c.rut as cliente_rut
            FROM facturas f
            INNER JOIN clientes c ON f.cliente_id = c.id
            WHERE {$where_sql}
            ORDER BY f.fecha_emision DESC, f.id DESC
            LIMIT ? OFFSET ?");

        $params[] = $paginacion['per_page'];
        $params[] = $paginacion['offset'];
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $facturas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $respuesta = respuestaPaginada($facturas, $total, $paginacion);
        enviarExito($respuesta, 'Facturas obtenidas exitosamente');
    }
}

// =====================================================
// POST - Crear nueva factura
// =====================================================
elseif ($metodo === 'POST') {
    $datos = obtenerDatosInput();

    validarParametros($datos, ['cliente_id', 'tipo_documento', 'fecha_emision', 'items']);

    $cliente_id = intval($datos['cliente_id']);
    $tipo_documento = $datos['tipo_documento']; // factura, boleta, nota_credito, nota_debito
    $fecha_emision = $datos['fecha_emision'];
    $fecha_vencimiento = $datos['fecha_vencimiento'] ?? null;
    $condicion_pago = $datos['condicion_pago'] ?? 'contado';
    $observaciones = $datos['observaciones'] ?? '';
    $items = $datos['items'];

    if (empty($items) || !is_array($items)) {
        enviarError(400, 'Debe incluir al menos un item', 'MISSING_ITEMS');
    }

    // Verificar que el cliente existe
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $cliente_id, $empresa_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        enviarError(404, 'Cliente no encontrado', 'CLIENT_NOT_FOUND');
    }
    $stmt->close();

    // Generar número de factura
    $stmt = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(numero_factura, 4) AS UNSIGNED)), 0) + 1 as siguiente
        FROM facturas WHERE empresa_id = ? AND tipo_documento = ?");
    $stmt->bind_param("is", $empresa_id, $tipo_documento);
    $stmt->execute();
    $siguiente = $stmt->get_result()->fetch_assoc()['siguiente'];
    $stmt->close();

    $numero_factura = sprintf("FAC%06d", $siguiente);

    // Calcular totales
    $subtotal = 0;
    foreach ($items as $item) {
        $cantidad = floatval($item['cantidad']);
        $precio_unitario = floatval($item['precio_unitario']);
        $subtotal += $cantidad * $precio_unitario;
    }

    // Obtener tasa de impuesto (IVA)
    $tasa_impuesto = 19.0; // Configurable
    $impuestos = $subtotal * ($tasa_impuesto / 100);
    $total = $subtotal + $impuestos;

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Insertar factura
        $stmt = $conn->prepare("INSERT INTO facturas
            (empresa_id, cliente_id, numero_factura, tipo_documento, fecha_emision, fecha_vencimiento,
             condicion_pago, subtotal, impuestos, total, estado, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitida', ?)");

        $stmt->bind_param("iisssssddds", $empresa_id, $cliente_id, $numero_factura, $tipo_documento,
            $fecha_emision, $fecha_vencimiento, $condicion_pago, $subtotal, $impuestos, $total, $observaciones);

        $stmt->execute();
        $factura_id = $conn->insert_id;
        $stmt->close();

        // Insertar detalle
        $stmt = $conn->prepare("INSERT INTO factura_detalle
            (factura_id, linea, producto_id, descripcion, cantidad, precio_unitario, descuento, total_linea)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $linea = 1;
        foreach ($items as $item) {
            $producto_id = isset($item['producto_id']) ? intval($item['producto_id']) : null;
            $descripcion = $item['descripcion'];
            $cantidad = floatval($item['cantidad']);
            $precio_unitario = floatval($item['precio_unitario']);
            $descuento = floatval($item['descuento'] ?? 0);
            $total_linea = ($cantidad * $precio_unitario) - $descuento;

            $stmt->bind_param("iiisddd", $factura_id, $linea, $producto_id, $descripcion,
                $cantidad, $precio_unitario, $descuento, $total_linea);

            $stmt->execute();
            $linea++;
        }
        $stmt->close();

        // Commit transacción
        $conn->commit();

        // Obtener factura creada
        $stmt = $conn->prepare("SELECT * FROM facturas WHERE id = ?");
        $stmt->bind_param("i", $factura_id);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($factura, 'Factura creada exitosamente', 201);

    } catch (Exception $e) {
        $conn->rollback();
        enviarError(500, 'Error al crear factura: ' . $e->getMessage(), 'DATABASE_ERROR');
    }
}

// =====================================================
// PUT - Actualizar factura
// =====================================================
elseif ($metodo === 'PUT') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['id']);

    $factura_id = intval($datos['id']);

    // Verificar que la factura existe
    $stmt = $conn->prepare("SELECT estado FROM facturas WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $factura_id, $empresa_id);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$factura) {
        enviarError(404, 'Factura no encontrada', 'INVOICE_NOT_FOUND');
    }

    // Solo se puede modificar si está en estado borrador o emitida
    if (!in_array($factura['estado'], ['borrador', 'emitida'])) {
        enviarError(400, 'No se puede modificar una factura en estado: ' . $factura['estado'], 'INVALID_STATE');
    }

    $campos_permitidos = ['fecha_vencimiento', 'observaciones', 'estado'];

    $sets = [];
    $params = [];
    $types = "";

    foreach ($campos_permitidos as $campo) {
        if (isset($datos[$campo])) {
            $sets[] = "{$campo} = ?";
            $params[] = $datos[$campo];
            $types .= "s";
        }
    }

    if (empty($sets)) {
        enviarError(400, 'No hay campos para actualizar', 'NO_FIELDS_TO_UPDATE');
    }

    $params[] = $factura_id;
    $params[] = $empresa_id;
    $types .= "ii";

    $sql = "UPDATE facturas SET " . implode(', ', $sets) . " WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM facturas WHERE id = ?");
        $stmt->bind_param("i", $factura_id);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($factura, 'Factura actualizada exitosamente');
    } else {
        enviarError(500, 'Error al actualizar factura: ' . $stmt->error, 'DATABASE_ERROR');
    }
}

// =====================================================
// DELETE - Anular factura
// =====================================================
elseif ($metodo === 'DELETE') {
    $factura_id = $_GET['id'] ?? null;

    if (!$factura_id) {
        enviarError(400, 'ID de factura requerido', 'MISSING_ID');
    }

    // Verificar que existe
    $stmt = $conn->prepare("SELECT estado FROM facturas WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $factura_id, $empresa_id);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$factura) {
        enviarError(404, 'Factura no encontrada', 'INVOICE_NOT_FOUND');
    }

    // Solo se puede anular si está emitida
    if (!in_array($factura['estado'], ['emitida', 'borrador'])) {
        enviarError(400, 'No se puede anular una factura en estado: ' . $factura['estado'], 'INVALID_STATE');
    }

    $stmt = $conn->prepare("UPDATE facturas SET estado = 'anulada' WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $factura_id, $empresa_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        enviarExito(['id' => $factura_id, 'estado' => 'anulada'], 'Factura anulada exitosamente');
    } else {
        enviarError(500, 'Error al anular factura', 'DATABASE_ERROR');
    }
}

else {
    enviarError(405, 'Método no permitido', 'METHOD_NOT_ALLOWED');
}
