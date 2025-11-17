<?php
/**
 * ============================================
 * PreviredManager - Gestión completa de Previred
 * ============================================
 * Generación de archivo .rem para Previred
 * Cálculo de cotizaciones AFP, Salud, AFC, SIS
 * Configuración por zona geográfica
 *
 * @author CONECTA ERP
 * @version 2.0
 */

require_once __DIR__ . '/../config/config.php';

class PreviredManager {

    private $conn;
    private $empresa_id;

    // Tasas AFP actualizadas 2025
    const AFP_TASAS = [
        'capital' => 11.44,
        'cuprum' => 11.44,
        'habitat' => 11.27,
        'planvital' => 11.16,
        'provida' => 11.54,
        'modelo' => 10.58,
        'uno' => 10.49
    ];

    // Tasas previsionales
    const SALUD_TASA = 7.0;  // Legal mínimo
    const AFC_TASA = 2.4;    // Seguro cesantía trabajador
    const SIS_TASA = 0.93;   // Seguro de invalidez y sobrevivencia
    const AFC_EMPLEADOR_TASA = 3.0;  // Seguro cesantía empleador (contrato indefinido)
    const AFC_EMPLEADOR_PLAZO_FIJO = 3.0; // Contrato plazo fijo

    // Zonas geográficas (asignación familiar)
    const ZONA_ASIGNACION = [
        'zona_1' => ['monto' => 13_390, 'regiones' => ['I', 'II', 'XI', 'XII']],  // Extremas
        'zona_2' => ['monto' => 8_242, 'regiones' => ['III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XIV', 'XV', 'XVI', 'RM']]  // Resto del país
    ];

    // UF y UTM (se actualizan desde indicadores_economicos)
    private $uf_actual;
    private $utm_actual;

    /**
     * Constructor
     */
    public function __construct($conn, $empresa_id) {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;

        // Cargar indicadores económicos actuales
        $this->cargarIndicadores();
    }

    /**
     * Cargar UF y UTM desde BD
     */
    private function cargarIndicadores() {
        $query = "SELECT uf, utm FROM indicadores_economicos
                  WHERE fecha = (SELECT MAX(fecha) FROM indicadores_economicos)
                  LIMIT 1";

        $result = $this->conn->query($query);
        $indicadores = $result->fetch_assoc();

        $this->uf_actual = $indicadores['uf'] ?? 37000;
        $this->utm_actual = $indicadores['utm'] ?? 66000;
    }

    /**
     * Calcular cotizaciones para un empleado
     *
     * @param array $empleado Datos del empleado
     * @return array Cotizaciones calculadas
     */
    public function calcularCotizaciones($empleado) {
        $sueldo_base = $empleado['sueldo_base'];
        $horas_extras = $empleado['horas_extras'] ?? 0;
        $bonos = $empleado['bonos'] ?? 0;
        $gratificacion = $empleado['gratificacion'] ?? 0;

        // Sueldo imponible
        $sueldo_imponible = $sueldo_base + $horas_extras + $bonos + $gratificacion;

        // Tope imponible (80.2 UF)
        $tope_imponible = round($this->uf_actual * 80.2);
        $sueldo_imponible = min($sueldo_imponible, $tope_imponible);

        // AFP
        $afp = $empleado['afp'] ?? 'capital';
        $tasa_afp = self::AFP_TASAS[$afp] ?? 11.44;
        $cotizacion_afp = round($sueldo_imponible * ($tasa_afp / 100));

        // SIS (Seguro Invalidez y Sobrevivencia)
        $cotizacion_sis = round($sueldo_imponible * (self::SIS_TASA / 100));

        // Salud
        $isapre = $empleado['isapre'] ?? null;
        $plan_isapre_uf = $empleado['plan_isapre_uf'] ?? 0;

        if ($isapre) {
            // Isapre: 7% legal + diferencia del plan
            $cotizacion_salud_legal = round($sueldo_imponible * (self::SALUD_TASA / 100));
            $cotizacion_salud_plan = round($plan_isapre_uf * $this->uf_actual);
            $cotizacion_salud = max($cotizacion_salud_legal, $cotizacion_salud_plan);
        } else {
            // FONASA: 7% legal
            $cotizacion_salud = round($sueldo_imponible * (self::SALUD_TASA / 100));
        }

        // AFC (Seguro cesantía)
        $tipo_contrato = $empleado['tipo_contrato'] ?? 'indefinido';
        $cotizacion_afc_trabajador = round($sueldo_imponible * (self::AFC_TASA / 100));

        $tasa_afc_empleador = $tipo_contrato == 'plazo_fijo'
            ? self::AFC_EMPLEADOR_PLAZO_FIJO
            : self::AFC_EMPLEADOR_TASA;

        $cotizacion_afc_empleador = round($sueldo_imponible * ($tasa_afc_empleador / 100));

        // Asignación familiar (si aplica)
        $cargas_familiares = $empleado['cargas_familiares'] ?? 0;
        $region = $empleado['region'] ?? 'RM';
        $asignacion_familiar = $this->calcularAsignacionFamiliar($sueldo_imponible, $cargas_familiares, $region);

        // Descuentos totales trabajador
        $total_descuentos = $cotizacion_afp + $cotizacion_sis + $cotizacion_salud + $cotizacion_afc_trabajador;

        // Líquido a pagar
        $liquido = $sueldo_base + $horas_extras + $bonos + $gratificacion + $asignacion_familiar - $total_descuentos;

        return [
            'sueldo_base' => $sueldo_base,
            'horas_extras' => $horas_extras,
            'bonos' => $bonos,
            'gratificacion' => $gratificacion,
            'sueldo_imponible' => $sueldo_imponible,
            'cotizacion_afp' => $cotizacion_afp,
            'cotizacion_sis' => $cotizacion_sis,
            'cotizacion_salud' => $cotizacion_salud,
            'cotizacion_afc_trabajador' => $cotizacion_afc_trabajador,
            'cotizacion_afc_empleador' => $cotizacion_afc_empleador,
            'asignacion_familiar' => $asignacion_familiar,
            'total_descuentos' => $total_descuentos,
            'liquido' => $liquido
        ];
    }

