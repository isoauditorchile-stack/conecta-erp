<!-- ===============================================
     MODAL: GUÍA DE DESPACHO ELECTRÓNICA (52)
     =============================================== -->

<div class="modal fade" id="modalGuiaDespacho52" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-truck"></i> Nueva Guía de Despacho Electrónica (52)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formGuia52" method="POST" action="">
                <input type="hidden" name="action" value="crear_guia_52">
                <input type="hidden" name="tipo_documento" value="52">

                <div class="modal-body">
                    <!-- Información del Documento -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle"></i> Información del Documento
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Emisión <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Traslado <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_traslado" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Operación constituye venta</option>
                                <option value="2">Ventas por efectuar</option>
                                <option value="3">Consignaciones</option>
                                <option value="4">Entrega gratuita</option>
                                <option value="5">Traslados internos</option>
                                <option value="6">Otros traslados no venta</option>
                                <option value="7">Guía de devolución</option>
                                <option value="8">Traslado para exportación</option>
                                <option value="9">Venta para exportación</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Indicador de Traslado</label>
                            <select class="form-select" name="indicador_traslado">
                                <option value="">Sin indicador</option>
                                <option value="1">Despacho por el vendedor</option>
                                <option value="2">Despacho por el comprador</option>
                            </select>
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
                            <input type="text" class="form-control" name="rut_receptor" placeholder="12.345.678-9" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Razón Social Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="razon_social_receptor" required>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label class="form-label">Dirección de Entrega <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="direccion_entrega" placeholder="Dirección completa..." required>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Comuna de Entrega <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="comuna_entrega" required>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Ciudad de Entrega</label>
                            <input type="text" class="form-control" name="ciudad_entrega">
                        </div>
                    </div>

                    <!-- Datos del Transporte -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-truck-moving"></i> Datos del Transporte
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Patente del Vehículo</label>
                            <input type="text" class="form-control" name="patente" placeholder="XX-YY-11">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">RUT Transportista</label>
                            <input type="text" class="form-control" name="rut_transportista" placeholder="12.345.678-9">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre Transportista/Chofer</label>
                            <input type="text" class="form-control" name="nombre_transportista">
                        </div>
                    </div>

                    <!-- Detalle de Productos -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-box"></i> Detalle de Productos
                            </h6>
                            <button type="button" class="btn btn-add-item mb-3" onclick="agregarLinea52()">
                                <i class="fas fa-plus"></i> Agregar Producto
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div id="detalle_lineas_52"></div>
                        </div>
                    </div>

                    <!-- Referencias -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-link"></i> Referencia a Documento (Opcional)
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Referencia</label>
                            <select class="form-select" name="referencia_tipo">
                                <option value="">Sin referencia</option>
                                <option value="33">Factura Electrónica</option>
                                <option value="34">Factura Exenta</option>
                                <option value="801">Orden de Compra</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Folio Referencia</label>
                            <input type="text" class="form-control" name="referencia_folio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Razón Referencia</label>
                            <input type="text" class="form-control" name="referencia_razon">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-comment"></i> Observaciones
                            </label>
                            <textarea class="form-control" name="observaciones" rows="2" placeholder="Observaciones adicionales..."></textarea>
                        </div>
                    </div>

                    <!-- Totales (para guías con valores) -->
                    <div class="total-section">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Los valores en guía de despacho son opcionales según el tipo de traslado.
                        </div>
                        <div class="total-row grand-total">
                            <span>TOTAL MERCADERÍA:</span>
                            <strong id="monto_total_52">$0</strong>
                        </div>
                        <input type="hidden" name="monto_total" id="monto_total_hidden_52">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="timbrar" value="1" class="btn btn-success">
                        <i class="fas fa-stamp"></i> Timbrar y Generar Guía
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let lineasGuia52 = 0;

function agregarLinea52() {
    lineasGuia52++;
    const lineaHTML = `
        <div class="detalle-row" id="linea_52_${lineasGuia52}">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Producto</label>
                    <input type="text" class="form-control" name="detalle[${lineasGuia52}][nombre]" placeholder="Nombre del producto..." required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" name="detalle[${lineasGuia52}][cantidad]"
                           id="cantidad_52_${lineasGuia52}" value="1" min="0.01" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio (Opcional)</label>
                    <input type="number" class="form-control" name="detalle[${lineasGuia52}][precio_unitario]"
                           id="precio_52_${lineasGuia52}" value="0" min="0" step="1" onchange="calcularTotales52()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control" id="total_linea_52_${lineasGuia52}" value="$0" readonly style="background: #e9ecef;">
                    <input type="hidden" name="detalle[${lineasGuia52}][total]" id="total_hidden_52_${lineasGuia52}">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarLinea52(${lineasGuia52})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    document.getElementById('detalle_lineas_52').insertAdjacentHTML('beforeend', lineaHTML);
}

function eliminarLinea52(index) {
    if (confirm('¿Eliminar esta línea?')) {
        document.getElementById(`linea_52_${index}`).remove();
        calcularTotales52();
    }
}

function calcularTotales52() {
    let total = 0;
    for (let i = 1; i <= lineasGuia52; i++) {
        const cantidad = parseFloat(document.getElementById(`cantidad_52_${i}`)?.value) || 0;
        const precio = parseFloat(document.getElementById(`precio_52_${i}`)?.value) || 0;
        const totalLinea = cantidad * precio;

        if (document.getElementById(`total_linea_52_${i}`)) {
            document.getElementById(`total_linea_52_${i}`).value = '$' + formatearMoneda(totalLinea);
            document.getElementById(`total_hidden_52_${i}`).value = totalLinea;
        }

        total += totalLinea;
    }

    document.getElementById('monto_total_52').textContent = '$' + formatearMoneda(total);
    document.getElementById('monto_total_hidden_52').value = Math.round(total);
}

document.getElementById('modalGuiaDespacho52').addEventListener('shown.bs.modal', function () {
    if (lineasGuia52 === 0) {
        agregarLinea52();
    }
});
</script>
