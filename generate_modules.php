<?php
/**
 * CONECTA ERP - Generador Automático de Módulos
 * Este script genera automáticamente los 106 submódulos del sistema
 */

// Definición de estructura de módulos
$modules_structure = [
    'FI' => [
        'name' => 'Finanzas',
        'color' => '#3b82f6',
        'icon' => 'fa-chart-line',
        'submodules' => [
            'general_ledger' => ['name' => 'Contabilidad General', 'icon' => 'fa-book', 'table' => 'fi_general_ledger'],
            'accounts_receivable' => ['name' => 'Cuentas por Cobrar', 'icon' => 'fa-hand-holding-usd', 'table' => 'fi_accounts_receivable'],
            'accounts_payable' => ['name' => 'Cuentas por Pagar', 'icon' => 'fa-file-invoice-dollar', 'table' => 'fi_accounts_payable'],
            'treasury' => ['name' => 'Tesorería', 'icon' => 'fa-university', 'table' => 'fi_treasury'],
            'fixed_assets' => ['name' => 'Activos Fijos', 'icon' => 'fa-building', 'table' => 'fi_fixed_assets'],
            'banks' => ['name' => 'Bancos y Conciliaciones', 'icon' => 'fa-landmark', 'table' => 'fi_bank_reconciliation'],
            'accounting_books' => ['name' => 'Libros Contables', 'icon' => 'fa-book-open', 'table' => 'fi_accounting_books'],
            'ifrs' => ['name' => 'IFRS Reporting', 'icon' => 'fa-chart-bar', 'table' => 'fi_ifrs_reports'],
            'journal_entries' => ['name' => 'Asientos Contables', 'icon' => 'fa-pen-alt', 'table' => 'fi_journal_entries'],
            'closing' => ['name' => 'Cierres Contables', 'icon' => 'fa-lock', 'table' => 'fi_closing_periods'],
            'reports' => ['name' => 'Reportes Financieros', 'icon' => 'fa-file-pdf', 'table' => 'fi_financial_reports'],
        ]
    ],
    'CO' => [
        'name' => 'Controlling',
        'color' => '#8b5cf6',
        'icon' => 'fa-chart-pie',
        'submodules' => [
            'cost_centers' => ['name' => 'Centros de Costo', 'icon' => 'fa-sitemap', 'table' => 'co_cost_centers'],
            'internal_orders' => ['name' => 'Órdenes Internas', 'icon' => 'fa-clipboard-list', 'table' => 'co_internal_orders'],
            'profitability' => ['name' => 'Análisis de Rentabilidad', 'icon' => 'fa-chart-line', 'table' => 'co_profitability_analysis'],
            'budgets' => ['name' => 'Presupuestos', 'icon' => 'fa-calculator', 'table' => 'co_budgets'],
            'expenses' => ['name' => 'Control de Gastos', 'icon' => 'fa-receipt', 'table' => 'co_expense_control'],
            'abc_costing' => ['name' => 'Costos ABC', 'icon' => 'fa-project-diagram', 'table' => 'co_abc_costing'],
            'projects' => ['name' => 'Análisis de Proyectos', 'icon' => 'fa-tasks', 'table' => 'co_project_analysis'],
            'investments' => ['name' => 'Control de Inversiones', 'icon' => 'fa-money-bill-wave', 'table' => 'co_investment_control'],
        ]
    ],
    'SD' => [
        'name' => 'Ventas & Distribución',
        'color' => '#10b981',
        'icon' => 'fa-shopping-cart',
        'submodules' => [
            'customers' => ['name' => 'Clientes', 'icon' => 'fa-users', 'table' => 'sd_customers'],
            'quotations' => ['name' => 'Cotizaciones', 'icon' => 'fa-file-alt', 'table' => 'sd_quotations'],
            'sales_orders' => ['name' => 'Pedidos de Venta', 'icon' => 'fa-shopping-bag', 'table' => 'sd_sales_orders'],
            'invoices' => ['name' => 'Facturación', 'icon' => 'fa-file-invoice', 'table' => 'sd_invoices'],
            'pos' => ['name' => 'Punto de Venta', 'icon' => 'fa-cash-register', 'table' => 'sd_pos_transactions'],
            'ecommerce' => ['name' => 'E-commerce', 'icon' => 'fa-store', 'table' => 'sd_ecommerce_orders'],
            'pricing' => ['name' => 'Precios Dinámicos', 'icon' => 'fa-tags', 'table' => 'sd_dynamic_pricing'],
            'commissions' => ['name' => 'Comisiones', 'icon' => 'fa-percentage', 'table' => 'sd_commissions'],
            'analytics' => ['name' => 'Análisis de Ventas', 'icon' => 'fa-chart-area', 'table' => 'sd_sales_analytics'],
        ]
    ],
    'MM' => [
        'name' => 'Materiales',
        'color' => '#f59e0b',
        'icon' => 'fa-boxes',
        'submodules' => [
            'products' => ['name' => 'Productos', 'icon' => 'fa-box', 'table' => 'mm_products'],
            'warehouses' => ['name' => 'Almacenes', 'icon' => 'fa-warehouse', 'table' => 'mm_warehouses'],
            'inventory' => ['name' => 'Inventario', 'icon' => 'fa-cubes', 'table' => 'mm_inventory'],
            'suppliers' => ['name' => 'Proveedores', 'icon' => 'fa-truck-loading', 'table' => 'mm_suppliers'],
            'purchase_orders' => ['name' => 'Órdenes de Compra', 'icon' => 'fa-shopping-cart', 'table' => 'mm_purchase_orders'],
            'goods_receipt' => ['name' => 'Recepción', 'icon' => 'fa-dolly', 'table' => 'mm_goods_receipt'],
            'mrp' => ['name' => 'MRP', 'icon' => 'fa-cogs', 'table' => 'mm_mrp'],
            'traceability' => ['name' => 'Trazabilidad', 'icon' => 'fa-barcode', 'table' => 'mm_traceability'],
        ]
    ],
    'PP' => [
        'name' => 'Producción',
        'color' => '#ef4444',
        'icon' => 'fa-industry',
        'submodules' => [
            'production_orders' => ['name' => 'Órdenes de Producción', 'icon' => 'fa-industry', 'table' => 'pp_production_orders'],
            'bom' => ['name' => 'BOM', 'icon' => 'fa-list-alt', 'table' => 'pp_bom'],
            'routing' => ['name' => 'Rutinas', 'icon' => 'fa-route', 'table' => 'pp_routing'],
            'mrp_ii' => ['name' => 'MRP II', 'icon' => 'fa-project-diagram', 'table' => 'pp_mrp_ii'],
            'capacity' => ['name' => 'Capacidades', 'icon' => 'fa-chart-pie', 'table' => 'pp_capacity_planning'],
            'quality' => ['name' => 'Control de Calidad', 'icon' => 'fa-check-double', 'table' => 'pp_quality_control'],
            'maintenance' => ['name' => 'Mantenimiento', 'icon' => 'fa-tools', 'table' => 'pp_preventive_maintenance'],
            'formulas' => ['name' => 'Fórmulas', 'icon' => 'fa-flask', 'table' => 'pp_formulas'],
            'costs' => ['name' => 'Costos', 'icon' => 'fa-dollar-sign', 'table' => 'pp_production_costs'],
            'work_centers' => ['name' => 'Centros de Trabajo', 'icon' => 'fa-industry', 'table' => 'pp_production_orders'],
        ]
    ],
    'HCM' => [
        'name' => 'Capital Humano',
        'color' => '#ec4899',
        'icon' => 'fa-user-tie',
        'submodules' => [
            'employees' => ['name' => 'Empleados', 'icon' => 'fa-id-card', 'table' => 'hcm_employees'],
            'payroll' => ['name' => 'Nómina', 'icon' => 'fa-money-check-alt', 'table' => 'hcm_payroll'],
            'recruitment' => ['name' => 'Reclutamiento', 'icon' => 'fa-user-plus', 'table' => 'hcm_recruitment'],
            'onboarding' => ['name' => 'Onboarding', 'icon' => 'fa-handshake', 'table' => 'hcm_onboarding'],
            'performance' => ['name' => 'Evaluación', 'icon' => 'fa-star', 'table' => 'hcm_performance_reviews'],
            'training' => ['name' => 'Capacitación', 'icon' => 'fa-graduation-cap', 'table' => 'hcm_training'],
            'org_development' => ['name' => 'Desarrollo Org.', 'icon' => 'fa-sitemap', 'table' => 'hcm_org_development'],
            'attendance' => ['name' => 'Asistencia', 'icon' => 'fa-clock', 'table' => 'hcm_attendance'],
            'benefits' => ['name' => 'Beneficios', 'icon' => 'fa-gift', 'table' => 'hcm_benefits'],
            'leaves' => ['name' => 'Vacaciones', 'icon' => 'fa-plane', 'table' => 'hcm_attendance'],
            'reports' => ['name' => 'Reportes RRHH', 'icon' => 'fa-file-excel', 'table' => 'hcm_employees'],
        ]
    ],
    // Puedes continuar con los otros módulos...
];

