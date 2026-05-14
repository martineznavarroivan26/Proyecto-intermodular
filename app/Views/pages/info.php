<?php
declare(strict_types=1);
?>
<div class="caja">
    <h1 class="titulo"><?= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="linea"></div>
    <div class="foro-container">
        <div class="foro-main">
            <div class="thread-card">
                <div class="thread-content">
                    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <a href="<?= route('home') ?>" class="new-thread-btn">Volver al inicio</a>
        </div>
    </div>
</div>