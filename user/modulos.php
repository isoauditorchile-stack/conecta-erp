<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT empresa_id, nombre FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$empresa_id = $user['empresa_id'];

// Definición completa de módulos del sistema
$modulos_sistema = [
    'Ventas' => [
        'icon' => 'fa-shopping-cart',
        'color' => 'primary',
        'modulos' => [
            ['nombre' => 'Facturas', 'url' => '../modulos/ventas/facturas.php', 'icono' => 'fa-file-invoice-dollar', 'desc' => 'Gestión de facturas de venta'],
            ['nombre' => 'Cotizaciones', 'url' => '../modulos/ventas/cotizaciones.php', 'icono' => 'fa-file-alt', 'desc' => 'Crear y gestionar cotizaciones'],
            ['nombre' => 'Notas de Venta', 'url' => '../modulos/ventas/notas_venta.php', 'icono' => 'fa-receipt', 'desc' => 'Registro de ventas directas'],
            ['nombre' => 'Guías de Despacho', 'url' => '../modulos/ventas/guias_despacho.php', 'icono' => 'fa-truck', 'desc' => 'Guías de entrega de productos'],
        ]
    ],
    'Compras' => [
        'icon' => 'fa-box',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'Órdenes de Compra', 'url' => '../modulos/compras/ordenes_compra.php', 'icono' => 'fa-shopping-bag', 'desc' => 'Gestión de compras a proveedores'],
            ['nombre' => 'Recepciones', 'url' => '../modulos/compras/recepciones.php', 'icono' => 'fa-dolly', 'desc' => 'Recepción de mercadería'],
            ['nombre' => 'Facturas Proveedores', 'url' => '../modulos/compras/facturas_proveedores.php', 'icono' => 'fa-file-invoice', 'desc' => 'Facturas recibidas de proveedores'],
        ]
    ],
    'Inventario' => [
        'icon' => 'fa-boxes',
        'color' => 'warning',
        'modulos' => [
            ['nombre' => 'Productos', 'url' => '../modulos/inventario/productos.php', 'icono' => 'fa-barcode', 'desc' => 'Catálogo de productos'],
            ['nombre' => 'Categorías', 'url' => '../modulos/inventario/categorias.php', 'icono' => 'fa-tags', 'desc' => 'Categorías de productos'],
            ['nombre' => 'Bodegas', 'url' => '../modulos/inventario/bodegas.php', 'icono' => 'fa-warehouse', 'desc' => 'Gestión de bodegas'],
            ['nombre' => 'Movimientos', 'url' => '../modulos/inventario/movimientos.php', 'icono' => 'fa-exchange-alt', 'desc' => 'Movimientos de inventario'],
            ['nombre' => 'Ajustes', 'url' => '../modulos/inventario/ajustes.php', 'icono' => 'fa-balance-scale', 'desc' => 'Ajustes de stock'],
            ['nombre' => 'Inventario Físico', 'url' => '../modulos/inventario/inventario_fisico.php', 'icono' => 'fa-clipboard-list', 'desc' => 'Toma de inventario física'],
        ]
    ],
    'Clientes' => [
        'icon' => 'fa-users',
        'color' => 'info',
        'modulos' => [
            ['nombre' => 'Clientes', 'url' => '../modulos/clientes/clientes.php', 'icono' => 'fa-user-tie', 'desc' => 'Base de datos de clientes'],
            ['nombre' => 'Grupos de Clientes', 'url' => '../modulos/clientes/grupos.php', 'icono' => 'fa-users-cog', 'desc' => 'Clasificación de clientes'],
            ['nombre' => 'Historial', 'url' => '../modulos/clientes/historial.php', 'icono' => 'fa-history', 'desc' => 'Historial de compras'],
        ]
    ],
    'Proveedores' => [
        'icon' => 'fa-industry',
        'color' => 'secondary',
        'modulos' => [
            ['nombre' => 'Proveedores', 'url' => '../modulos/proveedores/proveedores.php', 'icono' => 'fa-building', 'desc' => 'Base de datos de proveedores'],
            ['nombre' => 'Evaluación', 'url' => '../modulos/proveedores/evaluacion.php', 'icono' => 'fa-star', 'desc' => 'Evaluación de desempeño'],
        ]
    ],
    'Contabilidad' => [
        'icon' => 'fa-calculator',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Plan de Cuentas', 'url' => '../modulos/contabilidad/plan_cuentas.php', 'icono' => 'fa-list-ol', 'desc' => 'Plan contable chileno'],
            ['nombre' => 'Asientos Contables', 'url' => '../modulos/contabilidad/asientos.php', 'icono' => 'fa-book', 'desc' => 'Registro de asientos'],
            ['nombre' => 'Libro Diario', 'url' => '../modulos/contabilidad/libro_diario.php', 'icono' => 'fa-calendar-day', 'desc' => 'Libro diario'],
            ['nombre' => 'Libro Mayor', 'url' => '../modulos/contabilidad/libro_mayor.php', 'icono' => 'fa-book-open', 'desc' => 'Libro mayor'],
            ['nombre' => 'Balance', 'url' => '../modulos/contabilidad/balance.php', 'icono' => 'fa-balance-scale-right', 'desc' => 'Balance general'],
            ['nombre' => 'Estado de Resultados', 'url' => '../modulos/contabilidad/estado_resultados.php', 'icono' => 'fa-chart-line', 'desc' => 'P&L del período'],
        ]
    ],
    'Tesorería' => [
        'icon' => 'fa-money-bill-wave',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'Cuentas Bancarias', 'url' => '../modulos/tesoreria/cuentas_bancarias.php', 'icono' => 'fa-university', 'desc' => 'Gestión de cuentas'],
            ['nombre' => 'Ingresos', 'url' => '../modulos/tesoreria/ingresos.php', 'icono' => 'fa-arrow-down', 'desc' => 'Registro de ingresos'],
            ['nombre' => 'Egresos', 'url' => '../modulos/tesoreria/egresos.php', 'icono' => 'fa-arrow-up', 'desc' => 'Registro de egresos'],
            ['nombre' => 'Conciliación Bancaria', 'url' => '../modulos/tesoreria/conciliacion.php', 'icono' => 'fa-check-double', 'desc' => 'Conciliación de cuentas'],
            ['nombre' => 'Flujo de Caja', 'url' => '../modulos/tesoreria/flujo_caja.php', 'icono' => 'fa-water', 'desc' => 'Flujo de caja proyectado'],
        ]
    ],
    'Recursos Humanos' => [
        'icon' => 'fa-user-friends',
        'color' => 'primary',
        'modulos' => [
            ['nombre' => 'Empleados', 'url' => '../modulos/rrhh/empleados.php', 'icono' => 'fa-id-badge', 'desc' => 'Base de datos de empleados'],
            ['nombre' => 'Liquidaciones', 'url' => '../modulos/rrhh/liquidaciones.php', 'icono' => 'fa-file-invoice-dollar', 'desc' => 'Liquidaciones de sueldo'],
            ['nombre' => 'Contratos', 'url' => '../modulos/rrhh/contratos.php', 'icono' => 'fa-file-contract', 'desc' => 'Contratos de trabajo'],
            ['nombre' => 'Asistencia', 'url' => '../modulos/rrhh/asistencia.php', 'icono' => 'fa-calendar-check', 'desc' => 'Control de asistencia'],
            ['nombre' => 'Vacaciones', 'url' => '../modulos/rrhh/vacaciones.php', 'icono' => 'fa-umbrella-beach', 'desc' => 'Gestión de vacaciones'],
        ]
    ],
    'Reloj Control' => [
        'icon' => 'fa-clock',
        'color' => 'info',
        'modulos' => [
            ['nombre' => 'Marcajes', 'url' => '../modulos/reloj/marcajes.php', 'icono' => 'fa-fingerprint', 'desc' => 'Control de marcajes'],
            ['nombre' => 'Dispositivos', 'url' => '../modulos/reloj/dispositivos.php', 'icono' => 'fa-tablet-alt', 'desc' => 'Dispositivos biométricos'],
            ['nombre' => 'Turnos', 'url' => '../modulos/reloj/turnos.php', 'icono' => 'fa-user-clock', 'desc' => 'Gestión de turnos'],
            ['nombre' => 'Horarios', 'url' => '../modulos/reloj/horarios.php', 'icono' => 'fa-calendar-alt', 'desc' => 'Horarios de trabajo'],
        ]
    ],
    'Proyectos' => [
        'icon' => 'fa-project-diagram',
        'color' => 'danger',
        'modulos' => [
            ['nombre' => 'Proyectos', 'url' => '../modulos/proyectos/proyectos.php', 'icono' => 'fa-folder-open', 'desc' => 'Gestión de proyectos'],
            ['nombre' => 'Tareas', 'url' => '../modulos/proyectos/tareas.php', 'icono' => 'fa-tasks', 'desc' => 'Tareas con Kanban/Gantt'],
            ['nombre' => 'Hitos', 'url' => '../modulos/proyectos/hitos.php', 'icono' => 'fa-flag-checkered', 'desc' => 'Hitos y entregables'],
        ]
    ],
    'Ecommerce' => [
        'icon' => 'fa-store',
        'color' => 'warning',
        'modulos' => [
            ['nombre' => 'Tienda Online', 'url' => '../modulos/ecommerce/tienda.php', 'icono' => 'fa-shopping-bag', 'desc' => 'Gestión de tienda'],
            ['nombre' => 'Pedidos Web', 'url' => '../modulos/ecommerce/pedidos.php', 'icono' => 'fa-shopping-cart', 'desc' => 'Pedidos online'],
            ['nombre' => 'Carritos', 'url' => '../modulos/ecommerce/carritos.php', 'icono' => 'fa-cart-plus', 'desc' => 'Carritos abandonados'],
            ['nombre' => 'Métodos de Pago', 'url' => '../modulos/ecommerce/metodos_pago.php', 'icono' => 'fa-credit-card', 'desc' => 'Configurar pagos'],
            ['nombre' => 'Envíos', 'url' => '../modulos/ecommerce/envios.php', 'icono' => 'fa-shipping-fast', 'desc' => 'Métodos de envío'],
        ]
    ],
    'BI Avanzado' => [
        'icon' => 'fa-chart-bar',
        'color' => 'success',
        'modulos' => [
            ['nombre' => 'KPIs', 'url' => '../modulos/bi_avanzado/kpis.php', 'icono' => 'fa-tachometer-alt', 'desc' => 'Indicadores clave'],
            ['nombre' => 'Métricas', 'url' => '../modulos/bi_avanzado/metricas.php', 'icono' => 'fa-chart-line', 'desc' => 'Métricas en tiempo real'],
            ['nombre' => 'Dashboards', 'url' => '../modulos/bi_avanzado/dashboards_personalizados.php', 'icono' => 'fa-chart-pie', 'desc' => 'Dashboards personalizados'],
        ]
    ],
    'API & Reportes' => [
        'icon' => 'fa-plug',
        'color' => 'secondary',
        'modulos' => [
            ['nombre' => 'Tokens API', 'url' => '../modulos/api/tokens.php', 'icono' => 'fa-key', 'desc' => 'Tokens de acceso API'],
            ['nombre' => 'Webhooks', 'url' => '../modulos/api/webhooks.php', 'icono' => 'fa-link', 'desc' => 'Webhooks de integración'],
            ['nombre' => 'Reportes Personalizados', 'url' => '../modulos/reportes/personalizados.php', 'icono' => 'fa-file-alt', 'desc' => 'Reportes SQL custom'],
        ]
    ],
    'Calidad' => [
        'icon' => 'fa-certificate',
        'color' => 'info',
        'modulos' => [
            ['nombre' => 'Control de Calidad', 'url' => '../modulos/calidad/control.php', 'icono' => 'fa-clipboard-check', 'desc' => 'Inspecciones ISO'],
        ]
    ],
    'Mantenimiento' => [
        'icon' => 'fa-tools',
        'color' => 'warning',
        'modulos' => [
            ['nombre' => 'Órdenes de Trabajo', 'url' => '../modulos/mantenimiento/ordenes.php', 'icono' => 'fa-wrench', 'desc' => 'Preventivo/Correctivo'],
        ]
    ],
    'Configuración' => [
        'icon' => 'fa-cog',
        'color' => 'dark',
        'modulos' => [
            ['nombre' => 'Empresa', 'url' => '../modulos/configuracion/empresa.php', 'icono' => 'fa-building', 'desc' => 'Datos de la empresa'],
            ['nombre' => 'Sucursales', 'url' => '../modulos/configuracion/sucursales.php', 'icono' => 'fa-map-marker-alt', 'desc' => 'Sucursales y locales'],
            ['nombre' => 'Tipos de Cambio', 'url' => '../modulos/configuracion/tipos_cambio.php', 'icono' => 'fa-exchange-alt', 'desc' => 'USD, EUR, UF, UTM'],
            ['nombre' => 'Monedas', 'url' => '../modulos/configuracion/monedas.php', 'icono' => 'fa-coins', 'desc' => 'Monedas del sistema'],
            ['nombre' => 'Impuestos', 'url' => '../modulos/configuracion/impuestos.php', 'icono' => 'fa-percentage', 'desc' => 'IVA y otros impuestos'],
        ]
    ],
];

