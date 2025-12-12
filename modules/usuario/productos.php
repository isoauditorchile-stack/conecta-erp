<?php
require_once __DIR__ . '/../../config/config.php';

// Verificar si está logueado
if (!is_logged_in()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$user = db_get_row("SELECT * FROM usuarios WHERE id = ?", [$user_id]);
$empresa_id = $user['empresa_id'] ?? null;

// ========================================
// PROCESAR FORMULARIOS
// ========================================
$success = '';
$error = '';

// Crear producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {
    try {
        // Manejo de imagen principal
        $imagen_url = null;
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/productos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];

            if (in_array($file_ext, $allowed_exts)) {
                $producto_temp_id = uniqid();
                $imagen_name = 'producto_' . $producto_temp_id . '_principal.' . $file_ext;
                $imagen_path = $upload_dir . $imagen_name;

                if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $imagen_path)) {
                    $imagen_url = '/uploads/productos/' . $imagen_name;
                }
            }
        }

        // Insertar producto
        db_query("INSERT INTO ma_productos (
            empresa_id, codigo_interno, nombre, tipo_producto, sku, codigo_barras,
            unidad_medida, marca, modelo, categoria_id, subcategoria, estado,
            pais_origen, descripcion_corta, descripcion_larga, grupo_producto,
            linea_producto, tipo_contable, familia, subfamilia, segmento,
            tipo_tributario, tipo_costo, imagen_principal_url, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $empresa_id,
            $_POST['codigo_interno'] ?? '',
            $_POST['nombre'],
            $_POST['tipo_producto'] ?? 'inventariable',
            $_POST['sku'] ?? '',
            $_POST['codigo_barras'] ?? '',
            $_POST['unidad_medida'] ?? '',
            $_POST['marca'] ?? '',
            $_POST['modelo'] ?? '',
            $_POST['categoria_id'] ?? null,
            $_POST['subcategoria'] ?? '',
            $_POST['estado'] ?? 'activo',
            $_POST['pais_origen'] ?? '',
            $_POST['descripcion_corta'] ?? '',
            $_POST['descripcion_larga'] ?? '',
            $_POST['grupo_producto'] ?? '',
            $_POST['linea_producto'] ?? '',
            $_POST['tipo_contable'] ?? '',
            $_POST['familia'] ?? '',
            $_POST['subfamilia'] ?? '',
            $_POST['segmento'] ?? '',
            $_POST['tipo_tributario'] ?? 'afecto',
            $_POST['tipo_costo'] ?? 'promedio',
            $imagen_url,
            $user_id
        ]);

        $producto_id = db_get_var("SELECT LAST_INSERT_ID()");

        // Inventario (si es inventariable o materia prima)
        if (in_array($_POST['tipo_producto'], ['inventariable', 'materia_prima', 'producto_terminado'])) {
            db_query("INSERT INTO prod_inventario (
                producto_id, stock_actual, stock_minimo, stock_maximo, punto_reorden,
                ciclo_reposicion, bodega_principal_id, ubicacion_fisica, control_lotes,
                control_vencimiento, peso_bruto, peso_neto, volumen
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $producto_id,
                $_POST['stock_actual'] ?? 0,
                $_POST['stock_minimo'] ?? 0,
                $_POST['stock_maximo'] ?? 0,
                $_POST['punto_reorden'] ?? 0,
                $_POST['ciclo_reposicion'] ?? 0,
                $_POST['bodega_principal_id'] ?? null,
                $_POST['ubicacion_fisica'] ?? '',
                isset($_POST['control_lotes']) ? 1 : 0,
                isset($_POST['control_vencimiento']) ? 1 : 0,
                $_POST['peso_bruto'] ?? 0,
                $_POST['peso_neto'] ?? 0,
                $_POST['volumen'] ?? 0
            ]);
        }

        // Costos
        db_query("INSERT INTO prod_costos (
            producto_id, costo_compra, costo_produccion, costos_indirectos,
            costos_operacionales, ultimo_costo, costo_estandar, margen_porcentaje,
            precio_sugerido, metodo_costeo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $producto_id,
            $_POST['costo_compra'] ?? 0,
            $_POST['costo_produccion'] ?? 0,
            $_POST['costos_indirectos'] ?? 0,
            $_POST['costos_operacionales'] ?? 0,
            $_POST['ultimo_costo'] ?? 0,
            $_POST['costo_estandar'] ?? 0,
            $_POST['margen_porcentaje'] ?? 0,
            $_POST['precio_sugerido'] ?? 0,
            $_POST['tipo_costo'] ?? 'promedio'
        ]);

        // Precios
        db_query("INSERT INTO prod_precios (
            producto_id, precio_venta_neto, precio_bruto, impuesto_porcentaje,
            precio_web, precio_pos, precio_mayorista, precio_distribuidor
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
            $producto_id,
            $_POST['precio_venta_neto'] ?? 0,
            $_POST['precio_bruto'] ?? 0,
            $_POST['impuesto_porcentaje'] ?? 19,
            $_POST['precio_web'] ?? 0,
            $_POST['precio_pos'] ?? 0,
            $_POST['precio_mayorista'] ?? 0,
            $_POST['precio_distribuidor'] ?? 0
        ]);

        // Registrar en auditoría
        db_query("INSERT INTO prod_auditoria (producto_id, usuario_id, accion, fecha, ip_usuario, detalles)
                  VALUES (?, ?, 'CREAR', NOW(), ?, ?)", [
            $producto_id, $user_id, $_SERVER['REMOTE_ADDR'] ?? '',
            'Producto creado: ' . $_POST['nombre']
        ]);

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=producto_creado");
        exit;

    } catch (Exception $e) {
        $error = "Error al crear producto: " . $e->getMessage();
    }
}

