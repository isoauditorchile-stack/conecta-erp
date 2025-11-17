<?php
/**
 * INDICADORES API - Actualización automática de indicadores económicos
 * Obtiene datos desde mindicador.cl (API del Banco Central de Chile)
 */

class IndicadoresAPI {

    private $conn;
    private $api_url = 'https://mindicador.cl/api';

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Actualizar todos los indicadores
     */
    public function actualizarIndicadores() {
        try {
            $indicadores = $this->obtenerIndicadoresAPI();

            if (!$indicadores) {
                throw new Exception("No se pudieron obtener los indicadores de la API");
            }

            $fecha = date('Y-m-d');

            $stmt = $this->conn->prepare("INSERT INTO indicadores_economicos (fecha, dolar, uf, utm, euro) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE dolar = VALUES(dolar), uf = VALUES(uf), utm = VALUES(utm), euro = VALUES(euro)");

            $stmt->bind_param("sdddd",
                $fecha,
                $indicadores['dolar'],
                $indicadores['uf'],
                $indicadores['utm'],
                $indicadores['euro']
            );

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Indicadores actualizados correctamente',
                    'data' => $indicadores,
                    'fecha' => $fecha
                ];
            } else {
                throw new Exception("Error al guardar indicadores: " . $stmt->error);
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener indicadores desde la API de mindicador.cl
     */
    private function obtenerIndicadoresAPI() {
        try {
            // Intentar obtener los datos de la API
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'CONECTA ERP/1.0'
                ]
            ]);

            $response = @file_get_contents($this->api_url, false, $context);

            if ($response === false) {
                // Si falla la API, obtener los últimos valores guardados
                $stmt = $this->conn->query("SELECT dolar, uf, utm, euro FROM indicadores_economicos ORDER BY fecha DESC LIMIT 1");
                $last = $stmt->fetch_assoc();

                if ($last) {
                    return $last;
                }

                // Si no hay datos guardados, devolver valores por defecto
                return [
                    'dolar' => 900.00,
                    'uf' => 36500.00,
                    'utm' => 65000.00,
                    'euro' => 1000.00
                ];
            }

            $data = json_decode($response, true);

            if (!$data) {
                throw new Exception("Error al decodificar respuesta de la API");
            }

            return [
                'dolar' => isset($data['dolar']['valor']) ? floatval($data['dolar']['valor']) : 900.00,
                'uf' => isset($data['uf']['valor']) ? floatval($data['uf']['valor']) : 36500.00,
                'utm' => isset($data['utm']['valor']) ? floatval($data['utm']['valor']) : 65000.00,
                'euro' => isset($data['euro']['valor']) ? floatval($data['euro']['valor']) : 1000.00
            ];

        } catch (Exception $e) {
            error_log("Error en IndicadoresAPI: " . $e->getMessage());

            // Devolver valores por defecto si todo falla
            return [
                'dolar' => 900.00,
                'uf' => 36500.00,
                'utm' => 65000.00,
                'euro' => 1000.00
            ];
        }
    }

    /**
     * Obtener indicador específico
     */
    public function obtenerIndicador($tipo) {
        $tipos_validos = ['dolar', 'uf', 'utm', 'euro'];

        if (!in_array($tipo, $tipos_validos)) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT $tipo FROM indicadores_economicos ORDER BY fecha DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result ? $result[$tipo] : null;
    }

    /**
     * Obtener histórico de un indicador
     */
    public function obtenerHistorico($tipo, $dias = 30) {
        $tipos_validos = ['dolar', 'uf', 'utm', 'euro'];

        if (!in_array($tipo, $tipos_validos)) {
            return [];
        }

        $stmt = $this->conn->prepare("SELECT fecha, $tipo as valor FROM indicadores_economicos WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY) ORDER BY fecha DESC");
        $stmt->bind_param("i", $dias);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
