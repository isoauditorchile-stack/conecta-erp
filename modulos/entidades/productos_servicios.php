<?php
/**
 * PRODUCTOS Y SERVICIOS - CONECTA ERP
 * CRUD Completo de Productos y Servicios (PROD)
 * Nivel Empresarial - Estilo SAP/Softland
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
requireLogin();

// Verificar permisos
$puede_gestionar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('productos', 'gestionar');
$puede_crear = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('productos', 'crear');
$puede_editar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('productos', 'editar');
$puede_eliminar = ($_SESSION['es_super_admin'] == 1) || verificarPermiso('productos', 'eliminar');

$mensaje = '';
$tipo_mensaje = '';

// ==========================================
// PROCESAR ACCIONES CRUD
// ==========================================

// CREAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    if (!$puede_crear) {
        $mensaje = 'No tienes permisos para crear productos';
        $tipo_mensaje = 'danger';
    } else {
        $codigo = strtoupper(trim($_POST['codigo']));
        $sku = strtoupper(trim($_POST['sku']));
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $tipo = $_POST['tipo'];
        $categoria = $_POST['categoria'];
        $unidad_medida = $_POST['unidad_medida'];
        $precio_compra = (float)$_POST['precio_compra'];
        $precio_venta = (float)$_POST['precio_venta'];
        $stock = (float)$_POST['stock'];
        $stock_minimo = (float)$_POST['stock_minimo'];
        $stock_maximo = (float)$_POST['stock_maximo'];
        $codigo_barras = trim($_POST['codigo_barras']);
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);

        // Validaciones
        if (empty($codigo) || empty($nombre)) {
            $mensaje = 'Código y nombre son obligatorios';
            $tipo_mensaje = 'danger';
        } else {
            // Verificar código único
            $stmt = $conn->prepare("SELECT id FROM productos WHERE codigo = ? AND empresa_id = ?");
            $stmt->bind_param("si", $codigo, $_SESSION['empresa_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $mensaje = 'Ya existe un producto con este código';
                $tipo_mensaje = 'danger';
            } else {
                // Crear producto
                $stmt = $conn->prepare("INSERT INTO productos (
                    codigo, sku, nombre, descripcion, tipo, categoria, unidad_medida,
                    precio_compra, precio_venta, stock, stock_minimo, stock_maximo,
                    codigo_barras, marca, modelo, estado,
                    empresa_id, creado_por, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?, ?, NOW())");

                $stmt->bind_param("sssssssdddddsssii",
                    $codigo, $sku, $nombre, $descripcion, $tipo, $categoria, $unidad_medida,
                    $precio_compra, $precio_venta, $stock, $stock_minimo, $stock_maximo,
                    $codigo_barras, $marca, $modelo,
                    $_SESSION['empresa_id'], $_SESSION['usuario_id']
                );

                if ($stmt->execute()) {
                    $nuevo_id = $conn->insert_id;
                    $mensaje = "Producto '$nombre' creado exitosamente con código $codigo";
                    $tipo_mensaje = 'success';

                    logAuditoria('crear', 'productos', $nuevo_id, null, null,
                        "Producto creado: $nombre ($codigo)");
                } else {
                    $mensaje = 'Error al crear el producto: ' . $stmt->error;
                    $tipo_mensaje = 'danger';
                }
            }
            $stmt->close();
        }
    }
}

// EDITAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    if (!$puede_editar) {
        $mensaje = 'No tienes permisos para editar productos';
        $tipo_mensaje = 'danger';
    } else {
        $producto_id = (int)$_POST['producto_id'];
        $codigo = strtoupper(trim($_POST['codigo']));
        $sku = strtoupper(trim($_POST['sku']));
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $tipo = $_POST['tipo'];
        $categoria = $_POST['categoria'];
        $unidad_medida = $_POST['unidad_medida'];
        $precio_compra = (float)$_POST['precio_compra'];
        $precio_venta = (float)$_POST['precio_venta'];
        $stock_minimo = (float)$_POST['stock_minimo'];
        $stock_maximo = (float)$_POST['stock_maximo'];
        $estado = $_POST['estado'];

        $stmt = $conn->prepare("UPDATE productos SET
            codigo = ?, sku = ?, nombre = ?, descripcion = ?, tipo = ?, categoria = ?, unidad_medida = ?,
            precio_compra = ?, precio_venta = ?, stock_minimo = ?, stock_maximo = ?, estado = ?,
            modificado_por = ?, fecha_modificacion = NOW()
            WHERE id = ? AND empresa_id = ?");

        $stmt->bind_param("sssssssddddsiid",
            $codigo, $sku, $nombre, $descripcion, $tipo, $categoria, $unidad_medida,
            $precio_compra, $precio_venta, $stock_minimo, $stock_maximo, $estado,
            $_SESSION['usuario_id'], $producto_id, $_SESSION['empresa_id']
        );

        if ($stmt->execute()) {
            $mensaje = "Producto '$nombre' actualizado exitosamente";
            $tipo_mensaje = 'success';

            logAuditoria('editar', 'productos', $producto_id, null, null,
                "Producto editado: $nombre");
        } else {
            $mensaje = 'Error al actualizar el producto';
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ELIMINAR PRODUCTO
if (isset($_GET['eliminar']) && $puede_eliminar) {
    $producto_id = (int)$_GET['eliminar'];

    $stmt = $conn->prepare("SELECT nombre FROM productos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $producto_id, $_SESSION['empresa_id']);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Soft delete
    $stmt = $conn->prepare("UPDATE productos SET estado = 'inactivo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $producto_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Producto '{$producto['nombre']}' desactivado exitosamente";
        $tipo_mensaje = 'success';

        logAuditoria('eliminar', 'productos', $producto_id, null, null,
            "Producto desactivado: {$producto['nombre']}");
    }
    $stmt->close();
}

// ACTIVAR PRODUCTO
if (isset($_GET['activar']) && $puede_editar) {
    $producto_id = (int)$_GET['activar'];

    $stmt = $conn->prepare("UPDATE productos SET estado = 'activo', modificado_por = ?, fecha_modificacion = NOW()
                            WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("iii", $_SESSION['usuario_id'], $producto_id, $_SESSION['empresa_id']);

    if ($stmt->execute()) {
        $mensaje = "Producto activado exitosamente";
        $tipo_mensaje = 'success';
    }
    $stmt->close();
}

// ==========================================
// OBTENER LISTA DE PRODUCTOS
// ==========================================

$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_tipo = $_GET['tipo'] ?? 'todos';
$filtro_categoria = $_GET['categoria'] ?? 'todos';
$filtro_stock = $_GET['stock'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';

$query = "SELECT p.*,
          u1.nombre as creador_nombre, u1.apellido as creador_apellido
          FROM productos p
          LEFT JOIN usuarios u1 ON p.creado_por = u1.id
          WHERE p.empresa_id = {$_SESSION['empresa_id']}";

// Filtros
if ($filtro_estado !== 'todos') {
    $query .= " AND p.estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}

if ($filtro_tipo !== 'todos') {
    $query .= " AND p.tipo = '" . $conn->real_escape_string($filtro_tipo) . "'";
}

if ($filtro_categoria !== 'todos') {
    $query .= " AND p.categoria = '" . $conn->real_escape_string($filtro_categoria) . "'";
}

if ($filtro_stock === 'bajo') {
    $query .= " AND p.stock <= p.stock_minimo";
} elseif ($filtro_stock === 'critico') {
    $query .= " AND p.stock < (p.stock_minimo * 0.5)";
}

if (!empty($busqueda)) {
    $busqueda_escape = $conn->real_escape_string($busqueda);
    $query .= " AND (p.codigo LIKE '%{$busqueda_escape}%'
                 OR p.sku LIKE '%{$busqueda_escape}%'
                 OR p.nombre LIKE '%{$busqueda_escape}%'
                 OR p.descripcion LIKE '%{$busqueda_escape}%'
                 OR p.codigo_barras LIKE '%{$busqueda_escape}%')";
}

$query .= " ORDER BY p.fecha_creacion DESC";

$result = $conn->query($query);
$productos = $result->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN tipo = 'producto' THEN 1 ELSE 0 END) as productos,
    SUM(CASE WHEN tipo = 'servicio' THEN 1 ELSE 0 END) as servicios,
    SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) as stock_bajo,
    SUM(CASE WHEN stock < (stock_minimo * 0.5) THEN 1 ELSE 0 END) as stock_critico,
    SUM(stock * precio_venta) as valor_inventario
    FROM productos
    WHERE empresa_id = {$_SESSION['empresa_id']}")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos y Servicios - CONECTA ERP</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../assets/css/dark_mode.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 250px;
            --topbar-height: 60px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            padding: 20px 0;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a i {
            width: 25px;
            margin-right: 10px;
            font-size: 18px;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 30px;
            min-height: calc(100vh - var(--topbar-height));
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stats-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-icon.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; }
        .stats-icon.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); color: white; }
        .stats-icon.red { background: linear-gradient(135deg, #f56565 0%, #c53030 100%); color: white; }
        .stats-icon.purple { background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); color: white; }

        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #718096;
            font-size: 14px;
        }

        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-top: 20px;
        }

        .content-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f7fa;
        }

        .content-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .btn-gradient {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .badge-activo { background: #48bb78; }
        .badge-inactivo { background: #a0aec0; }
        .badge-producto { background: #667eea; }
        .badge-servicio { background: #ed8936; }

        .stock-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .stock-normal { background: #48bb78; color: white; }
        .stock-bajo { background: #ed8936; color: white; }
        .stock-critico { background: #f56565; color: white; }

        .table thead th {
            background: #f5f7fa;
            color: #2d3748;
            font-weight: 600;
            border: none;
        }

        .table tbody tr:hover {
            background: #f5f7fa;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 12px;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .modal-content {
            border-radius: 10px;
            border: none;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filters {
            background: #f5f7fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .topbar {
                left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-rocket"></i> CONECTA ERP</h3>
            <small style="color: rgba(255,255,255,0.7);">Productos</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="entidades_maestras.php"><i class="fas fa-database"></i> Entidades Maestras</a></li>
            <li><a href="gestion_clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="gestion_proveedores.php"><i class="fas fa-truck"></i> Proveedores</a></li>
            <li><a href="gestion_empleados.php"><i class="fas fa-user-tie"></i> Empleados</a></li>
            <li><a href="productos_servicios.php" class="active"><i class="fas fa-boxes"></i> Productos</a></li>
            <li><a href="../../user/dashboard.php"><i class="fas fa-arrow-left"></i> Volver</a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="entidades_maestras.php">Entidades</a></li>
                <li class="breadcrumb-item active">Productos y Servicios</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm" data-theme-toggle>
                <i class="fas fa-moon"></i>
            </button>
            <span class="text-muted">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Productos y Servicios</h1>
                <p class="text-muted mb-0">Gestión completa de catálogo e inventario</p>
            </div>
            <?php if ($puede_crear): ?>
                <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalCrearProducto">
                    <i class="fas fa-plus"></i> Nuevo Producto
                </button>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stats-label">Total Items</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['productos']); ?></div>
                    <div class="stats-label">Productos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['stock_bajo']); ?></div>
                    <div class="stats-label">Stock Bajo</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon purple">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-number">$<?php echo number_format($stats['valor_inventario'], 0, ',', '.'); ?></div>
                    <div class="stats-label">Valor Inventario</div>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-card-header">
                <h2 class="content-card-title">Catálogo de Productos</h2>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                            <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_tipo === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="producto" <?php echo $filtro_tipo === 'producto' ? 'selected' : ''; ?>>Productos</option>
                            <option value="servicio" <?php echo $filtro_tipo === 'servicio' ? 'selected' : ''; ?>>Servicios</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_categoria === 'todos' ? 'selected' : ''; ?>>Todas</option>
                            <option value="electronica" <?php echo $filtro_categoria === 'electronica' ? 'selected' : ''; ?>>Electrónica</option>
                            <option value="muebles" <?php echo $filtro_categoria === 'muebles' ? 'selected' : ''; ?>>Muebles</option>
                            <option value="textil" <?php echo $filtro_categoria === 'textil' ? 'selected' : ''; ?>>Textil</option>
                            <option value="alimentos" <?php echo $filtro_categoria === 'alimentos' ? 'selected' : ''; ?>>Alimentos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stock</label>
                        <select name="stock" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_stock === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="bajo" <?php echo $filtro_stock === 'bajo' ? 'selected' : ''; ?>>Stock Bajo</option>
                            <option value="critico" <?php echo $filtro_stock === 'critico' ? 'selected' : ''; ?>>Stock Crítico</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control" placeholder="Código, nombre, SKU..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-gradient w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-hover" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>SKU</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Precio Venta</th>
                            <th>Stock</th>
                            <th>Estado Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                        <?php
                            $stock_estado = 'normal';
                            if ($producto['tipo'] === 'producto') {
                                if ($producto['stock'] < ($producto['stock_minimo'] * 0.5)) {
                                    $stock_estado = 'critico';
                                } elseif ($producto['stock'] <= $producto['stock_minimo']) {
                                    $stock_estado = 'bajo';
                                }
                            }
                        ?>
                        <tr>
                            <td><strong><code><?php echo htmlspecialchars($producto['codigo']); ?></code></strong></td>
                            <td><?php echo htmlspecialchars($producto['sku']); ?></td>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $producto['tipo']; ?>">
                                    <?php echo ucfirst($producto['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($producto['categoria']); ?></td>
                            <td><strong>$<?php echo number_format($producto['precio_venta'], 0, ',', '.'); ?></strong></td>
                            <td>
                                <?php if ($producto['tipo'] === 'producto'): ?>
                                    <?php echo number_format($producto['stock'], 0); ?> <?php echo $producto['unidad_medida']; ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($producto['tipo'] === 'producto'): ?>
                                    <span class="stock-badge stock-<?php echo $stock_estado; ?>">
                                        <?php echo ucfirst($stock_estado); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $producto['estado']; ?>">
                                    <?php echo ucfirst($producto['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_editar): ?>
                                        <button class="btn btn-sm btn-primary" onclick="alert('Editar producto <?php echo $producto['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($puede_eliminar): ?>
                                        <?php if ($producto['estado'] === 'activo'): ?>
                                            <a href="?eliminar=<?php echo $producto['id']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Desactivar este producto?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?activar=<?php echo $producto['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info" onclick="alert('Ver detalle')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear Producto -->
    <div class="modal fade" id="modalCrearProducto" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-boxes"></i> Nuevo Producto/Servicio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Código *</label>
                                <input type="text" name="codigo" class="form-control" placeholder="PROD-001" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SKU</label>
                                <input type="text" name="sku" class="form-control" placeholder="SKU-001">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo *</label>
                                <select name="tipo" class="form-select" required>
                                    <option value="producto">Producto</option>
                                    <option value="servicio">Servicio</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="electronica">Electrónica</option>
                                    <option value="muebles">Muebles</option>
                                    <option value="textil">Textil</option>
                                    <option value="alimentos">Alimentos</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unidad de Medida</label>
                                <select name="unidad_medida" class="form-select">
                                    <option value="unidad">Unidad</option>
                                    <option value="kg">Kilogramo (kg)</option>
                                    <option value="lt">Litro (lt)</option>
                                    <option value="mt">Metro (mt)</option>
                                    <option value="caja">Caja</option>
                                    <option value="paquete">Paquete</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Precio Compra</label>
                                <input type="number" name="precio_compra" class="form-control" value="0" step="0.01">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Precio Venta *</label>
                                <input type="number" name="precio_venta" class="form-control" value="0" step="0.01" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Código de Barras</label>
                                <input type="text" name="codigo_barras" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock Inicial</label>
                                <input type="number" name="stock" class="form-control" value="0" step="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" class="form-control" value="5" step="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock Máximo</label>
                                <input type="number" name="stock_maximo" class="form-control" value="100" step="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_producto" class="btn btn-gradient">
                            <i class="fas fa-save"></i> Crear Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="../../assets/js/dark_mode.js"></script>

    <script>
        $('#tablaProductos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25
        });
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
