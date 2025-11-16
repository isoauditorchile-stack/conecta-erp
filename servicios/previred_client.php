<?php
/**
 * SERVICIOS EXTERNOS - PREVIRED
 * Integración con Previred Chile para previsión y seguridad social
 * - Generación de archivos previred
 * - Cálculo de cotizaciones AFP, Salud, AFC
 * - Envío de nóminas
 */

class PreviredClient {
    private $rut_empresa;
    private $periodo; // AAAAMM

    const AFP_TASAS = [
        'capital' => 11.44,
        'cuprum' => 11.44,
        'habitat' => 11.27,
        'planvital' => 11.16,
        'provida' => 11.54,
        'modelo' => 10.58,
        'uno' => 10.49
    ];

    const SALUD_TASA = 7.0; // Porcentaje base
    const AFC_TASA = 2.4; // Seguro de cesantía trabajador
    const SIS_TASA = 0.93; // Seguro de invalidez y sobrevivencia

    /**
     * Constructor
     */
    public function __construct($rut_empresa, $periodo) {
        $this->rut_empresa = $rut_empresa;
        $this->periodo = $periodo;
    }

    /**
     * Calcular cotizaciones de un empleado
     */
    public function calcularCotizaciones($sueldo_bruto, $afp, $isapre = null) {
        $tasa_afp = self::AFP_TASAS[$afp] ?? 11.44;

        $cotizaciones = [
            'sueldo_bruto' => $sueldo_bruto,
            'afp' => round($sueldo_bruto * ($tasa_afp / 100), 0),
            'sis' => round($sueldo_bruto * (self::SIS_TASA / 100), 0),
            'salud' => round($sueldo_bruto * (self::SALUD_TASA / 100), 0),
            'afc' => round($sueldo_bruto * (self::AFC_TASA / 100), 0),
        ];

        $cotizaciones['total_descuentos'] = $cotizaciones['afp'] + $cotizaciones['sis'] +
                                            $cotizaciones['salud'] + $cotizaciones['afc'];

        $cotizaciones['liquido'] = $sueldo_bruto - $cotizaciones['total_descuentos'];

        return $cotizaciones;
    }

    /**
     * Generar archivo TXT para Previred
     */
    public function generarArchivoPrevired($empleados) {
        $lineas = [];

        // Línea 1: Encabezado empleador
        $lineas[] = implode(';', [
            '1', // Tipo registro
            $this->rut_empresa,
            $this->periodo,
            count($empleados),
            date('Ymd')
        ]);

        // Línea 2+: Detalle empleados
        foreach ($empleados as $emp) {
            $lineas[] = implode(';', [
                '2', // Tipo registro
                $emp['rut'],
                $emp['apellido_paterno'],
                $emp['apellido_materno'],
                $emp['nombres'],
                $emp['sexo'], // M/F
                $emp['nacionalidad'],
                $emp['tipo_cotizante'], // 1=Dependiente, 2=Independiente
                $emp['codigo_afp'],
                $emp['dias_trabajados'],
                $emp['tipo_remuneracion'], // 1=Mensual, 2=Diaria
                $emp['sueldo_imponible'],
                $emp['cotizacion_afp'],
                $emp['cotizacion_sis'],
                $emp['cotizacion_salud'],
                $emp['cotizacion_afc'],
                $emp['codigo_isapre'],
                $emp['monto_isapre'],
                '' // Campo reservado
            ]);
        }

        return implode("\r\n", $lineas);
    }

    /**
     * Validar archivo Previred
     */
    public function validarArchivo($contenido) {
        $lineas = explode("\n", trim($contenido));
        $errores = [];

        // Validar línea 1
        $encabezado = str_getcsv($lineas[0], ';');
        if ($encabezado[0] != '1') {
            $errores[] = 'Línea 1 debe ser tipo 1 (encabezado)';
        }

        if (count($lineas) - 1 != $encabezado[3]) {
            $errores[] = 'Cantidad de empleados no coincide';
        }

        // Validar líneas de detalle
        for ($i = 1; $i < count($lineas); $i++) {
            $detalle = str_getcsv($lineas[$i], ';');

            if ($detalle[0] != '2') {
                $errores[] = "Línea " . ($i + 1) . " debe ser tipo 2 (detalle)";
            }

            if (empty($detalle[1])) {
                $errores[] = "Línea " . ($i + 1) . " falta RUT empleado";
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}
