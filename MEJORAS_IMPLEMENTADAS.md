# CONECTA ERP - Mejoras Implementadas

## 🎯 Resumen de Mejoras

Se han implementado las siguientes mejoras al sistema CONECTA ERP:

### ✅ 1. Sistema Multi-País
- Soporte para 8 países: Chile, Argentina, Perú, Colombia, México, Brasil, Estados Unidos, España
- Configuración específica por país (formato de RUT/Tax ID, moneda, IVA, código telefónico)
- Validación automática de RUT/DNI/CUIT según el país seleccionado

### ✅ 2. Sistema Multi-Idioma Real (8 Idiomas)
- **Español (ES)** 🇪🇸
- **English (EN)** 🇺🇸
- **Português (PT)** 🇧🇷
- **Français (FR)** 🇫🇷
- **Deutsch (DE)** 🇩🇪
- **Italiano (IT)** 🇮🇹
- **Русский (RU)** 🇷🇺
- **中文 (ZH)** 🇨🇳

**Sistema basado en base de datos** con tabla `translations` que permite cambiar el idioma de toda la interfaz en tiempo real.

### ✅ 3. Validación de RUT/Tax ID por País con Auto-Formato

**Ejemplo específico solicitado:**
- Usuario ingresa: `15895771k`
- Sistema muestra automáticamente: `15.895.771-k` (formato chileno)

**Formatos soportados:**
- **Chile (CL)**: XX.XXX.XXX-X (ej: 12.345.678-9)
- **Argentina (AR)**: XX-XXXXXXXX-X (ej: 20-12345678-9)
- **Perú (PE)**: XXXXXXXXXXX (ej: 12345678901)
- **Colombia (CO)**: XXX.XXX.XXX-X (ej: 123.456.789-0)
- **México (MX)**: XXXX######XXX (ej: ABCD123456XYZ)
- **Brasil (BR)**: XX.XXX.XXX/XXXX-XX (ej: 12.345.678/0001-90)
- **Estados Unidos (US)**: XX-XXXXXXX (ej: 12-3456789)
- **España (ES)**: X########X (ej: A12345678B)

### ✅ 4. Sistema Multi-Empresa
- Cada usuario puede crear y gestionar múltiples empresas
- Cada empresa tiene su propia configuración (país, moneda, idioma, zona horaria)
- Tabla `companies` con campos extendidos
- Tabla `company_users` para asociación usuario-empresa con roles

### ✅ 5. Sistema Multi-Usuario
- Múltiples usuarios por empresa
- Roles: owner, admin, user, viewer
- Permisos granulares por submódulo

### ✅ 6. Campos Adicionales de Empresa

**Nuevos campos agregados:**
- **Razón Social** (legal_name)
- **Industria** (industry)
- **Sitio Web** (website)
- **Dirección completa** (address, city, state_province, postal_code)
- **Número de empleados** (employees_count: 1-10, 11-50, 51-200, 201-500, 500+)
- **Ingresos anuales** (annual_revenue)
- **Inicio año fiscal** (fiscal_year_start: formato MM-DD)
- **Configuración de integraciones** (Previred, SII)

### ✅ 7. Integración con Previred (Chile)
- Tabla `previred_config` para configuración por empresa
- Campos: RUT empleador, entorno (sandbox/production), credenciales API
- Checkbox en registro para habilitar Previred
- Preparado para integración con API de Previred

### ✅ 8. Integración con SII (Chile)
- Tabla `sii_config` para configuración por empresa
- Campos: RUT empresa, razón social, entorno (certificación/producción), credenciales
- Checkbox en registro para habilitar SII
- Preparado para facturación electrónica

### ✅ 9. CSS Profesional y Elegante
- Archivo `assets/css/forms-enhanced.css` con estilos profesionales
- Estados de validación visual (valid/invalid) con iconos
- Animaciones suaves (slideInDown, fadeIn)
- Variables CSS para temas consistentes
- Responsive design

