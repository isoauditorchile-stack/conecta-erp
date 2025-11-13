# CONECTA ERP - Guía de Validación de RUT

## 📋 Resumen

Este documento describe la implementación del sistema de validación de RUT (Rol Único Tributario) chileno en todos los módulos del ERP CONECTA. El sistema utiliza el algoritmo Módulo 11 para validar RUTs y proporciona retroalimentación visual en tiempo real.

## 🎯 Características

- ✅ Validación con algoritmo Módulo 11
- ✅ Formato automático: `XX.XXX.XXX-X`
- ✅ Retroalimentación visual (bordes verdes/rojos)
- ✅ Mensajes de error descriptivos
- ✅ Soporte multi-país (CL, AR, PE, CO, MX, BR, US, ES)
- ✅ Validación en tiempo real (on blur)
- ✅ Validación al enviar formulario
- ✅ Fácil integración con data attributes

## 📁 Archivos del Sistema

### 1. JavaScript Library
**Ubicación:** `/assets/js/rut-validator.js`

Librería global que contiene todas las funciones de validación y formateo de RUT.

**Funciones principales:**
- `RUTValidator.formatChileanRUT(rut)` - Formatea RUT con puntos y guión
- `RUTValidator.validateChileanRUT(rut)` - Valida RUT usando Módulo 11
- `RUTValidator.cleanRUT(rut)` - Limpia formato del RUT
- `RUTValidator.init(inputSelector, countrySelector)` - Inicializa validación

### 2. CSS Styles
**Ubicación:** `/assets/css/rut-validator.css`

Estilos para retroalimentación visual y estados de validación.

**Clases CSS:**
- `.rut-input.valid` - Estado válido (verde)
- `.rut-input.invalid` - Estado inválido (rojo)
- `.rut-error-message` - Mensaje de error
- `.rut-success-message` - Mensaje de éxito
- `.tax-id-hint` - Texto de ayuda

## 🚀 Uso Básico

### Método 1: Auto-inicialización con Data Attributes (Recomendado)

```html
<!-- Incluir archivos CSS y JS -->
<link rel="stylesheet" href="/assets/css/rut-validator.css">
<script src="/assets/js/rut-validator.js"></script>

<!-- Agregar data-rut-validator al input -->
<input type="text"
       id="tax_id"
       name="tax_id"
       data-rut-validator="true"
       data-country-selector="#country"
       required>

<select id="country" name="country">
    <option value="CL">Chile</option>
    <option value="AR">Argentina</option>
    <!-- más países -->
</select>
```

**Ventajas:**
- ✅ No requiere JavaScript adicional
- ✅ Se inicializa automáticamente al cargar la página
- ✅ Ideal para múltiples formularios

### Método 2: Inicialización Manual

```html
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar en un input específico
    RUTValidator.init('#tax_id', '#country');

    // O inicializar en múltiples inputs
    RUTValidator.init('#employee_rut', '#country');
    RUTValidator.init('#customer_rut', '#country');
});
</script>
```

### Método 3: Uso Programático

```javascript
// Validar un RUT manualmente
const rut = '15.895.771-k';
if (RUTValidator.validateChileanRUT(rut)) {
    console.log('RUT válido');
}

// Formatear un RUT
const formatted = RUTValidator.formatChileanRUT('158957717');
console.log(formatted); // Output: 15.895.771-7

// Limpiar formato de un RUT
const clean = RUTValidator.cleanRUT('15.895.771-k');
console.log(clean); // Output: 15895771K
```

## 📊 Tablas de Base de Datos con Campos RUT/Tax ID

A continuación se listan todas las tablas que requieren validación de RUT:

### Módulo: Administración Central (ADM)

| Tabla | Campo | Descripción | Requerido |
|-------|-------|-------------|-----------|
| `companies` | `tax_id` | RUT de la empresa | ✅ |
| `users` | `tax_id` | RUT del usuario (opcional) | ❌ |

### Módulo: Gestión de Entidades (ENT)

| Tabla | Campo | Descripción | Requerido |
|-------|-------|-------------|-----------|
| `sd_customers` | `tax_id` | RUT del cliente | ✅ |
| `mm_suppliers` | `tax_id` | RUT del proveedor | ✅ |

### Módulo: Recursos Humanos (HCM)

| Tabla | Campo | Descripción | Requerido |
|-------|-------|-------------|-----------|
| `hcm_employees` | `tax_id` | RUT del empleado | ✅ |
| `hcm_previred_employee_data` | `rut` | RUT para exportación Previred | ✅ |
| `hcm_emergency_contacts` | `tax_id` | RUT del contacto de emergencia | ❌ |

### Módulo: SII (Integración Tributaria)

| Tabla | Campo | Descripción | Requerido |
|-------|-------|-------------|-----------|
| `sii_dte` | `rut_emisor` | RUT del emisor del DTE | ✅ |
| `sii_dte` | `rut_receptor` | RUT del receptor del DTE | ✅ |
| `sii_certificados` | `rut_titular` | RUT del titular del certificado | ✅ |

### Módulo: Finanzas (FI)

