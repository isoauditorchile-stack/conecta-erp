# 📘 MANUAL TÉCNICO - INTEGRACIONES Y RELOJ CONTROL

## PARTE 3: INTEGRACIONES EXTERNAS

<a name="integracion-sii"></a>
## 23. INTEGRACIÓN SII (SERVICIO DE IMPUESTOS INTERNOS CHILE)

### 🔒 CONFIGURACIÓN CERTIFICADO DIGITAL

El SII requiere un certificado digital (.pfx) emitido por alguna entidad certificadora autorizada.

#### Paso 1: Obtener Certificado Digital

1. **Comprar certificado** en:
   - E-Cert Chile (www.e-certchile.cl)
   - Acepta (www.acepta.com)
   - Firmapro (www.firmapro.cl)

2. **Tipos de certificado aceptados**:
   - Certificado de Firma Electrónica Avanzada
   - Certificado de Representante Legal
   - Certificado de Persona Natural (solo para contribuyentes individuales)

#### Paso 2: Convertir Certificado a Formato PEM

```bash
# Si tienes certificado .pfx
openssl pkcs12 -in certificado.pfx -out certificado.pem -nodes

# Separar clave privada
openssl pkcs12 -in certificado.pfx -nocerts -out clave_privada.pem -nodes

# Separar certificado público
openssl pkcs12 -in certificado.pfx -clcerts -nokeys -out certificado_publico.pem
```

#### Paso 3: Configurar en el Sistema

Ubicar archivos en `/var/www/conecta-erp/certificados/`:

```bash
sudo mkdir -p /var/www/conecta-erp/certificados
sudo cp certificado_publico.pem /var/www/conecta-erp/certificados/
sudo cp clave_privada.pem /var/www/conecta-erp/certificados/
sudo chmod 600 /var/www/conecta-erp/certificados/*.pem
sudo chown www-data:www-data /var/www/conecta-erp/certificados/*.pem
```

### 📡 CONFIGURACIÓN DE CONEXIÓN AL SII

Editar `/servicios/sii_client.php`:

