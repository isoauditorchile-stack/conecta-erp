<?php
/**
 * SERVICIOS EXTERNOS - TRANSBANK WEBPAY PLUS
 * Integración con Transbank para procesar pagos
 * - Webpay Plus (tarjetas de crédito/débito)
 * - Webpay Modal
 * - Consulta de transacciones
 */

class TransbankClient {
    private $commerce_code;
    private $api_key;
    private $ambiente; // 'integracion' o 'produccion'

    const URL_INTEGRACION = 'https://webpay3gint.transbank.cl';
    const URL_PRODUCCION = 'https://webpay3g.transbank.cl';

    /**
     * Constructor
     */
    public function __construct($config) {
        $this->commerce_code = $config['commerce_code'];
        $this->api_key = $config['api_key'];
        $this->ambiente = $config['ambiente'] ?? 'integracion';
    }

    /**
     * Obtener URL base según ambiente
     */
    private function getBaseURL() {
        return $this->ambiente === 'produccion' ? self::URL_PRODUCCION : self::URL_INTEGRACION;
    }

    /**
     * Crear transacción Webpay Plus
     */
    public function crearTransaccion($orden_id, $monto, $session_id, $return_url) {
        $url = $this->getBaseURL() . '/rswebpaytransaction/api/webpay/v1.2/transactions';

        $data = [
            'buy_order' => $orden_id,
            'session_id' => $session_id,
            'amount' => $monto,
            'return_url' => $return_url
        ];

        $response = $this->request('POST', $url, $data);

        if (isset($response['token']) && isset($response['url'])) {
            return [
                'success' => true,
                'token' => $response['token'],
                'url' => $response['url']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error_message'] ?? 'Error desconocido'
        ];
    }

    /**
     * Confirmar transacción
     */
    public function confirmarTransaccion($token) {
        $url = $this->getBaseURL() . '/rswebpaytransaction/api/webpay/v1.2/transactions/' . $token;

        $response = $this->request('PUT', $url);

        if (isset($response['vci']) && $response['vci'] === 'TSY') {
            // Transacción aprobada
            return [
                'success' => true,
                'authorization_code' => $response['authorization_code'],
                'amount' => $response['amount'],
                'buy_order' => $response['buy_order'],
                'session_id' => $response['session_id'],
                'card_detail' => [
                    'card_number' => $response['card_detail']['card_number'] ?? null
                ],
                'transaction_date' => $response['transaction_date'],
                'accounting_date' => $response['accounting_date'],
                'payment_type_code' => $response['payment_type_code']
            ];
        }

        return [
            'success' => false,
            'status' => $response['status'] ?? 'FAILED',
            'response_code' => $response['response_code'] ?? -1
        ];
    }

    /**
     * Consultar estado de transacción
     */
    public function consultarTransaccion($token) {
        $url = $this->getBaseURL() . '/rswebpaytransaction/api/webpay/v1.2/transactions/' . $token;

        $response = $this->request('GET', $url);

        return [
            'success' => isset($response['vci']),
            'data' => $response
        ];
    }

    /**
     * Anular/Reversar transacción
     */
    public function anularTransaccion($token, $monto) {
        $url = $this->getBaseURL() . '/rswebpaytransaction/api/webpay/v1.2/transactions/' . $token . '/refunds';

        $data = ['amount' => $monto];

        $response = $this->request('POST', $url, $data);

        return [
            'success' => isset($response['type']) && $response['type'] === 'REVERSED',
            'data' => $response
        ];
    }

    /**
     * Realizar petición HTTP
     */
    private function request($method, $url, $data = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Tbk-Api-Key-Id: ' . $this->commerce_code,
            'Tbk-Api-Key-Secret: ' . $this->api_key,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($response, true) ?: [];
    }
}
