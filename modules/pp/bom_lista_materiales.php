<?php
/**
 * BOM - LISTA DE MATERIALES (BILL OF MATERIALS)
 * Modulo completo de gestion de listas de materiales
 * Sin dependencias externas - Todo en un solo archivo
 */

// Iniciar sesion
session_start();

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Conexion a base de datos
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Error de conexion: " . $e->getMessage());
}

// Variables de sesion
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Variables de estado
$mensaje = '';
$tipo_mensaje = '';
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'listar';
$bom_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Funciones auxiliares
function ejecutarConsulta($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error SQL: " . $e->getMessage());
        return null;
    }
}

function ejecutarConsultaMultiple($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error SQL: " . $e->getMessage());
        return [];
    }
}

function insertarRegistro($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error INSERT: " . $e->getMessage());
        return false;
    }
}

function actualizarRegistro($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error UPDATE: " . $e->getMessage());
        return false;
    }
}

function eliminarRegistro($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error DELETE: " . $e->getMessage());
        return false;
    }
}

function registrarAuditoria($pdo, $user_id, $accion, $tabla, $registro_id, $detalles) {
    $sql = "INSERT INTO activity_logs (user_id, action, description, module, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $accion,
            "Tabla: $tabla, ID: $registro_id - $detalles",
            'PP-BOM',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Error auditoria: " . $e->getMessage());
    }
}

