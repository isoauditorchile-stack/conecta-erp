<!-- Modal de Registro COMPLETO con Selección de Plan -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal('registerModal')">&times;</button>
        <h2 style="font-size:2rem;margin-bottom:2rem;text-align:center;">Crear Cuenta - 14 Días Gratis</h2>

        <form method="POST" action="" id="registerForm">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="plan" id="selected_plan" value="BASICO">

            <!-- SECCIÓN 1: Datos Personales -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-user"></i> Información Personal
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido *</label>
                        <input type="text" name="lastname" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Usuario *</label>
                        <input type="text" name="username" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Contraseña *</label>
                        <input type="password" name="password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Cargo</label>
                        <input type="text" name="position" value="Director" placeholder="CEO, Director, Gerente...">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: Información de la Empresa -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-building"></i> Información de la Empresa
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre de Empresa *</label>
                        <input type="text" name="company_name" required>
                    </div>
                    <div class="form-group">
                        <label>Razón Social</label>
                        <input type="text" name="legal_name" placeholder="Si es diferente al nombre comercial">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>País *</label>
                        <select name="country" id="country" required onchange="updateTaxIdFormat(this.value)">
                            <option value="">Seleccione un país</option>
                            <?php foreach ($countries as $country): ?>
                            <option value="<?php echo $country['code']; ?>" data-taxid="<?php echo $country['tax_id_name']; ?>">
                                <?php echo $country['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label id="taxIdLabel">RUT/Tax ID *</label>
                        <input type="text" name="tax_id" id="tax_id" required>
                        <div class="tax-id-hint" id="taxIdHint"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Industria</label>
                        <input type="text" name="industry" placeholder="Ej: Tecnología, Retail, Manufactura...">
                    </div>
                    <div class="form-group">
                        <label>Número de Empleados</label>
                        <select name="employees">
                            <option value="1-10">1-10</option>
                            <option value="11-50">11-50</option>
                            <option value="51-200">51-200</option>
                            <option value="201-500">201-500</option>
                            <option value="500+">500+</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="tel" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label>Sitio Web</label>
                        <input type="url" name="website" placeholder="https://...">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: Ubicación -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-map-marker-alt"></i> Ubicación
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="address">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ciudad</label>
                        <input type="text" name="city">
                    </div>
                    <div class="form-group">
                        <label>Estado/Provincia</label>
                        <input type="text" name="state_province">
                    </div>
                    <div class="form-group">
                        <label>Código Postal</label>
                        <input type="text" name="postal_code">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 4: Configuración Regional -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-globe"></i> Configuración Regional
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Moneda *</label>
                        <select name="currency" required>
                            <option value="USD">USD - Dólar Estadounidense</option>
                            <option value="CLP" selected>CLP - Peso Chileno</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="ARS">ARS - Peso Argentino</option>
                            <option value="PEN">PEN - Sol Peruano</option>
                            <option value="COP">COP - Peso Colombiano</option>
                            <option value="MXN">MXN - Peso Mexicano</option>
                            <option value="BRL">BRL - Real Brasileño</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Idioma *</label>
                        <select name="language" required>
                            <option value="es" selected>Español</option>
                            <option value="en">English</option>
                            <option value="pt">Português</option>
                            <option value="fr">Français</option>
                            <option value="de">Deutsch</option>
                            <option value="it">Italiano</option>
                            <option value="ru">Русский</option>
                            <option value="zh">中文</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Zona Horaria</label>
                        <select name="timezone">
                            <option value="America/Santiago" selected>América/Santiago (Chile)</option>
                            <option value="America/Argentina/Buenos_Aires">América/Buenos Aires</option>
                            <option value="America/Lima">América/Lima</option>
                            <option value="America/Bogota">América/Bogotá</option>
                            <option value="America/Mexico_City">América/Ciudad de México</option>
                            <option value="America/Sao_Paulo">América/São Paulo</option>
                            <option value="America/New_York">América/Nueva York</option>
                            <option value="Europe/Madrid">Europa/Madrid</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Inicio Año Fiscal</label>
                        <input type="text" name="fiscal_year_start" value="01-01" placeholder="MM-DD">
                    </div>
                    <div class="form-group">
                        <label>Ingresos Anuales</label>
                        <input type="text" name="annual_revenue" placeholder="Opcional, en su moneda local">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 5: Integraciones (Chile) -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-plug"></i> Integraciones (Solo Chile)
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="previred_enabled" id="previred_enabled" onchange="togglePreviredFields()">
                    <label for="previred_enabled" style="margin:0;">
                        <strong>Previred</strong> - Sistema de remuneraciones y previsión social
                    </label>
                </div>
                <div id="previredFields" style="display:none;margin-top:1rem;">
                    <div class="form-group">
                        <label>RUT Previred</label>
                        <input type="text" name="previred_rut" placeholder="12.345.678-9">
                    </div>
                </div>

                <div class="checkbox-group" style="margin-top:1rem;">
                    <input type="checkbox" name="sii_enabled" id="sii_enabled" onchange="toggleSIIFields()">
                    <label for="sii_enabled" style="margin:0;">
                        <strong>SII</strong> - Servicio de Impuestos Internos - Facturación Electrónica
                    </label>
                </div>
                <div id="siiFields" style="display:none;margin-top:1rem;">
                    <div class="form-group">
                        <label>RUT SII</label>
                        <input type="text" name="sii_rut" placeholder="12.345.678-9">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;font-size:1.1rem;padding:1.2rem;">
                <i class="fas fa-rocket"></i> Crear Cuenta - 14 Días Gratis
            </button>

            <p style="text-align:center;margin-top:1.5rem;color:var(--text-muted);font-size:0.9rem;">
                ⏳ Tu solicitud será revisada por <strong>auditorexchile@gmail.com</strong><br>
                Recibirás un email cuando tu cuenta sea aprobada
            </p>
        </form>
    </div>
</div>

<script>
    function togglePreviredFields() {
        const fields = document.getElementById('previredFields');
        fields.style.display = document.getElementById('previred_enabled').checked ? 'block' : 'none';
    }

    function toggleSIIFields() {
        const fields = document.getElementById('siiFields');
        fields.style.display = document.getElementById('sii_enabled').checked ? 'block' : 'none';
    }

    function updateTaxIdFormat(countryCode) {
        const taxIdInput = document.getElementById('tax_id');
        const taxIdLabel = document.getElementById('taxIdLabel');
        const taxIdHint = document.getElementById('taxIdHint');
        const countrySelect = document.getElementById('country');
        const selectedOption = countrySelect.options[countrySelect.selectedIndex];

        if (countryCode && selectedOption) {
            const taxIdName = selectedOption.getAttribute('data-taxid');
            taxIdLabel.textContent = taxIdName + ' *';

            // Inicializar validador de RUT
            if (typeof TaxIDValidator !== 'undefined') {
                TaxIDValidator.init(taxIdInput, countryCode);

                // Mostrar formato esperado
                const config = TaxIDValidator.config[countryCode];
                if (config) {
                    taxIdHint.textContent = 'Formato: ' + config.format;
                }
            }
        }
    }

    // Inicializar al cargar
    document.addEventListener('DOMContentLoaded', function() {
        const countrySelect = document.getElementById('country');
        if (countrySelect) {
            const defaultCountry = 'CL';
            countrySelect.value = defaultCountry;
            updateTaxIdFormat(defaultCountry);
        }
    });
</script>
