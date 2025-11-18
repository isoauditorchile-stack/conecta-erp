<!-- ===============================================
     MODAL: FACTURA ELECTRÓNICA (33)
     ===============================================
     Modal completo y funcional para crear Facturas Electrónicas
     - Selección de cliente con búsqueda
     - Agregar múltiples productos/servicios
     - Cálculo automático de totales
     - Validación de datos
     - Referencias a otros documentos
================================================== -->

<div class="modal fade" id="modalFactura33" tabindex="-1" aria-labelledby="modalFactura33Label" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFactura33Label">
                    <i class="fas fa-file-invoice"></i> Nueva Factura Electrónica (33)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formFactura33" method="POST" action="">
                <input type="hidden" name="action" value="crear_factura_33">
                <input type="hidden" name="tipo_documento" value="33">

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
                            <input type="date" class="form-control" name="fecha_emision" id="fecha_emision_33" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="date" class="form-control" name="fecha_vencimiento" id="fecha_vencimiento_33">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Forma de Pago <span class="text-danger">*</span></label>
                            <select class="form-select" name="forma_pago" id="forma_pago_33" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Contado</option>
                                <option value="2">Crédito</option>
                                <option value="3">Sin Costo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Datos del Receptor (Cliente) -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user"></i> Datos del Receptor (Cliente)
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Buscar Cliente <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscar_cliente_33" placeholder="Buscar por RUT o Razón Social...">
                                <button type="button" class="btn btn-primary" onclick="buscarCliente33()">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-success" onclick="nuevoCliente33()">
                                    <i class="fas fa-plus"></i> Nuevo
                                </button>
                            </div>
                            <input type="hidden" name="cliente_id" id="cliente_id_33" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RUT Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="rut_receptor" id="rut_receptor_33" placeholder="12.345.678-9" required readonly>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label class="form-label">Razón Social Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="razon_social_receptor" id="razon_social_receptor_33" required readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Giro Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="giro_receptor" id="giro_receptor_33" required readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Dirección Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="direccion_receptor" id="direccion_receptor_33" required readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Comuna Receptor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="comuna_receptor" id="comuna_receptor_33" required readonly>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label">Ciudad Receptor</label>
                            <input type="text" class="form-control" name="ciudad_receptor" id="ciudad_receptor_33" readonly>
                        </div>
                    </div>

                    <!-- Detalle de Productos/Servicios -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-box"></i> Detalle de Productos/Servicios
                            </h6>
                            <button type="button" class="btn btn-add-item mb-3" onclick="agregarLinea33()">
                                <i class="fas fa-plus"></i> Agregar Línea
                            </button>
                        </div>
                        <div class="col-md-12">
                            <div id="detalle_lineas_33">
                                <!-- Las líneas se agregarán dinámicamente aquí -->
                            </div>
                        </div>
                    </div>

                    <!-- Descuentos y Recargos Globales -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-percent"></i> Descuentos y Recargos Globales
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Descuento Global %</label>
                            <input type="number" class="form-control" name="descuento_global_pct" id="descuento_global_pct_33"
                                   value="0" min="0" max="100" step="0.01" onchange="calcularTotales33()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Descuento Global Monto $</label>
                            <input type="number" class="form-control" name="descuento_global_monto" id="descuento_global_monto_33"
                                   value="0" min="0" step="1" onchange="calcularTotales33()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recargo Global Monto $</label>
                            <input type="number" class="form-control" name="recargo_global_monto" id="recargo_global_monto_33"
                                   value="0" min="0" step="1" onchange="calcularTotales33()">
                        </div>
                    </div>

                    <!-- Referencias a Otros Documentos -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-link"></i> Referencias a Otros Documentos (Opcional)
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Referencia</label>
                            <select class="form-select" name="referencia_tipo" id="referencia_tipo_33">
                                <option value="">Sin referencia</option>
                                <option value="801">Orden de Compra</option>
                                <option value="802">Nota de Pedido</option>
                                <option value="803">Contrato</option>
                                <option value="804">Resolución</option>
                                <option value="805">Proceso ChileCompra</option>
                                <option value="806">Ficha ChileCompra</option>
                                <option value="807">DUS</option>
                                <option value="808">B/L</option>
                                <option value="809">AWB</option>
                                <option value="810">MIC/DTA</option>
                                <option value="811">Carta de Porte</option>
                                <option value="812">Resolución del SNA</option>
                                <option value="813">Pasaporte</option>
                                <option value="814">Certificado de Depósito Bolsa Prod. Chile</option>
                                <option value="815">Vale de Prenda Bolsa Prod. Chile</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Folio Referencia</label>
                            <input type="text" class="form-control" name="referencia_folio" id="referencia_folio_33" placeholder="Ej: 12345">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Referencia</label>
                            <input type="date" class="form-control" name="referencia_fecha" id="referencia_fecha_33">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Razón Referencia</label>
                            <input type="text" class="form-control" name="referencia_razon" id="referencia_razon_33" placeholder="Motivo...">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-comment"></i> Observaciones / Notas
                            </label>
                            <textarea class="form-control" name="observaciones" id="observaciones_33" rows="3"
                                      placeholder="Observaciones adicionales del documento..." maxlength="1000"></textarea>
                            <small class="text-muted">Máximo 1000 caracteres</small>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="total-section">
                        <div class="total-row">
                            <span>Subtotal (Neto):</span>
                            <strong id="subtotal_33">$0</strong>
                        </div>
                        <div class="total-row">
                            <span>Descuento Global:</span>
                            <strong id="descuento_total_33">$0</strong>
                        </div>
                        <div class="total-row">
                            <span>Recargo Global:</span>
                            <strong id="recargo_total_33">$0</strong>
                        </div>
                        <div class="total-row">
                            <span>Monto Neto:</span>
                            <strong id="monto_neto_33">$0</strong>
                        </div>
                        <div class="total-row">
                            <span>IVA (19%):</span>
                            <strong id="monto_iva_33">$0</strong>
                        </div>
                        <div class="total-row grand-total">
                            <span>TOTAL:</span>
                            <strong id="monto_total_33">$0</strong>
                        </div>

                        <!-- Campos ocultos para enviar -->
                        <input type="hidden" name="monto_neto" id="monto_neto_hidden_33">
                        <input type="hidden" name="monto_iva" id="monto_iva_hidden_33">
                        <input type="hidden" name="monto_total" id="monto_total_hidden_33">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="guardar_borrador" value="1" class="btn btn-info">
                        <i class="fas fa-save"></i> Guardar Borrador
                    </button>
                    <button type="submit" name="timbrar" value="1" class="btn btn-success">
                        <i class="fas fa-stamp"></i> Timbrar y Generar DTE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Búsqueda de Clientes -->
