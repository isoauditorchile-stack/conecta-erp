<?php
/**
 * AUDITOR PRO - API v1
 * Utilidad para manejo de JSON Web Tokens
 *
 * @package AuditorPRO
 * @version 1.0
 */

class JWT {

    /**
     * Codificar datos en Base64 URL-safe
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodificar datos desde Base64 URL-safe
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generar un JWT
     *
     * @param array $payload Datos a incluir en el token
     * @param int $expiration Tiempo de expiración en segundos
     * @return string JWT generado
     */
    public static function encode($payload, $expiration = null) {
        if ($expiration === null) {
            $expiration = JWT_EXPIRATION_TIME;
        }

        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => JWT_ALGORITHM
        ];

        // Payload con tiempo de expiración
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiration;

        // Codificar header y payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Crear signature
        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            JWT_SECRET_KEY,
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);

        // Retornar JWT completo
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Decodificar y validar un JWT
     *
     * @param string $jwt Token a decodificar
     * @return array|false Payload si es válido, false si no
     */
    public static function decode($jwt) {
        // Separar partes del JWT
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verificar signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            JWT_SECRET_KEY,
            true
        );

        if (!hash_equals($signature, $expectedSignature)) {
            return false; // Signature inválida
        }

        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expirado
        }

        return $payload;
    }

    /**
     * Verificar si un JWT es válido
     *
     * @param string $jwt Token a verificar
     * @return bool
     */
    public static function verify($jwt) {
        return self::decode($jwt) !== false;
    }

    /**
     * Obtener el payload de un JWT sin validar expiración
     *
     * @param string $jwt
     * @return array|false
     */
    public static function getPayload($jwt) {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return false;
        }

        return json_decode(self::base64UrlDecode($parts[1]), true);
    }
}
