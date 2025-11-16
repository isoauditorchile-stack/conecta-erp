<?php
/**
 * API REST - CLIENTES
 * Endpoint para gestión de clientes
 * Métodos: GET, POST, PUT, DELETE
 */

require_once 'config.php';

$auth = validarAPIKey();
$empresa_id = $auth['empresa_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

// =====================================================
// GET - Listar clientes o obtener uno específico
// =====================================================
if ($metodo === 'GET') {
    $cliente_id = $_GET['id'] ?? null;

    if ($cliente_id) {
        // Obtener cliente específico
        $stmt = $conn->prepare("SELECT c.*, COUNT(f.id) as total_facturas,
            COALESCE(SUM(f.total), 0) as total_compras
            FROM clientes c
            LEFT JOIN facturas f ON c.id = f.cliente_id AND f.estado != 'anulada'
            WHERE c.id = ? AND c.empresa_id = ?
            GROUP BY c.id");
        $stmt->bind_param("ii", $cliente_id, $empresa_id);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cliente) {
            enviarError(404, 'Cliente no encontrado', 'CLIENT_NOT_FOUND');
        }

        enviarExito($cliente, 'Cliente encontrado');

    } else {
        // Listar clientes con paginación y filtros
        $paginacion = obtenerParametrosPaginacion();
        $busqueda = $_GET['search'] ?? '';
        $tipo = $_GET['tipo'] ?? '';
        $activo = isset($_GET['activo']) ? intval($_GET['activo']) : null;

        // Construir query con filtros
        $where = ["c.empresa_id = ?"];
        $params = [$empresa_id];
        $types = "i";

        if ($busqueda) {
            $where[] = "(c.razon_social LIKE ? OR c.rut LIKE ? OR c.email LIKE ?)";
            $busqueda_param = "%{$busqueda}%";
            $params[] = $busqueda_param;
            $params[] = $busqueda_param;
            $params[] = $busqueda_param;
            $types .= "sss";
        }

        if ($tipo) {
            $where[] = "c.tipo_cliente = ?";
            $params[] = $tipo;
            $types .= "s";
        }

        if ($activo !== null) {
            $where[] = "c.activo = ?";
            $params[] = $activo;
            $types .= "i";
        }

        $where_sql = implode(' AND ', $where);

        // Contar total
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clientes c WHERE {$where_sql}");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Obtener datos paginados
        $stmt = $conn->prepare("SELECT c.*, COUNT(f.id) as total_facturas,
            COALESCE(SUM(f.total), 0) as total_compras
            FROM clientes c
            LEFT JOIN facturas f ON c.id = f.cliente_id AND f.estado != 'anulada'
            WHERE {$where_sql}
            GROUP BY c.id
            ORDER BY c.razon_social
            LIMIT ? OFFSET ?");

        $params[] = $paginacion['per_page'];
        $params[] = $paginacion['offset'];
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $respuesta = respuestaPaginada($clientes, $total, $paginacion);
        enviarExito($respuesta, 'Clientes obtenidos exitosamente');
    }
}

// =====================================================
// POST - Crear nuevo cliente
// =====================================================
elseif ($metodo === 'POST') {
    $datos = obtenerDatosInput();

    validarParametros($datos, ['razon_social', 'rut', 'tipo_cliente']);

    $razon_social = $datos['razon_social'];
    $rut = $datos['rut'];
    $tipo_cliente = $datos['tipo_cliente'];
    $giro = $datos['giro'] ?? '';
    $direccion = $datos['direccion'] ?? '';
    $comuna = $datos['comuna'] ?? '';
    $ciudad = $datos['ciudad'] ?? '';
    $telefono = $datos['telefono'] ?? '';
    $email = $datos['email'] ?? '';
    $contacto = $datos['contacto'] ?? '';
    $condicion_pago = $datos['condicion_pago'] ?? 'contado';
    $limite_credito = floatval($datos['limite_credito'] ?? 0);

    // Verificar RUT duplicado
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE rut = ? AND empresa_id = ?");
    $stmt->bind_param("si", $rut, $empresa_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        enviarError(409, 'El RUT ya existe', 'DUPLICATE_RUT');
    }
    $stmt->close();

    // Insertar cliente
    $stmt = $conn->prepare("INSERT INTO clientes
        (empresa_id, razon_social, rut, tipo_cliente, giro, direccion, comuna, ciudad,
         telefono, email, contacto, condicion_pago, limite_credito, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

    $stmt->bind_param("isssssssssssd", $empresa_id, $razon_social, $rut, $tipo_cliente,
        $giro, $direccion, $comuna, $ciudad, $telefono, $email, $contacto,
        $condicion_pago, $limite_credito);

    if ($stmt->execute()) {
        $cliente_id = $conn->insert_id;
        $stmt->close();

        // Obtener cliente creado
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($cliente, 'Cliente creado exitosamente', 201);
    } else {
        enviarError(500, 'Error al crear cliente: ' . $stmt->error, 'DATABASE_ERROR');
    }
}

// =====================================================
// PUT - Actualizar cliente existente
// =====================================================
elseif ($metodo === 'PUT') {
    $datos = obtenerDatosInput();
    validarParametros($datos, ['id']);

    $cliente_id = intval($datos['id']);

    // Verificar que el cliente existe y pertenece a la empresa
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $cliente_id, $empresa_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        enviarError(404, 'Cliente no encontrado', 'CLIENT_NOT_FOUND');
    }
    $stmt->close();

    // Construir UPDATE dinámico
    $campos_permitidos = ['razon_social', 'giro', 'direccion', 'comuna', 'ciudad',
        'telefono', 'email', 'contacto', 'condicion_pago', 'limite_credito', 'activo'];

    $sets = [];
    $params = [];
    $types = "";

    foreach ($campos_permitidos as $campo) {
        if (isset($datos[$campo])) {
            $sets[] = "{$campo} = ?";
            $params[] = $datos[$campo];
            $types .= is_numeric($datos[$campo]) ? "d" : "s";
        }
    }

    if (empty($sets)) {
        enviarError(400, 'No hay campos para actualizar', 'NO_FIELDS_TO_UPDATE');
    }

    $params[] = $cliente_id;
    $params[] = $empresa_id;
    $types .= "ii";

    $sql = "UPDATE clientes SET " . implode(', ', $sets) . " WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $stmt->close();

        // Obtener cliente actualizado
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        enviarExito($cliente, 'Cliente actualizado exitosamente');
    } else {
        enviarError(500, 'Error al actualizar cliente: ' . $stmt->error, 'DATABASE_ERROR');
    }
}

// =====================================================
// DELETE - Eliminar cliente (soft delete)
// =====================================================
elseif ($metodo === 'DELETE') {
    $cliente_id = $_GET['id'] ?? null;

    if (!$cliente_id) {
        enviarError(400, 'ID de cliente requerido', 'MISSING_ID');
    }

    // Verificar que no tenga facturas pendientes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM facturas
        WHERE cliente_id = ? AND estado IN ('emitida', 'borrador')");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($resultado['total'] > 0) {
        enviarError(409, 'No se puede eliminar: el cliente tiene facturas pendientes', 'HAS_PENDING_INVOICES');
    }

    // Soft delete
    $stmt = $conn->prepare("UPDATE clientes SET activo = 0 WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $cliente_id, $empresa_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        enviarExito(null, 'Cliente eliminado exitosamente');
    } else {
        enviarError(404, 'Cliente no encontrado', 'CLIENT_NOT_FOUND');
    }
}

else {
    enviarError(405, 'Método no permitido', 'METHOD_NOT_ALLOWED');
}
