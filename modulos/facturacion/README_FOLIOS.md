# 📋 SISTEMA DE GESTIÓN DE FOLIOS CAF - CONECTA ERP

## 🎯 DESCRIPCIÓN

Sistema completo de gestión de folios autorizados por el SII de Chile para facturación electrónica.

Incluye:
- ✅ Dashboard visual con estado de todos los tipos de documentos (50+ tipos del SII)
- ✅ Carga automática de archivos CAF
- ✅ Alertas de folios bajos/críticos/agotados
- ✅ Estimación de días disponibles según consumo
- ✅ API REST para integración
- ✅ Notificaciones automáticas por email
- ✅ Sistema de monitoreo 24/7

---

## 📁 ESTRUCTURA DE ARCHIVOS

```
modulos/facturacion/
├── folios.php                      # Dashboard principal de folios
├── cargar_caf.php                  # Formulario de carga de CAF
├── api/
│   └── estado_folios.php           # API REST de estado
└── README_FOLIOS.md                # Esta documentación

cron/
└── alertas_folios_bajos.php        # Cron job de alertas diarias
```

---

## 🚀 ACCESO AL SISTEMA

### URL Principal:
```
http://tu-dominio.com/modulos/facturacion/folios.php
```

### Requisitos:
- Usuario autenticado
- Módulo "Ventas" activo en su plan
- Empresa registrada en el sistema

---

## 📊 DASHBOARD DE FOLIOS

### Características:

#### 1. **Estadísticas Generales**
- Total de tipos de documentos
- Total de folios disponibles
- Tipos en estado crítico
- Tipos sin folios

#### 2. **Tabla Detallada**
Para cada tipo de documento muestra:
- **Código:** Código SII del documento (33, 39, 52, etc.)
- **Folios Disponibles:** Cantidad exacta de folios sin usar
- **Estado:** Normal / Bajo / Crítico / Agotado
- **CAFs Activos:** Cantidad de archivos CAF cargados
- **Días Estimados:** Proyección de duración según consumo
- **Última Carga:** Fecha del último CAF cargado

#### 3. **Sistema de Alertas**
Códigos de color:
- 🟢 **Normal:** +100 folios disponibles
- 🟡 **Bajo:** 50-100 folios disponibles
- 🔴 **Crítico:** 1-49 folios disponibles
- ⚫ **Agotado:** 0 folios disponibles

#### 4. **Estimación Inteligente**
El sistema calcula automáticamente:
- Tasa de consumo diaria (documentos/día)
- Días estimados hasta agotar folios
- Fecha aproximada de agotamiento
- Recomendación de cuándo solicitar más folios

---

## 📤 CARGA DE ARCHIVOS CAF

### Paso a Paso:

#### 1. **Obtener Folios del SII**
```
1. Ingresa a https://www4.sii.cl/registrosc/
2. Login con RUT + Clave Tributaria
3. Ir a "Documentos Tributarios Electrónicos"
4. Seleccionar "Solicitar Folios"
5. Elegir:
   - Tipo de documento (33, 39, 52, 61, etc.)
   - Cantidad de folios (recomendado: 1000-5000)
6. Descargar archivo CAF (formato .xml)
```

#### 2. **Cargar CAF en CONECTA ERP**
```
1. Ve a: modulos/facturacion/cargar_caf.php
2. Arrastra el archivo XML o haz clic para seleccionarlo
3. El sistema valida automáticamente:
   ✓ Que sea un archivo CAF válido
   ✓ Que el RUT coincida con tu empresa
   ✓ Que no esté duplicado
4. Click en "Cargar CAF al Sistema"
5. ¡Listo! Los folios están disponibles
```

#### 3. **Validaciones Automáticas**
El sistema verifica:
- ✅ Formato XML válido
- ✅ Estructura CAF correcta
- ✅ RUT de empresa coincide
- ✅ Tipo de documento existe
- ✅ No está duplicado
- ✅ Firma digital válida

---

## 🔌 API REST

### Endpoint:
```
GET /modulos/facturacion/api/estado_folios.php
```

### Parámetros Opcionales:
- `tipo_documento`: Código del documento (ej: 33, 39)
- `alerta`: Nivel de alerta (critico, bajo, agotado)

### Ejemplos de Uso:

#### Obtener Estado de Todos los Tipos
```bash
curl http://tu-dominio.com/modulos/facturacion/api/estado_folios.php
```

#### Filtrar por Tipo de Documento
```bash
curl http://tu-dominio.com/modulos/facturacion/api/estado_folios.php?tipo_documento=33
```

#### Solo Folios Críticos
```bash
curl http://tu-dominio.com/modulos/facturacion/api/estado_folios.php?alerta=critico
```

### Response JSON:
```json
{
  "success": true,
  "data": [
    {
      "codigo": 33,
      "nombre": "Factura Electrónica",
      "folios_disponibles": 450,
      "porcentaje_disponible": 45.0,
      "cantidad_cafs": 2,
      "estado": "bajo",
      "consumo": {
        "documentos_30_dias": 120,
        "tasa_diaria": 4.0,
        "tasa_mensual": 120
      },
      "estimacion": {
        "dias_disponibles": 112,
        "fecha_agotamiento": "2025-05-10",
        "requiere_atencion": false
      }
    }
  ],
  "resumen": {
    "total_tipos_documento": 15,
    "total_folios_disponibles": 5430,
    "tipos_agotados": 0,
    "tipos_criticos": 2,
    "tipos_bajos": 3,
    "tipos_normales": 10,
    "total_alertas": 5
  },
  "alertas": [
    {
      "tipo": "warning",
      "codigo_documento": 33,
      "nombre_documento": "Factura Electrónica",
      "mensaje": "Quedan solo 450 folios para Factura Electrónica",
      "accion_recomendada": "Solicitar más folios al SII en los próximos días",
      "prioridad": "alta"
    }
  ],
  "timestamp": "2025-01-17 10:30:00"
}
```

