<?php
/**
 * SIDEBAR COMPLETO CON MÓDULOS COLAPSABLES
 * CONECTA ERP - Estilo Softland
 */
?>
<style>
    /* Sidebar Styles */
    :root {
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 70px;
    }

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transition: all 0.3s ease;
        z-index: 1000;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .sidebar-header {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: sticky;
        top: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        z-index: 10;
    }

    .sidebar-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        white-space: nowrap;
    }

    .sidebar.collapsed .sidebar-header h2 .text {
        display: none;
    }

    .sidebar-menu {
        list-style: none;
        padding: 10px 0;
        margin: 0;
    }

    .sidebar-menu > li {
        margin: 3px 0;
    }

    /* Menu Item */
    .sidebar-menu a,
    .sidebar-menu .menu-toggle {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        background: none;
        border: none;
        width: 100%;
        text-align: left;
    }

    .sidebar-menu a:hover,
    .sidebar-menu .menu-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.2);
        border-left: 4px solid white;
        color: white;
    }

    .sidebar-menu a i,
    .sidebar-menu .menu-toggle i {
        font-size: 1.1rem;
        min-width: 25px;
        text-align: center;
    }

    .sidebar-menu a span,
    .sidebar-menu .menu-toggle span {
        margin-left: 12px;
        flex: 1;
        white-space: nowrap;
    }

    .sidebar.collapsed .sidebar-menu a span,
    .sidebar.collapsed .sidebar-menu .menu-toggle span {
        display: none;
    }

    /* Submenu Toggle Arrow */
    .menu-arrow {
        margin-left: auto;
        transition: transform 0.3s ease;
        font-size: 0.8rem;
    }

    .menu-toggle.active .menu-arrow {
        transform: rotate(90deg);
    }

    .sidebar.collapsed .menu-arrow {
        display: none;
    }

    /* Submenu */
    .submenu {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background: rgba(0, 0, 0, 0.1);
    }

    .submenu.show {
        max-height: 1000px;
    }

    .sidebar.collapsed .submenu {
        display: none;
    }

    .submenu li a {
        padding: 10px 20px 10px 55px;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .submenu li a:hover {
        background: rgba(255, 255, 255, 0.15);
        color: white;
    }

    .submenu li a.active {
        background: rgba(255, 255, 255, 0.25);
        color: white;
        border-left: 4px solid white;
    }

    /* Badge for counters */
    .menu-badge {
        margin-left: auto;
        background: rgba(255, 255, 255, 0.3);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .sidebar.collapsed .menu-badge {
        display: none;
    }

    /* Divider */
    .menu-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 10px 20px;
    }

    .menu-label {
        padding: 15px 20px 5px 20px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 600;
    }

    .sidebar.collapsed .menu-label {
        display: none;
    }
</style>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>
            <i class="fas fa-rocket"></i>
            <span class="text">CONECTA ERP</span>
        </h2>
    </div>

    <ul class="sidebar-menu">
        <!-- Dashboard -->
        <li>
            <a href="/user/dashboard_user.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_user.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <div class="menu-divider"></div>
        <div class="menu-label">Módulos ERP</div>

        <!-- VENTAS -->
        <li>
            <button class="menu-toggle" onclick="toggleSubmenu('ventas')">
                <i class="fas fa-shopping-cart"></i>
                <span>Ventas</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </button>
            <ul class="submenu" id="submenu-ventas">
                <li><a href="/modulos/ventas/facturas.php"><i class="fas fa-file-invoice"></i> Facturación</a></li>
                <li><a href="/modulos/ventas/boletas.php"><i class="fas fa-receipt"></i> Boletas</a></li>
                <li><a href="/modulos/ventas/notas_credito.php"><i class="fas fa-file-invoice-dollar"></i> Notas de Crédito</a></li>
                <li><a href="/modulos/ventas/guias_despacho.php"><i class="fas fa-truck"></i> Guías de Despacho</a></li>
                <li><a href="/modulos/ventas/cotizaciones.php"><i class="fas fa-file-alt"></i> Cotizaciones</a></li>
                <li><a href="/modulos/ventas/clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            </ul>
        </li>

        <!-- COMPRAS -->
        <li>
            <button class="menu-toggle" onclick="toggleSubmenu('compras')">
                <i class="fas fa-shopping-bag"></i>
                <span>Compras</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </button>
            <ul class="submenu" id="submenu-compras">
                <li><a href="/modulos/compras/facturas_compra.php"><i class="fas fa-file-invoice"></i> Facturas de Compra</a></li>
                <li><a href="/modulos/compras/ordenes_compra.php"><i class="fas fa-clipboard-list"></i> Órdenes de Compra</a></li>
                <li><a href="/modulos/compras/proveedores.php"><i class="fas fa-truck-loading"></i> Proveedores</a></li>
                <li><a href="/modulos/compras/libro_compras.php"><i class="fas fa-book"></i> Libro de Compras</a></li>
            </ul>
        </li>

        <!-- CONTABILIDAD -->
        <li>
            <button class="menu-toggle" onclick="toggleSubmenu('contabilidad')">
                <i class="fas fa-calculator"></i>
                <span>Contabilidad</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </button>
            <ul class="submenu" id="submenu-contabilidad">
                <li><a href="/modulos/contabilidad/plan_cuentas.php"><i class="fas fa-list-ol"></i> Plan de Cuentas</a></li>
                <li><a href="/modulos/contabilidad/asientos.php"><i class="fas fa-edit"></i> Asientos Contables</a></li>
                <li><a href="/modulos/contabilidad/libro_mayor.php"><i class="fas fa-book-open"></i> Libro Mayor</a></li>
                <li><a href="/modulos/contabilidad/libro_diario.php"><i class="fas fa-calendar-alt"></i> Libro Diario</a></li>
                <li><a href="/modulos/contabilidad/balances.php"><i class="fas fa-balance-scale"></i> Balances</a></li>
                <li><a href="/modulos/contabilidad/cuadraturas.php"><i class="fas fa-check-double"></i> Cuadraturas</a></li>
                <li><a href="/modulos/contabilidad/declaraciones.php"><i class="fas fa-file-signature"></i> Declaraciones Juradas</a></li>
                <li><a href="/modulos/contabilidad/f29.php"><i class="fas fa-file-invoice-dollar"></i> Formulario 29</a></li>
            </ul>
        </li>

        <!-- RRHH -->
        <li>
            <button class="menu-toggle" onclick="toggleSubmenu('rrhh')">
                <i class="fas fa-users-cog"></i>
                <span>Recursos Humanos</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </button>
            <ul class="submenu" id="submenu-rrhh">
                <li><a href="/modulos/rrhh/empleados.php"><i class="fas fa-user-tie"></i> Empleados</a></li>
                <li><a href="/modulos/rrhh/nomina.php"><i class="fas fa-money-check-alt"></i> Nómina</a></li>
                <li><a href="/modulos/rrhh/asistencia.php"><i class="fas fa-clock"></i> Asistencia</a></li>
                <li><a href="/modulos/rrhh/vacaciones.php"><i class="fas fa-umbrella-beach"></i> Vacaciones</a></li>
                <li><a href="/modulos/rrhh/liquidaciones.php"><i class="fas fa-file-invoice"></i> Liquidaciones</a></li>
                <li><a href="/modulos/rrhh/previred.php"><i class="fas fa-file-upload"></i> Previred</a></li>
                <li><a href="/modulos/rrhh/contratos.php"><i class="fas fa-file-contract"></i> Contratos</a></li>
            </ul>
        </li>

        <!-- INVENTARIO -->
        <li>
            <button class="menu-toggle" onclick="toggleSubmenu('inventario')">
                <i class="fas fa-boxes"></i>
                <span>Inventario</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </button>
            <ul class="submenu" id="submenu-inventario">
                <li><a href="/modulos/inventario/productos.php"><i class="fas fa-cube"></i> Productos</a></li>
                <li><a href="/modulos/inventario/categorias.php"><i class="fas fa-tags"></i> Categorías</a></li>
                <li><a href="/modulos/inventario/stock.php"><i class="fas fa-warehouse"></i> Control de Stock</a></li>
                <li><a href="/modulos/inventario/movimientos.php"><i class="fas fa-exchange-alt"></i> Movimientos</a></li>
                <li><a href="/modulos/inventario/ajustes.php"><i class="fas fa-sliders-h"></i> Ajustes de Inventario</a></li>
            </ul>
        </li>

        <!-- REPORTES -->
        <li>
            <button class="menu-toggle" onclick="toggleSubmenu('reportes')">
                <i class="fas fa-chart-line"></i>
                <span>Reportes</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </button>
            <ul class="submenu" id="submenu-reportes">
                <li><a href="/modulos/reportes/ventas.php"><i class="fas fa-chart-bar"></i> Ventas</a></li>
                <li><a href="/modulos/reportes/compras.php"><i class="fas fa-chart-area"></i> Compras</a></li>
                <li><a href="/modulos/reportes/financieros.php"><i class="fas fa-money-bill-wave"></i> Financieros</a></li>
                <li><a href="/modulos/reportes/impuestos.php"><i class="fas fa-percent"></i> Impuestos</a></li>
                <li><a href="/modulos/reportes/rrhh.php"><i class="fas fa-users"></i> RRHH</a></li>
                <li><a href="/modulos/reportes/inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
            </ul>
        </li>

        <!-- CONFIGURACIÓN TRIBUTARIA -->
        <li>
            <button class="menu-toggle" onclick="toggleSubmenu('tributario')">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>SII y Tributario</span>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </button>
            <ul class="submenu" id="submenu-tributario">
                <li><a href="/modulos/tributario/folios.php"><i class="fas fa-barcode"></i> Folios CAF</a></li>
                <li><a href="/modulos/tributario/dte.php"><i class="fas fa-file-signature"></i> Documentos Tributarios</a></li>
                <li><a href="/modulos/tributario/libro_ventas.php"><i class="fas fa-book"></i> Libro de Ventas</a></li>
                <li><a href="/modulos/tributario/libro_compras.php"><i class="fas fa-book-open"></i> Libro de Compras</a></li>
                <li><a href="/modulos/tributario/certificados.php"><i class="fas fa-certificate"></i> Certificados Digitales</a></li>
                <li><a href="/modulos/tributario/indicadores.php"><i class="fas fa-dollar-sign"></i> Indicadores Económicos</a></li>
            </ul>
        </li>

        <div class="menu-divider"></div>
        <div class="menu-label">Configuración</div>

        <!-- CONFIGURACIÓN -->
        <li>
            <a href="/user/mi-empresa.php" <?php echo basename($_SERVER['PHP_SELF']) == 'mi-empresa.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-building"></i>
                <span>Mi Empresa</span>
            </a>
        </li>

        <li>
            <a href="/user/perfil.php" <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-user"></i>
                <span>Mi Perfil</span>
            </a>
        </li>

        <li>
            <a href="/user/suscripcion.php" <?php echo basename($_SERVER['PHP_SELF']) == 'suscripcion.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-credit-card"></i>
                <span>Suscripción</span>
            </a>
        </li>

        <li>
            <a href="/user/notificaciones.php" <?php echo basename($_SERVER['PHP_SELF']) == 'notificaciones.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-bell"></i>
                <span>Notificaciones</span>
                <?php if (isset($notificaciones_count) && $notificaciones_count > 0): ?>
                    <span class="menu-badge"><?php echo $notificaciones_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li>
            <a href="/user/configuracion.php" <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </li>

        <li>
            <a href="/user/soporte.php" <?php echo basename($_SERVER['PHP_SELF']) == 'soporte.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-headset"></i>
                <span>Soporte</span>
            </a>
        </li>

        <div class="menu-divider"></div>

        <li>
            <a href="/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</aside>

<script>
function toggleSubmenu(menuId) {
    const submenu = document.getElementById('submenu-' + menuId);
    const toggle = event.currentTarget;

    // Toggle active class
    toggle.classList.toggle('active');

    // Toggle submenu
    submenu.classList.toggle('show');

    // Save state to localStorage
    const isOpen = submenu.classList.contains('show');
    localStorage.setItem('submenu-' + menuId, isOpen ? 'open' : 'closed');
}

// Restore submenu states on page load
document.addEventListener('DOMContentLoaded', function() {
    const submenus = ['ventas', 'compras', 'contabilidad', 'rrhh', 'inventario', 'reportes', 'tributario'];

    submenus.forEach(menuId => {
        const state = localStorage.getItem('submenu-' + menuId);
        if (state === 'open') {
            const submenu = document.getElementById('submenu-' + menuId);
            const toggle = submenu.previousElementSibling;
            if (submenu && toggle) {
                submenu.classList.add('show');
                toggle.classList.add('active');
            }
        }
    });

    // Auto-open parent menu if child is active
    document.querySelectorAll('.submenu a.active').forEach(activeLink => {
        const submenu = activeLink.closest('.submenu');
        if (submenu) {
            submenu.classList.add('show');
            const toggle = submenu.previousElementSibling;
            if (toggle) {
                toggle.classList.add('active');
            }
        }
    });
});

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    localStorage.setItem('sidebar-collapsed',
        document.getElementById('sidebar').classList.contains('collapsed') ? 'true' : 'false'
    );
}

// Restore sidebar state
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.getElementById('sidebar').classList.add('collapsed');
    }
});
</script>
