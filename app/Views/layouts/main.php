<?php
declare(strict_types=1);

require __DIR__ . '/head.php';
require __DIR__ . '/navbar.php';
if ($flashSuccess !== null && $flashSuccess !== '') :
?>
<div id="flash-success-notification" class="alert alert-success" role="alert" style="position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: min(90vw, 28rem); max-width: 90vw; box-shadow: 0 12px 28px rgba(0, 0, 0, 0.3);">
	<?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php
endif;

echo '<main>';
echo $content;
require __DIR__ . '/login-modal.php';
require __DIR__ . '/perfil-modal.php';
require __DIR__ . '/footer.php';