---

## 📧 SISTEMA DE ALERTAS AUTOMÁTICAS

### Cron Job: `cron/alertas_folios_bajos.php`

#### Configuración:
```bash
# Ejecutar todos los días a las 8:00 AM
crontab -e
0 8 * * * /usr/bin/php /var/www/html/cron/alertas_folios_bajos.php
```

#### ¿Qué Hace?
1. Revisa todas las empresas activas
2. Verifica el estado de folios de cada tipo de documento
3. Identifica documentos en estado: Agotado / Crítico / Bajo
4. Envía email de alerta con:
   - Tabla de folios críticos
   - Nivel de urgencia
   - Instrucciones para solicitar folios
   - Link directo al dashboard

#### Niveles de Alerta:
- **URGENTE:** Folios agotados (0 disponibles)
- **ALTA PRIORIDAD:** Folios críticos (<50 disponibles)
- **ATENCIÓN:** Folios bajos (50-100 disponibles)

#### Email de Ejemplo:
```
Asunto: [URGENTE] Folios Bajos - EMPRESA DEMO S.A.

Se han detectado tipos de documentos con folios agotados:

Tipo Documento    | Código | Disponibles | Estado
------------------|--------|-------------|----------
Factura Electr.   |   33   |      0      | AGOTADO
Boleta Electr.    |   39   |     25      | CRÍTICO
Nota de Crédito   |   61   |     80      | BAJO

Acción Requerida:
✓ Solicitar folios al SII URGENTEMENTE
```

---

## 📚 TIPOS DE DOCUMENTOS SOPORTADOS

### Fase 1 - Esenciales (Más Usados)
- **33** - Factura Electrónica
- **39** - Boleta Electrónica
- **52** - Guía de Despacho Electrónica
- **61** - Nota de Crédito Electrónica

### Fase 2 - Importantes
- **34** - Factura Exenta Electrónica
- **41** - Boleta Exenta Electrónica
- **56** - Nota de Débito Electrónica
- **46** - Factura de Compra Electrónica

### Fase 3 - Exportación
- **110** - Factura de Exportación Electrónica
- **111** - Nota de Débito de Exportación
- **112** - Nota de Crédito de Exportación

### Fase 4 - Especializados
- **43** - Liquidación-Factura Electrónica
- **48** - Comprobante de Pago Electrónico
- **103** - Liquidación
- **906-914** - Documentos especializados

**Total: 50+ tipos de documentos del SII catalogados**

---

## ⚙️ RECOMENDACIONES DE USO

### Cantidad de Folios a Solicitar:

| Tipo Documento | Consumo Típico | Folios Recomendados |
|----------------|----------------|---------------------|
| Factura (33) | Alto | 1,000 - 5,000 |
| Boleta (39) | Muy Alto | 5,000 - 10,000 |
| Guía Despacho (52) | Medio | 1,000 - 3,000 |
| Nota Crédito (61) | Bajo | 500 - 1,000 |
| Nota Débito (56) | Bajo | 500 - 1,000 |

### Cuándo Solicitar Más Folios:
- ⚠️ **Crítico:** Cuando quedan <50 folios
- ⏰ **Preventivo:** Cuando quedan <100 folios
- ✅ **Ideal:** Renovar antes de llegar a 100 folios

### Buenas Prácticas:
1. **Mantener siempre stock:** No esperar a agotar folios
2. **Monitorear semanalmente:** Revisar dashboard cada semana
3. **Cargar CAF inmediatamente:** Apenas descargues del SII
4. **Validar datos empresa:** RUT y razón social correctos
5. **Backup de CAF:** Guardar copia de archivos CAF descargados

---

## 🔧 TROUBLESHOOTING

### Error: "El CAF no pertenece a esta empresa"
**Solución:** Verifica que el RUT de la empresa en CONECTA ERP coincida exactamente con el RUT usado en el SII.

### Error: "Este CAF ya fue cargado previamente"
**Solución:** No puedes cargar el mismo rango de folios dos veces. Si necesitas más folios, solicita un nuevo rango al SII.

### Error: "Tipo de documento no existe en el sistema"
**Solución:** El tipo de documento del CAF no está catalogado. Contacta a soporte.

### No se están enviando alertas por email
**Solución:**
1. Verifica que el cron job esté configurado
2. Revisa la configuración de email en el servidor
3. Verifica que `email_contacto` esté configurado en tabla `empresas`

---

## 📞 SOPORTE

Para consultas o problemas:
- **Email:** soporte@conectaerp.cl
- **Documentación SII:** https://www.sii.cl/factura_electronica/
- **Portal SII:** https://www4.sii.cl/registrosc/

---

## 📝 CHANGELOG

### v1.0.0 (2025-01-17)
- ✅ Dashboard completo de folios
- ✅ Carga automática de CAF
- ✅ Sistema de alertas inteligente
- ✅ API REST
- ✅ Notificaciones por email
- ✅ Soporte para 50+ tipos de documentos del SII
- ✅ Estimación de días disponibles
- ✅ Cron job de alertas diarias

---

**Sistema desarrollado por CONECTA ERP**
**Compatible con SII Chile 2025**
