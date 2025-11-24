<?php
/**
 * MODULO MAESTRO DE CLIENTES - CONECTA ERP
 * Sistema de Gestion Integral de Clientes
 * Desarrollado para integracion con SII Chile
 * Version 1.0 - Sistema Profesional Completo
 */

// Configuracion de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'conectae_conectaerpbd');
define('DB_USER', 'conectae_conectaerpuser');
define('DB_PASS', 'pt125824caraud');
define('DB_CHARSET', 'utf8mb4');

// Configuracion de sesion y errores
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Santiago');

// Clase de conexion a base de datos
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            die("Error de conexion: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}

// Clase principal de gestion de clientes
class ClienteManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Obtener todos los clientes con filtros
    public function obtenerClientes($filtros = []) {
        $sql = "SELECT c.*,
                       e.nombre as ejecutivo_nombre,
                       COUNT(DISTINCT p.id) as total_pedidos,
                       COALESCE(SUM(v.total), 0) as total_ventas
                FROM clientes c
                LEFT JOIN ejecutivos e ON c.ejecutivo_id = e.id
                LEFT JOIN pedidos p ON c.id = p.cliente_id
                LEFT JOIN ventas v ON c.id = v.cliente_id
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (c.nombre LIKE ? OR c.rut LIKE ? OR c.codigo LIKE ?)";
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        if (!empty($filtros['tipo_cliente'])) {
            $sql .= " AND c.tipo_cliente = ?";
            $params[] = $filtros['tipo_cliente'];
        }

        if (!empty($filtros['clasificacion'])) {
            $sql .= " AND c.clasificacion_abc = ?";
            $params[] = $filtros['clasificacion'];
        }

        if (!empty($filtros['zona'])) {
            $sql .= " AND c.zona_geografica = ?";
            $params[] = $filtros['zona'];
        }

        if (!empty($filtros['estado_credito'])) {
            $sql .= " AND c.estado_credito = ?";
            $params[] = $filtros['estado_credito'];
        }

        $sql .= " GROUP BY c.id ORDER BY c.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Obtener cliente por ID con toda la informacion
    public function obtenerClientePorId($id) {
        $sql = "SELECT * FROM clientes WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Crear nuevo cliente
    public function crearCliente($datos) {
        $sql = "INSERT INTO clientes (
            codigo, nombre, rut, tipo_cliente, clasificacion_abc, segmento_comercial,
            zona_geografica, ejecutivo_id, idioma, canal_venta,
            condicion_pago, forma_pago, descuento_comercial, lista_precios,
            plazo_entrega, politica_devolucion, acuerdos_comerciales, contratos_vigentes,
            limite_credito, riesgo_crediticio, garantias, documentacion_financiera,
            nivel_morosidad, estado_credito,
            tipo_contribuyente, exenciones, impuestos_asociados, certificados_tributarios,
            dte_habilitado, reglas_retencion,
            direccion_comercial, direccion_despacho, direccion_facturacion,
            latitud, longitud,
            notas, activo, fecha_creacion, usuario_creacion
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, NOW(), ?
        )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $datos['codigo'], $datos['nombre'], $datos['rut'],
            $datos['tipo_cliente'], $datos['clasificacion_abc'], $datos['segmento_comercial'],
            $datos['zona_geografica'], $datos['ejecutivo_id'], $datos['idioma'], $datos['canal_venta'],
            $datos['condicion_pago'], $datos['forma_pago'], $datos['descuento_comercial'], $datos['lista_precios'],
            $datos['plazo_entrega'], $datos['politica_devolucion'], $datos['acuerdos_comerciales'], $datos['contratos_vigentes'],
            $datos['limite_credito'], $datos['riesgo_crediticio'], $datos['garantias'], $datos['documentacion_financiera'],
            $datos['nivel_morosidad'], $datos['estado_credito'],
            $datos['tipo_contribuyente'], $datos['exenciones'], $datos['impuestos_asociados'], $datos['certificados_tributarios'],
            $datos['dte_habilitado'], $datos['reglas_retencion'],
            $datos['direccion_comercial'], $datos['direccion_despacho'], $datos['direccion_facturacion'],
            $datos['latitud'], $datos['longitud'],
            $datos['notas'], $datos['activo'], $datos['usuario_creacion']
        ]);
    }

    // Actualizar cliente
    public function actualizarCliente($id, $datos) {
        $sql = "UPDATE clientes SET
            nombre = ?, rut = ?, tipo_cliente = ?, clasificacion_abc = ?, segmento_comercial = ?,
            zona_geografica = ?, ejecutivo_id = ?, idioma = ?, canal_venta = ?,
            condicion_pago = ?, forma_pago = ?, descuento_comercial = ?, lista_precios = ?,
            plazo_entrega = ?, politica_devolucion = ?, acuerdos_comerciales = ?, contratos_vigentes = ?,
            limite_credito = ?, riesgo_crediticio = ?, garantias = ?, documentacion_financiera = ?,
            nivel_morosidad = ?, estado_credito = ?,
            tipo_contribuyente = ?, exenciones = ?, impuestos_asociados = ?, certificados_tributarios = ?,
            dte_habilitado = ?, reglas_retencion = ?,
            direccion_comercial = ?, direccion_despacho = ?, direccion_facturacion = ?,
            latitud = ?, longitud = ?,
            notas = ?, activo = ?, fecha_modificacion = NOW(), usuario_modificacion = ?
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $datos['nombre'], $datos['rut'], $datos['tipo_cliente'],
            $datos['clasificacion_abc'], $datos['segmento_comercial'],
            $datos['zona_geografica'], $datos['ejecutivo_id'], $datos['idioma'], $datos['canal_venta'],
            $datos['condicion_pago'], $datos['forma_pago'], $datos['descuento_comercial'], $datos['lista_precios'],
            $datos['plazo_entrega'], $datos['politica_devolucion'], $datos['acuerdos_comerciales'], $datos['contratos_vigentes'],
            $datos['limite_credito'], $datos['riesgo_crediticio'], $datos['garantias'], $datos['documentacion_financiera'],
            $datos['nivel_morosidad'], $datos['estado_credito'],
            $datos['tipo_contribuyente'], $datos['exenciones'], $datos['impuestos_asociados'], $datos['certificados_tributarios'],
            $datos['dte_habilitado'], $datos['reglas_retencion'],
            $datos['direccion_comercial'], $datos['direccion_despacho'], $datos['direccion_facturacion'],
            $datos['latitud'], $datos['longitud'],
            $datos['notas'], $datos['activo'], $datos['usuario_modificacion'],
            $id
        ]);
    }

    // Eliminar cliente
    public function eliminarCliente($id) {
        $sql = "DELETE FROM clientes WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    // Obtener sucursales de un cliente
    public function obtenerSucursales($cliente_id) {
        $sql = "SELECT * FROM clientes_sucursales WHERE cliente_id = ? ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll();
    }

    // Obtener contactos de un cliente
    public function obtenerContactos($cliente_id) {
        $sql = "SELECT * FROM clientes_contactos WHERE cliente_id = ? ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll();
    }

    // Obtener historial de pedidos
    public function obtenerHistorialPedidos($cliente_id) {
        $sql = "SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY fecha DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll();
    }

    // Obtener historial de ventas
    public function obtenerHistorialVentas($cliente_id) {
        $sql = "SELECT * FROM ventas WHERE cliente_id = ? ORDER BY fecha DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll();
    }

    // Obtener historial de pagos
    public function obtenerHistorialPagos($cliente_id) {
        $sql = "SELECT * FROM pagos WHERE cliente_id = ? ORDER BY fecha DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll();
    }

    // Obtener ejecutivos
    public function obtenerEjecutivos() {
        $sql = "SELECT id, nombre, email FROM ejecutivos WHERE activo = 1 ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Generar codigo de cliente automatico
    public function generarCodigoCliente() {
        $sql = "SELECT MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) as ultimo FROM clientes WHERE codigo LIKE 'CLI%'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch();
        $siguiente = isset($resultado['ultimo']) ? $resultado['ultimo'] + 1 : 1;
        return 'CLI' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
    }

    // Validar RUT chileno
    public function validarRut($rut) {
        $rut = preg_replace('/[^k0-9]/i', '', $rut);
        $dv = substr($rut, -1);
        $numero = substr($rut, 0, strlen($rut)-1);
        $i = 2;
        $suma = 0;
        foreach(array_reverse(str_split($numero)) as $v) {
            if($i==8) $i = 2;
            $suma += $v * $i;
            ++$i;
        }
        $dvr = 11 - ($suma % 11);
        if($dvr == 11) $dvr = 0;
        if($dvr == 10) $dvr = 'K';
        if((string)$dvr == strtoupper($dv)) return true;
        return false;
    }
}

// Procesamiento de acciones
$manager = new ClienteManager();
$mensaje = '';
$tipo_mensaje = '';
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'listar';
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $datos = [
                    'codigo' => $manager->generarCodigoCliente(),
                    'nombre' => $_POST['nombre'] ?? '',
                    'rut' => $_POST['rut'] ?? '',
                    'tipo_cliente' => $_POST['tipo_cliente'] ?? '',
                    'clasificacion_abc' => $_POST['clasificacion_abc'] ?? '',
                    'segmento_comercial' => $_POST['segmento_comercial'] ?? '',
                    'zona_geografica' => $_POST['zona_geografica'] ?? '',
                    'ejecutivo_id' => $_POST['ejecutivo_id'] ?? null,
                    'idioma' => $_POST['idioma'] ?? 'ES',
                    'canal_venta' => $_POST['canal_venta'] ?? '',
                    'condicion_pago' => $_POST['condicion_pago'] ?? '',
                    'forma_pago' => $_POST['forma_pago'] ?? '',
                    'descuento_comercial' => $_POST['descuento_comercial'] ?? 0,
                    'lista_precios' => $_POST['lista_precios'] ?? '',
                    'plazo_entrega' => $_POST['plazo_entrega'] ?? '',
                    'politica_devolucion' => $_POST['politica_devolucion'] ?? '',
                    'acuerdos_comerciales' => $_POST['acuerdos_comerciales'] ?? '',
                    'contratos_vigentes' => $_POST['contratos_vigentes'] ?? '',
                    'limite_credito' => $_POST['limite_credito'] ?? 0,
                    'riesgo_crediticio' => $_POST['riesgo_crediticio'] ?? '',
                    'garantias' => $_POST['garantias'] ?? '',
                    'documentacion_financiera' => $_POST['documentacion_financiera'] ?? '',
                    'nivel_morosidad' => $_POST['nivel_morosidad'] ?? 0,
                    'estado_credito' => $_POST['estado_credito'] ?? 'NORMAL',
                    'tipo_contribuyente' => $_POST['tipo_contribuyente'] ?? '',
                    'exenciones' => $_POST['exenciones'] ?? '',
                    'impuestos_asociados' => $_POST['impuestos_asociados'] ?? '',
                    'certificados_tributarios' => $_POST['certificados_tributarios'] ?? '',
                    'dte_habilitado' => isset($_POST['dte_habilitado']) ? 1 : 0,
                    'reglas_retencion' => $_POST['reglas_retencion'] ?? '',
                    'direccion_comercial' => $_POST['direccion_comercial'] ?? '',
                    'direccion_despacho' => $_POST['direccion_despacho'] ?? '',
                    'direccion_facturacion' => $_POST['direccion_facturacion'] ?? '',
                    'latitud' => $_POST['latitud'] ?? null,
                    'longitud' => $_POST['longitud'] ?? null,
                    'notas' => $_POST['notas'] ?? '',
                    'activo' => isset($_POST['activo']) ? 1 : 0,
                    'usuario_creacion' => 'ADMIN'
                ];

                if ($manager->crearCliente($datos)) {
                    $mensaje = 'Cliente creado exitosamente';
                    $tipo_mensaje = 'success';
                    $accion = 'listar';
                } else {
                    $mensaje = 'Error al crear cliente';
                    $tipo_mensaje = 'error';
                }
                break;

            case 'actualizar':
                $datos = [
                    'nombre' => $_POST['nombre'] ?? '',
                    'rut' => $_POST['rut'] ?? '',
                    'tipo_cliente' => $_POST['tipo_cliente'] ?? '',
                    'clasificacion_abc' => $_POST['clasificacion_abc'] ?? '',
                    'segmento_comercial' => $_POST['segmento_comercial'] ?? '',
                    'zona_geografica' => $_POST['zona_geografica'] ?? '',
                    'ejecutivo_id' => $_POST['ejecutivo_id'] ?? null,
                    'idioma' => $_POST['idioma'] ?? 'ES',
                    'canal_venta' => $_POST['canal_venta'] ?? '',
                    'condicion_pago' => $_POST['condicion_pago'] ?? '',
                    'forma_pago' => $_POST['forma_pago'] ?? '',
                    'descuento_comercial' => $_POST['descuento_comercial'] ?? 0,
                    'lista_precios' => $_POST['lista_precios'] ?? '',
                    'plazo_entrega' => $_POST['plazo_entrega'] ?? '',
                    'politica_devolucion' => $_POST['politica_devolucion'] ?? '',
                    'acuerdos_comerciales' => $_POST['acuerdos_comerciales'] ?? '',
                    'contratos_vigentes' => $_POST['contratos_vigentes'] ?? '',
                    'limite_credito' => $_POST['limite_credito'] ?? 0,
                    'riesgo_crediticio' => $_POST['riesgo_crediticio'] ?? '',
                    'garantias' => $_POST['garantias'] ?? '',
                    'documentacion_financiera' => $_POST['documentacion_financiera'] ?? '',
                    'nivel_morosidad' => $_POST['nivel_morosidad'] ?? 0,
                    'estado_credito' => $_POST['estado_credito'] ?? 'NORMAL',
                    'tipo_contribuyente' => $_POST['tipo_contribuyente'] ?? '',
                    'exenciones' => $_POST['exenciones'] ?? '',
                    'impuestos_asociados' => $_POST['impuestos_asociados'] ?? '',
                    'certificados_tributarios' => $_POST['certificados_tributarios'] ?? '',
                    'dte_habilitado' => isset($_POST['dte_habilitado']) ? 1 : 0,
                    'reglas_retencion' => $_POST['reglas_retencion'] ?? '',
                    'direccion_comercial' => $_POST['direccion_comercial'] ?? '',
                    'direccion_despacho' => $_POST['direccion_despacho'] ?? '',
                    'direccion_facturacion' => $_POST['direccion_facturacion'] ?? '',
                    'latitud' => $_POST['latitud'] ?? null,
                    'longitud' => $_POST['longitud'] ?? null,
                    'notas' => $_POST['notas'] ?? '',
                    'activo' => isset($_POST['activo']) ? 1 : 0,
                    'usuario_modificacion' => 'ADMIN'
                ];

                if ($manager->actualizarCliente($_POST['id'], $datos)) {
                    $mensaje = 'Cliente actualizado exitosamente';
                    $tipo_mensaje = 'success';
                    $accion = 'listar';
                } else {
                    $mensaje = 'Error al actualizar cliente';
                    $tipo_mensaje = 'error';
                }
                break;

            case 'eliminar':
                if ($manager->eliminarCliente($_POST['id'])) {
                    $mensaje = 'Cliente eliminado exitosamente';
                    $tipo_mensaje = 'success';
                    $accion = 'listar';
                } else {
                    $mensaje = 'Error al eliminar cliente';
                    $tipo_mensaje = 'error';
                }
                break;
        }
    }
}

