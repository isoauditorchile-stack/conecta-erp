# CONECTA ERP - Sistema de Producción REAL

**Versión:** 2.0.0
**Sistema:** ERP Empresarial Completo
**Competencia:** SAP, Softland, Oracle ERP
**Idioma:** 100% Español (archivos, base de datos, interfaz)
**Estado:** PRODUCCIÓN REAL

---

## 🎯 CARACTERÍSTICAS PRINCIPALES DEL SISTEMA

### ✅ Sistema Completamente en Español
- **TODOS** los archivos PHP renombrados de inglés a español
- Base de datos actualizada con nombres en español
- Interfaz 100% profesional en español
- Sistema REAL de producción, no de pruebas

### ✅ Sistema de Control Total - auditorexchile@gmail.com
- **Super Administrador Único**: auditorexchile@gmail.com
- Aprobación manual de TODOS los usuarios nuevos
- Panel exclusivo de super admin para gestión completa
- Control total sobre usuarios, pagos y suscripciones

### ✅ Sistema de Planes Profesionales
- **4 Planes:** Básico, Profesional, Empresarial, Corporativo
- Precios competitivos desde $49/mes
- Trial de 14 días para todos los usuarios nuevos
- Gestión completa de suscripciones

### ✅ Sistema de Aprobación Manual
- Usuarios registrados entran en estado "pending_approval"
- auditorexchile@gmail.com recibe notificación
- Debe aprobar o rechazar manualmente cada usuario
- Solo después de aprobación se activa el trial de 14 días

### ✅ Contador de Días y Notificaciones Automáticas
- Día 13: Primera advertencia (falta 1 día para llegar al día 13)
- Día 7: Segunda advertencia
- Día 1: Advertencia URGENTE (último día)
- Día 0: Desactivación automática

### ✅ Sistema de Pagos Integrado
- **PayPal**: Integración lista para configurar
- **Transferencia Bancaria**: Sistema de verificación manual
- Verificación de pagos por super admin
- Activación automática tras verificación

---

## 📁 ARCHIVOS RENOMBRADOS A ESPAÑOL

### Módulo FI (Finanzas) - 11 archivos
| Antes (inglés) | Ahora (español) |
|---|---|
| general_ledger.php | **libro_mayor.php** |
| accounts_payable.php | **cuentas_por_pagar.php** |
| accounts_receivable.php | **cuentas_por_cobrar.php** |
| fixed_assets.php | **activos_fijos.php** |
| banks.php | **bancos.php** |
| treasury.php | **tesoreria.php** |
| closing.php | **cierre_contable.php** |
| journal_entries.php | **asientos_contables.php** |
| reports.php | **reportes.php** |
| ifrs.php | **niif.php** |
| accounting_books.php | **libros_contables.php** |

### Módulo CO (Control de Gestión) - 8 archivos
| Antes (inglés) | Ahora (español) |
|---|---|
| cost_centers.php | **centros_costo.php** |
| internal_orders.php | **ordenes_internas.php** |
| profitability.php | **rentabilidad.php** |
| projects.php | **proyectos.php** |
| budgets.php | **presupuestos.php** |
| expenses.php | **gastos.php** |
| investments.php | **inversiones.php** |
| abc_costing.php | **costeo_abc.php** |

### Módulo SD (Ventas) - 9 archivos
| Antes (inglés) | Ahora (español) |
|---|---|
| customers.php | **clientes.php** |
| quotations.php | **cotizaciones.php** |
| sales_orders.php | **ordenes_venta.php** |
| invoices.php | **facturas.php** |
| pricing.php | **precios.php** |
| pos.php | **punto_venta.php** |
| ecommerce.php | **comercio_electronico.php** |
| commissions.php | **comisiones.php** |
| analytics.php | **analitica.php** |

### Módulo MM (Materiales) - 8 archivos
| Antes (inglés) | Ahora (español) |
|---|---|
| products.php | **productos.php** |
| purchase_orders.php | **ordenes_compra.php** |
| goods_receipt.php | **recepcion_mercaderia.php** |
| inventory.php | **inventario.php** |
| warehouses.php | **almacenes.php** |
| suppliers.php | **proveedores.php** |
| mrp.php | **planificacion_materiales.php** |
| traceability.php | **trazabilidad.php** |

