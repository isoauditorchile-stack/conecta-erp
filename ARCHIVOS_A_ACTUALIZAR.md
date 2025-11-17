# 📋 ARCHIVOS A ACTUALIZAR MANUALMENTE EN EL SERVIDOR

**Fecha**: 2025-01-17
**Branch**: `claude/conecta-erp-system-01GZKs5wV4m9SmX2oyPHMXWN`
**Commit**: `41a93da`

---

## ✅ ARCHIVOS MODIFICADOS (2 archivos)

### 1. `/install.php` ⭐ IMPORTANTE
**Ubicación en servidor**: `/home/conectae/public_html/install.php`

**Cambios**:
- ✅ Ignora errores duplicados automáticamente (1062, 1061, 1060)
- ✅ Ignora permisos RELOAD (1227)
- ✅ Ignora triggers/procedimientos existentes (1304, 1359, 1360)
- ✅ Parser mejorado elimina comentarios multilínea `/* */`
- ✅ Ignora `FLUSH PRIVILEGES` automáticamente
- ✅ Muestra SOLO errores reales

**Acción**: REEMPLAZAR archivo completo

---

### 2. `/sql/modulos/21_sistema_integracion_completa.sql` ⭐ NUEVO
**Ubicación en servidor**: `/home/conectae/public_html/sql/modulos/21_sistema_integracion_completa.sql`

**Contenido** (970 líneas):

#### SECCIÓN 1: Indicadores Económicos
- Tabla `indicadores_economicos` (Dólar, UF, UTM, Euro, IPC, TPM)
- Actualización automática desde API Banco Central

#### SECCIÓN 2: Tipos de Documentos SII (50+)
- Tabla `tipos_documentos_sii` con TODOS los tipos:
  - Facturas: 33, 34, 30, 32
  - Boletas: 35, 38, 39, 41
  - Notas Crédito: 60, 61
  - Notas Débito: 55, 56
  - Guías Despacho: 50, 52
  - Liquidaciones: 40, 43, 103
  - Exportación: 110, 111, 112
  - Documentos Referencia: 801-820
  - Especializados: 906-920

#### SECCIÓN 3: Sistema de Folios CAF
- Tabla `folios_caf` para gestión de Códigos de Autorización
- Control de folios disponibles, agotados, vencidos

#### SECCIÓN 4: Documentos Tributarios Electrónicos
- Tabla `documentos_tributarios` (DTE completos)
- Tabla `documentos_tributarios_detalle` (líneas)
- Almacenamiento de XML, TED, track ID SII

#### SECCIÓN 5: Libro de Compras y Ventas
- Tabla `libro_compras` (para declaración F29)
- Tabla `libro_ventas` (para declaración F29)
- Registro automático desde DTE

#### SECCIÓN 6: Declaraciones Juradas
- Tabla `declaraciones_juradas` (F29, F50, DJ1887, DJ1879, DJ1947, F22)
- Cálculo automático de IVA a pagar
- Fechas de vencimiento

#### SECCIÓN 7: Fechas Importantes
- Tabla `fechas_importantes` (calendario tributario)
- Alertas previas configurables
- Fechas predeterminadas Chile:
  - F29 (días 12-16 según RUT)
  - Previred (día 10)
  - DJ1879 (15 marzo)
  - F22 Renta (30 abril)

#### SECCIÓN 8: Períodos Contables
- Tabla `periodos_contables`
- Cambio automático de período cada mes
- Cambio manual disponible

#### SECCIÓN 9: Cuadraturas
- Tabla `cuadraturas` (IVA, ventas, compras, bancos, etc.)
- Verificación automática de cuadratura

#### SECCIÓN 10: Configuración DTE
- Tabla `configuracion_dte`
- Almacenamiento de certificados digitales (.pfx/.p12)
- Configuración ambiente certificación/producción

#### SECCIÓN 11: Vistas Consolidadas
- `v_estado_folios` - Resumen folios disponibles
- `v_libro_ventas_consolidado` - Para F29
- `v_libro_compras_consolidado` - Para F29
- `v_resumen_f29` - F29 automático
- `v_proximas_fechas` - Fechas importantes próximas
- `v_indicadores_actuales` - Últimos indicadores económicos

#### SECCIÓN 12: Procedimientos Almacenados
- `sp_obtener_siguiente_folio()` - Obtener folio del CAF
- `sp_generar_asiento_venta()` - Asiento contable automático desde factura
- `sp_actualizar_libro_ventas()` - Actualizar libro desde DTE
- `sp_cambiar_periodo()` - Cambiar período contable
- `sp_generar_f29()` - Generar F29 automáticamente

#### SECCIÓN 13: Triggers
- `tr_dte_insert_libro_ventas` - Auto-registrar en libro ventas
- `tr_folios_alerta` - Alerta cuando quedan pocos folios
- `ev_cambiar_periodo_automatico` - Event scheduler cambio período

**Acción**: SUBIR archivo nuevo

---

## 📂 ESTRUCTURA DE CARPETAS REQUERIDA

Asegúrate de que existan estas carpetas en el servidor:

```
/home/conectae/public_html/
├── sql/
│   ├── modulos/
│   │   └── 21_sistema_integracion_completa.sql  ← NUEVO
├── install.php  ← MODIFICADO
```

---