```php
<?php
/**
 * CLIENTE SII - FACTURACIÓN ELECTRÓNICA
 * CONFIGURACIÓN PARA PRODUCCIÓN
 */

class SIIClient {

    // =====================================================
    // CONFIGURACIÓN
    // =====================================================
    private $ambiente = 'produccion'; // 'certificacion' o 'produccion'

    // URLs SII
    private $urls = [
        'certificacion' => [
            'wsdl' => 'https://maullin.sii.cl/DTEWS/CrSeed.jws?WSDL',
            'token' => 'https://maullin.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL',
            'envio' => 'https://maullin.sii.cl/DTEWS/services/EnvioDTE'
        ],
        'produccion' => [
            'wsdl' => 'https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL',
            'token' => 'https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL',
            'envio' => 'https://palena.sii.cl/DTEWS/services/EnvioDTE'
        ]
    ];

    // Rutas de certificados
    private $cert_path = '/var/www/conecta-erp/certificados/certificado_publico.pem';
    private $key_path = '/var/www/conecta-erp/certificados/clave_privada.pem';

    // RUT Emisor (tu empresa)
    private $rut_emisor = '76123456-7';  // ⚠️ CAMBIAR POR RUT REAL

    // =====================================================
    // OBTENER SEMILLA (SEED)
    // =====================================================
    public function obtenerSemilla() {
        try {
            $client = new SoapClient($this->urls[$this->ambiente]['wsdl'], [
                'trace' => 1,
                'exceptions' => true,
                'connection_timeout' => 30
            ]);

            $response = $client->getSeed();

            if ($response) {
                $xml = simplexml_load_string($response);
                if ($xml->xpath('//SII:SEMILLA')) {
                    $semilla = (string)$xml->xpath('//SII:SEMILLA')[0];
                    return $semilla;
                }
            }

            throw new Exception('No se pudo obtener semilla del SII');

        } catch (Exception $e) {
            error_log("Error obteniendo semilla SII: " . $e->getMessage());
            throw $e;
        }
    }

    // =====================================================
    // FIRMAR SEMILLA CON CERTIFICADO
    // =====================================================
    private function firmarSemilla($semilla) {
        $xml_seed = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<getToken>
<item>
<Semilla>$semilla</Semilla>
</item>
</getToken>
XML;

        // Crear archivo temporal para firma
        $temp_unsigned = tempnam(sys_get_temp_dir(), 'seed_');
        file_put_contents($temp_unsigned, $xml_seed);

        $temp_signed = tempnam(sys_get_temp_dir(), 'seed_signed_');

        // Firmar con OpenSSL
        $cert = file_get_contents($this->cert_path);
        $key = file_get_contents($this->key_path);

        $pkcs12 = false;
        openssl_pkcs12_read(file_get_contents($this->cert_path), $pkcs12, '');

        if (!$pkcs12) {
            throw new Exception('Error leyendo certificado');
        }

        // Firmar XML
        $signature = '';
        openssl_pkcs7_sign(
            $temp_unsigned,
            $temp_signed,
            $cert,
            $key,
            [],
            PKCS7_DETACHED | PKCS7_NOATTR
        );

        $signed_data = file_get_contents($temp_signed);

        // Limpiar archivos temporales
        unlink($temp_unsigned);
        unlink($temp_signed);

        return $signed_data;
    }

    // =====================================================
    // OBTENER TOKEN DE AUTENTICACIÓN
    // =====================================================
    public function obtenerToken() {
        try {
            // 1. Obtener semilla
            $semilla = $this->obtenerSemilla();

            // 2. Firmar semilla
            $semilla_firmada = $this->firmarSemilla($semilla);

            // 3. Obtener token
            $client = new SoapClient($this->urls[$this->ambiente]['token'], [
                'trace' => 1,
                'exceptions' => true
            ]);

            $response = $client->getToken($semilla_firmada);

            if ($response) {
                $xml = simplexml_load_string($response);
                if ($xml->xpath('//TOKEN')) {
                    $token = (string)$xml->xpath('//TOKEN')[0];
                    return $token;
                }
            }

            throw new Exception('No se pudo obtener token del SII');

        } catch (Exception $e) {
            error_log("Error obteniendo token SII: " . $e->getMessage());
            throw $e;
        }
    }

    // =====================================================
    // GENERAR DTE (Documento Tributario Electrónico)
    // =====================================================
    public function generarDTE($tipo_dte, $datos_factura) {
        /**
         * TIPOS DE DTE:
         * 33 = Factura Electrónica
         * 34 = Factura Exenta Electrónica
         * 39 = Boleta Electrónica
         * 41 = Boleta Exenta Electrónica
         * 52 = Guía de Despacho Electrónica
         * 56 = Nota de Débito Electrónica
         * 61 = Nota de Crédito Electrónica
         */

        $folio = $datos_factura['numero_factura']; // Debe venir de CAF (Código de Autorización de Folios)
        $fecha_emision = date('Y-m-d');

        $xml_dte = <<<XML
<?xml version="1.0" encoding="ISO-8859-1"?>
<DTE version="1.0">
<Documento ID="DTE-{$tipo_dte}-{$folio}">
    <Encabezado>
        <IdDoc>
            <TipoDTE>{$tipo_dte}</TipoDTE>
            <Folio>{$folio}</Folio>
            <FchEmis>{$fecha_emision}</FchEmis>
        </IdDoc>
        <Emisor>
            <RUTEmisor>{$this->rut_emisor}</RUTEmisor>
            <RznSoc>{$datos_factura['razon_social_emisor']}</RznSoc>
            <GiroEmis>{$datos_factura['giro_emisor']}</GiroEmis>
            <Acteco>{$datos_factura['codigo_actividad']}</Acteco>
            <DirOrigen>{$datos_factura['direccion_emisor']}</DirOrigen>
            <CmnaOrigen>{$datos_factura['comuna_emisor']}</CmnaOrigen>
        </Emisor>
        <Receptor>
            <RUTRecep>{$datos_factura['rut_cliente']}</RUTRecep>
            <RznSocRecep>{$datos_factura['razon_social_cliente']}</RznSocRecep>
            <GiroRecep>{$datos_factura['giro_cliente']}</GiroRecep>
            <DirRecep>{$datos_factura['direccion_cliente']}</DirRecep>
            <CmnaRecep>{$datos_factura['comuna_cliente']}</CmnaRecep>
        </Receptor>
        <Totales>
            <MntNeto>{$datos_factura['monto_neto']}</MntNeto>
            <MntExe>0</MntExe>
            <TasaIVA>19</TasaIVA>
            <IVA>{$datos_factura['monto_iva']}</IVA>
            <MntTotal>{$datos_factura['monto_total']}</MntTotal>
        </Totales>
    </Encabezado>
    <Detalle>
XML;

        // Agregar items
        foreach ($datos_factura['items'] as $nro_linea => $item) {
            $xml_dte .= <<<ITEM
        <DetalleDTE>
            <NroLinDet>{$nro_linea}</NroLinDet>
            <NmbItem>{$item['nombre']}</NmbItem>
            <QtyItem>{$item['cantidad']}</QtyItem>
            <PrcItem>{$item['precio_unitario']}</PrcItem>
            <MontoItem>{$item['monto_total']}</MontoItem>
        </DetalleDTE>
ITEM;
        }

        $xml_dte .= <<<XML
    </Detalle>
</Documento>
</DTE>
XML;

        // Firmar DTE
        $dte_firmado = $this->firmarDTE($xml_dte);

        return $dte_firmado;
    }

    // =====================================================
    // ENVIAR DTE AL SII
    // =====================================================
    public function enviarDTE($dte_firmado) {
        try {
            $token = $this->obtenerToken();

            $client = new SoapClient($this->urls[$this->ambiente]['envio'], [
                'trace' => 1,
                'exceptions' => true
            ]);

            $response = $client->enviarDTE([
                'token' => $token,
                'archivo' => base64_encode($dte_firmado)
            ]);

            // Procesar respuesta
            if ($response) {
                $xml = simplexml_load_string($response);
                $estado = (string)$xml->xpath('//ESTADO')[0];
                $track_id = (string)$xml->xpath('//TRACKID')[0];

                return [
                    'success' => ($estado == '0'),
                    'track_id' => $track_id,
                    'mensaje' => (string)$xml->xpath('//GLOSA')[0]
                ];
            }

            throw new Exception('Respuesta inválida del SII');

        } catch (Exception $e) {
            error_log("Error enviando DTE al SII: " . $e->getMessage());
            throw $e;
        }
    }
}
```

