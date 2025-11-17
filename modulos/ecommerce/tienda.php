<?php
/**
 * TIENDA ONLINE - CONECTA ERP
 * Gestión de catálogo de productos para ecommerce
 * Incluye configuración de tienda, categorías y productos visibles
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$empresa_id = $stmt->get_result()->fetch_assoc()['empresa_id'];
$stmt->close();

$mensaje = '';
$tipo_mensaje = '';

// ==================================================================
// CONFIGURAR TIENDA
// ==================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $nombre_tienda = trim($_POST['nombre_tienda']);
    $url_tienda = trim($_POST['url_tienda']);
    $descripcion = trim($_POST['descripcion']);
    $email_contacto = trim($_POST['email_contacto']);
    $telefono_contacto = trim($_POST['telefono_contacto']);
    $activa = isset($_POST['activa']) ? 1 : 0;
    $iva_incluido = isset($_POST['iva_incluido']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO tienda_online (empresa_id, nombre_tienda, url_tienda, descripcion, email_contacto, telefono_contacto, activa, iva_incluido) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nombre_tienda = VALUES(nombre_tienda), url_tienda = VALUES(url_tienda), descripcion = VALUES(descripcion), email_contacto = VALUES(email_contacto), telefono_contacto = VALUES(telefono_contacto), activa = VALUES(activa), iva_incluido = VALUES(iva_incluido)");

    $stmt->bind_param("issssii", $empresa_id, $nombre_tienda, $url_tienda, $descripcion, $email_contacto, $telefono_contacto, $activa, $iva_incluido);

    if ($stmt->execute()) {
        $mensaje = "Configuración de tienda actualizada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error: " . $stmt->error;
        $tipo_mensaje = "danger";
    }
    $stmt->close();
}

// ==================================================================
// PUBLICAR/DESPUBLICAR PRODUCTO
// ==================================================================
if (isset($_GET['toggle_producto'])) {
    $producto_id = intval($_GET['toggle_producto']);
    $conn->query("UPDATE productos SET visible_tienda = NOT visible_tienda WHERE id = $producto_id AND empresa_id = $empresa_id");
    $mensaje = "Visibilidad del producto actualizada";
    $tipo_mensaje = "info";
}

// ==================================================================
// OBTENER CONFIGURACIÓN
// ==================================================================
$config = $conn->query("SELECT * FROM tienda_online WHERE empresa_id = $empresa_id LIMIT 1")->fetch_assoc();

// ==================================================================
// OBTENER PRODUCTOS
// ==================================================================
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$where = "p.empresa_id = $empresa_id";
if ($buscar) {
    $buscar_safe = $conn->real_escape_string($buscar);
    $where .= " AND (p.codigo LIKE '%$buscar_safe%' OR p.nombre LIKE '%$buscar_safe%')";
}
if ($categoria_filtro) {
    $where .= " AND p.categoria_id = $categoria_filtro";
}

$query = "SELECT p.*, c.nombre as categoria_nombre,
    (SELECT COUNT(*) FROM pedidos_web_detalle pwd
     INNER JOIN pedidos_web pw ON pwd.pedido_id = pw.id
     WHERE pwd.producto_id = p.id AND pw.estado != 'cancelado') as ventas_online
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE $where
    ORDER BY p.visible_tienda DESC, p.nombre ASC";

$productos = $conn->query($query);

// Categorías
$categorias = $conn->query("SELECT * FROM categorias_productos WHERE empresa_id = $empresa_id AND activo = 1 ORDER BY nombre");

// Estadísticas
$stats = $conn->query("SELECT
    COUNT(*) as total_productos,
    SUM(visible_tienda) as productos_publicados,
    (SELECT COUNT(*) FROM pedidos_web WHERE empresa_id = $empresa_id AND estado != 'cancelado') as total_pedidos_web,
    (SELECT SUM(total) FROM pedidos_web WHERE empresa_id = $empresa_id AND estado != 'cancelado') as ventas_totales
    FROM productos WHERE empresa_id = $empresa_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Online - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.2s;
            border-left: 4px solid #e0e0e0;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card.published {
            border-left-color: #48bb78;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid py-4">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-shopping-cart"></i> Tienda Online</h2>
                    <p class="text-muted">Gestión de catálogo y configuración de ecommerce</p>
                </div>
                <div>
                    <?php if ($config): ?>
                        <a href="<?php echo $config['url_tienda']; ?>" target="_blank" class="btn btn-outline-primary me-2">
                            <i class="fas fa-external-link-alt"></i> Ver Tienda
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalConfig">
                        <i class="fas fa-cog"></i> Configurar Tienda
                    </button>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0" style="border-left: 4px solid #667eea !important;">
                        <div class="card-body">
                            <h6 class="text-muted">Productos Totales</h6>
                            <h3><?php echo number_format($stats['total_productos']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0" style="border-left: 4px solid #48bb78 !important;">
                        <div class="card-body">
                            <h6 class="text-muted">Productos Publicados</h6>
                            <h3><?php echo number_format($stats['productos_publicados']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0" style="border-left: 4px solid #f59e0b !important;">
                        <div class="card-body">
                            <h6 class="text-muted">Pedidos Web</h6>
                            <h3><?php echo number_format($stats['total_pedidos_web']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0" style="border-left: 4px solid #ef4444 !important;">
                        <div class="card-body">
                            <h6 class="text-muted">Ventas Online</h6>
                            <h3>$<?php echo number_format($stats['ventas_totales'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado de la Tienda -->
            <?php if ($config): ?>
                <div class="alert <?php echo $config['activa'] ? 'alert-success' : 'alert-warning'; ?> mb-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                <i class="fas fa-<?php echo $config['activa'] ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                <?php echo $config['nombre_tienda']; ?>
                            </h5>
                            <p class="mb-0">
                                Estado: <strong><?php echo $config['activa'] ? 'ACTIVA' : 'INACTIVA'; ?></strong> •
                                URL: <a href="<?php echo $config['url_tienda']; ?>" target="_blank"><?php echo $config['url_tienda']; ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> Configura tu tienda online para comenzar a vender
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" name="buscar" class="form-control"
                                   placeholder="Buscar productos..." value="<?php echo htmlspecialchars($buscar); ?>">
                        </div>
                        <div class="col-md-5">
                            <select name="categoria" class="form-select">
                                <option value="0">Todas las categorías</option>
                                <?php while ($cat = $categorias->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                            <?php echo $categoria_filtro === $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['nombre']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Productos -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Catálogo de Productos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Imagen</th>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Ventas Online</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($productos->num_rows > 0): ?>
                                    <?php while ($prod = $productos->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $prod['imagen'] ?? '/assets/img/no-image.png'; ?>"
                                                     class="product-image" alt="Producto">
                                            </td>
                                            <td><strong><?php echo $prod['codigo']; ?></strong></td>
                                            <td><?php echo $prod['nombre']; ?></td>
                                            <td><?php echo $prod['categoria_nombre']; ?></td>
                                            <td class="text-end">
                                                <strong>$<?php echo number_format($prod['precio_venta'], 0, ',', '.'); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $prod['stock_actual'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $prod['stock_actual']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?php echo $prod['ventas_online']; ?></td>
                                            <td class="text-center">
                                                <?php if ($prod['visible_tienda']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-eye"></i> Publicado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-eye-slash"></i> Oculto
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="?toggle_producto=<?php echo $prod['id']; ?>" class="btn btn-sm btn-<?php echo $prod['visible_tienda'] ? 'warning' : 'success'; ?>">
                                                    <i class="fas fa-<?php echo $prod['visible_tienda'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                    <?php echo $prod['visible_tienda'] ? 'Ocultar' : 'Publicar'; ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay productos disponibles</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Configuración Tienda -->
    <div class="modal fade" id="modalConfig" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-cog"></i> Configuración de Tienda Online</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Nombre de la Tienda <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_tienda" class="form-control"
                                       value="<?php echo $config['nombre_tienda'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">URL de la Tienda <span class="text-danger">*</span></label>
                                <input type="url" name="url_tienda" class="form-control"
                                       value="<?php echo $config['url_tienda'] ?? ''; ?>"
                                       placeholder="https://tienda.ejemplo.cl" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3"><?php echo $config['descripcion'] ?? ''; ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email de Contacto</label>
                                <input type="email" name="email_contacto" class="form-control"
                                       value="<?php echo $config['email_contacto'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Teléfono de Contacto</label>
                                <input type="text" name="telefono_contacto" class="form-control"
                                       value="<?php echo $config['telefono_contacto'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="activa" class="form-check-input" id="activaTienda"
                                           <?php echo ($config['activa'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activaTienda">
                                        <strong>Tienda Activa</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="iva_incluido" class="form-check-input" id="ivaIncluido"
                                           <?php echo ($config['iva_incluido'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ivaIncluido">
                                        Precios con IVA incluido
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_config" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
