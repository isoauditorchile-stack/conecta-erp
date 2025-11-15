<?php
/**
 * AUDITORPRO - Clase de Manejo de Base de Datos
 */

// Evitar acceso directo
if (!defined('ACCESO_PERMITIDO')) {
    die('Acceso denegado');
}

/**
 * Clase Singleton para manejo de conexión a base de datos
 */
class BaseDatos {
    private static $instancia = null;
    private $conexion;

    /**
     * Constructor privado para patrón Singleton
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . BD_HOST . ";dbname=" . BD_NOMBRE . ";charset=" . BD_CHARSET;
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . BD_CHARSET
            ];

            $this->conexion = new PDO($dsn, BD_USUARIO, BD_CLAVE, $opciones);
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
        }
    }

    /**
     * Obtener instancia única de la clase
     */
    public static function obtener_instancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtener conexión PDO
     */
    public function obtener_conexion() {
        return $this->conexion;
    }

    /**
     * Ejecutar consulta preparada
     */
    public function consultar($sql, $params = []) {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Obtener todos los resultados
     */
    public function obtener_todos($sql, $params = []) {
        $stmt = $this->consultar($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener un solo resultado
     */
    public function obtener_uno($sql, $params = []) {
        $stmt = $this->consultar($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Insertar registro y retornar ID
     */
    public function insertar($sql, $params = []) {
        $this->consultar($sql, $params);
        return $this->conexion->lastInsertId();
    }

    /**
     * Actualizar registros
     */
    public function actualizar($sql, $params = []) {
        $stmt = $this->consultar($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Eliminar registros
     */
    public function eliminar($sql, $params = []) {
        $stmt = $this->consultar($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Evitar clonación
     */
    private function __clone() {}

    /**
     * Evitar deserialización
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton");
    }
}