| Tabla | Campo | Descripción | Requerido |
|-------|-------|-------------|-----------|
| `fi_accounts_receivable` | `customer_tax_id` | RUT del cliente (cuentas por cobrar) | ✅ |
| `fi_accounts_payable` | `supplier_tax_id` | RUT del proveedor (cuentas por pagar) | ✅ |

### Módulo: CRM

| Tabla | Campo | Descripción | Requerido |
|-------|-------|-------------|-----------|
| `crm_leads` | `tax_id` | RUT del prospecto | ❌ |
| `crm_contacts` | `tax_id` | RUT del contacto | ❌ |

## 🎨 Ejemplos de Implementación por Módulo

### 1. Formulario de Registro de Empleados (HCM)

```html
<form action="/hcm/employees/create" method="POST">
    <div class="rut-input-wrapper">
        <label for="employee_rut" class="required">RUT Empleado</label>
        <input type="text"
               id="employee_rut"
               name="tax_id"
               class="rut-input"
               data-rut-validator="true"
               required>
        <small class="tax-id-hint">Formato: XX.XXX.XXX-X</small>
    </div>

    <button type="submit">Guardar Empleado</button>
</form>
```

### 2. Formulario de Clientes (SD - Ventas)

```html
<form action="/sd/customers/create" method="POST">
    <div class="form-group">
        <label for="customer_name">Nombre/Razón Social</label>
        <input type="text" id="customer_name" name="customer_name" required>
    </div>

    <div class="form-group">
        <label for="country">País</label>
        <select id="country" name="country_id" required>
            <option value="CL">Chile</option>
            <option value="AR">Argentina</option>
        </select>
    </div>

    <div class="form-group">
        <label for="customer_rut" class="required">RUT/Tax ID</label>
        <input type="text"
               id="customer_rut"
               name="tax_id"
               data-rut-validator="true"
               data-country-selector="#country"
               required>
        <small id="customer_rutHint" class="tax-id-hint">
            Formato: XX.XXX.XXX-X
        </small>
    </div>

    <button type="submit">Crear Cliente</button>
</form>
```

### 3. Formulario de Proveedores (MM - Materiales)

```html
<form action="/mm/suppliers/create" method="POST" id="supplierForm">
    <div class="rut-input-wrapper">
        <label for="supplier_rut" class="required">RUT Proveedor</label>
        <input type="text"
               id="supplier_rut"
               name="tax_id"
               data-rut-validator="true"
               placeholder="15.895.771-k"
               required>
    </div>

    <button type="submit">Crear Proveedor</button>
</form>

<script>
// Validación adicional personalizada si es necesaria
document.getElementById('supplierForm').addEventListener('submit', function(e) {
    const rut = document.getElementById('supplier_rut').value;

    // Validación adicional de negocio
    if (rut && !RUTValidator.validateChileanRUT(rut)) {
        e.preventDefault();
        alert('El RUT ingresado no es válido');
    }
});
</script>
```

### 4. Formulario de Exportación Previred (HCM)

```html
<form action="/hcm/previred/export" method="POST">
    <h3>Exportación Previred - Datos del Empleado</h3>

    <div class="form-row">
        <div class="col">
            <label for="previred_rut">RUT</label>
            <input type="text"
                   id="previred_rut"
                   name="rut"
                   data-rut-validator="true"
                   required>
        </div>

        <div class="col">
            <label for="apellido_paterno">Apellido Paterno</label>
            <input type="text" id="apellido_paterno" name="apellido_paterno" required>
        </div>

        <div class="col">
            <label for="nombres">Nombres</label>
            <input type="text" id="nombres" name="nombres" required>
        </div>
    </div>

    <button type="submit">Generar Archivo Previred</button>
</form>
```

### 5. Formulario de Emisión DTE/SII (Factura Electrónica)

```html
<form action="/sii/dte/create" method="POST">
    <div class="form-group">
        <label for="dte_type">Tipo de Documento</label>
        <select id="dte_type" name="dte_type" required>
            <option value="33">33 - Factura Electrónica</option>
            <option value="34">34 - Factura Exenta</option>
            <option value="39">39 - Boleta Electrónica</option>
            <option value="52">52 - Guía de Despacho</option>
        </select>
    </div>

    <div class="form-row">
        <div class="col">
            <label for="rut_emisor" class="required">RUT Emisor</label>
            <input type="text"
                   id="rut_emisor"
                   name="rut_emisor"
                   data-rut-validator="true"
                   required>
        </div>

        <div class="col">
            <label for="rut_receptor" class="required">RUT Receptor</label>
            <input type="text"
                   id="rut_receptor"
                   name="rut_receptor"
                   data-rut-validator="true"
                   required>
        </div>
    </div>

    <button type="submit">Emitir DTE</button>
</form>
```

## 🔍 Validación Backend (PHP)

**IMPORTANTE:** La validación de RUT también debe implementarse en el backend por seguridad.

### Función PHP para validar RUT

