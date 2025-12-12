<?php
/**
 * CONECTA ERP - Exports de Contabilidad
 * Generación de reportes formales en Excel y PDF
 * Formato profesional estilo SII/DIAN/AFIP
 */

require_once __DIR__ . '/../../config/config.php';

// Verificar autenticación
if (!is_logged_in()) {
    die(json_encode(['error' => 'No autenticado']));
}

$user_id = $_SESSION['user_id'];
$user = db_get_row("SELECT * FROM usuarios WHERE id = ?", [$user_id]);
$empresa_id = $user['empresa_id'] ?? null;

// Obtener datos de empresa
try {
    $empresa = db_get_row("SELECT * FROM empresas WHERE id = ?", [$empresa_id]);
} catch (Exception $e) {
    $empresa = ['razon_social' => 'Sin Empresa', 'rut' => ''];
}

// Determinar tipo de export
$tipo = $_GET['tipo'] ?? '';
$formato = $_GET['formato'] ?? 'excel'; // excel | pdf

// ========================================
// LIBRO MAYOR - EXCEL
// ========================================
if ($tipo === 'mayor' && $formato === 'excel') {
    $cuenta_id = $_GET['cuenta_id'] ?? 0;
    $fecha_desde = $_GET['desde'] ?? date('Y-m-01');
    $fecha_hasta = $_GET['hasta'] ?? date('Y-m-d');

    try {
        $cuenta = db_get_row("SELECT * FROM fi_plan_cuentas WHERE id = ? AND empresa_id = ?", [$cuenta_id, $empresa_id]);

        if (!$cuenta) {
            die('Cuenta no encontrada');
        }

        // Saldo inicial
        $saldo_inicial = db_get_var("
            SELECT COALESCE(SUM(debe - haber), 0)
            FROM fi_comprobantes_detalle d
            INNER JOIN fi_comprobantes c ON d.comprobante_id = c.id
            WHERE d.cuenta_id = ?
            AND c.empresa_id = ?
            AND c.fecha < ?
            AND c.estado = 'contabilizado'
        ", [$cuenta_id, $empresa_id, $fecha_desde]) ?? 0;

        // Movimientos
        $movimientos = db_query("
            SELECT c.fecha, c.numero, c.tipo, d.descripcion, d.debe, d.haber,
                   c.referencia, u.nombre as usuario
            FROM fi_comprobantes_detalle d
            INNER JOIN fi_comprobantes c ON d.comprobante_id = c.id
            LEFT JOIN usuarios u ON c.usuario_crea_id = u.id
            WHERE d.cuenta_id = ?
            AND c.empresa_id = ?
            AND c.fecha BETWEEN ? AND ?
            AND c.estado = 'contabilizado'
            ORDER BY c.fecha ASC, c.numero ASC
        ", [$cuenta_id, $empresa_id, $fecha_desde, $fecha_hasta]);

        // Crear archivo Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Libro_Mayor_' . $cuenta['codigo'] . '_' . date('Ymd') . '.xls"');
        header('Cache-Control: max-age=0');

        echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
        echo "<head>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />";
        echo "<style>";
        echo "table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }";
        echo "th { background-color: #4472C4; color: white; font-weight: bold; border: 1px solid #000; padding: 8px; text-align: center; }";
        echo "td { border: 1px solid #ccc; padding: 6px; }";
        echo ".titulo { font-size: 18px; font-weight: bold; text-align: center; padding: 10px; }";
        echo ".subtitulo { font-size: 14px; text-align: center; padding: 5px; }";
        echo ".numero { text-align: right; }";
        echo ".fecha { text-align: center; }";
        echo ".total { background-color: #E7E6E6; font-weight: bold; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";

        // Membrete
        echo "<table><tr><td colspan='9' class='titulo'>" . htmlspecialchars($empresa['razon_social'] ?? 'EMPRESA') . "</td></tr>";
        echo "<tr><td colspan='9' class='subtitulo'>RUT: " . htmlspecialchars($empresa['rut'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td colspan='9' class='subtitulo'>LIBRO MAYOR</td></tr>";
        echo "<tr><td colspan='9' class='subtitulo'>Cuenta: " . htmlspecialchars($cuenta['codigo'] . ' - ' . $cuenta['nombre']) . "</td></tr>";
        echo "<tr><td colspan='9' class='subtitulo'>Período: " . date('d/m/Y', strtotime($fecha_desde)) . " al " . date('d/m/Y', strtotime($fecha_hasta)) . "</td></tr>";
        echo "<tr><td colspan='9'>&nbsp;</td></tr>";

        // Cabecera de tabla
        echo "<tr>";
        echo "<th>Fecha</th>";
        echo "<th>Comprobante</th>";
        echo "<th>Tipo</th>";
        echo "<th>Descripción</th>";
        echo "<th>Referencia</th>";
        echo "<th>Debe</th>";
        echo "<th>Haber</th>";
        echo "<th>Saldo</th>";
        echo "<th>Usuario</th>";
        echo "</tr>";

        // Saldo inicial
        echo "<tr class='total'>";
        echo "<td colspan='7'>SALDO INICIAL</td>";
        echo "<td class='numero'>" . number_format($saldo_inicial, 2, ',', '.') . "</td>";
        echo "<td></td>";
        echo "</tr>";

        // Movimientos
        $saldo_acum = $saldo_inicial;
        foreach ($movimientos as $mov) {
            $saldo_acum += ($mov['debe'] - $mov['haber']);

            echo "<tr>";
            echo "<td class='fecha'>" . date('d/m/Y', strtotime($mov['fecha'])) . "</td>";
            echo "<td class='fecha'>" . htmlspecialchars($mov['numero']) . "</td>";
            echo "<td class='fecha'>" . htmlspecialchars($mov['tipo']) . "</td>";
            echo "<td>" . htmlspecialchars($mov['descripcion']) . "</td>";
            echo "<td>" . htmlspecialchars($mov['referencia'] ?? '') . "</td>";
            echo "<td class='numero'>" . ($mov['debe'] > 0 ? number_format($mov['debe'], 2, ',', '.') : '-') . "</td>";
            echo "<td class='numero'>" . ($mov['haber'] > 0 ? number_format($mov['haber'], 2, ',', '.') : '-') . "</td>";
            echo "<td class='numero'>" . number_format($saldo_acum, 2, ',', '.') . "</td>";
            echo "<td class='fecha'>" . htmlspecialchars($mov['usuario'] ?? '') . "</td>";
            echo "</tr>";
        }

        // Totales
        $total_debe = array_sum(array_column($movimientos, 'debe'));
        $total_haber = array_sum(array_column($movimientos, 'haber'));

        echo "<tr class='total'>";
        echo "<td colspan='5'>TOTALES</td>";
        echo "<td class='numero'>" . number_format($total_debe, 2, ',', '.') . "</td>";
        echo "<td class='numero'>" . number_format($total_haber, 2, ',', '.') . "</td>";
        echo "<td class='numero'>" . number_format($saldo_acum, 2, ',', '.') . "</td>";
        echo "<td></td>";
        echo "</tr>";

        echo "<tr><td colspan='9'>&nbsp;</td></tr>";
        echo "<tr><td colspan='9' class='subtitulo'>Generado: " . date('d/m/Y H:i') . " por " . htmlspecialchars($user['nombre_completo'] ?? 'Usuario') . "</td></tr>";

        echo "</table>";
        echo "</body></html>";
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ========================================
// LIBRO DIARIO - EXCEL
// ========================================
if ($tipo === 'diario' && $formato === 'excel') {
    $fecha_desde = $_GET['desde'] ?? date('Y-m-01');
    $fecha_hasta = $_GET['hasta'] ?? date('Y-m-d');

    try {
        // Obtener comprobantes
        $comprobantes = db_query("
            SELECT c.id, c.fecha, c.numero, c.tipo, c.descripcion, c.total_debe, c.total_haber,
                   u.nombre as usuario
            FROM fi_comprobantes c
            LEFT JOIN usuarios u ON c.usuario_crea_id = u.id
            WHERE c.empresa_id = ?
            AND c.fecha BETWEEN ? AND ?
            AND c.estado = 'contabilizado'
            ORDER BY c.fecha ASC, c.numero ASC
        ", [$empresa_id, $fecha_desde, $fecha_hasta]);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Libro_Diario_' . date('Ymd') . '.xls"');
        header('Cache-Control: max-age=0');

        echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
        echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />";
        echo "<style>";
        echo "table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }";
        echo "th { background-color: #4472C4; color: white; font-weight: bold; border: 1px solid #000; padding: 8px; }";
        echo "td { border: 1px solid #ccc; padding: 6px; }";
        echo ".titulo { font-size: 18px; font-weight: bold; text-align: center; padding: 10px; }";
        echo ".subtitulo { font-size: 14px; text-align: center; padding: 5px; }";
        echo ".numero { text-align: right; }";
        echo ".total { background-color: #E7E6E6; font-weight: bold; }";
        echo ".detalle { background-color: #F2F2F2; font-size: 11px; }";
        echo "</style></head><body>";

        // Membrete
        echo "<table><tr><td colspan='7' class='titulo'>" . htmlspecialchars($empresa['razon_social'] ?? 'EMPRESA') . "</td></tr>";
        echo "<tr><td colspan='7' class='subtitulo'>RUT: " . htmlspecialchars($empresa['rut'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td colspan='7' class='subtitulo'>LIBRO DIARIO</td></tr>";
        echo "<tr><td colspan='7' class='subtitulo'>Período: " . date('d/m/Y', strtotime($fecha_desde)) . " al " . date('d/m/Y', strtotime($fecha_hasta)) . "</td></tr>";
        echo "<tr><td colspan='7'>&nbsp;</td></tr>";

        $total_general_debe = 0;
        $total_general_haber = 0;

        foreach ($comprobantes as $comp) {
            // Cabecera del comprobante
            echo "<tr class='total'>";
            echo "<td colspan='7'>";
            echo "Comprobante: " . htmlspecialchars($comp['numero']) . " | ";
            echo "Fecha: " . date('d/m/Y', strtotime($comp['fecha'])) . " | ";
            echo "Tipo: " . htmlspecialchars($comp['tipo']) . " | ";
            echo htmlspecialchars($comp['descripcion']);
            echo "</td></tr>";

            // Detalle
            $lineas = db_query("
                SELECT d.*, c.codigo as cuenta_codigo, c.nombre as cuenta_nombre
                FROM fi_comprobantes_detalle d
                INNER JOIN fi_plan_cuentas c ON d.cuenta_id = c.id
                WHERE d.comprobante_id = ?
                ORDER BY d.linea_num ASC
            ", [$comp['id']]);

            echo "<tr class='detalle'><th>Cuenta</th><th>Nombre</th><th>Descripción</th><th>C.Costo</th><th>Debe</th><th>Haber</th><th>Ref</th></tr>";

            foreach ($lineas as $linea) {
                echo "<tr class='detalle'>";
                echo "<td>" . htmlspecialchars($linea['cuenta_codigo']) . "</td>";
                echo "<td>" . htmlspecialchars($linea['cuenta_nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($linea['descripcion']) . "</td>";
                echo "<td></td>";
                echo "<td class='numero'>" . ($linea['debe'] > 0 ? number_format($linea['debe'], 2, ',', '.') : '-') . "</td>";
                echo "<td class='numero'>" . ($linea['haber'] > 0 ? number_format($linea['haber'], 2, ',', '.') : '-') . "</td>";
                echo "<td>" . htmlspecialchars($linea['documento_referencia'] ?? '') . "</td>";
                echo "</tr>";
            }

            echo "<tr class='total'>";
            echo "<td colspan='4'>TOTAL COMPROBANTE</td>";
            echo "<td class='numero'>" . number_format($comp['total_debe'], 2, ',', '.') . "</td>";
            echo "<td class='numero'>" . number_format($comp['total_haber'], 2, ',', '.') . "</td>";
            echo "<td></td></tr>";
            echo "<tr><td colspan='7'>&nbsp;</td></tr>";

            $total_general_debe += $comp['total_debe'];
            $total_general_haber += $comp['total_haber'];
        }

        echo "<tr class='total'>";
        echo "<td colspan='4'>TOTAL GENERAL PERIODO</td>";
        echo "<td class='numero'>" . number_format($total_general_debe, 2, ',', '.') . "</td>";
        echo "<td class='numero'>" . number_format($total_general_haber, 2, ',', '.') . "</td>";
        echo "<td></td></tr>";

        echo "<tr><td colspan='7'>&nbsp;</td></tr>";
        echo "<tr><td colspan='7' class='subtitulo'>Generado: " . date('d/m/Y H:i') . " por " . htmlspecialchars($user['nombre_completo'] ?? 'Usuario') . "</td></tr>";

        echo "</table></body></html>";
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ========================================
// BALANCE DE COMPROBACIÓN - EXCEL
// ========================================
if ($tipo === 'balance' && $formato === 'excel') {
    $periodo = $_GET['periodo'] ?? date('Y-m');

    try {
        // Obtener cuentas con movimiento
        $cuentas = db_query("
            SELECT c.codigo, c.nombre, c.tipo, c.naturaleza,
                   COALESCE(SUM(d.debe), 0) as total_debe,
                   COALESCE(SUM(d.haber), 0) as total_haber
            FROM fi_plan_cuentas c
            INNER JOIN fi_comprobantes_detalle d ON c.id = d.cuenta_id
            INNER JOIN fi_comprobantes comp ON d.comprobante_id = comp.id
            WHERE c.empresa_id = ?
            AND comp.periodo = ?
            AND comp.estado = 'contabilizado'
            GROUP BY c.id, c.codigo, c.nombre, c.tipo, c.naturaleza
            ORDER BY c.codigo ASC
        ", [$empresa_id, $periodo]);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Balance_Comprobacion_' . $periodo . '.xls"');
        header('Cache-Control: max-age=0');

        echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
        echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />";
        echo "<style>";
        echo "table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }";
        echo "th { background-color: #4472C4; color: white; font-weight: bold; border: 1px solid #000; padding: 8px; }";
        echo "td { border: 1px solid #ccc; padding: 6px; }";
        echo ".titulo { font-size: 18px; font-weight: bold; text-align: center; padding: 10px; }";
        echo ".subtitulo { font-size: 14px; text-align: center; padding: 5px; }";
        echo ".numero { text-align: right; }";
        echo ".total { background-color: #E7E6E6; font-weight: bold; }";
        echo "</style></head><body>";

        echo "<table><tr><td colspan='6' class='titulo'>" . htmlspecialchars($empresa['razon_social'] ?? 'EMPRESA') . "</td></tr>";
        echo "<tr><td colspan='6' class='subtitulo'>RUT: " . htmlspecialchars($empresa['rut'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td colspan='6' class='subtitulo'>BALANCE DE COMPROBACIÓN</td></tr>";
        echo "<tr><td colspan='6' class='subtitulo'>Período: " . $periodo . "</td></tr>";
        echo "<tr><td colspan='6'>&nbsp;</td></tr>";

        echo "<tr><th>Código</th><th>Cuenta</th><th>Debe</th><th>Haber</th><th>Saldo Deudor</th><th>Saldo Acreedor</th></tr>";

        $total_debe = 0;
        $total_haber = 0;
        $total_deudor = 0;
        $total_acreedor = 0;

        foreach ($cuentas as $c) {
            $saldo = $c['total_debe'] - $c['total_haber'];
            $saldo_deudor = $saldo > 0 ? $saldo : 0;
            $saldo_acreedor = $saldo < 0 ? abs($saldo) : 0;

            echo "<tr>";
            echo "<td>" . htmlspecialchars($c['codigo']) . "</td>";
            echo "<td>" . htmlspecialchars($c['nombre']) . "</td>";
            echo "<td class='numero'>" . number_format($c['total_debe'], 2, ',', '.') . "</td>";
            echo "<td class='numero'>" . number_format($c['total_haber'], 2, ',', '.') . "</td>";
            echo "<td class='numero'>" . ($saldo_deudor > 0 ? number_format($saldo_deudor, 2, ',', '.') : '-') . "</td>";
            echo "<td class='numero'>" . ($saldo_acreedor > 0 ? number_format($saldo_acreedor, 2, ',', '.') : '-') . "</td>";
            echo "</tr>";

            $total_debe += $c['total_debe'];
            $total_haber += $c['total_haber'];
            $total_deudor += $saldo_deudor;
            $total_acreedor += $saldo_acreedor;
        }

        echo "<tr class='total'>";
        echo "<td colspan='2'>TOTALES</td>";
        echo "<td class='numero'>" . number_format($total_debe, 2, ',', '.') . "</td>";
        echo "<td class='numero'>" . number_format($total_haber, 2, ',', '.') . "</td>";
        echo "<td class='numero'>" . number_format($total_deudor, 2, ',', '.') . "</td>";
        echo "<td class='numero'>" . number_format($total_acreedor, 2, ',', '.') . "</td>";
        echo "</tr>";

        echo "<tr><td colspan='6'>&nbsp;</td></tr>";
        echo "<tr><td colspan='6' class='subtitulo'>Generado: " . date('d/m/Y H:i') . " por " . htmlspecialchars($user['nombre_completo'] ?? 'Usuario') . "</td></tr>";

        echo "</table></body></html>";
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// Si no se especificó tipo/formato válido
die('Tipo o formato de export no válido');
