<?php
/**
 * AUDITOR PRO - API v1
 * Utilidad para respuestas HTTP estandarizadas
 *
 * @package AuditorPRO
 * @version 1.0
 */

class Response {

    /**
     * Enviar respuesta JSON
     */
    public static function json($data, $statusCode = 200, $headers = []) {
        http_response_code($statusCode);

        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Respuesta exitosa
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response, $statusCode);
    }

    /**
     * Respuesta de error
     */
    public static function error($message, $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $statusCode
            ],
            'timestamp' => date('c')
        ];

        if ($errors !== null) {
            $response['error']['details'] = $errors;
        }

        self::json($response, $statusCode);
    }

    /**
     * Respuesta de error de validación
     */
    public static function validationError($errors) {
        self::error('Validation failed', 422, $errors);
    }

    /**
     * Respuesta de no autorizado
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * Respuesta de prohibido
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }

    /**
     * Respuesta de no encontrado
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Respuesta de error del servidor
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }

    /**
     * Respuesta de límite de tasa excedido
     */
    public static function tooManyRequests($message = 'Too many requests') {
        self::error($message, 429);
    }

    /**
     * Respuesta paginada
     */
    public static function paginated($data, $total, $page, $pageSize, $message = 'Success') {
        $totalPages = ceil($total / $pageSize);

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'page' => (int)$page,
                'page_size' => (int)$pageSize,
                'total_pages' => (int)$totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'timestamp' => date('c')
        ];

        self::json($response, 200);
    }
}
