<!-- ===============================================
     MODAL: NOTA DE DÉBITO ELECTRÓNICA (56)
     =============================================== -->

<div class="modal fade" id="modalNotaDebito56" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nueva Nota de Débito Electrónica (56)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNotaDebito56" method="POST" action="">
                <input type="hidden" name="action" value="crear_nota_debito_56">
                <input type="hidden" name="tipo_documento" value="56">

                <div class="modal-body">
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Nota de Débito:</strong> Se utiliza para aumentar el monto de una factura o boleta previamente emitida (ej: intereses por mora, recargos, gastos no incluidos, etc.).
                    </div>

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
                            <label class="form-label">Motivo de la Nota de Débito <span class="text-danger">*</span></label>
                            <select class="form-select" name="motivo_nd" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Intereses por Mora</option>
                                <option value="2">Gastos Adicionales</option>
                                <option value="3">Corrección de Montos</option>
                                <option value="4">Reajustes</option>
                                <option value="5">Otros Recargos</option>
                            </select>
                        </div>
                    </div>

                    <!-- Documento de Referencia -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-file-invoice"></i> Documento de Referencia <span class="text-danger">*</span>
                            </h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Debe indicar el documento al que se le aplicará el cargo adicional
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Documento Ref. <span class="text-danger">*</span></label>
                            <select class="form-select" name="referencia_tipo" id="ref_tipo_56" required>
                                <option value="">Seleccione...</option>
                                <option value="33">Factura Electrónica (33)</option>
                                <option value="34">Factura Exenta (34)</option>
                                <option value="46">Factura de Compra (46)</option>
                                <option value="110">Factura de Exportación (110)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Folio Documento <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="referencia_folio" id="ref_folio_56" placeholder="Ej: 12345" required>
                                <button type="button" class="btn btn-outline-primary" onclick="buscarDocumento56()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Documento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="referencia_fecha" id="ref_fecha_56" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Monto Documento</label>
                            <input type="number" class="form-control" name="referencia_monto" id="ref_monto_56" readonly style="background: #e9ecef;">
                        </div>
                    </div>

                    <!-- Datos del Receptor -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user"></i> Datos del Receptor
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RUT Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="rut_receptor" id="rut_receptor_56" placeholder="12.345.678-9" required readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Razón Social Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="razon_social_receptor" id="razon_social_receptor_56" required readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Giro Receptor</label>
                            <input type="text" class="form-control" name="giro_receptor" id="giro_receptor_56" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Dirección Receptor</label>
                            <input type="text" class="form-control" name="direccion_receptor" id="direccion_receptor_56" readonly>
                        </div>
                    </div>

                    <!-- Detalle de Cargos -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-list"></i> Detalle de Cargos Adicionales
                            </h6>
                            <button type="button" class="btn btn-add-item mb-3" onclick="agregarLinea56()">
                                <i class="fas fa-plus"></i> Agregar Cargo
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div id="detalle_lineas_56"></div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-comment"></i> Observaciones / Razón del Cargo
                            </label>
                            <textarea class="form-control" name="observaciones" rows="3"
                                      placeholder="Describa el motivo del cargo adicional..." required></textarea>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="total-section">
                        <div class="total-row">
                            <span>Monto Neto a Cobrar:</span>
                            <strong id="monto_neto_56">$0</strong>
                        </div>
                        <div class="total-row">
                            <span>IVA a Cobrar:</span>
                            <strong id="monto_iva_56">$0</strong>
                        </div>
                        <div class="total-row grand-total">
                            <span>TOTAL A COBRAR:</span>
                            <strong id="monto_total_56">$0</strong>
                        </div>
                        <input type="hidden" name="monto_neto" id="monto_neto_hidden_56">
                        <input type="hidden" name="monto_iva" id="monto_iva_hidden_56">
                        <input type="hidden" name="monto_total" id="monto_total_hidden_56">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="timbrar" value="1" class="btn btn-success">
                        <i class="fas fa-stamp"></i> Timbrar y Generar ND
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let lineasND56 = 0;

