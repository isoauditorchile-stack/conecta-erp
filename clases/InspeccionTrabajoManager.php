<?php
/**
 * ============================================
 * INSPECCIÓN DEL TRABAJO - CONECTA ERP
 * ============================================
 * Genera todos los datos requeridos para fiscalizaciones
 * de la Dirección del Trabajo (DT) de Chile
 *
 * Incluye:
 * - Libro de remuneraciones
 * - Contratos de trabajo
 * - Finiquitos
 * - Asistencia y jornadas
 * - Vacaciones
 * - Licencias médicas
 * - Horas extras
 *
 * @author CONECTA ERP
 * @version 2.0
 */

class InspeccionTrabajoManager {

    private $conn;
    private $empresa_id;

    /**
     * Constructor
     */
    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
    }

    /**
     * ============================================
     * LIBRO DE REMUNERACIONES
     * ============================================
     */

    /**
     * Generar Libro de Remuneraciones (Artículo 62 Código del Trabajo)
     *
     * @param string $periodo_desde YYYY-MM
     * @param string $periodo_hasta YYYY-MM
     * @return array Datos del libro
     */
    public function generarLibroRemuneraciones($periodo_desde, $periodo_hasta) {
        $query = "SELECT
                    l.periodo,
                    e.rut,
                    CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                    e.cargo,
                    e.fecha_ingreso,
                    l.total_haberes,
                    l.total_imponible,
                    l.total_descuentos,
                    l.liquido_pagar,
                    l.datos_json
                  FROM liquidaciones l
                  INNER JOIN empleados e ON l.empleado_id = e.id
                  LEFT JOIN usuarios u ON e.usuario_id = u.id
                  WHERE l.empresa_id = ?
                  AND l.periodo BETWEEN ? AND ?
                  AND l.estado IN ('calculada', 'pagada')
                  ORDER BY l.periodo ASC, e.rut ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $this->empresa_id, $periodo_desde, $periodo_hasta);
        $stmt->execute();
        $result = $stmt->get_result();

        $libro = [];

        while ($row = $result->fetch_assoc()) {
            $datos = json_decode($row['datos_json'], true);

            $libro[] = [
                'periodo' => $row['periodo'],
                'rut' => $row['rut'],
                'nombre' => $row['nombre_completo'],
                'cargo' => $row['cargo'],
                'fecha_ingreso' => $row['fecha_ingreso'],
                'dias_trabajados' => $this->obtenerDiasTrabajados($row['rut'], $row['periodo']),
                'sueldo_base' => $datos['haberes']['sueldo_base']['monto'] ?? 0,
                'horas_extras' => $datos['haberes']['horas_extras']['monto'] ?? 0,
                'comisiones' => $datos['haberes']['comisiones']['monto'] ?? 0,
                'bonos' => $datos['haberes']['bonos']['monto'] ?? 0,
                'gratificacion' => $datos['haberes']['gratificacion']['monto'] ?? 0,
                'asignacion_familiar' => $datos['haberes']['asignacion_familiar']['monto'] ?? 0,
                'movilizacion' => $datos['haberes']['movilizacion']['monto'] ?? 0,
                'colacion' => $datos['haberes']['colacion']['monto'] ?? 0,
                'total_haberes' => $row['total_haberes'],
                'total_imponible' => $row['total_imponible'],
                'afp' => $datos['descuentos']['afp']['monto'] ?? 0,
                'salud' => $datos['descuentos']['salud']['monto'] ?? 0,
                'afc' => $datos['descuentos']['afc']['monto'] ?? 0,
                'impuesto' => $datos['descuentos']['impuesto']['monto'] ?? 0,
                'anticipos' => $datos['descuentos']['anticipos']['monto'] ?? 0,
                'prestamos' => $datos['descuentos']['prestamos']['monto'] ?? 0,
                'total_descuentos' => $row['total_descuentos'],
                'liquido_pagar' => $row['liquido_pagar'],
                'fecha_pago' => $this->obtenerFechaPago($row['rut'], $row['periodo']),
                'firma_empleado' => '',
                'observaciones' => ''
            ];
        }

        return [
            'empresa' => $this->obtenerDatosEmpresa(),
            'periodo_desde' => $periodo_desde,
            'periodo_hasta' => $periodo_hasta,
            'registros' => $libro,
            'total_registros' => count($libro),
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * ============================================
     * CONTRATOS DE TRABAJO
     * ============================================
     */

    /**
     * Generar contrato de trabajo (Artículo 10 Código del Trabajo)
     *
     * @param int $empleado_id
     * @return array Datos del contrato
     */
    public function generarContrato($empleado_id) {
        $empleado = $this->obtenerEmpleado($empleado_id);

        if (!$empleado) {
            throw new Exception("Empleado no encontrado");
        }

        $empresa = $this->obtenerDatosEmpresa();

        // Artículo 10: Cláusulas mínimas del contrato
        $contrato = [
            // 1. Lugar y fecha del contrato
            'lugar_contrato' => $empresa['ciudad'],
            'fecha_contrato' => date('Y-m-d'),

            // 2. Individualización de las partes
            'empleador' => [
                'razon_social' => $empresa['razon_social'],
                'rut' => $empresa['rut'],
                'direccion' => $empresa['direccion'],
                'comuna' => $empresa['comuna'],
                'ciudad' => $empresa['ciudad'],
                'giro' => $empresa['giro'],
                'representante_legal' => $empresa['representante_legal'],
                'rut_representante' => $empresa['rut_representante']
            ],

            'trabajador' => [
                'nombre_completo' => $empleado['nombre_completo'],
                'rut' => $empleado['rut'],
                'fecha_nacimiento' => $empleado['fecha_nacimiento'],
                'nacionalidad' => $empleado['nacionalidad'] ?? 'Chilena',
                'estado_civil' => $empleado['estado_civil'] ?? 'Soltero/a',
                'direccion' => $empleado['direccion'],
                'comuna' => $empleado['comuna'],
                'ciudad' => $empleado['ciudad'],
                'telefono' => $empleado['telefono'],
                'email' => $empleado['email']
            ],

            // 3. Determinación de la naturaleza de los servicios y del lugar
            'cargo' => $empleado['cargo'],
            'descripcion_funciones' => $empleado['funciones'] ?? 'Funciones propias del cargo',
            'lugar_prestacion_servicios' => $empresa['direccion'],

            // 4. Monto, forma y período de pago
            'remuneracion' => [
                'sueldo_base' => $empleado['sueldo_base'],
                'periodo_pago' => $empleado['periodo_pago'] ?? 'mensual',
                'forma_pago' => $empleado['forma_pago'] ?? 'transferencia',
                'banco' => $empleado['banco'] ?? null,
                'numero_cuenta' => $empleado['numero_cuenta'] ?? null,
                'dia_pago' => $empleado['dia_pago'] ?? '5'
            ],

            // 5. Duración y distribución de la jornada
            'jornada' => [
                'tipo' => $empleado['tipo_jornada'] ?? 'ordinaria_completa',
                'horas_semanales' => $empleado['horas_semanales'] ?? 45,
                'distribucion' => $empleado['distribucion_jornada'] ?? 'Lunes a viernes: 09:00 a 18:00',
                'descanso_semanal' => 'Sábado y domingo'
            ],

            // 6. Plazo del contrato
            'tipo_contrato' => $empleado['tipo_contrato'] ?? 'indefinido',
            'fecha_inicio' => $empleado['fecha_ingreso'],
            'fecha_termino' => $empleado['fecha_termino'] ?? null,
            'duracion_meses' => $empleado['duracion_contrato_meses'] ?? null,

            // 7. Otros pactos
            'clausulas_adicionales' => [
                'periodo_prueba' => $empleado['periodo_prueba'] ?? false,
                'dias_prueba' => $empleado['dias_prueba'] ?? 0,
                'pacto_horas_extras' => $empleado['pacto_horas_extras'] ?? false,
                'pacto_no_competencia' => $empleado['pacto_no_competencia'] ?? false,
                'clausula_confidencialidad' => $empleado['clausula_confidencialidad'] ?? false
            ],

            // Beneficios adicionales
            'beneficios' => [
                'asignacion_movilizacion' => $empleado['asignacion_movilizacion'] ?? 0,
                'asignacion_colacion' => $empleado['asignacion_colacion'] ?? 0,
                'bonos_acordados' => $empleado['bonos_acordados'] ?? []
            ],

            // Previsión y salud
            'prevision' => [
                'afp' => $empleado['afp'],
                'sistema_salud' => $empleado['sistema_salud'],
                'isapre' => $empleado['nombre_isapre'] ?? null,
                'plan_isapre' => $empleado['plan_isapre'] ?? null
            ],

            // Generación
            'fecha_generacion' => date('Y-m-d H:i:s'),
            'estado' => 'vigente'
        ];

        return $contrato;
    }

    /**
     * ============================================
     * REGISTRO DE ASISTENCIA
     * ============================================
     */

    /**
     * Generar registro de asistencia para fiscalización
     *
     * @param string $fecha_desde
     * @param string $fecha_hasta
     * @return array Registro de asistencia
     */
    public function generarRegistroAsistencia($fecha_desde, $fecha_hasta) {
        $query = "SELECT
                    a.fecha,
                    e.rut,
                    CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                    e.cargo,
                    a.hora_entrada,
                    a.hora_salida,
                    a.horas_trabajadas,
                    a.horas_extras,
                    a.tipo_jornada,
                    a.observaciones
                  FROM asistencia a
                  INNER JOIN empleados e ON a.empleado_id = e.id
                  LEFT JOIN usuarios u ON e.usuario_id = u.id
                  WHERE a.empresa_id = ?
                  AND a.fecha BETWEEN ? AND ?
                  ORDER BY a.fecha ASC, e.rut ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $this->empresa_id, $fecha_desde, $fecha_hasta);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * ============================================
     * REGISTRO DE VACACIONES
     * ============================================
     */

    /**
     * Generar registro de vacaciones
     *
     * @return array Registro de vacaciones
     */
    public function generarRegistroVacaciones() {
        $query = "SELECT
                    e.rut,
                    CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                    e.cargo,
                    e.fecha_ingreso,
                    v.ano,
                    v.dias_legales,
                    v.dias_progresivos,
                    v.dias_totales,
                    v.dias_tomados,
                    v.dias_pendientes,
                    v.fecha_desde,
                    v.fecha_hasta,
                    v.fecha_reincorporacion,
                    v.estado
                  FROM vacaciones v
                  INNER JOIN empleados e ON v.empleado_id = e.id
                  LEFT JOIN usuarios u ON e.usuario_id = u.id
                  WHERE v.empresa_id = ?
                  ORDER BY e.rut ASC, v.ano DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * ============================================
     * REGISTRO DE HORAS EXTRAS
     * ============================================
     */

    /**
     * Generar registro de horas extras
     *
     * @param string $periodo_desde
     * @param string $periodo_hasta
     * @return array Registro de horas extras
     */
    public function generarRegistroHorasExtras($periodo_desde, $periodo_hasta) {
        $query = "SELECT
                    he.fecha,
                    e.rut,
                    CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                    e.cargo,
                    he.cantidad_horas,
                    he.tipo_hora_extra,
                    he.valor_hora,
                    he.monto_total,
                    he.autorizado_por,
                    he.motivo,
                    he.observaciones
                  FROM horas_extras he
                  INNER JOIN empleados e ON he.empleado_id = e.id
                  LEFT JOIN usuarios u ON e.usuario_id = u.id
                  WHERE he.empresa_id = ?
                  AND he.fecha BETWEEN ? AND ?
                  ORDER BY he.fecha ASC, e.rut ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $this->empresa_id, $periodo_desde, $periodo_hasta);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * ============================================
     * FINIQUITOS
     * ============================================
     */

    /**
     * Generar finiquito de trabajador
     *
     * @param int $empleado_id
     * @param array $datos_termino
     * @return array Finiquito completo
     */
    public function generarFiniquito($empleado_id, $datos_termino) {
        $empleado = $this->obtenerEmpleado($empleado_id);
        $empresa = $this->obtenerDatosEmpresa();

        // Calcular indemnizaciones
        $indemnizaciones = $this->calcularIndemnizaciones($empleado, $datos_termino);

        $finiquito = [
            'empresa' => $empresa,
            'trabajador' => $empleado,
            'termino' => [
                'fecha_termino' => $datos_termino['fecha_termino'],
                'causal' => $datos_termino['causal'],
                'articulo' => $datos_termino['articulo'],
                'descripcion_causal' => $datos_termino['descripcion']
            ],
            'liquidacion_final' => $indemnizaciones,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];

        return $finiquito;
    }

    /**
     * Calcular indemnizaciones por años de servicio
     */
    private function calcularIndemnizaciones($empleado, $datos_termino) {
        $fecha_ingreso = new DateTime($empleado['fecha_ingreso']);
        $fecha_termino = new DateTime($datos_termino['fecha_termino']);
        $anos_servicio = $fecha_ingreso->diff($fecha_termino)->y;

        $sueldo_base = $empleado['sueldo_base'];
        $ultima_remuneracion = $this->obtenerUltimaRemuneracion($empleado['id']);

        // Indemnización por años de servicio (Art. 163)
        $indemnizacion_anos = 0;
        if (in_array($datos_termino['causal'], ['161', '162', '163'])) {
            $indemnizacion_anos = $ultima_remuneracion * $anos_servicio;
            $indemnizacion_anos = min($indemnizacion_anos, $ultima_remuneracion * 11); // Tope 11 años
        }

        // Vacaciones proporcionales
        $vacaciones_pendientes = $this->calcularVacacionesPendientes($empleado['id'], $datos_termino['fecha_termino']);

        return [
            'ultima_remuneracion' => $ultima_remuneracion,
            'anos_servicio' => $anos_servicio,
            'indemnizacion_anos_servicio' => $indemnizacion_anos,
            'indemnizacion_mes_aviso' => 0,
            'vacaciones_proporcionales' => $vacaciones_pendientes,
            'feriado_legal' => 0,
            'total_indemnizaciones' => $indemnizacion_anos + $vacaciones_pendientes
        ];
    }

    /**
     * ============================================
     * FUNCIONES AUXILIARES
     * ============================================
     */

    private function obtenerDatosEmpresa() {
        $query = "SELECT * FROM empresas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function obtenerEmpleado($empleado_id) {
        $query = "SELECT e.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo
                  FROM empleados e
                  LEFT JOIN usuarios u ON e.usuario_id = u.id
                  WHERE e.id = ? AND e.empresa_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $empleado_id, $this->empresa_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function obtenerDiasTrabajados($rut, $periodo) {
        $query = "SELECT COUNT(DISTINCT fecha) as dias
                  FROM asistencia a
                  INNER JOIN empleados e ON a.empleado_id = e.id
                  WHERE e.rut = ?
                  AND DATE_FORMAT(a.fecha, '%Y-%m') = ?
                  AND a.empresa_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $rut, $periodo, $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['dias'] ?? 30;
    }

    private function obtenerFechaPago($rut, $periodo) {
        return date('Y-m-05', strtotime($periodo . '-01')); // 5 de cada mes
    }

    private function obtenerUltimaRemuneracion($empleado_id) {
        $query = "SELECT liquido_pagar FROM liquidaciones
                  WHERE empleado_id = ? AND empresa_id = ?
                  ORDER BY periodo DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $empleado_id, $this->empresa_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['liquido_pagar'] ?? 0;
    }

    private function calcularVacacionesPendientes($empleado_id, $fecha_termino) {
        // Calcular días de vacaciones pendientes y convertir a monto
        return 0; // Implementar cálculo completo
    }

    /**
     * Generar reporte completo para fiscalización
     */
    public function generarReporteCompleto($fecha_desde, $fecha_hasta) {
        return [
            'empresa' => $this->obtenerDatosEmpresa(),
            'libro_remuneraciones' => $this->generarLibroRemuneraciones($fecha_desde, $fecha_hasta),
            'asistencia' => $this->generarRegistroAsistencia($fecha_desde, $fecha_hasta),
            'vacaciones' => $this->generarRegistroVacaciones(),
            'horas_extras' => $this->generarRegistroHorasExtras($fecha_desde, $fecha_hasta),
            'contratos_vigentes' => $this->obtenerContratosVigentes(),
            'finiquitos' => $this->obtenerFiniquitos($fecha_desde, $fecha_hasta),
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }

    private function obtenerContratosVigentes() {
        $query = "SELECT COUNT(*) as total FROM empleados
                  WHERE empresa_id = ? AND estado = 'activo'";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    private function obtenerFiniquitos($fecha_desde, $fecha_hasta) {
        $query = "SELECT COUNT(*) as total FROM finiquitos
                  WHERE empresa_id = ?
                  AND fecha_finiquito BETWEEN ? AND ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $this->empresa_id, $fecha_desde, $fecha_hasta);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }
}
?>
