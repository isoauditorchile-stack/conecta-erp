<?php
/**
 * CONECTA ERP - RUT Validator (Backend)
 * Version: 2.0.0
 *
 * Funciones para validar y formatear RUT chileno en el backend
 * usando el algoritmo Módulo 11.
 *
 * Usage:
 * require_once __DIR__ . '/includes/rut_validator.php';
 *
 * if (validateChileanRUT($rut)) {
 *     echo "RUT válido";
 * }
 */

/**
 * Valida un RUT chileno usando el algoritmo Módulo 11
 *
 * @param string $rut RUT a validar (puede incluir formato: 15.895.771-k)
 * @return bool True si el RUT es válido, false si no lo es
 *
 * @example
 * validateChileanRUT('15.895.771-k'); // Returns: true
 * validateChileanRUT('15895771k');    // Returns: true
 * validateChileanRUT('12.345.678-9'); // Returns: false (dígito verificador incorrecto)
 */
function validateChileanRUT($rut) {
    if (empty($rut)) {
        return false;
    }

    // Limpiar RUT (eliminar puntos, guiones y espacios)
    $rut = strtoupper(preg_replace('/[^0-9kK]/', '', trim($rut)));

    if (strlen($rut) < 2) {
        return false;
    }

    // Separar cuerpo y dígito verificador
    $body = substr($rut, 0, -1);
    $dv = substr($rut, -1);

    // Validar que el cuerpo contenga solo números
    if (!is_numeric($body)) {
        return false;
    }

    // Validar longitud (RUT chileno: 7-8 dígitos + DV)
    if (strlen($body) < 7 || strlen($body) > 8) {
        return false;
    }

    // Calcular dígito verificador usando algoritmo Módulo 11
    $suma = 0;
    $multiplo = 2;

    // Recorrer el cuerpo del RUT de derecha a izquierda
    for ($i = strlen($body) - 1; $i >= 0; $i--) {
        $suma += intval($body[$i]) * $multiplo;
        $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
    }

    $dvEsperado = 11 - ($suma % 11);
    $dvCalculado = $dvEsperado === 11 ? '0' : ($dvEsperado === 10 ? 'K' : strval($dvEsperado));

    return $dv === $dvCalculado;
}

/**
 * Limpia el formato de un RUT (elimina puntos, guiones y espacios)
 *
 * @param string $rut RUT con formato
 * @return string RUT sin formato (solo números y K)
 *
 * @example
 * cleanRUT('15.895.771-k'); // Returns: 15895771K
 */
function cleanRUT($rut) {
    return strtoupper(preg_replace('/[^0-9kK]/', '', trim($rut)));
}

/**
 * Formatea un RUT con puntos y guión (XX.XXX.XXX-X)
 *
 * @param string $rut RUT sin formato o con formato
 * @return string RUT formateado
 *
 * @example
 * formatChileanRUT('15895771k');     // Returns: 15.895.771-K
 * formatChileanRUT('15.895.771-k');  // Returns: 15.895.771-K
 */
function formatChileanRUT($rut) {
    // Limpiar RUT primero
    $rut = cleanRUT($rut);

    if (strlen($rut) < 2) {
        return $rut;
    }

    // Separar cuerpo y dígito verificador
    $body = substr($rut, 0, -1);
    $dv = substr($rut, -1);

    // Formatear el cuerpo con puntos (separador de miles)
    $formattedBody = number_format(intval($body), 0, '', '.');

    return $formattedBody . '-' . $dv;
}

/**
 * Valida y sanitiza un RUT para almacenamiento en base de datos
 *
 * @param string $rut RUT a validar y sanitizar
 * @param bool $required Si el RUT es requerido (default: true)
 * @return array ['valid' => bool, 'rut' => string|null, 'error' => string|null]
 *
 * @example
 * $result = validateAndSanitizeRUT('15.895.771-k');
 * if ($result['valid']) {
 *     // Guardar $result['rut'] en la base de datos
 * } else {
 *     echo $result['error'];
 * }
 */
function validateAndSanitizeRUT($rut, $required = true) {
    $rut = trim($rut);

    // Si está vacío
    if (empty($rut)) {
        if ($required) {
            return [
                'valid' => false,
                'rut' => null,
                'error' => 'RUT es requerido'
            ];
        } else {
            return [
                'valid' => true,
                'rut' => null,
                'error' => null
            ];
        }
    }

    // Validar RUT
    if (!validateChileanRUT($rut)) {
        return [
            'valid' => false,
            'rut' => null,
            'error' => 'RUT inválido. Verifica el dígito verificador.'
        ];
    }

    // Limpiar y retornar
    return [
        'valid' => true,
        'rut' => cleanRUT($rut),
        'error' => null
    ];
}