### 🔧 USO DEL CLIENTE SII

Ejemplo de uso en `modulos/finanzas/comprobantes_facturas.php`:

```php
<?php
require_once '../../servicios/sii_client.php';

// Crear cliente SII
$sii = new SIIClient();

// Preparar datos de factura
$datos_factura = [
    'numero_factura' => '123',
    'razon_social_emisor' => 'MI EMPRESA SPA',
    'giro_emisor' => 'Comercio',
    'codigo_actividad' => '479210',
    'direccion_emisor' => 'Av. Principal 123, Santiago',
    'comuna_emisor' => 'Santiago',
    'rut_cliente' => '76987654-3',
    'razon_social_cliente' => 'CLIENTE EJEMPLO LTDA',
    'giro_cliente' => 'Servicios',
    'direccion_cliente' => 'Calle Ejemplo 456',
    'comuna_cliente' => 'Providencia',
    'monto_neto' => 100000,
    'monto_iva' => 19000,
    'monto_total' => 119000,
    'items' => [
        1 => [
            'nombre' => 'Producto 1',
            'cantidad' => 2,
            'precio_unitario' => 50000,
            'monto_total' => 100000
        ]
    ]
];

try {
    // Generar DTE (tipo 33 = Factura Electrónica)
    $dte = $sii->generarDTE(33, $datos_factura);

    // Enviar al SII
    $resultado = $sii->enviarDTE($dte);

    if ($resultado['success']) {
        echo "Factura enviada exitosamente. Track ID: " . $resultado['track_id'];

        // Guardar track_id en base de datos
        $stmt = $conn->prepare("UPDATE facturas SET sii_track_id = ?, sii_estado = 'enviado' WHERE id = ?");
        $stmt->bind_param("si", $resultado['track_id'], $factura_id);
        $stmt->execute();
    } else {
        echo "Error: " . $resultado['mensaje'];
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### ⚠️ ERRORES COMUNES SII

❌ **Error**: "SEMILLA NO VÁLIDA"
✅ **Solución**:
- Verificar fecha/hora del servidor está sincronizada:
```bash
sudo ntpdate -s time.nist.gov
```

❌ **Error**: "CERTIFICADO NO VÁLIDO"
✅ **Solución**:
- Verificar certificado no está vencido
- Verificar certificado corresponde al RUT emisor
- Verificar permisos de archivos `.pem`

❌ **Error**: "CAF NO AUTORIZADO"
✅ **Solución**:
- Solicitar CAF (Código de Autorización de Folios) en sitio SII
- Descargar archivo XML del CAF
- Importar en el sistema

---

<a name="integracion-previred"></a>
## 24. INTEGRACIÓN PREVIRED (PREVISIÓN SOCIAL CHILE)

### 📋 DESCRIPCIÓN

Previred es el sistema centralizado de pago de cotizaciones previsionales en Chile.

### 🔧 CONFIGURACIÓN

Editar `/servicios/previred_client.php`:

```php
<?php
/**
 * CLIENTE PREVIRED
 * Generación de archivo de remuneraciones formato Previred
 */