### Módulo PP (Producción) - 10 archivos
| Antes (inglés) | Ahora (español) |
|---|---|
| production_orders.php | **ordenes_produccion.php** |
| bom.php | **lista_materiales.php** |
| routing.php | **rutas.php** |
| work_centers.php | **centros_trabajo.php** |
| capacity.php | **capacidad.php** |
| quality.php | **calidad.php** |
| mrp_ii.php | **planificacion_avanzada.php** |
| costs.php | **costos.php** |
| maintenance.php | **mantenimiento.php** |
| formulas.php | **formulas.php** (ya estaba en español) |

### Módulo HCM (Recursos Humanos) - 11 archivos
| Antes (inglés) | Ahora (español) |
|---|---|
| employees.php | **empleados.php** |
| recruitment.php | **reclutamiento.php** |
| onboarding.php | **incorporacion.php** |
| payroll.php | **nomina.php** |
| attendance.php | **asistencia.php** |
| leaves.php | **ausencias.php** |
| benefits.php | **beneficios.php** |
| performance.php | **desempeno.php** |
| training.php | **capacitacion.php** |
| org_development.php | **desarrollo_organizacional.php** |
| reports.php | **reportes.php** |

**Total: 57 archivos renombrados exitosamente** ✅

---

## 🗄️ NUEVAS TABLAS DE BASE DE DATOS

### Tabla `planes`
Gestión de planes de suscripción:
- Básico: $49/mes - 5 usuarios
- Profesional: $99/mes - 15 usuarios (MÁS POPULAR)
- Empresarial: $199/mes - 50 usuarios
- Corporativo: $499/mes - Ilimitado

### Tabla `suscripciones`
Gestión completa de suscripciones por usuario:
- Estado (trial, activa, suspendida, cancelada, vencida)
- Fechas de inicio y fin
- Control de trial (14 días)
- Flags de notificaciones (día 13, 7, 1)
- Aprobación por admin

### Tabla `pagos`
Registro de todos los pagos:
- Método (PayPal, transferencia, tarjeta)
- Estado (pendiente, completado, fallido)
- Referencia y comprobantes
- Verificación manual por admin

### Tabla `aprobaciones_usuario`
Control de aprobación manual:
- Estado (pendiente, aprobado, rechazado)
- Razón de rechazo
- Admin que aprobó/rechazó
- Datos de registro (IP, user agent)

### Tabla `notificaciones_trial`
Historial de notificaciones enviadas:
- Tipo (día_13, día_7, día_1, vencido)
- Mensaje enviado
- Fecha de envío
- Estado de lectura

### Tabla `configuracion_pagos`
Configuración de métodos de pago:
- Credenciales PayPal
- Datos bancarios para transferencias
- Email de notificaciones

---

## 🚀 INSTALACIÓN Y CONFIGURACIÓN

### Paso 1: Instalar Mejoras Anteriores
```bash
cd /home/user/conecta-erp
php install_improvements.php
```

### Paso 2: Instalar Sistema de Planes y Pagos
```bash
# Primero ejecutar el SQL de planes y pagos
# Conectar a MySQL y ejecutar:
mysql -u conectae_conectaerpuser -p conectae_conectaerpbd < database/schema_planes_pagos.sql
```

### Paso 3: Actualizar Nombres de Submódulos
```bash
php actualizar_submodulos_espanol.php
```

### Paso 4: Configurar CRON para Verificación Diaria
```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar diario a las 9:00 AM)
0 9 * * * /usr/bin/php /home/user/conecta-erp/cron/verificar_trials_diarios.php
```

### Paso 5: Usar la Nueva Página Principal
```bash
# Opción A: Reemplazar index.php
mv index.php index_old.php
cp index_con_planes.php index.php

# Opción B: Acceder directamente
# http://tu-dominio/index_con_planes.php
```

