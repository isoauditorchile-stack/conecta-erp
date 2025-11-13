# CONECTA ERP v2.0.0 - Sistema de Gestión Empresarial REAL

Sistema ERP profesional diseñado para competir con SAP y Softland. **14 módulos principales** y **106 submódulos especializados**. 100% en Español. Sistema REAL de producción, no es un demo.

## 🚀 Características Principales

- ✅ **14 Módulos Integrados**: Finanzas, Controlling, Ventas, Materiales, Producción, RRHH, SCM, CRM, Fidelización, BI, Facturación Electrónica, Proyectos, Admin, Mobile
- ✅ **106 Submódulos Especializados**: Todos los archivos en español (libro_mayor.php, empleados.php, etc.)
- ✅ **Sistema de Planes**: 4 planes profesionales (Básico, Profesional, Empresarial, Corporativo)
- ✅ **Control Total**: Super Admin único (auditorexchile@gmail.com) con aprobación manual de usuarios
- ✅ **Sistema de Trial**: 14 días de prueba SOLO después de aprobación manual
- ✅ **Notificaciones Automáticas**: Avisos automáticos en día 13, 7 y 1 antes de vencimiento
- ✅ **Validación RUT Inteligente**: Escribe 15895771k y se convierte automáticamente a 15.895.771-k
- ✅ **Multiusuario & Multiempresa**: Sistema multi-tenant completo
- ✅ **Multimoneda**: USD, CLP, EUR, ARS, PEN, COP, MXN, BRL
- ✅ **Multipaís**: Chile, Argentina, Perú, Colombia, México, Brasil, USA, España
- ✅ **Multiidioma REAL**: 8 idiomas funcionales desde base de datos (ES, EN, PT, FR, DE, IT, RU, ZH)
- ✅ **Integraciones Chilenas**: Previred (RRHH) y SII (Facturación Electrónica)
- ✅ **Diseño Profesional**: Interfaz elegante, responsive, con gradientes y CSS moderno
- ✅ **Pagos Integrados**: PayPal + Transferencia Bancaria con verificación manual

## 📋 Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3 o superior
- Apache 2.4 con mod_rewrite (o Nginx)
- Extensiones PHP requeridas:
  - PDO
  - pdo_mysql
  - mbstring
  - openssl
  - json

## 🔧 Instalación

### ⭐ MÉTODO 1: INSTALADOR WEB (RECOMENDADO)

**La forma más fácil y rápida de instalar CONECTA ERP**

1. **Sube el proyecto a tu servidor** (public_html o carpeta web)

2. **Accede al instalador web** en tu navegador:
   ```
   http://tu-dominio.com/install.php
   ```

3. **Sigue los 4 pasos del instalador:**
   - ✅ Paso 1: Verificación de requisitos del sistema
   - ✅ Paso 2: Configuración de base de datos
   - ✅ Paso 3: Instalación automática de tablas
   - ✅ Paso 4: Confirmación y acceso al sistema

4. **¡Listo!** El sistema está instalado y listo para usar

### 📦 MÉTODO 2: SCRIPT DE DEPLOYMENT AUTOMÁTICO

Si tienes acceso SSH:

```bash
# Ejecutar script de deployment
bash deploy_to_public_html.sh
```

Este script automáticamente:
- Hace backup de public_html existente
- Copia el proyecto a public_html
- Configura index.php con sistema de planes
- Establece permisos correctos (755/644)
- Crea directorio de logs
- Verifica estructura completa

Luego accede a `http://tu-dominio.com/install.php` para instalar la base de datos.

### 🛠️ MÉTODO 3: INSTALACIÓN MANUAL

#### 1. Copiar archivos

```bash
# Opción A: Copiar todo el proyecto
cd ~
cp -r /ruta/a/conecta-erp /home/user/public_html

# Opción B: Crear enlace simbólico
ln -s /ruta/a/conecta-erp ~/public_html
```

#### 2. Configurar permisos

```bash
cd ~/public_html

# Permisos para directorios
find . -type d -exec chmod 755 {} \;

# Permisos para archivos
find . -type f -exec chmod 644 {} \;

# Permisos para scripts ejecutables
chmod 755 instalar_sistema_completo.php
chmod 755 cron/verificar_trials_diarios.php
```

