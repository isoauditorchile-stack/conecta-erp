<?php
/**
 * PERIODO MANAGER - Gestión automática de períodos contables
 */

class PeriodoManager {

    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Cerrar período automáticamente (llamar al final de cada mes)
     */
    public function cerrarPeriodoAutomatico($empresa_id) {
        try {
            $periodo_actual = date('Y-m');
            $ultimo_dia_mes = date('Y-m-t');

            // Verificar si ya estamos en el último día del mes
            if (date('Y-m-d') !== $ultimo_dia_mes) {
                return [
                    'success' => false,
                    'message' => 'Solo se puede cerrar el período el último día del mes'
                ];
            }

            // Verificar si el período ya fue cerrado
            $stmt = $this->conn->prepare("SELECT estado FROM periodos_contables WHERE empresa_id = ? AND periodo = ?");
            $stmt->bind_param("is", $empresa_id, $periodo_actual);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result && $result['estado'] === 'cerrado') {
                return [
                    'success' => false,
                    'message' => 'El período ya está cerrado'
                ];
            }

            $this->conn->begin_transaction();

            // Actualizar el período actual a cerrado
            $stmt = $this->conn->prepare("UPDATE periodos_contables SET estado = 'cerrado', periodo_actual = 0 WHERE empresa_id = ? AND periodo = ?");
            $stmt->bind_param("is", $empresa_id, $periodo_actual);
            $stmt->execute();

            // Crear el nuevo período (mes siguiente)
            $periodo_siguiente = date('Y-m', strtotime('+1 month'));

            $stmt = $this->conn->prepare("INSERT INTO periodos_contables (empresa_id, periodo, estado, periodo_actual, fecha_apertura) VALUES (?, ?, 'abierto', 1, CURDATE()) ON DUPLICATE KEY UPDATE periodo_actual = 1, estado = 'abierto'");
            $stmt->bind_param("is", $empresa_id, $periodo_siguiente);
            $stmt->execute();

            $this->conn->commit();

            return [
                'success' => true,
                'message' => "Período $periodo_actual cerrado correctamente. Nuevo período: $periodo_siguiente",
                'periodo_cerrado' => $periodo_actual,
                'periodo_nuevo' => $periodo_siguiente
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => 'Error al cerrar período: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Abrir nuevo período manualmente
     */
    public function abrirPeriodo($empresa_id, $periodo) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO periodos_contables (empresa_id, periodo, estado, periodo_actual, fecha_apertura) VALUES (?, ?, 'abierto', 1, CURDATE())");
            $stmt->bind_param("is", $empresa_id, $periodo);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Período abierto correctamente'
                ];
            } else {
                throw new Exception($stmt->error);
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al abrir período: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener período actual
     */
    public function getPeriodoActual($empresa_id) {
        $stmt = $this->conn->prepare("SELECT * FROM periodos_contables WHERE empresa_id = ? AND periodo_actual = 1 LIMIT 1");
        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Verificar si se puede registrar movimiento en un período
     */
    public function puedeRegistrarMovimiento($empresa_id, $fecha) {
        $periodo = date('Y-m', strtotime($fecha));

        $stmt = $this->conn->prepare("SELECT estado FROM periodos_contables WHERE empresa_id = ? AND periodo = ?");
        $stmt->bind_param("is", $empresa_id, $periodo);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            // Si no existe el período, está abierto
            return true;
        }

        return $result['estado'] === 'abierto';
    }
}
?>