## 🚀 PASOS PARA ACTUALIZAR

### Opción 1: Actualizar solo los 2 archivos (RECOMENDADO)

1. **Subir archivos vía FTP/cPanel**:
   ```
   /install.php
   /sql/modulos/21_sistema_integracion_completa.sql
   ```

2. **Ejecutar instalador**:
   - Ir a: `http://tu-dominio.com/install.php`
   - Marcar: ☑️ Forzar reinstalación
   - Click: **Instalar Sistema**

3. **Importar SQL manualmente** (si prefieres):
   - phpMyAdmin → Import
   - Seleccionar: `21_sistema_integracion_completa.sql`
   - Click: Go

### Opción 2: Importar SQL directamente en phpMyAdmin

Si prefieres **NO usar install.php**:

1. Abrir phpMyAdmin
2. Seleccionar base de datos: `conectae_conectaerpbd`
3. Click en pestaña **SQL**
4. Copiar y pegar contenido de: `21_sistema_integracion_completa.sql`
5. Click **Go**

---

## ✅ VERIFICACIÓN POST-INSTALACIÓN

Ejecuta estas consultas en phpMyAdmin para verificar:

```sql
-- 1. Verificar que existen las nuevas tablas
SHOW TABLES LIKE '%indicadores%';
SHOW TABLES LIKE '%tipos_documentos_sii%';
SHOW TABLES LIKE '%folios_caf%';
SHOW TABLES LIKE '%libro_ventas%';
SHOW TABLES LIKE '%declaraciones_juradas%';
SHOW TABLES LIKE '%fechas_importantes%';

-- 2. Verificar datos iniciales
SELECT COUNT(*) FROM tipos_documentos_sii;  -- Debe ser 40+
SELECT COUNT(*) FROM indicadores_economicos;  -- Debe ser 2
SELECT COUNT(*) FROM fechas_importantes;  -- Debe ser 8

-- 3. Verificar procedimientos
SHOW PROCEDURE STATUS WHERE db = 'conectae_conectaerpbd';

-- 4. Verificar vistas
SHOW FULL TABLES WHERE Table_Type = 'VIEW';
```

---

## 📊 FUNCIONALIDADES NUEVAS DISPONIBLES

Una vez instalado, tendrás acceso a:

### 1. **Indicadores Económicos Automáticos** 💱
```php
// Obtener indicadores actuales
SELECT * FROM v_indicadores_actuales;
```

### 2. **Sistema de Facturación SII Completo** 📄
```php
// Obtener siguiente folio para Factura Electrónica (33)
CALL sp_obtener_siguiente_folio(1, 33, @folio, @caf_id);
SELECT @folio, @caf_id;
```

### 3. **Libro de Compras y Ventas** 📚
```php
// Actualizar libro de ventas del período
CALL sp_actualizar_libro_ventas('2025-01', 1);

// Ver resumen consolidado
SELECT * FROM v_libro_ventas_consolidado WHERE periodo = '2025-01';
```

### 4. **F29 Automático** 📝
```php
// Generar F29 automáticamente
CALL sp_generar_f29(1, '2025-01');

// Ver resumen F29
SELECT * FROM v_resumen_f29 WHERE periodo = '2025-01';
```

### 5. **Fechas Importantes** 📅
```php
// Ver próximas fechas importantes
SELECT * FROM v_proximas_fechas WHERE estado_alerta IN ('ALERTA', 'PROXIMA');
```

### 6. **Estado de Folios** 📊
```php
// Ver folios disponibles
SELECT * FROM v_estado_folios WHERE empresa_id = 1;
```

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "Table already exists"
✅ **Normal** - El instalador ignora esto automáticamente

### Error: "Duplicate entry"
✅ **Normal** - El instalador ignora esto automáticamente

### Error: "Access denied RELOAD"
✅ **Normal** - El instalador ignora `FLUSH PRIVILEGES` automáticamente

### Error: "Trigger already exists"
✅ **Normal** - El instalador ignora esto automáticamente

### Error REAL (que SÍ importa):
❌ Si ves errores que NO son de los anteriores, entonces SÍ hay un problema.
   Copia el error y envíalo para análisis.

---

## 📞 SOPORTE

Si tienes algún problema durante la instalación:

1. Verificar que los 2 archivos se subieron correctamente
2. Verificar permisos de archivos (644 para .php, 644 para .sql)
3. Revisar logs de error de PHP (`error_log`)
4. Verificar que la base de datos sea: `conectae_conectaerpbd`

---

## 🎯 RESUMEN EJECUTIVO

| Archivo | Acción | Prioridad |
|---------|--------|-----------|
| `/install.php` | REEMPLAZAR | ⭐⭐⭐ |
| `/sql/modulos/21_sistema_integracion_completa.sql` | SUBIR NUEVO | ⭐⭐⭐ |

**Resultado**: Sistema CONECTA ERP con integración completa estilo Softland:
- ✅ Indicadores económicos automáticos
- ✅ 50+ tipos documentos SII
- ✅ Folios CAF completos
- ✅ Libro compra/venta automático
- ✅ F29 automático
- ✅ Fechas importantes
- ✅ Contabilidad automática
- ✅ Cambio de período automático

---

**¿Listo para instalar?**
Sube los 2 archivos y ejecuta el instalador. El sistema estará completo.