function calcularCostoBOM($pdo, $bom_id) {
    // Calcular costo total del BOM sumando componentes
    $sql = "SELECT SUM(bc.quantity_required * COALESCE(p.standard_cost, 0)) as costo_total
            FROM pp_bom_components bc
            LEFT JOIN mm_products p ON bc.component_product_id = p.id
            WHERE bc.bom_id = ?";

    $resultado = ejecutarConsulta($pdo, $sql, [$bom_id]);
    return $resultado['costo_total'] ?? 0;
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    switch($accion) {
        case 'crear_bom':
            // Crear nuevo BOM
            $sql = "INSERT INTO pp_bom (
                company_id, bom_number, product_id, bom_version, bom_name,
                description, bom_type, base_quantity, unit_of_measure,
                valid_from, is_active, is_default, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())";

            $bom_number = 'BOM-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $id = insertarRegistro($pdo, $sql, [
                $company_id,
                $bom_number,
                $_POST['product_id'] ?? null,
                $_POST['bom_version'] ?? 1,
                $_POST['bom_name'] ?? '',
                $_POST['description'] ?? '',
                $_POST['tipo_bom'] ?? 'manufacturing',
                $_POST['base_quantity'] ?? 1,
                $_POST['unit_of_measure'] ?? 'UN',
                $_POST['valid_from'] ?? date('Y-m-d'),
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['is_default']) ? 1 : 0
            ]);

            if ($id) {
                registrarAuditoria($pdo, $user_id, 'CREATE', 'pp_bom', $id, 'BOM creado: ' . $bom_number);
                $mensaje = "BOM creado exitosamente: $bom_number";
                $tipo_mensaje = 'success';
                $bom_id = $id;
                $modo = 'editar';
            } else {
                $mensaje = "Error al crear el BOM";
                $tipo_mensaje = 'error';
            }
            break;

        case 'actualizar_bom':
            // Actualizar BOM existente
            $id = $_POST['bom_id'] ?? 0;

            $sql = "UPDATE pp_bom SET
                product_id = ?, bom_version = ?, bom_name = ?, description = ?,
                bom_type = ?, base_quantity = ?, unit_of_measure = ?,
                valid_from = ?, valid_to = ?, is_active = ?, is_default = ?,
                status = ?, notes = ?
                WHERE id = ? AND company_id = ?";

            $resultado = actualizarRegistro($pdo, $sql, [
                $_POST['product_id'] ?? null,
                $_POST['bom_version'] ?? 1,
                $_POST['bom_name'] ?? '',
                $_POST['description'] ?? '',
                $_POST['tipo_bom'] ?? 'manufacturing',
                $_POST['base_quantity'] ?? 1,
                $_POST['unit_of_measure'] ?? 'UN',
                $_POST['valid_from'] ?? date('Y-m-d'),
                $_POST['valid_to'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['is_default']) ? 1 : 0,
                $_POST['status'] ?? 'draft',
                $_POST['notes'] ?? '',
                $id,
                $company_id
            ]);

            if ($resultado !== false) {
                registrarAuditoria($pdo, $user_id, 'UPDATE', 'pp_bom', $id, 'BOM actualizado');
                $mensaje = "BOM actualizado exitosamente";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error al actualizar el BOM";
                $tipo_mensaje = 'error';
            }
            break;

        case 'agregar_componente':
            // Agregar componente al BOM
            $bom_id = $_POST['bom_id'] ?? 0;

            $sql = "INSERT INTO pp_bom_components (
                bom_id, line_number, component_product_id, component_type,
                quantity_required, unit_of_measure, scrap_percentage,
                is_critical, substitute_product_id, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $id = insertarRegistro($pdo, $sql, [
                $bom_id,
                $_POST['line_number'] ?? 1,
                $_POST['component_product_id'] ?? null,
                $_POST['component_type'] ?? 'raw_material',
                $_POST['quantity_required'] ?? 0,
                $_POST['unit_of_measure'] ?? 'UN',
                $_POST['scrap_percentage'] ?? 0,
                isset($_POST['is_critical']) ? 1 : 0,
                $_POST['substitute_product_id'] ?? null,
                $_POST['notes_componente'] ?? ''
            ]);

            if ($id) {
                // Actualizar costo total del BOM
                $costo_total = calcularCostoBOM($pdo, $bom_id);
                $sql_update = "UPDATE pp_bom SET notes = CONCAT(COALESCE(notes, ''), '\nCosto actualizado: $', ?) WHERE id = ?";
                actualizarRegistro($pdo, $sql_update, [$costo_total, $bom_id]);

                registrarAuditoria($pdo, $user_id, 'INSERT', 'pp_bom_components', $id, 'Componente agregado al BOM');
                $mensaje = "Componente agregado exitosamente";
                $tipo_mensaje = 'success';
            }
            break;

        case 'eliminar_componente':
            // Eliminar componente del BOM
            $componente_id = $_POST['componente_id'] ?? 0;
            $bom_id = $_POST['bom_id'] ?? 0;

            $sql = "DELETE FROM pp_bom_components WHERE id = ?";
            $resultado = eliminarRegistro($pdo, $sql, [$componente_id]);

            if ($resultado) {
                // Recalcular costo
                $costo_total = calcularCostoBOM($pdo, $bom_id);

                registrarAuditoria($pdo, $user_id, 'DELETE', 'pp_bom_components', $componente_id, 'Componente eliminado del BOM');
                $mensaje = "Componente eliminado exitosamente";
                $tipo_mensaje = 'success';
            }
            break;

        case 'crear_ruta':
            // Crear ruta asociada al BOM
            $sql = "INSERT INTO pp_routings (
                company_id, routing_number, routing_name, product_id,
                description, routing_version, valid_from, is_active,
                is_default, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())";

            $routing_number = 'RUTA-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $id = insertarRegistro($pdo, $sql, [
                $company_id,
                $routing_number,
                $_POST['routing_name'] ?? '',
                $_POST['product_id_ruta'] ?? null,
                $_POST['description_ruta'] ?? '',
                $_POST['routing_version'] ?? 1,
                $_POST['valid_from_ruta'] ?? date('Y-m-d'),
                1,
                1
            ]);

            if ($id) {
                registrarAuditoria($pdo, $user_id, 'CREATE', 'pp_routings', $id, 'Ruta creada');
                $mensaje = "Ruta creada exitosamente: $routing_number";
                $tipo_mensaje = 'success';
            }
            break;

        case 'agregar_operacion':
            // Agregar operacion a la ruta
            $routing_id = $_POST['routing_id'] ?? 0;

            $sql = "INSERT INTO pp_routing_operations (
                routing_id, operation_number, operation_name, description,
                work_center_id, operation_type, setup_time_minutes,
                run_time_per_unit_minutes, wait_time_minutes, move_time_minutes,
                is_critical_path, quality_inspection_required, instructions
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $id = insertarRegistro($pdo, $sql, [
                $routing_id,
                $_POST['operation_number'] ?? 1,
                $_POST['operation_name'] ?? '',
                $_POST['operation_description'] ?? '',
                $_POST['work_center_id'] ?? null,
                $_POST['operation_type'] ?? 'processing',
                $_POST['setup_time_minutes'] ?? 0,
                $_POST['run_time_per_unit_minutes'] ?? 0,
                $_POST['wait_time_minutes'] ?? 0,
                $_POST['move_time_minutes'] ?? 0,
                isset($_POST['is_critical_path']) ? 1 : 0,
                isset($_POST['quality_inspection_required']) ? 1 : 0,
                $_POST['instructions'] ?? ''
            ]);

            if ($id) {
                registrarAuditoria($pdo, $user_id, 'INSERT', 'pp_routing_operations', $id, 'Operacion agregada a ruta');
                $mensaje = "Operacion agregada exitosamente";
                $tipo_mensaje = 'success';
            }
            break;

        case 'aprobar_bom':
            // Aprobar BOM
            $id = $_POST['bom_id'] ?? 0;

            $sql = "UPDATE pp_bom SET
                status = 'approved',
                approved_by = ?,
                approved_at = NOW()
                WHERE id = ? AND company_id = ?";

            actualizarRegistro($pdo, $sql, [$user_id, $id, $company_id]);

            registrarAuditoria($pdo, $user_id, 'APPROVE', 'pp_bom', $id, 'BOM aprobado');
            $mensaje = "BOM aprobado exitosamente";
            $tipo_mensaje = 'success';
            break;

        case 'activar_bom':
            // Activar BOM
            $id = $_POST['bom_id'] ?? 0;

            $sql = "UPDATE pp_bom SET status = 'active' WHERE id = ? AND company_id = ?";
            actualizarRegistro($pdo, $sql, [$id, $company_id]);

            registrarAuditoria($pdo, $user_id, 'ACTIVATE', 'pp_bom', $id, 'BOM activado');
            $mensaje = "BOM activado exitosamente";
            $tipo_mensaje = 'success';
            break;

        case 'crear_version':
            // Crear nueva version del BOM
            $bom_id_original = $_POST['bom_id'] ?? 0;

            // Obtener datos del BOM original
            $sql = "SELECT * FROM pp_bom WHERE id = ? AND company_id = ?";
            $bom_original = ejecutarConsulta($pdo, $sql, [$bom_id_original, $company_id]);

            if ($bom_original) {
                // Crear nueva version
                $nueva_version = $bom_original['bom_version'] + 1;
                $nuevo_numero = $bom_original['bom_number'] . '-V' . $nueva_version;

                $sql = "INSERT INTO pp_bom (
                    company_id, bom_number, product_id, bom_version, bom_name,
                    description, bom_type, base_quantity, unit_of_measure,
                    valid_from, is_active, is_default, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'draft', NOW())";

                $nuevo_id = insertarRegistro($pdo, $sql, [
                    $company_id,
                    $nuevo_numero,
                    $bom_original['product_id'],
                    $nueva_version,
                    $bom_original['bom_name'],
                    $bom_original['description'],
                    $bom_original['bom_type'],
                    $bom_original['base_quantity'],
                    $bom_original['unit_of_measure'],
                    date('Y-m-d')
                ]);

                if ($nuevo_id) {
                    // Copiar componentes
                    $sql = "INSERT INTO pp_bom_components (
                        bom_id, line_number, component_product_id, component_type,
                        quantity_required, unit_of_measure, scrap_percentage,
                        is_critical, substitute_product_id, notes
                    )
                    SELECT ?, line_number, component_product_id, component_type,
                        quantity_required, unit_of_measure, scrap_percentage,
                        is_critical, substitute_product_id, notes
                    FROM pp_bom_components WHERE bom_id = ?";

                    $pdo->prepare($sql)->execute([$nuevo_id, $bom_id_original]);

                    registrarAuditoria($pdo, $user_id, 'VERSION', 'pp_bom', $nuevo_id, "Nueva version creada desde BOM ID: $bom_id_original");
                    $mensaje = "Nueva version creada exitosamente: V$nueva_version";
                    $tipo_mensaje = 'success';
                    $bom_id = $nuevo_id;
                    $modo = 'editar';
                }
            }
            break;
    }
}