<div class="modal fade" id="modalBuscarCliente33" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-search"></i> Buscar Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-lg" id="input_buscar_cliente_33"
                           placeholder="Buscar por RUT, Razón Social o Nombre..." onkeyup="filtrarClientes33()">
                </div>
                <div id="resultados_clientes_33" style="max-height: 400px; overflow-y: auto;">
                    <!-- Resultados de búsqueda -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales para Factura 33
let lineasFactura33 = 0;
let productosFactura33 = [];

// Agregar línea de detalle
function agregarLinea33() {
    lineasFactura33++;
    const lineaHTML = `
        <div class="detalle-row" id="linea_33_${lineasFactura33}">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Producto/Servicio</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="detalle[${lineasFactura33}][nombre]"
                               id="producto_nombre_33_${lineasFactura33}" placeholder="Nombre del producto..." required>
                        <button type="button" class="btn btn-outline-primary" onclick="buscarProducto33(${lineasFactura33})">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <input type="hidden" name="detalle[${lineasFactura33}][producto_id]" id="producto_id_33_${lineasFactura33}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" name="detalle[${lineasFactura33}][cantidad]"
                           id="cantidad_33_${lineasFactura33}" value="1" min="0.01" step="0.01"
                           onchange="calcularLineaTotal33(${lineasFactura33})" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio Unit.</label>
                    <input type="number" class="form-control" name="detalle[${lineasFactura33}][precio_unitario]"
                           id="precio_33_${lineasFactura33}" value="0" min="0" step="1"
                           onchange="calcularLineaTotal33(${lineasFactura33})" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desc. %</label>
                    <input type="number" class="form-control" name="detalle[${lineasFactura33}][descuento_pct]"
                           id="descuento_33_${lineasFactura33}" value="0" min="0" max="100" step="0.01"
                           onchange="calcularLineaTotal33(${lineasFactura33})">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total Línea</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control fw-bold" id="total_linea_33_${lineasFactura33}"
                               value="0" readonly style="background: #e9ecef;">
                        <input type="hidden" name="detalle[${lineasFactura33}][total]" id="total_linea_hidden_33_${lineasFactura33}">
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <textarea class="form-control form-control-sm" name="detalle[${lineasFactura33}][descripcion]"
                              id="descripcion_33_${lineasFactura33}" rows="1"
                              placeholder="Descripción adicional (opcional)..."></textarea>
                </div>
                <div class="col-md-12 mt-2 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarLinea33(${lineasFactura33})">
                        <i class="fas fa-trash"></i> Eliminar Línea
                    </button>
                </div>
            </div>
        </div>
    `;
    document.getElementById('detalle_lineas_33').insertAdjacentHTML('beforeend', lineaHTML);
}