```php
<?php
/**
 * Valida un RUT chileno usando el algoritmo Módulo 11
 *
 * @param string $rut RUT a validar (puede incluir formato)
 * @return bool True si es válido, false si no
 */
function validateChileanRUT($rut) {
    // Limpiar RUT (eliminar puntos y guión)
    $rut = strtoupper(preg_replace('/[^0-9kK]/', '', $rut));

    if (strlen($rut) < 2) {
        return false;
    }

    // Separar cuerpo y dígito verificador
    $body = substr($rut, 0, -1);
    $dv = substr($rut, -1);

    // Validar que el cuerpo sea numérico
    if (!is_numeric($body)) {
        return false;
    }

    // Calcular dígito verificador
    $suma = 0;
    $multiplo = 2;

    for ($i = strlen($body) - 1; $i >= 0; $i--) {
        $suma += intval($body[$i]) * $multiplo;
        $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
    }

    $dvEsperado = 11 - ($suma % 11);
    $dvCalculado = $dvEsperado === 11 ? '0' : ($dvEsperado === 10 ? 'K' : strval($dvEsperado));

    return $dv === $dvCalculado;
}

// Ejemplo de uso en un endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tax_id = trim($_POST['tax_id']);
    $country = $_POST['country'] ?? 'CL';

    if ($country === 'CL') {
        if (!validateChileanRUT($tax_id)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'RUT inválido. Verifica el dígito verificador.'
            ]);
            exit;
        }
    }

    // Continuar con el procesamiento...
}
?>
```

### Integración en Modelos (Ejemplo)

```php
<?php
class Employee {
    private $db;

    public function create($data) {
        // Validar RUT antes de insertar
        if (!empty($data['tax_id'])) {
            if (!$this->validateChileanRUT($data['tax_id'])) {
                throw new ValidationException('RUT inválido');
            }

            // Limpiar formato antes de guardar
            $data['tax_id'] = $this->cleanRUT($data['tax_id']);
        }

        // Insertar en base de datos
        return $this->db->insert('hcm_employees', $data);
    }

    private function cleanRUT($rut) {
        return strtoupper(preg_replace('/[^0-9kK]/', '', $rut));
    }

    private function validateChileanRUT($rut) {
        // Implementación del algoritmo Módulo 11
        // (igual que la función global)
    }
}
?>
```

## 📋 Checklist de Implementación

Usa este checklist para asegurar que todos los formularios tengan validación de RUT:

### Módulo: Administración Central (ADM)
- [x] ✅ Registro de empresas (index.php)
- [ ] ⬜ Edición de perfil de empresa
- [ ] ⬜ Gestión de usuarios

### Módulo: Recursos Humanos (HCM)
- [ ] ⬜ Registro de empleados
- [ ] ⬜ Edición de empleados
- [ ] ⬜ Contactos de emergencia
- [ ] ⬜ Exportación Previred
- [ ] ⬜ Reporte de nómina

### Módulo: Ventas (SD)
- [ ] ⬜ Registro de clientes
- [ ] ⬜ Edición de clientes
- [ ] ⬜ Órdenes de venta
- [ ] ⬜ Facturas
- [ ] ⬜ POS - Transacciones

### Módulo: Materiales (MM)
- [ ] ⬜ Registro de proveedores
- [ ] ⬜ Edición de proveedores
- [ ] ⬜ Órdenes de compra

### Módulo: Finanzas (FI)
- [ ] ⬜ Cuentas por cobrar
- [ ] ⬜ Cuentas por pagar
- [ ] ⬜ Asientos contables

### Módulo: CRM
- [ ] ⬜ Registro de leads
- [ ] ⬜ Registro de contactos
- [ ] ⬜ Conversión de leads a clientes

### Módulo: SII (Integración Tributaria)
- [ ] ⬜ Emisión de DTEs (Facturas electrónicas)
- [ ] ⬜ Gestión de certificados digitales
- [ ] ⬜ Libro de compras/ventas
- [ ] ⬜ Declaraciones (F29, F22, F50)

## 🐛 Troubleshooting

### Problema: La validación no se ejecuta

**Solución:**
1. Verificar que los archivos JS y CSS estén incluidos correctamente
2. Verificar la consola del navegador en busca de errores
3. Asegurar que el `data-rut-validator="true"` esté presente
4. Verificar que el input tenga un ID único

### Problema: El RUT se valida pero no se formatea

**Solución:**
1. Asegurar que el país seleccionado sea 'CL'
2. Verificar que el selector de país esté correctamente vinculado con `data-country-selector`
3. Comprobar que no haya conflictos con otros scripts de formateo

### Problema: Validación funciona en frontend pero falla en backend

**Solución:**
1. Implementar la función `validateChileanRUT()` en PHP
2. Asegurar que el formato del RUT se limpie antes de validar
3. Verificar que se esté usando el mismo algoritmo Módulo 11

## 📞 Soporte

Para problemas o preguntas sobre la validación de RUT:

1. Revisar este documento
2. Consultar el código fuente en `/assets/js/rut-validator.js`
3. Verificar ejemplos en `/index.php`
4. Contactar al equipo de desarrollo

---

**Última actualización:** 2024-11-13
**Versión:** 2.0.0
**Autor:** CONECTA ERP Development Team
