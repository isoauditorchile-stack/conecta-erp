<!-- ===============================================
     MODAL: NOTA DE CRÉDITO ELECTRÓNICA (61)
     =============================================== -->

<div class="modal fade" id="modalNotaCredito61" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-undo"></i> Nueva Nota de Crédito Electrónica (61)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNotaCredito61" method="POST" action="">
                <input type="hidden" name="action" value="crear_nota_credito_61">
                <input type="hidden" name="tipo_documento" value="61">

                <div class="modal-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota de Crédito:</strong> Se utiliza para corregir, anular o devolver una factura o boleta previamente emitida.
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
                            <label class="form-label">Motivo de la Nota de Crédito <span class="text-danger">*</span></label>
                            <select class="form-select" name="motivo_nc" id="motivo_nc_61" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Anula Documento de Referencia</option>
                                <option value="2">Corrige Textos de Documento de Referencia</option>
                                <option value="3">Corrige Montos</option>
                                <option value="4">Devolución de Mercaderías</option>
                                <option value="5">Descuentos o Bonificaciones</option>
                                <option value="6">Corrección de Datos del Receptor</option>
                            </select>
                        </div>
                    </div>

                    <!-- Documento de Referencia -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-file-invoice"></i> Documento de Referencia <span class="text-danger">*</span>
                            </h6>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Debe indicar el documento que está corrigiendo o anulando
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Documento Ref. <span class="text-danger">*</span></label>
                            <select class="form-select" name="referencia_tipo" id="ref_tipo_61" required>
                                <option value="">Seleccione...</option>
                                <option value="33">Factura Electrónica (33)</option>
                                <option value="34">Factura Exenta (34)</option>
                                <option value="39">Boleta Electrónica (39)</option>
                                <option value="41">Boleta Exenta (41)</option>
                                <option value="46">Factura de Compra (46)</option>
                                <option value="110">Factura de Exportación (110)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Folio Documento <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="referencia_folio" id="ref_folio_61" placeholder="Ej: 12345" required>
                                <button type="button" class="btn btn-outline-primary" onclick="buscarDocumento61()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Documento <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="referencia_fecha" id="ref_fecha_61" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Monto Documento</label>
                            <input type="number" class="form-control" name="referencia_monto" id="ref_monto_61" readonly style="background: #e9ecef;">
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
                            <input type="text" class="form-control" name="rut_receptor" id="rut_receptor_61" placeholder="12.345.678-9" required readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Razón Social Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="razon_social_receptor" id="razon_social_receptor_61" required readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Giro Receptor</label>
                            <input type="text" class="form-control" name="giro_receptor" id="giro_receptor_61" readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Dirección Receptor</label>
                            <input type="text" class="form-control" name="direccion_receptor" id="direccion_receptor_61" readonly>
                        </div>
                    </div>

                    <!-- Detalle de la Nota de Crédito -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-list"></i> Detalle de la Nota de Crédito
                            </h6>
                            <button type="button" class="btn btn-add-item mb-3" onclick="agregarLinea61()">
                                <i class="fas fa-plus"></i> Agregar Línea
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div id="detalle_lineas_61"></div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-comment"></i> Observaciones / Razón de la Nota de Crédito
                            </label>
                            <textarea class="form-control" name="observaciones" rows="3"
                                      placeholder="Describa el motivo de la nota de crédito..." required></textarea>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="total-section">
                        <div class="total-row">
                            <span>Monto Neto a Devolver:</span>
                            <strong id="monto_neto_61">$0</strong>
                        </div>
                        <div class="total-row">
                            <span>IVA a Devolver:</span>
                            <strong id="monto_iva_61">$0</strong>
                        </div>
                        <div class="total-row grand-total">
                            <span>TOTAL A DEVOLVER:</span>
                            <strong id="monto_total_61">$0</strong>
                        </div>
                        <input type="hidden" name="monto_neto" id="monto_neto_hidden_61">
                        <input type="hidden" name="monto_iva" id="monto_iva_hidden_61">
                        <input type="hidden" name="monto_total" id="monto_total_hidden_61">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="timbrar" value="1" class="btn btn-success">
                        <i class="fas fa-stamp"></i> Timbrar y Generar NC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let lineasNC61 = 0;

