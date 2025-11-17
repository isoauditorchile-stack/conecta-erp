<?php
/**
 * EXPORTACIÓN A EXCEL - REPORTE DE ASISTENCIA
 * Genera archivo Excel con: RUT, Nombre, Cargo, Hora Entrada, Hora Salida
 */

session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';

requireLogin();

$empresa_id = $_SESSION['empresa_id'];

// Obtener parámetros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];

    // Llamar al procedimiento almacenado
    $stmt = $conn->prepare("CALL sp_reporte_asistencia_excel(?, ?, ?)");
    $stmt->bind_param("iss", $empresa_id, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $datos = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Limpiar buffer
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Headers para descarga Excel
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Reporte_Asistencia_' . $fecha_inicio . '_' . $fecha_fin . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Agregar BOM UTF-8
    echo "\xEF\xBB\xBF";

    // Encabezados
    echo "RUT\tNOMBRE COMPLETO\tCARGO\tDEPARTAMENTO\tFECHA\tDÍA SEMANA\tHORA ENTRADA\tHORA SALIDA\tHORAS TRABAJADAS\tHORAS EXTRAS\tMINUTOS TARDE\tESTADO\n";

    // Datos
    foreach ($datos as $fila) {
        echo implode("\t", [
            $fila['RUT'],
            $fila['NOMBRE_COMPLETO'],
            $fila['CARGO'] ?? '',
            $fila['DEPARTAMENTO'] ?? '',
            $fila['FECHA'],
            $fila['DIA_SEMANA'],
            $fila['HORA_ENTRADA'] ?? '00:00:00',
            $fila['HORA_SALIDA'] ?? '00:00:00',
            $fila['HORAS_TRABAJADAS'] ?? '0.00',
            $fila['HORAS_EXTRAS'] ?? '0.00',
            $fila['MINUTOS_TARDE'] ?? '0',
            $fila['ESTADO']
        ]) . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Reporte Excel - CONECTA ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-file-excel"></i> Exportar Reporte a Excel</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control"
                                       value="<?php echo $fecha_inicio; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control"
                                       value="<?php echo $fecha_fin; ?>" required>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>El reporte incluirá:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>RUT del empleado</li>
                                    <li>Nombre completo</li>
                                    <li>Cargo</li>
                                    <li>Departamento</li>
                                    <li>Hora de entrada exacta (HH:MM:SS)</li>
                                    <li>Hora de salida exacta (HH:MM:SS)</li>
                                    <li>Horas trabajadas y extras</li>
                                    <li>Minutos de atraso</li>
                                    <li>Estado de asistencia</li>
                                </ul>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-download"></i> Descargar Excel
                            </button>
                            <a href="index.php" class="btn btn-secondary w-100 mt-2">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Profesional -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