// Obtener datos segun accion
$clientes = [];
$cliente = null;
$ejecutivos = $manager->obtenerEjecutivos();

if ($accion === 'listar') {
    $filtros = [
        'busqueda' => $_GET['busqueda'] ?? '',
        'tipo_cliente' => $_GET['tipo_cliente'] ?? '',
        'clasificacion' => $_GET['clasificacion'] ?? '',
        'zona' => $_GET['zona'] ?? '',
        'estado_credito' => $_GET['estado_credito'] ?? ''
    ];
    $clientes = $manager->obtenerClientes($filtros);
} elseif (($accion === 'editar' || $accion === 'ver') && $cliente_id > 0) {
    $cliente = $manager->obtenerClientePorId($cliente_id);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maestro de Clientes - CONECTA ERP</title>
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
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        /* Header estilo SAP */
        .header {
            background: linear-gradient(to right, #003366, #0066cc);
            color: white;
            padding: 25px 30px;
            border-bottom: 4px solid #ffa500;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Barra de herramientas */
        .toolbar {
            background: #f5f5f5;
            padding: 15px 30px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #0066cc;
            color: white;
        }

        .btn-primary:hover {
            background: #0052a3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        /* Contenido principal */
        .content {
            padding: 30px;
        }

        /* Mensajes */
        .mensaje {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Filtros */
        .filtros {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0,102,204,0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        /* Tabla estilo SAP */
        .table-container {
            overflow-x: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
        }

        th {
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #495057;
        }

        tbody tr {
            transition: background-color 0.2s;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        tbody tr:nth-child(even):hover {
            background-color: #f0f0f0;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-primary {
            background: #cce5ff;
            color: #004085;
        }

        /* Formulario en grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .form-section-title {
            font-size: 16px;
            font-weight: 700;
            color: #003366;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0066cc;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 25px;
        }

        .tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: none;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #6c757d;
        }

        .tab:hover {
            background: #e9ecef;
        }

        .tab.active {
            background: white;
            color: #003366;
            border-bottom: 3px solid #0066cc;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Vista detalle */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #0066cc;
        }

        .detail-label {
            font-size: 12px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 15px;
            color: #212529;
            font-weight: 500;
        }

        /* Checkbox personalizado */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        /* Acciones de tabla */
        .table-actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .filtros-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Impresion */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .toolbar, .btn, .table-actions {
                display: none;
            }

            .container {
                box-shadow: none;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content > * {
            animation: fadeIn 0.5s ease-out;
        }

        /* Estadisticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MAESTRO DE CLIENTES</h1>
            <div class="subtitle">Sistema Integral de Gestion de Clientes - Conecta ERP</div>
        </div>

        <div class="toolbar">
            <?php if ($accion === 'listar'): ?>
                <a href="?accion=crear" class="btn btn-primary">Nuevo Cliente</a>
                <button onclick="window.print()" class="btn btn-info">Imprimir Listado</button>
                <button onclick="exportarExcel()" class="btn btn-success">Exportar Excel</button>
            <?php elseif ($accion === 'crear' || $accion === 'editar'): ?>
                <a href="?accion=listar" class="btn btn-secondary">Volver al Listado</a>
                <button type="submit" form="formCliente" class="btn btn-success">Guardar Cliente</button>
            <?php elseif ($accion === 'ver'): ?>
                <a href="?accion=listar" class="btn btn-secondary">Volver al Listado</a>
                <a href="?accion=editar&id=<?php echo $cliente_id; ?>" class="btn btn-warning">Editar Cliente</a>
                <button onclick="window.print()" class="btn btn-info">Imprimir Ficha</button>
            <?php endif; ?>
        </div>

        <div class="content">
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <?php if ($accion === 'listar'): ?>
                <!-- VISTA LISTADO -->
                <div class="filtros">
                    <form method="GET" action="">
                        <input type="hidden" name="accion" value="listar">
                        <div class="filtros-grid">
                            <div class="form-group">
                                <label>Buscar</label>
                                <input type="text" name="busqueda" class="form-control"
                                       value="<?php echo htmlspecialchars($filtros['busqueda']); ?>"
                                       placeholder="Nombre, RUT o Codigo">
                            </div>
                            <div class="form-group">
                                <label>Tipo Cliente</label>
                                <select name="tipo_cliente" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="MINORISTA" <?php echo $filtros['tipo_cliente'] === 'MINORISTA' ? 'selected' : ''; ?>>Minorista</option>
                                    <option value="MAYORISTA" <?php echo $filtros['tipo_cliente'] === 'MAYORISTA' ? 'selected' : ''; ?>>Mayorista</option>
                                    <option value="DISTRIBUIDOR" <?php echo $filtros['tipo_cliente'] === 'DISTRIBUIDOR' ? 'selected' : ''; ?>>Distribuidor</option>
                                    <option value="EXPORTACION" <?php echo $filtros['tipo_cliente'] === 'EXPORTACION' ? 'selected' : ''; ?>>Exportacion</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Clasificacion ABC</label>
                                <select name="clasificacion" class="form-control">
                                    <option value="">Todas</option>
                                    <option value="A" <?php echo $filtros['clasificacion'] === 'A' ? 'selected' : ''; ?>>A - Premium</option>
                                    <option value="B" <?php echo $filtros['clasificacion'] === 'B' ? 'selected' : ''; ?>>B - Estandar</option>
                                    <option value="C" <?php echo $filtros['clasificacion'] === 'C' ? 'selected' : ''; ?>>C - Basico</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Estado Credito</label>
                                <select name="estado_credito" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="NORMAL" <?php echo $filtros['estado_credito'] === 'NORMAL' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="REVISION" <?php echo $filtros['estado_credito'] === 'REVISION' ? 'selected' : ''; ?>>En Revision</option>
                                    <option value="SUSPENDIDO" <?php echo $filtros['estado_credito'] === 'SUSPENDIDO' ? 'selected' : ''; ?>>Suspendido</option>
                                    <option value="BLOQUEADO" <?php echo $filtros['estado_credito'] === 'BLOQUEADO' ? 'selected' : ''; ?>>Bloqueado</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                        <a href="?accion=listar" class="btn btn-secondary">Limpiar Filtros</a>
                    </form>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Clientes</div>
                        <div class="stat-value"><?php echo count($clientes); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Clientes Activos</div>
                        <div class="stat-value"><?php echo count(array_filter($clientes, function($c) { return $c['activo'] == 1; })); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Clasificacion A</div>
                        <div class="stat-value"><?php echo count(array_filter($clientes, function($c) { return $c['clasificacion_abc'] === 'A'; })); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Con Credito</div>
                        <div class="stat-value"><?php echo count(array_filter($clientes, function($c) { return $c['limite_credito'] > 0; })); ?></div>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre / Razon Social</th>
                                <th>RUT</th>
                                <th>Tipo</th>
                                <th>Clasificacion</th>
                                <th>Zona</th>
                                <th>Ejecutivo</th>
                                <th>Limite Credito</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['codigo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($c['rut']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($c['tipo_cliente']); ?></span></td>
                                <td>
                                    <?php
                                    $clase_abc = 'badge-primary';
                                    if ($c['clasificacion_abc'] === 'A') $clase_abc = 'badge-success';
                                    elseif ($c['clasificacion_abc'] === 'B') $clase_abc = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $clase_abc; ?>"><?php echo htmlspecialchars($c['clasificacion_abc']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($c['zona_geografica']); ?></td>
                                <td><?php echo htmlspecialchars($c['ejecutivo_nombre'] ?? 'Sin asignar'); ?></td>
                                <td>$<?php echo number_format($c['limite_credito'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php
                                    $clase_estado = 'badge-success';
                                    if ($c['estado_credito'] === 'REVISION') $clase_estado = 'badge-warning';
                                    elseif ($c['estado_credito'] === 'SUSPENDIDO' || $c['estado_credito'] === 'BLOQUEADO') $clase_estado = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $clase_estado; ?>"><?php echo $c['activo'] ? 'ACTIVO' : 'INACTIVO'; ?></span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="?accion=ver&id=<?php echo $c['id']; ?>" class="btn btn-info btn-sm">Ver</a>
                                        <a href="?accion=editar&id=<?php echo $c['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirma eliminacion de cliente?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($accion === 'crear' || $accion === 'editar'): ?>
                <!-- VISTA FORMULARIO -->
                <form id="formCliente" method="POST">
                    <input type="hidden" name="accion" value="<?php echo $accion === 'crear' ? 'crear' : 'actualizar'; ?>">
                    <?php if ($accion === 'editar'): ?>
                        <input type="hidden" name="id" value="<?php echo $cliente_id; ?>">
                    <?php endif; ?>

                    <div class="tabs">
                        <button type="button" class="tab active" onclick="cambiarTab(event, 'tab1')">Datos Generales</button>
                        <button type="button" class="tab" onclick="cambiarTab(event, 'tab2')">Datos Comerciales</button>
                        <button type="button" class="tab" onclick="cambiarTab(event, 'tab3')">Datos de Credito</button>
                        <button type="button" class="tab" onclick="cambiarTab(event, 'tab4')">Datos Fiscales</button>
                        <button type="button" class="tab" onclick="cambiarTab(event, 'tab5')">Direcciones</button>
                    </div>

                    <!-- TAB 1: DATOS GENERALES -->
                    <div id="tab1" class="tab-content active">
                        <div class="form-section">
                            <div class="form-section-title">2.1 DATOS GENERALES</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nombre / Razon Social *</label>
                                    <input type="text" name="nombre" class="form-control" required
                                           value="<?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label>RUT / NIF *</label>
                                    <input type="text" name="rut" class="form-control" required
                                           value="<?php echo htmlspecialchars($cliente['rut'] ?? ''); ?>"
                                           placeholder="12345678-9">
                                </div>

                                <div class="form-group">
                                    <label>Tipo de Cliente *</label>
                                    <select name="tipo_cliente" class="form-control" required>
                                        <option value="">Seleccione</option>
                                        <option value="MINORISTA" <?php echo ($cliente['tipo_cliente'] ?? '') === 'MINORISTA' ? 'selected' : ''; ?>>Minorista</option>
                                        <option value="MAYORISTA" <?php echo ($cliente['tipo_cliente'] ?? '') === 'MAYORISTA' ? 'selected' : ''; ?>>Mayorista</option>
                                        <option value="DISTRIBUIDOR" <?php echo ($cliente['tipo_cliente'] ?? '') === 'DISTRIBUIDOR' ? 'selected' : ''; ?>>Distribuidor</option>
                                        <option value="EXPORTACION" <?php echo ($cliente['tipo_cliente'] ?? '') === 'EXPORTACION' ? 'selected' : ''; ?>>Exportacion</option>
                                        <option value="GOBIERNO" <?php echo ($cliente['tipo_cliente'] ?? '') === 'GOBIERNO' ? 'selected' : ''; ?>>Gobierno</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Clasificacion ABC *</label>
                                    <select name="clasificacion_abc" class="form-control" required>
                                        <option value="">Seleccione</option>
                                        <option value="A" <?php echo ($cliente['clasificacion_abc'] ?? '') === 'A' ? 'selected' : ''; ?>>A - Premium (Alta Importancia)</option>
                                        <option value="B" <?php echo ($cliente['clasificacion_abc'] ?? '') === 'B' ? 'selected' : ''; ?>>B - Estandar (Importancia Media)</option>
                                        <option value="C" <?php echo ($cliente['clasificacion_abc'] ?? '') === 'C' ? 'selected' : ''; ?>>C - Basico (Baja Importancia)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Segmento Comercial</label>
                                    <select name="segmento_comercial" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="RETAIL" <?php echo ($cliente['segmento_comercial'] ?? '') === 'RETAIL' ? 'selected' : ''; ?>>Retail</option>
                                        <option value="CONSTRUCCION" <?php echo ($cliente['segmento_comercial'] ?? '') === 'CONSTRUCCION' ? 'selected' : ''; ?>>Construccion</option>
                                        <option value="MANUFACTURA" <?php echo ($cliente['segmento_comercial'] ?? '') === 'MANUFACTURA' ? 'selected' : ''; ?>>Manufactura</option>
                                        <option value="SERVICIOS" <?php echo ($cliente['segmento_comercial'] ?? '') === 'SERVICIOS' ? 'selected' : ''; ?>>Servicios</option>
                                        <option value="TECNOLOGIA" <?php echo ($cliente['segmento_comercial'] ?? '') === 'TECNOLOGIA' ? 'selected' : ''; ?>>Tecnologia</option>
                                        <option value="SALUD" <?php echo ($cliente['segmento_comercial'] ?? '') === 'SALUD' ? 'selected' : ''; ?>>Salud</option>
                                        <option value="EDUCACION" <?php echo ($cliente['segmento_comercial'] ?? '') === 'EDUCACION' ? 'selected' : ''; ?>>Educacion</option>
                                        <option value="AGRICOLA" <?php echo ($cliente['segmento_comercial'] ?? '') === 'AGRICOLA' ? 'selected' : ''; ?>>Agricola</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Zona Geografica *</label>
                                    <select name="zona_geografica" class="form-control" required>
                                        <option value="">Seleccione</option>
                                        <option value="REGION METROPOLITANA" <?php echo ($cliente['zona_geografica'] ?? '') === 'REGION METROPOLITANA' ? 'selected' : ''; ?>>Region Metropolitana</option>
                                        <option value="VALPARAISO" <?php echo ($cliente['zona_geografica'] ?? '') === 'VALPARAISO' ? 'selected' : ''; ?>>Valparaiso</option>
                                        <option value="BIOBIO" <?php echo ($cliente['zona_geografica'] ?? '') === 'BIOBIO' ? 'selected' : ''; ?>>Bio Bio</option>
                                        <option value="ARAUCANIA" <?php echo ($cliente['zona_geografica'] ?? '') === 'ARAUCANIA' ? 'selected' : ''; ?>>Araucania</option>
                                        <option value="LOS LAGOS" <?php echo ($cliente['zona_geografica'] ?? '') === 'LOS LAGOS' ? 'selected' : ''; ?>>Los Lagos</option>
                                        <option value="MAULE" <?php echo ($cliente['zona_geografica'] ?? '') === 'MAULE' ? 'selected' : ''; ?>>Maule</option>
                                        <option value="ANTOFAGASTA" <?php echo ($cliente['zona_geografica'] ?? '') === 'ANTOFAGASTA' ? 'selected' : ''; ?>>Antofagasta</option>
                                        <option value="COQUIMBO" <?php echo ($cliente['zona_geografica'] ?? '') === 'COQUIMBO' ? 'selected' : ''; ?>>Coquimbo</option>
                                        <option value="OHIGGINS" <?php echo ($cliente['zona_geografica'] ?? '') === 'OHIGGINS' ? 'selected' : ''; ?>>O'Higgins</option>
                                        <option value="OTROS" <?php echo ($cliente['zona_geografica'] ?? '') === 'OTROS' ? 'selected' : ''; ?>>Otras Regiones</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Ejecutivo Asignado</label>
                                    <select name="ejecutivo_id" class="form-control">
                                        <option value="">Sin asignar</option>
                                        <?php foreach ($ejecutivos as $ej): ?>
                                            <option value="<?php echo $ej['id']; ?>"
                                                <?php echo ($cliente['ejecutivo_id'] ?? '') == $ej['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ej['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Idioma</label>
                                    <select name="idioma" class="form-control">
                                        <option value="ES" <?php echo ($cliente['idioma'] ?? 'ES') === 'ES' ? 'selected' : ''; ?>>Espanol</option>
                                        <option value="EN" <?php echo ($cliente['idioma'] ?? '') === 'EN' ? 'selected' : ''; ?>>Ingles</option>
                                        <option value="PT" <?php echo ($cliente['idioma'] ?? '') === 'PT' ? 'selected' : ''; ?>>Portugues</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Canal de Venta</label>
                                    <select name="canal_venta" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="DIRECTO" <?php echo ($cliente['canal_venta'] ?? '') === 'DIRECTO' ? 'selected' : ''; ?>>Venta Directa</option>
                                        <option value="DISTRIBUIDOR" <?php echo ($cliente['canal_venta'] ?? '') === 'DISTRIBUIDOR' ? 'selected' : ''; ?>>Distribuidor</option>
                                        <option value="ECOMMERCE" <?php echo ($cliente['canal_venta'] ?? '') === 'ECOMMERCE' ? 'selected' : ''; ?>>E-commerce</option>
                                        <option value="TELEFONO" <?php echo ($cliente['canal_venta'] ?? '') === 'TELEFONO' ? 'selected' : ''; ?>>Telefono</option>
                                        <option value="LICITACION" <?php echo ($cliente['canal_venta'] ?? '') === 'LICITACION' ? 'selected' : ''; ?>>Licitacion</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DATOS COMERCIALES -->
                    <div id="tab2" class="tab-content">
                        <div class="form-section">
                            <div class="form-section-title">2.2 DATOS COMERCIALES</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Condicion de Pago</label>
                                    <select name="condicion_pago" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="CONTADO" <?php echo ($cliente['condicion_pago'] ?? '') === 'CONTADO' ? 'selected' : ''; ?>>Contado</option>
                                        <option value="30_DIAS" <?php echo ($cliente['condicion_pago'] ?? '') === '30_DIAS' ? 'selected' : ''; ?>>30 Dias</option>
                                        <option value="60_DIAS" <?php echo ($cliente['condicion_pago'] ?? '') === '60_DIAS' ? 'selected' : ''; ?>>60 Dias</option>
                                        <option value="90_DIAS" <?php echo ($cliente['condicion_pago'] ?? '') === '90_DIAS' ? 'selected' : ''; ?>>90 Dias</option>
                                        <option value="120_DIAS" <?php echo ($cliente['condicion_pago'] ?? '') === '120_DIAS' ? 'selected' : ''; ?>>120 Dias</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Forma de Pago</label>
                                    <select name="forma_pago" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="EFECTIVO" <?php echo ($cliente['forma_pago'] ?? '') === 'EFECTIVO' ? 'selected' : ''; ?>>Efectivo</option>
                                        <option value="TRANSFERENCIA" <?php echo ($cliente['forma_pago'] ?? '') === 'TRANSFERENCIA' ? 'selected' : ''; ?>>Transferencia Bancaria</option>
                                        <option value="CHEQUE" <?php echo ($cliente['forma_pago'] ?? '') === 'CHEQUE' ? 'selected' : ''; ?>>Cheque</option>
                                        <option value="TARJETA" <?php echo ($cliente['forma_pago'] ?? '') === 'TARJETA' ? 'selected' : ''; ?>>Tarjeta de Credito</option>
                                        <option value="LETRA" <?php echo ($cliente['forma_pago'] ?? '') === 'LETRA' ? 'selected' : ''; ?>>Letra de Cambio</option>
                                        <option value="PAGARE" <?php echo ($cliente['forma_pago'] ?? '') === 'PAGARE' ? 'selected' : ''; ?>>Pagare</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Descuento Comercial (%)</label>
                                    <input type="number" step="0.01" name="descuento_comercial" class="form-control"
                                           value="<?php echo htmlspecialchars($cliente['descuento_comercial'] ?? '0'); ?>"
                                           min="0" max="100">
                                </div>

                                <div class="form-group">
                                    <label>Lista de Precios Predeterminada</label>
                                    <select name="lista_precios" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="GENERAL" <?php echo ($cliente['lista_precios'] ?? '') === 'GENERAL' ? 'selected' : ''; ?>>Lista General</option>
                                        <option value="MAYORISTA" <?php echo ($cliente['lista_precios'] ?? '') === 'MAYORISTA' ? 'selected' : ''; ?>>Lista Mayorista</option>
                                        <option value="MINORISTA" <?php echo ($cliente['lista_precios'] ?? '') === 'MINORISTA' ? 'selected' : ''; ?>>Lista Minorista</option>
                                        <option value="ESPECIAL" <?php echo ($cliente['lista_precios'] ?? '') === 'ESPECIAL' ? 'selected' : ''; ?>>Lista Especial</option>
                                        <option value="EXPORTACION" <?php echo ($cliente['lista_precios'] ?? '') === 'EXPORTACION' ? 'selected' : ''; ?>>Lista Exportacion</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Plazo de Entrega</label>
                                    <select name="plazo_entrega" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="24_HORAS" <?php echo ($cliente['plazo_entrega'] ?? '') === '24_HORAS' ? 'selected' : ''; ?>>24 Horas</option>
                                        <option value="48_HORAS" <?php echo ($cliente['plazo_entrega'] ?? '') === '48_HORAS' ? 'selected' : ''; ?>>48 Horas</option>
                                        <option value="72_HORAS" <?php echo ($cliente['plazo_entrega'] ?? '') === '72_HORAS' ? 'selected' : ''; ?>>72 Horas</option>
                                        <option value="5_DIAS" <?php echo ($cliente['plazo_entrega'] ?? '') === '5_DIAS' ? 'selected' : ''; ?>>5 Dias Habiles</option>
                                        <option value="7_DIAS" <?php echo ($cliente['plazo_entrega'] ?? '') === '7_DIAS' ? 'selected' : ''; ?>>7 Dias Habiles</option>
                                        <option value="15_DIAS" <?php echo ($cliente['plazo_entrega'] ?? '') === '15_DIAS' ? 'selected' : ''; ?>>15 Dias Habiles</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Politica de Devoluciones</label>
                                    <select name="politica_devolucion" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="ESTANDAR" <?php echo ($cliente['politica_devolucion'] ?? '') === 'ESTANDAR' ? 'selected' : ''; ?>>Estandar (30 dias)</option>
                                        <option value="EXTENDIDA" <?php echo ($cliente['politica_devolucion'] ?? '') === 'EXTENDIDA' ? 'selected' : ''; ?>>Extendida (60 dias)</option>
                                        <option value="SIN_DEVOLUCION" <?php echo ($cliente['politica_devolucion'] ?? '') === 'SIN_DEVOLUCION' ? 'selected' : ''; ?>>Sin Devolucion</option>
                                        <option value="ESPECIAL" <?php echo ($cliente['politica_devolucion'] ?? '') === 'ESPECIAL' ? 'selected' : ''; ?>>Politica Especial</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Acuerdos Comerciales</label>
                                <textarea name="acuerdos_comerciales" class="form-control" rows="4"><?php echo htmlspecialchars($cliente['acuerdos_comerciales'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Contratos Vigentes</label>
                                <textarea name="contratos_vigentes" class="form-control" rows="4"><?php echo htmlspecialchars($cliente['contratos_vigentes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DATOS DE CREDITO -->
                    <div id="tab3" class="tab-content">
                        <div class="form-section">
                            <div class="form-section-title">2.3 DATOS DE CREDITO Y RIESGO</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Limite de Credito ($)</label>
                                    <input type="number" step="0.01" name="limite_credito" class="form-control"
                                           value="<?php echo htmlspecialchars($cliente['limite_credito'] ?? '0'); ?>"
                                           min="0">
                                </div>

                                <div class="form-group">
                                    <label>Riesgo Crediticio (Score)</label>
                                    <select name="riesgo_crediticio" class="form-control">
                                        <option value="">No evaluado</option>
                                        <option value="BAJO" <?php echo ($cliente['riesgo_crediticio'] ?? '') === 'BAJO' ? 'selected' : ''; ?>>Bajo (AAA)</option>
                                        <option value="MEDIO_BAJO" <?php echo ($cliente['riesgo_crediticio'] ?? '') === 'MEDIO_BAJO' ? 'selected' : ''; ?>>Medio Bajo (AA)</option>
                                        <option value="MEDIO" <?php echo ($cliente['riesgo_crediticio'] ?? '') === 'MEDIO' ? 'selected' : ''; ?>>Medio (A)</option>
                                        <option value="MEDIO_ALTO" <?php echo ($cliente['riesgo_crediticio'] ?? '') === 'MEDIO_ALTO' ? 'selected' : ''; ?>>Medio Alto (BBB)</option>
                                        <option value="ALTO" <?php echo ($cliente['riesgo_crediticio'] ?? '') === 'ALTO' ? 'selected' : ''; ?>>Alto (BB)</option>
                                        <option value="MUY_ALTO" <?php echo ($cliente['riesgo_crediticio'] ?? '') === 'MUY_ALTO' ? 'selected' : ''; ?>>Muy Alto (B)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Nivel de Morosidad (%)</label>
                                    <input type="number" step="0.01" name="nivel_morosidad" class="form-control"
                                           value="<?php echo htmlspecialchars($cliente['nivel_morosidad'] ?? '0'); ?>"
                                           min="0" max="100">
                                </div>

                                <div class="form-group">
                                    <label>Estado de Credito</label>
                                    <select name="estado_credito" class="form-control">
                                        <option value="NORMAL" <?php echo ($cliente['estado_credito'] ?? 'NORMAL') === 'NORMAL' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="REVISION" <?php echo ($cliente['estado_credito'] ?? '') === 'REVISION' ? 'selected' : ''; ?>>En Revision</option>
                                        <option value="SUSPENDIDO" <?php echo ($cliente['estado_credito'] ?? '') === 'SUSPENDIDO' ? 'selected' : ''; ?>>Suspendido</option>
                                        <option value="BLOQUEADO" <?php echo ($cliente['estado_credito'] ?? '') === 'BLOQUEADO' ? 'selected' : ''; ?>>Bloqueado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Garantias</label>
                                <textarea name="garantias" class="form-control" rows="4"><?php echo htmlspecialchars($cliente['garantias'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Documentacion Financiera</label>
                                <textarea name="documentacion_financiera" class="form-control" rows="4"><?php echo htmlspecialchars($cliente['documentacion_financiera'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: DATOS FISCALES -->
                    <div id="tab4" class="tab-content">
                        <div class="form-section">
                            <div class="form-section-title">2.4 DATOS FISCALES Y TRIBUTARIOS</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Tipo de Contribuyente</label>
                                    <select name="tipo_contribuyente" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="PRIMERA_CATEGORIA" <?php echo ($cliente['tipo_contribuyente'] ?? '') === 'PRIMERA_CATEGORIA' ? 'selected' : ''; ?>>Primera Categoria</option>
                                        <option value="SEGUNDA_CATEGORIA" <?php echo ($cliente['tipo_contribuyente'] ?? '') === 'SEGUNDA_CATEGORIA' ? 'selected' : ''; ?>>Segunda Categoria</option>
                                        <option value="REGIMEN_SIMPLIFICADO" <?php echo ($cliente['tipo_contribuyente'] ?? '') === 'REGIMEN_SIMPLIFICADO' ? 'selected' : ''; ?>>Regimen Simplificado (14 ter)</option>
                                        <option value="EXENTO" <?php echo ($cliente['tipo_contribuyente'] ?? '') === 'EXENTO' ? 'selected' : ''; ?>>Exento</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Impuestos Asociados</label>
                                    <select name="impuestos_asociados" class="form-control">
                                        <option value="">Seleccione</option>
                                        <option value="IVA_19" <?php echo ($cliente['impuestos_asociados'] ?? '') === 'IVA_19' ? 'selected' : ''; ?>>IVA 19%</option>
                                        <option value="IVA_EXENTO" <?php echo ($cliente['impuestos_asociados'] ?? '') === 'IVA_EXENTO' ? 'selected' : ''; ?>>Exento de IVA</option>
                                        <option value="IVA_ZONA_FRANCA" <?php echo ($cliente['impuestos_asociados'] ?? '') === 'IVA_ZONA_FRANCA' ? 'selected' : ''; ?>>Zona Franca</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Reglas de Retencion</label>
                                    <select name="reglas_retencion" class="form-control">
                                        <option value="">Sin retencion</option>
                                        <option value="RETENCION_10" <?php echo ($cliente['reglas_retencion'] ?? '') === 'RETENCION_10' ? 'selected' : ''; ?>>Retencion 10%</option>
                                        <option value="RETENCION_20" <?php echo ($cliente['reglas_retencion'] ?? '') === 'RETENCION_20' ? 'selected' : ''; ?>>Retencion 20%</option>
                                        <option value="HONORARIOS" <?php echo ($cliente['reglas_retencion'] ?? '') === 'HONORARIOS' ? 'selected' : ''; ?>>Retencion Honorarios</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="dte_habilitado" id="dte_habilitado"
                                               <?php echo ($cliente['dte_habilitado'] ?? 0) ? 'checked' : ''; ?>>
                                        <label for="dte_habilitado">DTE / Facturacion Electronica Habilitada</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Exenciones Tributarias</label>
                                <textarea name="exenciones" class="form-control" rows="4"><?php echo htmlspecialchars($cliente['exenciones'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Certificados Tributarios</label>
                                <textarea name="certificados_tributarios" class="form-control" rows="4"><?php echo htmlspecialchars($cliente['certificados_tributarios'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: DIRECCIONES -->
                    <div id="tab5" class="tab-content">
                        <div class="form-section">
                            <div class="form-section-title">2.5 DIRECCIONES Y UBICACION</div>

                            <div class="form-group">
                                <label>Direccion Comercial</label>
                                <textarea name="direccion_comercial" class="form-control" rows="2"><?php echo htmlspecialchars($cliente['direccion_comercial'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Direccion de Despacho</label>
                                <textarea name="direccion_despacho" class="form-control" rows="2"><?php echo htmlspecialchars($cliente['direccion_despacho'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Direccion de Facturacion</label>
                                <textarea name="direccion_facturacion" class="form-control" rows="2"><?php echo htmlspecialchars($cliente['direccion_facturacion'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Latitud</label>
                                    <input type="text" name="latitud" class="form-control"
                                           value="<?php echo htmlspecialchars($cliente['latitud'] ?? ''); ?>"
                                           placeholder="-33.4489">
                                </div>

                                <div class="form-group">
                                    <label>Longitud</label>
                                    <input type="text" name="longitud" class="form-control"
                                           value="<?php echo htmlspecialchars($cliente['longitud'] ?? ''); ?>"
                                           placeholder="-70.6693">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Notas Adicionales</label>
                                <textarea name="notas" class="form-control" rows="6"><?php echo htmlspecialchars($cliente['notas'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="activo" id="activo"
                                           <?php echo ($cliente['activo'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="activo">Cliente Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            <?php elseif ($accion === 'ver' && $cliente): ?>
                <!-- VISTA DETALLE -->
                <div class="tabs">
                    <button type="button" class="tab active" onclick="cambiarTab(event, 'tab1')">Datos Generales</button>
                    <button type="button" class="tab" onclick="cambiarTab(event, 'tab2')">Datos Comerciales</button>
                    <button type="button" class="tab" onclick="cambiarTab(event, 'tab3')">Datos de Credito</button>
                    <button type="button" class="tab" onclick="cambiarTab(event, 'tab4')">Datos Fiscales</button>
                    <button type="button" class="tab" onclick="cambiarTab(event, 'tab5')">Historial</button>
                </div>

                <!-- TAB 1: DATOS GENERALES -->
                <div id="tab1" class="tab-content active">
                    <div class="form-section">
                        <div class="form-section-title">DATOS GENERALES</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Codigo Cliente</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['codigo']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Nombre / Razon Social</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">RUT / NIF</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['rut']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Tipo de Cliente</div>
                                <div class="detail-value"><span class="badge badge-info"><?php echo htmlspecialchars($cliente['tipo_cliente']); ?></span></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Clasificacion ABC</div>
                                <div class="detail-value">
                                    <?php
                                    $clase_abc = 'badge-primary';
                                    if ($cliente['clasificacion_abc'] === 'A') $clase_abc = 'badge-success';
                                    elseif ($cliente['clasificacion_abc'] === 'B') $clase_abc = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $clase_abc; ?>"><?php echo htmlspecialchars($cliente['clasificacion_abc']); ?></span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Segmento Comercial</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['segmento_comercial']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Zona Geografica</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['zona_geografica']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Idioma</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['idioma']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Canal de Venta</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['canal_venta']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Estado</div>
                                <div class="detail-value">
                                    <span class="badge <?php echo $cliente['activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $cliente['activo'] ? 'ACTIVO' : 'INACTIVO'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: DATOS COMERCIALES -->
                <div id="tab2" class="tab-content">
                    <div class="form-section">
                        <div class="form-section-title">DATOS COMERCIALES</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Condicion de Pago</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['condicion_pago']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Forma de Pago</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['forma_pago']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Descuento Comercial</div>
                                <div class="detail-value"><?php echo number_format($cliente['descuento_comercial'], 2); ?>%</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Lista de Precios</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['lista_precios']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Plazo de Entrega</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['plazo_entrega']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Politica de Devolucion</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['politica_devolucion']); ?></div>
                            </div>
                        </div>

                        <?php if ($cliente['acuerdos_comerciales']): ?>
                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Acuerdos Comerciales</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($cliente['acuerdos_comerciales'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($cliente['contratos_vigentes']): ?>
                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Contratos Vigentes</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($cliente['contratos_vigentes'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 3: DATOS DE CREDITO -->
                <div id="tab3" class="tab-content">
                    <div class="form-section">
                        <div class="form-section-title">DATOS DE CREDITO Y RIESGO</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Limite de Credito</div>
                                <div class="detail-value">$<?php echo number_format($cliente['limite_credito'], 0, ',', '.'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Riesgo Crediticio</div>
                                <div class="detail-value">
                                    <?php
                                    $clase_riesgo = 'badge-success';
                                    if (in_array($cliente['riesgo_crediticio'], ['MEDIO_ALTO', 'ALTO', 'MUY_ALTO'])) $clase_riesgo = 'badge-danger';
                                    elseif ($cliente['riesgo_crediticio'] === 'MEDIO') $clase_riesgo = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $clase_riesgo; ?>"><?php echo htmlspecialchars($cliente['riesgo_crediticio']); ?></span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Nivel de Morosidad</div>
                                <div class="detail-value"><?php echo number_format($cliente['nivel_morosidad'], 2); ?>%</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Estado de Credito</div>
                                <div class="detail-value">
                                    <?php
                                    $clase_estado = 'badge-success';
                                    if ($cliente['estado_credito'] === 'REVISION') $clase_estado = 'badge-warning';
                                    elseif (in_array($cliente['estado_credito'], ['SUSPENDIDO', 'BLOQUEADO'])) $clase_estado = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $clase_estado; ?>"><?php echo htmlspecialchars($cliente['estado_credito']); ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if ($cliente['garantias']): ?>
                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Garantias</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($cliente['garantias'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($cliente['documentacion_financiera']): ?>
                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Documentacion Financiera</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($cliente['documentacion_financiera'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 4: DATOS FISCALES -->
                <div id="tab4" class="tab-content">
                    <div class="form-section">
                        <div class="form-section-title">DATOS FISCALES Y TRIBUTARIOS</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Tipo de Contribuyente</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['tipo_contribuyente']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Impuestos Asociados</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['impuestos_asociados']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Reglas de Retencion</div>
                                <div class="detail-value"><?php echo htmlspecialchars($cliente['reglas_retencion'] ?: 'Sin retencion'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">DTE Habilitado</div>
                                <div class="detail-value">
                                    <span class="badge <?php echo $cliente['dte_habilitado'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $cliente['dte_habilitado'] ? 'SI' : 'NO'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Direccion Comercial</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($cliente['direccion_comercial'])); ?></div>
                        </div>

                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Direccion de Despacho</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($cliente['direccion_despacho'])); ?></div>
                        </div>

                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Direccion de Facturacion</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($cliente['direccion_facturacion'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: HISTORIAL -->
                <div id="tab5" class="tab-content">
                    <div class="form-section">
                        <div class="form-section-title">HISTORIAL Y GESTION</div>
                        <p>Modulo de historial de pedidos, ventas, pagos y actividades CRM en desarrollo.</p>
                        <p>Se integrara con modulos de CxC, Ventas, Logistica y Tesoreria.</p>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        function cambiarTab(evento, tabId) {
            var tabs = document.getElementsByClassName('tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }

            var tabContents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }

            evento.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function exportarExcel() {
            alert('Funcionalidad de exportacion a Excel en desarrollo');
        }
    </script>
</body>
</html>
