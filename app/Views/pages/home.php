<?php
declare(strict_types=1);

$homeForumPosts = is_array($homeForumPosts ?? null) ? $homeForumPosts : [];
$homeUpcomingEvents = is_array($homeUpcomingEvents ?? null) ? $homeUpcomingEvents : [];
$homeCarousel = is_array($homeCarousel ?? null) ? $homeCarousel : [];
$homeNews = is_array($homeNews ?? null) ? $homeNews : [];
?>
<div class="caja">
    <h1 class="titulo">Bienvenido a INAMANIA</h1>
    <div class="linea"></div>

    <div class="eliminar">
        <?php if ($homeCarousel !== []) : ?>
            <div id="homeHeroCarousel" class="carousel slide carousel-fade hero-carousel" data-bs-ride="carousel" data-bs-interval="3800">
                <div class="carousel-indicators">
                    <?php foreach ($homeCarousel as $index => $slide) : ?>
                        <button
                            type="button"
                            data-bs-target="#homeHeroCarousel"
                            data-bs-slide-to="<?= (int) $index ?>"
                            class="<?= $index === 0 ? 'active' : '' ?>"
                            <?= $index === 0 ? 'aria-current="true"' : '' ?>
                            aria-label="Destacada <?= (int) ($index + 1) ?>"
                        ></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner hero-carousel-inner">
                    <?php foreach ($homeCarousel as $index => $slide) : ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img
                                src="<?= asset((string) ($slide['imagen'] ?? 'uploads/imagenes/noticias/Noticia.jpg')) ?>"
                                class="d-block w-100 hero-carousel-img"
                                alt="<?= htmlspecialchars((string) ($slide['titulo'] ?? 'Destacada'), ENT_QUOTES, 'UTF-8') ?>"
                            >
                            <div class="hero-slide-caption">
                                <h3><?= htmlspecialchars((string) ($slide['titulo'] ?? 'Destacada'), ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars((string) ($slide['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
            </div>
        <?php else : ?>
            <div class="noticia2 home-foro-card">
                <h5>Sin destacadas disponibles</h5>
                <p>No hay elementos activos en el carrusel ahora mismo.</p>
            </div>
        <?php endif; ?>
        <div class="linea"></div>
    </div>

    <h1 class="titulo">Noticias</h1>
    <div class="noticias home-news-grid">
        <?php if ($homeNews === []) : ?>
            <div class="noticia2 home-foro-card">
                <h5>Sin noticias publicadas</h5>
                <p>Cuando moderación publique noticias activas aparecerán aquí.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($homeNews as $index => $news) : ?>
            <article class="flex home-news-card">
                <img
                    src="<?= asset((string) ($news['imagen'] ?? 'uploads/imagenes/noticias/Noticia.jpg')) ?>"
                    class="<?= $index % 2 === 0 ? 'img-noticia' : 'img-noticia2' ?> home-news-image"
                    alt="<?= htmlspecialchars((string) ($news['titulo'] ?? ('Noticia ' . ($index + 1))), ENT_QUOTES, 'UTF-8') ?>"
                >
                <div class="home-news-content">
                    <h3 class="home-news-title"><?= htmlspecialchars((string) ($news['titulo'] ?? ('Noticia ' . ($index + 1))), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="noticia home-news-text"><?= htmlspecialchars((string) ($news['texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="linea"></div>

    <h1 class="titulo">Juegos</h1>
    <div class="noticias home-games-grid">
        <article class="flex home-game-card">
            <a href="<?= route('ie1') ?>" class="home-game-link"><img src="<?= asset('uploads/imagenes/juegos/Inazuma_eleven_caratula.webp') ?>" class="caratula home-game-image" alt="Inazuma Eleven 1"></a>
            <div class="home-news-content">
                <h3 class="home-news-title">Inazuma Eleven</h3>
                <p class="noticia home-news-text">La historia sigue a Mark Evans y al Instituto Raimon en su camino para salvar el club y conquistar el Football Frontier.</p>
            </div>
        </article>
        <article class="flex home-game-card">
            <a href="<?= route('ie2') ?>" class="home-game-link"><img src="<?= asset('uploads/imagenes/juegos/Inazuma-Eleven-1-2-3-Box-Art.webp') ?>" class="caratula home-game-image" alt="Trilogía original"></a>
            <div class="home-news-content">
                <h3 class="home-news-title">Trilogía Original</h3>
                <p class="noticia home-news-text">La trilogía original reúne los tres primeros juegos y resume el ascenso de Raimon desde equipo débil hasta leyenda.</p>
            </div>
        </article>
    </div>

    <div class="linea"></div>

    <h1 class="titulo">Foro</h1>
    <div class="noticias home-foro-list">
        <?php if ($homeForumPosts === []) : ?>
            <div class="noticia2 home-foro-card">
                <h5>Foro en preparacion</h5>
                <p>Todavia no hay POSTS publicados. Puedes crear el primero en la seccion de foro.</p>
                <p><a href="<?= route('foro') ?>">Ir al foro</a></p>
            </div>
        <?php endif; ?>

        <?php foreach ($homeForumPosts as $forumPost) : ?>
            <div class="noticia2 home-foro-card">
                <h5><?= htmlspecialchars((string) ($forumPost['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                <p><?= htmlspecialchars(mb_strimwidth((string) ($forumPost['contenido'] ?? ''), 0, 140, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                <p><a href="<?= route('foro') . '&q=' . urlencode((string) ($forumPost['titulo'] ?? '')) ?>">Ver en foro</a></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="linea"></div>

    <h1 class="titulo">Eventos proximos</h1>
    <div class="events-container">
        <?php if ($homeUpcomingEvents === []) : ?>
            <div class="noticia2 home-foro-card">
                <h5>No hay eventos proximos con inscripcion abierta</h5>
                <p>Vuelve pronto para descubrir nuevos torneos y actividades.</p>
                <p><a href="<?= route('eventos') ?>">Ver todos los eventos</a></p>
            </div>
        <?php endif; ?>

        <?php
        $monthMap = [
            1 => 'ENE',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'ABR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AGO',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DIC',
        ];
        ?>
        <?php foreach ($homeUpcomingEvents as $event) : ?>
            <?php
            $startDate = (string) ($event['fecha_inicio'] ?? '');
            $monthLabel = '---';
            $dayLabel = '--';

            if ($startDate !== '') {
                try {
                    $start = new \DateTimeImmutable($startDate);
                    $monthLabel = $monthMap[(int) $start->format('n')] ?? strtoupper($start->format('M'));
                    $dayLabel = $start->format('d');
                } catch (\Throwable $exception) {
                    $monthLabel = '---';
                    $dayLabel = '--';
                }
            }

            $location = trim((string) ($event['lugar'] ?? ''));
            $subtitle = $location !== '' ? 'Lugar: ' . $location : 'Inscripcion disponible';
            ?>
            <div class="event-card">
                <div class="date-badge"><span class="date-month"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></span><span class="date-day"><?= htmlspecialchars($dayLabel, ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="event-details"><div class="event-title"><?= htmlspecialchars((string) ($event['titulo'] ?? 'Evento'), ENT_QUOTES, 'UTF-8') ?></div><div class="event-subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div></div>
                <a href="<?= route('eventos') ?>" class="event-button">INSCRIBIRME</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>