<?php
/**
 * CONECTA ERP - Sistema de Internacionalización (i18n)
 * Soporte para 8 idiomas
 */

class I18n {
    private static $instance = null;
    private $lang = 'es';
    private $translations = [];
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadLanguage($this->lang);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establecer idioma
     */
    public function setLanguage($lang) {
        if (in_array($lang, ['es', 'en', 'pt', 'fr', 'de', 'it', 'ru', 'zh'])) {
            $this->lang = $lang;
            $this->loadLanguage($lang);
            $_SESSION['language'] = $lang;
        }
    }

    /**
     * Obtener idioma actual
     */
    public function getLanguage() {
        return $this->lang;
    }

    /**
     * Cargar traducciones desde BD
     */
    private function loadLanguage($lang) {
        $column = "lang_" . $lang;

        try {
            $results = $this->db->fetchAll("SELECT key_text, {$column} as translation FROM translations");

            foreach ($results as $row) {
                $this->translations[$row['key_text']] = $row['translation'];
            }
        } catch (Exception $e) {
            // Si falla, usar traducciones por defecto
            $this->translations = $this->getDefaultTranslations($lang);
        }
    }

    /**
     * Traducir texto
     */
    public function translate($key, $replacements = []) {
        $text = $this->translations[$key] ?? $key;

        // Reemplazar placeholders {variable}
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace('{' . $placeholder . '}', $value, $text);
        }

        return $text;
    }

    /**
     * Alias corto para translate
     */
    public function t($key, $replacements = []) {
        return $this->translate($key, $replacements);
    }

    /**
     * Traducciones por defecto si no hay BD
     */
    private function getDefaultTranslations($lang) {
        $translations = [
            'es' => [
                'welcome' => 'Bienvenido',
                'dashboard' => 'Panel de Control',
                'logout' => 'Cerrar Sesión',
                'login' => 'Iniciar Sesión',
                'register' => 'Registrarse',
                'email' => 'Correo Electrónico',
                'password' => 'Contraseña',
                'company_name' => 'Nombre de Empresa',
                'tax_id' => 'RUT/NIT/RFC',
                'country' => 'País',
                'phone' => 'Teléfono',
                'save' => 'Guardar',
                'cancel' => 'Cancelar',
                'edit' => 'Editar',
                'delete' => 'Eliminar',
                'search' => 'Buscar',
                'loading' => 'Cargando...',
                'error' => 'Error',
                'success' => 'Éxito'
            ],
            'en' => [
                'welcome' => 'Welcome',
                'dashboard' => 'Dashboard',
                'logout' => 'Logout',
                'login' => 'Login',
                'register' => 'Register',
                'email' => 'Email',
                'password' => 'Password',
                'company_name' => 'Company Name',
                'tax_id' => 'Tax ID',
                'country' => 'Country',
                'phone' => 'Phone',
                'save' => 'Save',
                'cancel' => 'Cancel',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'search' => 'Search',
                'loading' => 'Loading...',
                'error' => 'Error',
                'success' => 'Success'
            ],
            'pt' => [
                'welcome' => 'Bem-vindo',
                'dashboard' => 'Painel de Controle',
                'logout' => 'Sair',
                'login' => 'Entrar',
                'register' => 'Registrar',
                'email' => 'E-mail',
                'password' => 'Senha',
                'company_name' => 'Nome da Empresa',
                'tax_id' => 'CPF/CNPJ',
                'country' => 'País',
                'phone' => 'Telefone',
                'save' => 'Salvar',
                'cancel' => 'Cancelar',
                'edit' => 'Editar',
                'delete' => 'Excluir',
                'search' => 'Buscar',
                'loading' => 'Carregando...',
                'error' => 'Erro',
                'success' => 'Sucesso'
            ]
        ];

        return $translations[$lang] ?? $translations['es'];
    }
}

/**
 * Función helper global para traducción
 */
function __($key, $replacements = []) {
    return I18n::getInstance()->translate($key, $replacements);
}

/**
 * Obtener configuración de país
 */
function getCountryConfig($countryCode) {
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM countries WHERE code = ?", [$countryCode]);
}

/**
 * Obtener todos los países activos
 */
function getActiveCountries($lang = 'es') {
    $db = Database::getInstance();
    $nameColumn = "name_" . $lang;
    return $db->fetchAll("SELECT code, {$nameColumn} as name, currency_code, tax_id_name, phone_code FROM countries WHERE is_active = 1 ORDER BY {$nameColumn}");
}