function agregarLinea61() {
    lineasNC61++;
    const lineaHTML = `
        <div class="detalle-row" id="linea_61_${lineasNC61}">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" name="detalle[${lineasNC61}][nombre]" placeholder="Descripción del ítem..." required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" name="detalle[${lineasNC61}][cantidad]"
                           id="cantidad_61_${lineasNC61}" value="1" min="0.01" step="0.01"
                           onchange="calcularLineaTotal61(${lineasNC61})" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio Unit.</label>
                    <input type="number" class="form-control" name="detalle[${lineasNC61}][precio_unitario]"
                           id="precio_61_${lineasNC61}" value="0" min="0" step="1"
                           onchange="calcularLineaTotal61(${lineasNC61})" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control fw-bold" id="total_linea_61_${lineasNC61}"
                               value="0" readonly style="background: #e9ecef;">
                        <input type="hidden" name="detalle[${lineasNC61}][total]" id="total_hidden_61_${lineasNC61}">
                    </div>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarLinea61(${lineasNC61})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    document.getElementById('detalle_lineas_61').insertAdjacentHTML('beforeend', lineaHTML);
}

function eliminarLinea61(index) {
    if (confirm('¿Eliminar esta línea?')) {
        document.getElementById(`linea_61_${index}`).remove();
        calcularTotales61();
    }
}

function calcularLineaTotal61(index) {
    const cantidad = parseFloat(document.getElementById(`cantidad_61_${index}`).value) || 0;
    const precio = parseFloat(document.getElementById(`precio_61_${index}`).value) || 0;
    const totalLinea = cantidad * precio;

    document.getElementById(`total_linea_61_${index}`).value = formatearMoneda(totalLinea);
    document.getElementById(`total_hidden_61_${index}`).value = totalLinea;
    calcularTotales61();
}

function calcularTotales61() {
    let subtotal = 0;
    for (let i = 1; i <= lineasNC61; i++) {
        const totalLinea = parseFloat(document.getElementById(`total_hidden_61_${i}`)?.value) || 0;
        subtotal += totalLinea;
    }

    const montoNeto = subtotal;
    const montoIVA = montoNeto * 0.19;
    const montoTotal = montoNeto + montoIVA;

    document.getElementById('monto_neto_61').textContent = '$' + formatearMoneda(montoNeto);
    document.getElementById('monto_iva_61').textContent = '$' + formatearMoneda(montoIVA);
    document.getElementById('monto_total_61').textContent = '$' + formatearMoneda(montoTotal);

    document.getElementById('monto_neto_hidden_61').value = Math.round(montoNeto);
    document.getElementById('monto_iva_hidden_61').value = Math.round(montoIVA);
    document.getElementById('monto_total_hidden_61').value = Math.round(montoTotal);
}

function buscarDocumento61() {
    const tipo = document.getElementById('ref_tipo_61').value;
    const folio = document.getElementById('ref_folio_61').value;

    if (!tipo || !folio) {
        alert('Debe seleccionar tipo de documento e ingresar el folio');
        return;
    }

    // Buscar documento en la base de datos
    fetch(`api/buscar_documento.php?tipo=${tipo}&folio=${folio}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.documento) {
                const doc = data.documento;

                // Llenar datos del documento de referencia
                document.getElementById('ref_fecha_61').value = doc.fecha_emision;
                document.getElementById('ref_monto_61').value = doc.monto_total;

                // Llenar datos del receptor
                document.getElementById('rut_receptor_61').value = doc.rut_receptor;
                document.getElementById('razon_social_receptor_61').value = doc.razon_social_receptor;
                document.getElementById('giro_receptor_61').value = doc.giro_receptor || '';
                document.getElementById('direccion_receptor_61').value = doc.direccion_receptor || '';

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

document.getElementById('modalNotaCredito61').addEventListener('shown.bs.modal', function () {
    if (lineasNC61 === 0) {
        agregarLinea61();
    }
});
</script>
