# CONECTA ERP - Sistema ERP Profesional Multi-Empresa

![CONECTA ERP](https://via.placeholder.com/1200x300/667eea/ffffff?text=CONECTA+ERP)

## 🚀 Características Principales

CONECTA ERP es un sistema ERP completo y profesional con las siguientes características:

### ✨ Características del Sistema

- **14 Módulos Principales** con 106 Submódulos especializados
- **Multiempresa** - Gestiona múltiples empresas desde una sola cuenta
- **Multiidioma** - 9 idiomas disponibles (Español, English, Português, Français, Deutsch, Italiano, Русский, 中文, 日本語)
- **Multipais** - Soporta 9 países con validación de documentos locales
- **Sistema de Planes** - 4 planes de suscripción con período de prueba de 14 días
- **Actualización Automática** - UF, Dólar, UTM y otros indicadores económicos
- **Validación de RUT Chileno** - Formateo y validación automática
- **Dashboard Completo** - Para administradores y usuarios
- **Sistema de Permisos** - Control de acceso basado en roles
- **Logs de Auditoría** - Registro completo de actividades

## 📦 Módulos del Sistema

### 1. **Administración Central** (3 submódulos)
   - Gestión de Empresas
   - Gestión de Seguridad
   - Parametrización Global

### 2. **Gestión de Entidades** (5 submódulos)
   - Entidades Maestras
   - Gestión de Clientes
   - Gestión de Proveedores
   - Gestión de Empleados
   - Productos y Servicios

### 3. **Finanzas (FI)** (11 submódulos)
   - Contabilidad General
   - Cuentas por Pagar
   - Cuentas por Cobrar
   - Tesorería
   - Activos Fijos
   - Comprobantes y Facturas
   - IFRS
   - Consolidación
   - Reporting Financiero
   - Presupuestos
   - Impuestos

### 4. **Controlling (CO)** (8 submódulos)
   - Centros de Costo
   - Análisis de Rentabilidad
   - Contabilidad de Órdenes Internas
   - Contabilidad de Proyectos
   - Control de Gastos Generales
   - Costos del Producto
   - Análisis de Resultados
   - Planificación y Presupuestos

### 5. **Ventas (SD)** (9 submódulos)
   - Pedidos
   - Facturación
   - Punto de Venta (POS)
   - Configuración de Cajas
   - Gestión de Precios
   - Análisis de Ventas
   - Devoluciones
   - Catálogo de Productos
   - Promociones

### 6. **Materiales (MM)** (8 submódulos)
   - Inventario
   - Compras
   - Gestión de Almacenes
   - Verificación de Facturas
   - Planificación de Necesidades
   - Gestión de Calidad
   - Valoración de Inventario
   - Análisis de Compras

### 7. **Producción (PP)** (10 submódulos)
   - Órdenes de Producción
   - MRP
   - Planificación de Producción
   - Control de Planta
   - Gestión de Calidad
   - Mantenimiento
   - Gestión de Recursos
   - Optimización de Procesos
   - Costos de Producción
   - Trazabilidad

### 8. **RRHH (HCM)** (11 submódulos)
   - Personal
   - Nómina
   - Reclutamiento
   - Evaluación de Desempeño
   - Capacitación
   - Gestión de Talento
   - Beneficios y Compensaciones
   - Control de Asistencia
   - Vacaciones y Licencias
   - Salud Ocupacional
   - Análisis de Personal

### 9. **SCM (Supply Chain Management)** (10 submódulos)
   - Logística
   - Transporte
   - Gestión de Rutas
   - Planificación de Entregas
   - Control de Flotilla
   - Optimización de Rutas
   - Gestión de Almacenes SCM
   - Trazabilidad de Envíos
   - Análisis de Cadena de Suministro
   - Gestión de Proveedores SCM

### 10. **CRM (Customer Relationship Management)** (8 submódulos)
   - Gestión de Clientes
   - Oportunidades
   - Leads
   - Pipeline de Ventas
   - Contactos
   - Campañas
   - Cuentas
   - Cotizaciones

### 11. **Fidelización** (7 submódulos)
   - Programas de Lealtad
   - Sistema de Puntos
   - Gestión de Recompensas
   - Campañas Personalizadas
   - Análisis de Fidelización
   - Comentarios de Clientes
   - Segmentación de Clientes

### 12. **Business Intelligence** (15 submódulos)
   - Dashboards
   - KPIs
   - Informes Ejecutivos
   - Análisis Predictivo
   - Reportes Financieros
   - Reportes de Ventas
   - Reportes de Inventario
   - Reportes de Producción
   - Reportes de RRHH
   - Reportes de Compras
   - Reportes de Calidad
   - Reportes de Mantenimiento
   - Reportes de Logística
   - Reportes de Proyectos
   - Reportes Personalizados

### 13. **Configuración** (1 submódulo)
   - Ajustes del Sistema

### 14. **Dashboard Principal** (1 submódulo)
   - Panel principal de navegación y estadísticas

## 🛠️ Requisitos del Sistema

- **PHP** 7.4 o superior
- **MySQL** 5.7 o superior (recomendado MySQL 8.0+)
- **Apache** 2.4+ o **Nginx**
- **Extensiones PHP requeridas:**
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - curl
  - openssl

## 📥 Instalación

### Opción 1: Instalación Automática (Recomendada)

1. **Clonar o descargar el repositorio**
   ```bash
   git clone https://github.com/tu-usuario/conecta-erp.git
   cd conecta-erp
   ```

2. **Configurar la base de datos**

   Editar el archivo `includes/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'conectae_conectaerpbd');
   define('DB_USER', 'conectae_conectaerpuser');
   define('DB_PASS', 'pt125824caraud');
   ```

3. **Ejecutar el instalador**

   Abrir en el navegador:
   ```
   http://tu-dominio.com/installer.php
   ```

   El instalador creará automáticamente:
   - Todas las tablas necesarias
   - Datos iniciales (países, idiomas, planes)
   - Usuario administrador
   - Módulos y submódulos

4. **Credenciales de Administrador**
   ```
   Email: auditorexchile@gmail.com
   Password: admin123
   ```

5. **Eliminar el instalador** (por seguridad)
   ```bash
   rm installer.php
   ```

### Opción 2: Instalación Manual

1. **Crear la base de datos**
   ```sql
   CREATE DATABASE conectae_conectaerpbd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Importar el esquema SQL**
   ```bash
   mysql -u usuario -p conectae_conectaerpbd < database/schema.sql
   ```

3. **Configurar el archivo config.php** como se indica arriba

4. **Acceder al sistema**
   ```
   http://tu-dominio.com/login_new.php
   ```

## 🔧 Configuración Post-Instalación

### 1. Actualización Automática de Indicadores

Para actualizar automáticamente los indicadores económicos (UF, Dólar, UTM), configurar CRON:

```bash
# Editar crontab
crontab -e

# Agregar la siguiente línea (ejecuta diariamente a las 9:00 AM)
0 9 * * * php /ruta/completa/conecta-erp/cron/update_indicators.php
```

### 2. Configuración de Email

Editar `includes/config.php` y configurar los parámetros de email:

```php
// Configurar servicio de email (PHPMailer, SMTP, etc.)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'tu-email@gmail.com');
define('MAIL_PASSWORD', 'tu-password');
```

### 3. Configuración de Zona Horaria

En `includes/config.php`:
```php
define('APP_TIMEZONE', 'America/Santiago'); // Cambiar según tu zona
```

## 📱 Acceso al Sistema

### Para Usuarios Normales

1. **Registro**: `http://tu-dominio.com/register.php`
   - Seleccionar país (9 disponibles)
   - Seleccionar idioma (9 disponibles)
   - Ingresar documento con validación automática (RUT, DNI, CPF, etc.)
   - Seleccionar plan de suscripción
   - 14 días de prueba gratis

2. **Aprobación**: El administrador debe aprobar la cuenta

3. **Login**: `http://tu-dominio.com/login_new.php`

4. **Dashboard**: `http://tu-dominio.com/user/dashboard.php`

### Para Administradores

1. **Login**: `http://tu-dominio.com/login_new.php`
   ```
   Email: auditorexchile@gmail.com
   Password: admin123
   ```

2. **Dashboard Admin**: `http://tu-dominio.com/admin/dashboard.php`

## 🎨 Estructura del Proyecto

```
conecta-erp/
├── admin/                  # Panel de administración
│   ├── dashboard.php       # Dashboard del administrador
│   ├── users.php           # Gestión de usuarios
│   ├── companies.php       # Gestión de empresas
│   └── ...
├── user/                   # Panel de usuario
│   ├── dashboard.php       # Dashboard del usuario
│   └── ...
├── modules/                # Módulos del sistema
│   ├── admin/              # Módulo de Administración
│   ├── entities/           # Módulo de Entidades
│   ├── fi/                 # Módulo de Finanzas
│   ├── co/                 # Módulo de Controlling
│   ├── sd/                 # Módulo de Ventas
│   ├── mm/                 # Módulo de Materiales
│   ├── pp/                 # Módulo de Producción
│   ├── hcm/                # Módulo de RRHH
│   ├── scm/                # Módulo de SCM
│   ├── crm/                # Módulo de CRM
│   ├── loyalty/            # Módulo de Fidelización
│   ├── bi/                 # Módulo de BI
│   ├── config/             # Módulo de Configuración
│   └── example_module.php  # Plantilla de módulo
├── includes/               # Archivos de configuración
│   ├── config.php          # Configuración principal
│   ├── i18n.php            # Sistema multiidioma
│   └── rut_validator.php   # Validador de RUT
├── assets/                 # Recursos estáticos
│   ├── css/
│   │   └── style.css       # Estilos personalizados
│   ├── js/
│   └── img/
├── cron/                   # Scripts de tareas programadas
│   └── update_indicators.php
├── database/               # Scripts SQL
│   └── schema.sql
├── installer.php           # Instalador automático
├── index_new.php           # Página de inicio
├── register.php            # Registro de usuarios
├── login_new.php           # Inicio de sesión
├── logout.php              # Cerrar sesión
└── README_INSTALACION.md   # Este archivo
```

## 🌍 Países y Documentos Soportados

| País | Código | Documento | Formato | Validación |
|------|--------|-----------|---------|------------|
| Chile | CL | RUT | ##.###.###-# | Sí (algoritmo módulo 11) |
| Argentina | AR | DNI | ##.###.### | No |
| Perú | PE | DNI | ######## | No |
| Colombia | CO | CC | ########## | No |
| México | MX | CURP | AAAA######AAAAAA## | No |
| Brasil | BR | CPF | ###.###.###-## | Sí (algoritmo CPF) |
| España | ES | DNI | ########-A | Sí (letra de control) |
| Estados Unidos | US | SSN | ###-##-#### | No |
| Uruguay | UY | CI | #.###.###-# | No |

## 🗣️ Idiomas Soportados

1. 🇪🇸 Español
2. 🇺🇸 English
3. 🇧🇷 Português
4. 🇫🇷 Français
5. 🇩🇪 Deutsch
6. 🇮🇹 Italiano
7. 🇷🇺 Русский (Ruso)
8. 🇨🇳 中文 (Chino)
9. 🇯🇵 日本語 (Japonés)

## 💳 Planes de Suscripción

### Plan Básico - $29.99/mes
- Hasta 3 usuarios
- 1 empresa
- Módulos básicos
- 10GB almacenamiento
- Soporte por email

### Plan Profesional - $79.99/mes (Recomendado)
- Hasta 10 usuarios
- 3 empresas
- Todos los módulos
- 100GB almacenamiento
- Soporte prioritario
- Reportes avanzados

### Plan Empresarial - $199.99/mes
- Hasta 50 usuarios
- 10 empresas
- Todos los módulos
- Almacenamiento ilimitado
- Soporte 24/7
- API completa

### Plan Personalizado - Contactar
- Usuarios ilimitados
- Empresas ilimitadas
- Todo incluido
- Desarrollo personalizado
- Consultor dedicado

## 🔐 Seguridad

- **Passwords**: Hasheados con bcrypt
- **Sesiones**: Configuración segura con regeneración periódica
- **CSRF Protection**: Tokens CSRF en formularios
- **SQL Injection**: Uso de prepared statements
- **XSS Protection**: Sanitización de inputs
- **Logs de auditoría**: Registro de todas las acciones

## 📊 Indicadores Económicos

El sistema actualiza automáticamente los siguientes indicadores (Chile):

- **UF** (Unidad de Fomento)
- **UTM** (Unidad Tributaria Mensual)
- **Dólar** (USD)
- **Euro** (EUR)

Fuente: https://mindicador.cl/api

## 🐛 Solución de Problemas

### Error de conexión a base de datos
```
Error de conexión a la base de datos.
```
**Solución**: Verificar credenciales en `includes/config.php`

### Usuario no aprobado
```
Su cuenta aún no ha sido aprobada.
```
**Solución**: El administrador debe aprobar la cuenta desde el dashboard admin

### Trial expirado
```
Su período de prueba ha expirado.
```
**Solución**: Seleccionar un plan de suscripción desde el panel de usuario

## 📞 Soporte

- **Email**: auditorexchile@gmail.com
- **Teléfono**: +56 9 XXXX XXXX
- **Ubicación**: Santiago, Chile

## 📝 Licencia

© 2025 CONECTA ERP. Todos los derechos reservados. Desarrollado por Auditorex Chile.

## 🎯 Próximas Características

- [ ] Integración con SII (Servicio de Impuestos Internos - Chile)
- [ ] Integración con Previred
- [ ] API RESTful completa
- [ ] Aplicación móvil (iOS/Android)
- [ ] Módulo de E-commerce
- [ ] Sincronización en tiempo real
- [ ] Exportación a Excel/PDF avanzada
- [ ] Firma electrónica de documentos

## 👥 Contribuir

Si deseas contribuir al proyecto, por favor contactar a auditorexchile@gmail.com

---

**¡Gracias por usar CONECTA ERP!** 🚀