class PreviredClient {

    // =====================================================
    // TASAS VIGENTES 2025
    // =====================================================
    private $tasas = [
        // AFP
        'afp_capital' => 11.44,
        'afp_cuprum' => 11.44,
        'afp_habitat' => 11.27,
        'afp_planvital' => 11.16,
        'afp_provida' => 11.45,
        'afp_modelo' => 10.77,
        'afp_uno' => 10.49,

        // Salud
        'fonasa' => 7.00,
        'isapre_variable' => 0, // Se define por trabajador

        // Seguros
        'sis' => 1.26,  // Seguro de Invalidez y Sobrevivencia
        'afc' => 0.60,  // Seguro de Cesantía (trabajador)
        'afc_empleador' => 2.40  // Seguro de Cesantía (empleador)
    ];

    // =====================================================
    // CALCULAR COTIZACIONES DE UN TRABAJADOR
    // =====================================================
    public function calcularCotizaciones($rut, $nombre, $salario_bruto, $afp, $isapre = null) {
        $cotizaciones = [];

        // Tope imponible 2025: UF 81.6 (aprox. $2.800.000)
        $tope_imponible = 2800000;
        $salario_imponible = min($salario_bruto, $tope_imponible);

        // AFP
        $tasa_afp = $this->tasas['afp_' . strtolower($afp)] ?? 11.44;
        $cotizaciones['afp'] = round($salario_imponible * ($tasa_afp / 100));

        // Salud
        if ($isapre) {
            $cotizaciones['salud'] = round($salario_bruto * ($this->tasas['isapre_variable'] / 100));
        } else {
            $cotizaciones['salud'] = round($salario_imponible * ($this->tasas['fonasa'] / 100));
        }

        // SIS (incluido en AFP)
        $cotizaciones['sis'] = round($salario_imponible * ($this->tasas['sis'] / 100));

        // AFC (Seguro de Cesantía)
        $cotizaciones['afc_trabajador'] = round($salario_imponible * ($this->tasas['afc'] / 100));
        $cotizaciones['afc_empleador'] = round($salario_imponible * ($this->tasas['afc_empleador'] / 100));

        // Total descuentos trabajador
        $cotizaciones['total_descuentos'] = $cotizaciones['afp'] +
                                             $cotizaciones['salud'] +
                                             $cotizaciones['afc_trabajador'];

        // Salario líquido
        $cotizaciones['salario_liquido'] = $salario_bruto - $cotizaciones['total_descuentos'];

        return $cotizaciones;
    }

