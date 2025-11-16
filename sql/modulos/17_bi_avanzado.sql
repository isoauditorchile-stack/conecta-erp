-- =====================================================
-- MÓDULO 12 EXTENDIDO: BUSINESS INTELLIGENCE AVANZADO
-- 3 submódulos adicionales
-- =====================================================

USE conectae_conectaerpbd;

-- =====================================================
-- Submódulo BI 16: Machine Learning & Predicciones
-- =====================================================

-- Tabla: Modelos Predictivos
CREATE TABLE IF NOT EXISTS `bi_modelos_predictivos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo_modelo` ENUM('regresion_lineal','clasificacion','clustering','series_temporales','neural_network') DEFAULT 'regresion_lineal',
  `objetivo` VARCHAR(255) NOT NULL COMMENT 'Ej: Predicción de ventas, Churn de clientes',
  `variables_input` TEXT COMMENT 'JSON array de variables independientes',
  `variable_target` VARCHAR(100) COMMENT 'Variable a predecir',
  `algoritmo` VARCHAR(100),
  `parametros` TEXT COMMENT 'JSON con hiperparámetros',
  `dataset_entrenamiento` TEXT COMMENT 'Query SQL o referencia a datos',
  `fecha_entrenamiento` DATETIME,
  `metricas_precision` TEXT COMMENT 'JSON: accuracy, R2, MAE, RMSE, etc',
  `estado` ENUM('entrenamiento','activo','obsoleto','error') DEFAULT 'entrenamiento',
  `version` INT(11) DEFAULT 1,
  `creado_por` INT(11),
  `notas` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_modelo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Predicciones Generadas
CREATE TABLE IF NOT EXISTS `bi_predicciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `modelo_id` INT(11) NOT NULL,
  `fecha_prediccion` DATETIME NOT NULL,
  `periodo_predicho` VARCHAR(50) COMMENT 'Ej: 2025-02, Q1-2025',
  `valor_predicho` DECIMAL(15,2),
  `intervalo_confianza_min` DECIMAL(15,2),
  `intervalo_confianza_max` DECIMAL(15,2),
  `probabilidad` DECIMAL(5,4) COMMENT 'Para modelos de clasificación',
  `valor_real` DECIMAL(15,2) COMMENT 'Valor real cuando esté disponible',
  `error_prediccion` DECIMAL(15,2) GENERATED ALWAYS AS (ABS(`valor_real` - `valor_predicho`)) STORED,
  `inputs_usados` TEXT COMMENT 'JSON con valores de variables input',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_modelo` (`modelo_id`),
  KEY `idx_periodo` (`periodo_predicho`),
  CONSTRAINT `fk_pred_modelo` FOREIGN KEY (`modelo_id`) REFERENCES `bi_modelos_predictivos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Submódulo BI 17: Analytics Avanzado en Tiempo Real
-- =====================================================

-- Tabla: Métricas en Tiempo Real
CREATE TABLE IF NOT EXISTS `bi_metricas_tiempo_real` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `metrica` VARCHAR(100) NOT NULL,
  `categoria` VARCHAR(50) COMMENT 'ventas, inventario, produccion, etc',
  `valor` DECIMAL(15,2) NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `metadata` TEXT COMMENT 'JSON con datos adicionales',
  PRIMARY KEY (`id`),
  KEY `idx_empresa_metrica` (`empresa_id`, `metrica`),
  KEY `idx_timestamp` (`timestamp`),
  CONSTRAINT `fk_metrica_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Alertas de BI