#### 3. Instalar base de datos

**Opción A: Usando el instalador web**
```
http://tu-dominio.com/install.php
```

**Opción B: Usando phpMyAdmin**
1. Crea la base de datos `conectae_conectaerpbd`
2. Importa el archivo `database/INSTALAR_PHPMYADMIN.sql`

**Opción C: Usando línea de comandos**
```bash
php instalar_sistema_completo.php
```

## 🎯 Primer Acceso al Sistema

### 1. Acceder a la página principal
```
http://tu-dominio.com/index.php
```

### 2. Registrarse como Super Admin
- **Email**: auditorexchile@gmail.com
- **Contraseña**: La que tu elijas
- Este será el ÚNICO usuario con control total del sistema

### 3. Otros usuarios
- Cualquier otro email que se registre quedará como "Pendiente de Aprobación"
- Solo el super admin puede aprobar usuarios
- Después de aprobación, reciben 14 días de trial
- Notificaciones automáticas en días 13, 7 y 1

### 4. Panel Super Admin
```
http://tu-dominio.com/admin/panel_super_admin.php
```

Desde aquí puedes:
- ✅ Aprobar/Rechazar nuevos usuarios
- ✅ Ver usuarios en trial con días restantes
- ✅ Verificar pagos pendientes
- ✅ Activar/Suspender suscripciones
- ✅ Ver historial completo de actividades

## 💼 Planes Disponibles

| Plan | Precio Mensual | Precio Anual | Usuarios | Empresas | Módulos |
|------|----------------|--------------|----------|----------|---------|
| **Básico** | $49 USD | $490 USD | 5 | 1 | Básicos |
| **Profesional** | $99 USD | $990 USD | 15 | 3 | Todos |
| **Empresarial** | $199 USD | $1,990 USD | 50 | 10 | Todos + BI |
| **Corporativo** | $499 USD | $4,990 USD | Ilimitados | Ilimitadas | Todos + Personalización |

## 📚 Módulos del Sistema (Todos en Español)

### 1. Finanzas (FI) - 11 Submódulos
- 📖 `libro_mayor.php` - Libro Mayor
- 💰 `cuentas_por_cobrar.php` - Cuentas por Cobrar
- 💸 `cuentas_por_pagar.php` - Cuentas por Pagar
- 🏦 `tesoreria.php` - Tesorería
- 🏢 `activos_fijos.php` - Activos Fijos
- 🏦 `bancos_conciliacion.php` - Bancos y Conciliaciones
- 📚 `libros_contables.php` - Libros Contables
- 📊 `ifrs_reporting.php` - IFRS Reporting
- ✍️ `asientos_contables.php` - Asientos Contables
- 🔒 `cierres_contables.php` - Cierres Contables
- 📈 `reportes_financieros.php` - Reportes Financieros

### 2. Controlling (CO) - 8 Submódulos
- 🎯 `centros_costo.php` - Centros de Costo
- 📋 `ordenes_internas.php` - Órdenes Internas
- 📊 `analisis_rentabilidad.php` - Análisis de Rentabilidad
- 💰 `presupuestos.php` - Presupuestos
- 💳 `control_gastos.php` - Control de Gastos
- 🔢 `costos_abc.php` - Costos ABC
- 📈 `analisis_proyectos.php` - Análisis de Proyectos
- 💼 `control_inversiones.php` - Control de Inversiones

### 3. Ventas & Distribución (SD) - 9 Submódulos
- 👥 `clientes.php` - Clientes
- 📝 `cotizaciones.php` - Cotizaciones
- 🛒 `pedidos_venta.php` - Pedidos de Venta
- 🧾 `facturacion.php` - Facturación
- 💳 `punto_venta.php` - Punto de Venta (POS)
- 🛍️ `ecommerce.php` - E-commerce
- 💲 `precios_dinamicos.php` - Precios Dinámicos
- 💰 `comisiones.php` - Comisiones
- 📊 `analisis_ventas.php` - Análisis de Ventas