// Eliminar línea de detalle
function eliminarLinea33(index) {
    if (confirm('¿Eliminar esta línea?')) {
        document.getElementById(`linea_33_${index}`).remove();
        calcularTotales33();
    }
}

// Calcular total de una línea
function calcularLineaTotal33(index) {
    const cantidad = parseFloat(document.getElementById(`cantidad_33_${index}`).value) || 0;
    const precio = parseFloat(document.getElementById(`precio_33_${index}`).value) || 0;
    const descuentoPct = parseFloat(document.getElementById(`descuento_33_${index}`).value) || 0;

    const subtotalLinea = cantidad * precio;
    const descuentoMonto = subtotalLinea * (descuentoPct / 100);
    const totalLinea = subtotalLinea - descuentoMonto;

    document.getElementById(`total_linea_33_${index}`).value = formatearMoneda(totalLinea);
    document.getElementById(`total_linea_hidden_33_${index}`).value = totalLinea;

    calcularTotales33();
}

// Calcular totales del documento
function calcularTotales33() {
    let subtotal = 0;

    // Sumar todas las líneas
    for (let i = 1; i <= lineasFactura33; i++) {
        const totalLinea = parseFloat(document.getElementById(`total_linea_hidden_33_${i}`)?.value) || 0;
        subtotal += totalLinea;
    }

    // Aplicar descuentos y recargos globales
    const descuentoPct = parseFloat(document.getElementById('descuento_global_pct_33').value) || 0;
    const descuentoMonto = parseFloat(document.getElementById('descuento_global_monto_33').value) || 0;
    const recargoMonto = parseFloat(document.getElementById('recargo_global_monto_33').value) || 0;

    const descuentoPctMonto = subtotal * (descuentoPct / 100);
    const descuentoTotal = descuentoPctMonto + descuentoMonto;

    const montoNeto = subtotal - descuentoTotal + recargoMonto;
    const montoIVA = montoNeto * 0.19;
    const montoTotal = montoNeto + montoIVA;

    // Mostrar valores
    document.getElementById('subtotal_33').textContent = '$' + formatearMoneda(subtotal);
    document.getElementById('descuento_total_33').textContent = '-$' + formatearMoneda(descuentoTotal);
    document.getElementById('recargo_total_33').textContent = '+$' + formatearMoneda(recargoMonto);
    document.getElementById('monto_neto_33').textContent = '$' + formatearMoneda(montoNeto);
    document.getElementById('monto_iva_33').textContent = '$' + formatearMoneda(montoIVA);
    document.getElementById('monto_total_33').textContent = '$' + formatearMoneda(montoTotal);

    // Campos ocultos
    document.getElementById('monto_neto_hidden_33').value = Math.round(montoNeto);
    document.getElementById('monto_iva_hidden_33').value = Math.round(montoIVA);
    document.getElementById('monto_total_hidden_33').value = Math.round(montoTotal);
}