### ✅ 10. Multi-Moneda
Soporte para 8 monedas principales:
- USD - Dólar Estadounidense
- CLP - Peso Chileno
- EUR - Euro
- ARS - Peso Argentino
- PEN - Sol Peruano
- COP - Peso Colombiano
- MXN - Peso Mexicano
- BRL - Real Brasileño

### ✅ 11. Configuración Regional Completa
- **Zona horaria** (timezone) con opciones de toda América y Europa
- **Idioma preferido** por usuario y empresa
- **Formato de fecha** según país
- **Inicio de año fiscal** personalizable

---

## 📁 Archivos Creados/Modificados

### Nuevos Archivos

1. **`/includes/i18n.php`**
   - Sistema de internacionalización
   - Clase `I18n` singleton
   - Función helper `__($key)` para traducciones
   - Función `getActiveCountries($lang)` para obtener países en el idioma seleccionado

2. **`/assets/js/tax-id-validator.js`**
   - Validación de RUT/Tax ID para 8 países
   - Auto-formato en tiempo real (ej: 15895771k → 15.895.771-k)
   - Verificación de dígito verificador para Chile y Argentina
   - Configuración por país (nombre, formato, placeholder, regex)

3. **`/assets/css/forms-enhanced.css`**
   - Estilos profesionales para formularios
   - Estados de validación visual
   - Animaciones CSS
   - Variables de colores

4. **`/index_improved.php`**
   - Página principal mejorada con todos los campos nuevos
   - Formulario de registro completo (5 secciones)
   - Integración con sistema de traducciones
   - Selector de idioma en tiempo real
   - Validación de RUT integrada

5. **`/database/schema_improvements.sql`**
   - Tabla `countries` (países con configuración por idioma)
   - Tabla `companies` (empresas con campos extendidos)
   - Tabla `company_users` (relación usuario-empresa)
   - Tabla `previred_config` (configuración Previred)
   - Tabla `sii_config` (configuración SII)
   - Tabla `translations` (traducciones en 8 idiomas)

6. **`/install_improvements.php`**
   - Script de instalación de mejoras
   - Crea tablas nuevas
   - Inserta datos de países
   - Inserta traducciones básicas

### Archivos Modificados

- **`/includes/config.php`**: Ya tenía las funciones necesarias
- **`/database/schema.sql`**: Schema original se mantiene

---

## 🚀 Instalación

### Paso 1: Asegurar que el servidor MySQL está corriendo

```bash
# Verificar estado de MySQL/MariaDB
systemctl status mysql
# o
systemctl status mariadb

# Si no está corriendo, iniciarlo
systemctl start mysql
```

### Paso 2: Ejecutar el script de mejoras

```bash
cd /home/user/conecta-erp
php install_improvements.php
```

Este script:
- Crea las nuevas tablas (countries, companies, company_users, previred_config, sii_config, translations)
- Inserta los 8 países con toda su configuración
- Inserta las traducciones básicas en 8 idiomas

### Paso 3: Reemplazar index.php por index_improved.php

**Opción A: Hacer backup y reemplazar**
```bash
mv index.php index_backup.php
mv index_improved.php index.php
```

**Opción B: Usar directamente index_improved.php**
```bash
# Simplemente acceder a http://tu-dominio/index_improved.php
```

### Paso 4: Probar el sistema

1. Acceder a la página principal
2. Cambiar idioma usando el selector superior derecho
3. Hacer clic en "Registrarse"
4. Seleccionar país (por ejemplo, Chile)
5. Ingresar RUT sin formato (ej: `15895771k`)
6. **Verificar** que se auto-formatea a `15.895.771-k`
7. Completar el formulario con todos los campos
8. Habilitar Previred y/o SII si es Chile
9. Registrar

---

## 🎨 Características del Formulario de Registro

