/**
 * CONECTA ERP - Validador y Formateador de RUT/DNI/CUIT por País
 * Sistema multipaís con validación automática
 */

const TaxIDValidator = {
    /**
     * Configuración por país
     */
    config: {
        CL: {
            name: 'RUT',
            format: 'XX.XXX.XXX-X',
            placeholder: '12.345.678-9',
            maxLength: 12,
            validate: 'validateChileanRUT',
            format: 'formatChileanRUT'
        },
        AR: {
            name: 'CUIT',
            format: 'XX-XXXXXXXX-X',
            placeholder: '20-12345678-9',
            maxLength: 13,
            validate: 'validateArgentinaCUIT',
            format: 'formatArgentinaCUIT'
        },
        PE: {
            name: 'RUC',
            format: 'XXXXXXXXXXX',
            placeholder: '20123456789',
            maxLength: 11,
            validate: 'validatePeruRUC',
            format: 'formatPeruRUC'
        },
        CO: {
            name: 'NIT',
            format: 'XXX.XXX.XXX-X',
            placeholder: '123.456.789-0',
            maxLength: 13,
            validate: 'validatecolombiaNIT',
            format: 'formatColombiaNIT'
        },
        MX: {
            name: 'RFC',
            format: 'XXXX-XXXXXX-XXX',
            placeholder: 'ABCD-123456-XY9',
            maxLength: 13,
            validate: 'validateMexicoRFC',
            format: 'formatMexicoRFC'
        },
        BR: {
            name: 'CNPJ',
            format: 'XX.XXX.XXX/XXXX-XX',
            placeholder: '12.345.678/0001-90',
            maxLength: 18,
            validate: 'validateBrazilCNPJ',
            format: 'formatBrazilCNPJ'
        },
        US: {
            name: 'EIN',
            format: 'XX-XXXXXXX',
            placeholder: '12-3456789',
            maxLength: 10,
            validate: 'validateUSEIN',
            format: 'formatUSEIN'
        },
        ES: {
            name: 'CIF',
            format: 'XXXXXXXXX',
            placeholder: 'A12345678',
            maxLength: 9,
            validate: 'validateSpainCIF',
            format: 'formatSpainCIF'
        }
    },

    /**
     * Inicializar validador en un campo
     */
    init: function(inputElement, countryCode) {
        if (!inputElement) return;

        const country = this.config[countryCode] || this.config['CL'];

        // Actualizar placeholder y maxLength
        inputElement.placeholder = country.placeholder;
        inputElement.maxLength = country.maxLength;

        // Agregar eventos
        inputElement.addEventListener('input', (e) => {
            this.handleInput(e.target, countryCode);
        });

        inputElement.addEventListener('blur', (e) => {
            this.validateField(e.target, countryCode);
        });
    },

    /**
     * Manejar entrada de texto
     */
    handleInput: function(input, countryCode) {
        const country = this.config[countryCode];
        if (!country) return;

        // Formatear mientras escribe
        const formatted = this[country.format](input.value);
        input.value = formatted;
    },

    /**
     * Validar campo
     */
    validateField: function(input, countryCode) {
        const country = this.config[countryCode];
        if (!country) return false;

        const isValid = this[country.validate](input.value);

        // Actualizar UI
        if (isValid) {
            input.classList.remove('invalid');
            input.classList.add('valid');
            this.showSuccess(input, `${country.name} válido`);
        } else {
            input.classList.remove('valid');
            input.classList.add('invalid');
            this.showError(input, `${country.name} inválido`);
        }

        return isValid;
    },

    /**
     * CHILE - Validar RUT
     */
    validateChileanRUT: function(rut) {
        // Limpiar RUT
        rut = rut.replace(/\./g, '').replace(/-/g, '').toUpperCase();

        if (rut.length < 8) return false;

        const body = rut.slice(0, -1);
        const dv = rut.slice(-1);

        // Validar que el cuerpo sean solo números
        if (!/^\d+$/.test(body)) return false;

        // Calcular dígito verificador
        let suma = 0;
        let multiplo = 2;

        for (let i = body.length - 1; i >= 0; i--) {
            suma += parseInt(body.charAt(i)) * multiplo;
            multiplo = multiplo < 7 ? multiplo + 1 : 2;
        }

        const dvEsperado = 11 - (suma % 11);
        const dvCalculado = dvEsperado === 11 ? '0' : dvEsperado === 10 ? 'K' : dvEsperado.toString();

        return dv === dvCalculado;
    },

    /**
     * CHILE - Formatear RUT (15895771K → 15.895.771-K)
     */
    formatChileanRUT: function(rut) {
        // Limpiar
        rut = rut.replace(/\./g, '').replace(/-/g, '').toUpperCase();

        // Validar longitud
        if (rut.length < 2) return rut;

        // Separar cuerpo y dígito verificador
        const body = rut.slice(0, -1);
        const dv = rut.slice(-1);

        // Formatear con puntos
        let formatted = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return formatted + '-' + dv;
    },

    /**
     * ARGENTINA - Validar CUIT
     */
    validateArgentinaCUIT: function(cuit) {
        cuit = cuit.replace(/-/g, '');

        if (cuit.length !== 11) return false;
        if (!/^\d+$/.test(cuit)) return false;

        const multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        let suma = 0;

        for (let i = 0; i < 10; i++) {
            suma += parseInt(cuit[i]) * multiplicadores[i];
        }

        const verificador = 11 - (suma % 11);
        const dvCalculado = verificador === 11 ? 0 : verificador === 10 ? 9 : verificador;

        return parseInt(cuit[10]) === dvCalculado;
    },

    /**
     * ARGENTINA - Formatear CUIT
     */
    formatArgentinaCUIT: function(cuit) {
        cuit = cuit.replace(/\D/g, '');
        if (cuit.length <= 2) return cuit;
        if (cuit.length <= 10) return cuit.slice(0, 2) + '-' + cuit.slice(2);
        return cuit.slice(0, 2) + '-' + cuit.slice(2, 10) + '-' + cuit.slice(10);
    },

    /**
     * PERÚ - Validar RUC
     */
    validatePeruRUC: function(ruc) {
        ruc = ruc.replace(/\D/g, '');

        if (ruc.length !== 11) return false;

        // Validar tipo de RUC
        const tipo = ruc.substring(0, 2);
        if (!['10', '15', '17', '20'].includes(tipo)) return false;

        return true;
    },

    /**
     * PERÚ - Formatear RUC
     */
    formatPeruRUC: function(ruc) {
        return ruc.replace(/\D/g, '').slice(0, 11);
    },

    /**
     * COLOMBIA - Validar NIT
     */
    validatecolombiaNIT: function(nit) {
        nit = nit.replace(/\./g, '').replace(/-/g, '');

        if (nit.length < 9) return false;

        const body = nit.slice(0, -1);
        const dv = parseInt(nit.slice(-1));

        const primos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        let suma = 0;

        for (let i = 0; i < body.length; i++) {
            suma += parseInt(body[body.length - 1 - i]) * primos[i];
        }

        const dvCalculado = suma % 11;
        const dvFinal = dvCalculado > 1 ? 11 - dvCalculado : dvCalculado;

        return dv === dvFinal;
    },

    /**
     * COLOMBIA - Formatear NIT
     */
    formatColombiaNIT: function(nit) {
        nit = nit.replace(/\D/g, '');
        if (nit.length <= 3) return nit;
        if (nit.length <= 6) return nit.slice(0, 3) + '.' + nit.slice(3);
        if (nit.length <= 9) return nit.slice(0, 3) + '.' + nit.slice(3, 6) + '.' + nit.slice(6);
        return nit.slice(0, 3) + '.' + nit.slice(3, 6) + '.' + nit.slice(6, 9) + '-' + nit.slice(9);
    },

    /**
     * MÉXICO - Validar RFC
     */
    validateMexicoRFC: function(rfc) {
        rfc = rfc.replace(/-/g, '').toUpperCase();

        // Persona Moral: 12 caracteres, Persona Física: 13 caracteres
        if (rfc.length !== 12 && rfc.length !== 13) return false;

        // Validar formato básico
        const regex = /^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/;
        return regex.test(rfc);
    },

    /**
     * MÉXICO - Formatear RFC
     */
    formatMexicoRFC: function(rfc) {
        rfc = rfc.replace(/[^A-Z0-9&Ñ]/gi, '').toUpperCase();
        if (rfc.length <= 4) return rfc;
        if (rfc.length <= 10) return rfc.slice(0, 4) + '-' + rfc.slice(4);
        return rfc.slice(0, 4) + '-' + rfc.slice(4, 10) + '-' + rfc.slice(10);
    },

    /**
     * BRASIL - Validar CNPJ
     */
    validateBrazilCNPJ: function(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');

        if (cnpj.length !== 14) return false;
        if (/^(\d)\1+$/.test(cnpj)) return false;

        // Validar primer dígito verificador
        let sum = 0;
        let pos = 5;
        for (let i = 0; i < 12; i++) {
            sum += parseInt(cnpj[i]) * pos--;
            if (pos < 2) pos = 9;
        }
        let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        if (result !== parseInt(cnpj[12])) return false;

        // Validar segundo dígito verificador
        sum = 0;
        pos = 6;
        for (let i = 0; i < 13; i++) {
            sum += parseInt(cnpj[i]) * pos--;
            if (pos < 2) pos = 9;
        }
        result = sum % 11 < 2 ? 0 : 11 - (sum % 11);

        return result === parseInt(cnpj[13]);
    },

    /**
     * BRASIL - Formatear CNPJ
     */
    formatBrazilCNPJ: function(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        if (cnpj.length <= 2) return cnpj;
        if (cnpj.length <= 5) return cnpj.slice(0, 2) + '.' + cnpj.slice(2);
        if (cnpj.length <= 8) return cnpj.slice(0, 2) + '.' + cnpj.slice(2, 5) + '.' + cnpj.slice(5);
        if (cnpj.length <= 12) return cnpj.slice(0, 2) + '.' + cnpj.slice(2, 5) + '.' + cnpj.slice(5, 8) + '/' + cnpj.slice(8);
        return cnpj.slice(0, 2) + '.' + cnpj.slice(2, 5) + '.' + cnpj.slice(5, 8) + '/' + cnpj.slice(8, 12) + '-' + cnpj.slice(12);
    },

    /**
     * USA - Validar EIN
     */
    validateUSEIN: function(ein) {
        ein = ein.replace(/-/g, '');
        return ein.length === 9 && /^\d+$/.test(ein);
    },

    /**
     * USA - Formatear EIN
     */
    formatUSEIN: function(ein) {
        ein = ein.replace(/\D/g, '');
        if (ein.length <= 2) return ein;
        return ein.slice(0, 2) + '-' + ein.slice(2);
    },

    /**
     * ESPAÑA - Validar CIF
     */
    validateSpainCIF: function(cif) {
        cif = cif.toUpperCase();
        if (cif.length !== 9) return false;

        const regex = /^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/;
        return regex.test(cif);
    },

    /**
     * ESPAÑA - Formatear CIF
     */
    formatSpainCIF: function(cif) {
        return cif.replace(/[^A-Z0-9]/gi, '').toUpperCase().slice(0, 9);
    },

    /**
     * Mostrar mensaje de éxito
     */
    showSuccess: function(input, message) {
        this.clearMessages(input);
        const feedback = document.createElement('div');
        feedback.className = 'field-feedback success';
        feedback.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        input.parentElement.appendChild(feedback);
    },

    /**
     * Mostrar mensaje de error
     */
    showError: function(input, message) {
        this.clearMessages(input);
        const feedback = document.createElement('div');
        feedback.className = 'field-feedback error';
        feedback.innerHTML = `<i class="fas fa-times-circle"></i> ${message}`;
        input.parentElement.appendChild(feedback);
    },

    /**
     * Limpiar mensajes
     */
    clearMessages: function(input) {
        const existing = input.parentElement.querySelector('.field-feedback');
        if (existing) existing.remove();
    }
};

// Exportar para uso global
window.TaxIDValidator = TaxIDValidator;