// Formatear moneda
function formatearMoneda(monto) {
    return Math.round(monto).toLocaleString('es-CL');
}

// Buscar cliente
function buscarCliente33() {
    const modalBuscar = new bootstrap.Modal(document.getElementById('modalBuscarCliente33'));
    modalBuscar.show();
    cargarClientes33();
}

// Cargar lista de clientes
function cargarClientes33() {
    fetch('api/buscar_clientes.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarClientes33(data.clientes);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Mostrar clientes en modal
function mostrarClientes33(clientes) {
    const resultados = document.getElementById('resultados_clientes_33');
    if (clientes.length === 0) {
        resultados.innerHTML = '<p class="text-center text-muted">No se encontraron clientes</p>';
        return;
    }

    let html = '<div class="list-group">';
    clientes.forEach(cliente => {
        html += `
            <a href="#" class="list-group-item list-group-item-action" onclick="seleccionarCliente33(${cliente.id}, '${cliente.rut}', '${cliente.razon_social}', '${cliente.giro}', '${cliente.direccion}', '${cliente.comuna}', '${cliente.ciudad}'); return false;">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${cliente.razon_social}</h6>
                    <small>${cliente.rut}</small>
                </div>
                <p class="mb-1 small">${cliente.giro || 'Sin giro'}</p>
                <small class="text-muted">${cliente.direccion || ''}, ${cliente.comuna || ''}</small>
            </a>
        `;
    });
    html += '</div>';
    resultados.innerHTML = html;
}

// Filtrar clientes en búsqueda
function filtrarClientes33() {
    const termino = document.getElementById('input_buscar_cliente_33').value.toLowerCase();
    const items = document.querySelectorAll('#resultados_clientes_33 .list-group-item');

    items.forEach(item => {
        const texto = item.textContent.toLowerCase();
        item.style.display = texto.includes(termino) ? '' : 'none';
    });
}

// Seleccionar cliente
function seleccionarCliente33(id, rut, razonSocial, giro, direccion, comuna, ciudad) {
    document.getElementById('cliente_id_33').value = id;
    document.getElementById('rut_receptor_33').value = rut;
    document.getElementById('razon_social_receptor_33').value = razonSocial;
    document.getElementById('giro_receptor_33').value = giro || '';
    document.getElementById('direccion_receptor_33').value = direccion || '';
    document.getElementById('comuna_receptor_33').value = comuna || '';
    document.getElementById('ciudad_receptor_33').value = ciudad || '';

    // Cerrar modal
    const modalBuscar = bootstrap.Modal.getInstance(document.getElementById('modalBuscarCliente33'));
    modalBuscar.hide();
}

// Nuevo cliente
function nuevoCliente33() {
    alert('Funcionalidad de crear nuevo cliente en desarrollo');
    // Aquí se abriría un modal para crear un nuevo cliente
}

// Buscar producto
function buscarProducto33(index) {
    alert('Funcionalidad de búsqueda de productos en desarrollo');
    // Aquí se abriría un modal para buscar productos
}

// Inicializar al abrir modal
document.getElementById('modalFactura33').addEventListener('shown.bs.modal', function () {
    if (lineasFactura33 === 0) {
        agregarLinea33(); // Agregar primera línea automáticamente
    }
});

// Validar formulario antes de enviar
document.getElementById('formFactura33').addEventListener('submit', function(e) {
    if (lineasFactura33 === 0) {
        e.preventDefault();
        alert('Debe agregar al menos una línea de detalle');
        return false;
    }

    const montoTotal = parseFloat(document.getElementById('monto_total_hidden_33').value);
    if (montoTotal <= 0) {
        e.preventDefault();
        alert('El monto total debe ser mayor a $0');
        return false;
    }

    // Confirmar antes de timbrar
    if (document.activeElement.name === 'timbrar') {
        if (!confirm('¿Está seguro de timbrar este documento? Una vez timbrado no podrá ser modificado.')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>