// Obtener datos segun modo
$bom_actual = null;
$componentes_bom = [];
$rutas_producto = [];
$operaciones_ruta = [];

if ($modo == 'editar' || $modo == 'ver') {
    // Obtener datos del BOM
    $sql = "SELECT b.*, p.product_name, p.product_code,
            u_aprobador.username as aprobador_nombre
            FROM pp_bom b
            LEFT JOIN mm_products p ON b.product_id = p.id
            LEFT JOIN users u_aprobador ON b.approved_by = u_aprobador.id
            WHERE b.id = ? AND b.company_id = ?";
    $bom_actual = ejecutarConsulta($pdo, $sql, [$bom_id, $company_id]);

    if ($bom_actual) {
        // Obtener componentes
        $sql = "SELECT bc.*, p.product_name, p.product_code, p.standard_cost,
                ps.product_name as sustituto_nombre, ps.product_code as sustituto_codigo
                FROM pp_bom_components bc
                LEFT JOIN mm_products p ON bc.component_product_id = p.id
                LEFT JOIN mm_products ps ON bc.substitute_product_id = ps.id
                WHERE bc.bom_id = ?
                ORDER BY bc.line_number";
        $componentes_bom = ejecutarConsultaMultiple($pdo, $sql, [$bom_id]);

        // Obtener rutas del producto
        if ($bom_actual['product_id']) {
            $sql = "SELECT * FROM pp_routings
                    WHERE product_id = ? AND company_id = ? AND is_active = 1
                    ORDER BY routing_version DESC";
            $rutas_producto = ejecutarConsultaMultiple($pdo, $sql, [$bom_actual['product_id'], $company_id]);

            // Si hay rutas, obtener operaciones de la primera
            if (!empty($rutas_producto)) {
                $sql = "SELECT ro.*, wc.work_center_name
                        FROM pp_routing_operations ro
                        LEFT JOIN pp_work_centers wc ON ro.work_center_id = wc.id
                        WHERE ro.routing_id = ?
                        ORDER BY ro.operation_number";
                $operaciones_ruta = ejecutarConsultaMultiple($pdo, $sql, [$rutas_producto[0]['id']]);
            }
        }
    }
}

// Obtener listado de BOMs
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';
$filtro_producto = isset($_GET['filtro_producto']) ? $_GET['filtro_producto'] : '';

$sql = "SELECT b.*, p.product_name, p.product_code
        FROM pp_bom b
        LEFT JOIN mm_products p ON b.product_id = p.id
        WHERE b.company_id = ?";
$params = [$company_id];

if ($filtro_estado) {
    $sql .= " AND b.status = ?";
    $params[] = $filtro_estado;
}
if ($filtro_tipo) {
    $sql .= " AND b.bom_type = ?";
    $params[] = $filtro_tipo;
}
if ($filtro_producto) {
    $sql .= " AND (p.product_code LIKE ? OR p.product_name LIKE ?)";
    $params[] = "%$filtro_producto%";
    $params[] = "%$filtro_producto%";
}

$sql .= " ORDER BY b.created_at DESC LIMIT 100";
$boms = ejecutarConsultaMultiple($pdo, $sql, $params);

// Obtener listas para selects
$productos = ejecutarConsultaMultiple($pdo, "SELECT id, product_code, product_name, standard_cost FROM mm_products WHERE company_id = ? AND is_active = 1 ORDER BY product_name LIMIT 500", [$company_id]);
$centros_trabajo = ejecutarConsultaMultiple($pdo, "SELECT id, work_center_code, work_center_name FROM pp_work_centers WHERE company_id = ? AND is_active = 1 ORDER BY work_center_name LIMIT 100", [$company_id]);

// Calcular costo total si hay BOM actual
$costo_total_bom = 0;
$costo_materiales = 0;
$cantidad_componentes = 0;

