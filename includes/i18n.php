<?php
/**
 * CONECTA ERP - Internationalization (i18n) System
 * Soporte para 8 idiomas: ES, EN, PT, FR, DE, IT, RU, ZH
 */

class I18n {
    private static $instance = null;
    private $currentLanguage = 'es';
    private $fallbackLanguage = 'es';
    private $translations = [];
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadLanguageFromSession();
        $this->loadTranslations();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cargar idioma desde sesión o cookie
     */
    private function loadLanguageFromSession() {
        // Prioridad: 1. Parámetro GET, 2. Sesión, 3. Cookie, 4. Browser, 5. Default
        if (isset($_GET['lang']) && $this->isValidLanguage($_GET['lang'])) {
            $this->currentLanguage = $_GET['lang'];
            $_SESSION['language'] = $_GET['lang'];
            setcookie('conecta_language', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/');
        } elseif (isset($_SESSION['language']) && $this->isValidLanguage($_SESSION['language'])) {
            $this->currentLanguage = $_SESSION['language'];
        } elseif (isset($_COOKIE['conecta_language']) && $this->isValidLanguage($_COOKIE['conecta_language'])) {
            $this->currentLanguage = $_COOKIE['conecta_language'];
            $_SESSION['language'] = $_COOKIE['conecta_language'];
        } else {
            // Detectar idioma del navegador
            $browserLang = $this->detectBrowserLanguage();
            if ($browserLang) {
                $this->currentLanguage = $browserLang;
            }
        }
    }

    /**
     * Detectar idioma del navegador
     */
    private function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($langs as $lang) {
            $lang = substr($lang, 0, 2);
            if ($this->isValidLanguage($lang)) {
                return $lang;
            }
        }
        return null;
    }

    /**
     * Validar si el idioma está soportado
     */
    private function isValidLanguage($lang) {
        $supportedLanguages = ['es', 'en', 'pt', 'fr', 'de', 'it', 'ru', 'zh'];
        return in_array(strtolower($lang), $supportedLanguages);
    }

    /**
     * Cargar traducciones desde la base de datos
     */
    private function loadTranslations() {
        try {
            // Cargar traducciones del idioma actual
            $translations = $this->db->fetchAll(
                "SELECT translation_key, translation_value FROM translations WHERE language_code = ?",
                [$this->currentLanguage]
            );

            foreach ($translations as $trans) {
                $this->translations[$trans['translation_key']] = $trans['translation_value'];
            }

            // Si no hay traducciones, cargar fallback (español)
            if (empty($this->translations) && $this->currentLanguage !== $this->fallbackLanguage) {
                $fallbackTranslations = $this->db->fetchAll(
                    "SELECT translation_key, translation_value FROM translations WHERE language_code = ?",
                    [$this->fallbackLanguage]
                );

                foreach ($fallbackTranslations as $trans) {
                    $this->translations[$trans['translation_key']] = $trans['translation_value'];
                }
            }

            // Si aún no hay traducciones, usar hardcoded
            if (empty($this->translations)) {
                $this->translations = $this->getHardcodedTranslations();
            }
        } catch (Exception $e) {
            error_log("Error loading translations: " . $e->getMessage());
            // Usar traducciones hardcoded
            $this->translations = $this->getHardcodedTranslations();
        }
    }

