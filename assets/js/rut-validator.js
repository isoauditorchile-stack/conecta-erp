/**
 * CONECTA ERP - RUT Validator Library
 * Version: 2.0.0
 *
 * Global library for validating and formatting Chilean RUT (Rol Único Tributario)
 * using the Module 11 algorithm. Also supports multi-country tax IDs.
 *
 * Features:
 * - Real-time RUT formatting (XX.XXX.XXX-X)
 * - Module 11 validation algorithm
 * - Visual feedback (green/red borders)
 * - Error message display
 * - Multi-country tax ID support
 * - Easy initialization with data attributes
 *
 * Usage:
 * 1. Include this file in your HTML: <script src="/assets/js/rut-validator.js"></script>
 * 2. Add data-rut-validator="true" to your RUT input field
 * 3. Optionally add data-country-selector="#country" to link with country select
 *
 * Manual usage:
 * RUTValidator.formatChileanRUT('158957717') // Returns: 15.895.771-7
 * RUTValidator.validateChileanRUT('15.895.771-k') // Returns: true
 * RUTValidator.init('#tax_id', '#country') // Initialize on specific elements
 */

const RUTValidator = (function() {
    'use strict';

    // Configuration for different country tax IDs
    const TAX_ID_CONFIG = {
        'CL': {
            label: 'RUT Empresa',
            placeholder: '15.895.771-k',
            hint: 'Formato: XX.XXX.XXX-X (ej: 15.895.771-k o 77.866.873-4)',
            pattern: '[0-9]{1,2}\\.[0-9]{3}\\.[0-9]{3}-[0-9kK]',
            validateFunction: 'validateChileanRUT',
            formatFunction: 'formatChileanRUT'
        },
        'AR': {
            label: 'CUIT',
            placeholder: '20-12345678-3',
            hint: 'Formato: XX-XXXXXXXX-X',
            pattern: '[0-9]{2}-[0-9]{8}-[0-9]'
        },
        'PE': {
            label: 'RUC',
            placeholder: '12345678901',
            hint: 'Formato: 11 dígitos',
            pattern: '[0-9]{11}'
        },
        'CO': {
            label: 'NIT',
            placeholder: '900.123.456-7',
            hint: 'Formato: XXX.XXX.XXX-X',
            pattern: '[0-9]{3}\\.[0-9]{3}\\.[0-9]{3}-[0-9]'
        },
        'MX': {
            label: 'RFC',
            placeholder: 'ABC123456XXX',
            hint: 'Formato: 12-13 caracteres alfanuméricos',
            pattern: '[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}'
        },
        'BR': {
            label: 'CNPJ',
            placeholder: '12.345.678/0001-90',
            hint: 'Formato: XX.XXX.XXX/XXXX-XX',
            pattern: '[0-9]{2}\\.[0-9]{3}\\.[0-9]{3}/[0-9]{4}-[0-9]{2}'
        },
        'US': {
            label: 'EIN',
            placeholder: '12-3456789',
            hint: 'Formato: XX-XXXXXXX',
            pattern: '[0-9]{2}-[0-9]{7}'
        },
        'ES': {
            label: 'CIF/NIF',
            placeholder: 'A-12345678',
            hint: 'Formato: X-XXXXXXXX',
            pattern: '[A-Z]-[0-9]{8}'
        }
    };

    /**
     * Format Chilean RUT with dots and dash
     * @param {string} rut - Raw RUT string
     * @returns {string} Formatted RUT (XX.XXX.XXX-X)
     */
    function formatChileanRUT(rut) {
        // Remove everything except numbers and K
        rut = rut.replace(/[^0-9kK]/g, '').toUpperCase();

        if (rut.length < 2) return rut;

        // Separate body and verification digit
        const body = rut.slice(0, -1);
        const dv = rut.slice(-1);

        // Format body with dots
        let formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return formattedBody + '-' + dv;
    }

    /**
     * Validate Chilean RUT using Module 11 algorithm
     * @param {string} rut - RUT to validate (can be formatted or raw)
     * @returns {boolean} True if valid, false otherwise
     */
    function validateChileanRUT(rut) {
        // Clean RUT (remove dots and dashes)
        rut = rut.replace(/\./g, '').replace(/-/g, '').toUpperCase();

        if (rut.length < 2) return false;

        const body = rut.slice(0, -1);
        const dv = rut.slice(-1);

        // Validate that body contains only numbers
        if (!/^\d+$/.test(body)) return false;

        // Calculate verification digit using Module 11
        let suma = 0;
        let multiplo = 2;

        for (let i = body.length - 1; i >= 0; i--) {
            suma += parseInt(body.charAt(i)) * multiplo;
            multiplo = multiplo < 7 ? multiplo + 1 : 2;
        }

        const dvEsperado = 11 - (suma % 11);
        const dvCalculado = dvEsperado === 11 ? '0' : dvEsperado === 10 ? 'K' : dvEsperado.toString();

        return dv === dvCalculado;
    }

    /**
     * Clean RUT (remove formatting)
     * @param {string} rut - Formatted RUT
     * @returns {string} Clean RUT
     */
    function cleanRUT(rut) {
        return rut.replace(/\./g, '').replace(/-/g, '').toUpperCase();
    }

    /**
     * Show error message below input field
     * @param {HTMLElement} input - Input element
     * @param {string} message - Error message
     */
    function showError(input, message) {
        // Remove existing error if present
        removeError(input);

        // Set red border
        input.style.borderColor = '#ef4444';
        input.style.borderWidth = '2px';

        // Create error message element
        const errorMsg = document.createElement('small');
        errorMsg.className = 'rut-error-message';
        errorMsg.style.cssText = 'color: #ef4444; font-size: 0.85rem; display: block; margin-top: 0.25rem; font-weight: 500;';
        errorMsg.textContent = '❌ ' + message;

        // Insert after input or after parent div
        if (input.parentElement) {
            input.parentElement.appendChild(errorMsg);
        }
    }

    /**
     * Show success state (green border)
     * @param {HTMLElement} input - Input element
     */
    function showSuccess(input) {
        removeError(input);
        input.style.borderColor = '#10b981';
        input.style.borderWidth = '2px';
    }

    /**
     * Remove error message and reset border
     * @param {HTMLElement} input - Input element
     */
    function removeError(input) {
        // Remove error message
        const errorMsg = input.parentElement?.querySelector('.rut-error-message');
        if (errorMsg) {
            errorMsg.remove();
        }

        // Reset border if it was showing error
        if (input.style.borderColor === 'rgb(239, 68, 68)') {
            input.style.borderColor = '';
            input.style.borderWidth = '';
        }
    }

    /**
     * Update tax ID label, placeholder, and hint based on country
     * @param {HTMLElement} input - Tax ID input element
     * @param {string} country - Country code
     */
    function updateTaxIdPlaceholder(input, country) {
        const config = TAX_ID_CONFIG[country];

        if (!config) return;

        // Update placeholder
        input.placeholder = config.placeholder;

        // Update pattern attribute
        if (config.pattern) {
            input.setAttribute('pattern', config.pattern);
        }

        // Update label if exists
        const label = input.parentElement?.querySelector('label') ||
                     document.querySelector(`label[for="${input.id}"]`);
        if (label) {
            label.textContent = config.label;
        }

        // Update hint if exists
        const hint = input.parentElement?.querySelector('.tax-id-hint') ||
                    document.querySelector(`#${input.id}Hint`);
        if (hint) {
            hint.textContent = config.hint;
        }

        // Clear any errors when country changes
        removeError(input);
    }

    /**
     * Attach input event listener for real-time formatting
     * @param {HTMLElement} input - Tax ID input element
     * @param {HTMLElement} countrySelect - Country select element
     */
    function attachInputListener(input, countrySelect) {
        input.addEventListener('input', function(e) {
            const country = countrySelect ? countrySelect.value : 'CL';
            let value = e.target.value;

            if (country === 'CL') {
                // Only allow numbers and K
                value = value.replace(/[^0-9kK]/g, '');

                // Auto-format if it has enough characters
                if (value.length >= 2) {
                    e.target.value = formatChileanRUT(value);
                } else {
                    e.target.value = value;
                }
            }
        });
    }

    /**
     * Attach blur event listener for validation
     * @param {HTMLElement} input - Tax ID input element
     * @param {HTMLElement} countrySelect - Country select element
     */
    function attachBlurListener(input, countrySelect) {
        input.addEventListener('blur', function(e) {
            const country = countrySelect ? countrySelect.value : 'CL';
            const value = e.target.value.trim();

            // Don't validate if empty (let HTML5 required handle it)
            if (value.length === 0) {
                removeError(input);
                input.style.borderColor = '';
                input.style.borderWidth = '';
                return;
            }

            if (country === 'CL') {
                const clean = cleanRUT(value);

                if (!validateChileanRUT(clean)) {
                    showError(input, 'RUT inválido. Verifica el dígito verificador.');
                } else {
                    showSuccess(input);
                }
            }
        });
    }

    /**
     * Attach form submit validation
     * @param {HTMLElement} input - Tax ID input element
     * @param {HTMLElement} countrySelect - Country select element
     */
    function attachSubmitValidation(input, countrySelect) {
        const form = input.closest('form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const country = countrySelect ? countrySelect.value : 'CL';
            const value = input.value.trim();

            // Skip validation if field is empty and not required
            if (value.length === 0 && !input.hasAttribute('required')) {
                return true;
            }

            if (country === 'CL' && value.length > 0) {
                const clean = cleanRUT(value);
                if (!validateChileanRUT(clean)) {
                    e.preventDefault();
                    input.focus();
                    showError(input, 'RUT inválido. Verifica el dígito verificador.');
                    alert('Por favor, ingresa un RUT válido. Ejemplo: 15.895.771-k o 77.866.873-4');
                    return false;
                }
            }
        });
    }

    /**
     * Attach country change listener
     * @param {HTMLElement} input - Tax ID input element
     * @param {HTMLElement} countrySelect - Country select element
     */
    function attachCountryChangeListener(input, countrySelect) {
        if (!countrySelect) return;

        countrySelect.addEventListener('change', function(e) {
            const country = e.target.value;
            updateTaxIdPlaceholder(input, country);

            // Clear the input value when country changes
            input.value = '';
            removeError(input);
        });

        // Initialize on page load
        updateTaxIdPlaceholder(input, countrySelect.value);
    }

    /**
     * Initialize RUT validation on specific elements
     * @param {string|HTMLElement} inputSelector - Input element or selector
     * @param {string|HTMLElement} countrySelector - Country select element or selector (optional)
     * @returns {boolean} True if initialized successfully
     */
    function init(inputSelector, countrySelector = null) {
        try {
            // Get input element
            const input = typeof inputSelector === 'string'
                ? document.querySelector(inputSelector)
                : inputSelector;

            if (!input) {
                console.error('RUTValidator: Input element not found:', inputSelector);
                return false;
            }

            // Get country select element (optional)
            let countrySelect = null;
            if (countrySelector) {
                countrySelect = typeof countrySelector === 'string'
                    ? document.querySelector(countrySelector)
                    : countrySelector;
            }

            // Attach all event listeners
            attachInputListener(input, countrySelect);
            attachBlurListener(input, countrySelect);
            attachSubmitValidation(input, countrySelect);
            attachCountryChangeListener(input, countrySelect);

            // Mark as initialized
            input.setAttribute('data-rut-initialized', 'true');

            return true;
        } catch (error) {
            console.error('RUTValidator: Error initializing:', error);
            return false;
        }
    }

    /**
     * Auto-initialize all elements with data-rut-validator attribute
     */
    function autoInit() {
        const elements = document.querySelectorAll('[data-rut-validator="true"]');

        elements.forEach(input => {
            // Skip if already initialized
            if (input.getAttribute('data-rut-initialized') === 'true') {
                return;
            }

            // Get country selector from data attribute
            const countrySelector = input.getAttribute('data-country-selector');

            init(input, countrySelector);
        });
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        // DOM is already loaded, initialize immediately
        autoInit();
    }

    // Public API
    return {
        formatChileanRUT: formatChileanRUT,
        validateChileanRUT: validateChileanRUT,
        cleanRUT: cleanRUT,
        init: init,
        autoInit: autoInit,
        showError: showError,
        showSuccess: showSuccess,
        removeError: removeError,
        TAX_ID_CONFIG: TAX_ID_CONFIG
    };
})();

// Expose globally
if (typeof window !== 'undefined') {
    window.RUTValidator = RUTValidator;
}

// Support for CommonJS (Node.js)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RUTValidator;
}