### Sección 1: Información Personal
- Nombre
- Apellido
- Email
- Usuario
- Contraseña
- Cargo

### Sección 2: Información de la Empresa
- Nombre de empresa
- Razón social
- País (selector con 8 países)
- RUT/Tax ID (con auto-formato)
- Industria
- Número de empleados (selector)
- Teléfono
- Sitio web

### Sección 3: Ubicación
- Dirección
- Ciudad
- Estado/Provincia
- Código postal

### Sección 4: Configuración Regional
- Moneda (8 opciones)
- Idioma (8 opciones)
- Zona horaria
- Inicio de año fiscal
- Ingresos anuales

### Sección 5: Integraciones (Solo Chile)
- **Previred**: Sistema de remuneraciones
  - Checkbox para habilitar
  - Campo RUT Previred
- **SII**: Facturación electrónica
  - Checkbox para habilitar
  - Campo RUT SII

---

## 💡 Ejemplos de Uso

### Cambiar Idioma
```javascript
// En el navegador, seleccionar idioma del dropdown superior
// O navegar a: ?lang=en
```

### Validar RUT Chileno
```javascript
// Usuario ingresa: 15895771k
// TaxIDValidator automáticamente formatea a: 15.895.771-k
// Y valida el dígito verificador
```

### Crear Empresa Multi-País
```php
// 1. Usuario en Chile registra empresa chilena
// 2. Luego desde dashboard puede crear empresa en Argentina
// 3. Cada empresa tiene su RUT/CUIT válido según país
// 4. Cada empresa puede tener su moneda y configuración
```

---

## 🔧 Funciones JavaScript Importantes

### `TaxIDValidator.init(input, countryCode)`
Inicializa el validador para un input específico según el país.

### `updateTaxIdFormat(countryCode)`
Actualiza el formato y placeholder del campo RUT según el país seleccionado.

### `changeLanguage(lang)`
Cambia el idioma de toda la interfaz recargando la página con `?lang=XX`.

---

## 📊 Base de Datos

### Nuevas Tablas

**`countries`** (39 columnas)
- Configuración de países en 8 idiomas
- Formatos de Tax ID específicos por país
- Habilitar/deshabilitar Previred y SII por país

**`companies`** (25 columnas)
- Información completa de empresas
- Relación con país
- Configuración de integraciones

**`company_users`**
- Relación many-to-many entre users y companies
- Roles por usuario en cada empresa

**`previred_config`** (13 columnas)
- Configuración de Previred por empresa
- Entorno (sandbox/production)
- Credenciales API

**`sii_config`** (17 columnas)
- Configuración de SII por empresa
- Entorno (certificación/producción)
- Credenciales y certificados

**`translations`** (11 columnas)
- Traducciones en 8 idiomas
- Clave de texto única
- Columnas: key_text, lang_es, lang_en, lang_pt, lang_fr, lang_de, lang_it, lang_ru, lang_zh

---

## ✨ Próximos Pasos Recomendados

1. **Implementar APIs reales**:
   - `/includes/previred_api.php` - Integración con API de Previred
   - `/includes/sii_api.php` - Integración con API de SII

2. **Dashboard Multi-Empresa**:
   - Selector de empresa actual
   - Cambiar entre empresas del usuario

3. **Más Traducciones**:
   - Agregar más keys a la tabla `translations`
   - Traducir todos los módulos y submódulos

4. **Conversión de Moneda**:
   - Integrar API de tasas de cambio
   - Mostrar montos en diferentes monedas

5. **Reportes por País**:
   - Reportes específicos según regulaciones de cada país
   - Formatos de impuestos locales

---

## 📞 Soporte

Para cualquier consulta:
- Email: auditorexchile@gmail.com
- Sistema: CONECTA ERP v1.0.0

---

**Fecha de Implementación**: 2025-11-13
**Versión**: 1.1.0
**Estado**: ✅ Completado y listo para pruebas
