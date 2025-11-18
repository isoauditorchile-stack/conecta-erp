<!-- ===============================================
     MODAL: BOLETA ELECTRÓNICA (39)
     =============================================== -->

<div class="modal fade" id="modalBoleta39" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-receipt"></i> Nueva Boleta Electrónica (39)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBoleta39" method="POST" action="">
                <input type="hidden" name="action" value="crear_boleta_39">
                <input type="hidden" name="tipo_documento" value="39">

                <div class="modal-body">
                    <!-- Información del Documento -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle"></i> Información del Documento
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Emisión <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Venta</label>
                            <select class="form-select" name="tipo_venta">
                                <option value="1" selected>Venta al Contado</option>
                                <option value="2">Venta a Crédito</option>
                            </select>
                        </div>
                    </div>

                    <!-- Datos del Cliente (Opcional para Boleta) -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user"></i> Datos del Cliente (Opcional)
                            </h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Para boletas no es obligatorio identificar al cliente, pero puede hacerlo si lo desea.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RUT Cliente</label>
                            <input type="text" class="form-control" name="rut_receptor" placeholder="12.345.678-9 (opcional)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre Cliente</label>
                            <input type="text" class="form-control" name="razon_social_receptor" placeholder="Nombre (opcional)">
                        </div>
                    </div>

                    <!-- Detalle de Productos -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-shopping-cart"></i> Detalle de Productos/Servicios
                            </h6>
                            <button type="button" class="btn btn-add-item mb-3" onclick="agregarLinea39()">
                                <i class="fas fa-plus"></i> Agregar Producto
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div id="detalle_lineas_39"></div>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="total-section">
                        <div class="total-row">
                            <span>Subtotal (Neto):</span>
                            <strong id="subtotal_39">$0</strong>
                        </div>
                        <div class="total-row">
                            <span>IVA (19%):</span>
                            <strong id="monto_iva_39">$0</strong>
                        </div>
                        <div class="total-row grand-total">
                            <span>TOTAL:</span>
                            <strong id="monto_total_39">$0</strong>
                        </div>
                        <input type="hidden" name="monto_neto" id="monto_neto_hidden_39">
                        <input type="hidden" name="monto_iva" id="monto_iva_hidden_39">
                        <input type="hidden" name="monto_total" id="monto_total_hidden_39">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="timbrar" value="1" class="btn btn-success">
                        <i class="fas fa-stamp"></i> Timbrar y Generar Boleta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let lineasBoleta39 = 0;

function agregarLinea39() {
    lineasBoleta39++;
    const lineaHTML = `
        <div class="detalle-row" id="linea_39_${lineasBoleta39}">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Producto/Servicio</label>
                    <input type="text" class="form-control" name="detalle[${lineasBoleta39}][nombre]" placeholder="Nombre del producto..." required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" name="detalle[${lineasBoleta39}][cantidad]"
                           id="cantidad_39_${lineasBoleta39}" value="1" min="0.01" step="0.01"
                           onchange="calcularLineaTotal39(${lineasBoleta39})" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio Unit.</label>
                    <input type="number" class="form-control" name="detalle[${lineasBoleta39}][precio_unitario]"
                           id="precio_39_${lineasBoleta39}" value="0" min="0" step="1"
                           onchange="calcularLineaTotal39(${lineasBoleta39})" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total Línea</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control fw-bold" id="total_linea_39_${lineasBoleta39}"
                               value="0" readonly style="background: #e9ecef;">
                        <input type="hidden" name="detalle[${lineasBoleta39}][total]" id="total_linea_hidden_39_${lineasBoleta39}">
                    </div>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarLinea39(${lineasBoleta39})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    document.getElementById('detalle_lineas_39').insertAdjacentHTML('beforeend', lineaHTML);
}

function eliminarLinea39(index) {
    if (confirm('¿Eliminar esta línea?')) {
        document.getElementById(`linea_39_${index}`).remove();
        calcularTotales39();
    }
}

function calcularLineaTotal39(index) {
    const cantidad = parseFloat(document.getElementById(`cantidad_39_${index}`).value) || 0;
    const precio = parseFloat(document.getElementById(`precio_39_${index}`).value) || 0;
    const totalLinea = cantidad * precio;

    document.getElementById(`total_linea_39_${index}`).value = formatearMoneda(totalLinea);
    document.getElementById(`total_linea_hidden_39_${index}`).value = totalLinea;
    calcularTotales39();
}

function calcularTotales39() {
    let subtotal = 0;
    for (let i = 1; i <= lineasBoleta39; i++) {
        const totalLinea = parseFloat(document.getElementById(`total_linea_hidden_39_${i}`)?.value) || 0;
        subtotal += totalLinea;
    }

    const montoNeto = subtotal;
    const montoIVA = montoNeto * 0.19;
    const montoTotal = montoNeto + montoIVA;

    document.getElementById('subtotal_39').textContent = '$' + formatearMoneda(montoNeto);
    document.getElementById('monto_iva_39').textContent = '$' + formatearMoneda(montoIVA);
    document.getElementById('monto_total_39').textContent = '$' + formatearMoneda(montoTotal);

    document.getElementById('monto_neto_hidden_39').value = Math.round(montoNeto);
    document.getElementById('monto_iva_hidden_39').value = Math.round(montoIVA);
    document.getElementById('monto_total_hidden_39').value = Math.round(montoTotal);
}

document.getElementById('modalBoleta39').addEventListener('shown.bs.modal', function () {
    if (lineasBoleta39 === 0) {
        agregarLinea39();
    }
});
</script>