// Mensaje de éxito
if (isset($_GET['success']) && $_GET['success'] === 'producto_creado') {
    $success = "Producto creado exitosamente";
}

// ========================================
// AUTO-CREAR TABLAS (sin datos embebidos)
// ========================================
try {
    // Tabla: Marcas
    db_query("CREATE TABLE IF NOT EXISTS marcas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) UNIQUE NOT NULL,
        descripcion TEXT,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Tipos de Producto
    db_query("CREATE TABLE IF NOT EXISTS tipos_producto (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        requiere_inventario TINYINT(1) DEFAULT 1,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla: Tipos de Costo
    db_query("CREATE TABLE IF NOT EXISTS tipos_costeo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla principal: ma_productos (asegurar estructura)
    db_query("CREATE TABLE IF NOT EXISTS ma_productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        codigo_interno VARCHAR(50) UNIQUE,
        nombre VARCHAR(255) NOT NULL,
        tipo_producto ENUM('inventariable', 'no_inventariable', 'materia_prima', 'producto_terminado', 'kit') DEFAULT 'inventariable',
        sku VARCHAR(100),
        codigo_barras VARCHAR(100),
        unidad_medida VARCHAR(50),
        marca VARCHAR(100),
        modelo VARCHAR(100),
        categoria_id INT,
        subcategoria VARCHAR(100),
        estado ENUM('activo', 'inactivo', 'descontinuado') DEFAULT 'activo',
        pais_origen VARCHAR(100),
        descripcion_corta TEXT,
        descripcion_larga TEXT,
        grupo_producto VARCHAR(100),
        linea_producto VARCHAR(100),
        tipo_contable VARCHAR(50),
        familia VARCHAR(100),
        subfamilia VARCHAR(100),
        segmento VARCHAR(100),
        tipo_tributario ENUM('afecto', 'exento') DEFAULT 'afecto',
        tipo_costo VARCHAR(50),
        imagen_principal_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        INDEX idx_empresa (empresa_id),
        INDEX idx_tipo (tipo_producto),
        INDEX idx_sku (sku),
        INDEX idx_codigo_barras (codigo_barras),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Clasificación avanzada
    db_query("CREATE TABLE IF NOT EXISTS prod_clasificacion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL UNIQUE,
        cuenta_ventas VARCHAR(50),
        cuenta_costos VARCHAR(50),
        cuenta_compras VARCHAR(50),
        cuenta_inventario VARCHAR(50),
        codigo_contable VARCHAR(50),
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Inventario
    db_query("CREATE TABLE IF NOT EXISTS prod_inventario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL UNIQUE,
        stock_actual DECIMAL(15,4) DEFAULT 0,
        stock_minimo DECIMAL(15,4) DEFAULT 0,
        stock_maximo DECIMAL(15,4) DEFAULT 0,
        punto_reorden DECIMAL(15,4) DEFAULT 0,
        ciclo_reposicion INT DEFAULT 0,
        bodega_principal_id INT,
        ubicacion_fisica VARCHAR(255),
        control_lotes TINYINT(1) DEFAULT 0,
        control_vencimiento TINYINT(1) DEFAULT 0,
        peso_bruto DECIMAL(10,3),
        peso_neto DECIMAL(10,3),
        volumen DECIMAL(10,3),
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Costos
    db_query("CREATE TABLE IF NOT EXISTS prod_costos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL UNIQUE,
        costo_compra DECIMAL(15,4) DEFAULT 0,
        costo_produccion DECIMAL(15,4) DEFAULT 0,
        costos_indirectos DECIMAL(15,4) DEFAULT 0,
        costos_operacionales DECIMAL(15,4) DEFAULT 0,
        ultimo_costo DECIMAL(15,4) DEFAULT 0,
        costo_estandar DECIMAL(15,4) DEFAULT 0,
        margen_porcentaje DECIMAL(5,2) DEFAULT 0,
        precio_sugerido DECIMAL(15,4) DEFAULT 0,
        metodo_costeo ENUM('promedio', 'fifo', 'estandar', 'manual') DEFAULT 'promedio',
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // BOM (Bill of Materials)
    db_query("CREATE TABLE IF NOT EXISTS prod_bom (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        componente_id INT NOT NULL,
        cantidad DECIMAL(15,4) NOT NULL,
        costo_componente DECIMAL(15,4),
        tiempo_operacion INT,
        merma_porcentaje DECIMAL(5,2),
        version VARCHAR(50),
        activo TINYINT(1) DEFAULT 1,
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE,
        FOREIGN KEY (componente_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Proveedores asociados
    db_query("CREATE TABLE IF NOT EXISTS prod_proveedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        proveedor_id INT NOT NULL,
        es_principal TINYINT(1) DEFAULT 0,
        codigo_proveedor VARCHAR(100),
        precio_proveedor DECIMAL(15,4),
        lead_time_dias INT,
        moneda VARCHAR(10),
        condiciones_compra TEXT,
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Características técnicas
    db_query("CREATE TABLE IF NOT EXISTS prod_caracteristicas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL UNIQUE,
        dimensiones VARCHAR(255),
        materiales TEXT,
        fabricacion TEXT,
        compatibilidades TEXT,
        normativas TEXT,
        color VARCHAR(100),
        voltaje VARCHAR(50),
        potencia VARCHAR(50),
        garantia_meses INT,
        certificaciones TEXT,
        codigo_aduanero VARCHAR(50),
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Precios
    db_query("CREATE TABLE IF NOT EXISTS prod_precios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL UNIQUE,
        precio_venta_neto DECIMAL(15,4) DEFAULT 0,
        precio_bruto DECIMAL(15,4) DEFAULT 0,
        impuesto_porcentaje DECIMAL(5,2) DEFAULT 0,
        precio_web DECIMAL(15,4),
        precio_pos DECIMAL(15,4),
        precio_mayorista DECIMAL(15,4),
        precio_distribuidor DECIMAL(15,4),
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Imágenes
    db_query("CREATE TABLE IF NOT EXISTS prod_imagenes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        ruta_imagen VARCHAR(500) NOT NULL,
        es_principal TINYINT(1) DEFAULT 0,
        orden INT DEFAULT 0,
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        subido_por INT,
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Documentos
    db_query("CREATE TABLE IF NOT EXISTS prod_documentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        tipo_documento VARCHAR(100),
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500) NOT NULL,
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        subido_por INT,
        comentario TEXT,
        FOREIGN KEY (producto_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Variantes
    db_query("CREATE TABLE IF NOT EXISTS prod_variantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_padre_id INT NOT NULL,
        sku VARCHAR(100) UNIQUE NOT NULL,
        codigo_barras VARCHAR(100),
        atributos JSON,
        stock_independiente DECIMAL(15,4) DEFAULT 0,
        precio_diferencial DECIMAL(15,4) DEFAULT 0,
        FOREIGN KEY (producto_padre_id) REFERENCES ma_productos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Auditoría
    db_query("CREATE TABLE IF NOT EXISTS prod_auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT,
        usuario_id INT NOT NULL,
        accion VARCHAR(100) NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_usuario VARCHAR(50),
        detalles TEXT,
        campo_modificado VARCHAR(100),
        valor_anterior TEXT,
        valor_nuevo TEXT,
        INDEX idx_producto (producto_id),
        INDEX idx_fecha (fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

} catch (Exception $e) {
    // Silenciar errores si ya existen
}

// ========================================
// CONSULTAR DATOS
// ========================================

// Consultar productos
$productos = db_query("
    SELECT p.*,
           i.stock_actual,
           c.precio_venta_neto,
           co.costo_compra
    FROM ma_productos p
    LEFT JOIN prod_inventario i ON p.id = i.producto_id
    LEFT JOIN prod_precios c ON p.id = c.producto_id
    LEFT JOIN prod_costos co ON p.id = co.producto_id
    WHERE p.empresa_id = ?
    ORDER BY p.nombre ASC
", [$empresa_id]);

// Consultar datos para selects (TODO desde SQL, NADA embebido)
try { $unidades_medida = db_query("SELECT * FROM unidades_medida WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $unidades_medida = []; }
try { $marcas = db_query("SELECT * FROM marcas WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $marcas = []; }
try { $categorias = db_query("SELECT * FROM ma_categorias_productos WHERE empresa_id = ? ORDER BY nombre ASC", [$empresa_id]); } catch (Exception $e) { $categorias = []; }
try { $tipos_producto = db_query("SELECT * FROM tipos_producto WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $tipos_producto = []; }
try { $tipos_costeo = db_query("SELECT * FROM tipos_costeo WHERE activo = 1 ORDER BY nombre ASC"); } catch (Exception $e) { $tipos_costeo = []; }
try { $bodegas = db_query("SELECT * FROM ma_bodegas WHERE empresa_id = ? ORDER BY nombre ASC", [$empresa_id]); } catch (Exception $e) { $bodegas = []; }

// Estadísticas
$stats = [
    'total_productos' => db_get_var("SELECT COUNT(*) FROM ma_productos WHERE empresa_id = ?", [$empresa_id]) ?? 0,
    'inventariables' => db_get_var("SELECT COUNT(*) FROM ma_productos WHERE empresa_id = ? AND tipo_producto IN ('inventariable', 'materia_prima', 'producto_terminado')", [$empresa_id]) ?? 0,
    'servicios' => db_get_var("SELECT COUNT(*) FROM ma_productos WHERE empresa_id = ? AND tipo_producto = 'no_inventariable'", [$empresa_id]) ?? 0,
    'activos' => db_get_var("SELECT COUNT(*) FROM ma_productos WHERE empresa_id = ? AND estado = 'activo'", [$empresa_id]) ?? 0
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos y Servicios - CONECTA ERP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">

    <style>
/* Layout principal */
.main-wrapper {
    margin-left: 260px !important;
    width: calc(100% - 260px) !important;
    padding: 0 !important;
}

.main-header {
    width: 100% !important;
    padding: 1rem 2rem !important;
}

.main-content {
    width: 100% !important;
    padding: 2rem !important;
    margin: 0 !important;
}

/* Botón fullscreen */
.fullscreen-btn {
    position: fixed;
    top: 80px;
    right: 20px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    z-index: 10000;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.fullscreen-btn:hover {
    transform: scale(1.15);
}

body.fullscreen-mode .sidebar { display: none !important; }
body.fullscreen-mode .main-wrapper {
    margin-left: 0 !important;
    width: 100vw !important;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 9999;
    background: var(--body-bg);
    overflow: auto;
}

.stat-card {
    border-left: 4px solid var(--primary-color);
}

.badge-activo { background: #10b981; color: white; }
.badge-inactivo { background: #6b7280; color: white; }
.badge-descontinuado { background: #ef4444; color: white; }

.info-item {
    padding: 0.75rem;
    background: var(--body-bg);
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.info-item label {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    display: block;
}

.info-item .value {
    color: var(--text-primary);
}

.action-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0 0.125rem;
}

.nav-tabs .nav-link {
    color: var(--text-secondary);
    border: none;
    border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link.active {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
    background: transparent;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}

.producto-imagen {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.badge-tipo {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>
</head>
<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar_user.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper" id="mainWrapper">
        <header class="main-header">
            <div class="header-left">
                <h4 class="mb-0" style="color: var(--text-primary);">
                    <i class="fas fa-box me-2"></i>
                    Productos y Servicios
                </h4>
                <small class="text-muted">Módulo completo de gestión de productos, inventario y costos</small>
            </div>

            <div class="header-right">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['nombre_completo'], 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $user['nombre_completo']; ?></div>
                        <div class="user-role">Usuario</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content" id="mainContent">
            <!-- Botón Pantalla Completa -->
            <button class="fullscreen-btn" id="fullscreenToggle" title="Pantalla completa">
                <i class="fas fa-expand"></i>
            </button>

            <!-- Mensajes -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Total Productos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_productos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Inventariables</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['inventariables']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Servicios</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['servicios']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-concierge-bell"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted mb-1" style="font-size: 0.875rem;">Activos</p>
                                    <h2 class="mb-0"><?php echo number_format($stats['activos']); ?></h2>
                                </div>
                                <div class="feature-icon" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón Nuevo Producto -->
            <div class="mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearProducto">
                    <i class="fas fa-plus me-2"></i>Nuevo Producto/Servicio
                </button>
            </div>

            <!-- Listado de Productos -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Código/SKU</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Stock</th>
                                    <th>Costo</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">No hay productos registrados</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <tr>
                                            <td>
                                                <?php if ($producto['imagen_principal_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($producto['imagen_principal_url']); ?>"
                                                         alt="Imagen" class="producto-imagen">
                                                <?php else: ?>
                                                    <div style="width: 60px; height: 60px; border-radius: 8px; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-box text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($producto['codigo_interno'] ?? 'N/A'); ?></strong>
                                                <br><small class="text-muted">SKU: <?php echo htmlspecialchars($producto['sku'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                                <?php if ($producto['marca']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($producto['marca']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-tipo bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $producto['tipo_producto'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $producto['stock_actual'] !== null ? number_format($producto['stock_actual'], 2) : 'N/A'; ?></td>
                                            <td>$<?php echo number_format($producto['costo_compra'] ?? 0, 0, ',', '.'); ?></td>
                                            <td>$<?php echo number_format($producto['precio_venta_neto'] ?? 0, 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $producto['estado']; ?>">
                                                    <?php echo ucfirst($producto['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info action-btn" onclick="verProducto(<?php echo $producto['id']; ?>)"
                                                        title="Ver Ficha">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning action-btn" onclick="editarProducto(<?php echo $producto['id']; ?>)"
                                                        title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal: Crear Producto -->
    <div class="modal fade" id="modalCrearProducto" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Nuevo Producto/Servicio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCrearProducto" method="POST" enctype="multipart/form-data">
                        <!-- Tabs de Secciones -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seccion1" type="button">
                                    1. General
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion2" type="button">
                                    2. Inventario
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion3" type="button">
                                    3. Costos/Precios
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seccion4" type="button">
                                    4. Imagen
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- SECCIÓN 1: GENERAL -->
                            <div class="tab-pane fade show active" id="seccion1">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Código Interno</label>
                                        <input type="text" class="form-control" name="codigo_interno">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">SKU</label>
                                        <input type="text" class="form-control" name="sku">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Código de Barras</label>
                                        <input type="text" class="form-control" name="codigo_barras">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nombre del Producto *</label>
                                        <input type="text" class="form-control" name="nombre" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo de Producto *</label>
                                        <select class="form-select" name="tipo_producto" required>
                                            <option value="inventariable">Producto Inventariable</option>
                                            <option value="no_inventariable">Servicio / No Inventariable</option>
                                            <option value="materia_prima">Materia Prima</option>
                                            <option value="producto_terminado">Producto Terminado</option>
                                            <option value="kit">Kit / Pack / Combo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Unidad de Medida</label>
                                        <input type="text" class="form-control" name="unidad_medida" placeholder="Ej: UN, KG, M">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Marca</label>
                                        <input type="text" class="form-control" name="marca">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Modelo</label>
                                        <input type="text" class="form-control" name="modelo">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="estado">
                                            <option value="activo">Activo</option>
                                            <option value="inactivo">Inactivo</option>
                                            <option value="descontinuado">Descontinuado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Grupo de Productos</label>
                                        <input type="text" class="form-control" name="grupo_producto">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Línea de Productos</label>
                                        <input type="text" class="form-control" name="linea_producto">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo Tributario</label>
                                        <select class="form-select" name="tipo_tributario">
                                            <option value="afecto">Afecto a IVA</option>
                                            <option value="exento">Exento de IVA</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo de Costo</label>
                                        <select class="form-select" name="tipo_costo">
                                            <option value="promedio">Costo Promedio Ponderado</option>
                                            <option value="fifo">FIFO</option>
                                            <option value="estandar">Costo Estándar</option>
                                            <option value="manual">Manual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Descripción Corta</label>
                                        <textarea class="form-control" name="descripcion_corta" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: INVENTARIO -->
                            <div class="tab-pane fade" id="seccion2">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Stock Actual</label>
                                        <input type="number" class="form-control" name="stock_actual" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Stock Mínimo</label>
                                        <input type="number" class="form-control" name="stock_minimo" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Stock Máximo</label>
                                        <input type="number" class="form-control" name="stock_maximo" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Punto de Reorden</label>
                                        <input type="number" class="form-control" name="punto_reorden" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ubicación Física</label>
                                        <input type="text" class="form-control" name="ubicacion_fisica" placeholder="Pasillo, Estante, Nivel">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Peso Neto (kg)</label>
                                        <input type="number" class="form-control" name="peso_neto" value="0" step="0.001">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Volumen (m³)</label>
                                        <input type="number" class="form-control" name="volumen" value="0" step="0.001">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="control_lotes" id="controlLotes">
                                            <label class="form-check-label" for="controlLotes">
                                                Control de Lotes/Series
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="control_vencimiento" id="controlVencimiento">
                                            <label class="form-check-label" for="controlVencimiento">
                                                Control de Vencimiento
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 3: COSTOS Y PRECIOS -->
                            <div class="tab-pane fade" id="seccion3">
                                <div class="row g-3">
                                    <div class="col-md-12"><h6>Costos</h6></div>
                                    <div class="col-md-4">
                                        <label class="form-label">Costo de Compra</label>
                                        <input type="number" class="form-control" name="costo_compra" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Costo de Producción</label>
                                        <input type="number" class="form-control" name="costo_produccion" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Costo Estándar</label>
                                        <input type="number" class="form-control" name="costo_estandar" value="0" step="0.01">
                                    </div>

                                    <div class="col-md-12"><h6>Precios</h6></div>
                                    <div class="col-md-3">
                                        <label class="form-label">Precio Venta Neto</label>
                                        <input type="number" class="form-control" name="precio_venta_neto" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">IVA (%)</label>
                                        <input type="number" class="form-control" name="impuesto_porcentaje" value="19" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Precio WEB</label>
                                        <input type="number" class="form-control" name="precio_web" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Precio POS</label>
                                        <input type="number" class="form-control" name="precio_pos" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Precio Mayorista</label>
                                        <input type="number" class="form-control" name="precio_mayorista" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Precio Distribuidor</label>
                                        <input type="number" class="form-control" name="precio_distribuidor" value="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 4: IMAGEN -->
                            <div class="tab-pane fade" id="seccion4">
                                <div class="row g-3">
                                    <div class="col-md-12 text-center">
                                        <label class="form-label d-block">Imagen Principal del Producto</label>
                                        <p class="text-muted small">Formatos: JPG, PNG | Resolución mínima: 800x800px</p>
                                        <input type="file" class="form-control" name="imagen_principal" accept="image/jpeg,image/jpg,image/png">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Ver Ficha del Producto -->
    <div class="modal fade" id="modalVerProducto" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-box me-2"></i>
                        Ficha del Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div id="productoImagen"></div>
                            <h5 class="mt-3" id="productoNombre"></h5>
                            <p class="text-muted" id="productoSKU"></p>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Código:</label>
                                        <div class="value" id="productoCodigo">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Tipo:</label>
                                        <div class="value" id="productoTipo">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Estado:</label>
                                        <div class="value" id="productoEstado">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Stock:</label>
                                        <div class="value" id="productoStock">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Costo:</label>
                                        <div class="value" id="productoCosto">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label>Precio:</label>
                                        <div class="value" id="productoPrecio">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================
        // DATOS DEL SERVIDOR (JAVASCRIPT)
        // ============================================
        const productosData = <?php echo json_encode($productos); ?>;

        // ============================================
        // FUNCIONES GLOBALES PARA ONCLICK
        // ============================================

        // Ver Ficha Completa
        function verProducto(id) {
            const producto = productosData.find(p => p.id == id);
            if (producto) {
                // Imagen
                const imagenDiv = document.getElementById('productoImagen');
                if (producto.imagen_principal_url) {
                    imagenDiv.innerHTML = '<img src="' + producto.imagen_principal_url + '" style="width: 180px; height: 180px; object-fit: cover; border-radius: 8px;" alt="Imagen">';
                } else {
                    imagenDiv.innerHTML = '<div style="width: 180px; height: 180px; border-radius: 8px; background: #e5e7eb; display: flex; align-items: center; justify-content: center;"><i class="fas fa-box fa-4x text-muted"></i></div>';
                }

                // Datos
                document.getElementById('productoNombre').textContent = producto.nombre;
                document.getElementById('productoSKU').textContent = 'SKU: ' + (producto.sku || 'N/A');
                document.getElementById('productoCodigo').textContent = producto.codigo_interno || 'N/A';
                document.getElementById('productoTipo').textContent = producto.tipo_producto.replace('_', ' ');
                document.getElementById('productoEstado').innerHTML =
                    '<span class="badge badge-' + producto.estado + '">' + producto.estado.toUpperCase() + '</span>';
                document.getElementById('productoStock').textContent = producto.stock_actual !== null ? parseFloat(producto.stock_actual).toFixed(2) : 'N/A';
                document.getElementById('productoCosto').textContent = '$' + (producto.costo_compra || 0).toLocaleString('es-CL');
                document.getElementById('productoPrecio').textContent = '$' + (producto.precio_venta_neto || 0).toLocaleString('es-CL');

                const modal = new bootstrap.Modal(document.getElementById('modalVerProducto'));
                modal.show();
            }
        }

        // Editar Producto
        function editarProducto(id) {
            alert('Funcionalidad de edición en desarrollo para ID: ' + id);
        }

        // ============================================
        // DOM READY
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            // Fullscreen toggle
            const fullscreenBtn = document.getElementById('fullscreenToggle');
            const icon = fullscreenBtn.querySelector('i');

            fullscreenBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.toggle('fullscreen-mode');

                if (document.body.classList.contains('fullscreen-mode')) {
                    icon.classList.remove('fa-expand');
                    icon.classList.add('fa-compress');
                } else {
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            });

            // ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.body.classList.contains('fullscreen-mode')) {
                    document.body.classList.remove('fullscreen-mode');
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            });
        });
    </script>
</body>
</html>
