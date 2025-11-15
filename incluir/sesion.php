<?php
/**
 * AUDITORPRO - Clase de Manejo de Sesiones
 */

// Evitar acceso directo
if (!defined('ACCESO_PERMITIDO')) {
    die('Acceso denegado');
}

/**
 * Clase para manejo de sesiones de usuario
 */
class Sesion {
    /**
     * Iniciar sesión segura
     */
    public static function iniciar() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
            session_name(NOMBRE_SESION);
            session_start();

            // Regenerar ID de sesión periódicamente
            if (!isset($_SESSION['ultima_regeneracion'])) {
                $_SESSION['ultima_regeneracion'] = time();
            } else if (time() - $_SESSION['ultima_regeneracion'] > 300) {
                session_regenerate_id(true);
                $_SESSION['ultima_regeneracion'] = time();
            }
        }
    }

    /**
     * Destruir sesión
     */
    public static function destruir() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();

            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }

            session_destroy();
        }
    }

    /**
     * Establecer valor en sesión
     */
    public static function establecer($clave, $valor) {
        $_SESSION[$clave] = $valor;
    }

    /**
     * Obtener valor de sesión
     */
    public static function obtener($clave, $por_defecto = null) {
        return isset($_SESSION[$clave]) ? $_SESSION[$clave] : $por_defecto;
    }

    /**
     * Verificar si existe clave en sesión
     */
    public static function existe($clave) {
        return isset($_SESSION[$clave]);
    }

    /**
     * Eliminar clave de sesión
     */
    public static function eliminar($clave) {
        if (isset($_SESSION[$clave])) {
            unset($_SESSION[$clave]);
        }
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function esta_autenticado() {
        return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function es_administrador() {
        return isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
    }

    /**
     * Verificar si el usuario es SUPER ADMIN (auditorexchile@gmail.com)
     * Este es el ÚNICO usuario con acceso TOTAL a TODOS los módulos ISO
     */
    public static function es_super_admin() {
        if (!self::esta_autenticado()) {
            return false;
        }

        $correo_usuario = self::obtener('usuario_correo', '');
        return strtolower(trim($correo_usuario)) === strtolower(SUPER_ADMIN_EMAIL);
    }

    /**
     * Verificar si el usuario tiene acceso total al sistema
     * Solo auditorexchile@gmail.com tiene acceso total
     */
    public static function tiene_acceso_total() {
        return self::es_super_admin();
    }

    /**
     * Obtener información completa del usuario actual desde BD
     */
    public static function obtener_usuario_actual() {
        if (!self::esta_autenticado()) {
            return null;
        }

        $bd = BaseDatos::obtener_instancia();
        $stmt = $bd->consultar(
            "SELECT * FROM users WHERE id = ?",
            [self::obtener('id_usuario')]
        );
        return $stmt->fetch();
    }

    /**
     * Establecer datos de usuario en sesión tras login exitoso
     */
    public static function establecer_datos_usuario($usuario) {
        self::establecer('id_usuario', $usuario['id']);
        self::establecer('usuario_nombre', $usuario['firstname'] . ' ' . $usuario['lastname']);
        self::establecer('usuario_correo', $usuario['email']);
        self::establecer('id_empresa', $usuario['company_id'] ?? 0);
        self::establecer('es_admin', $usuario['is_admin'] == 1 || $usuario['is_super_admin'] == 1);
        self::establecer('plan_actual', $usuario['plan_actual'] ?? 'TRIAL');
        self::establecer('estado', $usuario['status'] ?? 'pending_approval');
    }

    /**
     * Verificar estado de trial del usuario
     */
    public static function verificar_estado_trial() {
        if (!self::esta_autenticado()) {
            return false;
        }

        // Super admin siempre tiene acceso
        if (self::es_super_admin()) {
            return true;
        }

        $usuario = self::obtener_usuario_actual();
        if (!$usuario) {
            return false;
        }

        // Admin siempre tiene acceso
        if ($usuario['is_admin'] == 1 || $usuario['is_super_admin'] == 1) {
            return true;
        }

        // Verificar estado activo
        if ($usuario['status'] === 'active') {
            return true;
        }

        // Verificar trial
        if ($usuario['status'] === 'trial' && $usuario['trial_ends_at']) {
            return strtotime($usuario['trial_ends_at']) > time();
        }

        return false;
    }

    /**
     * Generar token CSRF
     */
    public static function generar_token_csrf() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar token CSRF
     */
    public static function verificar_token_csrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Establecer mensaje flash
     */
    public static function establecer_flash($tipo, $mensaje) {
        $_SESSION['flash_tipo'] = $tipo;
        $_SESSION['flash_mensaje'] = $mensaje;
    }

    /**
     * Obtener y limpiar mensaje flash
     */
    public static function obtener_flash() {
        if (isset($_SESSION['flash_mensaje'])) {
            $flash = [
                'tipo' => $_SESSION['flash_tipo'] ?? 'info',
                'mensaje' => $_SESSION['flash_mensaje']
            ];
            unset($_SESSION['flash_tipo']);
            unset($_SESSION['flash_mensaje']);
            return $flash;
        }
        return null;
    }
}