    /**
     * Calcular asignación familiar según tramo de ingresos
     */
    private function calcularAsignacionFamiliar($sueldo_imponible, $cargas, $region) {
        if ($cargas == 0) return 0;

        // Determinar zona geográfica
        $zona = in_array($region, self::ZONA_ASIGNACION['zona_1']['regiones']) ? 'zona_1' : 'zona_2';
        $monto_por_carga = self::ZONA_ASIGNACION[$zona]['monto'];

        // Tramos de ingreso
        $subsidio_maximo = $this->utm_actual * 13.47;
        $subsidio_medio = $this->utm_actual * 13.47 * 2;

        if ($sueldo_imponible <= $subsidio_maximo) {
            // 100% del monto
            return $monto_por_carga * $cargas;
        } elseif ($sueldo_imponible <= $subsidio_medio) {
            // 50% del monto
            return round(($monto_por_carga * $cargas) / 2);
        } else {
            // Sin beneficio
            return 0;
        }
    }

    /**
     * Generar archivo .rem para Previred
     *
     * @param string $periodo YYYY-MM
     * @param array $opciones Opciones adicionales
     * @return string Ruta del archivo generado
     */
    public function generarArchivoPrevired($periodo, $opciones = []) {
        // Obtener datos de la empresa
        $empresa = $this->obtenerDatosEmpresa();

        // Obtener nómina del período
        $nominas = $this->obtenerNominaPeriodo($periodo);

        if (empty($nominas)) {
            throw new Exception("No hay nóminas para el período $periodo");
        }

        // Generar líneas del archivo
        $lineas = [];

        // Línea 1: Encabezado empresa
        $lineas[] = $this->generarLineaEmpresa($empresa, $periodo);

        // Líneas de trabajadores
        foreach ($nominas as $nomina) {
            $lineas[] = $this->generarLineaTrabajador($nomina, $periodo);
        }

        // Línea final: Totalizador
        $lineas[] = $this->generarLineaTotalizador($nominas);

        // Crear archivo
        $contenido = implode("\r\n", $lineas);

        // Guardar archivo
        $nombre_archivo = 'previred_' . str_replace('-', '', $periodo) . '_' . $empresa['rut'] . '.rem';
        $ruta_archivo = __DIR__ . '/../storage/previred/' . $nombre_archivo;

        // Crear directorio si no existe
        if (!is_dir(dirname($ruta_archivo))) {
            mkdir(dirname($ruta_archivo), 0755, true);
        }

        file_put_contents($ruta_archivo, $contenido);

        // Registrar en BD
        $this->registrarArchivoGenerado($periodo, $ruta_archivo);

        return $ruta_archivo;
    }

    /**
     * Generar línea de empresa (Tipo 1)
     */
    private function generarLineaEmpresa($empresa, $periodo) {
        $rut = str_replace(['.', '-'], '', $empresa['rut']);
        $ano_mes = str_replace('-', '', $periodo);

        $linea = sprintf(
            "1%011s%06s%-40s%-50s%-30s",
            $rut,
            $ano_mes,
            $this->limpiarTexto($empresa['nombre_empresa'], 40),
            $this->limpiarTexto($empresa['direccion'], 50),
            ''  // Campo reservado
        );

        return $linea;
    }

