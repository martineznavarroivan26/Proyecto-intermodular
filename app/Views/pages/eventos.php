<?php
declare(strict_types=1);

$calendarEvents = is_array($calendarEvents ?? null) ? $calendarEvents : [];
$recommendedEvents = is_array($recommendedEvents ?? null) ? $recommendedEvents : [];
$enrolledEvents = is_array($enrolledEvents ?? null) ? $enrolledEvents : [];
$enrolledEventIds = is_array($enrolledEventIds ?? null) ? $enrolledEventIds : [];
$isAuthenticated = (bool) ($isAuthenticated ?? false);
$flashError = isset($flashError) ? (string) $flashError : '';
$flashSuccess = isset($flashSuccess) ? (string) $flashSuccess : '';
$enrolledEventLookup = array_fill_keys(
    array_map(static fn ($eventId): int => (int) $eventId, $enrolledEventIds),
    true
);
?>
<div class="caja">
    <h1 class="titulo">Eventos y Torneos</h1>
    <div class="linea"></div>

    <div class="calendario-section">
        <div class="calendario-container" data-calendar-root>
            <div class="calendar-shell">
                <div class="calendar-toolbar">
                    <button type="button" class="calendar-nav-btn" data-calendar-prev aria-label="Mes anterior">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <h2 class="calendar-title" data-calendar-title>Calendario</h2>
                    <button type="button" class="calendar-nav-btn" data-calendar-next aria-label="Mes siguiente">
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>

                <p class="calendar-range-help">Puedes navegar entre los proximos 12 meses.</p>

                <div class="calendar-layout">
                    <section class="calendar-grid-panel" aria-label="Calendario mensual">
                        <div class="calendar-weekdays" aria-hidden="true">
                            <span>Lun</span>
                            <span>Mar</span>
                            <span>Mie</span>
                            <span>Jue</span>
                            <span>Vie</span>
                            <span>Sab</span>
                            <span>Dom</span>
                        </div>
                        <div class="calendar-grid" data-calendar-grid></div>
                    </section>

                    <aside class="calendar-events-panel" aria-live="polite">
                        <h3 data-calendar-selected-label>Eventos del dia</h3>
                        <div data-calendar-events-list class="calendar-events-list"></div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <script id="calendar-events-data" type="application/json"><?= json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

    <div class="linea"></div>

    <h1 class="titulo">Eventos recomendados</h1>
    <div class="events-container">
        <?php if ($flashSuccess !== '') : ?>
            <div class="event-alert event-alert-info">
                <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError !== '') : ?>
            <div class="event-alert event-alert-error">
                <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($recommendedEvents === []) : ?>
            <div class="event-alert event-alert-info">
                Todavia no hay eventos programados.
            </div>
        <?php endif; ?>

        <?php $todayDate = new \DateTimeImmutable('today'); ?>

        <?php foreach ($recommendedEvents as $event) : ?>
            <?php
            $startDate = (string) ($event['fecha_inicio'] ?? '');
            $registrationDate = (string) ($event['fecha_inscripcion'] ?? '');
            $monthLabel = '';
            $dayLabel = '';
            $totalInscritos = (int) ($event['total_inscritos'] ?? 0);
            $slots = isset($event['plazas']) && $event['plazas'] !== null ? (int) $event['plazas'] : null;
            $precio = (float) ($event['precio'] ?? 0);
            $eventId = (int) ($event['evento_id'] ?? 0);
            $isEnrolled = isset($enrolledEventLookup[$eventId]);
            $isRegistrationOpen = true;
            $isRegistrationClosed = false;
            $isSoldOut = $slots !== null && $slots > 0 && $totalInscritos >= $slots;
            $registrationHint = '';
            if ($startDate !== '') {
                $dateObject = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
                if ($dateObject instanceof \DateTimeImmutable) {
                    $monthLabel = mb_strtoupper($dateObject->format('M'), 'UTF-8');
                    $dayLabel = $dateObject->format('d');

                    if ($dateObject < $todayDate) {
                        $isRegistrationOpen = false;
                        $isRegistrationClosed = true;
                        $registrationHint = 'INSCRIPCION CERRADA';
                    }
                }
            }

            if ($registrationDate !== '' && !$isRegistrationClosed) {
                $registrationDateObject = \DateTimeImmutable::createFromFormat('Y-m-d', $registrationDate);
                if ($registrationDateObject instanceof \DateTimeImmutable && $registrationDateObject > $todayDate) {
                    $isRegistrationOpen = false;
                    $registrationHint = 'APERTURA DE INSCRIPCIONES: ' . $registrationDateObject->format('d/m/Y');
                }
            }
            ?>
            <div class="event-card">
                <div class="date-badge">
                    <span class="date-month"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="date-day"><?= htmlspecialchars($dayLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="event-details">
                    <div class="event-title"><?= htmlspecialchars((string) ($event['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="event-subtitle"><?= htmlspecialchars((string) ($event['descripcion'] ?? 'Sin descripcion'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="event-subtitle">Inscritos: <?= htmlspecialchars((string) $totalInscritos, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if ($isEnrolled) : ?>
                    <span class="event-button event-button-disabled">INSCRITO</span>
                <?php elseif ($isSoldOut) : ?>
                    <span class="event-button event-button-disabled">PLAZAS AGOTADAS</span>
                <?php elseif (!$isRegistrationOpen) : ?>
                    <span class="event-button event-button-disabled"><?= htmlspecialchars($registrationHint, ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif ($precio > 0) : ?>
                    <?php if (!$isAuthenticated) : ?>
                        <a href="#authModal" class="event-button event-button-paid" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-mode="login" data-auth-trigger="modal" title="Inicia sesion para agregar al carrito">
                            <i class="fa fa-credit-card" aria-hidden="true"></i>
                            <span class="event-paid-price"><?= htmlspecialchars(number_format($precio, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?> &euro;</span>
                            <span class="visually-hidden">Evento de pago - inicia sesion</span>
                        </a>
                    <?php else : ?>
                        <button type="button" class="event-button event-button-paid add-to-cart-btn" data-evento-id="<?= htmlspecialchars((string) ($event['evento_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-evento-titulo="<?= htmlspecialchars((string) ($event['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-evento-precio="<?= htmlspecialchars((string) $precio, ENT_QUOTES, 'UTF-8') ?>" data-evento-fecha="<?= htmlspecialchars((string) ($event['fecha_inicio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" title="Agregar al carrito">
                            <i class="fa fa-credit-card" aria-hidden="true"></i>
                            <span class="event-paid-price"><?= htmlspecialchars(number_format($precio, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?> &euro;</span>
                            <span class="visually-hidden">Evento de pago</span>
                        </button>
                    <?php endif; ?>
                <?php elseif (!$isAuthenticated) : ?>
                    <a href="#authModal" class="event-button" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-mode="login" data-auth-trigger="modal">UNIRME</a>
                <?php else : ?>
                    <button type="button" class="event-button join-free-event-btn" data-evento-id="<?= htmlspecialchars((string) $eventId, ENT_QUOTES, 'UTF-8') ?>">UNIRME</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($isAuthenticated) : ?>
        <div class="linea"></div>
        <h1 class="titulo" id="eventos-inscritos">Eventos a los que estas inscrito</h1>
        <div class="events-container">
            <?php if ($enrolledEvents === []) : ?>
                <div class="event-alert event-alert-info">
                    Todavia no estas inscrito en ningun evento.
                </div>
            <?php endif; ?>

            <?php foreach ($enrolledEvents as $event) : ?>
                <?php
                $startDate = (string) ($event['fecha_inicio'] ?? '');
                $monthLabel = '';
                $dayLabel = '';
                if ($startDate !== '') {
                    $dateObject = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
                    if ($dateObject instanceof \DateTimeImmutable) {
                        $monthLabel = mb_strtoupper($dateObject->format('M'), 'UTF-8');
                        $dayLabel = $dateObject->format('d');
                    }
                }
                ?>
                <div class="event-card">
                    <div class="date-badge">
                        <span class="date-month"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="date-day"><?= htmlspecialchars($dayLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="event-details">
                        <div class="event-title"><?= htmlspecialchars((string) ($event['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="event-subtitle"><?= htmlspecialchars((string) ($event['descripcion'] ?? 'Sin descripcion'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <button type="button" class="event-button event-button-danger leave-event-btn" data-evento-id="<?= htmlspecialchars((string) ($event['evento_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">DESINSCRIBIRME</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="linea"></div>
    <h1 class="titulo">Información Importante</h1>
    <div class="grid-eventos">
        <div class="evento-card-grande"><h2>Cómo Participar</h2><p>Regístrate, explora el calendario y confirma tu inscripción en el evento deseado.</p></div>
        <div class="evento-card-grande"><h2>Sistema de Premios</h2><p>Los premios varían según el evento e incluyen merchandising, códigos y reconocimientos.</p></div>
    </div>
</div>