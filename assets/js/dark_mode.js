/**
 * DARK MODE TOGGLE - CONECTA ERP
 * Sistema de tema oscuro con persistencia en localStorage
 * Soporta: light, dark, auto (sigue preferencias del sistema)
 */

const DarkMode = {
    // Configuración
    storageKey: 'conecta_theme',
    defaultTheme: 'light',

    // Inicializar dark mode
    init() {
        this.loadTheme();
        this.setupListeners();
        this.watchSystemPreference();
    },

    // Cargar tema guardado o detectar preferencia del sistema
    loadTheme() {
        const savedTheme = localStorage.getItem(this.storageKey);

        if (savedTheme) {
            this.applyTheme(savedTheme);
        } else {
            // Si no hay tema guardado, usar preferencia del sistema
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.applyTheme(prefersDark ? 'dark' : 'light');
        }

        // Actualizar el selector si existe
        this.updateSelector();
    },

    // Aplicar tema
    applyTheme(theme) {
        const html = document.documentElement;

        if (theme === 'auto') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            theme = prefersDark ? 'dark' : 'light';
        }

        // Remover clases anteriores
        html.classList.remove('light-theme', 'dark-theme');

        // Agregar clase del tema actual
        html.classList.add(`${theme}-theme`);

        // Guardar en localStorage
        localStorage.setItem(this.storageKey, theme);

        // Actualizar meta theme-color para navegadores móviles
        this.updateMetaThemeColor(theme);

        // Disparar evento personalizado
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    },

    // Toggle entre light y dark
    toggle() {
        const currentTheme = this.getCurrentTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
        this.updateSelector();
    },

    // Obtener tema actual
    getCurrentTheme() {
        return document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light';
    },

    // Configurar event listeners
    setupListeners() {
        // Toggle button (icono de luna/sol)
        const toggleBtns = document.querySelectorAll('[data-theme-toggle]');
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
                this.updateToggleButton(btn);
            });
        });

        // Theme selector (dropdown con opciones light/dark/auto)
        const themeSelectors = document.querySelectorAll('[data-theme-selector]');
        themeSelectors.forEach(selector => {
            selector.addEventListener('change', (e) => {
                this.applyTheme(e.target.value);
            });
        });
    },

    // Actualizar botón toggle (cambiar icono)
    updateToggleButton(btn) {
        const theme = this.getCurrentTheme();
        const icon = btn.querySelector('i');

        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Actualizar aria-label para accesibilidad
        btn.setAttribute('aria-label',
            theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'
        );
    },

    // Actualizar selector dropdown
    updateSelector() {
        const savedTheme = localStorage.getItem(this.storageKey);
        const selectors = document.querySelectorAll('[data-theme-selector]');

        selectors.forEach(selector => {
            if (savedTheme) {
                selector.value = savedTheme;
            }
        });
    },

    // Actualizar meta theme-color para navegadores móviles
    updateMetaThemeColor(theme) {
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');

        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }

        // Colores del tema
        const colors = {
            light: '#667eea',
            dark: '#1a202c'
        };

        metaThemeColor.content = colors[theme] || colors.light;
    },

    // Observar cambios en preferencias del sistema
    watchSystemPreference() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        mediaQuery.addEventListener('change', (e) => {
            const savedTheme = localStorage.getItem(this.storageKey);

            // Solo auto-cambiar si el usuario seleccionó "auto"
            if (savedTheme === 'auto') {
                this.applyTheme('auto');
            }
        });
    },

    // API pública para desarrolladores
    setTheme(theme) {
        if (['light', 'dark', 'auto'].includes(theme)) {
            this.applyTheme(theme);
            this.updateSelector();
        } else {
            console.error('Tema inválido. Use: light, dark, o auto');
        }
    },

    getTheme() {
        return localStorage.getItem(this.storageKey) || this.defaultTheme;
    }
};

// Inicializar dark mode cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => DarkMode.init());
} else {
    DarkMode.init();
}

// Exportar para uso global
window.DarkMode = DarkMode;
