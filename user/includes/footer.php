<!-- Footer Minimalista Profesional -->
<footer class="footer-minimal">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <span class="text-muted small">
                    &copy; <?php echo date('Y'); ?> <strong>CONECTA ERP</strong> - Sistema de Gestión Empresarial
                </span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted small">
                    <i class="fas fa-check-circle text-success"></i> v2.0
                    <span class="mx-2">|</span>
                    <i class="fas fa-shield-alt text-success"></i> SII Integrado
                    <span class="mx-2">|</span>
                    <i class="fas fa-file-alt text-success"></i> Previred
                </span>
            </div>
        </div>
    </div>
</footer>

<style>
.footer-minimal {
    background: #ffffff;
    border-top: 1px solid #e0e0e0;
    padding: 15px 0;
    margin-top: auto;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 999;
}

.footer-minimal .text-muted {
    color: #6c757d !important;
}

.footer-minimal strong {
    color: #495057;
}

/* Agregar padding al body para que el contenido no quede detrás del footer */
body {
    padding-bottom: 60px;
}
</style>