    /**
     * Obtener traducciones hardcoded (fallback)
     */
    private function getHardcodedTranslations() {
        $allTranslations = [
            'es' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => 'Sistema de Gestión Empresarial',
                'welcome' => 'Bienvenido',
                'register' => 'Registrarse',
                'login' => 'Iniciar Sesión',
                'logout' => 'Cerrar Sesión',
                'home' => 'Inicio',
                'dashboard' => 'Panel de Control',
                'email' => 'Correo Electrónico',
                'password' => 'Contraseña',
                'confirm_password' => 'Confirmar Contraseña',
                'firstname' => 'Nombre',
                'lastname' => 'Apellido',
                'company_name' => 'Nombre de la Empresa',
                'tax_id' => 'RUT/NIT/RFC',
                'phone' => 'Teléfono',
                'country' => 'País',
                'language' => 'Idioma',
                'save' => 'Guardar',
                'cancel' => 'Cancelar',
                'edit' => 'Editar',
                'delete' => 'Eliminar',
                'search' => 'Buscar',
                'loading' => 'Cargando...',
                'error' => 'Error',
                'success' => 'Éxito',
                'submit' => 'Enviar',
                'back' => 'Volver',
                'next' => 'Siguiente',
                'previous' => 'Anterior',
                'yes' => 'Sí',
                'no' => 'No',
                'or' => 'o',
                'register_title' => 'Crear Nueva Cuenta',
                'register_subtitle' => 'Comienza tu prueba gratuita de 14 días',
                'already_have_account' => '¿Ya tienes una cuenta?',
                'login_here' => 'Inicia sesión aquí',
                'no_account' => '¿No tienes una cuenta?',
                'register_here' => 'Regístrate aquí',
                'generate_password' => 'Generar Contraseña Segura',
                'password_copied' => '¡Contraseña copiada al portapapeles!',
                'choose_plan' => 'Elige tu plan',
                'free_trial' => 'Prueba Gratuita 14 días',
                'then_from' => 'Luego desde',
                'per_month' => '/mes',
                'select_plan' => 'Seleccionar Plan',
                'all_features' => 'Todas las funcionalidades',
                'unlimited_users' => 'Usuarios ilimitados',
                'priority_support' => 'Soporte prioritario',
                'footer_about' => 'Acerca de',
                'footer_features' => 'Características',
                'footer_support' => 'Soporte',
                'footer_legal' => 'Legal',
                'footer_contact' => 'Contacto',
            ],
            'en' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => 'Enterprise Management System',
                'welcome' => 'Welcome',
                'register' => 'Register',
                'login' => 'Login',
                'logout' => 'Logout',
                'home' => 'Home',
                'dashboard' => 'Dashboard',
                'email' => 'Email',
                'password' => 'Password',
                'confirm_password' => 'Confirm Password',
                'firstname' => 'First Name',
                'lastname' => 'Last Name',
                'company_name' => 'Company Name',
                'tax_id' => 'Tax ID',
                'phone' => 'Phone',
                'country' => 'Country',
                'language' => 'Language',
                'save' => 'Save',
                'cancel' => 'Cancel',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'search' => 'Search',
                'loading' => 'Loading...',
                'error' => 'Error',
                'success' => 'Success',
                'submit' => 'Submit',
                'back' => 'Back',
                'next' => 'Next',
                'previous' => 'Previous',
                'yes' => 'Yes',
                'no' => 'No',
                'or' => 'or',
                'register_title' => 'Create New Account',
                'register_subtitle' => 'Start your 14-day free trial',
                'already_have_account' => 'Already have an account?',
                'login_here' => 'Login here',
                'no_account' => 'Don\'t have an account?',
                'register_here' => 'Register here',
                'generate_password' => 'Generate Secure Password',
                'password_copied' => 'Password copied to clipboard!',
                'choose_plan' => 'Choose your plan',
                'free_trial' => '14-Day Free Trial',
                'then_from' => 'Then from',
                'per_month' => '/month',
                'select_plan' => 'Select Plan',
                'all_features' => 'All features',
                'unlimited_users' => 'Unlimited users',
                'priority_support' => 'Priority support',
                'footer_about' => 'About',
                'footer_features' => 'Features',
                'footer_support' => 'Support',
                'footer_legal' => 'Legal',
                'footer_contact' => 'Contact',
            ],
            'pt' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => 'Sistema de Gestão Empresarial',
                'welcome' => 'Bem-vindo',
                'register' => 'Registrar',
                'login' => 'Entrar',
                'logout' => 'Sair',
                'home' => 'Início',
                'dashboard' => 'Painel de Controle',
                'email' => 'E-mail',
                'password' => 'Senha',
                'confirm_password' => 'Confirmar Senha',
                'firstname' => 'Nome',
                'lastname' => 'Sobrenome',
                'company_name' => 'Nome da Empresa',
                'tax_id' => 'CPF/CNPJ',
                'phone' => 'Telefone',
                'country' => 'País',
                'language' => 'Idioma',
                'save' => 'Salvar',
                'cancel' => 'Cancelar',
                'edit' => 'Editar',
                'delete' => 'Excluir',
                'search' => 'Buscar',
                'loading' => 'Carregando...',
                'error' => 'Erro',
                'success' => 'Sucesso',
                'submit' => 'Enviar',
                'back' => 'Voltar',
                'next' => 'Próximo',
                'previous' => 'Anterior',
                'yes' => 'Sim',
                'no' => 'Não',
                'or' => 'ou',
                'register_title' => 'Criar Nova Conta',
                'register_subtitle' => 'Comece seu teste gratuito de 14 dias',
                'already_have_account' => 'Já tem uma conta?',
                'login_here' => 'Entre aqui',
                'no_account' => 'Não tem uma conta?',
                'register_here' => 'Registre-se aqui',
                'generate_password' => 'Gerar Senha Segura',
                'password_copied' => 'Senha copiada para a área de transferência!',
            ],
            'fr' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => 'Système de Gestion d\'Entreprise',
                'welcome' => 'Bienvenue',
                'register' => 'S\'inscrire',
                'login' => 'Se connecter',
                'logout' => 'Se déconnecter',
                'home' => 'Accueil',
                'dashboard' => 'Tableau de bord',
                'email' => 'E-mail',
                'password' => 'Mot de passe',
                'confirm_password' => 'Confirmer le mot de passe',
                'firstname' => 'Prénom',
                'lastname' => 'Nom',
                'company_name' => 'Nom de l\'entreprise',
                'tax_id' => 'Numéro fiscal',
                'phone' => 'Téléphone',
                'country' => 'Pays',
                'language' => 'Langue',
            ],
            'de' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => 'Unternehmensverwaltungssystem',
                'welcome' => 'Willkommen',
                'register' => 'Registrieren',
                'login' => 'Anmelden',
                'logout' => 'Abmelden',
                'home' => 'Startseite',
                'dashboard' => 'Dashboard',
                'email' => 'E-Mail',
                'password' => 'Passwort',
                'confirm_password' => 'Passwort bestätigen',
                'firstname' => 'Vorname',
                'lastname' => 'Nachname',
                'company_name' => 'Firmenname',
                'tax_id' => 'Steuernummer',
                'phone' => 'Telefon',
                'country' => 'Land',
                'language' => 'Sprache',
            ],
            'it' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => 'Sistema di Gestione Aziendale',
                'welcome' => 'Benvenuto',
                'register' => 'Registrati',
                'login' => 'Accedi',
                'logout' => 'Esci',
                'home' => 'Home',
                'dashboard' => 'Pannello di controllo',
                'email' => 'Email',
                'password' => 'Password',
                'confirm_password' => 'Conferma password',
                'firstname' => 'Nome',
                'lastname' => 'Cognome',
                'company_name' => 'Nome azienda',
                'tax_id' => 'Partita IVA',
                'phone' => 'Telefono',
                'country' => 'Paese',
                'language' => 'Lingua',
            ],
            'ru' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => 'Система управления предприятием',
                'welcome' => 'Добро пожаловать',
                'register' => 'Регистрация',
                'login' => 'Войти',
                'logout' => 'Выйти',
                'home' => 'Главная',
                'dashboard' => 'Панель управления',
                'email' => 'Эл. почта',
                'password' => 'Пароль',
                'confirm_password' => 'Подтвердите пароль',
                'firstname' => 'Имя',
                'lastname' => 'Фамилия',
                'company_name' => 'Название компании',
                'tax_id' => 'ИНН',
                'phone' => 'Телефон',
                'country' => 'Страна',
                'language' => 'Язык',
            ],
            'zh' => [
                'app_name' => 'CONECTA ERP',
                'app_slogan' => '企业管理系统',
                'welcome' => '欢迎',
                'register' => '注册',
                'login' => '登录',
                'logout' => '登出',
                'home' => '首页',
                'dashboard' => '仪表板',
                'email' => '电子邮件',
                'password' => '密码',
                'confirm_password' => '确认密码',
                'firstname' => '名字',
                'lastname' => '姓氏',
                'company_name' => '公司名称',
                'tax_id' => '税号',
                'phone' => '电话',
                'country' => '国家',
                'language' => '语言',
            ],
        ];

        return $allTranslations[$this->currentLanguage] ?? $allTranslations['es'];
    }

    /**
     * Obtener traducción
     * @param string $key Clave de traducción
     * @param array $params Parámetros para reemplazar en la traducción
     * @return string Traducción o clave si no existe
     */
    public function get($key, $params = []) {
        $translation = $this->translations[$key] ?? $key;

        // Reemplazar parámetros
        if (!empty($params)) {
            foreach ($params as $paramKey => $paramValue) {
                $translation = str_replace('{' . $paramKey . '}', $paramValue, $translation);
            }
        }

        return $translation;
    }

    /**
     * Obtener idioma actual
     */
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }

    /**
     * Cambiar idioma
     */
    public function setLanguage($lang) {
        if ($this->isValidLanguage($lang)) {
            $this->currentLanguage = $lang;
            $_SESSION['language'] = $lang;
            setcookie('conecta_language', $lang, time() + (365 * 24 * 60 * 60), '/');
            $this->loadTranslations();
            return true;
        }
        return false;
    }

    /**
     * Obtener lista de idiomas soportados
     */
    public function getSupportedLanguages() {
        try {
            return $this->db->fetchAll(
                "SELECT code, name, native_name, flag_emoji FROM languages WHERE is_active = 1 ORDER BY sort_order"
            );
        } catch (Exception $e) {
            // Fallback si la tabla no existe
            return [
                ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'flag_emoji' => '🇪🇸'],
                ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇺🇸'],
                ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português', 'flag_emoji' => '🇧🇷'],
                ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'flag_emoji' => '🇫🇷'],
                ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'flag_emoji' => '🇩🇪'],
                ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'flag_emoji' => '🇮🇹'],
                ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'flag_emoji' => '🇷🇺'],
                ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'flag_emoji' => '🇨🇳'],
            ];
        }
    }

    /**
     * Formatear fecha según idioma
     */
    public function formatDate($date, $format = 'medium') {
        $timestamp = is_numeric($date) ? $date : strtotime($date);

        $formats = [
            'es' => ['short' => 'd/m/Y', 'medium' => 'd/m/Y H:i', 'long' => 'd \d\e F \d\e Y'],
            'en' => ['short' => 'm/d/Y', 'medium' => 'm/d/Y h:i A', 'long' => 'F d, Y'],
            'pt' => ['short' => 'd/m/Y', 'medium' => 'd/m/Y H:i', 'long' => 'd \d\e F \d\e Y'],
            'fr' => ['short' => 'd/m/Y', 'medium' => 'd/m/Y H:i', 'long' => 'd F Y'],
            'de' => ['short' => 'd.m.Y', 'medium' => 'd.m.Y H:i', 'long' => 'd. F Y'],
            'it' => ['short' => 'd/m/Y', 'medium' => 'd/m/Y H:i', 'long' => 'd F Y'],
            'ru' => ['short' => 'd.m.Y', 'medium' => 'd.m.Y H:i', 'long' => 'd F Y г.'],
            'zh' => ['short' => 'Y/m/d', 'medium' => 'Y/m/d H:i', 'long' => 'Y年m月d日'],
        ];

        $langFormats = $formats[$this->currentLanguage] ?? $formats['es'];
        $dateFormat = $langFormats[$format] ?? $langFormats['medium'];

        return date($dateFormat, $timestamp);
    }

    /**
     * Formatear número según idioma
     */
    public function formatNumber($number, $decimals = 2) {
        $formats = [
            'es' => ['decimal' => ',', 'thousands' => '.'],
            'en' => ['decimal' => '.', 'thousands' => ','],
            'pt' => ['decimal' => ',', 'thousands' => '.'],
            'fr' => ['decimal' => ',', 'thousands' => ' '],
            'de' => ['decimal' => ',', 'thousands' => '.'],
            'it' => ['decimal' => ',', 'thousands' => '.'],
            'ru' => ['decimal' => ',', 'thousands' => ' '],
            'zh' => ['decimal' => '.', 'thousands' => ','],
        ];

        $format = $formats[$this->currentLanguage] ?? $formats['es'];
        return number_format($number, $decimals, $format['decimal'], $format['thousands']);
    }

    /**
     * Formatear moneda según idioma
     */
    public function formatCurrency($amount, $currency = 'CLP') {
        $symbols = [
            'CLP' => '$',
            'USD' => 'US$',
            'EUR' => '€',
            'GBP' => '£',
            'BRL' => 'R$',
            'ARS' => '$',
            'MXN' => '$',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        $formattedAmount = $this->formatNumber($amount, 2);

        // En español y portugués, el símbolo va después
        if (in_array($this->currentLanguage, ['es', 'pt'])) {
            return $formattedAmount . ' ' . $symbol;
        }

        return $symbol . ' ' . $formattedAmount;
    }
}

/**
 * Helper function - Atajo para obtener traducción
 */
function __($key, $params = []) {
    return I18n::getInstance()->get($key, $params);
}

/**
 * Helper function - Atajo para obtener idioma actual
 */
function currentLanguage() {
    return I18n::getInstance()->getCurrentLanguage();
}

/**
 * Helper function - Formatear fecha
 */
function formatDate($date, $format = 'medium') {
    return I18n::getInstance()->formatDate($date, $format);
}

/**
 * Helper function - Formatear número
 */
function formatNumber($number, $decimals = 2) {
    return I18n::getInstance()->formatNumber($number, $decimals);
}

/**
 * Helper function - Formatear moneda
 */
function formatCurrency($amount, $currency = 'CLP') {
    return I18n::getInstance()->formatCurrency($amount, $currency);
}

/**
 * Obtener lista de idiomas soportados
 */
function getSupportedLanguages() {
    return I18n::getInstance()->getSupportedLanguages();
}
