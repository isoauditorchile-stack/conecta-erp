<?php
/**
 * TRANSBANK CLIENT - CONECTA ERP
 * Cliente para integración con Transbank Webpay Plus
 * Permite procesar pagos online con tarjetas de crédito/débito
 *
 * IMPORTANTE: Requiere credenciales de Transbank
 * - Modo Desarrollo: Usa credenciales de prueba
 * - Modo Producción: Usa credenciales reales
 *
 * Documentación: https://www.transbankdevelopers.cl/
 */

class TransbankClient {
    private $conn;
    private $empresa_id;
    private $modo; // 'desarrollo' o 'produccion'
    private $commerce_code;
    private $api_key;
    private $base_url;

    // URLs de Transbank
    const URL_DESARROLLO = 'https://webpay3gint.transbank.cl';
    const URL_PRODUCCION = 'https://webpay3g.transbank.cl';

    // Credenciales de prueba (Transbank)
    const TEST_COMMERCE_CODE = '597055555532';
    const TEST_API_KEY = '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C';

    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;

        // Obtener configuración de la empresa
        $config = $this->obtenerConfiguracion();

        $this->modo = $config['modo_transbank'] ?? 'desarrollo';
        $this->commerce_code = $config['transbank_commerce_code'] ?? self::TEST_COMMERCE_CODE;
        $this->api_key = $config['transbank_api_key'] ?? self::TEST_API_KEY;
        $this->base_url = $this->modo === 'produccion' ? self::URL_PRODUCCION : self::URL_DESARROLLO;
    }

    /**
     * Obtener configuración de Transbank de la empresa
     */
    private function obtenerConfiguracion() {
        $stmt = $this->conn->prepare("SELECT * FROM configuracion_empresa WHERE empresa_id = ? LIMIT 1");
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result ?? [];
    }

    /**
     * Crear una transacción de pago
     *
     * @param float $monto Monto total a cobrar
     * @param string $orden_id ID de la orden/pedido
     * @param string $return_url URL de retorno después del pago
     * @return array ['success' => bool, 'token' => string, 'url' => string, 'error' => string]
     */
    public function crearTransaccion($monto, $orden_id, $return_url) {
        try {
            $endpoint = '/rswebpaytransaction/api/webpay/v1.2/transactions';
            $url = $this->base_url . $endpoint;

            $data = [
                'buy_order' => $orden_id,
                'session_id' => session_id(),
                'amount' => round($monto),
                'return_url' => $return_url
            ];

            $response = $this->makeRequest('POST', $url, $data);

            if ($response['success']) {
                // Guardar transacción en BD
                $this->registrarTransaccion($orden_id, $response['data']['token'], $monto, 'creada');

                return [
                    'success' => true,
                    'token' => $response['data']['token'],
                    'url' => $response['data']['url'] . '?token_ws=' . $response['data']['token'],
                    'data' => $response['data']
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error al crear transacción'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirmar una transacción (después del pago)
     *
     * @param string $token Token recibido de Transbank
     * @return array ['success' => bool, 'data' => array, 'error' => string]
     */
    public function confirmarTransaccion($token) {
        try {
            $endpoint = "/rswebpaytransaction/api/webpay/v1.2/transactions/$token";
            $url = $this->base_url . $endpoint;

            $response = $this->makeRequest('PUT', $url);

            if ($response['success']) {
                $data = $response['data'];

                // Actualizar transacción en BD
                $estado = $data['response_code'] === 0 ? 'aprobada' : 'rechazada';
                $this->actualizarTransaccion($token, $estado, json_encode($data));

                return [
                    'success' => $data['response_code'] === 0,
                    'aprobada' => $data['response_code'] === 0,
                    'data' => $data,
                    'orden_id' => $data['buy_order'],
                    'monto' => $data['amount'],
                    'codigo_autorizacion' => $data['authorization_code'] ?? null,
                    'tipo_pago' => $data['payment_type_code'] ?? null,
                    'cuotas' => $data['installments_number'] ?? 0
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error al confirmar transacción'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Anular/Reversar una transacción
     *
     * @param string $token Token de la transacción
     * @param float $monto Monto a anular
     * @return array ['success' => bool, 'data' => array, 'error' => string]
     */
    public function anularTransaccion($token, $monto) {
        try {
            $endpoint = "/rswebpaytransaction/api/webpay/v1.2/transactions/$token/refunds";
            $url = $this->base_url . $endpoint;

            $data = [
                'amount' => round($monto)
            ];

            $response = $this->makeRequest('POST', $url, $data);

            if ($response['success']) {
                $this->actualizarTransaccion($token, 'anulada', json_encode($response['data']));

                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error al anular transacción'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Realizar petición HTTP a Transbank
     */
    private function makeRequest($method, $url, $data = null) {
        $headers = [
            'Content-Type: application/json',
            'Tbk-Api-Key-Id: ' . $this->commerce_code,
            'Tbk-Api-Key-Secret: ' . $this->api_key
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $curl_error
            ];
        }

        $result = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => $result
            ];
        }

        return [
            'success' => false,
            'error' => $result['error_message'] ?? 'Error HTTP: ' . $http_code
        ];
    }

    /**
     * Registrar transacción en base de datos
     */
    private function registrarTransaccion($orden_id, $token, $monto, $estado) {
        $stmt = $this->conn->prepare("INSERT INTO transacciones_pago (empresa_id, orden_id, token, monto, estado, metodo_pago, fecha_creacion) VALUES (?, ?, ?, ?, ?, 'transbank', NOW())");
        $stmt->bind_param("issds", $this->empresa_id, $orden_id, $token, $monto, $estado);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Actualizar estado de transacción
     */
    private function actualizarTransaccion($token, $estado, $respuesta_json) {
        $stmt = $this->conn->prepare("UPDATE transacciones_pago SET estado = ?, respuesta_json = ?, fecha_actualizacion = NOW() WHERE token = ?");
        $stmt->bind_param("sss", $estado, $respuesta_json, $token);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Obtener estado de una transacción
     */
    public function obtenerEstadoTransaccion($token) {
        $stmt = $this->conn->prepare("SELECT * FROM transacciones_pago WHERE token = ? AND empresa_id = ?");
        $stmt->bind_param("si", $token, $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $result;
    }

    /**
     * Verificar si está en modo de prueba
     */
    public function esModoDesarrollo() {
        return $this->modo === 'desarrollo';
    }

    /**
     * Obtener información del modo actual
     */
    public function getInfo() {
        return [
            'modo' => $this->modo,
            'commerce_code' => $this->commerce_code,
            'base_url' => $this->base_url,
            'es_prueba' => $this->esModoDesarrollo()
        ];
    }
}
?>