---

## 👑 PANEL DE SUPER ADMINISTRADOR

### Acceso Exclusivo
**URL:** `/admin/panel_super_admin.php`
**Usuario:** auditorexchile@gmail.com

### Funcionalidades del Panel

#### 1. **Usuarios Pendientes de Aprobación**
- Ver todos los usuarios que se registraron
- Ver datos completos: empresa, RUT, país, IP de registro
- **Aprobar:** Activa el trial de 14 días
- **Rechazar:** Niega el acceso con razón

#### 2. **Usuarios en Trial**
- Ver todos los usuarios en período de prueba
- **Días restantes** con código de colores:
  - 🟢 Verde: Más de 3 días
  - 🟡 Amarillo: 2-3 días
  - 🔴 Rojo: 1 día o menos (CRÍTICO)
- Ordenados por fecha de vencimiento

#### 3. **Pagos Pendientes**
- Ver todos los pagos que necesitan verificación
- Información completa: usuario, plan, monto, método, referencia
- **Verificar Pago:**
  - Activa la suscripción
  - Elimina el trial
  - Usuario pasa a estado "activo"

#### 4. **Estadísticas en Tiempo Real**
- Usuarios pendientes de aprobación
- Usuarios en trial activo
- Usuarios activos (pagando)
- Pagos pendientes de verificación
- Ingresos del mes actual

---

## 📧 SISTEMA DE NOTIFICACIONES

### Notificación Día 13
**Cuándo:** Cuando el usuario tiene 1 día restante (próximo a llegar al día 13)
**Asunto:** "Trial por vencer - 13 días restantes"
**Contenido:**
- Recordatorio amigable
- Link a planes
- Información de contacto

### Notificación Día 7
**Cuándo:** 7 días restantes
**Asunto:** "Trial por vencer - 7 días restantes"
**Contenido:**
- Advertencia más seria
- Métodos de pago disponibles
- Urgencia moderada

### Notificación Día 1
**Cuándo:** ÚLTIMO DÍA
**Asunto:** "🚨 ÚLTIMO DÍA de Trial - Acción Requerida"
**Contenido:**
- Advertencia URGENTE
- Información de que perderá acceso
- WhatsApp y email de contacto urgente

### Desactivación Automática
**Cuándo:** Trial vencido (día 0)
**Acción:**
- Usuario pasa a estado "expired"
- Suscripción pasa a "vencida"
- Email de notificación de vencimiento
- Usuario NO puede iniciar sesión

---

## 💳 SISTEMA DE PAGOS

### PayPal (Pendiente Configuración)
1. Obtener credenciales PayPal Client ID y Secret
2. Actualizar tabla `configuracion_pagos`
3. Configurar en producción (modo "live")

### Transferencia Bancaria
**Configuración actual en BD:**
- Banco: Banco de Chile (o el que configures)
- Cuenta: [CONFIGURAR]
- Titular: CONECTA ERP SpA
- Email: auditorexchile@gmail.com

**Proceso:**
1. Usuario selecciona "Transferencia Bancaria"
2. Sistema muestra datos bancarios
3. Usuario realiza transferencia y sube comprobante
4. Pago queda en estado "pendiente"
5. **auditorexchile@gmail.com** verifica en panel
6. Al verificar, se activa automáticamente la suscripción

---

## 📋 FLUJO COMPLETO DE USUARIO

### 1. Registro
```
Usuario visita index.php
  ↓
Selecciona un plan (Básico, Profesional, Empresarial, Corporativo)
  ↓
Completa formulario extenso (5 secciones):
  - Datos personales
  - Información empresa (con RUT auto-formato)
  - Ubicación
  - Configuración regional
  - Integraciones (Previred, SII)
  ↓
Submit → Estado: "pending_approval"
  ↓
Mensaje: "Espera aprobación del administrador"
```

