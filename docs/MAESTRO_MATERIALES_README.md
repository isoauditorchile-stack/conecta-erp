# MODULO MAESTRO DE MATERIALES - CONECTA ERP

## Descripcion

Modulo completo para la gestion de materiales con todas sus propiedades, integraciones y trazabilidad.

## Caracteristicas

- Sin dependencias externas
- Sin AJAX (procesamiento server-side)
- UTF-8 encoding
- Prepared statements para seguridad
- CRUD completo (Crear, Leer, Actualizar, Eliminar)
- Interfaz moderna y responsiva

## Instalacion

### 1. Crear la tabla en la base de datos

Ejecutar el script SQL ubicado en:
```
/database/maestro_materiales.sql
```

Puede ejecutarlo de las siguientes formas:

**Opcion A: Via phpMyAdmin**
1. Acceder a phpMyAdmin
2. Seleccionar la base de datos `conectae_conectaerpbd`
3. Ir a la pestana SQL
4. Copiar y pegar el contenido del archivo `maestro_materiales.sql`
5. Ejecutar

**Opcion B: Via linea de comandos (si tiene acceso MySQL)**
```bash
mysql -u conectae_conectaerpuser -p conectae_conectaerpbd < /home/user/conecta-erp/database/maestro_materiales.sql
```
Contrasena: pt125824caraud

**Opcion C: Via script PHP**
```bash
php /home/user/conecta-erp/database/install_maestro_materiales.php
```

### 2. Acceder al modulo

El modulo ya esta integrado en el sistema. Para acceder:

1. Iniciar sesion en CONECTA ERP
2. Ir al Dashboard de usuario
3. Seleccionar el modulo "MM - Materials Management"
4. Hacer clic en "Maestro de Materiales"

O acceder directamente via URL:
```
http://su-dominio.com/modules/mm/materiales.php
```

## Estructura de la tabla

La tabla `mm_maestro_materiales` incluye las siguientes secciones:

### Identificacion del Material
- Codigo material (obligatorio)
- Codigo interno
- Codigo de barras
- Descripcion (obligatoria)
- Descripcion larga
- Tipo de material, familia, subfamilia, categoria
- Marca, modelo, SKU
- Estado del material

### Datos Generales
- Unidades de medida (base, compra, venta)
- Factores de conversion
- Peso, volumen, dimensiones
- Color, talla, formato

### Informacion Comercial
- Precios (base, minimo, maximo)
- Lista de precios
- Moneda
- Impuestos
- Tipo de venta y producto

### Control de Inventario
- Control de stock
- Niveles (minimo, maximo, punto de reposicion)
- Lote y serie
- Vida util y vencimiento
- Politica de rotacion (FIFO/LIFO/FEFO)
- Ubicaciones y bodegas

### Costos
- Costos (promedio, ultimo, estandar)
- Metodo de costeo
- Margenes y markup

### Proveedores
- Proveedor principal y secundarios
- Codigo en proveedor
- Costos y plazos
- Minimos de compra

### Datos para Compras
- Permisos de compra
- Cantidades minimas y multiplos
- Plazos de reposicion
- Costos de flete e importacion

### Datos para Ventas
- Permisos de venta
- Descuentos maximos
- Listas de precios
- Comisiones
- Promociones

### Imagen y Adjuntos
- Imagenes (principal y secundaria)
- Ficha tecnica
- Manual
- Documentos asociados

### Integraciones
- Contabilidad
- Inventario
- Compras
- Ventas
- E-commerce
- POS
- Produccion

### Auditoria
- Fecha y usuario de creacion
- Fecha y usuario de modificacion
- Historial de cambios

## Uso del Modulo

### Crear un Material

1. Hacer clic en "Nuevo Material"
2. Llenar los campos obligatorios (marcados con *)
   - Codigo Material
   - Descripcion
3. Completar las secciones adicionales segun necesidad
4. Hacer clic en "Crear Material"

### Editar un Material

1. En la lista de materiales, hacer clic en el boton de edicion (icono lapiz)
2. Modificar los campos necesarios
3. Hacer clic en "Actualizar Material"

### Eliminar un Material

1. En la lista de materiales, hacer clic en el boton de eliminacion (icono basura)
2. Confirmar la eliminacion en el dialogo

### Filtrar Materiales

Utilizar los filtros disponibles:
- **Buscar**: Por codigo, descripcion o codigo de barras
- **Familia**: Filtrar por familia de materiales
- **Estado**: Filtrar por estado (activo, inactivo, descontinuado, en evaluacion)

## Archivos del Modulo

```
/database/
  └── maestro_materiales.sql              # Script de creacion de tabla
  └── install_maestro_materiales.php      # Script de instalacion

/modules/mm/
  └── materiales.php                      # Modulo principal con CRUD completo
  └── index.php                           # Menu actualizado con enlace

/docs/
  └── MAESTRO_MATERIALES_README.md        # Esta documentacion
```

## Caracteristicas Tecnicas

- **Base de datos**: MySQL/MariaDB con charset utf8mb4
- **Motor**: InnoDB
- **Seguridad**: Prepared statements PDO
- **Autenticacion**: Sistema integrado de CONECTA ERP
- **Responsive**: Adaptable a dispositivos moviles
- **Dark theme**: Interfaz oscura moderna

## Permisos

El modulo utiliza el sistema de permisos de CONECTA ERP:
- Cada usuario solo ve sus propios materiales
- Los administradores pueden ver todos los materiales
- Se registra auditoria de creacion y modificacion

## Soporte

Para reportar problemas o solicitar mejoras:
- Revisar la consola del navegador para errores JavaScript
- Revisar los logs de PHP para errores del servidor
- Contactar al administrador del sistema

## Version

- **Version**: 1.0.0
- **Fecha**: 2025-11-21
- **Autor**: CONECTA ERP Team
