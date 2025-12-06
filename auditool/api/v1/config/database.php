<?php
/**
 * AUDITOR PRO - API v1
 * Configuración de Base de Datos
 *
 * @package AuditorPRO
 * @version 1.0
 * @author AUDITOR PRO Team
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'auditor_pro';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    /**
     * Obtener conexión PDO
     * @return PDO|null
     */
    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }

        return $this->conn;
    }
}
