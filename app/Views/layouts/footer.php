<?php
declare(strict_types=1);
?>
</main>
<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>Contacto</h3>
            <p>Email: info@inamania.com</p>
            <p>Teléfono: +34 123 456 789</p>
        </div>
        <div class="footer-section">
            <h3>Legal</h3>
            <a href="<?= route('terminos') ?>">Términos y Condiciones</a>
            <a href="<?= route('privacidad') ?>">Política de Privacidad</a>
            <a href="<?= route('aviso-legal') ?>">Aviso Legal</a>
        </div>
        <div class="footer-section">
            <h3>Síguenos</h3>
            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer"><i class="fa fa-facebook-square"></i> Facebook</a>
            <a href="https://x.com" target="_blank" rel="noopener noreferrer"><i class="fa fa-twitter-square"></i> Twitter</a>
            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer"><i class="fa fa-instagram"></i> Instagram</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&COPY; INAMANIA - Todos los derechos reservados</p>
    </div>
</footer>
<script src="<?= asset('estilos/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('estilos/js/custom-cursor.js') ?>"></script>
<script src="<?= asset('estilos/js/auth-modal.js') ?>"></script>
<script src="<?= asset('estilos/js/profile-modal.js') ?>"></script>
<script src="<?= asset('estilos/js/flash-notification.js') ?>"></script>
<?php foreach (($scripts ?? []) as $script) : ?>
<script src="<?= asset((string) $script) ?>"></script>
<?php endforeach; ?>
</body>
</html>