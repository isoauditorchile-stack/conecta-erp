<?php
/**
 * ===============================================
 * MÓDULO DE FACTURACIÓN - CARGAR ARCHIVO CAF
 * ===============================================
 * Formulario para cargar archivos CAF (Código de Autorización de Folios)
 * descargados desde el portal del SII
 */

session_start();
require_once '../../includes/config.php';
require_once '../../includes/middleware_acceso.php';

requiereModulo('ventas');

$empresa_id = $_SESSION['empresa_id'];
$usuario_id = $_SESSION['user_id'];

$message = '';
$message_type = '';
$detalle_caf = null;

// Procesar carga de CAF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_caf'])) {
    if ($_FILES['archivo_caf']['error'] === UPLOAD_ERR_OK) {
        $archivo_tmp = $_FILES['archivo_caf']['tmp_name'];
        $archivo_nombre = $_FILES['archivo_caf']['name'];

        // Leer contenido del archivo
        $xml_content = file_get_contents($archivo_tmp);

        try {
            // Parsear XML
            $xml = @simplexml_load_string($xml_content);

            if (!$xml) {
                throw new Exception("El archivo no es un XML válido");
            }

            // Validar que sea un CAF válido
            if (!isset($xml->CAF) || !isset($xml->CAF->DA)) {
                throw new Exception("El archivo no tiene la estructura de un CAF válido");
            }

            // Extraer datos del CAF
            $caf = $xml->CAF->DA;

            $rut_emisor = (string)$caf->RE;
            $tipo_documento = (int)$caf->TD;
            $folio_desde = (int)$caf->RNG->D;
            $folio_hasta = (int)$caf->RNG->H;
            $fecha_autorizacion = (string)$caf->FA;

            // Llaves RSA
            $rsa_modulo = (string)$caf->RSAPK->M;
            $rsa_exponente = (string)$caf->RSAPK->E;
            $idk = (string)$caf->IDK;

            // Validar que el RUT coincida con la empresa
            $stmt = $conn->prepare("SELECT rut FROM empresas WHERE id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $empresa_rut = $stmt->get_result()->fetch_assoc()['rut'];
            $stmt->close();

            if ($empresa_rut !== $rut_emisor) {
                throw new Exception("El CAF no pertenece a esta empresa. RUT del CAF: $rut_emisor, RUT empresa: $empresa_rut");
            }

            // Verificar si el tipo de documento existe
            $stmt = $conn->prepare("SELECT nombre FROM tipos_documentos_sii WHERE codigo = ?");
            $stmt->bind_param("i", $tipo_documento);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Tipo de documento $tipo_documento no existe en el sistema");
            }

            $tipo_doc_nombre = $result->fetch_assoc()['nombre'];
            $stmt->close();

            // Verificar si ya existe este CAF
            $stmt = $conn->prepare("
                SELECT id FROM folios_caf
                WHERE empresa_id = ?
                  AND tipo_documento = ?
                  AND folio_desde = ?
                  AND folio_hasta = ?
            ");
            $stmt->bind_param("iiii", $empresa_id, $tipo_documento, $folio_desde, $folio_hasta);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Este CAF ya fue cargado previamente");
            }
            $stmt->close();

            // Insertar CAF
            $stmt = $conn->prepare("
                INSERT INTO folios_caf (
                    empresa_id, tipo_documento, folio_desde, folio_hasta, folio_actual,
                    caf_xml, fecha_autorizacion, rsa_modulo, rsa_exponente, idk,
                    archivo_original, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')
            ");

            $folio_actual = $folio_desde - 1; // Empezar desde antes del primer folio

            $stmt->bind_param("iiiisssssss",
                $empresa_id,
                $tipo_documento,
                $folio_desde,
                $folio_hasta,
                $folio_actual,
                $xml_content,
                $fecha_autorizacion,
                $rsa_modulo,
                $rsa_exponente,
                $idk,
                $archivo_nombre
            );

            if ($stmt->execute()) {
                $cantidad_folios = $folio_hasta - $folio_desde + 1;
                $message = "CAF cargado exitosamente. Se agregaron $cantidad_folios folios para $tipo_doc_nombre (código $tipo_documento)";
                $message_type = "success";

                $detalle_caf = [
                    'tipo_documento' => $tipo_documento,
                    'tipo_nombre' => $tipo_doc_nombre,
                    'folio_desde' => $folio_desde,
                    'folio_hasta' => $folio_hasta,
                    'cantidad' => $cantidad_folios,
                    'fecha_autorizacion' => $fecha_autorizacion,
                    'rut_empresa' => $rut_emisor
                ];
            } else {
                throw new Exception("Error al guardar CAF en base de datos: " . $stmt->error);
            }

            $stmt->close();

        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Error al subir archivo: " . $_FILES['archivo_caf']['error'];
        $message_type = "danger";
    }
}

// Obtener tipos de documento para referencia
$tipos_doc = $conn->query("
    SELECT codigo, nombre, categoria, fase_implementacion
    FROM tipos_documentos_sii
    WHERE electronico = 1 AND activo = 1 AND requiere_folio = 1
    ORDER BY fase_implementacion ASC, codigo ASC
");

// Tipo de documento pre-seleccionado (desde URL)
$tipo_preseleccionado = $_GET['tipo'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Archivo CAF - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }

        .upload-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .drop-zone {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 60px 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9ff;
        }

        .drop-zone:hover {
            background: #eef1ff;
            border-color: #764ba2;
        }

        .drop-zone.dragging {
            background: #e0e7ff;
            border-color: #4c51bf;
        }

        .drop-zone i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .file-input {
            display: none;
        }

        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .tipo-doc-card {
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .tipo-doc-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .tipo-doc-card.selected {
            border-color: #667eea;
            background: #eef1ff;
        }

        .success-animation {
            text-align: center;
            padding: 40px;
        }

        .success-animation i {
            font-size: 5rem;
            color: #28a745;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .detalle-caf {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .detalle-caf .row {
            margin-bottom: 10px;
        }

        .detalle-caf .label {
            font-weight: 600;
            color: #6c757d;
        }

        .detalle-caf .value {
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-upload"></i> Cargar Archivo CAF</h1>
            <p class="mb-0">Sube el archivo XML descargado desde el portal del SII</p>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <strong>
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php endif; ?>
                </strong>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($detalle_caf): ?>
            <!-- Animación de éxito -->
            <div class="success-animation">
                <i class="fas fa-check-circle"></i>
                <h3 class="mt-3">CAF Cargado Exitosamente</h3>
            </div>

            <!-- Detalle del CAF cargado -->
            <div class="detalle-caf">
                <h5 class="mb-3"><i class="fas fa-info-circle"></i> Detalle del CAF Cargado</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Tipo de Documento:</div>
                        <div class="value"><?php echo $detalle_caf['tipo_nombre']; ?> (<?php echo $detalle_caf['tipo_documento']; ?>)</div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">RUT Empresa:</div>
                        <div class="value"><?php echo $detalle_caf['rut_empresa']; ?></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Rango de Folios:</div>
                        <div class="value">
                            Desde <?php echo number_format($detalle_caf['folio_desde']); ?>
                            hasta <?php echo number_format($detalle_caf['folio_hasta']); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Cantidad de Folios:</div>
                        <div class="value"><?php echo number_format($detalle_caf['cantidad']); ?> folios</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="label">Fecha de Autorización:</div>
                        <div class="value"><?php echo date('d/m/Y', strtotime($detalle_caf['fecha_autorizacion'])); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Estado:</div>
                        <div class="value"><span class="badge bg-success">Activo</span></div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="folios.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-list"></i> Ver Estado de Folios
                </a>
                <a href="cargar_caf.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-plus"></i> Cargar Otro CAF
                </a>
            </div>

        <?php else: ?>
            <!-- Formulario de carga -->
            <div class="upload-card">
                <h3 class="mb-4"><i class="fas fa-file-upload"></i> Selecciona el Archivo CAF</h3>

                <form method="POST" enctype="multipart/form-data" id="formUpload">
                    <!-- Zona de Drag & Drop -->
                    <div class="drop-zone" id="dropZone" onclick="document.getElementById('archivo_caf').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4>Arrastra el archivo CAF aquí</h4>
                        <p class="text-muted">o haz clic para seleccionarlo desde tu computador</p>
                        <p class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Solo archivos XML (formato CAF del SII)
                            </small>
                        </p>
                    </div>

                    <input type="file"
                           id="archivo_caf"
                           name="archivo_caf"
                           accept=".xml"
                           class="file-input"
                           required>

                    <div id="fileInfo" class="mt-3" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-file-alt"></i>
                            <strong>Archivo seleccionado:</strong>
                            <span id="fileName"></span>
                            <span class="float-end">
                                <span id="fileSize"></span>
                            </span>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn-upload" id="btnSubmit" disabled>
                            <i class="fas fa-upload"></i> Cargar CAF al Sistema
                        </button>
                    </div>
                </form>

                <!-- Tipos de documentos comunes -->
                <div class="mt-5">
                    <h5><i class="fas fa-info-circle"></i> Tipos de Documentos Más Comunes</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="tipo-doc-card">
                                <strong>33 - Factura Electrónica</strong>
                                <p class="text-muted mb-0"><small>Ventas B2B afectas a IVA</small></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="tipo-doc-card">
                                <strong>39 - Boleta Electrónica</strong>
                                <p class="text-muted mb-0"><small>Ventas B2C afectas a IVA</small></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="tipo-doc-card">
                                <strong>52 - Guía de Despacho Electrónica</strong>
                                <p class="text-muted mb-0"><small>Traslado de mercancías</small></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="tipo-doc-card">
                                <strong>61 - Nota de Crédito Electrónica</strong>
                                <p class="text-muted mb-0"><small>Devoluciones y descuentos</small></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instrucciones -->
                <div class="mt-4">
                    <h5><i class="fas fa-question-circle"></i> ¿Cómo obtener el archivo CAF?</h5>
                    <ol>
                        <li>Ingresa al <a href="https://www4.sii.cl/registrosc/" target="_blank">Portal SII</a></li>
                        <li>Autentícate con tu RUT y Clave Tributaria</li>
                        <li>Ve a "Documentos Tributarios Electrónicos" → "Solicitar Folios"</li>
                        <li>Selecciona el tipo de documento y la cantidad de folios</li>
                        <li>Descarga el archivo CAF (formato XML)</li>
                        <li>Sube el archivo aquí</li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4 mb-4">
            <a href="folios.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Gestión de Folios
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('archivo_caf');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const btnSubmit = document.getElementById('btnSubmit');

        // Prevenir comportamiento por defecto
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragging');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragging');
            }, false);
        });

        // Handle drop
        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                handleFiles(files);
            }
        }, false);

        // Handle file selection
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            if (files.length === 0) return;

            const file = files[0];

            // Validar que sea XML
            if (!file.name.endsWith('.xml')) {
                alert('Por favor selecciona un archivo XML');
                fileInput.value = '';
                return;
            }

            // Mostrar información del archivo
            fileName.textContent = file.name;
            fileSize.textContent = formatBytes(file.size);
            fileInfo.style.display = 'block';
            btnSubmit.disabled = false;
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    </script>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
