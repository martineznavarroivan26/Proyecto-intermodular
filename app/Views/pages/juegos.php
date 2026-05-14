<?php
declare(strict_types=1);

$gamesBySaga = is_array($gamesBySaga ?? null) ? $gamesBySaga : [];
?>
<div class="caja">
    <h1 class="titulo">Saga de Juegos de Inazuma Eleven</h1>
    <div class="linea"></div>

    <?php foreach ($gamesBySaga as $sagaName => $games) : ?>
        <h2 class="titulo juego-saga-title"><?= htmlspecialchars((string) $sagaName, ENT_QUOTES, 'UTF-8') ?></h2>
        <div class="juegos-saga-grid">
            <?php foreach ($games as $game) : ?>
                <a class="juego-card-link" href="<?= route((string) ($game['route'] ?? 'juegos')) ?>">
                    <article class="juego-card-item">
                        <img
                            src="<?= asset((string) ($game['image'] ?? 'uploads/imagenes/juegos/Inazuma_eleven_caratula.webp')) ?>"
                            class="caratula juego-caratula"
                            alt="<?= htmlspecialchars((string) ($game['title'] ?? 'Juego'), ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <div class="noticia juego-card-bubble">
                            <strong><?= htmlspecialchars((string) ($game['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <?= htmlspecialchars((string) ($game['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </article>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="linea"></div>
    <?php endforeach; ?>
</div>