### 4. Materiales (MM) - 8 Submódulos
- 📦 `productos.php` - Productos
- 🏪 `almacenes.php` - Almacenes
- 📊 `inventario.php` - Inventario
- 🏭 `proveedores.php` - Proveedores
- 🛒 `ordenes_compra.php` - Órdenes de Compra
- 📥 `recepcion_mercancias.php` - Recepción de Mercancías
- 📈 `mrp_planificacion.php` - MRP - Planificación
- 🔍 `trazabilidad.php` - Trazabilidad

### 5. Producción (PP) - 10 Submódulos
- 🏭 `ordenes_produccion.php` - Órdenes de Producción
- 📋 `bom.php` - BOM (Bill of Materials)
- ⚙️ `rutinas_produccion.php` - Rutinas de Producción
- 📊 `mrp2.php` - MRP II
- 📅 `planificacion_capacidades.php` - Planificación de Capacidades
- ✅ `control_calidad.php` - Control de Calidad
- 🔧 `mantenimiento_preventivo.php` - Mantenimiento Preventivo
- 🧪 `formulas_produccion.php` - Fórmulas de Producción
- 💰 `costos_produccion.php` - Costos de Producción
- 🏭 `centros_trabajo.php` - Centros de Trabajo

### 6. Capital Humano (HCM) - 11 Submódulos
- 👤 `empleados.php` - Empleados
- 💰 `nomina.php` - Nómina (con integración Previred)
- 📝 `reclutamiento.php` - Reclutamiento
- 🎯 `onboarding.php` - Onboarding
- ⭐ `evaluacion_desempeno.php` - Evaluación de Desempeño
- 📚 `capacitacion.php` - Capacitación
- 📈 `desarrollo_organizacional.php` - Desarrollo Organizacional
- ⏰ `asistencia.php` - Asistencia
- 🎁 `beneficios.php` - Beneficios
- 🏖️ `vacaciones_permisos.php` - Vacaciones y Permisos
- 📊 `reportes_rrhh.php` - Reportes RRHH

### 7-14. Otros Módulos
- **Supply Chain Management (SCM)** - 10 submódulos
- **CRM** - 9 submódulos
- **Fidelización (LOY)** - 7 submódulos
- **Business Intelligence (BI)** - 8 submódulos
- **Facturación Electrónica (FE)** - 6 submódulos (integración SII Chile)
- **Gestión de Proyectos (PM)** - 8 submódulos
- **Configuración & Admin (ADM)** - 10 submódulos
- **Mobile Apps (MOB)** - 5 submódulos

**Total: 106 submódulos profesionales**

## 🔐 Sistema de Seguridad

- ✅ Super Admin único con control total
- ✅ Aprobación manual de TODOS los usuarios
- ✅ Contraseñas hasheadas con bcrypt
- ✅ Protección CSRF en formularios
- ✅ Sesiones seguras con regeneración periódica
- ✅ Validación y sanitización de datos
- ✅ Logs de auditoría de todas las actividades
- ✅ Control de acceso basado en roles
- ✅ Protección de archivos sensibles vía .htaccess
- ✅ Headers de seguridad (X-Frame-Options, CSP, etc.)

## 🌐 Internacionalización REAL

El sistema tiene traducciones FUNCIONALES en base de datos:
- 🇪🇸 Español (por defecto)
- 🇺🇸 English
- 🇧🇷 Português
- 🇫🇷 Français
- 🇩🇪 Deutsch
- 🇮🇹 Italiano
- 🇷🇺 Русский
- 🇨🇳 中文

Todas las traducciones se cargan desde la tabla `translations` con soporte para variables dinámicas.

## 🆔 Validación RUT/Tax ID por País

Sistema inteligente que detecta el país y valida automáticamente:

