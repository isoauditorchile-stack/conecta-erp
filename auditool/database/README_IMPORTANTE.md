# ⚠️ SOLUCIÓN AL ERROR 500 - AUDITOR PRO

## 🔴 PROBLEMA
Los módulos ISO dan **ERROR 500** porque las tablas de la base de datos no tienen todas las columnas necesarias.

## ✅ SOLUCIÓN (2 OPCIONES)

### **OPCIÓN 1: Si las tablas YA EXISTEN (Recomendada)**
Ejecuta el script de migración desde tu navegador:
```
https://isogestion.conectaerp.com/auditool/database/migrate.php
```

Este script agregará las columnas faltantes **sin borrar ningún dato**.

### **OPCIÓN 2: Si las tablas NO EXISTEN o quieres empezar de cero**
Importa el schema completo en phpMyAdmin:

1. Abre phpMyAdmin
2. Selecciona la base de datos: `conectae_isogestionbd`
3. Ve a la pestaña "Importar"
4. Sube el archivo: `/auditool/database/schema_iso27001.sql`
5. Click en "Continuar"

## 📋 COLUMNAS QUE SE AGREGAN

### Tabla `iso27001_assets`:
- `asset_description` (renombrado desde `description`)
- `acquisition_date` (fecha de adquisición)
- `estimated_value` (valor estimado)
- `recovery_time` (tiempo de recuperación)

## ✅ VERIFICACIÓN

Después de ejecutar la migración, verifica que funcione:
1. Ve a: `https://isogestion.conectaerp.com/auditool/iso_27001/activos.php`
2. Si carga SIN ERROR 500 → ¡Todo correcto! ✓

## 🆘 SI PERSISTE EL ERROR

Contacta al soporte con el mensaje de error completo.

---
**AUDITOR PRO by AuditorEx Chile**
