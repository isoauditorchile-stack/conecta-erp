# 📘 MANUAL COMPLETO DE USUARIO Y ADMINISTRADOR
# CONECTA ERP - SISTEMA EMPRESARIAL NIVEL SAP/SOFTLAND

## 🔒 DOCUMENTO CONFIDENCIAL - USO EXCLUSIVO ADMINISTRADORES
### NO DISTRIBUIR A USUARIOS FINALES

---

**Versión**: 1.0
**Fecha**: Noviembre 2025
**Autor**: Equipo Técnico CONECTA ERP
**Sistema**: CONECTA ERP - 107 Submódulos Completos
**Clasificación**: **CONFIDENCIAL**

---

## 📑 ÍNDICE COMPLETO

### PARTE 1: INTRODUCCIÓN Y ARQUITECTURA
1. [Visión General del Sistema](#vision-general)
2. [Arquitectura Técnica](#arquitectura)
3. [Acceso al Sistema](#acceso-sistema)
4. [Navegación General](#navegacion)

### PARTE 2: MÓDULOS COMPLETOS (17 MÓDULOS - 107 SUBMÓDULOS)

#### MÓDULO 1: ADMINISTRACIÓN CENTRAL
5. [Gestión de Usuarios](#mod1-usuarios)
6. [Gestión de Empresas](#mod1-empresas)
7. [Auditoría del Sistema](#mod1-auditoria)

#### MÓDULO 2: GESTIÓN DE ENTIDADES
8. [Gestión de Clientes](#mod2-clientes)
9. [Gestión de Proveedores](#mod2-proveedores)
10. [Productos y Servicios](#mod2-productos)
11. [Categorías](#mod2-categorias)
12. [Empleados](#mod2-empleados)

#### MÓDULO 3: FINANZAS (FI)
13. [Comprobantes y Facturas](#mod3-facturas)
14. [Cuentas por Cobrar](#mod3-cobrar)
15. [Cuentas por Pagar](#mod3-pagar)
16. [Tesorería](#mod3-tesoreria)
17. [Cuentas Bancarias](#mod3-bancos)
18. [Conciliación Bancaria](#mod3-conciliacion)
19. [Presupuesto](#mod3-presupuesto)
20. [Flujo de Caja](#mod3-flujo)
21. [Activos Fijos](#mod3-activos)
22. [Centro de Costos](#mod3-centros)
23. [Dashboard Finanzas](#mod3-dashboard)

#### MÓDULO 4: CONTROLLING (CO)
24. [Dashboard Controlling](#mod4-dashboard)
25. [Análisis de Costos](#mod4-costos)
26. [Análisis de Rentabilidad](#mod4-rentabilidad)
27. [Presupuesto vs Real](#mod4-presupuesto)
28. [Márgenes de Contribución](#mod4-margenes)
29. [Análisis de Variaciones](#mod4-variaciones)
30. [KPIs Financieros](#mod4-kpis)
31. [Simulación de Escenarios](#mod4-escenarios)

#### MÓDULO 5: VENTAS (SD)
32. [Cotizaciones](#mod5-cotizaciones)
33. [Pedidos de Venta](#mod5-pedidos)
34. [Órdenes de Venta](#mod5-ordenes)
35. [Facturación](#mod5-facturacion)
36. [Devoluciones](#mod5-devoluciones)
37. [Dashboard Ventas](#mod5-dashboard)
38. [Análisis de Ventas](#mod5-analisis)
39. [Comisiones](#mod5-comisiones)
40. [Metas de Vendedores](#mod5-metas)

#### MÓDULO 6: MATERIALES (MM)
41. [Inventario](#mod6-inventario)
42. [Movimientos de Inventario](#mod6-movimientos)
43. [Órdenes de Compra](#mod6-ordenes)
44. [Recepciones](#mod6-recepciones)
45. [Proveedores](#mod6-proveedores)
46. [Valorización de Inventario](#mod6-valorizacion)
47. [Trazabilidad](#mod6-trazabilidad)
48. [Dashboard Materiales](#mod6-dashboard)

#### MÓDULO 7: PRODUCCIÓN (PP)
49. [Órdenes de Fabricación](#mod7-ordenes)
50. [Planificación de Producción](#mod7-planificacion)
51. [Lista de Materiales (BOM)](#mod7-bom)
52. [Rutas de Producción](#mod7-rutas)
53. [Consumo de Materiales](#mod7-consumo)
54. [Control de Calidad](#mod7-calidad)
55. [Mantenimiento de Equipos](#mod7-mantenimiento)
56. [Capacidad de Planta](#mod7-capacidad)
57. [Costos de Producción](#mod7-costos)
58. [Dashboard Producción](#mod7-dashboard)

#### MÓDULO 8: RRHH (HCM)
59. [Dashboard RRHH](#mod8-dashboard)
60. [Gestión de Empleados](#mod8-empleados)
61. [Nómina](#mod8-nomina)
62. [Asistencia](#mod8-asistencia)
63. [Vacaciones](#mod8-vacaciones)
64. [Liquidaciones](#mod8-liquidaciones)
65. [Capacitaciones](#mod8-capacitaciones)
66. [Evaluaciones de Desempeño](#mod8-evaluaciones)
67. [Reclutamiento](#mod8-reclutamiento)
68. [Estructura Organizacional](#mod8-estructura)
69. [Beneficios](#mod8-beneficios)

#### MÓDULO 9: SCM (SUPPLY CHAIN)
70. [Dashboard SCM](#mod9-dashboard)
71. [Gestión de Proveedores](#mod9-proveedores)
72. [Planificación de Demanda](#mod9-demanda)
73. [Gestión de Inventario SCM](#mod9-inventario)
74. [Transportistas](#mod9-transportistas)
75. [Rutas de Entrega](#mod9-rutas)
76. [Envíos](#mod9-envios)
77. [Trazabilidad](#mod9-trazabilidad)
78. [Integración con Proveedores](#mod9-integracion)
79. [KPIs SCM](#mod9-kpis)

#### MÓDULO 10: CRM
80. [Dashboard CRM](#mod10-dashboard)
81. [Oportunidades](#mod10-oportunidades)
82. [Actividades](#mod10-actividades)
83. [Campañas](#mod10-campanas)
84. [Contactos](#mod10-contactos)
85. [Cuentas](#mod10-cuentas)
86. [Pipeline de Ventas](#mod10-pipeline)
87. [Análisis CRM](#mod10-analisis)

#### MÓDULO 11: FIDELIZACIÓN
88. [Programas de Lealtad](#mod11-programas)
89. [Sistema de Puntos](#mod11-puntos)
90. [Recompensas](#mod11-recompensas)
91. [Segmentación de Clientes](#mod11-segmentacion)
92. [Campañas Personalizadas](#mod11-campanas)
93. [Comentarios y Valoraciones](#mod11-comentarios)
94. [Análisis de Fidelización](#mod11-analisis)

#### MÓDULO 12: BUSINESS INTELLIGENCE
95. [Dashboards Ejecutivos](#mod12-dashboards)
96. [KPIs Empresariales](#mod12-kpis)
97. [Análisis Predictivo](#mod12-predictivo)
98. [Reportes Personalizados](#mod12-reportes)
99. [Machine Learning](#mod12-ml)
100. [Analytics en Tiempo Real](#mod12-realtime)
101. [Análisis de Comportamiento (RFM)](#mod12-rfm)

#### MÓDULO 13: CONFIGURACIÓN
102. [Configuración del Sistema](#mod13-sistema)

#### MÓDULO 14: E-COMMERCE
103. [Catálogo Web](#mod14-catalogo)
104. [Pedidos Online](#mod14-pedidos)
105. [Cupones y Descuentos](#mod14-cupones)
106. [Analytics Web](#mod14-analytics)

#### MÓDULO 15: PROYECTOS
107. [Gestión de Proyectos](#mod15-proyectos)
108. [Tareas y Asignaciones](#mod15-tareas)
109. [Seguimiento de Costos](#mod15-costos)

#### MÓDULO 16: CALIDAD
110. [Inspecciones de Calidad](#mod16-inspecciones)
111. [No Conformidades](#mod16-nc)

#### MÓDULO 17: MANTENIMIENTO
112. [Órdenes de Mantenimiento](#mod17-ordenes)
113. [Planificación Preventiva](#mod17-planificacion)

#### MÓDULO 18: RELOJ CONTROL (NUEVO)
114. [Control de Asistencia](#mod18-asistencia)
115. [Reportes Excel Asistencia](#mod18-reportes)

### PARTE 3: INTEGRACIONES
116. [Integración SII](#integracion-sii)
117. [Integración Previred](#integracion-previred)
118. [Integración Transbank](#integracion-transbank)

### PARTE 4: API REST
119. [API REST - Guía Completa](#api-rest)

### PARTE 5: TROUBLESHOOTING
120. [Solución de Problemas Comunes](#troubleshooting)

---

# PARTE 1: INTRODUCCIÓN Y ARQUITECTURA

<a name="vision-general"></a>
## 1. VISIÓN GENERAL DEL SISTEMA

### ¿Qué es CONECTA ERP?

**CONECTA ERP** es un sistema de planificación de recursos empresariales (ERP) de **nivel empresarial**, comparable a sistemas internacionales como **SAP** y **Softland**, diseñado específicamente para empresas chilenas y latinoamericanas.

### Características Principales

| Característica | Descripción |
|----------------|-------------|
| **Módulos** | 17 módulos completos |
| **Submódulos** | 107 funcionalidades específicas |
| **Multi-tenant** | Soporte para múltiples empresas |
| **Multi-país** | 9 países configurables |
| **Multi-idioma** | 8 idiomas soportados |
| **API REST** | 7 endpoints completos |
| **Integraciones** | SII, Previred, Transbank |
| **Seguridad** | 100% prepared statements |

### ¿Para quién es este sistema?

- ✅ Empresas medianas y grandes (50+ empleados)
- ✅ Empresas con múltiples sucursales
- ✅ Empresas que requieren facturación electrónica (SII)
- ✅ Empresas que necesitan control de asistencia
- ✅ Empresas con operaciones complejas

---

<a name="arquitectura"></a>
## 2. ARQUITECTURA TÉCNICA

### Stack Tecnológico

```
┌─────────────────────────────────────┐
│         FRONTEND                    │
│  Bootstrap 5.3 + jQuery + Chart.js  │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         BACKEND                     │
│       PHP 7.4+ / 8.1                │
│    (100% Prepared Statements)       │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│       BASE DE DATOS                 │
│     MySQL 5.7+ / MariaDB 10.3+      │
│   (120+ tablas, vistas, triggers)   │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│      INTEGRACIONES                  │
│   SII | Previred | Transbank        │
└─────────────────────────────────────┘
```

### Estructura de Directorios

```
/var/www/conecta-erp/
├── api/                    # API REST (7 endpoints)
├── assets/                 # CSS, JS, imágenes
├── cron/                   # Scripts automáticos
├── includes/               # Archivos compartidos
│   └── config.php          # ⚠️ Configuración CRÍTICA
├── modulos/                # 17 módulos del ERP
│   ├── admin/
│   ├── entidades/
│   ├── finanzas/
│   ├── controlling/
│   ├── ventas/
│   ├── materiales/
│   ├── produccion/
│   ├── rrhh/
│   │   └── asistencia/     # ⭐ Reloj Control
│   ├── scm/
│   ├── crm/
│   ├── fidelizacion/
│   ├── bi/
│   ├── configuracion/
│   ├── ecommerce/
│   ├── proyectos/
│   ├── calidad/
│   └── mantenimiento/
├── servicios/              # SII, Previred, Transbank
├── sql/                    # 18 archivos SQL
└── user/                   # Dashboard principal
```

---

<a name="acceso-sistema"></a>
## 3. ACCESO AL SISTEMA

### URL de Acceso

```
http://tusitio.com/conecta-erp/
```

### Primera Vez - Crear Usuario Administrador

Si es la primera vez que accedes, necesitas crear un usuario super admin:

**Paso 1**: Conectar a MySQL
```bash
mysql -u root -p conectae_conectaerpbd
```

**Paso 2**: Generar hash de contraseña
```php
<?php
// Ejecutar en: /tmp/generar_password.php
echo password_hash('MI_CONTRASEÑA_SEGURA', PASSWORD_BCRYPT);
?>
```

**Paso 3**: Crear usuario
```sql
INSERT INTO usuarios (
  email,
  password,
  nombre,
  apellido,
  es_super_admin,
  activo
) VALUES (
  '[email protected]',
  '$2y$10$HASH_GENERADO_AQUI',
  'Administrador',
  'Sistema',
  1,
  1
);
```

### Credenciales de Acceso

| Campo | Valor |
|-------|-------|
| **Email** | [email protected] |
| **Contraseña** | (la que configuraste) |

### Pantalla de Login

![Pantalla de Login](imagenes/login.png)
*Captura: Pantalla de inicio de sesión*

---

<a name="navegacion"></a>
## 4. NAVEGACIÓN GENERAL

### Dashboard Principal

Al iniciar sesión, verás el **Dashboard Principal** con:

1. **Sidebar Izquierdo**: 17 módulos colapsables
2. **Topbar Superior**: Usuario, notificaciones, dark mode
3. **Área Central**: KPIs y gráficos
4. **Accesos Rápidos**: Funciones más usadas

![Dashboard Principal](imagenes/dashboard.png)
*Captura: Vista general del dashboard*

### Sidebar - 17 Módulos

El sidebar contiene todos los módulos del sistema:

```
📦 Administración (3)
📦 Entidades (5)
💰 Finanzas FI (11)
📊 Controlling CO (8)
🛒 Ventas SD (9)
📦 Materiales MM (8)
🏭 Producción PP (10)
👔 RRHH HCM (11)
🚚 SCM (10)
🤝 CRM (8)
🎁 Fidelización (7)
📈 Business Intelligence (18)
⚙️ Configuración (1)
🛍️ E-Commerce (4)
📁 Proyectos (3)
🏆 Calidad (2)
🔧 Mantenimiento (2)
```

### Elementos Comunes en Todas las Pantallas

| Elemento | Ubicación | Función |
|----------|-----------|---------|
| **Botón Volver** | Superior izquierda | Regresar al módulo anterior |
| **Botón Guardar** | Inferior derecha | Guardar cambios |
| **Botón Cancelar** | Inferior derecha | Cancelar operación |
| **Búsqueda** | Superior centro | Buscar registros |
| **Filtros** | Superior derecha | Filtrar datos |
| **Exportar** | Superior derecha | Exportar a Excel/PDF |

---

# PARTE 2: MÓDULOS COMPLETOS

# MÓDULO 1: ADMINISTRACIÓN CENTRAL

<a name="mod1-usuarios"></a>
## 5. GESTIÓN DE USUARIOS

### Ubicación
```
Dashboard → Administración → Gestión de Usuarios
```

### Propósito
Administrar usuarios del sistema, sus roles, permisos y accesos.

### Pantalla Principal

![Gestión de Usuarios](imagenes/mod1-usuarios.png)
*Captura: Listado de usuarios del sistema*

### Funciones Disponibles

#### 5.1. Crear Nuevo Usuario

**Pasos**:
1. Click en botón **"Nuevo Usuario"**
2. Completar formulario:
   - Email (único)
   - Nombre y Apellido
   - Contraseña (mínimo 8 caracteres)
   - Rol
   - Empresa (si multi-tenant)
3. Click en **"Guardar"**

**Campos del Formulario**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| Email | email | ✅ | Email único del usuario |
| Nombre | texto | ✅ | Nombre(s) |
| Apellido | texto | ✅ | Apellido(s) |
| Contraseña | password | ✅ | Mínimo 8 caracteres |
| Rol | select | ✅ | super_admin, admin, gerente, vendedor, operador |
| Empresa | select | ✅ | Empresa a la que pertenece |
| Teléfono | texto | ❌ | Teléfono de contacto |
| Foto | archivo | ❌ | Foto de perfil |

**Roles Disponibles**:

| Rol | Descripción | Permisos |
|-----|-------------|----------|
| **Super Admin** | Administrador total | Acceso a TODO |
| **Admin** | Administrador empresa | Acceso a su empresa |
| **Gerente** | Gerente | Reportes y gestión |
| **Vendedor** | Vendedor | Solo módulo ventas |
| **Operador** | Operador | Acceso limitado |

![Crear Usuario](imagenes/mod1-crear-usuario.png)
*Captura: Formulario de creación de usuario*

#### 5.2. Editar Usuario

**Pasos**:
1. Buscar usuario en la lista
2. Click en botón **"Editar"** (ícono lápiz)
3. Modificar campos necesarios
4. Click en **"Actualizar"**

**Campos Editables**:
- ✅ Nombre y apellido
- ✅ Teléfono
- ✅ Rol
- ✅ Estado (activo/inactivo)
- ❌ Email (NO se puede cambiar)

#### 5.3. Desactivar Usuario

**Pasos**:
1. Buscar usuario
2. Click en botón **"Desactivar"**
3. Confirmar acción

⚠️ **IMPORTANTE**: Los usuarios NO se eliminan, solo se desactivan para mantener trazabilidad.

#### 5.4. Configurar Permisos Granulares

**Pasos**:
1. Editar usuario
2. Ir a pestaña **"Permisos"**
3. Configurar permisos por módulo:
   - **read**: Solo lectura
   - **write**: Crear y editar
   - **delete**: Eliminar
   - **admin**: Administración completa

**Ejemplo de Permisos JSON**:
```json
{
  "finanzas": ["read", "write"],
  "ventas": ["read", "write", "delete"],
  "inventario": ["read"],
  "rrhh": ["read", "write"]
}
```

![Configurar Permisos](imagenes/mod1-permisos.png)
*Captura: Configuración de permisos granulares*

#### 5.5. Restablecer Contraseña

**Opción A: Desde Administración**
1. Editar usuario
2. Click en **"Restablecer Contraseña"**
3. Ingresar nueva contraseña
4. Guardar

**Opción B: Usuario Olvidó Contraseña**
1. En login, click **"Olvidé mi contraseña"**
2. Ingresar email
3. Recibirá link de restablecimiento por email

### Casos de Uso Comunes

**Caso 1: Nuevo Empleado Ingresa**
```
1. Admin crea usuario con rol "operador"
2. Asigna permisos básicos
3. Envía credenciales al empleado
4. Empleado cambia contraseña en primer login
```

**Caso 2: Empleado Cambia de Cargo**
```
1. Admin edita usuario
2. Cambia rol de "operador" a "gerente"
3. Actualiza permisos
4. Cambios son inmediatos
```

**Caso 3: Empleado Sale de la Empresa**
```
1. Admin desactiva usuario
2. Usuario no puede iniciar sesión
3. Historial se mantiene intacto
```

### Errores Comunes y Soluciones

❌ **Error**: "Email ya existe"
✅ **Solución**: El email debe ser único. Verificar si el usuario ya existe o usar otro email.

❌ **Error**: "Contraseña muy débil"
✅ **Solución**: La contraseña debe tener mínimo 8 caracteres, incluir mayúsculas, minúsculas y números.

❌ **Error**: "No tienes permisos para crear usuarios"
✅ **Solución**: Solo usuarios con rol super_admin o admin pueden crear usuarios.

---

<a name="mod1-empresas"></a>
## 6. GESTIÓN DE EMPRESAS

### Ubicación
```
Dashboard → Administración → Gestión de Empresas
```

### Propósito
Administrar empresas en un entorno multi-tenant, configurar datos fiscales y planes.

### Pantalla Principal

![Gestión de Empresas](imagenes/mod1-empresas.png)
*Captura: Listado de empresas en el sistema*

### Funciones Disponibles

#### 6.1. Registrar Nueva Empresa

**Pasos**:
1. Click en **"Nueva Empresa"**
2. Completar datos de la empresa
3. Seleccionar plan de suscripción
4. Guardar

**Formulario de Registro**:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **RUT** | RUT de la empresa | 76123456-7 |
| **Razón Social** | Nombre legal | MI EMPRESA SPA |
| **Nombre Comercial** | Nombre comercial | Mi Empresa |
| **Giro** | Actividad económica | Comercio y servicios |
| **Dirección** | Dirección fiscal | Av. Principal 123, Santiago |
| **Comuna** | Comuna | Santiago |
| **Ciudad** | Ciudad | Santiago |
| **Región** | Región | Metropolitana |
| **Teléfono** | Teléfono | +56912345678 |
| **Email** | Email corporativo | [email protected] |
| **Sitio Web** | URL (opcional) | www.miempresa.cl |

**Datos Fiscales**:
- Código Actividad Económica (SII)
- Representante Legal
- RUT Representante

**Selección de Plan**:

| Plan | Usuarios | Módulos | Precio Ref. |
|------|----------|---------|-------------|
| **Básico** | Hasta 5 | Core + Ventas | $50.000/mes |
| **Profesional** | Hasta 25 | Todos menos BI | $150.000/mes |
| **Empresarial** | Ilimitados | Todos | $300.000/mes |

![Crear Empresa](imagenes/mod1-crear-empresa.png)
*Captura: Formulario de registro de empresa*

#### 6.2. Editar Datos de Empresa

**Pasos**:
1. Buscar empresa en la lista
2. Click en **"Editar"**
3. Modificar campos
4. Guardar cambios

**Datos Editables**:
- ✅ Información de contacto
- ✅ Dirección
- ✅ Logo de la empresa
- ✅ Plan de suscripción
- ❌ RUT (NO se puede cambiar)

#### 6.3. Configurar Logo de Empresa

El logo aparece en:
- Facturas
- Reportes
- Dashboard
- Emails

**Pasos**:
1. Editar empresa
2. Sección **"Logo"**
3. Click **"Subir Logo"**
4. Seleccionar imagen (PNG o JPG, máx 2MB)
5. Guardar

**Especificaciones de Logo**:
- Formato: PNG o JPG
- Tamaño recomendado: 300x100 px
- Fondo transparente (PNG)
- Peso máximo: 2 MB

![Configurar Logo](imagenes/mod1-logo-empresa.png)
*Captura: Configuración de logo de empresa*

#### 6.4. Cambiar Plan de Suscripción

**Pasos**:
1. Editar empresa
2. Sección **"Plan de Suscripción"**
3. Seleccionar nuevo plan
4. Confirmar cambio
5. Guardar

⚠️ **IMPORTANTE**:
- El upgrade es inmediato
- El downgrade puede requerir desactivar funciones
- Diferencia se prorratea en próxima factura

#### 6.5. Desactivar Empresa

**Pasos**:
1. Buscar empresa
2. Click **"Desactivar"**
3. Confirmar

**Consecuencias**:
- ❌ Usuarios no pueden iniciar sesión
- ❌ API keys dejan de funcionar
- ✅ Datos se mantienen intactos
- ✅ Se puede reactivar cuando sea necesario

### Configuración Multi-Tenant

El sistema soporta **múltiples empresas independientes** en la misma instalación:

**Características**:
- ✅ Datos completamente aislados por `empresa_id`
- ✅ Usuarios pueden pertenecer a múltiples empresas
- ✅ Cada empresa tiene su propia configuración
- ✅ Facturación independiente

**Ejemplo de Configuración**:
```
Empresa A: Retail (Plan Profesional)
  └─ 15 usuarios
  └─ Módulos: Ventas, Inventario, Finanzas

Empresa B: Servicios (Plan Empresarial)
  └─ 50 usuarios
  └─ Módulos: TODOS

Empresa C: Manufactura (Plan Básico)
  └─ 3 usuarios
  └─ Módulos: Producción, Inventario
```

### Errores Comunes

❌ **Error**: "RUT ya existe"
✅ **Solución**: Cada empresa debe tener un RUT único. Verificar si ya está registrada.

❌ **Error**: "No se puede downgrade el plan, hay funciones en uso"
✅ **Solución**: Desactivar primero las funciones del plan superior antes de hacer downgrade.

---

<a name="mod1-auditoria"></a>
## 7. AUDITORÍA DEL SISTEMA

### Ubicación
```
Dashboard → Administración → Auditoría
```

### Propósito
Revisar todos los eventos del sistema para seguridad, cumplimiento y troubleshooting.

### Pantalla Principal

![Auditoría](imagenes/mod1-auditoria.png)
*Captura: Registro de auditoría del sistema*

### Eventos Auditados Automáticamente

| Categoría | Eventos |
|-----------|---------|
| **Seguridad** | Login, Logout, Intentos fallidos |
| **Usuarios** | Crear, Editar, Desactivar usuarios |
| **Datos** | Crear, Editar, Eliminar registros |
| **Configuración** | Cambios en configuración |
| **Financiero** | Facturas, Pagos, Movimientos |
| **Inventario** | Movimientos de stock |

### Funciones Disponibles

#### 7.1. Ver Registro de Auditoría

**Pantalla muestra**:
- ✅ Fecha y hora exacta
- ✅ Usuario que realizó la acción
- ✅ Módulo/tabla afectada
- ✅ Tipo de acción (CREATE, UPDATE, DELETE, LOGIN)
- ✅ IP del usuario
- ✅ Detalles de la acción (JSON)

![Detalle Auditoría](imagenes/mod1-auditoria-detalle.png)
*Captura: Detalle de evento de auditoría*

#### 7.2. Filtrar Registros

**Filtros Disponibles**:

| Filtro | Opciones |
|--------|----------|
| **Fecha** | Desde - Hasta |
| **Usuario** | Seleccionar usuario |
| **Módulo** | Todos los módulos |
| **Acción** | CREATE, UPDATE, DELETE, LOGIN, LOGOUT |
| **IP** | Dirección IP |

**Ejemplo de Filtro**:
```
Fecha: 01/11/2025 - 30/11/2025
Usuario: [email protected]
Módulo: Facturas
Acción: CREATE, UPDATE
```

#### 7.3. Exportar Registro

**Formatos Disponibles**:
- Excel (.xlsx)
- PDF
- CSV

**Pasos**:
1. Aplicar filtros deseados
2. Click **"Exportar"**
3. Seleccionar formato
4. Descargar archivo

### Casos de Uso

**Caso 1: Investigar Cambios en Factura**
```
1. Filtrar por módulo "Facturas"
2. Buscar número de factura
3. Ver historial completo de cambios
4. Identificar usuario y fecha
```

**Caso 2: Detectar Acceso No Autorizado**
```
1. Filtrar por acción "LOGIN"
2. Ordenar por fecha DESC
3. Buscar IPs sospechosas
4. Verificar usuarios activos
```

**Caso 3: Cumplimiento Normativo**
```
1. Exportar todo el mes en Excel
2. Filtrar por módulo "Finanzas"
3. Entregar a auditor externo
```

### Detalles Técnicos

**Estructura del Registro**:
```json
{
  "fecha": "2025-11-16 14:30:45",
  "usuario_id": 15,
  "email": "[email protected]",
  "modulo": "facturas",
  "accion": "UPDATE",
  "tabla": "facturas",
  "registro_id": 1234,
  "ip_address": "192.168.1.100",
  "datos_anteriores": {
    "total": 100000,
    "estado": "emitida"
  },
  "datos_nuevos": {
    "total": 119000,
    "estado": "pagada"
  }
}
```

### Retención de Registros

⚠️ **IMPORTANTE**: Los registros de auditoría se mantienen:
- Por defecto: 365 días (1 año)
- Configurable en: Configuración → Sistema → Retención Auditoría
- Nunca se eliminan registros críticos (LOGIN, DELETE)

---

(Continúa con los 106 submódulos restantes...)