    /**
     * Generar línea de trabajador (Tipo 2)
     */
    private function generarLineaTrabajador($nomina, $periodo) {
        // Obtener datos completos del empleado
        $query = "SELECT e.*, u.nombre, u.apellido
                  FROM empleados e
                  LEFT JOIN usuarios u ON e.usuario_id = u.id
                  WHERE e.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $nomina['empleado_id']);
        $stmt->execute();
        $empleado = $stmt->get_result()->fetch_assoc();

        $rut_empleado = str_replace(['.', '-'], '', $empleado['rut']);

        // Códigos AFP/Isapre
        $codigo_afp = $this->obtenerCodigoAFP($empleado['afp']);
        $codigo_isapre = $empleado['isapre'] ? $this->obtenerCodigoIsapre($empleado['isapre']) : '00';

        $linea = sprintf(
            "2%011s%02s%-30s%-50s%010d%010d%010d%010d%010d%010d%02s%010d",
            $rut_empleado,
            $codigo_afp,
            $this->limpiarTexto($empleado['nombre'] . ' ' . $empleado['apellido'], 30),
            '',  // Dirección trabajador
            $nomina['salario_base'] * 100,  // Montos en centavos
            $nomina['afp'] * 100,
            $nomina['salud'] * 100,
            0,  // AFC
            0,  // APV (opcional)
            $nomina['liquido'] * 100,
            $codigo_isapre,
            0   // Campo reservado
        );

        return $linea;
    }

    /**
     * Generar línea totalizador (Tipo 3)
     */
    private function generarLineaTotalizador($nominas) {
        $total_trabajadores = count($nominas);
        $total_remuneraciones = array_sum(array_column($nominas, 'salario_base'));
        $total_cotizaciones = array_sum(array_column($nominas, 'afp')) +
                             array_sum(array_column($nominas, 'salud'));

        $linea = sprintf(
            "3%010d%015d%015d",
            $total_trabajadores,
            $total_remuneraciones * 100,
            $total_cotizaciones * 100
        );

        return $linea;
    }

    /**
     * Obtener código AFP
     */
    private function obtenerCodigoAFP($nombre_afp) {
        $codigos = [
            'capital' => '03',
            'cuprum' => '05',
            'habitat' => '08',
            'planvital' => '29',
            'provida' => '33',
            'modelo' => '34',
            'uno' => '35'
        ];

        return $codigos[strtolower($nombre_afp)] ?? '03';
    }

    /**
     * Obtener código Isapre
     */
    private function obtenerCodigoIsapre($nombre_isapre) {
        $codigos = [
            'colmena' => '01',
            'consalud' => '02',
            'cruz_blanca' => '03',
            'banmedica' => '04',
            'vida_tres' => '05',
            'nueva_masvida' => '06'
        ];

        return $codigos[strtolower($nombre_isapre)] ?? '01';
    }

    /**
     * Limpiar texto para archivo
     */
    private function limpiarTexto($texto, $longitud) {
        // Remover tildes y caracteres especiales
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        $texto = strtoupper($texto);
        $texto = preg_replace('/[^A-Z0-9 ]/', '', $texto);

        return str_pad(substr($texto, 0, $longitud), $longitud);
    }

    /**
     * Obtener datos de la empresa
     */
    private function obtenerDatosEmpresa() {
        $query = "SELECT * FROM empresas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->empresa_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Obtener nóminas del período
     */
    private function obtenerNominaPeriodo($periodo) {
        $query = "SELECT n.*, e.rut, e.afp, e.isapre
                  FROM nomina n
                  INNER JOIN empleados e ON n.empleado_id = e.id
                  WHERE n.empresa_id = ?
                  AND DATE_FORMAT(n.periodo, '%Y-%m') = ?
                  AND n.estado IN ('calculada', 'pagada')";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $this->empresa_id, $periodo);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Registrar archivo generado
     */
    private function registrarArchivoGenerado($periodo, $ruta) {
        $query = "INSERT INTO archivos_previred (empresa_id, periodo, ruta_archivo, fecha_generacion)
                  VALUES (?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE
                  ruta_archivo = VALUES(ruta_archivo),
                  fecha_generacion = NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $this->empresa_id, $periodo, $ruta);
        $stmt->execute();
    }

    /**
     * Validar archivo Previred
     */
    public function validarArchivo($ruta_archivo) {
        $contenido = file_get_contents($ruta_archivo);
        $lineas = explode("\r\n", $contenido);

        $errores = [];

        // Validar primera línea (empresa)
        if (!preg_match('/^1/', $lineas[0])) {
            $errores[] = "Primera línea debe ser tipo 1 (empresa)";
        }

        // Validar última línea (totalizador)
        $ultima = end($lineas);
        if (!preg_match('/^3/', $ultima)) {
            $errores[] = "Última línea debe ser tipo 3 (totalizador)";
        }

        // Validar largo de líneas
        foreach ($lineas as $i => $linea) {
            if (strlen($linea) < 150) {
                $errores[] = "Línea " . ($i + 1) . " tiene largo inválido";
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}