    // =====================================================
    // GENERAR ARCHIVO PREVIRED (FORMATO TXT)
    // =====================================================
    public function generarArchivoPrevired($empresa_id, $mes, $anio) {
        global $conn;

        // Obtener datos de la empresa
        $stmt = $conn->prepare("SELECT rut, nombre FROM empresas WHERE id = ?");
        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();
        $empresa = $stmt->get_result()->fetch_assoc();

        // Obtener empleados y sus liquidaciones del mes
        $stmt = $conn->prepare("
            SELECT
                e.rut,
                CONCAT(e.nombres, ' ', e.apellidos) as nombre_completo,
                l.sueldo_base,
                l.total_haberes,
                l.total_descuentos,
                l.liquido_pagar,
                e.afp,
                e.salud_prevision,
                l.cotizacion_afp,
                l.cotizacion_salud,
                l.cotizacion_afc
            FROM liquidaciones l
            INNER JOIN empleados e ON l.empleado_id = e.id
            WHERE e.empresa_id = ?
              AND l.mes = ?
              AND l.anio = ?
              AND e.activo = 1
        ");
        $stmt->bind_param("iii", $empresa_id, $mes, $anio);
        $stmt->execute();
        $empleados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Generar archivo TXT formato Previred
        $contenido = "";

        // Encabezado (línea 1)
        $contenido .= "01";  // Tipo de registro
        $contenido .= str_pad($empresa['rut'], 10, "0", STR_PAD_LEFT);
        $contenido .= str_pad($empresa['nombre'], 40, " ");
        $contenido .= str_pad($mes, 2, "0", STR_PAD_LEFT);
        $contenido .= $anio;
        $contenido .= "\r\n";

        // Detalle de trabajadores
        foreach ($empleados as $empleado) {
            $contenido .= "02";  // Tipo de registro (detalle)
            $contenido .= str_pad($empleado['rut'], 10, "0", STR_PAD_LEFT);
            $contenido .= str_pad($empleado['nombre_completo'], 40, " ");
            $contenido .= str_pad(number_format($empleado['sueldo_base'], 0, '', ''), 10, "0", STR_PAD_LEFT);
            $contenido .= str_pad(number_format($empleado['cotizacion_afp'], 0, '', ''), 10, "0", STR_PAD_LEFT);
            $contenido .= str_pad(number_format($empleado['cotizacion_salud'], 0, '', ''), 10, "0", STR_PAD_LEFT);
            $contenido .= str_pad(number_format($empleado['cotizacion_afc'], 0, '', ''), 10, "0", STR_PAD_LEFT);
            $contenido .= "\r\n";
        }

        // Totalización (última línea)
        $total_trabajadores = count($empleados);
        $total_remuneraciones = array_sum(array_column($empleados, 'sueldo_base'));
        $total_cotizaciones = array_sum(array_column($empleados, 'cotizacion_afp')) +
                               array_sum(array_column($empleados, 'cotizacion_salud')) +
                               array_sum(array_column($empleados, 'cotizacion_afc'));

        $contenido .= "03";  // Tipo de registro (totalización)
        $contenido .= str_pad($total_trabajadores, 10, "0", STR_PAD_LEFT);
        $contenido .= str_pad(number_format($total_remuneraciones, 0, '', ''), 15, "0", STR_PAD_LEFT);
        $contenido .= str_pad(number_format($total_cotizaciones, 0, '', ''), 15, "0", STR_PAD_LEFT);
        $contenido .= "\r\n";

        return $contenido;
    }

    // =====================================================
    // DESCARGAR ARCHIVO PREVIRED
    // =====================================================
    public function descargarArchivo($empresa_id, $mes, $anio) {
        $contenido = $this->generarArchivoPrevired($empresa_id, $mes, $anio);

        $nombre_archivo = "Previred_{$mes}_{$anio}.txt";

        header('Content-Type: text/plain; charset=ISO-8859-1');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $contenido;
        exit;
    }
}
```

### 📤 USO DEL CLIENTE PREVIRED

Ejemplo en módulo de RRHH:

```php
<?php
require_once '../../servicios/previred_client.php';

$previred = new PreviredClient();

// Generar archivo para el mes actual
$mes = date('n');
$anio = date('Y');

$previred->descargarArchivo($empresa_id, $mes, $anio);
```

---

<a name="reloj-control"></a>
## 26. SISTEMA DE ASISTENCIA Y RELOJ CONTROL

### 📋 DESCRIPCIÓN COMPLETA

Sistema completo de control de asistencia con precisión de segundos, integrado con dispositivos biométricos.

### 🔧 TABLAS SQL PRINCIPALES

Ya fueron creadas en `sql/modulos/18_reloj_control.sql`:

1. **reloj_dispositivos**: Dispositivos de marcaje
2. **horarios_trabajo**: Configuración de horarios
3. **empleado_horarios**: Asignación de horarios a empleados
4. **asistencia_marcajes**: Registro de marcajes (entrada/salida)
5. **asistencia_resumen_diario**: Resumen consolidado
6. **asistencia_justificaciones**: Permisos y licencias
7. **asistencia_alertas**: Alertas automáticas

### 📱 CONFIGURACIÓN DE DISPOSITIVOS BIOMÉTRICOS

#### Dispositivos Compatibles

- **ZKTeco** (ZK4500, F18, etc.)
- **HID DigitalPersona**
- **Suprema BioMini**
- **Cualquier dispositivo con SDK SOAP/REST**

#### Configuración ZKTeco

1. **Conectar dispositivo a la red**
2. **Obtener IP del dispositivo** (ej: 192.168.1.100)
3. **Configurar en base de datos**:

```sql
INSERT INTO reloj_dispositivos (
  empresa_id, codigo, nombre, ubicacion, tipo, ip_address, puerto, marca, modelo, activo
) VALUES (
  1,
  'RC-001',
  'Reloj Entrada Principal',
  'Recepción - Piso 1',
  'biometrico',
  '192.168.1.100',
  4370,
  'ZKTeco',
  'ZK4500',
  1
);
```

#### Script de Sincronización con Dispositivo

Crear `cron/sincronizar_reloj.php`:

```php
<?php
/**
 * SINCRONIZACIÓN CON DISPOSITIVOS DE RELOJ CONTROL
 * Ejecutar cada 5 minutos vía CRON
 */

require_once '../includes/config.php';

// Obtener dispositivos activos
$stmt = $conn->prepare("SELECT * FROM reloj_dispositivos WHERE activo = 1");
$stmt->execute();
$dispositivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($dispositivos as $dispositivo) {
    // Conectar al dispositivo (ejemplo ZKTeco con SDK)
    try {
        $zk = new ZKLib($dispositivo['ip_address'], $dispositivo['puerto']);

        if ($zk->connect()) {
            // Obtener registros de asistencia del dispositivo
            $registros = $zk->getAttendance();

            foreach ($registros as $registro) {
                // Buscar empleado por ID biométrico o RUT
                $stmt = $conn->prepare("SELECT id, empresa_id FROM empleados WHERE codigo_biometrico = ? OR rut = ?");
                $stmt->bind_param("ss", $registro['uid'], $registro['uid']);
                $stmt->execute();
                $empleado = $stmt->get_result()->fetch_assoc();

                if ($empleado) {
                    // Insertar marcaje
                    $fecha = date('Y-m-d', strtotime($registro['timestamp']));
                    $hora = date('H:i:s', strtotime($registro['timestamp']));
                    $tipo = ($registro['state'] == 0) ? 'entrada' : 'salida';

                    $stmt = $conn->prepare("
                        INSERT INTO asistencia_marcajes (
                          empresa_id, empleado_id, dispositivo_id, fecha, hora, timestamp_marca, tipo, metodo, verificado
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'biometrico', 1)
                    ");
                    $stmt->bind_param("iiisss",
                        $empleado['empresa_id'],
                        $empleado['id'],
                        $dispositivo['id'],
                        $fecha,
                        $hora,
                        $registro['timestamp'],
                        $tipo
                    );
                    $stmt->execute();

                    echo "Marcaje registrado: Empleado {$empleado['id']} - {$tipo} - {$registro['timestamp']}\n";
                }
            }

            // Actualizar última sincronización
            $stmt = $conn->prepare("UPDATE reloj_dispositivos SET ultima_sincronizacion = NOW() WHERE id = ?");
            $stmt->bind_param("i", $dispositivo['id']);
            $stmt->execute();

            $zk->disconnect();
        }

    } catch (Exception $e) {
        error_log("Error sincronizando dispositivo {$dispositivo['codigo']}: " . $e->getMessage());
    }
}
```

### 📊 REPORTES EXCEL DE ASISTENCIA

El módulo `/modulos/rrhh/asistencia/reporte_excel.php` ya fue creado.

**Contenido del reporte**:
- RUT empleado
- Nombre completo
- Cargo
- Departamento
- Fecha
- Día de la semana
- Hora entrada exacta (HH:MM:SS)
- Hora salida exacta (HH:MM:SS)
- Horas trabajadas
- Horas extras
- Minutos de atraso
- Estado (presente/ausente/tarde/etc.)

### 🔄 CONFIGURAR CRON PARA SINCRONIZACIÓN

```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar cada 5 minutos)
*/5 * * * * /usr/bin/php /var/www/conecta-erp/cron/sincronizar_reloj.php >> /var/www/conecta-erp/logs/reloj_sync.log 2>&1
```

### ⚠️ ERRORES COMUNES RELOJ CONTROL

❌ **Error**: "No se puede conectar al dispositivo"
✅ **Solución**:
```bash
# Verificar conectividad
ping 192.168.1.100

# Verificar puerto abierto
telnet 192.168.1.100 4370

# Verificar firewall
sudo ufw allow from 192.168.1.100 to any port 4370
```

❌ **Error**: "Marcajes duplicados"
✅ **Solución**:
```sql
-- Agregar índice único
ALTER TABLE asistencia_marcajes
ADD UNIQUE KEY uk_marcaje (empleado_id, fecha, hora, tipo);
```

❌ **Error**: "Empleado no encontrado en dispositivo"
✅ **Solución**:
- Verificar `codigo_biometrico` en tabla `empleados`
- Re-enrollar huella en dispositivo
- Verificar sincronización de usuarios

---

## RESUMEN DE INTEGRACIONES

| Integración | Estado | Configuración Crítica |
|-------------|--------|----------------------|
| **SII** | ✅ Completo | Certificado digital .pfx |
| **Previred** | ✅ Completo | Tasas actualizadas 2025 |
| **Transbank** | ✅ Completo | API Key de producción |
| **Reloj Control** | ✅ Completo | IP dispositivos biométricos |

---

**📞 SOPORTE TÉCNICO**

Para problemas con integraciones, contactar al equipo técnico con los siguientes datos:
- Logs del sistema (`/var/www/conecta-erp/logs/`)
- Versión de PHP (`php -v`)
- Detalles del error exacto
- Capturas de pantalla si aplica

