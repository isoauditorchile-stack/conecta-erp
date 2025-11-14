<?php
/**
 * CONECTA ERP - Actualización Automática de Indicadores Económicos
 * Este script debe ejecutarse diariamente mediante CRON
 *
 * Configurar en crontab:
 * 0 9 * * * php /path/to/conecta-erp/cron/update_indicators.php
 */

require_once dirname(__DIR__) . '/includes/config.php';

echo "=== CONECTA ERP - Actualización de Indicadores Económicos ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$db = Database::getInstance();
$today = date('Y-m-d');

/**
 * Obtener UF desde API del Banco Central de Chile (Mindicador.cl)
 */
function getUF() {
    $url = "https://mindicador.cl/api/uf";
    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['serie'][0]['valor'])) {
            return $data['serie'][0]['valor'];
        }
    }

    return null;
}

/**
 * Obtener Dólar desde API del Banco Central de Chile
 */
function getDolar() {
    $url = "https://mindicador.cl/api/dolar";
    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['serie'][0]['valor'])) {
            return $data['serie'][0]['valor'];
        }
    }

    return null;
}

/**
 * Obtener UTM desde API
 */
function getUTM() {
    $url = "https://mindicador.cl/api/utm";
    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['serie'][0]['valor'])) {
            return $data['serie'][0]['valor'];
        }
    }

    return null;
}

/**
 * Obtener Euro desde API
 */
function getEuro() {
    $url = "https://mindicador.cl/api/euro";
    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['serie'][0]['valor'])) {
            return $data['serie'][0]['valor'];
        }
    }

    return null;
}

try {
    // Obtener indicadores
    echo "Obteniendo indicadores económicos...\n";

    $uf = getUF();
    $dolar = getDolar();
    $utm = getUTM();
    $euro = getEuro();

    // Actualizar o insertar UF
    if ($uf !== null) {
        $existing = $db->fetchOne("SELECT id FROM economic_indicators WHERE indicator_code = 'UF' AND date = ?", [$today]);

        if ($existing) {
            $db->update(
                "UPDATE economic_indicators SET value = ?, updated_at = NOW() WHERE indicator_code = 'UF' AND date = ?",
                [$uf, $today]
            );
            echo "✓ UF actualizada: $uf CLP\n";
        } else {
            $db->insert(
                "INSERT INTO economic_indicators (indicator_code, indicator_name, value, currency, date, source) VALUES (?, ?, ?, ?, ?, ?)",
                ['UF', 'Unidad de Fomento', $uf, 'CLP', $today, 'mindicador.cl']
            );
            echo "✓ UF insertada: $uf CLP\n";
        }
    } else {
        echo "✗ No se pudo obtener UF\n";
    }

    // Actualizar o insertar Dólar
    if ($dolar !== null) {
        $existing = $db->fetchOne("SELECT id FROM economic_indicators WHERE indicator_code = 'USD' AND date = ?", [$today]);

        if ($existing) {
            $db->update(
                "UPDATE economic_indicators SET value = ?, updated_at = NOW() WHERE indicator_code = 'USD' AND date = ?",
                [$dolar, $today]
            );
            echo "✓ Dólar actualizado: $dolar CLP\n";
        } else {
            $db->insert(
                "INSERT INTO economic_indicators (indicator_code, indicator_name, value, currency, date, source) VALUES (?, ?, ?, ?, ?, ?)",
                ['USD', 'Dólar Estadounidense', $dolar, 'CLP', $today, 'mindicador.cl']
            );
            echo "✓ Dólar insertado: $dolar CLP\n";
        }
    } else {
        echo "✗ No se pudo obtener Dólar\n";
    }

    // Actualizar o insertar UTM
    if ($utm !== null) {
        $existing = $db->fetchOne("SELECT id FROM economic_indicators WHERE indicator_code = 'UTM' AND date = ?", [$today]);

        if ($existing) {
            $db->update(
                "UPDATE economic_indicators SET value = ?, updated_at = NOW() WHERE indicator_code = 'UTM' AND date = ?",
                [$utm, $today]
            );
            echo "✓ UTM actualizada: $utm CLP\n";
        } else {
            $db->insert(
                "INSERT INTO economic_indicators (indicator_code, indicator_name, value, currency, date, source) VALUES (?, ?, ?, ?, ?, ?)",
                ['UTM', 'Unidad Tributaria Mensual', $utm, 'CLP', $today, 'mindicador.cl']
            );
            echo "✓ UTM insertada: $utm CLP\n";
        }
    } else {
        echo "✗ No se pudo obtener UTM\n";
    }

    // Actualizar o insertar Euro
    if ($euro !== null) {
        $existing = $db->fetchOne("SELECT id FROM economic_indicators WHERE indicator_code = 'EUR' AND date = ?", [$today]);

        if ($existing) {
            $db->update(
                "UPDATE economic_indicators SET value = ?, updated_at = NOW() WHERE indicator_code = 'EUR' AND date = ?",
                [$euro, $today]
            );
            echo "✓ Euro actualizado: $euro CLP\n";
        } else {
            $db->insert(
                "INSERT INTO economic_indicators (indicator_code, indicator_name, value, currency, date, source) VALUES (?, ?, ?, ?, ?, ?)",
                ['EUR', 'Euro', $euro, 'CLP', $today, 'mindicador.cl']
            );
            echo "✓ Euro insertado: $euro CLP\n";
        }
    } else {
        echo "✗ No se pudo obtener Euro\n";
    }

    echo "\n=== Actualización completada exitosamente ===\n";

    // Log de actividad
    logActivity(1, 'update_indicators', 'Indicadores económicos actualizados automáticamente', 'system');

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    error_log("Error actualizando indicadores: " . $e->getMessage());
}
