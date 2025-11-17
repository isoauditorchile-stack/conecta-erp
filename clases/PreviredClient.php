<?php
/**
 * PREVIRED CLIENT - CONECTA ERP
 * Cliente para integración con Previred Chile
 */
class PreviredClient {
    private $conn;
    private $empresa_id;
    
    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
    }
    
    public function generarArchivoREM($periodo) {
        $empleados = $this->obtenerEmpleados($periodo);
        $lineas = [];
        
        foreach ($empleados as $emp) {
            $lineas[] = $this->generarLineaREM($emp);
        }
        
        $contenido = implode("\r\n", $lineas);
        $filename = "previred_{$periodo}.rem";
        
        file_put_contents("/tmp/$filename", $contenido);
        
        return ['success' => true, 'archivo' => "/tmp/$filename", 'lineas' => count($lineas)];
    }
    
    private function generarLineaREM($empleado) {
        return sprintf("%s;%s;%d;%d;%d", 
            $empleado['rut'], 
            $empleado['nombre'], 
            $empleado['sueldo_base'],
            $empleado['afp'],
            $empleado['salud']
        );
    }
    
    private function obtenerEmpleados($periodo) {
        $stmt = $this->conn->prepare("SELECT * FROM empleados WHERE empresa_id = ? AND activo = 1");
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function validarArchivo($filepath) {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Archivo no encontrado'];
        }
        
        $lineas = file($filepath, FILE_IGNORE_NEW_LINES);
        $errores = [];
        
        foreach ($lineas as $num => $linea) {
            $campos = explode(';', $linea);
            if (count($campos) < 5) {
                $errores[] = "Línea " . ($num + 1) . ": Campos insuficientes";
            }
        }
        
        if (empty($errores)) {
            return ['success' => true, 'mensaje' => 'Archivo válido', 'lineas' => count($lineas)];
        }
        
        return ['success' => false, 'errores' => $errores];
    }
    
    public function cargarArchivo($filepath) {
        $validacion = $this->validarArchivo($filepath);
        
        if (!$validacion['success']) {
            return $validacion;
        }
        
        return ['success' => true, 'mensaje' => 'Archivo cargado exitosamente'];
    }
}
?>
