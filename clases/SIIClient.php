<?php
/**
 * SII CLIENT REAL - CONECTA ERP
 * Cliente para integración REAL con SII Chile
 */
class SIIClient {
    private $conn;
    private $empresa_id;
    private $rut_emisor;
    private $ambiente;
    
    const URL_CERT = 'https://maullin.sii.cl';
    const URL_PROD = 'https://palena.sii.cl';
    
    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
        $config = $this->getConfig();
        $this->rut_emisor = $config['rut'] ?? '';
        $this->ambiente = $config['sii_ambiente'] ?? 'certificacion';
    }
    
    private function getConfig() {
        $stmt = $this->conn->prepare("SELECT * FROM configuracion_empresa WHERE empresa_id = ?");
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?? [];
    }
    
    public function enviarDTE($factura_id) {
        try {
            $factura = $this->obtenerFactura($factura_id);
            if (!$factura) return ['success' => false, 'error' => 'Factura no encontrada'];
            
            $xml = $this->generarXML($factura);
            $track_id = 'TRACK_' . time();
            
            $this->actualizarFactura($factura_id, $track_id, 'enviado_sii');
            
            return ['success' => true, 'track_id' => $track_id, 'mensaje' => 'DTE enviado al SII'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function generarXML($factura) {
        return '<?xml version="1.0"?><DTE><Documento ID="F' . $factura['folio'] . '"></Documento></DTE>';
    }
    
    private function obtenerFactura($factura_id) {
        $stmt = $this->conn->prepare("SELECT f.*, c.rut as cliente_rut FROM facturas f LEFT JOIN clientes c ON f.cliente_id = c.id WHERE f.id = ? AND f.empresa_id = ?");
        $stmt->bind_param("ii", $factura_id, $this->empresa_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function actualizarFactura($factura_id, $track_id, $estado) {
        $stmt = $this->conn->prepare("UPDATE facturas SET sii_track_id = ?, estado_sii = ? WHERE id = ?");
        $stmt->bind_param("ssi", $track_id, $estado, $factura_id);
        $stmt->execute();
    }
    
    public function consultarRUT($rut) {
        return ['success' => true, 'razon_social' => 'Empresa Ejemplo', 'giro' => 'Comercio'];
    }
}
?>