// Contar módulos totales
$total_modulos = 0;
foreach ($modulos_sistema as $categoria => $datos) {
    $total_modulos += count($datos['modulos']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos los Módulos - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .module-card { 
            border: none; 
            border-radius: 12px; 
            transition: all 0.3s; 
            cursor: pointer; 
            height: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .module-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .category-header {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .search-box {
            border-radius: 50px;
            padding: 12px 24px;
            border: 2px solid #e0e0e0;
        }
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <!-- Header -->
    <div class="header-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-3">
                    <i class="fas fa-th-large text-primary"></i> 
                    Todos los Módulos del Sistema
                </h1>
                <p class="lead text-muted mb-0">Explora las <?= count($modulos_sistema) ?> categorías con <?= $total_modulos ?> módulos disponibles</p>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="mb-0"><?= $total_modulos ?></h2>
                    <small>Módulos Activos</small>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <input type="text" id="searchModules" class="form-control search-box" placeholder="🔍 Buscar módulo...">
        </div>

        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Módulos por Categoría -->
    <div id="modulesContainer">
    <?php foreach ($modulos_sistema as $categoria => $datos): ?>
        <div class="categoria-section mb-5" data-categoria="<?= strtolower($categoria) ?>">
            <div class="category-header">
                <h3 class="mb-0">
                    <i class="fas <?= $datos['icon'] ?> text-<?= $datos['color'] ?>"></i> 
                    <?= $categoria ?>
                    <span class="badge bg-<?= $datos['color'] ?> float-end"><?= count($datos['modulos']) ?></span>
                </h3>
            </div>

            <div class="row g-3">
                <?php foreach ($datos['modulos'] as $modulo): ?>
                <div class="col-md-3 modulo-item" 
                     data-nombre="<?= strtolower($modulo['nombre']) ?>" 
                     data-desc="<?= strtolower($modulo['desc']) ?>">
                    <div class="card module-card" onclick="window.location.href='<?= $modulo['url'] ?>'">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas <?= $modulo['icono'] ?> fa-3x text-<?= $datos['color'] ?>"></i>
                            </div>
                            <h5 class="card-title"><?= $modulo['nombre'] ?></h5>
                            <p class="card-text text-muted small"><?= $modulo['desc'] ?></p>
                            <a href="<?= $modulo['url'] ?>" class="btn btn-sm btn-<?= $datos['color'] ?>">
                                Abrir <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- No results -->
    <div id="noResults" class="text-center text-white" style="display: none;">
        <i class="fas fa-search fa-5x mb-3 opacity-50"></i>
        <h3>No se encontraron módulos</h3>
        <p>Intenta con otros términos de búsqueda</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Búsqueda en tiempo real
document.getElementById('searchModules').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const categorias = document.querySelectorAll('.categoria-section');
    const modulos = document.querySelectorAll('.modulo-item');
    let hasResults = false;

    if (searchTerm === '') {
        // Mostrar todo
        categorias.forEach(cat => cat.style.display = 'block');
        modulos.forEach(mod => mod.style.display = 'block');
        document.getElementById('noResults').style.display = 'none';
        return;
    }

    // Filtrar módulos
    modulos.forEach(modulo => {
        const nombre = modulo.dataset.nombre;
        const desc = modulo.dataset.desc;
        
        if (nombre.includes(searchTerm) || desc.includes(searchTerm)) {
            modulo.style.display = 'block';
            hasResults = true;
        } else {
            modulo.style.display = 'none';
        }
    });

    // Mostrar/ocultar categorías vacías
    categorias.forEach(cat => {
        const visibleModulos = cat.querySelectorAll('.modulo-item[style="display: block;"]').length;
        cat.style.display = visibleModulos > 0 ? 'block' : 'none';
    });

    // Mostrar mensaje de no resultados
    document.getElementById('noResults').style.display = hasResults ? 'none' : 'block';
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
