/**
 * RUT VALIDATOR & FORMATTER - CONECTA ERP
 * Validación y formato automático para RUT/RUN chileno
 * Algoritmo: Módulo 11
 */

const RutValidator = {
    /**
     * Limpiar RUT (remover puntos, guiones y espacios)
     * @param {string} rut - RUT a limpiar
     * @returns {string} RUT limpio
     */
    clean(rut) {
        if (!rut) return '';
        return rut.toString().replace(/[^0-9kK]/g, '').toUpperCase();
    },

    /**
     * Formatear RUT (XX.XXX.XXX-X)
     * @param {string} rut - RUT a formatear
     * @returns {string} RUT formateado
     */
    format(rut) {
        if (!rut) return '';

        // Limpiar RUT
        const cleanRut = this.clean(rut);

        if (cleanRut.length < 2) return cleanRut;

        // Separar cuerpo y dígito verificador
        const body = cleanRut.slice(0, -1);
        const dv = cleanRut.slice(-1);

        // Formatear cuerpo con puntos
        const formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return `${formattedBody}-${dv}`;
    },

    /**
     * Calcular dígito verificador
     * @param {string} rut - RUT sin DV
     * @returns {string} Dígito verificador (0-9 o K)
     */
    calculateDV(rut) {
        if (!rut) return '';

        const cleanRut = this.clean(rut);
        let sum = 0;
        let multiplier = 2;

        // Recorrer RUT de derecha a izquierda
        for (let i = cleanRut.length - 1; i >= 0; i--) {
            sum += parseInt(cleanRut[i]) * multiplier;
            multiplier = multiplier === 7 ? 2 : multiplier + 1;
        }

        const remainder = sum % 11;
        const dv = 11 - remainder;

        if (dv === 11) return '0';
        if (dv === 10) return 'K';
        return dv.toString();
    },

    /**
     * Validar RUT completo
     * @param {string} rut - RUT a validar
     * @returns {boolean} true si es válido
     */
    validate(rut) {
        if (!rut) return false;

        const cleanRut = this.clean(rut);

        // Validar largo mínimo (ej: 1000000-0 = 7 dígitos + 1 DV)
        if (cleanRut.length < 2) return false;

        // Validar largo máximo (ej: 99.999.999-9 = 8 dígitos + 1 DV)
        if (cleanRut.length > 9) return false;

        // Separar cuerpo y dígito verificador
        const body = cleanRut.slice(0, -1);
        const dv = cleanRut.slice(-1);

        // Validar que el cuerpo sean solo números
        if (!/^\d+$/.test(body)) return false;

        // Validar que el DV sea válido (0-9 o K)
        if (!/^[0-9K]$/.test(dv)) return false;

        // Calcular DV esperado
        const expectedDV = this.calculateDV(body);

        // Comparar DV
        return dv === expectedDV;
    },

    /**
     * Validar con mensaje de error
     * @param {string} rut - RUT a validar
     * @returns {object} {valid: boolean, message: string}
     */
    validateWithMessage(rut) {
        if (!rut || rut.trim() === '') {
            return {
                valid: false,
                message: 'El RUT es requerido'
            };
        }

        const cleanRut = this.clean(rut);

        if (cleanRut.length < 2) {
            return {
                valid: false,
                message: 'RUT demasiado corto'
            };
        }

        if (cleanRut.length > 9) {
            return {
                valid: false,
                message: 'RUT demasiado largo'
            };
        }

        const body = cleanRut.slice(0, -1);
        const dv = cleanRut.slice(-1);

        if (!/^\d+$/.test(body)) {
            return {
                valid: false,
                message: 'RUT contiene caracteres inválidos'
            };
        }

        const expectedDV = this.calculateDV(body);

        if (dv !== expectedDV) {
            return {
                valid: false,
                message: `RUT inválido. DV correcto: ${expectedDV}`
            };
        }

        return {
            valid: true,
            message: 'RUT válido'
        };
    },

    /**
     * Auto-formatear mientras el usuario escribe
     * @param {HTMLInputElement} input - Campo de input
     */
    autoFormat(input) {
        if (!input) return;

        input.addEventListener('input', (e) => {
            const cursorPosition = e.target.selectionStart;
            const oldValue = e.target.value;
            const newValue = this.format(oldValue);

            // Actualizar valor solo si cambió
            if (oldValue !== newValue) {
                e.target.value = newValue;

                // Ajustar posición del cursor
                const diff = newValue.length - oldValue.length;
                const newCursorPosition = cursorPosition + diff;
                e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            }
        });

        input.addEventListener('blur', (e) => {
            const validation = this.validateWithMessage(e.target.value);

            // Remover clases anteriores
            e.target.classList.remove('is-valid', 'is-invalid');

            // Agregar clase según validación
            if (e.target.value.trim() !== '') {
                if (validation.valid) {
                    e.target.classList.add('is-valid');
                    this.showFeedback(e.target, validation.message, 'valid');
                } else {
                    e.target.classList.add('is-invalid');
                    this.showFeedback(e.target, validation.message, 'invalid');
                }
            }
        });
    },

    /**
     * Mostrar feedback de validación
     * @param {HTMLInputElement} input - Campo de input
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - 'valid' o 'invalid'
     */
    showFeedback(input, message, type) {
        // Buscar o crear elemento de feedback
        let feedback = input.parentElement.querySelector(`.${type}-feedback`);

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = `${type}-feedback`;
            input.parentElement.appendChild(feedback);
        }

        feedback.textContent = message;

        // Remover feedback del tipo opuesto
        const oppositeType = type === 'valid' ? 'invalid' : 'valid';
        const oppositeFeedback = input.parentElement.querySelector(`.${oppositeType}-feedback`);
        if (oppositeFeedback) {
            oppositeFeedback.remove();
        }
    },

    /**
     * Inicializar validación en todos los inputs con data-rut
     */
    init() {
        const rutInputs = document.querySelectorAll('input[data-rut]');

        rutInputs.forEach(input => {
            this.autoFormat(input);

            // Agregar atributos de accesibilidad
            input.setAttribute('placeholder', 'Ej: 12.345.678-9');
            input.setAttribute('maxlength', '12'); // XX.XXX.XXX-X
            input.setAttribute('pattern', '^[0-9]{1,2}\\.[0-9]{3}\\.[0-9]{3}-[0-9Kk]$');
        });
    },

    /**
     * Validar formulario completo
     * @param {HTMLFormElement} form - Formulario a validar
     * @returns {boolean} true si todos los RUTs son válidos
     */
    validateForm(form) {
        if (!form) return true;

        const rutInputs = form.querySelectorAll('input[data-rut]');
        let allValid = true;

        rutInputs.forEach(input => {
            const validation = this.validateWithMessage(input.value);

            // Remover clases anteriores
            input.classList.remove('is-valid', 'is-invalid');

            // Validar solo si el campo no está vacío o es requerido
            if (input.value.trim() !== '' || input.hasAttribute('required')) {
                if (validation.valid) {
                    input.classList.add('is-valid');
                    this.showFeedback(input, validation.message, 'valid');
                } else {
                    input.classList.add('is-invalid');
                    this.showFeedback(input, validation.message, 'invalid');
                    allValid = false;
                }
            }
        });

        return allValid;
    },

    /**
     * Obtener RUT limpio de un input
     * @param {HTMLInputElement} input - Campo de input
     * @returns {string} RUT limpio
     */
    getCleanRut(input) {
        if (!input) return '';
        return this.clean(input.value);
    },

    /**
     * Obtener solo el cuerpo del RUT (sin DV)
     * @param {string} rut - RUT completo
     * @returns {string} Cuerpo del RUT
     */
    getBody(rut) {
        const cleanRut = this.clean(rut);
        return cleanRut.slice(0, -1);
    },

    /**
     * Obtener solo el dígito verificador
     * @param {string} rut - RUT completo
     * @returns {string} Dígito verificador
     */
    getDV(rut) {
        const cleanRut = this.clean(rut);
        return cleanRut.slice(-1);
    },

    /**
     * Generar RUT aleatorio válido (útil para testing)
     * @param {number} min - Número mínimo
     * @param {number} max - Número máximo
     * @returns {string} RUT formateado
     */
    generateRandom(min = 1000000, max = 25000000) {
        const body = Math.floor(Math.random() * (max - min + 1)) + min;
        const dv = this.calculateDV(body.toString());
        return this.format(body + dv);
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => RutValidator.init());
} else {
    RutValidator.init();
}

// Exportar para uso global
window.RutValidator = RutValidator;

// jQuery plugin (opcional, si jQuery está disponible)
if (typeof jQuery !== 'undefined') {
    jQuery.fn.rutValidator = function() {
        return this.each(function() {
            RutValidator.autoFormat(this);
        });
    };
}