function agregarLinea56() {
    lineasND56++;
    const lineaHTML = `
        <div class="detalle-row" id="linea_56_${lineasND56}">
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Descripción del Cargo</label>
                    <input type="text" class="form-control" name="detalle[${lineasND56}][nombre]" placeholder="Ej: Intereses por mora, Gastos adicionales..." required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" name="detalle[${lineasND56}][cantidad]"
                           id="cantidad_56_${lineasND56}" value="1" min="0.01" step="0.01"
                           onchange="calcularLineaTotal56(${lineasND56})" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Monto Unit.</label>
                    <input type="number" class="form-control" name="detalle[${lineasND56}][precio_unitario]"
                           id="precio_56_${lineasND56}" value="0" min="0" step="1"
                           onchange="calcularLineaTotal56(${lineasND56})" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control fw-bold" id="total_linea_56_${lineasND56}"
                           value="$0" readonly style="background: #e9ecef;">
                    <input type="hidden" name="detalle[${lineasND56}][total]" id="total_hidden_56_${lineasND56}">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarLinea56(${lineasND56})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    document.getElementById('detalle_lineas_56').insertAdjacentHTML('beforeend', lineaHTML);
}

function eliminarLinea56(index) {
    if (confirm('¿Eliminar este cargo?')) {
        document.getElementById(`linea_56_${index}`).remove();
        calcularTotales56();
    }
}

function calcularLineaTotal56(index) {
    const cantidad = parseFloat(document.getElementById(`cantidad_56_${index}`).value) || 0;
    const precio = parseFloat(document.getElementById(`precio_56_${index}`).value) || 0;
    const totalLinea = cantidad * precio;

    document.getElementById(`total_linea_56_${index}`).value = '$' + formatearMoneda(totalLinea);
    document.getElementById(`total_hidden_56_${index}`).value = totalLinea;
    calcularTotales56();
}

function calcularTotales56() {
    let subtotal = 0;
    for (let i = 1; i <= lineasND56; i++) {
        const totalLinea = parseFloat(document.getElementById(`total_hidden_56_${i}`)?.value) || 0;
        subtotal += totalLinea;
    }

    const montoNeto = subtotal;
    const montoIVA = montoNeto * 0.19;
    const montoTotal = montoNeto + montoIVA;

    document.getElementById('monto_neto_56').textContent = '$' + formatearMoneda(montoNeto);
    document.getElementById('monto_iva_56').textContent = '$' + formatearMoneda(montoIVA);
    document.getElementById('monto_total_56').textContent = '$' + formatearMoneda(montoTotal);

    document.getElementById('monto_neto_hidden_56').value = Math.round(montoNeto);
    document.getElementById('monto_iva_hidden_56').value = Math.round(montoIVA);
    document.getElementById('monto_total_hidden_56').value = Math.round(montoTotal);
}

function buscarDocumento56() {
    const tipo = document.getElementById('ref_tipo_56').value;
    const folio = document.getElementById('ref_folio_56').value;

    if (!tipo || !folio) {
        alert('Debe seleccionar tipo de documento e ingresar el folio');
        return;
    }

    fetch(`api/buscar_documento.php?tipo=${tipo}&folio=${folio}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.documento) {
                const doc = data.documento;
                document.getElementById('ref_fecha_56').value = doc.fecha_emision;
                document.getElementById('ref_monto_56').value = doc.monto_total;
                document.getElementById('rut_receptor_56').value = doc.rut_receptor;
                document.getElementById('razon_social_receptor_56').value = doc.razon_social_receptor;
                document.getElementById('giro_receptor_56').value = doc.giro_receptor || '';
                document.getElementById('direccion_receptor_56').value = doc.direccion_receptor || '';
                alert('Documento encontrado y datos cargados');
            } else {
                alert('Documento no encontrado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al buscar el documento');
        });
}

document.getElementById('modalNotaDebito56').addEventListener('shown.bs.modal', function () {
    if (lineasND56 === 0) {
        agregarLinea56();
    }
});
</script>