CREATE TABLE IF NOT EXISTS `bi_alertas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `nombre_alerta` VARCHAR(255) NOT NULL,
  `tipo` ENUM('umbral','tendencia','anomalia','prediccion') DEFAULT 'umbral',
  `metrica_monitoreada` VARCHAR(100) NOT NULL,
  `condicion` TEXT NOT NULL COMMENT 'Expresión lógica o query',
  `umbral_valor` DECIMAL(15,2),
  `umbral_operador` ENUM('mayor','menor','igual','entre','fuera_rango'),
  `gravedad` ENUM('info','warning','error','critico') DEFAULT 'warning',
  `activa` TINYINT(1) DEFAULT 1,
  `notificar_usuarios` TEXT COMMENT 'JSON array de user IDs',
  `notificar_email` TEXT COMMENT 'JSON array de emails',
  `frecuencia_check_minutos` INT(11) DEFAULT 15,
  `ultima_evaluacion` DATETIME,
  `ultima_activacion` DATETIME,
  `total_activaciones` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_activa` (`activa`),
  CONSTRAINT `fk_alerta_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Historial de Activaciones de Alertas
CREATE TABLE IF NOT EXISTS `bi_alerta_historial` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `alerta_id` INT(11) NOT NULL,
  `fecha_activacion` DATETIME NOT NULL,
  `valor_detectado` DECIMAL(15,2),
  `mensaje` TEXT,
  `resuelto` TINYINT(1) DEFAULT 0,
  `resuelto_por` INT(11),
  `fecha_resolucion` DATETIME,
  `notas_resolucion` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_alerta` (`alerta_id`),
  KEY `idx_fecha` (`fecha_activacion`),
  CONSTRAINT `fk_hist_alerta` FOREIGN KEY (`alerta_id`) REFERENCES `bi_alertas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Submódulo BI 18: Análisis de Comportamiento
-- =====================================================

-- Tabla: Segmentos de Clientes (RFM Analysis)
CREATE TABLE IF NOT EXISTS `bi_segmentos_clientes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `cliente_id` INT(11) NOT NULL,
  `fecha_calculo` DATE NOT NULL,
  `recency_days` INT(11) COMMENT 'Días desde última compra',
  `frequency_count` INT(11) COMMENT 'Cantidad de compras',
  `monetary_value` DECIMAL(15,2) COMMENT 'Valor total de compras',
  `r_score` INT(1) COMMENT 'Score 1-5 para Recency',
  `f_score` INT(1) COMMENT 'Score 1-5 para Frequency',
  `m_score` INT(1) COMMENT 'Score 1-5 para Monetary',
  `rfm_score` INT(3) GENERATED ALWAYS AS (`r_score` * 100 + `f_score` * 10 + `m_score`) STORED,
  `segmento` VARCHAR(50) COMMENT 'Champions, Loyal, At Risk, etc',
  `valor_vida_estimado` DECIMAL(15,2) COMMENT 'CLV - Customer Lifetime Value',
  `probabilidad_churn` DECIMAL(5,4) COMMENT 'Probabilidad de abandono',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa_cliente` (`empresa_id`, `cliente_id`),
  KEY `idx_segmento` (`segmento`),
  KEY `idx_fecha` (`fecha_calculo`),
  CONSTRAINT `fk_segmento_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_segmento_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Análisis de Canasta (Market Basket Analysis)
CREATE TABLE IF NOT EXISTS `bi_analisis_canasta` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `producto_a_id` INT(11) NOT NULL,
  `producto_b_id` INT(11) NOT NULL,
  `fecha_analisis` DATE NOT NULL,
  `support` DECIMAL(5,4) COMMENT 'Soporte: % transacciones con ambos productos',
  `confidence` DECIMAL(5,4) COMMENT 'Confianza: P(B|A)',
  `lift` DECIMAL(10,4) COMMENT 'Lift: Fuerza de asociación',
  `transacciones_con_ambos` INT(11),
  `transacciones_con_a` INT(11),
  `transacciones_con_b` INT(11),
  `transacciones_totales` INT(11),
  `recomendacion_activa` TINYINT(1) DEFAULT 0 COMMENT 'Usar para cross-selling',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_productos_fecha` (`empresa_id`, `producto_a_id`, `producto_b_id`, `fecha_analisis`),
  KEY `idx_lift` (`lift` DESC),
  CONSTRAINT `fk_canasta_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_canasta_prod_a` FOREIGN KEY (`producto_a_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_canasta_prod_b` FOREIGN KEY (`producto_b_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: Patrones de Comportamiento
