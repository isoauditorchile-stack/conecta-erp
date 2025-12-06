<?php
/**
 * AUDITOR PRO - API v1
 * Middleware de Autenticación
 *
 * @package AuditorPRO
 * @version 1.0
 */

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {

    /**
     * Verificar autenticación JWT
     *
     * @return array|false Datos del usuario si está autenticado, false si no
     */
    public static function authenticate() {
        // Obtener token del header Authorization
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            Response::unauthorized('Missing authorization header');
            return false;
        }

        $authHeader = $headers['Authorization'];

        // Verificar formato Bearer
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            Response::unauthorized('Invalid authorization header format');
            return false;
        }

        $jwt = $matches[1];

        // Decodificar y validar JWT
        $payload = JWT::decode($jwt);

        if ($payload === false) {
            Response::unauthorized('Invalid or expired token');
            return false;
        }

        // Retornar datos del usuario
        return $payload;
    }

    /**
     * Verificar que el usuario tenga un rol específico
     *
     * @param array $user Datos del usuario
     * @param string|array $roles Rol(es) requerido(s)
     * @return bool
     */
    public static function hasRole($user, $roles) {
        if (!isset($user['role'])) {
            return false;
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($user['role'], $roles);
    }

    /**
     * Verificar que el usuario tenga un permiso específico
     *
     * @param array $user Datos del usuario
     * @param string $permission Permiso requerido
     * @return bool
     */
    public static function hasPermission($user, $permission) {
        if (!isset($user['permissions'])) {
            return false;
        }

        return in_array($permission, $user['permissions']);
    }

    /**
     * Middleware para requerir autenticación
     */
    public static function require() {
        $user = self::authenticate();

        if ($user === false) {
            exit;
        }

        return $user;
    }

    /**
     * Middleware para requerir rol específico
     */
    public static function requireRole($roles) {
        $user = self::require();

        if (!self::hasRole($user, $roles)) {
            Response::forbidden('Insufficient permissions');
            exit;
        }

        return $user;
    }

    /**
     * Middleware para requerir permiso específico
     */
    public static function requirePermission($permission) {
        $user = self::require();

        if (!self::hasPermission($user, $permission)) {
            Response::forbidden('Insufficient permissions');
            exit;
        }

        return $user;
    }
}
