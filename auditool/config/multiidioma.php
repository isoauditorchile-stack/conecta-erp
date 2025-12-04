<?php
/**
 * AUDITOR PRO - Sistema Multi-ISO
 * Configuración Multi-Idioma
 *
 * Idiomas soportados: Español (es), English (en), Português (pt)
 */

// Obtener idioma de la sesión o usar el predeterminado
$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'es';

// Diccionario de traducciones
$translations = [
    // Navegación y menús
    'home' => [
        'es' => 'Inicio',
        'en' => 'Home',
        'pt' => 'Início'
    ],
    'dashboard' => [
        'es' => 'Panel de Control',
        'en' => 'Dashboard',
        'pt' => 'Painel de Controle'
    ],
    'logout' => [
        'es' => 'Cerrar Sesión',
        'en' => 'Logout',
        'pt' => 'Sair'
    ],
    'back' => [
        'es' => 'Volver',
        'en' => 'Back',
        'pt' => 'Voltar'
    ],

    // Acciones CRUD
    'add' => [
        'es' => 'Agregar',
        'en' => 'Add',
        'pt' => 'Adicionar'
    ],
    'edit' => [
        'es' => 'Editar',
        'en' => 'Edit',
        'pt' => 'Editar'
    ],
    'delete' => [
        'es' => 'Eliminar',
        'en' => 'Delete',
        'pt' => 'Excluir'
    ],
    'save' => [
        'es' => 'Guardar',
        'en' => 'Save',
        'pt' => 'Salvar'
    ],
    'cancel' => [
        'es' => 'Cancelar',
        'en' => 'Cancel',
        'pt' => 'Cancelar'
    ],
    'download' => [
        'es' => 'Descargar',
        'en' => 'Download',
        'pt' => 'Baixar'
    ],
    'export' => [
        'es' => 'Exportar',
        'en' => 'Export',
        'pt' => 'Exportar'
    ],
    'print' => [
        'es' => 'Imprimir',
        'en' => 'Print',
        'pt' => 'Imprimir'
    ],
    'search' => [
        'es' => 'Buscar',
        'en' => 'Search',
        'pt' => 'Pesquisar'
    ],
    'filter' => [
        'es' => 'Filtrar',
        'en' => 'Filter',
        'pt' => 'Filtrar'
    ],

    // Módulos ISO 27001
    'assets' => [
        'es' => 'Activos de Información',
        'en' => 'Information Assets',
        'pt' => 'Ativos de Informação'
    ],
    'risks' => [
        'es' => 'Análisis de Riesgos',
        'en' => 'Risk Analysis',
        'pt' => 'Análise de Riscos'
    ],
    'controls' => [
        'es' => 'Controles Anexo A',
        'en' => 'Annex A Controls',
        'pt' => 'Controles Anexo A'
    ],
    'incidents' => [
        'es' => 'Incidentes de Seguridad',
        'en' => 'Security Incidents',
        'pt' => 'Incidentes de Segurança'
    ],
    'policies' => [
        'es' => 'Políticas y Normas',
        'en' => 'Policies and Standards',
        'pt' => 'Políticas e Normas'
    ],
    'documents' => [
        'es' => 'Biblioteca de Documentos',
        'en' => 'Document Library',
        'pt' => 'Biblioteca de Documentos'
    ],
    'procedures' => [
        'es' => 'Procedimientos',
        'en' => 'Procedures',
        'pt' => 'Procedimentos'
    ],
    'formats' => [
        'es' => 'Formatos y Plantillas',
        'en' => 'Formats and Templates',
        'pt' => 'Formatos e Modelos'
    ],

    // Estados y niveles
    'status' => [
        'es' => 'Estado',
        'en' => 'Status',
        'pt' => 'Status'
    ],
    'active' => [
        'es' => 'Activo',
        'en' => 'Active',
        'pt' => 'Ativo'
    ],
    'inactive' => [
        'es' => 'Inactivo',
        'en' => 'Inactive',
        'pt' => 'Inativo'
    ],
    'pending' => [
        'es' => 'Pendiente',
        'en' => 'Pending',
        'pt' => 'Pendente'
    ],
    'completed' => [
        'es' => 'Completado',
        'en' => 'Completed',
        'pt' => 'Concluído'
    ],
    'in_progress' => [
        'es' => 'En Progreso',
        'en' => 'In Progress',
        'pt' => 'Em Andamento'
    ],

    // Niveles de riesgo
    'critical' => [
        'es' => 'Crítico',
        'en' => 'Critical',
        'pt' => 'Crítico'
    ],
    'high' => [
        'es' => 'Alto',
        'en' => 'High',
        'pt' => 'Alto'
    ],
    'medium' => [
        'es' => 'Medio',
        'en' => 'Medium',
        'pt' => 'Médio'
    ],
    'low' => [
        'es' => 'Bajo',
        'en' => 'Low',
        'pt' => 'Baixo'
    ],
    'very_low' => [
        'es' => 'Muy Bajo',
        'en' => 'Very Low',
        'pt' => 'Muito Baixo'
    ],

    // Campos comunes
    'name' => [
        'es' => 'Nombre',
        'en' => 'Name',
        'pt' => 'Nome'
    ],
    'description' => [
        'es' => 'Descripción',
        'en' => 'Description',
        'pt' => 'Descrição'
    ],
    'date' => [
        'es' => 'Fecha',
        'en' => 'Date',
        'pt' => 'Data'
    ],
    'user' => [
        'es' => 'Usuario',
        'en' => 'User',
        'pt' => 'Usuário'
    ],
    'company' => [
        'es' => 'Empresa',
        'en' => 'Company',
        'pt' => 'Empresa'
    ],
    'category' => [
        'es' => 'Categoría',
        'en' => 'Category',
        'pt' => 'Categoria'
    ],
    'type' => [
        'es' => 'Tipo',
        'en' => 'Type',
        'pt' => 'Tipo'
    ],
    'owner' => [
        'es' => 'Responsable',
        'en' => 'Owner',
        'pt' => 'Responsável'
    ],

    // Mensajes
    'success_save' => [
        'es' => 'Registro guardado exitosamente',
        'en' => 'Record saved successfully',
        'pt' => 'Registro salvo com sucesso'
    ],
    'success_delete' => [
        'es' => 'Registro eliminado exitosamente',
        'en' => 'Record deleted successfully',
        'pt' => 'Registro excluído com sucesso'
    ],
    'error_save' => [
        'es' => 'Error al guardar el registro',
        'en' => 'Error saving record',
        'pt' => 'Erro ao salvar registro'
    ],
    'error_delete' => [
        'es' => 'Error al eliminar el registro',
        'en' => 'Error deleting record',
        'pt' => 'Erro ao excluir registro'
    ],
    'confirm_delete' => [
        'es' => '¿Está seguro de eliminar este registro?',
        'en' => 'Are you sure you want to delete this record?',
        'pt' => 'Tem certeza que deseja excluir este registro?'
    ],
    'no_data' => [
        'es' => 'No hay datos disponibles',
        'en' => 'No data available',
        'pt' => 'Nenhum dado disponível'
    ],

    // Seguridad de la Información
    'confidentiality' => [
        'es' => 'Confidencialidad',
        'en' => 'Confidentiality',
        'pt' => 'Confidencialidade'
    ],
    'integrity' => [
        'es' => 'Integridad',
        'en' => 'Integrity',
        'pt' => 'Integridade'
    ],
    'availability' => [
        'es' => 'Disponibilidad',
        'en' => 'Availability',
        'pt' => 'Disponibilidade'
    ],

    // ISO 22301
    'business_continuity' => [
        'es' => 'Continuidad del Negocio',
        'en' => 'Business Continuity',
        'pt' => 'Continuidade do Negócio'
    ],
    'bia' => [
        'es' => 'Análisis de Impacto al Negocio',
        'en' => 'Business Impact Analysis',
        'pt' => 'Análise de Impacto nos Negócios'
    ],

    // Auditorías
    'audit' => [
        'es' => 'Auditoría',
        'en' => 'Audit',
        'pt' => 'Auditoria'
    ],
    'non_conformity' => [
        'es' => 'No Conformidad',
        'en' => 'Non-Conformity',
        'pt' => 'Não Conformidade'
    ],
    'corrective_action' => [
        'es' => 'Acción Correctiva',
        'en' => 'Corrective Action',
        'pt' => 'Ação Corretiva'
    ]
];

/**
 * Función para obtener traducción
 * @param string $key Clave de traducción
 * @param string $lang Código de idioma (es, en, pt)
 * @return string Texto traducido
 */
function translate($key, $lang = null) {
    global $translations, $language;

    if ($lang === null) {
        $lang = $language;
    }

    if (isset($translations[$key][$lang])) {
        return $translations[$key][$lang];
    }

    // Si no existe la traducción, devolver la clave
    return $key;
}

/**
 * Función abreviada para traducir
 */
function t($key, $lang = null) {
    return translate($key, $lang);
}

/**
 * Obtener todos los idiomas soportados
 */
function getSupportedLanguages() {
    return json_decode(SUPPORTED_LANGUAGES, true);
}

/**
 * Cambiar idioma de la sesión
 */
function changeLanguage($lang) {
    if (in_array($lang, ['es', 'en', 'pt'])) {
        $_SESSION['language'] = $lang;
        return true;
    }
    return false;
}
?>
