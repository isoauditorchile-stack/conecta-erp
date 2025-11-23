<?php
session_start();

// Database connection - EXACT as specified
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=conectae_conectaerpbd;charset=utf8mb4",
        "conectae_conectaerpuser",
        "pt125824caraud"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Initialize messages
$message = '';
$messageType = '';

// ============================================================================
// CRUD OPERATIONS - MAESTRO PROVEEDORES
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CREATE/UPDATE PROVEEDOR
    if (isset($_POST['action']) && $_POST['action'] === 'save_proveedor') {
        try {
            $id = $_POST['id'] ?? null;
            $codigo = $_POST['codigo'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $razon_social = $_POST['razon_social'] ?? '';
            $rfc = $_POST['rfc'] ?? '';
            $giro = $_POST['giro'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $email = $_POST['email'] ?? '';
            $sitio_web = $_POST['sitio_web'] ?? '';
            $tipo_proveedor = $_POST['tipo_proveedor'] ?? '';
            $categoria = $_POST['categoria'] ?? '';
            $pais = $_POST['pais'] ?? '';
            $condicion_pago = $_POST['condicion_pago'] ?? '';
            $moneda = $_POST['moneda'] ?? 'MXN';
            $plazo_entrega = $_POST['plazo_entrega'] ?? '';
            $calificacion = $_POST['calificacion'] ?? 0;
            $observaciones = $_POST['observaciones'] ?? '';
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($id) {
                // UPDATE
                $sql = "UPDATE proveedores SET
                        codigo = ?, nombre = ?, razon_social = ?, rfc = ?, giro = ?,
                        telefono = ?, email = ?, sitio_web = ?, tipo_proveedor = ?, categoria = ?,
                        pais = ?, condicion_pago = ?, moneda = ?, plazo_entrega = ?,
                        calificacion = ?, observaciones = ?, activo = ?,
                        fecha_modificacion = NOW()
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo, $nombre, $razon_social, $rfc, $giro, $telefono, $email,
                               $sitio_web, $tipo_proveedor, $categoria, $pais, $condicion_pago,
                               $moneda, $plazo_entrega, $calificacion, $observaciones, $activo, $id]);
                $message = "Proveedor actualizado exitosamente";
            } else {
                // CREATE
                $sql = "INSERT INTO proveedores (codigo, nombre, razon_social, rfc, giro, telefono, email,
                        sitio_web, tipo_proveedor, categoria, pais, condicion_pago, moneda,
                        plazo_entrega, calificacion, observaciones, activo, fecha_creacion)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo, $nombre, $razon_social, $rfc, $giro, $telefono, $email,
                               $sitio_web, $tipo_proveedor, $categoria, $pais, $condicion_pago,
                               $moneda, $plazo_entrega, $calificacion, $observaciones, $activo]);
                $message = "Proveedor creado exitosamente";
            }
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // DELETE PROVEEDOR
    if (isset($_POST['action']) && $_POST['action'] === 'delete_proveedor') {
        try {
            $id = $_POST['id'];
            $sql = "DELETE FROM proveedores WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $message = "Proveedor eliminado exitosamente";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error al eliminar: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // ============================================================================
    // CRUD OPERATIONS - CONTACTOS
    // ============================================================================
    if (isset($_POST['action']) && $_POST['action'] === 'save_contacto') {
        try {
            $id = $_POST['contacto_id'] ?? null;
            $proveedor_id = $_POST['proveedor_id'];
            $nombre = $_POST['nombre'];
            $cargo = $_POST['cargo'];
            $departamento = $_POST['departamento'] ?? '';
            $telefono = $_POST['telefono'];
            $extension = $_POST['extension'] ?? '';
            $celular = $_POST['celular'] ?? '';
            $email = $_POST['email'];
            $principal = isset($_POST['principal']) ? 1 : 0;
            $notas = $_POST['notas'] ?? '';

            if ($id) {
                // UPDATE
                $sql = "UPDATE proveedores_contactos SET
                        nombre = ?, cargo = ?, departamento = ?, telefono = ?, extension = ?,
                        celular = ?, email = ?, principal = ?, notas = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $cargo, $departamento, $telefono, $extension,
                               $celular, $email, $principal, $notas, $id]);
                $message = "Contacto actualizado exitosamente";
            } else {
                // CREATE
                $sql = "INSERT INTO proveedores_contactos (proveedor_id, nombre, cargo, departamento,
                        telefono, extension, celular, email, principal, notas)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$proveedor_id, $nombre, $cargo, $departamento, $telefono,
                               $extension, $celular, $email, $principal, $notas]);
                $message = "Contacto creado exitosamente";
            }
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_contacto') {
        try {
            $id = $_POST['id'];
            $sql = "DELETE FROM proveedores_contactos WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $message = "Contacto eliminado exitosamente";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // ============================================================================
    // CRUD OPERATIONS - CONTRATOS
    // ============================================================================
    if (isset($_POST['action']) && $_POST['action'] === 'save_contrato') {
        try {
            $id = $_POST['contrato_id'] ?? null;
            $proveedor_id = $_POST['proveedor_id'];
            $numero_contrato = $_POST['numero_contrato'];
            $descripcion = $_POST['descripcion'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $monto = $_POST['monto'];
            $moneda = $_POST['moneda'];
            $estado = $_POST['estado'];
            $tipo_contrato = $_POST['tipo_contrato'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';

            if ($id) {
                // UPDATE
                $sql = "UPDATE proveedores_contratos SET
                        numero_contrato = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?,
                        monto = ?, moneda = ?, estado = ?, tipo_contrato = ?, observaciones = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$numero_contrato, $descripcion, $fecha_inicio, $fecha_fin,
                               $monto, $moneda, $estado, $tipo_contrato, $observaciones, $id]);
                $message = "Contrato actualizado exitosamente";
            } else {
                // CREATE
                $sql = "INSERT INTO proveedores_contratos (proveedor_id, numero_contrato, descripcion,
                        fecha_inicio, fecha_fin, monto, moneda, estado, tipo_contrato, observaciones)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$proveedor_id, $numero_contrato, $descripcion, $fecha_inicio,
                               $fecha_fin, $monto, $moneda, $estado, $tipo_contrato, $observaciones]);
                $message = "Contrato creado exitosamente";
            }
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_contrato') {
        try {
            $id = $_POST['id'];
            $sql = "DELETE FROM proveedores_contratos WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $message = "Contrato eliminado exitosamente";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // ============================================================================
    // CRUD OPERATIONS - EVALUACIONES
    // ============================================================================
    if (isset($_POST['action']) && $_POST['action'] === 'save_evaluacion') {
        try {
            $id = $_POST['evaluacion_id'] ?? null;
            $proveedor_id = $_POST['proveedor_id'];
            $fecha_evaluacion = $_POST['fecha_evaluacion'];
            $periodo = $_POST['periodo'];
            $calidad = $_POST['calidad'] ?? 0;
            $entrega = $_POST['entrega'] ?? 0;
            $precio = $_POST['precio'] ?? 0;
            $servicio = $_POST['servicio'] ?? 0;
            $documentacion = $_POST['documentacion'] ?? 0;
            $innovacion = $_POST['innovacion'] ?? 0;

            // Calculate average
            $calificacion_total = ($calidad + $entrega + $precio + $servicio + $documentacion + $innovacion) / 6;

            // Determine nivel_desempeno
            if ($calificacion_total >= 9) {
                $nivel_desempeno = 'EXCELENTE';
            } elseif ($calificacion_total >= 8) {
                $nivel_desempeno = 'MUY BUENO';
            } elseif ($calificacion_total >= 7) {
                $nivel_desempeno = 'BUENO';
            } elseif ($calificacion_total >= 6) {
                $nivel_desempeno = 'ACEPTABLE';
            } else {
                $nivel_desempeno = 'DEFICIENTE';
            }

            $estado = $_POST['estado'];
            $observaciones = $_POST['observaciones'] ?? '';
            $evaluador = $_POST['evaluador'] ?? '';

            if ($id) {
                // UPDATE
                $sql = "UPDATE proveedores_evaluaciones SET
                        fecha_evaluacion = ?, periodo = ?, calidad = ?, entrega = ?, precio = ?,
                        servicio = ?, documentacion = ?, innovacion = ?, calificacion_total = ?,
                        nivel_desempeno = ?, estado = ?, observaciones = ?, evaluador = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fecha_evaluacion, $periodo, $calidad, $entrega, $precio,
                               $servicio, $documentacion, $innovacion, $calificacion_total,
                               $nivel_desempeno, $estado, $observaciones, $evaluador, $id]);
                $message = "Evaluación actualizada exitosamente";
            } else {
                // CREATE
                $sql = "INSERT INTO proveedores_evaluaciones (proveedor_id, fecha_evaluacion, periodo,
                        calidad, entrega, precio, servicio, documentacion, innovacion,
                        calificacion_total, nivel_desempeno, estado, observaciones, evaluador)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$proveedor_id, $fecha_evaluacion, $periodo, $calidad, $entrega,
                               $precio, $servicio, $documentacion, $innovacion, $calificacion_total,
                               $nivel_desempeno, $estado, $observaciones, $evaluador]);
                $message = "Evaluación creada exitosamente";
            }
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_evaluacion') {
        try {
            $id = $_POST['id'];
            $sql = "DELETE FROM proveedores_evaluaciones WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $message = "Evaluación eliminada exitosamente";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // ============================================================================
    // CRUD OPERATIONS - CONDICIONES COMERCIALES
    // ============================================================================
    if (isset($_POST['action']) && $_POST['action'] === 'save_condicion') {
        try {
            $id = $_POST['condicion_id'] ?? null;
            $proveedor_id = $_POST['proveedor_id'];
            $codigo_condicion = $_POST['codigo_condicion'];
            $descripcion = $_POST['descripcion'];
            $tipo_condicion = $_POST['tipo_condicion'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $descuento = $_POST['descuento'] ?? 0;
            $volumen_minimo = $_POST['volumen_minimo'] ?? 0;
            $condiciones = $_POST['condiciones'] ?? '';
            $estado = $_POST['estado'];

            if ($id) {
                // UPDATE
                $sql = "UPDATE proveedores_condiciones SET
                        codigo_condicion = ?, descripcion = ?, tipo_condicion = ?, fecha_inicio = ?,
                        fecha_fin = ?, descuento = ?, volumen_minimo = ?, condiciones = ?, estado = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$codigo_condicion, $descripcion, $tipo_condicion, $fecha_inicio,
                               $fecha_fin, $descuento, $volumen_minimo, $condiciones, $estado, $id]);
                $message = "Condición comercial actualizada exitosamente";
            } else {
                // CREATE
                $sql = "INSERT INTO proveedores_condiciones (proveedor_id, codigo_condicion, descripcion,
                        tipo_condicion, fecha_inicio, fecha_fin, descuento, volumen_minimo, condiciones, estado)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$proveedor_id, $codigo_condicion, $descripcion, $tipo_condicion,
                               $fecha_inicio, $fecha_fin, $descuento, $volumen_minimo, $condiciones, $estado]);
                $message = "Condición comercial creada exitosamente";
            }
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_condicion') {
        try {
            $id = $_POST['id'];
            $sql = "DELETE FROM proveedores_condiciones WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $message = "Condición comercial eliminada exitosamente";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // ============================================================================
    // CRUD OPERATIONS - DIRECCIONES
    // ============================================================================
    if (isset($_POST['action']) && $_POST['action'] === 'save_direccion') {
        try {
            $id = $_POST['direccion_id'] ?? null;
            $proveedor_id = $_POST['proveedor_id'];
            $tipo = $_POST['tipo'];
            $calle = $_POST['calle'];
            $numero_ext = $_POST['numero_ext'];
            $numero_int = $_POST['numero_int'] ?? '';
            $colonia = $_POST['colonia'];
            $ciudad = $_POST['ciudad'];
            $estado = $_POST['estado'];
            $codigo_postal = $_POST['codigo_postal'];
            $pais = $_POST['pais'];
            $predeterminada = isset($_POST['predeterminada']) ? 1 : 0;

            if ($id) {
                // UPDATE
                $sql = "UPDATE direccion SET
                        tipo = ?, calle = ?, numero_ext = ?, numero_int = ?, colonia = ?,
                        ciudad = ?, estado = ?, codigo_postal = ?, pais = ?, predeterminada = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$tipo, $calle, $numero_ext, $numero_int, $colonia, $ciudad,
                               $estado, $codigo_postal, $pais, $predeterminada, $id]);
                $message = "Dirección actualizada exitosamente";
            } else {
                // CREATE - For PROVEEDOR entity type
                $sql = "INSERT INTO direccion (entidad_tipo, entidad_id, tipo, calle, numero_ext, numero_int,
                        colonia, ciudad, estado, codigo_postal, pais, predeterminada)
                        VALUES ('PROVEEDOR', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$proveedor_id, $tipo, $calle, $numero_ext, $numero_int, $colonia,
                               $ciudad, $estado, $codigo_postal, $pais, $predeterminada]);
                $message = "Dirección creada exitosamente";
            }
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_direccion') {
        try {
            $id = $_POST['id'];
            $sql = "DELETE FROM direccion WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $message = "Dirección eliminada exitosamente";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get selected proveedor_id
$selected_proveedor_id = $_GET['proveedor_id'] ?? null;

// Fetch all proveedores
$sql_proveedores = "SELECT * FROM proveedores ORDER BY codigo";
$stmt_proveedores = $pdo->query($sql_proveedores);
$proveedores = $stmt_proveedores->fetchAll();

// Fetch contactos for selected proveedor
$contactos = [];
if ($selected_proveedor_id) {
    $sql_contactos = "SELECT * FROM proveedores_contactos WHERE proveedor_id = ? ORDER BY principal DESC, id DESC";
    $stmt_contactos = $pdo->prepare($sql_contactos);
    $stmt_contactos->execute([$selected_proveedor_id]);
    $contactos = $stmt_contactos->fetchAll();
}

// Fetch contratos for selected proveedor
$contratos = [];
if ($selected_proveedor_id) {
    $sql_contratos = "SELECT * FROM proveedores_contratos WHERE proveedor_id = ? ORDER BY fecha_inicio DESC";
    $stmt_contratos = $pdo->prepare($sql_contratos);
    $stmt_contratos->execute([$selected_proveedor_id]);
    $contratos = $stmt_contratos->fetchAll();
}

// Fetch evaluaciones for selected proveedor
$evaluaciones = [];
if ($selected_proveedor_id) {
    $sql_evaluaciones = "SELECT * FROM proveedores_evaluaciones WHERE proveedor_id = ? ORDER BY fecha_evaluacion DESC";
    $stmt_evaluaciones = $pdo->prepare($sql_evaluaciones);
    $stmt_evaluaciones->execute([$selected_proveedor_id]);
    $evaluaciones = $stmt_evaluaciones->fetchAll();
}

// Fetch condiciones for selected proveedor
$condiciones = [];
if ($selected_proveedor_id) {
    $sql_condiciones = "SELECT * FROM proveedores_condiciones WHERE proveedor_id = ? ORDER BY fecha_inicio DESC";
    $stmt_condiciones = $pdo->prepare($sql_condiciones);
    $stmt_condiciones->execute([$selected_proveedor_id]);
    $condiciones = $stmt_condiciones->fetchAll();
}

// Fetch direcciones for selected proveedor
$direcciones = [];
if ($selected_proveedor_id) {
    $sql_direcciones = "SELECT * FROM direccion WHERE entidad_tipo = 'PROVEEDOR' AND entidad_id = ? ORDER BY predeterminada DESC, id DESC";
    $stmt_direcciones = $pdo->prepare($sql_direcciones);
    $stmt_direcciones->execute([$selected_proveedor_id]);
    $direcciones = $stmt_direcciones->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .message {
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 0 20px;
            overflow-x: auto;
        }

        .tab {
            padding: 15px 25px;
            cursor: pointer;
            border: none;
            background: transparent;
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab:hover {
            background: rgba(0,0,0,0.05);
            color: #1e3c72;
        }

        .tab.active {
            color: #1e3c72;
            border-bottom-color: #1e3c72;
            background: white;
        }

        .tab-content {
            display: none;
            padding: 30px;
            animation: fadeIn 0.3s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(86, 171, 47, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(235, 51, 73, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }

        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody td {
            padding: 15px;
            font-size: 14px;
            color: #333;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }

        /* MODAL STYLES */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 50px auto;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 22px;
        }

        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            line-height: 1;
        }

        .close:hover,
        .close:focus {
            transform: rotate(90deg);
            opacity: 0.8;
        }

        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            color: #1e3c72;
            font-size: 24px;
        }

        .proveedor-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .proveedor-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #495057;
        }

        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
            }

            .actions {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestión de Proveedores</h1>
            <p>Sistema de Administración de Relaciones con Proveedores - SAP Style ERP</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="openTab(event, 'maestro')">Maestro Proveedores</button>
            <button class="tab" onclick="openTab(event, 'contactos')">Contactos</button>
            <button class="tab" onclick="openTab(event, 'contratos')">Contratos</button>
            <button class="tab" onclick="openTab(event, 'evaluaciones')">Evaluaciones</button>
            <button class="tab" onclick="openTab(event, 'condiciones')">Condiciones Comerciales</button>
            <button class="tab" onclick="openTab(event, 'direcciones')">Direcciones</button>
        </div>

        <!-- ============================================================================ -->
        <!-- TAB: MAESTRO PROVEEDORES -->
        <!-- ============================================================================ -->
        <div id="maestro" class="tab-content active">
            <div class="section-header">
                <h2>Maestro de Proveedores</h2>
                <button class="btn btn-success" onclick="clearFormProveedor()">+ Nuevo Proveedor</button>
            </div>

            <form method="POST" id="formProveedor">
                <input type="hidden" name="action" value="save_proveedor">
                <input type="hidden" name="id" id="proveedor_id">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Código *</label>
                        <input type="text" name="codigo" id="codigo" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre Comercial *</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Razón Social *</label>
                        <input type="text" name="razon_social" id="razon_social" required>
                    </div>
                    <div class="form-group">
                        <label>RFC</label>
                        <input type="text" name="rfc" id="rfc">
                    </div>
                    <div class="form-group">
                        <label>Giro</label>
                        <input type="text" name="giro" id="giro">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="telefono">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="email">
                    </div>
                    <div class="form-group">
                        <label>Sitio Web</label>
                        <input type="text" name="sitio_web" id="sitio_web">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Proveedor</label>
                        <select name="tipo_proveedor" id="tipo_proveedor">
                            <option value="">Seleccionar...</option>
                            <option value="Materias Primas">Materias Primas</option>
                            <option value="Servicios">Servicios</option>
                            <option value="Bienes de Capital">Bienes de Capital</option>
                            <option value="Logística">Logística</option>
                            <option value="Tecnología">Tecnología</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Categoría</label>
                        <select name="categoria" id="categoria">
                            <option value="">Seleccionar...</option>
                            <option value="A - Estratégico">A - Estratégico</option>
                            <option value="B - Regular">B - Regular</option>
                            <option value="C - Ocasional">C - Ocasional</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>País</label>
                        <select name="pais" id="pais">
                            <option value="">Seleccionar...</option>
                            <option value="México">México</option>
                            <option value="Estados Unidos">Estados Unidos</option>
                            <option value="China">China</option>
                            <option value="Canadá">Canadá</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Condición de Pago</label>
                        <select name="condicion_pago" id="condicion_pago">
                            <option value="">Seleccionar...</option>
                            <option value="Contado">Contado</option>
                            <option value="30 días">30 días</option>
                            <option value="60 días">60 días</option>
                            <option value="90 días">90 días</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Moneda</label>
                        <select name="moneda" id="moneda">
                            <option value="MXN">MXN - Peso Mexicano</option>
                            <option value="USD">USD - Dólar</option>
                            <option value="EUR">EUR - Euro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Plazo de Entrega</label>
                        <input type="text" name="plazo_entrega" id="plazo_entrega" placeholder="ej: 15 días">
                    </div>
                    <div class="form-group">
                        <label>Calificación (0-10)</label>
                        <input type="number" step="0.1" min="0" max="10" name="calificacion" id="calificacion" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" id="observaciones"></textarea>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="activo" id="activo" checked>
                    <label for="activo">Proveedor Activo</label>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
                    <button type="button" class="btn btn-secondary" onclick="clearFormProveedor()">Limpiar</button>
                </div>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>RFC</th>
                            <th>Tipo</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($proveedor['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['rfc'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['tipo_proveedor'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['telefono'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['email'] ?? '-'); ?></td>
                            <td>
                                <?php if ($proveedor['activo']): ?>
                                <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-warning" onclick='editProveedor(<?php echo json_encode($proveedor); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este proveedor?');">
                                        <input type="hidden" name="action" value="delete_proveedor">
                                        <input type="hidden" name="id" value="<?php echo $proveedor['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <h3>No hay proveedores registrados</h3>
                                <p>Agregue un nuevo proveedor para comenzar</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================================================ -->
        <!-- TAB: CONTACTOS -->
        <!-- ============================================================================ -->
        <div id="contactos" class="tab-content">
            <div class="section-header">
                <h2>Contactos de Proveedores</h2>
            </div>

            <div class="proveedor-selector">
                <label><strong>Seleccione un Proveedor:</strong></label>
                <select onchange="window.location.href='?proveedor_id=' + this.value + '#contactos'">
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?php echo $proveedor['id']; ?>" <?php echo $selected_proveedor_id == $proveedor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($proveedor['codigo'] . ' - ' . $proveedor['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_proveedor_id): ?>
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="openModalContacto()">+ Nuevo Contacto</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Cargo</th>
                            <th>Departamento</th>
                            <th>Teléfono</th>
                            <th>Celular</th>
                            <th>Email</th>
                            <th>Principal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contactos as $cont): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cont['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cont['cargo']); ?></td>
                            <td><?php echo htmlspecialchars($cont['departamento'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($cont['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($cont['celular'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($cont['email']); ?></td>
                            <td>
                                <?php if ($cont['principal']): ?>
                                <span class="badge badge-success">Sí</span>
                                <?php else: ?>
                                <span class="badge badge-danger">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-warning" onclick='editContacto(<?php echo json_encode($cont); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este contacto?');">
                                        <input type="hidden" name="action" value="delete_contacto">
                                        <input type="hidden" name="id" value="<?php echo $cont['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($contactos)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <h3>No hay contactos registrados</h3>
                                <p>Agregue un nuevo contacto para este proveedor</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>Seleccione un proveedor para ver sus contactos</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================================ -->
        <!-- TAB: CONTRATOS -->
        <!-- ============================================================================ -->
        <div id="contratos" class="tab-content">
            <div class="section-header">
                <h2>Contratos de Proveedores</h2>
            </div>

            <div class="proveedor-selector">
                <label><strong>Seleccione un Proveedor:</strong></label>
                <select onchange="window.location.href='?proveedor_id=' + this.value + '#contratos'">
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?php echo $proveedor['id']; ?>" <?php echo $selected_proveedor_id == $proveedor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($proveedor['codigo'] . ' - ' . $proveedor['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_proveedor_id): ?>
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="openModalContrato()">+ Nuevo Contrato</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. Contrato</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contratos as $contrato): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($contrato['numero_contrato']); ?></strong></td>
                            <td><?php echo htmlspecialchars($contrato['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($contrato['tipo_contrato'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($contrato['fecha_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($contrato['fecha_fin'])); ?></td>
                            <td><?php echo '$' . number_format($contrato['monto'], 2) . ' ' . $contrato['moneda']; ?></td>
                            <td>
                                <?php
                                $estado_class = match($contrato['estado']) {
                                    'Activo' => 'badge-success',
                                    'Finalizado' => 'badge-danger',
                                    'Suspendido' => 'badge-warning',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?php echo $estado_class; ?>"><?php echo $contrato['estado']; ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-warning" onclick='editContrato(<?php echo json_encode($contrato); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este contrato?');">
                                        <input type="hidden" name="action" value="delete_contrato">
                                        <input type="hidden" name="id" value="<?php echo $contrato['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($contratos)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <h3>No hay contratos registrados</h3>
                                <p>Agregue un nuevo contrato para este proveedor</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>Seleccione un proveedor para ver sus contratos</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================================ -->
        <!-- TAB: EVALUACIONES -->
        <!-- ============================================================================ -->
        <div id="evaluaciones" class="tab-content">
            <div class="section-header">
                <h2>Evaluaciones de Desempeño</h2>
            </div>

            <div class="proveedor-selector">
                <label><strong>Seleccione un Proveedor:</strong></label>
                <select onchange="window.location.href='?proveedor_id=' + this.value + '#evaluaciones'">
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?php echo $proveedor['id']; ?>" <?php echo $selected_proveedor_id == $proveedor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($proveedor['codigo'] . ' - ' . $proveedor['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_proveedor_id): ?>
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="openModalEvaluacion()">+ Nueva Evaluación</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Periodo</th>
                            <th>Calidad</th>
                            <th>Entrega</th>
                            <th>Precio</th>
                            <th>Servicio</th>
                            <th>Calificación Total</th>
                            <th>Nivel Desempeño</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluaciones as $eval): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($eval['fecha_evaluacion'])); ?></td>
                            <td><?php echo htmlspecialchars($eval['periodo']); ?></td>
                            <td><?php echo number_format($eval['calidad'], 1); ?></td>
                            <td><?php echo number_format($eval['entrega'], 1); ?></td>
                            <td><?php echo number_format($eval['precio'], 1); ?></td>
                            <td><?php echo number_format($eval['servicio'], 1); ?></td>
                            <td><strong><?php echo number_format($eval['calificacion_total'], 2); ?></strong></td>
                            <td>
                                <?php
                                $nivel_class = match($eval['nivel_desempeno']) {
                                    'EXCELENTE' => 'badge-success',
                                    'MUY BUENO' => 'badge-info',
                                    'BUENO' => 'badge-primary',
                                    'ACEPTABLE' => 'badge-warning',
                                    'DEFICIENTE' => 'badge-danger',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?php echo $nivel_class; ?>"><?php echo $eval['nivel_desempeno']; ?></span>
                            </td>
                            <td>
                                <?php
                                $estado_class = match($eval['estado']) {
                                    'APROBADA' => 'badge-success',
                                    'RECHAZADA' => 'badge-danger',
                                    'BORRADOR' => 'badge-warning',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?php echo $estado_class; ?>"><?php echo $eval['estado']; ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-warning" onclick='editEvaluacion(<?php echo json_encode($eval); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta evaluación?');">
                                        <input type="hidden" name="action" value="delete_evaluacion">
                                        <input type="hidden" name="id" value="<?php echo $eval['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($evaluaciones)): ?>
                        <tr>
                            <td colspan="10" class="empty-state">
                                <h3>No hay evaluaciones registradas</h3>
                                <p>Agregue una nueva evaluación para este proveedor</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>Seleccione un proveedor para ver sus evaluaciones</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================================ -->
        <!-- TAB: CONDICIONES COMERCIALES -->
        <!-- ============================================================================ -->
        <div id="condiciones" class="tab-content">
            <div class="section-header">
                <h2>Condiciones Comerciales</h2>
            </div>

            <div class="proveedor-selector">
                <label><strong>Seleccione un Proveedor:</strong></label>
                <select onchange="window.location.href='?proveedor_id=' + this.value + '#condiciones'">
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?php echo $proveedor['id']; ?>" <?php echo $selected_proveedor_id == $proveedor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($proveedor['codigo'] . ' - ' . $proveedor['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_proveedor_id): ?>
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="openModalCondicion()">+ Nueva Condición</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Descuento</th>
                            <th>Vol. Mínimo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($condiciones as $cond): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cond['codigo_condicion']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cond['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($cond['tipo_condicion']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cond['fecha_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cond['fecha_fin'])); ?></td>
                            <td><?php echo $cond['descuento'] . '%'; ?></td>
                            <td><?php echo number_format($cond['volumen_minimo'], 0); ?></td>
                            <td>
                                <?php
                                $estado_class = match($cond['estado']) {
                                    'Activo' => 'badge-success',
                                    'Vencido' => 'badge-danger',
                                    'Pendiente' => 'badge-warning',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?php echo $estado_class; ?>"><?php echo $cond['estado']; ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-warning" onclick='editCondicion(<?php echo json_encode($cond); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta condición?');">
                                        <input type="hidden" name="action" value="delete_condicion">
                                        <input type="hidden" name="id" value="<?php echo $cond['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($condiciones)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <h3>No hay condiciones comerciales registradas</h3>
                                <p>Agregue una nueva condición para este proveedor</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>Seleccione un proveedor para ver sus condiciones comerciales</h3>
            </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================================ -->
        <!-- TAB: DIRECCIONES -->
        <!-- ============================================================================ -->
        <div id="direcciones" class="tab-content">
            <div class="section-header">
                <h2>Direcciones de Proveedores</h2>
            </div>

            <div class="proveedor-selector">
                <label><strong>Seleccione un Proveedor:</strong></label>
                <select onchange="window.location.href='?proveedor_id=' + this.value + '#direcciones'">
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?php echo $proveedor['id']; ?>" <?php echo $selected_proveedor_id == $proveedor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($proveedor['codigo'] . ' - ' . $proveedor['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_proveedor_id): ?>
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="openModalDireccion()">+ Nueva Dirección</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Dirección</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                            <th>CP</th>
                            <th>País</th>
                            <th>Predeterminada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($direcciones as $dir): ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($dir['tipo']); ?></span></td>
                            <td><?php echo htmlspecialchars($dir['calle'] . ' ' . $dir['numero_ext']); ?></td>
                            <td><?php echo htmlspecialchars($dir['ciudad']); ?></td>
                            <td><?php echo htmlspecialchars($dir['estado']); ?></td>
                            <td><?php echo htmlspecialchars($dir['codigo_postal']); ?></td>
                            <td><?php echo htmlspecialchars($dir['pais']); ?></td>
                            <td>
                                <?php if ($dir['predeterminada']): ?>
                                <span class="badge badge-success">Sí</span>
                                <?php else: ?>
                                <span class="badge badge-danger">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-warning" onclick='editDireccion(<?php echo json_encode($dir); ?>)'>Editar</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta dirección?');">
                                        <input type="hidden" name="action" value="delete_direccion">
                                        <input type="hidden" name="id" value="<?php echo $dir['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($direcciones)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <h3>No hay direcciones registradas</h3>
                                <p>Agregue una nueva dirección para este proveedor</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>Seleccione un proveedor para ver sus direcciones</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================================ -->
    <!-- MODAL: CONTACTOS -->
    <!-- ============================================================================ -->
    <div id="modalContactos" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalContactoTitle">Agregar Contacto</h3>
                <span class="close" onclick="closeModalContacto()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="formContacto">
                    <input type="hidden" name="action" value="save_contacto">
                    <input type="hidden" name="proveedor_id" value="<?php echo $selected_proveedor_id; ?>">
                    <input type="hidden" name="contacto_id" id="contacto_id">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre Completo *</label>
                            <input type="text" name="nombre" id="cont_nombre" required>
                        </div>
                        <div class="form-group">
                            <label>Cargo *</label>
                            <input type="text" name="cargo" id="cont_cargo" required>
                        </div>
                        <div class="form-group">
                            <label>Departamento</label>
                            <input type="text" name="departamento" id="cont_departamento">
                        </div>
                        <div class="form-group">
                            <label>Teléfono *</label>
                            <input type="text" name="telefono" id="cont_telefono" required>
                        </div>
                        <div class="form-group">
                            <label>Extensión</label>
                            <input type="text" name="extension" id="cont_extension">
                        </div>
                        <div class="form-group">
                            <label>Celular</label>
                            <input type="text" name="celular" id="cont_celular">
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" id="cont_email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notas</label>
                        <textarea name="notas" id="cont_notas" rows="3"></textarea>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="principal" id="cont_principal">
                        <label for="cont_principal">Contacto Principal</label>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModalContacto()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================================ -->
    <!-- MODAL: CONTRATOS -->
    <!-- ============================================================================ -->
    <div id="modalContratos" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalContratoTitle">Agregar Contrato</h3>
                <span class="close" onclick="closeModalContrato()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="formContrato">
                    <input type="hidden" name="action" value="save_contrato">
                    <input type="hidden" name="proveedor_id" value="<?php echo $selected_proveedor_id; ?>">
                    <input type="hidden" name="contrato_id" id="contrato_id">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Número de Contrato *</label>
                            <input type="text" name="numero_contrato" id="contr_numero" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Contrato</label>
                            <select name="tipo_contrato" id="contr_tipo">
                                <option value="Suministro">Suministro</option>
                                <option value="Servicio">Servicio</option>
                                <option value="Mantenimiento">Mantenimiento</option>
                                <option value="Licencia">Licencia</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha Inicio *</label>
                            <input type="date" name="fecha_inicio" id="contr_fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Fin *</label>
                            <input type="date" name="fecha_fin" id="contr_fecha_fin" required>
                        </div>
                        <div class="form-group">
                            <label>Monto *</label>
                            <input type="number" step="0.01" name="monto" id="contr_monto" required>
                        </div>
                        <div class="form-group">
                            <label>Moneda *</label>
                            <select name="moneda" id="contr_moneda" required>
                                <option value="MXN">MXN - Peso Mexicano</option>
                                <option value="USD">USD - Dólar</option>
                                <option value="EUR">EUR - Euro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado *</label>
                            <select name="estado" id="contr_estado" required>
                                <option value="Activo">Activo</option>
                                <option value="En Negociación">En Negociación</option>
                                <option value="Suspendido">Suspendido</option>
                                <option value="Finalizado">Finalizado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción *</label>
                        <textarea name="descripcion" id="contr_descripcion" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" id="contr_observaciones" rows="3"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModalContrato()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================================ -->
    <!-- MODAL: EVALUACIONES -->
    <!-- ============================================================================ -->
    <div id="modalEvaluaciones" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalEvaluacionTitle">Agregar Evaluación de Desempeño</h3>
                <span class="close" onclick="closeModalEvaluacion()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEvaluacion">
                    <input type="hidden" name="action" value="save_evaluacion">
                    <input type="hidden" name="proveedor_id" value="<?php echo $selected_proveedor_id; ?>">
                    <input type="hidden" name="evaluacion_id" id="evaluacion_id">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fecha de Evaluación *</label>
                            <input type="date" name="fecha_evaluacion" id="eval_fecha" required>
                        </div>
                        <div class="form-group">
                            <label>Periodo *</label>
                            <input type="text" name="periodo" id="eval_periodo" placeholder="ej: Q1 2024" required>
                        </div>
                        <div class="form-group">
                            <label>Evaluador</label>
                            <input type="text" name="evaluador" id="eval_evaluador">
                        </div>
                        <div class="form-group">
                            <label>Estado *</label>
                            <select name="estado" id="eval_estado" required>
                                <option value="BORRADOR">BORRADOR</option>
                                <option value="APROBADA">APROBADA</option>
                                <option value="RECHAZADA">RECHAZADA</option>
                            </select>
                        </div>
                    </div>

                    <h4 style="margin: 20px 0 15px; color: #1e3c72;">Criterios de Evaluación (0-10)</h4>
                    <div class="criteria-grid">
                        <div class="form-group">
                            <label>Calidad *</label>
                            <input type="number" step="0.1" min="0" max="10" name="calidad" id="eval_calidad" required value="0">
                        </div>
                        <div class="form-group">
                            <label>Entrega *</label>
                            <input type="number" step="0.1" min="0" max="10" name="entrega" id="eval_entrega" required value="0">
                        </div>
                        <div class="form-group">
                            <label>Precio *</label>
                            <input type="number" step="0.1" min="0" max="10" name="precio" id="eval_precio" required value="0">
                        </div>
                        <div class="form-group">
                            <label>Servicio *</label>
                            <input type="number" step="0.1" min="0" max="10" name="servicio" id="eval_servicio" required value="0">
                        </div>
                        <div class="form-group">
                            <label>Documentación *</label>
                            <input type="number" step="0.1" min="0" max="10" name="documentacion" id="eval_documentacion" required value="0">
                        </div>
                        <div class="form-group">
                            <label>Innovación *</label>
                            <input type="number" step="0.1" min="0" max="10" name="innovacion" id="eval_innovacion" required value="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" id="eval_observaciones" rows="4"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModalEvaluacion()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================================ -->
    <!-- MODAL: CONDICIONES COMERCIALES -->
    <!-- ============================================================================ -->
    <div id="modalCondiciones" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalCondicionTitle">Agregar Condición Comercial</h3>
                <span class="close" onclick="closeModalCondicion()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="formCondicion">
                    <input type="hidden" name="action" value="save_condicion">
                    <input type="hidden" name="proveedor_id" value="<?php echo $selected_proveedor_id; ?>">
                    <input type="hidden" name="condicion_id" id="condicion_id">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Código de Condición *</label>
                            <input type="text" name="codigo_condicion" id="cond_codigo" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Condición *</label>
                            <select name="tipo_condicion" id="cond_tipo" required>
                                <option value="Descuento por Volumen">Descuento por Volumen</option>
                                <option value="Precio Especial">Precio Especial</option>
                                <option value="Bonificación">Bonificación</option>
                                <option value="Pago Anticipado">Pago Anticipado</option>
                                <option value="Exclusividad">Exclusividad</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha Inicio *</label>
                            <input type="date" name="fecha_inicio" id="cond_fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Fin *</label>
                            <input type="date" name="fecha_fin" id="cond_fecha_fin" required>
                        </div>
                        <div class="form-group">
                            <label>Descuento (%)</label>
                            <input type="number" step="0.01" name="descuento" id="cond_descuento" value="0">
                        </div>
                        <div class="form-group">
                            <label>Volumen Mínimo</label>
                            <input type="number" step="0.01" name="volumen_minimo" id="cond_volumen" value="0">
                        </div>
                        <div class="form-group">
                            <label>Estado *</label>
                            <select name="estado" id="cond_estado" required>
                                <option value="Activo">Activo</option>
                                <option value="Pendiente">Pendiente</option>
                                <option value="Vencido">Vencido</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción *</label>
                        <textarea name="descripcion" id="cond_descripcion" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Condiciones y Términos</label>
                        <textarea name="condiciones" id="cond_condiciones" rows="4"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModalCondicion()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================================ -->
    <!-- MODAL: DIRECCIONES -->
    <!-- ============================================================================ -->
    <div id="modalDirecciones" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalDireccionTitle">Agregar Dirección</h3>
                <span class="close" onclick="closeModalDireccion()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="formDireccion">
                    <input type="hidden" name="action" value="save_direccion">
                    <input type="hidden" name="proveedor_id" value="<?php echo $selected_proveedor_id; ?>">
                    <input type="hidden" name="direccion_id" id="direccion_id">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo de Dirección *</label>
                            <select name="tipo" id="dir_tipo" required>
                                <option value="Fiscal">Fiscal</option>
                                <option value="Entrega">Entrega</option>
                                <option value="Facturación">Facturación</option>
                                <option value="Planta">Planta</option>
                                <option value="Almacén">Almacén</option>
                                <option value="Otra">Otra</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>País *</label>
                            <select name="pais" id="dir_pais" required>
                                <option value="México">México</option>
                                <option value="Estados Unidos">Estados Unidos</option>
                                <option value="Canadá">Canadá</option>
                                <option value="China">China</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Calle *</label>
                            <input type="text" name="calle" id="dir_calle" required>
                        </div>
                        <div class="form-group">
                            <label>Número Exterior *</label>
                            <input type="text" name="numero_ext" id="dir_numero_ext" required>
                        </div>
                        <div class="form-group">
                            <label>Número Interior</label>
                            <input type="text" name="numero_int" id="dir_numero_int">
                        </div>
                        <div class="form-group">
                            <label>Colonia *</label>
                            <input type="text" name="colonia" id="dir_colonia" required>
                        </div>
                        <div class="form-group">
                            <label>Ciudad *</label>
                            <input type="text" name="ciudad" id="dir_ciudad" required>
                        </div>
                        <div class="form-group">
                            <label>Estado *</label>
                            <input type="text" name="estado" id="dir_estado" required>
                        </div>
                        <div class="form-group">
                            <label>Código Postal *</label>
                            <input type="text" name="codigo_postal" id="dir_codigo_postal" required>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="predeterminada" id="dir_predeterminada">
                        <label for="dir_predeterminada">Dirección Predeterminada</label>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModalDireccion()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ============================================================================
        // TAB NAVIGATION
        // ============================================================================
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Handle hash navigation
        window.addEventListener('load', function() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                const tab = document.querySelector(`.tab[onclick*="${hash}"]`);
                if (tab) {
                    tab.click();
                }
            }
        });

        // ============================================================================
        // MAESTRO PROVEEDORES FUNCTIONS
        // ============================================================================
        function clearFormProveedor() {
            document.getElementById('formProveedor').reset();
            document.getElementById('proveedor_id').value = '';
        }

        function editProveedor(proveedor) {
            document.getElementById('proveedor_id').value = proveedor.id;
            document.getElementById('codigo').value = proveedor.codigo;
            document.getElementById('nombre').value = proveedor.nombre;
            document.getElementById('razon_social').value = proveedor.razon_social;
            document.getElementById('rfc').value = proveedor.rfc || '';
            document.getElementById('giro').value = proveedor.giro || '';
            document.getElementById('telefono').value = proveedor.telefono || '';
            document.getElementById('email').value = proveedor.email || '';
            document.getElementById('sitio_web').value = proveedor.sitio_web || '';
            document.getElementById('tipo_proveedor').value = proveedor.tipo_proveedor || '';
            document.getElementById('categoria').value = proveedor.categoria || '';
            document.getElementById('pais').value = proveedor.pais || '';
            document.getElementById('condicion_pago').value = proveedor.condicion_pago || '';
            document.getElementById('moneda').value = proveedor.moneda || 'MXN';
            document.getElementById('plazo_entrega').value = proveedor.plazo_entrega || '';
            document.getElementById('calificacion').value = proveedor.calificacion || 0;
            document.getElementById('observaciones').value = proveedor.observaciones || '';
            document.getElementById('activo').checked = proveedor.activo == 1;

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // ============================================================================
        // MODAL CONTACTOS FUNCTIONS
        // ============================================================================
        function openModalContacto() {
            document.getElementById('formContacto').reset();
            document.getElementById('contacto_id').value = '';
            document.getElementById('modalContactoTitle').textContent = 'Agregar Contacto';
            document.getElementById('modalContactos').style.display = 'block';
        }

        function closeModalContacto() {
            document.getElementById('modalContactos').style.display = 'none';
        }

        function editContacto(contacto) {
            document.getElementById('contacto_id').value = contacto.id;
            document.getElementById('cont_nombre').value = contacto.nombre;
            document.getElementById('cont_cargo').value = contacto.cargo;
            document.getElementById('cont_departamento').value = contacto.departamento || '';
            document.getElementById('cont_telefono').value = contacto.telefono;
            document.getElementById('cont_extension').value = contacto.extension || '';
            document.getElementById('cont_celular').value = contacto.celular || '';
            document.getElementById('cont_email').value = contacto.email;
            document.getElementById('cont_notas').value = contacto.notas || '';
            document.getElementById('cont_principal').checked = contacto.principal == 1;
            document.getElementById('modalContactoTitle').textContent = 'Editar Contacto';
            document.getElementById('modalContactos').style.display = 'block';
        }

        // ============================================================================
        // MODAL CONTRATOS FUNCTIONS
        // ============================================================================
        function openModalContrato() {
            document.getElementById('formContrato').reset();
            document.getElementById('contrato_id').value = '';
            document.getElementById('modalContratoTitle').textContent = 'Agregar Contrato';
            document.getElementById('modalContratos').style.display = 'block';
        }

        function closeModalContrato() {
            document.getElementById('modalContratos').style.display = 'none';
        }

        function editContrato(contrato) {
            document.getElementById('contrato_id').value = contrato.id;
            document.getElementById('contr_numero').value = contrato.numero_contrato;
            document.getElementById('contr_tipo').value = contrato.tipo_contrato || '';
            document.getElementById('contr_descripcion').value = contrato.descripcion;
            document.getElementById('contr_fecha_inicio').value = contrato.fecha_inicio;
            document.getElementById('contr_fecha_fin').value = contrato.fecha_fin;
            document.getElementById('contr_monto').value = contrato.monto;
            document.getElementById('contr_moneda').value = contrato.moneda;
            document.getElementById('contr_estado').value = contrato.estado;
            document.getElementById('contr_observaciones').value = contrato.observaciones || '';
            document.getElementById('modalContratoTitle').textContent = 'Editar Contrato';
            document.getElementById('modalContratos').style.display = 'block';
        }

        // ============================================================================
        // MODAL EVALUACIONES FUNCTIONS
        // ============================================================================
        function openModalEvaluacion() {
            document.getElementById('formEvaluacion').reset();
            document.getElementById('evaluacion_id').value = '';
            document.getElementById('modalEvaluacionTitle').textContent = 'Agregar Evaluación de Desempeño';
            document.getElementById('modalEvaluaciones').style.display = 'block';
        }

        function closeModalEvaluacion() {
            document.getElementById('modalEvaluaciones').style.display = 'none';
        }

        function editEvaluacion(evaluacion) {
            document.getElementById('evaluacion_id').value = evaluacion.id;
            document.getElementById('eval_fecha').value = evaluacion.fecha_evaluacion;
            document.getElementById('eval_periodo').value = evaluacion.periodo;
            document.getElementById('eval_calidad').value = evaluacion.calidad;
            document.getElementById('eval_entrega').value = evaluacion.entrega;
            document.getElementById('eval_precio').value = evaluacion.precio;
            document.getElementById('eval_servicio').value = evaluacion.servicio;
            document.getElementById('eval_documentacion').value = evaluacion.documentacion;
            document.getElementById('eval_innovacion').value = evaluacion.innovacion;
            document.getElementById('eval_estado').value = evaluacion.estado;
            document.getElementById('eval_observaciones').value = evaluacion.observaciones || '';
            document.getElementById('eval_evaluador').value = evaluacion.evaluador || '';
            document.getElementById('modalEvaluacionTitle').textContent = 'Editar Evaluación';
            document.getElementById('modalEvaluaciones').style.display = 'block';
        }

        // ============================================================================
        // MODAL CONDICIONES FUNCTIONS
        // ============================================================================
        function openModalCondicion() {
            document.getElementById('formCondicion').reset();
            document.getElementById('condicion_id').value = '';
            document.getElementById('modalCondicionTitle').textContent = 'Agregar Condición Comercial';
            document.getElementById('modalCondiciones').style.display = 'block';
        }

        function closeModalCondicion() {
            document.getElementById('modalCondiciones').style.display = 'none';
        }

        function editCondicion(condicion) {
            document.getElementById('condicion_id').value = condicion.id;
            document.getElementById('cond_codigo').value = condicion.codigo_condicion;
            document.getElementById('cond_tipo').value = condicion.tipo_condicion;
            document.getElementById('cond_descripcion').value = condicion.descripcion;
            document.getElementById('cond_fecha_inicio').value = condicion.fecha_inicio;
            document.getElementById('cond_fecha_fin').value = condicion.fecha_fin;
            document.getElementById('cond_descuento').value = condicion.descuento || 0;
            document.getElementById('cond_volumen').value = condicion.volumen_minimo || 0;
            document.getElementById('cond_condiciones').value = condicion.condiciones || '';
            document.getElementById('cond_estado').value = condicion.estado;
            document.getElementById('modalCondicionTitle').textContent = 'Editar Condición Comercial';
            document.getElementById('modalCondiciones').style.display = 'block';
        }

        // ============================================================================
        // MODAL DIRECCIONES FUNCTIONS
        // ============================================================================
        function openModalDireccion() {
            document.getElementById('formDireccion').reset();
            document.getElementById('direccion_id').value = '';
            document.getElementById('modalDireccionTitle').textContent = 'Agregar Dirección';
            document.getElementById('modalDirecciones').style.display = 'block';
        }

        function closeModalDireccion() {
            document.getElementById('modalDirecciones').style.display = 'none';
        }

        function editDireccion(direccion) {
            document.getElementById('direccion_id').value = direccion.id;
            document.getElementById('dir_tipo').value = direccion.tipo;
            document.getElementById('dir_calle').value = direccion.calle;
            document.getElementById('dir_numero_ext').value = direccion.numero_ext;
            document.getElementById('dir_numero_int').value = direccion.numero_int || '';
            document.getElementById('dir_colonia').value = direccion.colonia;
            document.getElementById('dir_ciudad').value = direccion.ciudad;
            document.getElementById('dir_estado').value = direccion.estado;
            document.getElementById('dir_codigo_postal').value = direccion.codigo_postal;
            document.getElementById('dir_pais').value = direccion.pais;
            document.getElementById('dir_predeterminada').checked = direccion.predeterminada == 1;
            document.getElementById('modalDireccionTitle').textContent = 'Editar Dirección';
            document.getElementById('modalDirecciones').style.display = 'block';
        }

        // ============================================================================
        // MODAL WINDOW CLICK HANDLERS
        // ============================================================================
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