/**
 * Valida múltiples RUTs a la vez
 *
 * @param array $ruts Array de RUTs a validar
 * @return array Array con los resultados ['rut' => bool]
 *
 * @example
 * $results = validateMultipleRUTs(['15.895.771-k', '77.866.873-4', '12.345.678-9']);
 * // Returns: ['15.895.771-k' => true, '77.866.873-4' => true, '12.345.678-9' => false]
 */
function validateMultipleRUTs($ruts) {
    $results = [];

    foreach ($ruts as $rut) {
        $results[$rut] = validateChileanRUT($rut);
    }

    return $results;
}

/**
 * Genera un dígito verificador para un RUT dado
 * (útil para testing o generación de datos de prueba)
 *
 * @param string $rutBody Cuerpo del RUT (sin dígito verificador)
 * @return string Dígito verificador calculado
 *
 * @example
 * generateRUTVerifier('15895771'); // Returns: K
 * generateRUTVerifier('77866873'); // Returns: 4
 */
function generateRUTVerifier($rutBody) {
    $rutBody = preg_replace('/[^0-9]/', '', $rutBody);

    if (!is_numeric($rutBody) || strlen($rutBody) < 7) {
        return null;
    }

    $suma = 0;
    $multiplo = 2;

    for ($i = strlen($rutBody) - 1; $i >= 0; $i--) {
        $suma += intval($rutBody[$i]) * $multiplo;
        $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
    }

    $dvEsperado = 11 - ($suma % 11);
    return $dvEsperado === 11 ? '0' : ($dvEsperado === 10 ? 'K' : strval($dvEsperado));
}

/**
 * Verifica si un string tiene formato de RUT válido (sin validar dígito)
 *
 * @param string $rut String a verificar
 * @return bool True si tiene formato de RUT
 *
 * @example
 * hasRUTFormat('15.895.771-k');  // Returns: true
 * hasRUTFormat('15895771k');     // Returns: true
 * hasRUTFormat('abc123');        // Returns: false
 */
function hasRUTFormat($rut) {
    $clean = cleanRUT($rut);

    // Debe tener al menos 8 caracteres (7 dígitos + 1 DV)
    if (strlen($clean) < 8) {
        return false;
    }

    // El cuerpo debe ser numérico
    $body = substr($clean, 0, -1);
    if (!is_numeric($body)) {
        return false;
    }

    // El DV debe ser número o K
    $dv = substr($clean, -1);
    if (!preg_match('/^[0-9K]$/', $dv)) {
        return false;
    }

    return true;
}

/**
 * Middleware de validación de RUT para usar en endpoints API
 *
 * @param array $data Datos del POST/GET
 * @param string $field Nombre del campo que contiene el RUT
 * @param bool $required Si el RUT es requerido
 * @return array ['success' => bool, 'data' => array, 'error' => string|null]
 *
 * @example
 * $validation = validateRUTMiddleware($_POST, 'tax_id', true);
 * if (!$validation['success']) {
 *     http_response_code(400);
 *     echo json_encode(['error' => $validation['error']]);
 *     exit;
 * }
 * $cleanData = $validation['data'];
 */
function validateRUTMiddleware($data, $field = 'tax_id', $required = true) {
    $rut = isset($data[$field]) ? trim($data[$field]) : '';

    $result = validateAndSanitizeRUT($rut, $required);

    if (!$result['valid']) {
        return [
            'success' => false,
            'data' => null,
            'error' => $result['error']
        ];
    }

    // Actualizar el campo con el RUT limpio
    $data[$field] = $result['rut'];

    return [
        'success' => true,
        'data' => $data,
        'error' => null
    ];
}

// =============================================================================
// EJEMPLOS DE USO
// =============================================================================

/*
// Ejemplo 1: Validación simple
if (validateChileanRUT('15.895.771-k')) {
    echo "RUT válido";
}

// Ejemplo 2: Validar y limpiar para guardar en BD
$result = validateAndSanitizeRUT($_POST['tax_id']);
if ($result['valid']) {
    $db->insert('employees', ['tax_id' => $result['rut']]);
} else {
    echo $result['error'];
}

// Ejemplo 3: Uso en un endpoint API
$validation = validateRUTMiddleware($_POST, 'tax_id', true);
if (!$validation['success']) {
    http_response_code(400);
    echo json_encode(['error' => $validation['error']]);
    exit;
}
$cleanData = $validation['data'];

// Ejemplo 4: Formatear RUT para mostrar en UI
$formattedRUT = formatChileanRUT('15895771k');
echo $formattedRUT; // Output: 15.895.771-K

// Ejemplo 5: Generar RUT de prueba
$dv = generateRUTVerifier('15895771');
$testRUT = '15895771' . $dv; // 15895771K

// Ejemplo 6: Validar múltiples RUTs
$ruts = ['15.895.771-k', '77.866.873-4', '12.345.678-9'];
$results = validateMultipleRUTs($ruts);
foreach ($results as $rut => $valid) {
    echo "$rut: " . ($valid ? 'válido' : 'inválido') . "\n";
}
*/

?>