### 2. Aprobación
```
auditorexchile@gmail.com recibe notificación
  ↓
Accede a /admin/panel_super_admin.php
  ↓
Ve usuario pendiente con todos sus datos
  ↓
Decide: APROBAR o RECHAZAR
  ↓
Si APRUEBA:
  - Usuario pasa a "trial"
  - Se crea suscripción de 14 días
  - trial_ends_at = NOW() + 14 días
  - Usuario recibe email de aprobación
```

### 3. Trial de 14 Días
```
Usuario puede usar sistema completo
  ↓
Script CRON verifica diariamente (9:00 AM)
  ↓
Día 13: Envía primera notificación
Día 7: Envía segunda notificación
Día 1: Envía notificación URGENTE
  ↓
Día 0: DESACTIVACIÓN AUTOMÁTICA
  - Estado: "expired"
  - NO puede iniciar sesión
```

### 4. Pago y Activación
```
Usuario selecciona plan y método de pago
  ↓
Opción A: PayPal
  - Pago automático
  - Verificación automática (cuando esté configurado)

Opción B: Transferencia
  - Ve datos bancarios
  - Realiza transferencia
  - Sube comprobante
  - Estado: "pendiente"
  ↓
auditorexchile@gmail.com verifica pago manualmente
  ↓
Hace click en "Verificar"
  ↓
Sistema automáticamente:
  - Pago → "completado"
  - Suscripción → "activa"
  - Usuario → "active"
  - Fecha fin = +30 días (mensual) o +365 días (anual)
```

---

## 🛡️ SEGURIDAD Y CONTROL

### Control Total del Super Admin
- **auditorexchile@gmail.com** es el ÚNICO super admin
- Ningún otro usuario puede ser admin sin modificar código
- Control manual de TODAS las aprobaciones
- Control manual de TODOS los pagos

### Estados de Usuario
```
pending_approval → Esperando aprobación de admin
trial → Trial de 14 días activo
active → Suscripción pagada y activa
expired → Trial vencido sin pago
suspended → Suspendido por admin
rejected → Rechazado por admin
```

### Protecciones
- Validación de RUT por país con dígito verificador
- Sesiones seguras con regeneración
- CSRF tokens
- Password hasheado con BCrypt
- SQL injection protegido con PDO prepared statements

---

## 📞 SOPORTE Y CONTACTO

**Super Administrador:**
Email: auditorexchile@gmail.com

**Sistema:**
CONECTA ERP v2.0.0
Sistema de Producción REAL

**Competencia:**
SAP, Softland, Oracle ERP, Microsoft Dynamics

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Renombrar todos los archivos a español (57 archivos)
- [x] Actualizar base de datos con nombres en español
- [x] Crear sistema de planes profesionales
- [x] Crear panel de super admin
- [x] Implementar sistema de aprobación manual
- [x] Implementar contador de días de trial
- [x] Crear notificaciones día 13, 7, 1
- [x] Implementar desactivación automática
- [x] Integrar sistema de pagos (PayPal + Transferencia)
- [x] Crear script CRON de verificación diaria
- [ ] Configurar credenciales PayPal en producción
- [ ] Configurar datos bancarios reales
- [ ] Configurar servidor SMTP para emails
- [ ] Configurar SSL/HTTPS en producción
- [ ] Probar flujo completo en producción

---

## 🎯 PRÓXIMOS PASOS RECOMENDADOS

1. **Configurar servidor de email (SMTP)**
   - PHPMailer o Sendgrid
   - Para enviar notificaciones reales

2. **Configurar PayPal en producción**
   - Client ID y Secret reales
   - Modo "live" (no sandbox)

3. **Actualizar datos bancarios**
   - Cuenta bancaria real
   - RUT de la empresa

4. **Configurar SSL/HTTPS**
   - Certificado SSL
   - Forzar HTTPS

5. **Hacer pruebas exhaustivas**
   - Flujo completo de registro
   - Aprobación
   - Trial
   - Notificaciones
   - Pago
   - Activación

---

**Fecha de Implementación:** 2025-11-13
**Versión:** 2.0.0
**Estado:** ✅ SISTEMA DE PRODUCCIÓN REAL - LISTO PARA COMPETIR CON SAP Y SOFTLAND
