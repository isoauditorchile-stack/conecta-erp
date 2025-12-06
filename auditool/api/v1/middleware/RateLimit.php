<?php
/**
 * AUDITOR PRO - API v1
 * Middleware de Rate Limiting
 *
 * @package AuditorPRO
 * @version 1.0
 */

require_once __DIR__ . '/../utils/Response.php';

class RateLimitMiddleware {

    private static $storageFile = __DIR__ . '/../../../data/rate_limits.json';

    /**
     * Obtener identificador del cliente
     */
    private static function getClientId() {
        // Usar IP + User Agent como identificador
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return md5($ip . $userAgent);
    }

    /**
     * Cargar datos de rate limits
     */
    private static function loadData() {
        if (!file_exists(self::$storageFile)) {
            return [];
        }

        $data = file_get_contents(self::$storageFile);
        return json_decode($data, true) ?? [];
    }

    /**
     * Guardar datos de rate limits
     */
    private static function saveData($data) {
        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(self::$storageFile, json_encode($data));
    }

    /**
     * Limpiar datos antiguos
     */
    private static function cleanup(&$data) {
        $now = time();

        foreach ($data as $clientId => $clientData) {
            if ($clientData['reset_time'] < $now) {
                unset($data[$clientId]);
            }
        }
    }

    /**
     * Aplicar rate limiting
     *
     * @param int $maxRequests Máximo de requests permitidos
     * @param int $windowSeconds Ventana de tiempo en segundos
     */
    public static function apply($maxRequests = null, $windowSeconds = null) {
        if ($maxRequests === null) {
            $maxRequests = API_RATE_LIMIT;
        }

        if ($windowSeconds === null) {
            $windowSeconds = API_RATE_LIMIT_WINDOW;
        }

        $clientId = self::getClientId();
        $now = time();

        // Cargar datos
        $data = self::loadData();

        // Limpiar datos antiguos
        self::cleanup($data);

        // Inicializar cliente si no existe
        if (!isset($data[$clientId])) {
            $data[$clientId] = [
                'requests' => 0,
                'reset_time' => $now + $windowSeconds
            ];
        }

        // Verificar si la ventana ha expirado
        if ($data[$clientId]['reset_time'] < $now) {
            $data[$clientId] = [
                'requests' => 0,
                'reset_time' => $now + $windowSeconds
            ];
        }

        // Incrementar contador
        $data[$clientId]['requests']++;

        // Agregar headers de rate limit
        $remaining = max(0, $maxRequests - $data[$clientId]['requests']);
        $resetTime = $data[$clientId]['reset_time'];

        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $resetTime);

        // Verificar si se excedió el límite
        if ($data[$clientId]['requests'] > $maxRequests) {
            self::saveData($data);

            Response::tooManyRequests('Rate limit exceeded. Try again in ' . ($resetTime - $now) . ' seconds.');
            exit;
        }

        // Guardar datos
        self::saveData($data);
    }
}
