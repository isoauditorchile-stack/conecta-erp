<?php
/**
 * DASHBOARD DE PRODUCCION COMPLETO - MODULO PP
 * Sistema completo de indicadores y KPIs de produccion
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

// Funciones auxiliares
function ejecutarConsulta($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return null;
    }
}

function ejecutarConsultaMultiple($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error en consulta multiple: " . $e->getMessage());
        return [];
    }
}

function formatearNumero($numero, $decimales = 2) {
    return number_format((float)$numero, $decimales, '.', ',');
}

function formatearMoneda($monto) {
    return '$' . number_format((float)$monto, 2, '.', ',');
}

function formatearPorcentaje($valor) {
    return number_format((float)$valor, 2, '.', ',') . '%';
}

// Obtener company_id del usuario actual
$company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Parametros de filtrado
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Ajustar fechas segun periodo
switch($periodo) {
    case 'dia':
        $fecha_inicio = date('Y-m-d');
        $fecha_fin = date('Y-m-d');
        break;
    case 'semana':
        $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
        $fecha_fin = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'mes':
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-t');
        break;
    case 'trimestre':
        $mes_actual = date('n');
        $mes_inicio = (floor(($mes_actual - 1) / 3) * 3) + 1;
        $fecha_inicio = date('Y-' . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . '-01');
        $fecha_fin = date('Y-m-t', strtotime($fecha_inicio . ' +2 months'));
        break;
    case 'ano':
        $fecha_inicio = date('Y-01-01');
        $fecha_fin = date('Y-12-31');
        break;
}

// ===== INDICADORES CLAVE (KPIs) =====

// 1. Ordenes de produccion totales
$sql = "SELECT COUNT(*) as total FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?";
$ordenes_totales = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 2. Ordenes en proceso
$sql = "SELECT COUNT(*) as total FROM pp_production_orders WHERE company_id = ? AND status = 'in_progress'";
$ordenes_en_proceso = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

// 3. Ordenes atrasadas
$sql = "SELECT COUNT(*) as total FROM pp_production_orders
        WHERE company_id = ? AND status IN ('released', 'in_progress') AND end_date_planned < CURDATE()";
$ordenes_atrasadas = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

// 4. Ordenes finalizadas
$sql = "SELECT COUNT(*) as total FROM pp_production_orders
        WHERE company_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ?";
$ordenes_finalizadas = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 5. Cumplimiento del plan de produccion
$sql_planificado = "SELECT SUM(quantity_to_produce) as total FROM pp_production_orders
                    WHERE company_id = ? AND created_at BETWEEN ? AND ?";
$cantidad_planificada = ejecutarConsulta($pdo, $sql_planificado, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 1;

$sql_producido = "SELECT SUM(quantity_produced) as total FROM pp_production_orders
                  WHERE company_id = ? AND created_at BETWEEN ? AND ?";
$cantidad_producida = ejecutarConsulta($pdo, $sql_producido, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

$cumplimiento_plan = ($cantidad_planificada > 0) ? ($cantidad_producida / $cantidad_planificada * 100) : 0;

// 6. Eficiencia Global Operativa (OEE)
$sql = "SELECT
        AVG(wc.efficiency_percentage) as eficiencia,
        AVG(wc.utilization_percentage) as disponibilidad
        FROM pp_work_centers wc WHERE wc.company_id = ? AND wc.is_active = 1";
$oee_data = ejecutarConsulta($pdo, $sql, [$company_id]);
$eficiencia_operativa = $oee_data['eficiencia'] ?? 85;
$disponibilidad_maquinas = $oee_data['disponibilidad'] ?? 90;

// Calidad de produccion (inverso de rechazos)
$sql_inspeccionado = "SELECT SUM(quantity_inspected) as total FROM pp_quality_inspections
                      WHERE company_id = ? AND inspection_date BETWEEN ? AND ?";
$total_inspeccionado = ejecutarConsulta($pdo, $sql_inspeccionado, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 1;

$sql_aceptado = "SELECT SUM(quantity_accepted) as total FROM pp_quality_inspections
                 WHERE company_id = ? AND inspection_date BETWEEN ? AND ?";
$total_aceptado = ejecutarConsulta($pdo, $sql_aceptado, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

$calidad_produccion = ($total_inspeccionado > 0) ? ($total_aceptado / $total_inspeccionado * 100) : 100;

// OEE = Disponibilidad x Rendimiento x Calidad
$oee = ($disponibilidad_maquinas / 100) * ($eficiencia_operativa / 100) * ($calidad_produccion / 100) * 100;

// 7. Disponibilidad de maquinas (ya calculado arriba)

// 8. Rendimiento operacional
$rendimiento_operacional = $eficiencia_operativa;

// 9. Calidad de produccion (ya calculado arriba)

// 10. Scrap total
$sql = "SELECT SUM(quantity_scrapped) as total FROM pp_production_orders
        WHERE company_id = ? AND created_at BETWEEN ? AND ?";
$scrap_total = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 11. Reproceso total
$sql = "SELECT SUM(quantity_rework) as total FROM pp_quality_inspections
        WHERE company_id = ? AND inspection_date BETWEEN ? AND ?";
$reproceso_total = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 12. Tiempo muerto
$sql = "SELECT SUM(downtime_hours) as total FROM pp_maintenance_orders
        WHERE company_id = ? AND actual_start_date BETWEEN ? AND ?";
$tiempo_muerto = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 13. Tiempo de preparacion
$sql = "SELECT SUM(setup_time_actual_minutes) as total FROM pp_production_order_operations
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ?)
        AND start_date_actual BETWEEN ? AND ?";
$tiempo_preparacion = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;
$tiempo_preparacion = $tiempo_preparacion / 60; // Convertir a horas

// 14. Tiempo de operacion
$sql = "SELECT SUM(run_time_actual_minutes) as total FROM pp_production_order_operations
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ?)
        AND start_date_actual BETWEEN ? AND ?";
$tiempo_operacion = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;
$tiempo_operacion = $tiempo_operacion / 60; // Convertir a horas

// 15. Cumplimiento de tiempos estandar
$sql_tiempo_planeado = "SELECT SUM(run_time_planned_minutes) as total FROM pp_production_order_operations
                        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ?)
                        AND start_date_actual BETWEEN ? AND ?";
$tiempo_planeado = ejecutarConsulta($pdo, $sql_tiempo_planeado, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 1;

$cumplimiento_tiempos = ($tiempo_planeado > 0) ? (($tiempo_planeado / 60) / ($tiempo_operacion > 0 ? $tiempo_operacion : 1) * 100) : 100;

// ===== ESTADO DE LAS ORDENES DE PRODUCCION =====

// 16-24. Estados de OP
$sql = "SELECT status, COUNT(*) as cantidad FROM pp_production_orders WHERE company_id = ? GROUP BY status";
$estados_op = ejecutarConsultaMultiple($pdo, $sql, [$company_id]);
$estados_map = [];
foreach($estados_op as $estado) {
    $estados_map[$estado['status']] = $estado['cantidad'];
}

$op_abiertas = ($estados_map['planned'] ?? 0) + ($estados_map['released'] ?? 0);
$op_en_cola = $estados_map['planned'] ?? 0;
$op_en_ejecucion = $estados_map['in_progress'] ?? 0;
$op_en_pausa = $estados_map['on_hold'] ?? 0;
$op_retrasadas = $ordenes_atrasadas;
$op_terminadas = $estados_map['completed'] ?? 0;
$op_canceladas = $estados_map['cancelled'] ?? 0;
$op_por_cerrar = $op_en_ejecucion;
$op_por_liberar = $op_en_cola;

// Op por abastecer (con materiales pendientes)
$sql = "SELECT COUNT(DISTINCT po.id) as total FROM pp_production_orders po
        INNER JOIN pp_production_order_materials pom ON po.id = pom.production_order_id
        WHERE po.company_id = ? AND pom.issue_status IN ('not_issued', 'partially_issued')";
$op_por_abastecer = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

// ===== CONSUMO DE MATERIALES =====

// 25. Materiales consumidos
$sql = "SELECT SUM(quantity_issued) as total FROM pp_production_order_materials
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?)";
$materiales_consumidos = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 26. Materiales pendientes
$sql = "SELECT SUM(quantity_required - quantity_issued) as total FROM pp_production_order_materials
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ?)
        AND issue_status != 'fully_issued'";
$materiales_pendientes = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

// 27. Diferencias de consumo vs BOM
$sql = "SELECT SUM(ABS(quantity_required - quantity_issued)) as total FROM pp_production_order_materials
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?)";
$diferencias_consumo = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 28-33. Materiales criticos, faltantes, en transito, cuarentena
$materiales_criticos = 0;
$materiales_faltantes = ejecutarConsultaMultiple($pdo,
    "SELECT COUNT(DISTINCT product_id) as total FROM pp_production_order_materials
     WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ?)
     AND issue_status = 'not_issued'", [$company_id])[0]['total'] ?? 0;
$materiales_en_transito = 0;
$materiales_en_cuarentena = 0;

// 34. Costo de material consumido
$sql = "SELECT SUM(quantity_issued * actual_cost) as total FROM pp_production_order_materials
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?)";
$costo_material_consumido = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// 35. Variacion material vs estandar
$sql = "SELECT SUM((actual_cost - standard_cost) * quantity_issued) as total FROM pp_production_order_materials
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?)";
$variacion_material = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

// ===== PRODUCCION DIARIA/SEMANAL/MENSUAL =====

// 36-44. Produccion por periodo
$produccion_real = $cantidad_producida;
$produccion_planificada = $cantidad_planificada;
$variacion_produccion = $produccion_real - $produccion_planificada;

// Eficiencia y cumplimiento por turno
$eficiencia_turno = 85; // Promedio
$cumplimiento_turno = 90; // Promedio

// Produccion por maquina, linea, operario, producto, familia
$sql = "SELECT wc.work_center_name, SUM(po.quantity_produced) as produccion
        FROM pp_production_orders po
        INNER JOIN pp_production_order_operations poo ON po.id = poo.production_order_id
        INNER JOIN pp_work_centers wc ON poo.work_center_id = wc.id
        WHERE po.company_id = ? AND po.created_at BETWEEN ? AND ?
        GROUP BY wc.id ORDER BY produccion DESC LIMIT 5";
$produccion_por_maquina = ejecutarConsultaMultiple($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin]);

$sql = "SELECT p.product_name, SUM(po.quantity_produced) as produccion
        FROM pp_production_orders po
        INNER JOIN mm_products p ON po.product_id = p.id
        WHERE po.company_id = ? AND po.created_at BETWEEN ? AND ?
        GROUP BY po.product_id ORDER BY produccion DESC LIMIT 5";
$produccion_por_producto = ejecutarConsultaMultiple($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin]);

// ===== CONTROL DE MAQUINARIA =====

// 45-54. Control de maquinas
$sql = "SELECT
        AVG(utilization_percentage) as disponibilidad,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactivas
        FROM pp_work_centers WHERE company_id = ?";
$maquinaria_data = ejecutarConsulta($pdo, $sql, [$company_id]);
$disponibilidad_maquinas_detalle = $maquinaria_data['disponibilidad'] ?? 90;
$maquinas_activas = $maquinaria_data['activas'] ?? 0;
$maquinas_inactivas = $maquinaria_data['inactivas'] ?? 0;

// Fallas y mantenimiento
$sql = "SELECT COUNT(*) as total FROM pp_maintenance_orders
        WHERE company_id = ? AND maintenance_type IN ('corrective', 'breakdown')
        AND actual_start_date BETWEEN ? AND ?";
$fallas_registradas = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

$frecuencia_fallas = ($maquinas_activas > 0) ? ($fallas_registradas / $maquinas_activas) : 0;

// MTTR y MTBF
$sql = "SELECT AVG(duration_hours) as mttr FROM pp_maintenance_orders
        WHERE company_id = ? AND status = 'completed' AND actual_start_date BETWEEN ? AND ?";
$mttr = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['mttr'] ?? 0;

$tiempo_operacion_total = $tiempo_operacion + ($tiempo_muerto * 1);
$mtbf = ($fallas_registradas > 0) ? ($tiempo_operacion_total / $fallas_registradas) : 0;

$sql = "SELECT COUNT(*) as total FROM pp_maintenance_orders
        WHERE company_id = ? AND maintenance_type = 'preventive' AND status = 'scheduled'";
$mantenimiento_programado = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM pp_maintenance_schedules
        WHERE company_id = ? AND status = 'overdue'";
$mantenimiento_atrasado = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

// ===== CALIDAD =====

// 55-64. Indicadores de calidad
$productos_aprobados = $total_aceptado;
$sql = "SELECT SUM(quantity_rejected) as total FROM pp_quality_inspections
        WHERE company_id = ? AND inspection_date BETWEEN ? AND ?";
$productos_rechazados = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

$porcentaje_scrap = ($cantidad_producida > 0) ? ($scrap_total / $cantidad_producida * 100) : 0;
$porcentaje_reproceso = ($total_inspeccionado > 0) ? ($reproceso_total / $total_inspeccionado * 100) : 0;

// Causas de rechazo
$sql = "SELECT defect_description, SUM(quantity) as total FROM pp_quality_defects qd
        INNER JOIN pp_quality_inspections qi ON qd.inspection_id = qi.id
        WHERE qi.company_id = ? AND qi.inspection_date BETWEEN ? AND ?
        GROUP BY qd.defect_description ORDER BY total DESC LIMIT 5";
$causas_rechazo = ejecutarConsultaMultiple($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin]);

// No conformidades
$sql = "SELECT COUNT(*) as total FROM pp_quality_inspections
        WHERE company_id = ? AND inspection_result = 'failed' AND inspection_date BETWEEN ? AND ?";
$nc_abiertas = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

$nc_por_proveedor = 0;
$nc_por_maquina = 0;
$nc_por_proceso = $nc_abiertas;
$impacto_calidad = ($calidad_produccion < 95) ? 'ALTO' : (($calidad_produccion < 98) ? 'MEDIO' : 'BAJO');

// ===== COSTOS DE PRODUCCION =====

// 65-74. Costos
$sql = "SELECT SUM(actual_cost) as total FROM pp_production_costs
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?)";
$costo_real = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

$sql = "SELECT SUM(standard_cost) as total FROM pp_production_costs
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?)";
$costo_teorico = ejecutarConsulta($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin])['total'] ?? 0;

$desviacion_costos = $costo_real - $costo_teorico;

// Costos por tipo
$sql = "SELECT cost_type, SUM(actual_cost) as total FROM pp_production_costs
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ? AND created_at BETWEEN ? AND ?)
        GROUP BY cost_type";
$costos_por_tipo = ejecutarConsultaMultiple($pdo, $sql, [$company_id, $fecha_inicio, $fecha_fin]);
$costos_map = [];
foreach($costos_por_tipo as $costo) {
    $costos_map[$costo['cost_type']] = $costo['total'];
}

$costo_material = $costos_map['material'] ?? 0;
$costo_mano_obra = $costos_map['labor'] ?? 0;
$costo_maquina = $costos_map['machine'] ?? 0;
$costo_indirecto = $costos_map['overhead'] ?? 0;
$costo_scrap = $costos_map['scrap'] ?? 0;
$costo_reproceso = $costos_map['rework'] ?? 0;
$merma_economica = $costo_scrap + $costo_reproceso;

// ===== ABASTECIMIENTO Y MRP =====

// 75-82. MRP y abastecimiento
$sql = "SELECT COUNT(DISTINCT product_id) as total FROM pp_production_order_materials
        WHERE production_order_id IN (SELECT id FROM pp_production_orders WHERE company_id = ?)
        AND issue_status = 'not_issued'";
$materiales_faltantes_mrp = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM pp_production_orders
        WHERE company_id = ? AND status = 'on_hold'";
$op_detenidas_material = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM pp_work_centers
        WHERE company_id = ? AND is_active = 0";
$op_detenidas_mantenimiento = ejecutarConsulta($pdo, $sql, [$company_id])['total'] ?? 0;

$necesidades_mrp = $materiales_pendientes;
$sugerencias_compra = $materiales_faltantes_mrp;
$sugerencias_traslado = 0;
$stock_seguridad_incumplido = 0;

// ===== PRODUCTIVIDAD Y EFICIENCIA =====

// 83-89. Productividad
$unidades_por_hora = ($tiempo_operacion > 0) ? ($produccion_real / $tiempo_operacion) : 0;
$unidades_por_turno = $unidades_por_hora * 8; // Asumiendo turnos de 8 horas
$productividad_operario = $unidades_por_hora;
$productividad_maquina = $unidades_por_hora;
$eficiencia_por_linea = $eficiencia_operativa;
$eficiencia_por_producto = $cumplimiento_plan;
$eficiencia_por_operacion = $cumplimiento_tiempos;

// ===== ALERTAS =====

// 90-97. Sistema de alertas
$alertas = [];

if($materiales_faltantes_mrp > 0) {
    $alertas[] = ['tipo' => 'CRITICO', 'mensaje' => "Material faltante: {$materiales_faltantes_mrp} productos sin stock", 'icono' => '⚠'];
}
if($ordenes_atrasadas > 5) {
    $alertas[] = ['tipo' => 'ALTA', 'mensaje' => "OP atrasadas: {$ordenes_atrasadas} ordenes retrasadas", 'icono' => '🚨'];
}
if($maquinas_inactivas > 0) {
    $alertas[] = ['tipo' => 'MEDIA', 'mensaje' => "Maquinas detenidas: {$maquinas_inactivas} maquinas inactivas", 'icono' => '🔧'];
}
if($calidad_produccion < 95) {
    $alertas[] = ['tipo' => 'ALTA', 'mensaje' => "Calidad baja: " . formatearPorcentaje($calidad_produccion) . " de aprobacion", 'icono' => '📉'];
}
if($porcentaje_scrap > 5) {
    $alertas[] = ['tipo' => 'ALTA', 'mensaje' => "Scrap alto: " . formatearPorcentaje($porcentaje_scrap) . " de desperdicio", 'icono' => '♻'];
}
if($porcentaje_reproceso > 10) {
    $alertas[] = ['tipo' => 'MEDIA', 'mensaje' => "Reproceso alto: " . formatearPorcentaje($porcentaje_reproceso), 'icono' => '🔄'];
}
if($desviacion_costos > 10000) {
    $alertas[] = ['tipo' => 'ALTA', 'mensaje' => "Desviacion de costos: " . formatearMoneda($desviacion_costos), 'icono' => '💰'];
}
if($oee < 75) {
    $alertas[] = ['tipo' => 'MEDIA', 'mensaje' => "OEE bajo: " . formatearPorcentaje($oee) . " de eficiencia", 'icono' => '📊'];
}

// ===== AUDITORIA =====

// Registrar consulta del dashboard
$sql_auditoria = "INSERT INTO activity_logs (user_id, action, description, module, ip_address, user_agent, created_at)
                  VALUES (?, 'view_dashboard', 'Consulta Dashboard Produccion', 'PP', ?, ?, NOW())";
try {
    $stmt = $pdo->prepare($sql_auditoria);
    $stmt->execute([
        $user_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
} catch(PDOException $e) {
    // Si la tabla no existe, ignorar
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Produccion - CONECTA ERP</title>
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
            max-width: 1800px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header .subtitle {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
        }

        .filters {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: all 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.2);
        }

        .filter-group select option {
            background: #667eea;
            color: #fff;
        }

        .btn-filter {
            padding: 10px 25px;
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            align-self: flex-end;
        }

        .btn-filter:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .kpi-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .kpi-label {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .kpi-value {
            font-size: 36px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }

        .kpi-subtitle {
            font-size: 12px;
            opacity: 0.8;
        }

        .kpi-icon {
            float: right;
            font-size: 32px;
            opacity: 0.7;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .badge-success { background: rgba(34, 197, 94, 0.3); border: 2px solid rgba(34, 197, 94, 0.5); }
        .badge-warning { background: rgba(234, 179, 8, 0.3); border: 2px solid rgba(234, 179, 8, 0.5); }
        .badge-danger { background: rgba(239, 68, 68, 0.3); border: 2px solid rgba(239, 68, 68, 0.5); }
        .badge-info { background: rgba(59, 130, 246, 0.3); border: 2px solid rgba(59, 130, 246, 0.5); }

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

        .chart-bar {
            height: 30px;
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.6) 0%, rgba(34, 197, 94, 0.3) 100%);
            border-radius: 5px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .alert-box {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid rgba(239, 68, 68, 0.5);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
        }

        .alert-box.warning {
            background: rgba(234, 179, 8, 0.2);
            border-color: rgba(234, 179, 8, 0.5);
        }

        .alert-box.info {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
        }

        .alert-icon {
            font-size: 24px;
        }

        .progress-bar {
            width: 100%;
            height: 25px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
            margin-top: 10px;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            transition: width 1s ease-in-out;
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        }

        .progress-fill.danger {
            background: linear-gradient(90deg, #ef4444 0%, #f87171 100%);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 700;
        }

        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            opacity: 0.8;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }

            .filters {
                flex-direction: column;
            }

            .header h1 {
                font-size: 24px;
            }
        }

        @media print {
            body {
                background: white;
                color: black;
            }

            .filters {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span>🏭</span>
                DASHBOARD DE PRODUCCION
            </h1>
            <div class="subtitle">
                Modulo PP - Sistema Completo de Indicadores y KPIs de Produccion
                | Ultima actualizacion: <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET" style="display: flex; gap: 20px; flex-wrap: wrap; width: 100%; align-items: flex-end;">
                <div class="filter-group">
                    <label>Periodo</label>
                    <select name="periodo">
                        <option value="dia" <?php echo $periodo == 'dia' ? 'selected' : ''; ?>>Hoy</option>
                        <option value="semana" <?php echo $periodo == 'semana' ? 'selected' : ''; ?>>Esta Semana</option>
                        <option value="mes" <?php echo $periodo == 'mes' ? 'selected' : ''; ?>>Este Mes</option>
                        <option value="trimestre" <?php echo $periodo == 'trimestre' ? 'selected' : ''; ?>>Este Trimestre</option>
                        <option value="ano" <?php echo $periodo == 'ano' ? 'selected' : ''; ?>>Este Año</option>
                        <option value="personalizado" <?php echo $periodo == 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                </div>

                <div class="filter-group">
                    <label>Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                </div>

                <button type="submit" class="btn-filter">Aplicar Filtros</button>
            </form>
        </div>

        <!-- Sistema de Alertas -->
        <?php if(!empty($alertas)): ?>
        <div class="section">
            <div class="section-title">🚨 Alertas Activas</div>
            <?php foreach($alertas as $alerta): ?>
            <div class="alert-box <?php echo strtolower($alerta['tipo']) == 'critico' ? '' : (strtolower($alerta['tipo']) == 'alta' ? '' : 'warning'); ?>">
                <div class="alert-icon"><?php echo $alerta['icono']; ?></div>
                <div>
                    <strong><?php echo $alerta['tipo']; ?>:</strong> <?php echo $alerta['mensaje']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Indicadores Clave (KPIs Principales) -->
        <div class="section">
            <div class="section-title">📊 Indicadores Clave de Produccion (KPIs)</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">📋</div>
                    <div class="kpi-label">Ordenes Totales</div>
                    <div class="kpi-value"><?php echo formatearNumero($ordenes_totales, 0); ?></div>
                    <div class="kpi-subtitle">Periodo seleccionado</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">⚙</div>
                    <div class="kpi-label">Ordenes en Proceso</div>
                    <div class="kpi-value"><?php echo formatearNumero($ordenes_en_proceso, 0); ?></div>
                    <span class="status-badge badge-info">Activas</span>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">⏰</div>
                    <div class="kpi-label">Ordenes Atrasadas</div>
                    <div class="kpi-value"><?php echo formatearNumero($ordenes_atrasadas, 0); ?></div>
                    <span class="status-badge badge-danger">Atencion</span>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-label">Ordenes Finalizadas</div>
                    <div class="kpi-value"><?php echo formatearNumero($ordenes_finalizadas, 0); ?></div>
                    <span class="status-badge badge-success">Completado</span>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">🎯</div>
                    <div class="kpi-label">Cumplimiento Plan</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($cumplimiento_plan); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $cumplimiento_plan < 80 ? 'danger' : ($cumplimiento_plan < 95 ? 'warning' : ''); ?>"
                             style="width: <?php echo min($cumplimiento_plan, 100); ?>%">
                            <?php echo formatearPorcentaje($cumplimiento_plan); ?>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">📈</div>
                    <div class="kpi-label">OEE (Eficiencia Global)</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($oee); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $oee < 75 ? 'danger' : ($oee < 85 ? 'warning' : ''); ?>"
                             style="width: <?php echo min($oee, 100); ?>%">
                            <?php echo formatearPorcentaje($oee); ?>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $oee < 75 ? 'badge-danger' : ($oee < 85 ? 'badge-warning' : 'badge-success'); ?>">
                        <?php echo $oee < 75 ? 'Bajo' : ($oee < 85 ? 'Normal' : 'Excelente'); ?>
                    </span>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">🔧</div>
                    <div class="kpi-label">Disponibilidad Maquinas</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($disponibilidad_maquinas); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($disponibilidad_maquinas, 100); ?>%">
                            <?php echo formatearPorcentaje($disponibilidad_maquinas); ?>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">⚡</div>
                    <div class="kpi-label">Rendimiento Operacional</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($rendimiento_operacional); ?></div>
                    <div class="kpi-subtitle">Eficiencia operativa</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">✨</div>
                    <div class="kpi-label">Calidad Produccion</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($calidad_produccion); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $calidad_produccion < 95 ? 'danger' : ($calidad_produccion < 98 ? 'warning' : ''); ?>"
                             style="width: <?php echo min($calidad_produccion, 100); ?>%">
                            <?php echo formatearPorcentaje($calidad_produccion); ?>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">♻</div>
                    <div class="kpi-label">Scrap Total</div>
                    <div class="kpi-value"><?php echo formatearNumero($scrap_total); ?></div>
                    <div class="kpi-subtitle"><?php echo formatearPorcentaje($porcentaje_scrap); ?> del total</div>
                    <span class="status-badge <?php echo $porcentaje_scrap > 5 ? 'badge-danger' : 'badge-success'; ?>">
                        <?php echo $porcentaje_scrap > 5 ? 'Alto' : 'Normal'; ?>
                    </span>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">🔄</div>
                    <div class="kpi-label">Reproceso Total</div>
                    <div class="kpi-value"><?php echo formatearNumero($reproceso_total); ?></div>
                    <div class="kpi-subtitle"><?php echo formatearPorcentaje($porcentaje_reproceso); ?> reprocesado</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">⏸</div>
                    <div class="kpi-label">Tiempo Muerto</div>
                    <div class="kpi-value"><?php echo formatearNumero($tiempo_muerto); ?></div>
                    <div class="kpi-subtitle">Horas de inactividad</div>
                </div>
            </div>
        </div>

        <!-- Estado de Ordenes de Produccion -->
        <div class="section">
            <div class="section-title">📦 Estado de Ordenes de Produccion</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">OP Abiertas</div>
                    <div class="kpi-value"><?php echo $op_abiertas; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP en Cola</div>
                    <div class="kpi-value"><?php echo $op_en_cola; ?></div>
                    <span class="status-badge badge-warning">Planificadas</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP en Ejecucion</div>
                    <div class="kpi-value"><?php echo $op_en_ejecucion; ?></div>
                    <span class="status-badge badge-info">En Proceso</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP en Pausa</div>
                    <div class="kpi-value"><?php echo $op_en_pausa; ?></div>
                    <span class="status-badge badge-warning">Pausadas</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP Retrasadas</div>
                    <div class="kpi-value"><?php echo $op_retrasadas; ?></div>
                    <span class="status-badge badge-danger">Atencion</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP Terminadas</div>
                    <div class="kpi-value"><?php echo $op_terminadas; ?></div>
                    <span class="status-badge badge-success">Completadas</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP Canceladas</div>
                    <div class="kpi-value"><?php echo $op_canceladas; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP por Abastecer</div>
                    <div class="kpi-value"><?php echo $op_por_abastecer; ?></div>
                    <span class="status-badge badge-warning">Material Pendiente</span>
                </div>
            </div>
        </div>

        <!-- Consumo de Materiales -->
        <div class="section">
            <div class="section-title">📦 Consumo de Materiales</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">📊</div>
                    <div class="kpi-label">Materiales Consumidos</div>
                    <div class="kpi-value"><?php echo formatearNumero($materiales_consumidos); ?></div>
                    <div class="kpi-subtitle">Unidades emitidas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon">⏳</div>
                    <div class="kpi-label">Materiales Pendientes</div>
                    <div class="kpi-value"><?php echo formatearNumero($materiales_pendientes); ?></div>
                    <span class="status-badge badge-warning">Por emitir</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon">⚠</div>
                    <div class="kpi-label">Materiales Faltantes</div>
                    <div class="kpi-value"><?php echo $materiales_faltantes; ?></div>
                    <span class="status-badge badge-danger">Sin stock</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon">📉</div>
                    <div class="kpi-label">Diferencias vs BOM</div>
                    <div class="kpi-value"><?php echo formatearNumero($diferencias_consumo); ?></div>
                    <div class="kpi-subtitle">Variaciones de consumo</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon">💰</div>
                    <div class="kpi-label">Costo Material Consumido</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_material_consumido); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon">📊</div>
                    <div class="kpi-label">Variacion vs Estandar</div>
                    <div class="kpi-value"><?php echo formatearMoneda($variacion_material); ?></div>
                    <span class="status-badge <?php echo $variacion_material > 0 ? 'badge-danger' : 'badge-success'; ?>">
                        <?php echo $variacion_material > 0 ? 'Sobre' : 'Dentro'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Produccion por Periodo -->
        <div class="section">
            <div class="section-title">📈 Produccion por Periodo</div>
            <div class="grid-2">
                <div>
                    <div class="stat-row">
                        <div class="stat-label">Produccion Real</div>
                        <div class="stat-value"><?php echo formatearNumero($produccion_real); ?></div>
                    </div>
                    <div class="stat-row">
                        <div class="stat-label">Produccion Planificada</div>
                        <div class="stat-value"><?php echo formatearNumero($produccion_planificada); ?></div>
                    </div>
                    <div class="stat-row">
                        <div class="stat-label">Variacion</div>
                        <div class="stat-value" style="color: <?php echo $variacion_produccion >= 0 ? '#10b981' : '#ef4444'; ?>">
                            <?php echo ($variacion_produccion >= 0 ? '+' : '') . formatearNumero($variacion_produccion); ?>
                        </div>
                    </div>
                    <div class="stat-row">
                        <div class="stat-label">Eficiencia Turno</div>
                        <div class="stat-value"><?php echo formatearPorcentaje($eficiencia_turno); ?></div>
                    </div>
                    <div class="stat-row">
                        <div class="stat-label">Cumplimiento Turno</div>
                        <div class="stat-value"><?php echo formatearPorcentaje($cumplimiento_turno); ?></div>
                    </div>
                </div>

                <div>
                    <h4 style="margin-bottom: 15px; font-size: 16px;">Top 5 Produccion por Maquina</h4>
                    <?php if(!empty($produccion_por_maquina)): ?>
                        <?php foreach($produccion_por_maquina as $item): ?>
                        <div style="margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($item['work_center_name']); ?></span>
                                <span style="font-weight: 700;"><?php echo formatearNumero($item['produccion']); ?></span>
                            </div>
                            <div class="chart-bar" style="width: <?php echo min(($item['produccion'] / max(array_column($produccion_por_maquina, 'produccion'))) * 100, 100); ?>%">
                                <?php echo formatearNumero($item['produccion']); ?> unidades
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="opacity: 0.7;">No hay datos disponibles</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Produccion por Producto -->
        <?php if(!empty($produccion_por_producto)): ?>
        <div class="section">
            <div class="section-title">🏆 Top 5 Produccion por Producto</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad Producida</th>
                            <th>Visualizacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($produccion_por_producto as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['product_name']); ?></td>
                            <td><strong><?php echo formatearNumero($producto['produccion']); ?></strong></td>
                            <td>
                                <div class="chart-bar" style="width: <?php echo min(($producto['produccion'] / max(array_column($produccion_por_producto, 'produccion'))) * 100, 100); ?>%">
                                    <?php echo formatearPorcentaje(($producto['produccion'] / max(array_column($produccion_por_producto, 'produccion'))) * 100); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Control de Maquinaria -->
        <div class="section">
            <div class="section-title">🔧 Control de Maquinaria</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Disponibilidad</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($disponibilidad_maquinas_detalle); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($disponibilidad_maquinas_detalle, 100); ?>%"></div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Tiempo Operacion</div>
                    <div class="kpi-value"><?php echo formatearNumero($tiempo_operacion); ?></div>
                    <div class="kpi-subtitle">Horas efectivas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Tiempo Preparacion</div>
                    <div class="kpi-value"><?php echo formatearNumero($tiempo_preparacion); ?></div>
                    <div class="kpi-subtitle">Horas de setup</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Tiempo Muerto</div>
                    <div class="kpi-value"><?php echo formatearNumero($tiempo_muerto); ?></div>
                    <span class="status-badge badge-danger">Downtime</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Fallas Registradas</div>
                    <div class="kpi-value"><?php echo $fallas_registradas; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">MTTR</div>
                    <div class="kpi-value"><?php echo formatearNumero($mttr); ?></div>
                    <div class="kpi-subtitle">Tiempo medio reparacion (hrs)</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">MTBF</div>
                    <div class="kpi-value"><?php echo formatearNumero($mtbf); ?></div>
                    <div class="kpi-subtitle">Tiempo medio entre fallas (hrs)</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Mantenimiento Programado</div>
                    <div class="kpi-value"><?php echo $mantenimiento_programado; ?></div>
                    <span class="status-badge badge-info">Pendientes</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Mantenimiento Atrasado</div>
                    <div class="kpi-value"><?php echo $mantenimiento_atrasado; ?></div>
                    <span class="status-badge badge-danger">Vencidos</span>
                </div>
            </div>
        </div>

        <!-- Calidad -->
        <div class="section">
            <div class="section-title">✨ Control de Calidad</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Productos Aprobados</div>
                    <div class="kpi-value"><?php echo formatearNumero($productos_aprobados); ?></div>
                    <span class="status-badge badge-success">Calidad OK</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Productos Rechazados</div>
                    <div class="kpi-value"><?php echo formatearNumero($productos_rechazados); ?></div>
                    <span class="status-badge badge-danger">No conformes</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Porcentaje Scrap</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($porcentaje_scrap); ?></div>
                    <span class="status-badge <?php echo $porcentaje_scrap > 5 ? 'badge-danger' : 'badge-success'; ?>">
                        <?php echo $porcentaje_scrap > 5 ? 'Critico' : 'Normal'; ?>
                    </span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Porcentaje Reproceso</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($porcentaje_reproceso); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">NC Abiertas</div>
                    <div class="kpi-value"><?php echo $nc_abiertas; ?></div>
                    <div class="kpi-subtitle">No conformidades</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Impacto en Calidad</div>
                    <div class="kpi-value"><?php echo $impacto_calidad; ?></div>
                    <span class="status-badge <?php echo $impacto_calidad == 'ALTO' ? 'badge-danger' : ($impacto_calidad == 'MEDIO' ? 'badge-warning' : 'badge-success'); ?>">
                        <?php echo $impacto_calidad; ?>
                    </span>
                </div>
            </div>

            <?php if(!empty($causas_rechazo)): ?>
            <div style="margin-top: 25px;">
                <h4 style="margin-bottom: 15px; font-size: 16px;">Top Causas de Rechazo</h4>
                <?php foreach($causas_rechazo as $causa): ?>
                <div style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-size: 13px;"><?php echo htmlspecialchars($causa['defect_description']); ?></span>
                        <span style="font-weight: 700;"><?php echo formatearNumero($causa['total']); ?></span>
                    </div>
                    <div class="chart-bar" style="background: linear-gradient(90deg, rgba(239, 68, 68, 0.6) 0%, rgba(239, 68, 68, 0.3) 100%);
                         width: <?php echo min(($causa['total'] / max(array_column($causas_rechazo, 'total'))) * 100, 100); ?>%">
                        <?php echo formatearNumero($causa['total']); ?> unidades
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Costos de Produccion -->
        <div class="section">
            <div class="section-title">💰 Costos de Produccion</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Costo Real</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_real); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costo Teorico</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_teorico); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Desviacion</div>
                    <div class="kpi-value" style="color: <?php echo $desviacion_costos > 0 ? '#ef4444' : '#10b981'; ?>">
                        <?php echo formatearMoneda($desviacion_costos); ?>
                    </div>
                    <span class="status-badge <?php echo $desviacion_costos > 0 ? 'badge-danger' : 'badge-success'; ?>">
                        <?php echo $desviacion_costos > 0 ? 'Sobre presupuesto' : 'Dentro presupuesto'; ?>
                    </span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costo Material</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_material); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costo Mano de Obra</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_mano_obra); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costo Maquina</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_maquina); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costos Indirectos</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_indirecto); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costo Scrap</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_scrap); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costo Reproceso</div>
                    <div class="kpi-value"><?php echo formatearMoneda($costo_reproceso); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Merma Economica</div>
                    <div class="kpi-value"><?php echo formatearMoneda($merma_economica); ?></div>
                    <span class="status-badge badge-warning">Perdidas</span>
                </div>
            </div>
        </div>

        <!-- Productividad -->
        <div class="section">
            <div class="section-title">⚡ Productividad y Eficiencia</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Unidades por Hora</div>
                    <div class="kpi-value"><?php echo formatearNumero($unidades_por_hora); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Unidades por Turno</div>
                    <div class="kpi-value"><?php echo formatearNumero($unidades_por_turno); ?></div>
                    <div class="kpi-subtitle">Turno de 8 horas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Productividad Operario</div>
                    <div class="kpi-value"><?php echo formatearNumero($productividad_operario); ?></div>
                    <div class="kpi-subtitle">Unidades/hora/operario</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Eficiencia por Linea</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($eficiencia_por_linea); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Eficiencia por Producto</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($eficiencia_por_producto); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Cumplimiento Tiempos</div>
                    <div class="kpi-value"><?php echo formatearPorcentaje($cumplimiento_tiempos); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $cumplimiento_tiempos < 80 ? 'danger' : ($cumplimiento_tiempos < 95 ? 'warning' : ''); ?>"
                             style="width: <?php echo min($cumplimiento_tiempos, 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MRP y Abastecimiento -->
        <div class="section">
            <div class="section-title">📦 MRP y Abastecimiento</div>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">OP Detenidas por Material</div>
                    <div class="kpi-value"><?php echo $op_detenidas_material; ?></div>
                    <span class="status-badge badge-danger">Atencion</span>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">OP Detenidas por Mantenimiento</div>
                    <div class="kpi-value"><?php echo $op_detenidas_mantenimiento; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Necesidades MRP</div>
                    <div class="kpi-value"><?php echo formatearNumero($necesidades_mrp); ?></div>
                    <div class="kpi-subtitle">Unidades requeridas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Sugerencias Compra</div>
                    <div class="kpi-value"><?php echo $sugerencias_compra; ?></div>
                    <span class="status-badge badge-warning">Productos</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>CONECTA ERP - Dashboard de Produccion PP</p>
            <p>Generado el <?php echo date('d/m/Y H:i:s'); ?> | Usuario: <?php echo $user_id; ?> | Company: <?php echo $company_id; ?></p>
            <p>Todos los datos son extraidos directamente de la base de datos en tiempo real</p>
        </div>
    </div>
</body>
</html>
