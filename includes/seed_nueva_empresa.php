<?php
/**
 * CONECTA ERP - Seed para Nueva Empresa
 *
 * Este script se ejecuta automáticamente cuando se registra una nueva empresa
 * Crea todos los datos maestros base para que la empresa pueda empezar a operar
 *
 * MULTIEMPRESA: Cada empresa tiene su propio conjunto de datos completamente aislados
 */

function seedNuevaEmpresa($company_id, $owner_user_id) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // Leer el archivo SQL de seed
        $sql_file = file_get_contents(__DIR__ . '/../database/SEED_empresa_nueva.sql');

        if (!$sql_file) {
            throw new Exception("No se pudo leer el archivo SEED_empresa_nueva.sql");
        }

        // Reemplazar placeholders
        $current_year = date('Y');
        $sql_file = str_replace('{{COMPANY_ID}}', $company_id, $sql_file);
        $sql_file = str_replace('{{OWNER_USER_ID}}', $owner_user_id, $sql_file);
        $sql_file = str_replace('{{CURRENT_YEAR}}', $current_year, $sql_file);

        // Dividir en statements individuales
        $statements = [];
        $current_statement = '';
        $lines = explode("\n", $sql_file);

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar comentarios y líneas vacías
            if (empty($line) || strpos($line, '--') === 0) {
                continue;
            }

            $current_statement .= $line . ' ';

            // Si termina en punto y coma, es el fin del statement
            if (substr($line, -1) === ';') {
                $statements[] = trim($current_statement);
                $current_statement = '';
            }
        }

        // Ejecutar cada statement
        $pdo->beginTransaction();

        $executed = 0;
        $errors = [];

        foreach ($statements as $statement) {
            if (empty($statement)) continue;

            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Algunas tablas pueden no existir aún, es normal
                // Solo registramos el error pero continuamos
                $errors[] = [
                    'statement' => substr($statement, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }

        $pdo->commit();

        // Log del resultado
        error_log("Seed ejecutado para empresa $company_id: $executed statements exitosos, " . count($errors) . " errores");

        return [
            'success' => true,
            'executed' => $executed,
            'errors' => $errors
        ];

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Error en seedNuevaEmpresa: " . $e->getMessage());

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Asignar módulos según el plan
 */
function asignarModulosSegunPlan($user_id, $company_id, $plan_code) {
    try {
        $db = Database::getInstance();

        // Obtener plan
        $plan = $db->fetchOne("SELECT * FROM planes WHERE plan_code = ?", [$plan_code]);

        if (!$plan) {
            throw new Exception("Plan no encontrado: $plan_code");
        }

        // Obtener todos los módulos activos
        $modules = $db->fetchAll("SELECT * FROM modules WHERE is_active = 1 ORDER BY id");

        // Determinar qué módulos incluir
        $modulos_asignar = [];

        if ($plan['plan_code'] === 'TRIAL') {
            // Trial: Solo 1 empresa, max 3 usuarios, 14 módulos
            $modulos_asignar = $modules; // Todos los módulos
        } elseif ($plan['plan_code'] === 'STARTER') {
            // Starter: Módulos básicos (definir cuáles)
            // Por ejemplo: FI, CO, SD, MM, CRM
            $modulos_basicos = ['FI', 'CO', 'SD', 'MM', 'CRM'];
            foreach ($modules as $module) {
                if (in_array($module['code'], $modulos_basicos)) {
                    $modulos_asignar[] = $module;
                }
            }
        } elseif ($plan['plan_code'] === 'PROFESSIONAL') {
            // Professional: Módulos avanzados
            $modulos_prof = ['FI', 'CO', 'SD', 'MM', 'PP', 'QM', 'PM', 'HR', 'CRM', 'SCM'];
            foreach ($modules as $module) {
                if (in_array($module['code'], $modulos_prof)) {
                    $modulos_asignar[] = $module;
                }
            }
        } elseif ($plan['plan_code'] === 'ENTERPRISE' || $plan['plan_code'] === 'CUSTOM') {
            // Enterprise/Custom: TODOS los módulos
            $modulos_asignar = $modules;
        }

        // Obtener submódulos de los módulos asignados
        $permisos_insertados = 0;

        foreach ($modulos_asignar as $module) {
            // Obtener submódulos del módulo
            $submodules = $db->fetchAll(
                "SELECT * FROM submodules WHERE module_id = ? AND is_active = 1",
                [$module['id']]
            );

            // Asignar permisos COMPLETOS (create, read, update, delete) al usuario owner
            foreach ($submodules as $submodule) {
                $db->insert(
                    "INSERT INTO user_permissions (user_id, submodule_id, can_create, can_read, can_update, can_delete)
                     VALUES (?, ?, 1, 1, 1, 1)",
                    [$user_id, $submodule['id']]
                );
                $permisos_insertados++;
            }
        }

        return [
            'success' => true,
            'modulos' => count($modulos_asignar),
            'permisos' => $permisos_insertados
        ];

    } catch (Exception $e) {
        error_log("Error en asignarModulosSegunPlan: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