/**
 * Plantilla para generar un archivo de submódulo
 */
function generateSubmoduleTemplate($module_code, $submodule_file, $submodule_data, $module_data) {
    $module_name = $module_data['name'];
    $submodule_name = $submodule_data['name'];
    $icon = $submodule_data['icon'];
    $table = $submodule_data['table'];
    $color = $module_data['color'];

    return <<<PHP
<?php
require_once __DIR__ . '/../../includes/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/index.php');
}

\$user = getCurrentUser();
\$db = Database::getInstance();

// Procesar formularios
if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset(\$_POST['action'])) {
            switch (\$_POST['action']) {
                case 'create':
                    // Lógica para crear registro
                    showAlert('Registro creado exitosamente', 'success');
                    break;

                case 'update':
                    // Lógica para actualizar registro
                    showAlert('Registro actualizado exitosamente', 'success');
                    break;

                case 'delete':
                    // Lógica para eliminar registro
                    showAlert('Registro eliminado exitosamente', 'success');
                    break;
            }
        }
    } catch (Exception \$e) {
        showAlert('Error: ' . \$e->getMessage(), 'error');
    }
}

// Obtener datos
\$records = \$db->fetchAll("SELECT * FROM {$table} WHERE user_id = ? ORDER BY id DESC LIMIT 100", [\$user['id']]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$submodule_name} - CONECTA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body { display: flex; min-height: 100vh; background: var(--dark); }
        .sidebar { width: 280px; background: var(--dark-light); border-right: 1px solid rgba(255, 255, 255, 0.08); padding: 2rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo { padding: 0 1.5rem 2rem; display: flex; align-items: center; gap: 1rem; font-size: 1.5rem; font-weight: 900; border-bottom: 1px solid rgba(255, 255, 255, 0.08); margin-bottom: 2rem; }
        .logo i { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 0.6rem; border-radius: 12px; color: white; box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4); }
        .sidebar-menu { list-style: none; padding: 0 0.75rem; }
        .menu-link { display: flex; align-items: center; gap: 1rem; padding: 0.875rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: 12px; transition: var(--transition); font-weight: 500; }
        .menu-link:hover, .menu-link.active { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .menu-link i { width: 24px; text-align: center; }
        .main-content { flex: 1; margin-left: 280px; }
        .topbar { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding: 1.25rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .content { padding: 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 1rem; }
        .page-title i { color: {$color}; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="../../<?php echo isAdmin() ? 'admin' : 'user'; ?>/dashboard_<?php echo isAdmin() ? 'admin' : 'user'; ?>.php" class="logo" style="text-decoration: none; color: var(--text);">
            <i class="fas fa-cube"></i>
            <span>CONECTA ERP</span>
        </a>
        <ul class="sidebar-menu">
            <li><a href="../{$module_code}/index.php" class="menu-link"><i class="fas fa-arrow-left"></i> Volver a {$module_name}</a></li>
            <li><a href="./{$submodule_file}.php" class="menu-link active"><i class="{$icon}"></i> {$submodule_name}</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title"><i class="{$icon}"></i> {$submodule_name}</h1>
            <button class="btn btn-primary" onclick="openModal('createModal')">
                <i class="fas fa-plus"></i> Nuevo
            </button>
        </div>
        <div class="content">
            <?php if (\$alert = getAlert()): ?>
                <div class="alert alert-<?php echo \$alert['type']; ?>">
                    <i class="fas fa-info-circle"></i>
                    <?php echo htmlspecialchars(\$alert['message']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Listado de Registros</h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Descripción</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty(\$records)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-dark); margin-bottom: 1rem; display: block;"></i>
                                        <p style="color: var(--text-muted);">No hay registros disponibles</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (\$records as \$record): ?>
                                    <tr>
                                        <td><?php echo \$record['id']; ?></td>
                                        <td><?php echo htmlspecialchars(\$record['description'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate(\$record['created_at'] ?? date('Y-m-d')); ?></td>
                                        <td>
                                            <button class="btn btn-secondary btn-sm" onclick="edit(<?php echo \$record['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-error btn-sm" onclick="deleteRecord(<?php echo \$record['id']; ?>)">
                                                <i class="fas fa-trash"></i>
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
    <script>
        function openModal(id) { alert('Funcionalidad de modal en desarrollo'); }
        function edit(id) { alert('Editar registro ' + id); }
        function deleteRecord(id) { if(confirm('¿Eliminar este registro?')) { alert('Eliminar ' + id); } }
    </script>
</body>
</html>
PHP;
}

/**
 * Crear estructura de directorios y archivos
 */
function generateModules($modules_structure) {
    $base_dir = __DIR__ . '/modules';

    if (!file_exists($base_dir)) {
        mkdir($base_dir, 0755, true);
    }

    $total_modules = 0;

    foreach ($modules_structure as $module_code => $module_data) {
        $module_dir = $base_dir . '/' . strtolower($module_code);

        if (!file_exists($module_dir)) {
            mkdir($module_dir, 0755, true);
        }

        // Crear archivo index.php para el módulo principal
        $index_content = generateModuleIndexTemplate($module_code, $module_data);
        file_put_contents($module_dir . '/index.php', $index_content);

        // Crear cada submódulo
        foreach ($module_data['submodules'] as $submodule_file => $submodule_data) {
            $submodule_content = generateSubmoduleTemplate($module_code, $submodule_file, $submodule_data, $module_data);
            file_put_contents($module_dir . '/' . $submodule_file . '.php', $submodule_content);
            $total_modules++;
            echo "✓ Creado: {$module_code}/{$submodule_file}.php\n";
        }
    }

    return $total_modules;
}

/**
 * Template para index de módulo
 */
function generateModuleIndexTemplate($module_code, $module_data) {
    $module_name = $module_data['name'];
    $icon = $module_data['icon'];
    $color = $module_data['color'];

    $submodules_html = '';
    foreach ($module_data['submodules'] as $file => $data) {
        $submodules_html .= "
                    <a href='./{$file}.php' class='module-card'>
                        <div class='module-icon' style='background: {$color};'>
                            <i class='{$data['icon']}'></i>
                        </div>
                        <div class='module-title'>{$data['name']}</div>
                    </a>";
    }

    return "<?php
require_once __DIR__ . '/../../includes/config.php';
if (!isAuthenticated()) redirect('/index.php');
\$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>{$module_name} - CONECTA ERP</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
    <link rel='stylesheet' href='../../assets/css/global.css'>
    <style>
        body { padding: 2rem; background: var(--dark); }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 900; color: var(--text); margin-bottom: 1rem; }
        .page-title i { color: {$color}; }
        .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; max-width: 1400px; margin: 0 auto; }
        .module-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 2rem; text-align: center; text-decoration: none; color: var(--text); transition: all 0.3s; }
        .module-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: {$color}; }
        .module-icon { width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; }
        .module-title { font-size: 1.2rem; font-weight: 700; }
        .back-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.05); border-radius: 10px; color: var(--text); text-decoration: none; margin-bottom: 2rem; }
        .back-btn:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <a href='../../<?php echo isAdmin() ? 'admin' : 'user'; ?>/dashboard_<?php echo isAdmin() ? 'admin' : 'user'; ?>.php' class='back-btn'>
        <i class='fas fa-arrow-left'></i> Volver al Dashboard
    </a>
    <div class='page-header'>
        <h1 class='page-title'><i class='{$icon}'></i> {$module_name}</h1>
        <p style='color: var(--text-muted); font-size: 1.2rem;'>Selecciona un submódulo para comenzar</p>
    </div>
    <div class='modules-grid'>{$submodules_html}
    </div>
</body>
</html>";
}

// Ejecutar generador
echo "==========================================\n";
echo "CONECTA ERP - Generador de Módulos\n";
echo "==========================================\n\n";

$total = generateModules($modules_structure);

echo "\n==========================================\n";
echo "✓ Proceso completado exitosamente\n";
echo "✓ Total de submódulos generados: {$total}\n";
echo "==========================================\n";