CREATE TABLE IF NOT EXISTS `bi_patrones_comportamiento` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) NOT NULL,
  `tipo_patron` ENUM('navegacion','compra','abandono','recompra','estacionalidad') DEFAULT 'compra',
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `query_deteccion` TEXT COMMENT 'Query SQL para detectar el patrón',
  `frecuencia_ocurrencia` DECIMAL(5,2) COMMENT '% de veces que ocurre',
  `impacto_ventas` DECIMAL(15,2),
  `activo` TINYINT(1) DEFAULT 1,
  `acciones_sugeridas` TEXT,
  `ultima_evaluacion` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  CONSTRAINT `fk_patron_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTAS AVANZADAS DE BI
-- =====================================================

-- Vista: Precisión de Modelos Predictivos
CREATE OR REPLACE VIEW `v_bi_precision_modelos` AS
SELECT
  m.id as modelo_id,
  m.empresa_id,
  m.nombre,
  m.tipo_modelo,
  m.estado,
  COUNT(p.id) as total_predicciones,
  COUNT(CASE WHEN p.valor_real IS NOT NULL THEN 1 END) as predicciones_validadas,
  AVG(p.error_prediccion) as error_promedio,
  STDDEV(p.error_prediccion) as desviacion_error,
  MIN(p.error_prediccion) as error_minimo,
  MAX(p.error_prediccion) as error_maximo
FROM bi_modelos_predictivos m
LEFT JOIN bi_predicciones p ON m.id = p.modelo_id
GROUP BY m.id;

-- Vista: Resumen de Segmentación RFM
CREATE OR REPLACE VIEW `v_bi_resumen_rfm` AS
SELECT
  empresa_id,
  segmento,
  COUNT(*) as cantidad_clientes,
  AVG(recency_days) as recency_promedio,
  AVG(frequency_count) as frequency_promedio,
  AVG(monetary_value) as monetary_promedio,
  SUM(monetary_value) as valor_total_segmento,
  AVG(valor_vida_estimado) as clv_promedio,
  AVG(probabilidad_churn) as churn_promedio
FROM bi_segmentos_clientes
WHERE fecha_calculo = (SELECT MAX(fecha_calculo) FROM bi_segmentos_clientes)
GROUP BY empresa_id, segmento;

-- Vista: Top Asociaciones de Productos
CREATE OR REPLACE VIEW `v_bi_top_asociaciones` AS
SELECT
  ac.empresa_id,
  pa.nombre as producto_a,
  pb.nombre as producto_b,
  ac.support,
  ac.confidence,
  ac.lift,
  ac.transacciones_con_ambos,
  ac.recomendacion_activa
FROM bi_analisis_canasta ac
INNER JOIN productos pa ON ac.producto_a_id = pa.id
INNER JOIN productos pb ON ac.producto_b_id = pb.id
WHERE ac.fecha_analisis = (SELECT MAX(fecha_analisis) FROM bi_analisis_canasta)
  AND ac.lift > 1.0
ORDER BY ac.lift DESC, ac.confidence DESC;

-- Vista: Alertas Activas con Última Activación
CREATE OR REPLACE VIEW `v_bi_alertas_activas` AS
SELECT
  a.id,
  a.empresa_id,
  a.nombre_alerta,
  a.tipo,
  a.metrica_monitoreada,
  a.gravedad,
  a.ultima_evaluacion,
  a.ultima_activacion,
  a.total_activaciones,
  ah.valor_detectado as ultimo_valor_detectado,
  ah.resuelto
FROM bi_alertas a
LEFT JOIN bi_alerta_historial ah ON a.id = ah.alerta_id
  AND ah.id = (SELECT MAX(id) FROM bi_alerta_historial WHERE alerta_id = a.id)
WHERE a.activa = 1;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

-- Procedimiento: Calcular Segmentación RFM
DELIMITER $$
CREATE PROCEDURE `sp_calcular_rfm`(IN p_empresa_id INT)
BEGIN
  DECLARE v_fecha_calculo DATE;
  SET v_fecha_calculo = CURDATE();

  -- Eliminar cálculos anteriores del día
  DELETE FROM bi_segmentos_clientes
  WHERE empresa_id = p_empresa_id AND fecha_calculo = v_fecha_calculo;

  -- Calcular RFM
  INSERT INTO bi_segmentos_clientes (
    empresa_id, cliente_id, fecha_calculo,
    recency_days, frequency_count, monetary_value,
    r_score, f_score, m_score, segmento, valor_vida_estimado, probabilidad_churn
  )
  SELECT
    p_empresa_id,
    c.id as cliente_id,
    v_fecha_calculo,
    DATEDIFF(CURDATE(), MAX(f.fecha_emision)) as recency_days,
    COUNT(f.id) as frequency_count,
    SUM(f.total) as monetary_value,
    -- R Score (invertido: menor días = mejor score)
    CASE
      WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 30 THEN 5
      WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 90 THEN 4
      WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 180 THEN 3
      WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 365 THEN 2
      ELSE 1
    END as r_score,
    -- F Score
    CASE
      WHEN COUNT(f.id) >= 20 THEN 5
      WHEN COUNT(f.id) >= 10 THEN 4
      WHEN COUNT(f.id) >= 5 THEN 3
      WHEN COUNT(f.id) >= 2 THEN 2
      ELSE 1
    END as f_score,
    -- M Score
    CASE
      WHEN SUM(f.total) >= 10000000 THEN 5
      WHEN SUM(f.total) >= 5000000 THEN 4
      WHEN SUM(f.total) >= 1000000 THEN 3
      WHEN SUM(f.total) >= 500000 THEN 2
      ELSE 1
    END as m_score,
    -- Segmento (simplificado)
    CASE
      WHEN (CASE WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 30 THEN 5 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 90 THEN 4 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 180 THEN 3 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 365 THEN 2 ELSE 1 END) >= 4
        AND (CASE WHEN COUNT(f.id) >= 20 THEN 5 WHEN COUNT(f.id) >= 10 THEN 4 WHEN COUNT(f.id) >= 5 THEN 3 WHEN COUNT(f.id) >= 2 THEN 2 ELSE 1 END) >= 4 THEN 'Champions'
      WHEN (CASE WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 30 THEN 5 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 90 THEN 4 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 180 THEN 3 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 365 THEN 2 ELSE 1 END) >= 3 THEN 'Loyal'
      WHEN (CASE WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 30 THEN 5 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 90 THEN 4 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 180 THEN 3 WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) <= 365 THEN 2 ELSE 1 END) = 2 THEN 'At Risk'
      ELSE 'Lost'
    END as segmento,
    -- CLV estimado (valor promedio * frecuencia * 12 meses)
    (SUM(f.total) / COUNT(f.id)) * COUNT(f.id) * 12 as valor_vida_estimado,
    -- Probabilidad de churn (basado en recency)
    CASE
      WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) > 365 THEN 0.9
      WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) > 180 THEN 0.6
      WHEN DATEDIFF(CURDATE(), MAX(f.fecha_emision)) > 90 THEN 0.3
      ELSE 0.1
    END as probabilidad_churn
  FROM clientes c
  INNER JOIN facturas f ON c.id = f.cliente_id
  WHERE c.empresa_id = p_empresa_id
    AND f.estado = 'pagada'
  GROUP BY c.id;

END$$
DELIMITER ;

-- Procedimiento: Calcular Market Basket Analysis
DELIMITER $$
CREATE PROCEDURE `sp_calcular_market_basket`(IN p_empresa_id INT, IN p_min_support DECIMAL(5,4))
BEGIN
  DECLARE v_fecha_analisis DATE;
  DECLARE v_total_transacciones INT;

  SET v_fecha_analisis = CURDATE();

  -- Contar total de transacciones
  SELECT COUNT(DISTINCT id) INTO v_total_transacciones
  FROM facturas
  WHERE empresa_id = p_empresa_id
    AND estado = 'pagada'
    AND fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 90 DAY);

  -- Eliminar análisis anterior del día
  DELETE FROM bi_analisis_canasta
  WHERE empresa_id = p_empresa_id AND fecha_analisis = v_fecha_analisis;

  -- Calcular asociaciones de productos
  INSERT INTO bi_analisis_canasta (
    empresa_id, producto_a_id, producto_b_id, fecha_analisis,
    support, confidence, lift,
    transacciones_con_ambos, transacciones_con_a, transacciones_con_b, transacciones_totales,
    recomendacion_activa
  )
  SELECT
    p_empresa_id,
    a.producto_id as producto_a_id,
    b.producto_id as producto_b_id,
    v_fecha_analisis,
    -- Support
    COUNT(DISTINCT a.factura_id) / v_total_transacciones as support,
    -- Confidence
    COUNT(DISTINCT a.factura_id) / (SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = a.producto_id) as confidence,
    -- Lift
    (COUNT(DISTINCT a.factura_id) / v_total_transacciones) /
    ((SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = a.producto_id) / v_total_transacciones *
     (SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = b.producto_id) / v_total_transacciones) as lift,
    COUNT(DISTINCT a.factura_id) as transacciones_con_ambos,
    (SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = a.producto_id) as transacciones_con_a,
    (SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = b.producto_id) as transacciones_con_b,
    v_total_transacciones,
    -- Activar recomendación si lift > 1.5 y confidence > 0.3
    CASE WHEN
      (COUNT(DISTINCT a.factura_id) / v_total_transacciones) /
      ((SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = a.producto_id) / v_total_transacciones *
       (SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = b.producto_id) / v_total_transacciones) > 1.5
      AND COUNT(DISTINCT a.factura_id) / (SELECT COUNT(DISTINCT factura_id) FROM factura_detalle WHERE producto_id = a.producto_id) > 0.3
    THEN 1 ELSE 0 END
  FROM factura_detalle a
  INNER JOIN factura_detalle b ON a.factura_id = b.factura_id AND a.producto_id < b.producto_id
  INNER JOIN facturas f ON a.factura_id = f.id
  WHERE f.empresa_id = p_empresa_id
    AND f.estado = 'pagada'
    AND f.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
  GROUP BY a.producto_id, b.producto_id
  HAVING support >= p_min_support;

END$$
DELIMITER ;

-- =====================================================
-- EVENTOS PROGRAMADOS
-- =====================================================

-- Evento: Actualizar segmentación RFM diariamente
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_actualizar_rfm`
ON SCHEDULE EVERY 1 DAY
STARTS '2025-01-01 03:00:00'
DO
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_empresa_id INT;
  DECLARE cur CURSOR FOR SELECT id FROM empresas WHERE activo = 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_empresa_id;
    IF done THEN
      LEAVE read_loop;
    END IF;

    CALL sp_calcular_rfm(v_empresa_id);
  END LOOP;

  CLOSE cur;
END$$
DELIMITER ;

-- Evento: Actualizar market basket semanalmente
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_actualizar_market_basket`
ON SCHEDULE EVERY 1 WEEK
STARTS '2025-01-01 04:00:00'
DO
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_empresa_id INT;
  DECLARE cur CURSOR FOR SELECT id FROM empresas WHERE activo = 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_empresa_id;
    IF done THEN
      LEAVE read_loop;
    END IF;

    -- Support mínimo de 1% (0.01)
    CALL sp_calcular_market_basket(v_empresa_id, 0.01);
  END LOOP;

  CLOSE cur;
END$$
DELIMITER ;
