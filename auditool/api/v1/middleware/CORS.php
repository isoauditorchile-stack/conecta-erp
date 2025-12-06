<?php
/**
 * AUDITOR PRO - API v1
 * Middleware de CORS (Cross-Origin Resource Sharing)
 *
 * @package AuditorPRO
 * @version 1.0
 */

class CORSMiddleware {

    /**
     * Aplicar headers CORS
     */
    public static function apply() {
        // Permitir origen
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // En producción, validar contra lista de orígenes permitidos
            header('Access-Control-Allow-Origin: ' . (CORS_ALLOWED_ORIGINS === '*' ? $_SERVER['HTTP_ORIGIN'] : CORS_ALLOWED_ORIGINS));
            header('Access-Control-Allow-Credentials: true');
        } else {
            header('Access-Control-Allow-Origin: ' . CORS_ALLOWED_ORIGINS);
        }

        // Métodos permitidos
        header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);

        // Headers permitidos
        header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);

        // Tiempo de cache para preflight
        header('Access-Control-Max-Age: 86400');

        // Manejar preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
