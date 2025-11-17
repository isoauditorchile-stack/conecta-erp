<!-- ============================================ -->
<!-- FOOTER PROFESIONAL - CONECTA ERP -->
<!-- ============================================ -->

<footer class="footer mt-auto py-4 bg-dark text-white">
    <div class="container">
        <div class="row">
            <!-- Columna 1: Información de la empresa -->
            <div class="col-md-4 mb-3">
                <h5 class="text-warning">
                    <i class="fas fa-link"></i> CONECTA ERP
                </h5>
                <p class="small mb-2">Sistema de Gestión Empresarial Integral</p>
                <p class="small text-muted">
                    Solución completa para la gestión de ventas, compras, inventario,
                    contabilidad, recursos humanos y más.
                </p>
                <div class="mt-2">
                    <span class="badge bg-success">v2.0.0</span>
                    <span class="badge bg-info">Chile</span>
                </div>
            </div>

            <!-- Columna 2: Enlaces rápidos -->
            <div class="col-md-4 mb-3">
                <h6 class="text-warning mb-3">Enlaces Rápidos</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <a href="/user/dashboard.php" class="text-white-50 text-decoration-none">
                            <i class="fas fa-home fa-fw"></i> Dashboard
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/user/suscripcion.php" class="text-white-50 text-decoration-none">
                            <i class="fas fa-crown fa-fw"></i> Mi Suscripción
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/docs/manual_usuario.pdf" class="text-white-50 text-decoration-none" target="_blank">
                            <i class="fas fa-book fa-fw"></i> Manual de Usuario
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="https://api.whatsapp.com/send?phone=56912345678" class="text-white-50 text-decoration-none" target="_blank">
                            <i class="fab fa-whatsapp fa-fw"></i> Soporte Técnico
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Columna 3: Contacto y redes sociales -->
            <div class="col-md-4 mb-3">
                <h6 class="text-warning mb-3">Contacto</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2 text-white-50">
                        <i class="fas fa-envelope fa-fw"></i> soporte@conectaerp.cl
                    </li>
                    <li class="mb-2 text-white-50">
                        <i class="fas fa-phone fa-fw"></i> +56 9 1234 5678
                    </li>
                    <li class="mb-2 text-white-50">
                        <i class="fas fa-map-marker-alt fa-fw"></i> Santiago, Chile
                    </li>
                </ul>

                <!-- Redes Sociales -->
                <div class="mt-3">
                    <h6 class="text-warning mb-2">Síguenos</h6>
                    <a href="#" class="text-white-50 me-3" target="_blank" title="Facebook">
                        <i class="fab fa-facebook fa-lg"></i>
                    </a>
                    <a href="#" class="text-white-50 me-3" target="_blank" title="Twitter">
                        <i class="fab fa-twitter fa-lg"></i>
                    </a>
                    <a href="#" class="text-white-50 me-3" target="_blank" title="LinkedIn">
                        <i class="fab fa-linkedin fa-lg"></i>
                    </a>
                    <a href="#" class="text-white-50 me-3" target="_blank" title="Instagram">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <a href="#" class="text-white-50" target="_blank" title="YouTube">
                        <i class="fab fa-youtube fa-lg"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Separador -->
        <hr class="border-secondary my-3">

        <!-- Línea de copyright y enlaces legales -->
        <div class="row">
            <div class="col-md-6 text-center text-md-start small">
                <p class="mb-0 text-white-50">
                    &copy; <?php echo date('Y'); ?> <strong>CONECTA ERP</strong>. Todos los derechos reservados.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end small">
                <a href="/legal/terminos.php" class="text-white-50 text-decoration-none me-3">Términos de Uso</a>
                <a href="/legal/privacidad.php" class="text-white-50 text-decoration-none me-3">Política de Privacidad</a>
                <a href="/legal/cookies.php" class="text-white-50 text-decoration-none">Cookies</a>
            </div>
        </div>

        <!-- Cumplimiento normativo chileno -->
        <div class="row mt-3">
            <div class="col-12 text-center">
                <p class="small text-white-50 mb-0">
                    <i class="fas fa-check-circle text-success"></i> Sistema cumple con normativa SII ·
                    <i class="fas fa-check-circle text-success"></i> Facturación Electrónica ·
                    <i class="fas fa-check-circle text-success"></i> Ley de Protección de Datos Personales
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- ============================================ -->
<!-- Estilos adicionales para el footer -->
<!-- ============================================ -->
<style>
footer.footer {
    margin-top: 3rem;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
    border-top: 3px solid #ffc107;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

footer.footer a:hover {
    color: #ffc107 !important;
    transition: color 0.3s ease;
}

footer.footer .fab:hover,
footer.footer .fas:hover {
    transform: scale(1.2);
    transition: transform 0.3s ease;
}

/* Hacer que el footer quede al final de la página */
html, body {
    height: 100%;
}

body {
    display: flex;
    flex-direction: column;
}

.content-wrapper {
    flex: 1 0 auto;
}

footer.footer {
    flex-shrink: 0;
}

/* Responsive */
@media (max-width: 768px) {
    footer.footer .col-md-4 {
        text-align: center;
    }

    footer.footer ul {
        padding-left: 0;
    }
}
</style>
