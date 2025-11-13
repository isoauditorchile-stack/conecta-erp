<?php
/**
 * Script para instalar mejoras de base de datos
 * - Países multi-idioma
 * - Multi-empresa
 * - Previred y SII
 * - Traducciones
 */

require_once __DIR__ . '/includes/config.php';

echo "==========================================\n";
echo "CONECTA ERP - Instalador de Mejoras\n";
echo "==========================================\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Leer archivo SQL
    $sql = file_get_contents(__DIR__ . '/database/schema_improvements.sql');

    // Separar por declaraciones
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );

    $success = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $success++;
            echo ".";
        } catch (PDOException $e) {
            // Ignorar errores de "tabla ya existe"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "\nError: " . $e->getMessage() . "\n";
                $errors++;
            } else {
                echo "s"; // skip
            }
        }
    }

    echo "\n\n";
    echo "✓ Declaraciones ejecutadas: $success\n";
    if ($errors > 0) {
        echo "⚠ Errores: $errors\n";
    }

    // Insertar datos de países si no existen
    echo "\nInsertando países...\n";

    $countries_data = [
        ['CL', 'Chile', 'Chile', 'Chile', 'Chile', 'Chili', 'Chile', 'Chile', 'Чили', '智利', 'RUT', 'XX.XXX.XXX-X', '^[0-9]{7,8}-[0-9K]$', 'CLP', '+56', 19.00, 1, 1],
        ['AR', 'Argentina', 'Argentina', 'Argentina', 'Argentina', 'Argentine', 'Argentinien', 'Argentina', 'Аргентина', '阿根廷', 'CUIT', 'XX-XXXXXXXX-X', '^[0-9]{2}-[0-9]{8}-[0-9]$', 'ARS', '+54', 21.00, 0, 0],
        ['PE', 'Perú', 'Peru', 'Peru', 'Pérou', 'Peru', 'Peru', 'Perù', 'Перу', '秘鲁', 'RUC', 'XXXXXXXXXXX', '^[0-9]{11}$', 'PEN', '+51', 18.00, 0, 0],
        ['CO', 'Colombia', 'Colombia', 'Colômbia', 'Colombie', 'Kolumbien', 'Colombia', 'Colombia', 'Колумбия', '哥伦比亚', 'NIT', 'XXX.XXX.XXX-X', '^[0-9]{9}-[0-9]$', 'COP', '+57', 19.00, 0, 0],
        ['MX', 'México', 'Mexico', 'México', 'Mexique', 'Mexiko', 'Messico', 'Messico', 'Мексика', '墨西哥', 'RFC', 'XXXX######XXX', '^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$', 'MXN', '+52', 16.00, 0, 0],
        ['BR', 'Brasil', 'Brazil', 'Brasil', 'Brésil', 'Brasilien', 'Brasile', 'Brasile', 'Бразилия', '巴西', 'CNPJ', 'XX.XXX.XXX/XXXX-XX', '^[0-9]{2}\\.[0-9]{3}\\.[0-9]{3}/[0-9]{4}-[0-9]{2}$', 'BRL', '+55', 17.00, 0, 0],
        ['US', 'Estados Unidos', 'United States', 'Estados Unidos', 'États-Unis', 'Vereinigte Staaten', 'Stati Uniti', 'Stati Uniti', 'США', '美国', 'EIN', 'XX-XXXXXXX', '^[0-9]{2}-[0-9]{7}$', 'USD', '+1', 0.00, 0, 0],
        ['ES', 'España', 'Spain', 'Espanha', 'Espagne', 'Spanien', 'Spagna', 'Spagna', 'Испания', '西班牙', 'NIF/CIF', 'X########X', '^[A-Z][0-9]{7,8}[A-Z0-9]$', 'EUR', '+34', 21.00, 0, 0]
    ];

    foreach ($countries_data as $country) {
        try {
            $check = $db->fetchOne("SELECT id FROM countries WHERE code = ?", [$country[0]]);
            if (!$check) {
                $db->insert(
                    "INSERT INTO countries (
                        code, name_es, name_en, name_pt, name_fr, name_de, name_it, name_ru, name_zh,
                        tax_id_name, tax_id_format, tax_id_validation_regex,
                        currency_code, phone_code, vat_rate, previred_enabled, sii_enabled, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                    $country
                );
                echo "  ✓ " . $country[1] . "\n";
            } else {
                echo "  - " . $country[1] . " (ya existe)\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error con " . $country[1] . ": " . $e->getMessage() . "\n";
        }
    }

    // Insertar traducciones básicas
    echo "\nInsertando traducciones básicas...\n";

    $translations = [
        ['app_name', 'CONECTA ERP', 'CONECTA ERP', 'CONECTA ERP', 'CONECTA ERP', 'CONECTA ERP', 'CONECTA ERP', 'CONECTA ERP', 'CONECTA ERP'],
        ['hero_description', 'Sistema de Gestión Empresarial Completo - Multi-país, Multi-idioma, Multi-empresa', 'Complete Enterprise Management System - Multi-country, Multi-language, Multi-company', 'Sistema Completo de Gestão Empresarial - Multi-país, Multi-idioma, Multi-empresa', 'Système de Gestion d\'Entreprise Complet', 'Komplettes Unternehmensverwaltungssystem', 'Sistema Completo di Gestione Aziendale', 'Комплексная Система Управления Предприятием', '完整的企业管理系统'],
        ['start_free_trial', 'Comenzar Prueba Gratuita', 'Start Free Trial', 'Iniciar Teste Gratuito', 'Commencer l\'Essai Gratuit', 'Kostenlose Testversion Starten', 'Inizia Prova Gratuita', 'Начать Бесплатную Пробную Версию', '开始免费试用'],
        ['create_account', 'Crear Cuenta', 'Create Account', 'Criar Conta', 'Créer un Compte', 'Konto Erstellen', 'Crea Account', 'Создать Аккаунт', '创建账户'],
        ['personal_information', 'Información Personal', 'Personal Information', 'Informação Pessoal', 'Informations Personnelles', 'Persönliche Informationen', 'Informazioni Personali', 'Личная Информация', '个人信息'],
        ['company_information', 'Información de la Empresa', 'Company Information', 'Informação da Empresa', 'Informations sur l\'Entreprise', 'Unternehmensinformationen', 'Informazioni Aziendali', 'Информация о Компании', '公司信息'],
        ['location', 'Ubicación', 'Location', 'Localização', 'Emplacement', 'Standort', 'Posizione', 'Местоположение', '位置'],
        ['regional_settings', 'Configuración Regional', 'Regional Settings', 'Configurações Regionais', 'Paramètres Régionaux', 'Regionale Einstellungen', 'Impostazioni Regionali', 'Региональные Настройки', '区域设置'],
        ['integrations', 'Integraciones', 'Integrations', 'Integrações', 'Intégrations', 'Integrationen', 'Integrazioni', 'Интеграции', '集成'],
        ['firstname', 'Nombre', 'First Name', 'Nome', 'Prénom', 'Vorname', 'Nome', 'Имя', '名字'],
        ['lastname', 'Apellido', 'Last Name', 'Sobrenome', 'Nom', 'Nachname', 'Cognome', 'Фамилия', '姓氏'],
        ['email', 'Correo Electrónico', 'Email', 'E-mail', 'E-mail', 'E-Mail', 'Email', 'Электронная Почта', '电子邮件'],
        ['username', 'Usuario', 'Username', 'Usuário', 'Nom d\'utilisateur', 'Benutzername', 'Nome utente', 'Имя пользователя', '用户名'],
        ['password', 'Contraseña', 'Password', 'Senha', 'Mot de passe', 'Passwort', 'Password', 'Пароль', '密码'],
        ['position', 'Cargo', 'Position', 'Cargo', 'Poste', 'Position', 'Posizione', 'Должность', '职位'],
        ['company_name', 'Nombre de Empresa', 'Company Name', 'Nome da Empresa', 'Nom de l\'Entreprise', 'Firmenname', 'Nome Azienda', 'Название Компании', '公司名称'],
        ['legal_name', 'Razón Social', 'Legal Name', 'Razão Social', 'Raison Sociale', 'Rechtsname', 'Ragione Sociale', 'Юридическое Название', '法定名称'],
        ['legal_name_hint', 'Si es diferente al nombre comercial', 'If different from trade name', 'Se diferente do nome comercial', 'Si différent du nom commercial', 'Falls abweichend vom Handelsnamen', 'Se diverso dal nome commerciale', 'Если отличается от торгового названия', '如果与商业名称不同'],
        ['tax_id', 'RUT/NIT/RFC', 'Tax ID', 'CPF/CNPJ', 'Numéro fiscal', 'Steuernummer', 'Partita IVA', 'ИНН', '税号'],
        ['country', 'País', 'Country', 'País', 'Pays', 'Land', 'Paese', 'Страна', '国家'],
        ['select_country', 'Seleccione un país', 'Select a country', 'Selecione um país', 'Sélectionnez un pays', 'Land auswählen', 'Seleziona un paese', 'Выберите страну', '选择国家'],
        ['industry', 'Industria', 'Industry', 'Indústria', 'Industrie', 'Branche', 'Industria', 'Отрасль', '行业'],
        ['industry_hint', 'Ej: Tecnología, Retail, Manufactura...', 'Ex: Technology, Retail, Manufacturing...', 'Ex: Tecnologia, Varejo, Manufatura...', 'Ex: Technologie, Commerce de détail, Fabrication...', 'Z.B.: Technologie, Einzelhandel, Fertigung...', 'Es: Tecnologia, Vendita al dettaglio, Produzione...', 'Пример: Технологии, Розничная торговля, Производство...', '例如：科技、零售、制造...'],
        ['employees_count', 'Número de Empleados', 'Number of Employees', 'Número de Funcionários', 'Nombre d\'Employés', 'Anzahl der Mitarbeiter', 'Numero di Dipendenti', 'Количество Сотрудников', '员工人数'],
        ['phone', 'Teléfono', 'Phone', 'Telefone', 'Téléphone', 'Telefon', 'Telefono', 'Телефон', '电话'],
        ['website', 'Sitio Web', 'Website', 'Site', 'Site Web', 'Webseite', 'Sito Web', 'Веб-сайт', '网站'],
        ['address', 'Dirección', 'Address', 'Endereço', 'Adresse', 'Adresse', 'Indirizzo', 'Адрес', '地址'],
        ['city', 'Ciudad', 'City', 'Cidade', 'Ville', 'Stadt', 'Città', 'Город', '城市'],
        ['state_province', 'Estado/Provincia', 'State/Province', 'Estado/Província', 'État/Province', 'Bundesland/Provinz', 'Stato/Provincia', 'Штат/Провинция', '州/省'],
        ['postal_code', 'Código Postal', 'Postal Code', 'Código Postal', 'Code Postal', 'Postleitzahl', 'Codice Postale', 'Почтовый Индекс', '邮政编码'],
        ['currency', 'Moneda', 'Currency', 'Moeda', 'Devise', 'Währung', 'Valuta', 'Валюта', '货币'],
        ['language', 'Idioma', 'Language', 'Idioma', 'Langue', 'Sprache', 'Lingua', 'Язык', '语言'],
        ['timezone', 'Zona Horaria', 'Timezone', 'Fuso Horário', 'Fuseau Horaire', 'Zeitzone', 'Fuso Orario', 'Часовой Пояс', '时区'],
        ['fiscal_year_start', 'Inicio Año Fiscal', 'Fiscal Year Start', 'Início Ano Fiscal', 'Début de l\'Année Fiscale', 'Beginn des Geschäftsjahres', 'Inizio Anno Fiscale', 'Начало Финансового Года', '财政年度开始'],
        ['annual_revenue', 'Ingresos Anuales', 'Annual Revenue', 'Receita Anual', 'Revenu Annuel', 'Jahresumsatz', 'Fatturato Annuo', 'Годовой Доход', '年收入'],
        ['annual_revenue_hint', 'Opcional, en su moneda local', 'Optional, in your local currency', 'Opcional, em sua moeda local', 'Facultatif, dans votre devise locale', 'Optional, in Ihrer lokalen Währung', 'Facoltativo, nella tua valuta locale', 'Необязательно, в вашей местной валюте', '可选，以当地货币计'],
        ['chile_only', 'Solo Chile', 'Chile Only', 'Somente Chile', 'Chili Uniquement', 'Nur Chile', 'Solo Cile', 'Только Чили', '仅智利'],
        ['previred_description', 'Sistema de remuneraciones y previsión social', 'Payroll and social security system', 'Sistema de remuneração e previdência social', 'Système de paie et de sécurité sociale', 'Lohn- und Sozialversicherungssystem', 'Sistema di buste paga e previdenza sociale', 'Система оплаты труда и социального обеспечения', '工资和社会保障系统'],
        ['sii_description', 'Servicio de Impuestos Internos - Facturación Electrónica', 'Internal Revenue Service - Electronic Invoicing', 'Serviço de Impostos Internos - Faturamento Eletrônico', 'Service des Impôts Internes - Facturation Électronique', 'Finanzamt - Elektronische Rechnungsstellung', 'Servizio delle Imposte Interne - Fatturazione Elettronica', 'Служба Внутренних Доходов - Электронное Выставление Счетов', '国内税务局 - 电子发票'],
        ['previred_rut', 'RUT Previred', 'Previred RUT', 'RUT Previred', 'RUT Previred', 'Previred RUT', 'RUT Previred', 'Previred RUT', 'Previred RUT'],
        ['sii_rut', 'RUT SII', 'SII RUT', 'RUT SII', 'RUT SII', 'SII RUT', 'RUT SII', 'SII RUT', 'SII RUT'],
        ['login', 'Iniciar Sesión', 'Login', 'Entrar', 'Se connecter', 'Anmelden', 'Accedi', 'Войти', '登录'],
        ['register', 'Registrarse', 'Register', 'Registrar', 'S\'inscrire', 'Registrieren', 'Registrati', 'Зарегистрироваться', '注册'],
        ['format', 'Formato', 'Format', 'Formato', 'Format', 'Format', 'Formato', 'Формат', '格式'],
        ['error', 'Error', 'Error', 'Erro', 'Erreur', 'Fehler', 'Errore', 'Ошибка', '错误'],
        ['success', 'Éxito', 'Success', 'Sucesso', 'Succès', 'Erfolg', 'Successo', 'Успех', '成功'],
        ['registration_success_trial', 'Registro exitoso! Tu período de prueba de 14 días ha comenzado. Espera la aprobación del administrador.', 'Registration successful! Your 14-day trial period has started. Wait for admin approval.', 'Registro bem-sucedido! Seu período de teste de 14 dias começou. Aguarde aprovação do administrador.', 'Inscription réussie! Votre période d\'essai de 14 jours a commencé. Attendez l\'approbation de l\'administrateur.', 'Registrierung erfolgreich! Ihre 14-tägige Testphase hat begonnen. Warten Sie auf die Genehmigung des Administrators.', 'Registrazione riuscita! Il tuo periodo di prova di 14 giorni è iniziato. Attendi l\'approvazione dell\'amministratore.', 'Регистрация успешна! Начался 14-дневный пробный период. Ожидайте одобрения администратора.', '注册成功！您的14天试用期已开始。等待管理员批准。'],
        ['invalid_credentials', 'Credenciales inválidas', 'Invalid credentials', 'Credenciais inválidas', 'Identifiants invalides', 'Ungültige Anmeldedaten', 'Credenziali non valide', 'Неверные учетные данные', '凭据无效'],
        ['country_not_found', 'País no encontrado', 'Country not found', 'País não encontrado', 'Pays non trouvé', 'Land nicht gefunden', 'Paese non trovato', 'Страна не найдена', '未找到国家']
    ];

    foreach ($translations as $trans) {
        try {
            $check = $db->fetchOne("SELECT id FROM translations WHERE key_text = ?", [$trans[0]]);
            if (!$check) {
                $db->insert(
                    "INSERT INTO translations (key_text, lang_es, lang_en, lang_pt, lang_fr, lang_de, lang_it, lang_ru, lang_zh)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    $trans
                );
                echo "  ✓ " . $trans[0] . "\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error con " . $trans[0] . ": " . $e->getMessage() . "\n";
        }
    }

    echo "\n==========================================\n";
    echo "✓ Instalación completada exitosamente!\n";
    echo "==========================================\n\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