if ($bom_actual) {
    foreach ($componentes_bom as $comp) {
        $costo_comp = ($comp['quantity_required'] * ($comp['standard_cost'] ?? 0));
        $costo_materiales += $costo_comp;
        $cantidad_componentes++;
    }
    $costo_total_bom = $costo_materiales;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOM - Lista de Materiales - CONECTA ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
        }

        .btn-primary:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 2px solid rgba(34, 197, 94, 0.5);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid rgba(239, 68, 68, 0.5);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.2);
        }

        .form-group select option {
            background: #667eea;
            color: #fff;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(255, 255, 255, 0.15);
            padding: 12px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-draft { background: rgba(156, 163, 175, 0.3); border: 2px solid rgba(156, 163, 175, 0.5); }
        .badge-approved { background: rgba(59, 130, 246, 0.3); border: 2px solid rgba(59, 130, 246, 0.5); }
        .badge-active { background: rgba(34, 197, 94, 0.3); border: 2px solid rgba(34, 197, 94, 0.5); }
        .badge-obsolete { background: rgba(239, 68, 68, 0.3); border: 2px solid rgba(239, 68, 68, 0.5); }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab.active {
            color: #fff;
            border-bottom-color: #fff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-label {
            font-weight: 600;
            opacity: 0.8;
        }

        .info-value {
            font-weight: 700;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
        }

        .close-modal {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .badge-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            margin-right: 5px;
        }

        .tree-item {
            padding: 10px;
            margin-left: 20px;
            border-left: 2px solid rgba(255, 255, 255, 0.2);
        }

        .nivel-0 { margin-left: 0; font-weight: 700; }
        .nivel-1 { margin-left: 20px; }
        .nivel-2 { margin-left: 40px; opacity: 0.9; }
        .nivel-3 { margin-left: 60px; opacity: 0.8; }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
            }

            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span>📋</span>
                BOM - LISTA DE MATERIALES
            </h1>
            <div class="actions">
                <?php if ($modo != 'crear' && $modo != 'editar'): ?>
                <a href="?modo=crear" class="btn btn-primary">+ Nuevo BOM</a>
                <?php endif; ?>
                <?php if ($modo != 'listar'): ?>
                <a href="?modo=listar" class="btn btn-secondary">← Volver al Listado</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <span><?php echo $tipo_mensaje == 'success' ? '✓' : '✗'; ?></span>
            <span><?php echo htmlspecialchars($mensaje); ?></span>
        </div>
        <?php endif; ?>

        <!-- MODO: LISTAR -->
        <?php if ($modo == 'listar'): ?>
        <div class="section">
            <div class="section-title">Filtros de Busqueda</div>
            <form method="GET" action="">
                <input type="hidden" name="modo" value="listar">
                <div class="filters">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="filtro_estado">
                            <option value="">Todos</option>
                            <option value="draft" <?php echo $filtro_estado == 'draft' ? 'selected' : ''; ?>>Borrador</option>
                            <option value="approved" <?php echo $filtro_estado == 'approved' ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="active" <?php echo $filtro_estado == 'active' ? 'selected' : ''; ?>>Activo</option>
                            <option value="obsolete" <?php echo $filtro_estado == 'obsolete' ? 'selected' : ''; ?>>Obsoleto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="filtro_tipo">
                            <option value="">Todos</option>
                            <option value="manufacturing" <?php echo $filtro_tipo == 'manufacturing' ? 'selected' : ''; ?>>Fabricacion</option>
                            <option value="assembly" <?php echo $filtro_tipo == 'assembly' ? 'selected' : ''; ?>>Ensamblaje</option>
                            <option value="kit" <?php echo $filtro_tipo == 'kit' ? 'selected' : ''; ?>>Kit</option>
                            <option value="disassembly" <?php echo $filtro_tipo == 'disassembly' ? 'selected' : ''; ?>>Desensamblaje</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Producto</label>
                        <input type="text" name="filtro_producto" value="<?php echo htmlspecialchars($filtro_producto); ?>" placeholder="Buscar por codigo o nombre">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="section">
            <div class="section-title">Listado de BOMs (<?php echo count($boms); ?>)</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Codigo BOM</th>
                            <th>Version</th>
                            <th>Producto</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Cantidad Base</th>
                            <th>Estado</th>
                            <th>Valido Desde</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($boms)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <span style="font-size: 48px; display: block; margin-bottom: 10px;">📋</span>
                                No hay BOMs registrados
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($boms as $bom): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bom['bom_number']); ?></strong></td>
                                <td>
                                    <span style="background: rgba(255, 255, 255, 0.2); padding: 2px 8px; border-radius: 5px;">
                                        V<?php echo $bom['bom_version']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($bom['product_code']): ?>
                                        <div><?php echo htmlspecialchars($bom['product_code']); ?></div>
                                        <div style="font-size: 12px; opacity: 0.8;"><?php echo htmlspecialchars($bom['product_name']); ?></div>
                                    <?php else: ?>
                                        <span style="opacity: 0.5;">Sin producto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($bom['bom_name'] ?: 'Sin nombre'); ?></td>
                                <td><?php echo htmlspecialchars($bom['bom_type']); ?></td>
                                <td><?php echo number_format($bom['base_quantity'], 2); ?> <?php echo htmlspecialchars($bom['unit_of_measure']); ?></td>
                                <td>
                                    <span class="status-badge badge-<?php echo $bom['status']; ?>">
                                        <?php echo strtoupper($bom['status']); ?>
                                    </span>
                                    <?php if ($bom['is_default']): ?>
                                    <span class="status-badge" style="background: rgba(234, 179, 8, 0.3); border: 2px solid rgba(234, 179, 8, 0.5);">
                                        DEFAULT
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($bom['valid_from'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?modo=ver&id=<?php echo $bom['id']; ?>" class="btn btn-secondary btn-sm">Ver</a>
                                        <a href="?modo=editar&id=<?php echo $bom['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- MODO: CREAR -->
        <?php if ($modo == 'crear'): ?>
        <div class="section">
            <div class="section-title">Nuevo BOM (Bill of Materials)</div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="crear_bom">

                <div class="grid-3">
                    <div class="form-group">
                        <label>Producto Final *</label>
                        <select name="product_id" required>
                            <option value="">Seleccione producto</option>
                            <?php foreach ($productos as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>">
                                <?php echo htmlspecialchars($prod['product_code'] . ' - ' . $prod['product_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nombre del BOM</label>
                        <input type="text" name="bom_name" placeholder="Nombre descriptivo">
                    </div>

                    <div class="form-group">
                        <label>Version</label>
                        <input type="number" name="bom_version" value="1" min="1">
                    </div>

                    <div class="form-group">
                        <label>Tipo de BOM *</label>
                        <select name="tipo_bom" required>
                            <option value="manufacturing">Fabricacion</option>
                            <option value="assembly">Ensamblaje</option>
                            <option value="kit">Kit</option>
                            <option value="disassembly">Desensamblaje</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cantidad Base</label>
                        <input type="number" name="base_quantity" value="1" step="0.01" min="0.01">
                    </div>

                    <div class="form-group">
                        <label>Unidad de Medida</label>
                        <input type="text" name="unit_of_measure" value="UN">
                    </div>

                    <div class="form-group">
                        <label>Valido Desde</label>
                        <input type="date" name="valid_from" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" checked>
                            <span>Activo</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_default">
                            <span>BOM por Defecto</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripcion</label>
                    <textarea name="description" placeholder="Descripcion detallada del BOM"></textarea>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Crear BOM</button>
                    <a href="?modo=listar" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- MODO: EDITAR / VER -->
        <?php if (($modo == 'editar' || $modo == 'ver') && $bom_actual): ?>

        <!-- Informacion General y Acciones -->
        <div class="section">
            <div class="section-title">
                <div>
                    BOM: <?php echo htmlspecialchars($bom_actual['bom_number']); ?>
                    <span class="status-badge badge-<?php echo $bom_actual['status']; ?>">
                        <?php echo strtoupper($bom_actual['status']); ?>
                    </span>
                    <span style="background: rgba(255, 255, 255, 0.2); padding: 4px 12px; border-radius: 20px; font-size: 11px; margin-left: 10px;">
                        VERSION <?php echo $bom_actual['bom_version']; ?>
                    </span>
                </div>
                <div class="actions">
                    <?php if ($bom_actual['status'] == 'draft'): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Aprobar este BOM?');">
                        <input type="hidden" name="accion" value="aprobar_bom">
                        <input type="hidden" name="bom_id" value="<?php echo $bom_id; ?>">
                        <button type="submit" class="btn btn-success btn-sm">✓ Aprobar</button>
                    </form>
                    <?php endif; ?>

                    <?php if ($bom_actual['status'] == 'approved'): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Activar este BOM?');">
                        <input type="hidden" name="accion" value="activar_bom">
                        <input type="hidden" name="bom_id" value="<?php echo $bom_id; ?>">
                        <button type="submit" class="btn btn-warning btn-sm">⚡ Activar</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" style="display: inline;" onsubmit="return confirm('Crear nueva version de este BOM?');">
                        <input type="hidden" name="accion" value="crear_version">
                        <input type="hidden" name="bom_id" value="<?php echo $bom_id; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">📄 Nueva Version</button>
                    </form>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title">Datos Generales</div>
                    <div class="info-row">
                        <span class="info-label">Codigo BOM:</span>
                        <span class="info-value"><?php echo htmlspecialchars($bom_actual['bom_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Version:</span>
                        <span class="info-value">V<?php echo $bom_actual['bom_version']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?php echo htmlspecialchars($bom_actual['bom_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tipo:</span>
                        <span class="info-value"><?php echo htmlspecialchars($bom_actual['bom_type']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                        <span class="info-value"><?php echo strtoupper($bom_actual['status']); ?></span>
                    </div>
                    <?php if ($bom_actual['approved_by']): ?>
                    <div class="info-row">
                        <span class="info-label">Aprobado por:</span>
                        <span class="info-value"><?php echo htmlspecialchars($bom_actual['aprobador_nombre']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Aprobacion:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($bom_actual['approved_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-title">Producto y Costos</div>
                    <div class="info-row">
                        <span class="info-label">Producto:</span>
                        <span class="info-value"><?php echo htmlspecialchars($bom_actual['product_code'] . ' - ' . $bom_actual['product_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cantidad Base:</span>
                        <span class="info-value"><?php echo number_format($bom_actual['base_quantity'], 2); ?> <?php echo $bom_actual['unit_of_measure']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Componentes:</span>
                        <span class="info-value"><?php echo $cantidad_componentes; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Costo Materiales:</span>
                        <span class="info-value">$<?php echo number_format($costo_materiales, 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Costo Total:</span>
                        <span class="info-value" style="font-size: 18px; color: #10b981;">$<?php echo number_format($costo_total_bom, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs de Secciones -->
        <div class="section">
            <div class="tabs">
                <button class="tab active" onclick="cambiarTab(event, 'tab-datos')">📋 Datos</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-componentes')">📦 Componentes (<?php echo count($componentes_bom); ?>)</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-jerarquia')">🌳 Jerarquia</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-rutas')">⚙ Rutas (<?php echo count($rutas_producto); ?>)</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-costos')">💰 Costos</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-documentos')">📄 Documentos</button>
                <button class="tab" onclick="cambiarTab(event, 'tab-auditoria')">🔍 Auditoria</button>
            </div>

            <!-- TAB: Datos -->
            <div id="tab-datos" class="tab-content active">
                <?php if ($modo == 'editar'): ?>
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="actualizar_bom">
                    <input type="hidden" name="bom_id" value="<?php echo $bom_id; ?>">

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Producto Final</label>
                            <select name="product_id">
                                <option value="">Seleccione producto</option>
                                <?php foreach ($productos as $prod): ?>
                                <option value="<?php echo $prod['id']; ?>" <?php echo $prod['id'] == $bom_actual['product_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prod['product_code'] . ' - ' . $prod['product_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nombre del BOM</label>
                            <input type="text" name="bom_name" value="<?php echo htmlspecialchars($bom_actual['bom_name']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Version</label>
                            <input type="number" name="bom_version" value="<?php echo $bom_actual['bom_version']; ?>" min="1">
                        </div>

                        <div class="form-group">
                            <label>Tipo de BOM</label>
                            <select name="tipo_bom">
                                <option value="manufacturing" <?php echo $bom_actual['bom_type'] == 'manufacturing' ? 'selected' : ''; ?>>Fabricacion</option>
                                <option value="assembly" <?php echo $bom_actual['bom_type'] == 'assembly' ? 'selected' : ''; ?>>Ensamblaje</option>
                                <option value="kit" <?php echo $bom_actual['bom_type'] == 'kit' ? 'selected' : ''; ?>>Kit</option>
                                <option value="disassembly" <?php echo $bom_actual['bom_type'] == 'disassembly' ? 'selected' : ''; ?>>Desensamblaje</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Estado</label>
                            <select name="status">
                                <option value="draft" <?php echo $bom_actual['status'] == 'draft' ? 'selected' : ''; ?>>Borrador</option>
                                <option value="approved" <?php echo $bom_actual['status'] == 'approved' ? 'selected' : ''; ?>>Aprobado</option>
                                <option value="active" <?php echo $bom_actual['status'] == 'active' ? 'selected' : ''; ?>>Activo</option>
                                <option value="obsolete" <?php echo $bom_actual['status'] == 'obsolete' ? 'selected' : ''; ?>>Obsoleto</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cantidad Base</label>
                            <input type="number" name="base_quantity" value="<?php echo $bom_actual['base_quantity']; ?>" step="0.01">
                        </div>

                        <div class="form-group">
                            <label>Unidad de Medida</label>
                            <input type="text" name="unit_of_measure" value="<?php echo htmlspecialchars($bom_actual['unit_of_measure']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Valido Desde</label>
                            <input type="date" name="valid_from" value="<?php echo $bom_actual['valid_from']; ?>">
                        </div>

                        <div class="form-group">
                            <label>Valido Hasta</label>
                            <input type="date" name="valid_to" value="<?php echo $bom_actual['valid_to']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" <?php echo $bom_actual['is_active'] ? 'checked' : ''; ?>>
                                <span>Activo</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_default" <?php echo $bom_actual['is_default'] ? 'checked' : ''; ?>>
                                <span>BOM por Defecto</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="description"><?php echo htmlspecialchars($bom_actual['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Notas</label>
                        <textarea name="notes"><?php echo htmlspecialchars($bom_actual['notes'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <!-- TAB: Componentes -->
            <div id="tab-componentes" class="tab-content">
                <div style="margin-bottom: 20px;">
                    <button onclick="mostrarModal('modalComponente')" class="btn btn-primary">+ Agregar Componente</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Linea</th>
                                <th>Codigo</th>
                                <th>Descripcion</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>UM</th>
                                <th>Merma %</th>
                                <th>Costo Unit.</th>
                                <th>Costo Total</th>
                                <th>Critico</th>
                                <th>Sustituto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($componentes_bom)): ?>
                            <tr>
                                <td colspan="12" style="text-align: center; padding: 30px;">
                                    No hay componentes agregados a este BOM
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($componentes_bom as $comp): ?>
                                <tr>
                                    <td><?php echo $comp['line_number']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($comp['product_code'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($comp['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($comp['component_type']); ?></td>
                                    <td><?php echo number_format($comp['quantity_required'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($comp['unit_of_measure']); ?></td>
                                    <td><?php echo number_format($comp['scrap_percentage'], 2); ?>%</td>
                                    <td>$<?php echo number_format($comp['standard_cost'] ?? 0, 2); ?></td>
                                    <td><strong>$<?php echo number_format(($comp['quantity_required'] * ($comp['standard_cost'] ?? 0)), 2); ?></strong></td>
                                    <td>
                                        <?php if ($comp['is_critical']): ?>
                                        <span style="color: #ef4444;">⚠ SI</span>
                                        <?php else: ?>
                                        <span style="opacity: 0.5;">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($comp['substitute_product_id']): ?>
                                        <div style="font-size: 12px;">
                                            <?php echo htmlspecialchars($comp['sustituto_codigo']); ?>
                                        </div>
                                        <?php else: ?>
                                        <span style="opacity: 0.5;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminar este componente?');">
                                            <input type="hidden" name="accion" value="eliminar_componente">
                                            <input type="hidden" name="componente_id" value="<?php echo $comp['id']; ?>">
                                            <input type="hidden" name="bom_id" value="<?php echo $bom_id; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">✗</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($componentes_bom)): ?>
                <div style="margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.1); border-radius: 10px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 700; font-size: 16px;">COSTO TOTAL DE MATERIALES:</span>
                        <span style="font-weight: 800; font-size: 20px; color: #10b981;">$<?php echo number_format($costo_materiales, 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- TAB: Jerarquia -->
            <div id="tab-jerarquia" class="tab-content">
                <div class="card">
                    <div class="card-title">Estructura Jerarquica del BOM</div>

                    <div class="tree-item nivel-0">
                        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: rgba(255, 255, 255, 0.1); border-radius: 8px;">
                            <span style="font-size: 24px;">📦</span>
                            <div>
                                <div style="font-weight: 700; font-size: 16px;">
                                    <?php echo htmlspecialchars($bom_actual['product_code']); ?> - <?php echo htmlspecialchars($bom_actual['product_name']); ?>
                                </div>
                                <div style="font-size: 12px; opacity: 0.8;">
                                    Cantidad: <?php echo number_format($bom_actual['base_quantity'], 2); ?> <?php echo $bom_actual['unit_of_measure']; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($componentes_bom)): ?>
                            <?php foreach ($componentes_bom as $comp): ?>
                            <div class="tree-item nivel-1">
                                <div style="display: flex; align-items: center; gap: 10px; padding: 8px; background: rgba(255, 255, 255, 0.05); border-radius: 6px;">
                                    <span style="font-size: 20px;">
                                        <?php
                                        $iconos = [
                                            'raw_material' => '🔩',
                                            'semi_finished' => '⚙',
                                            'consumable' => '🧴',
                                            'phantom' => '👻'
                                        ];
                                        echo $iconos[$comp['component_type']] ?? '📦';
                                        ?>
                                    </span>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($comp['product_code']); ?> - <?php echo htmlspecialchars($comp['product_name']); ?>
                                        </div>
                                        <div style="font-size: 12px; opacity: 0.8;">
                                            Cantidad: <?php echo number_format($comp['quantity_required'], 2); ?> <?php echo $comp['unit_of_measure']; ?>
                                            | Tipo: <?php echo $comp['component_type']; ?>
                                            <?php if ($comp['is_critical']): ?>
                                            | <span style="color: #ef4444;">⚠ CRITICO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="font-weight: 700; color: #10b981;">
                                        $<?php echo number_format(($comp['quantity_required'] * ($comp['standard_cost'] ?? 0)), 2); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="tree-item nivel-1" style="opacity: 0.6;">
                                No hay componentes en este nivel
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB: Rutas -->
            <div id="tab-rutas" class="tab-content">
                <?php if (!empty($rutas_producto)): ?>
                    <?php foreach ($rutas_producto as $ruta): ?>
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-title">
                            Ruta: <?php echo htmlspecialchars($ruta['routing_number']); ?>
                            <span class="status-badge badge-<?php echo $ruta['status']; ?>" style="float: right;">
                                <?php echo strtoupper($ruta['status']); ?>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ruta['routing_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Version:</span>
                            <span class="info-value">V<?php echo $ruta['routing_version']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Valido Desde:</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($ruta['valid_from'])); ?></span>
                        </div>

                        <?php if (!empty($operaciones_ruta)): ?>
                        <div style="margin-top: 20px;">
                            <h4 style="margin-bottom: 10px;">Operaciones</h4>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Num.</th>
                                            <th>Operacion</th>
                                            <th>Centro de Trabajo</th>
                                            <th>Tipo</th>
                                            <th>T. Setup</th>
                                            <th>T. Run</th>
                                            <th>Critica</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($operaciones_ruta as $op): ?>
                                        <tr>
                                            <td><?php echo $op['operation_number']; ?></td>
                                            <td><?php echo htmlspecialchars($op['operation_name']); ?></td>
                                            <td><?php echo htmlspecialchars($op['work_center_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($op['operation_type']); ?></td>
                                            <td><?php echo $op['setup_time_minutes']; ?> min</td>
                                            <td><?php echo number_format($op['run_time_per_unit_minutes'], 2); ?> min</td>
                                            <td><?php echo $op['is_critical_path'] ? '⚠ SI' : 'No'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card">
                        <p style="text-align: center; padding: 30px; opacity: 0.7;">
                            No hay rutas asociadas a este producto
                        </p>
                        <div style="text-align: center;">
                            <button onclick="mostrarModal('modalRuta')" class="btn btn-primary">+ Crear Ruta</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB: Costos -->
            <div id="tab-costos" class="tab-content">
                <div class="grid-2">
                    <div class="card">
                        <div class="card-title">Desglose de Costos</div>
                        <div class="info-row">
                            <span class="info-label">Costo de Materiales:</span>
                            <span class="info-value">$<?php echo number_format($costo_materiales, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Costo Indirectos:</span>
                            <span class="info-value">$0.00</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Costo Mano Obra Est.:</span>
                            <span class="info-value">$0.00</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Costo Maquina Est.:</span>
                            <span class="info-value">$0.00</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Costo Scrap Est.:</span>
                            <span class="info-value">$0.00</span>
                        </div>
                        <div class="info-row" style="border-top: 2px solid rgba(255, 255, 255, 0.3); margin-top: 10px; padding-top: 10px;">
                            <span class="info-label" style="font-size: 16px; font-weight: 700;">COSTO TOTAL:</span>
                            <span class="info-value" style="font-size: 20px; color: #10b981;">${{number_format($costo_total_bom, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Costo Unitario:</span>
                            <span class="info-value">$<?php echo number_format(($costo_total_bom / max($bom_actual['base_quantity'], 1)), 2); ?></span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">Componentes mas Costosos</div>
                        <?php
                        $componentes_ordenados = $componentes_bom;
                        usort($componentes_ordenados, function($a, $b) {
                            $costo_a = $a['quantity_required'] * ($a['standard_cost'] ?? 0);
                            $costo_b = $b['quantity_required'] * ($b['standard_cost'] ?? 0);
                            return $costo_b <=> $costo_a;
                        });
                        $top_componentes = array_slice($componentes_ordenados, 0, 5);
                        ?>
                        <?php if (!empty($top_componentes)): ?>
                            <?php foreach ($top_componentes as $idx => $comp): ?>
                            <div class="info-row">
                                <span class="info-label">
                                    <?php echo ($idx + 1); ?>. <?php echo htmlspecialchars($comp['product_code']); ?>
                                </span>
                                <span class="info-value">
                                    $<?php echo number_format(($comp['quantity_required'] * ($comp['standard_cost'] ?? 0)), 2); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="opacity: 0.7;">No hay componentes</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB: Documentos -->
            <div id="tab-documentos" class="tab-content">
                <div class="card">
                    <div class="card-title">Documentos Asociados</div>
                    <p style="opacity: 0.7; margin-bottom: 20px;">
                        Sistema de gestion de documentos: planos, especificaciones tecnicas, manuales, fichas tecnicas, certificaciones, etc.
                    </p>
                    <div style="text-align: center; padding: 40px;">
                        <span style="font-size: 64px;">📄</span>
                        <p style="margin-top: 20px;">Funcionalidad de carga de documentos en desarrollo</p>
                    </div>
                </div>
            </div>

            <!-- TAB: Auditoria -->
            <div id="tab-auditoria" class="tab-content">
                <div class="card">
                    <div class="card-title">Informacion de Auditoria</div>
                    <div class="info-row">
                        <span class="info-label">Fecha Creacion:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($bom_actual['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Version Actual:</span>
                        <span class="info-value">V<?php echo $bom_actual['bom_version']; ?></span>
                    </div>
                    <?php if ($bom_actual['approved_by']): ?>
                    <div class="info-row">
                        <span class="info-label">Aprobado por:</span>
                        <span class="info-value"><?php echo htmlspecialchars($bom_actual['aprobador_nombre']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha Aprobacion:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($bom_actual['approved_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Estado Actual:</span>
                        <span class="info-value">
                            <span class="status-badge badge-<?php echo $bom_actual['status']; ?>">
                                <?php echo strtoupper($bom_actual['status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">BOM por Defecto:</span>
                        <span class="info-value"><?php echo $bom_actual['is_default'] ? 'SI' : 'No'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Componentes:</span>
                        <span class="info-value"><?php echo count($componentes_bom); ?></span>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <div class="card-title">Historial de Versiones</div>
                    <?php
                    // Obtener versiones anteriores
                    $sql = "SELECT * FROM pp_bom
                            WHERE company_id = ? AND product_id = ? AND id != ?
                            ORDER BY bom_version DESC LIMIT 10";
                    $versiones = ejecutarConsultaMultiple($pdo, $sql, [$company_id, $bom_actual['product_id'], $bom_id]);
                    ?>
                    <?php if (!empty($versiones)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Version</th>
                                        <th>Codigo BOM</th>
                                        <th>Estado</th>
                                        <th>Fecha Creacion</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($versiones as $version): ?>
                                    <tr>
                                        <td>V<?php echo $version['bom_version']; ?></td>
                                        <td><?php echo htmlspecialchars($version['bom_number']); ?></td>
                                        <td>
                                            <span class="status-badge badge-<?php echo $version['status']; ?>">
                                                <?php echo strtoupper($version['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($version['created_at'])); ?></td>
                                        <td>
                                            <a href="?modo=ver&id=<?php echo $version['id']; ?>" class="btn btn-secondary btn-sm">Ver</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="opacity: 0.7; text-align: center; padding: 20px;">
                            No hay versiones anteriores de este BOM
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Modal Agregar Componente -->
    <div id="modalComponente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Agregar Componente al BOM</div>
                <button class="close-modal" onclick="cerrarModal('modalComponente')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="agregar_componente">
                <input type="hidden" name="bom_id" value="<?php echo $bom_id; ?>">

                <div class="form-group">
                    <label>Numero de Linea</label>
                    <input type="number" name="line_number" value="<?php echo count($componentes_bom) + 1; ?>" required>
                </div>

                <div class="form-group">
                    <label>Componente / Material *</label>
                    <select name="component_product_id" required>
                        <option value="">Seleccione producto</option>
                        <?php foreach ($productos as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>">
                            <?php echo htmlspecialchars($prod['product_code'] . ' - ' . $prod['product_name']); ?>
                            (Costo: $<?php echo number_format($prod['standard_cost'] ?? 0, 2); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tipo de Componente *</label>
                    <select name="component_type" required>
                        <option value="raw_material">Materia Prima</option>
                        <option value="semi_finished">Semi-terminado</option>
                        <option value="consumable">Consumible</option>
                        <option value="phantom">Fantasma</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cantidad Requerida *</label>
                    <input type="number" name="quantity_required" step="0.0001" required>
                </div>

                <div class="form-group">
                    <label>Unidad de Medida</label>
                    <input type="text" name="unit_of_measure" value="UN" required>
                </div>

                <div class="form-group">
                    <label>Porcentaje de Merma</label>
                    <input type="number" name="scrap_percentage" step="0.01" value="0" min="0" max="100">
                </div>

                <div class="form-group">
                    <label>Producto Sustituto (opcional)</label>
                    <select name="substitute_product_id">
                        <option value="">Sin sustituto</option>
                        <?php foreach ($productos as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>">
                            <?php echo htmlspecialchars($prod['product_code'] . ' - ' . $prod['product_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_critical">
                        <span>Componente Critico</span>
                    </label>
                </div>

                <div class="form-group">
                    <label>Notas</label>
                    <textarea name="notes_componente" placeholder="Observaciones sobre este componente"></textarea>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Agregar Componente</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalComponente')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Crear Ruta -->
    <div id="modalRuta" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Crear Nueva Ruta</div>
                <button class="close-modal" onclick="cerrarModal('modalRuta')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="crear_ruta">
                <input type="hidden" name="product_id_ruta" value="<?php echo $bom_actual['product_id'] ?? ''; ?>">

                <div class="form-group">
                    <label>Nombre de la Ruta *</label>
                    <input type="text" name="routing_name" required>
                </div>

                <div class="form-group">
                    <label>Version</label>
                    <input type="number" name="routing_version" value="1" min="1">
                </div>

                <div class="form-group">
                    <label>Valido Desde</label>
                    <input type="date" name="valid_from_ruta" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Descripcion</label>
                    <textarea name="description_ruta"></textarea>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Crear Ruta</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalRuta')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funcion para cambiar tabs
        function cambiarTab(evt, tabId) {
            var contents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < contents.length; i++) {
                contents[i].classList.remove('active');
            }

            var tabs = document.getElementsByClassName('tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }

            document.getElementById(tabId).classList.add('active');
            evt.currentTarget.classList.add('active');
        }

        // Funciones para modales
        function mostrarModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