| País | Formato | Ejemplo | Validación |
|------|---------|---------|------------|
| 🇨🇱 Chile | XX.XXX.XXX-X | 15.895.771-k | Dígito verificador |
| 🇦🇷 Argentina | XX-XXXXXXXX-X | 20-12345678-3 | CUIT/CUIL |
| 🇵🇪 Perú | XXXXXXXXXXX | 12345678901 | RUC 11 dígitos |
| 🇨🇴 Colombia | XXX.XXX.XXX-X | 900.123.456-7 | NIT con verificación |
| 🇲🇽 México | XXXX-XXXXXX-XXX | AAAA-123456-XXX | RFC |
| 🇧🇷 Brasil | XX.XXX.XXX/XXXX-XX | 12.345.678/0001-90 | CNPJ |
| 🇺🇸 USA | XX-XXXXXXX | 12-3456789 | EIN |
| 🇪🇸 España | X-XXXXXXXX | A-12345678 | CIF/NIF |

**Auto-formato en tiempo real**: El usuario escribe `15895771k` y el sistema muestra automáticamente `15.895.771-k`

## 💰 Monedas y Países Soportados

- 💵 USD - Dólar Americano (USA)
- 💵 CLP - Peso Chileno (Chile)
- 💶 EUR - Euro (España)
- 💵 ARS - Peso Argentino (Argentina)
- 💵 PEN - Sol Peruano (Perú)
- 💵 COP - Peso Colombiano (Colombia)
- 💵 MXN - Peso Mexicano (México)
- 💵 BRL - Real Brasileño (Brasil)

## ⚙️ CRON Jobs (Automatización)

### Verificación de Trials Diarios

Configura este CRON para ejecutarse diariamente:

```bash
# Agregar a crontab -e
0 2 * * * php /home/user/public_html/cron/verificar_trials_diarios.php
```

Este script automáticamente:
- ✅ Verifica días restantes de trial de cada usuario
- ✅ Envía notificación en día 13 (primera alerta)
- ✅ Envía notificación en día 7 (segunda alerta)
- ✅ Envía notificación en día 1 (alerta urgente)
- ✅ Desactiva automáticamente cuentas expiradas
- ✅ Registra todas las acciones en logs

## 📁 Estructura de Directorios

```
conecta-erp/
├── install.php                         ← Instalador web (WordPress-style)
├── index.php                           ← Página principal con planes
├── deploy_to_public_html.sh           ← Script de deployment automático
├── instalar_sistema_completo.php      ← Instalador CLI
├── renombrar_archivos_espanol.php     ← Script para renombrar archivos
├── actualizar_submodulos_espanol.php  ← Actualizar nombres en BD
├── .htaccess                           ← Configuración Apache segura
│
├── admin/                              ← Panel de administración
│   ├── dashboard_admin.php            ← Dashboard admin
│   └── panel_super_admin.php          ← Panel EXCLUSIVO super admin
│
├── user/                               ← Panel de usuario
│   └── dashboard_user.php             ← Dashboard usuario
│
├── modules/                            ← TODOS los módulos (en español)
│   ├── fi/                            ← Finanzas (11 submódulos)
│   ├── co/                            ← Controlling (8 submódulos)
│   ├── sd/                            ← Ventas (9 submódulos)
│   ├── mm/                            ← Materiales (8 submódulos)
│   ├── pp/                            ← Producción (10 submódulos)
│   ├── hcm/                           ← RRHH (11 submódulos)
│   ├── scm/                           ← Supply Chain (10 submódulos)
│   ├── crm/                           ← CRM (9 submódulos)
│   ├── loy/                           ← Fidelización (7 submódulos)
│   ├── bi/                            ← Business Intelligence (8 submódulos)
│   ├── fe/                            ← Facturación Electrónica (6 submódulos)
│   ├── pm/                            ← Proyectos (8 submódulos)
│   ├── adm/                           ← Admin (10 submódulos)
│   └── mob/                           ← Mobile (5 submódulos)
│
├── includes/                           ← Configuración y utilidades
│   ├── config.php                     ← Configuración BD (generado por instalador)
│   ├── i18n.php                       ← Sistema de internacionalización
│   └── modal_registro_completo.php    ← Modal de registro con planes
│
├── assets/                             ← Recursos estáticos
│   ├── css/
│   │   ├── global.css                 ← Estilos globales profesionales
│   │   └── forms-enhanced.css         ← Estilos de formularios elegantes
│   └── js/
│       └── tax-id-validator.js        ← Validación RUT/Tax ID inteligente
│
├── database/                           ← Scripts SQL
│   ├── INSTALAR_PHPMYADMIN.sql        ← SQL limpio para phpMyAdmin
│   ├── schema_completo_base.sql       ← Schema base completo
│   ├── schema_planes_pagos.sql        ← Planes, suscripciones, pagos
│   └── schema_improvements.sql         ← Mejoras multipaís/multiempresa
│
├── cron/                               ← Scripts CRON
│   └── verificar_trials_diarios.php   ← Verificación automática de trials
│
└── logs/                               ← Logs del sistema
    └── php_errors.log                 ← Log de errores PHP
```

