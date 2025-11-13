<?php
/**
 * CONECTA ERP - Script de Renombrado de Archivos a Español
 * Sistema REAL de Producción - Todos los archivos deben estar en español
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  CONECTA ERP - Renombrar Archivos a Español             ║\n";
echo "║  Sistema de Producción Profesional                      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Mapeo completo de archivos inglés => español
$mappings = [
    // MÓDULO FI (Finance / Finanzas)
    '/home/user/conecta-erp/modules/fi/accounting_books.php' => '/home/user/conecta-erp/modules/fi/libros_contables.php',
    '/home/user/conecta-erp/modules/fi/accounts_payable.php' => '/home/user/conecta-erp/modules/fi/cuentas_por_pagar.php',
    '/home/user/conecta-erp/modules/fi/accounts_receivable.php' => '/home/user/conecta-erp/modules/fi/cuentas_por_cobrar.php',
    '/home/user/conecta-erp/modules/fi/banks.php' => '/home/user/conecta-erp/modules/fi/bancos.php',
    '/home/user/conecta-erp/modules/fi/closing.php' => '/home/user/conecta-erp/modules/fi/cierre_contable.php',
    '/home/user/conecta-erp/modules/fi/fixed_assets.php' => '/home/user/conecta-erp/modules/fi/activos_fijos.php',
    '/home/user/conecta-erp/modules/fi/general_ledger.php' => '/home/user/conecta-erp/modules/fi/libro_mayor.php',
    '/home/user/conecta-erp/modules/fi/ifrs.php' => '/home/user/conecta-erp/modules/fi/niif.php',
    '/home/user/conecta-erp/modules/fi/journal_entries.php' => '/home/user/conecta-erp/modules/fi/asientos_contables.php',
    '/home/user/conecta-erp/modules/fi/reports.php' => '/home/user/conecta-erp/modules/fi/reportes.php',
    '/home/user/conecta-erp/modules/fi/treasury.php' => '/home/user/conecta-erp/modules/fi/tesoreria.php',

    // MÓDULO CO (Controlling / Control de Gestión)
    '/home/user/conecta-erp/modules/co/abc_costing.php' => '/home/user/conecta-erp/modules/co/costeo_abc.php',
    '/home/user/conecta-erp/modules/co/budgets.php' => '/home/user/conecta-erp/modules/co/presupuestos.php',
    '/home/user/conecta-erp/modules/co/cost_centers.php' => '/home/user/conecta-erp/modules/co/centros_costo.php',
    '/home/user/conecta-erp/modules/co/expenses.php' => '/home/user/conecta-erp/modules/co/gastos.php',
    '/home/user/conecta-erp/modules/co/internal_orders.php' => '/home/user/conecta-erp/modules/co/ordenes_internas.php',
    '/home/user/conecta-erp/modules/co/investments.php' => '/home/user/conecta-erp/modules/co/inversiones.php',
    '/home/user/conecta-erp/modules/co/profitability.php' => '/home/user/conecta-erp/modules/co/rentabilidad.php',
    '/home/user/conecta-erp/modules/co/projects.php' => '/home/user/conecta-erp/modules/co/proyectos.php',

    // MÓDULO SD (Sales & Distribution / Ventas y Distribución)
    '/home/user/conecta-erp/modules/sd/analytics.php' => '/home/user/conecta-erp/modules/sd/analitica.php',
    '/home/user/conecta-erp/modules/sd/commissions.php' => '/home/user/conecta-erp/modules/sd/comisiones.php',
    '/home/user/conecta-erp/modules/sd/customers.php' => '/home/user/conecta-erp/modules/sd/clientes.php',
    '/home/user/conecta-erp/modules/sd/ecommerce.php' => '/home/user/conecta-erp/modules/sd/comercio_electronico.php',
    '/home/user/conecta-erp/modules/sd/invoices.php' => '/home/user/conecta-erp/modules/sd/facturas.php',
    '/home/user/conecta-erp/modules/sd/pos.php' => '/home/user/conecta-erp/modules/sd/punto_venta.php',
    '/home/user/conecta-erp/modules/sd/pricing.php' => '/home/user/conecta-erp/modules/sd/precios.php',
    '/home/user/conecta-erp/modules/sd/quotations.php' => '/home/user/conecta-erp/modules/sd/cotizaciones.php',
    '/home/user/conecta-erp/modules/sd/sales_orders.php' => '/home/user/conecta-erp/modules/sd/ordenes_venta.php',

    // MÓDULO MM (Materials Management / Gestión de Materiales)
    '/home/user/conecta-erp/modules/mm/goods_receipt.php' => '/home/user/conecta-erp/modules/mm/recepcion_mercaderia.php',
    '/home/user/conecta-erp/modules/mm/inventory.php' => '/home/user/conecta-erp/modules/mm/inventario.php',
    '/home/user/conecta-erp/modules/mm/mrp.php' => '/home/user/conecta-erp/modules/mm/planificacion_materiales.php',
    '/home/user/conecta-erp/modules/mm/products.php' => '/home/user/conecta-erp/modules/mm/productos.php',
    '/home/user/conecta-erp/modules/mm/purchase_orders.php' => '/home/user/conecta-erp/modules/mm/ordenes_compra.php',
    '/home/user/conecta-erp/modules/mm/suppliers.php' => '/home/user/conecta-erp/modules/mm/proveedores.php',
    '/home/user/conecta-erp/modules/mm/traceability.php' => '/home/user/conecta-erp/modules/mm/trazabilidad.php',
    '/home/user/conecta-erp/modules/mm/warehouses.php' => '/home/user/conecta-erp/modules/mm/almacenes.php',

    // MÓDULO PP (Production Planning / Planificación de Producción)
    '/home/user/conecta-erp/modules/pp/bom.php' => '/home/user/conecta-erp/modules/pp/lista_materiales.php',
    '/home/user/conecta-erp/modules/pp/capacity.php' => '/home/user/conecta-erp/modules/pp/capacidad.php',
    '/home/user/conecta-erp/modules/pp/costs.php' => '/home/user/conecta-erp/modules/pp/costos.php',
    '/home/user/conecta-erp/modules/pp/formulas.php' => '/home/user/conecta-erp/modules/pp/formulas.php', // Ya está en español
    '/home/user/conecta-erp/modules/pp/maintenance.php' => '/home/user/conecta-erp/modules/pp/mantenimiento.php',
    '/home/user/conecta-erp/modules/pp/mrp_ii.php' => '/home/user/conecta-erp/modules/pp/planificacion_avanzada.php',
    '/home/user/conecta-erp/modules/pp/production_orders.php' => '/home/user/conecta-erp/modules/pp/ordenes_produccion.php',
    '/home/user/conecta-erp/modules/pp/quality.php' => '/home/user/conecta-erp/modules/pp/calidad.php',
    '/home/user/conecta-erp/modules/pp/routing.php' => '/home/user/conecta-erp/modules/pp/rutas.php',
    '/home/user/conecta-erp/modules/pp/work_centers.php' => '/home/user/conecta-erp/modules/pp/centros_trabajo.php',

    // MÓDULO HCM (Human Capital Management / Gestión de Capital Humano)
    '/home/user/conecta-erp/modules/hcm/attendance.php' => '/home/user/conecta-erp/modules/hcm/asistencia.php',
    '/home/user/conecta-erp/modules/hcm/benefits.php' => '/home/user/conecta-erp/modules/hcm/beneficios.php',
    '/home/user/conecta-erp/modules/hcm/employees.php' => '/home/user/conecta-erp/modules/hcm/empleados.php',
    '/home/user/conecta-erp/modules/hcm/leaves.php' => '/home/user/conecta-erp/modules/hcm/ausencias.php',
    '/home/user/conecta-erp/modules/hcm/onboarding.php' => '/home/user/conecta-erp/modules/hcm/incorporacion.php',
    '/home/user/conecta-erp/modules/hcm/org_development.php' => '/home/user/conecta-erp/modules/hcm/desarrollo_organizacional.php',
    '/home/user/conecta-erp/modules/hcm/payroll.php' => '/home/user/conecta-erp/modules/hcm/nomina.php',
    '/home/user/conecta-erp/modules/hcm/performance.php' => '/home/user/conecta-erp/modules/hcm/desempeno.php',
    '/home/user/conecta-erp/modules/hcm/recruitment.php' => '/home/user/conecta-erp/modules/hcm/reclutamiento.php',
    '/home/user/conecta-erp/modules/hcm/reports.php' => '/home/user/conecta-erp/modules/hcm/reportes.php',
    '/home/user/conecta-erp/modules/hcm/training.php' => '/home/user/conecta-erp/modules/hcm/capacitacion.php',
];

$total = count($mappings);
$success = 0;
$errors = 0;
$skipped = 0;

echo "Total de archivos a renombrar: $total\n\n";

foreach ($mappings as $old => $new) {
    // Verificar si el archivo origen existe
    if (!file_exists($old)) {
        echo "⚠ SKIP: $old (no existe)\n";
        $skipped++;
        continue;
    }

    // Verificar si el archivo destino ya existe
    if (file_exists($new)) {
        echo "⚠ SKIP: " . basename($new) . " (ya existe)\n";
        $skipped++;
        continue;
    }

    // Renombrar
    if (rename($old, $new)) {
        echo "✓ " . basename($old) . " → " . basename($new) . "\n";
        $success++;
    } else {
        echo "✗ ERROR: No se pudo renombrar " . basename($old) . "\n";
        $errors++;
    }
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN DE RENOMBRADO                                   ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  Total archivos:        " . str_pad($total, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
echo "║  Renombrados exitosos:  " . str_pad($success, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
echo "║  Omitidos:              " . str_pad($skipped, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
echo "║  Errores:               " . str_pad($errors, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

if ($success > 0) {
    echo "✅ ¡Archivos renombrados exitosamente a español!\n";
    echo "\n📝 IMPORTANTE: Ahora debes actualizar la base de datos.\n";
    echo "   Ejecuta: php actualizar_submodulos_espanol.php\n\n";
}

if ($errors > 0) {
    echo "⚠ Hubo algunos errores. Revisa los permisos de los archivos.\n\n";
}
