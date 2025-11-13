<?php
/**
 * CONECTA ERP - Actualizar Submódulos a Español en Base de Datos
 * Actualiza la tabla submodules con los nuevos nombres de archivo en español
 */

require_once __DIR__ . '/includes/config.php';

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  CONECTA ERP - Actualizar Submódulos a Español          ║\n";
echo "║  Actualización de Base de Datos                         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$db = Database::getInstance();

// Mapeo de actualizaciones: ID del submódulo => nuevo nombre de archivo
$updates = [
    // MÓDULO FI (Finance)
    ['id' => 1, 'code' => 'FI-GL', 'name' => 'Libro Mayor', 'file' => 'libro_mayor.php'],
    ['id' => 2, 'code' => 'FI-AP', 'name' => 'Cuentas por Pagar', 'file' => 'cuentas_por_pagar.php'],
    ['id' => 3, 'code' => 'FI-AR', 'name' => 'Cuentas por Cobrar', 'file' => 'cuentas_por_cobrar.php'],
    ['id' => 4, 'code' => 'FI-FA', 'name' => 'Activos Fijos', 'file' => 'activos_fijos.php'],
    ['id' => 5, 'code' => 'FI-BK', 'name' => 'Bancos', 'file' => 'bancos.php'],
    ['id' => 6, 'code' => 'FI-TR', 'name' => 'Tesorería', 'file' => 'tesoreria.php'],
    ['id' => 7, 'code' => 'FI-CL', 'name' => 'Cierre Contable', 'file' => 'cierre_contable.php'],
    ['id' => 8, 'code' => 'FI-JE', 'name' => 'Asientos Contables', 'file' => 'asientos_contables.php'],
    ['id' => 9, 'code' => 'FI-RP', 'name' => 'Reportes Financieros', 'file' => 'reportes.php'],
    ['id' => 10, 'code' => 'FI-IF', 'name' => 'NIIF/IFRS', 'file' => 'niif.php'],
    ['id' => 11, 'code' => 'FI-AB', 'name' => 'Libros Contables', 'file' => 'libros_contables.php'],

    // MÓDULO CO (Controlling)
    ['id' => 13, 'code' => 'CO-CC', 'name' => 'Centros de Costo', 'file' => 'centros_costo.php'],
    ['id' => 14, 'code' => 'CO-IO', 'name' => 'Órdenes Internas', 'file' => 'ordenes_internas.php'],
    ['id' => 15, 'code' => 'CO-PR', 'name' => 'Rentabilidad', 'file' => 'rentabilidad.php'],
    ['id' => 16, 'code' => 'CO-PJ', 'name' => 'Proyectos', 'file' => 'proyectos.php'],
    ['id' => 17, 'code' => 'CO-BG', 'name' => 'Presupuestos', 'file' => 'presupuestos.php'],
    ['id' => 18, 'code' => 'CO-EX', 'name' => 'Gastos', 'file' => 'gastos.php'],
    ['id' => 19, 'code' => 'CO-IN', 'name' => 'Inversiones', 'file' => 'inversiones.php'],
    ['id' => 20, 'code' => 'CO-ABC', 'name' => 'Costeo ABC', 'file' => 'costeo_abc.php'],

    // MÓDULO SD (Sales & Distribution)
    ['id' => 22, 'code' => 'SD-CU', 'name' => 'Clientes', 'file' => 'clientes.php'],
    ['id' => 23, 'code' => 'SD-QT', 'name' => 'Cotizaciones', 'file' => 'cotizaciones.php'],
    ['id' => 24, 'code' => 'SD-SO', 'name' => 'Órdenes de Venta', 'file' => 'ordenes_venta.php'],
    ['id' => 25, 'code' => 'SD-IV', 'name' => 'Facturas', 'file' => 'facturas.php'],
    ['id' => 26, 'code' => 'SD-PR', 'name' => 'Precios', 'file' => 'precios.php'],
    ['id' => 27, 'code' => 'SD-POS', 'name' => 'Punto de Venta', 'file' => 'punto_venta.php'],
    ['id' => 28, 'code' => 'SD-EC', 'name' => 'Comercio Electrónico', 'file' => 'comercio_electronico.php'],
    ['id' => 29, 'code' => 'SD-CM', 'name' => 'Comisiones', 'file' => 'comisiones.php'],
    ['id' => 30, 'code' => 'SD-AN', 'name' => 'Analítica de Ventas', 'file' => 'analitica.php'],

    // MÓDULO MM (Materials Management)
    ['id' => 32, 'code' => 'MM-PR', 'name' => 'Productos', 'file' => 'productos.php'],
    ['id' => 33, 'code' => 'MM-PO', 'name' => 'Órdenes de Compra', 'file' => 'ordenes_compra.php'],
    ['id' => 34, 'code' => 'MM-GR', 'name' => 'Recepción de Mercadería', 'file' => 'recepcion_mercaderia.php'],
    ['id' => 35, 'code' => 'MM-IV', 'name' => 'Inventario', 'file' => 'inventario.php'],
    ['id' => 36, 'code' => 'MM-WH', 'name' => 'Almacenes', 'file' => 'almacenes.php'],
    ['id' => 37, 'code' => 'MM-SU', 'name' => 'Proveedores', 'file' => 'proveedores.php'],
    ['id' => 38, 'code' => 'MM-MRP', 'name' => 'Planificación de Materiales', 'file' => 'planificacion_materiales.php'],
    ['id' => 39, 'code' => 'MM-TR', 'name' => 'Trazabilidad', 'file' => 'trazabilidad.php'],

    // MÓDULO PP (Production Planning)
    ['id' => 41, 'code' => 'PP-PO', 'name' => 'Órdenes de Producción', 'file' => 'ordenes_produccion.php'],
    ['id' => 42, 'code' => 'PP-BOM', 'name' => 'Lista de Materiales (BOM)', 'file' => 'lista_materiales.php'],
    ['id' => 43, 'code' => 'PP-RT', 'name' => 'Rutas de Producción', 'file' => 'rutas.php'],
    ['id' => 44, 'code' => 'PP-WC', 'name' => 'Centros de Trabajo', 'file' => 'centros_trabajo.php'],
    ['id' => 45, 'code' => 'PP-CP', 'name' => 'Capacidad', 'file' => 'capacidad.php'],
    ['id' => 46, 'code' => 'PP-QC', 'name' => 'Control de Calidad', 'file' => 'calidad.php'],
    ['id' => 47, 'code' => 'PP-MRP', 'name' => 'Planificación Avanzada (MRP II)', 'file' => 'planificacion_avanzada.php'],
    ['id' => 48, 'code' => 'PP-CT', 'name' => 'Costos de Producción', 'file' => 'costos.php'],
    ['id' => 49, 'code' => 'PP-MT', 'name' => 'Mantenimiento', 'file' => 'mantenimiento.php'],
    ['id' => 50, 'code' => 'PP-FM', 'name' => 'Fórmulas', 'file' => 'formulas.php'],

    // MÓDULO HCM (Human Capital Management)
    ['id' => 52, 'code' => 'HCM-EMP', 'name' => 'Empleados', 'file' => 'empleados.php'],
    ['id' => 53, 'code' => 'HCM-REC', 'name' => 'Reclutamiento', 'file' => 'reclutamiento.php'],
    ['id' => 54, 'code' => 'HCM-ONB', 'name' => 'Incorporación', 'file' => 'incorporacion.php'],
    ['id' => 55, 'code' => 'HCM-PAY', 'name' => 'Nómina', 'file' => 'nomina.php'],
    ['id' => 56, 'code' => 'HCM-ATT', 'name' => 'Asistencia', 'file' => 'asistencia.php'],
    ['id' => 57, 'code' => 'HCM-LV', 'name' => 'Ausencias y Licencias', 'file' => 'ausencias.php'],
    ['id' => 58, 'code' => 'HCM-BEN', 'name' => 'Beneficios', 'file' => 'beneficios.php'],
    ['id' => 59, 'code' => 'HCM-PER', 'name' => 'Desempeño', 'file' => 'desempeno.php'],
    ['id' => 60, 'code' => 'HCM-TRN', 'name' => 'Capacitación', 'file' => 'capacitacion.php'],
    ['id' => 61, 'code' => 'HCM-ORG', 'name' => 'Desarrollo Organizacional', 'file' => 'desarrollo_organizacional.php'],
    ['id' => 62, 'code' => 'HCM-RPT', 'name' => 'Reportes de RRHH', 'file' => 'reportes.php'],
];

$total = count($updates);
$success = 0;
$errors = 0;

echo "Actualizando $total submódulos en la base de datos...\n\n";

foreach ($updates as $update) {
    try {
        $db->update(
            "UPDATE submodules SET name = ?, file = ? WHERE id = ?",
            [$update['name'], $update['file'], $update['id']]
        );
        echo "✓ [{$update['code']}] {$update['name']} → {$update['file']}\n";
        $success++;
    } catch (Exception $e) {
        echo "✗ ERROR [{$update['code']}]: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN DE ACTUALIZACIÓN                                ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  Total submódulos:      " . str_pad($total, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
echo "║  Actualizados exitosos: " . str_pad($success, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
echo "║  Errores:               " . str_pad($errors, 4, ' ', STR_PAD_LEFT) . "                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

if ($success > 0) {
    echo "✅ ¡Base de datos actualizada exitosamente!\n";
    echo "   Todos los submódulos ahora tienen nombres en español.\n\n";
}

if ($errors > 0) {
    echo "⚠ Hubo algunos errores. Verifica la conexión a la base de datos.\n\n";
}