## 🔒 Archivos Protegidos

El archivo `.htaccess` protege automáticamente:
- ❌ `config.php` - Credenciales de base de datos
- ❌ `install.php` - Instalador (desactivar después de instalar)
- ❌ `instalar_sistema_completo.php` - Script de instalación
- ❌ `renombrar_archivos_espanol.php` - Script de renombrado
- ❌ `database/` - Directorio de SQL
- ❌ `.env` - Variables de entorno

## 🐛 Solución de Problemas

### Error de conexión a base de datos
Verifica las credenciales en `includes/config.php` o vuelve a ejecutar el instalador web.

### Página en blanco
Revisa los logs en `logs/php_errors.log` o activa el reporte de errores:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Los módulos no aparecen
Ejecuta el instalador web nuevamente o verifica que todas las tablas estén creadas.

### RUT no se formatea automáticamente
Verifica que `assets/js/tax-id-validator.js` esté cargado correctamente en el formulario.

### Notificaciones de trial no se envían
Configura el CRON job para ejecutar `cron/verificar_trials_diarios.php` diariamente.

## 📞 Soporte y Contacto

- **Email**: auditorexchile@gmail.com
- **Website**: https://conectaerp.com
- **Sistema**: CONECTA ERP v2.0.0

## 📄 Licencia

© 2024 CONECTA ERP. Sistema propietario de producción REAL.

Este es un sistema profesional diseñado para competir con SAP y Softland. NO es un sistema de prueba.

## 🎯 Sistema de Producción REAL

Este ERP fue diseñado con las siguientes características de producción:

✅ **Control Total del Super Admin**
- Un solo usuario tiene control absoluto: auditorexchile@gmail.com
- Aprobación manual de TODOS los usuarios nuevos
- Verificación manual de TODOS los pagos
- Panel exclusivo con estadísticas completas

✅ **Sistema de Trial Controlado**
- NO hay trial automático para nadie
- Trial de 14 días SOLO después de aprobación manual
- Notificaciones automáticas programadas
- Desactivación automática al vencer

✅ **Sistema de Pagos Real**
- Integración con PayPal
- Opción de transferencia bancaria
- Verificación manual de pagos
- Historial completo de transacciones

✅ **Arquitectura Empresarial**
- Multi-tenant (múltiples empresas)
- Multi-usuario con roles granulares
- Multi-país con configuraciones específicas
- Multi-moneda con conversión automática
- Multi-idioma REAL desde base de datos

✅ **Integraciones Reales**
- Previred (Chile) - Sistema de nómina y previsión
- SII (Chile) - Facturación electrónica
- Más integraciones próximamente

## 🚀 Roadmap Futuro

- [ ] API REST completa con autenticación JWT
- [ ] Aplicaciones móviles nativas (iOS y Android)
- [ ] Integración con bancos chilenos (BancoEstado, Santander, etc.)
- [ ] Módulo de IA para predicciones financieras
- [ ] Blockchain para trazabilidad de documentos
- [ ] Integración con marketplaces (Mercado Libre, Amazon)
- [ ] Sistema de facturación electrónica multi-país
- [ ] Dashboard ejecutivo con KPIs en tiempo real
- [ ] Sistema de workflows personalizables
- [ ] Motor de reglas de negocio configurable

---

**🇨🇱 Hecho con excelencia en Chile**

**CONECTA ERP v2.0.0 - Sistema de Producción REAL**
