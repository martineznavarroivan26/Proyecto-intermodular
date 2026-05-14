<?php
declare(strict_types=1);

$actionErrors = is_array($actionErrors ?? null) ? $actionErrors : [];
$users = is_array($users ?? null) ? $users : [];
$ipBlocks = is_array($ipBlocks ?? null) ? $ipBlocks : [];
$posts = is_array($posts ?? null) ? $posts : [];
$comments = is_array($comments ?? null) ? $comments : [];
$events = is_array($events ?? null) ? $events : [];
$carouselItems = is_array($carouselItems ?? null) ? $carouselItems : [];
$newsItems = is_array($newsItems ?? null) ? $newsItems : [];
$canManageRoles = (bool) ($canManageRoles ?? false);
?>
<div class="caja moderation-page">
    <h1 class="titulo">Panel de Moderación</h1>
    <div class="linea"></div>

    <?php if ($actionErrors !== []) : ?>
        <div class="alert alert-danger" role="alert" style="margin: 1rem 5%;">
            <?= implode('<br>', array_map(static fn (string $error): string => htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $actionErrors)) ?>
        </div>
    <?php endif; ?>

    <section class="moderation-block">
        <h2 class="titulo">Usuarios: bloquear/desbloquear</h2>
        <div class="moderation-table-wrap">
            <table class="table moderation-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>IP registro</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <?php $blocked = (int) ($user['bloqueado'] ?? 0) === 1; ?>
                        <tr>
                            <td><?= (int) ($user['usuario_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($user['nombre_usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) (($user['ip_registro'] ?? null) ?? 'No disponible'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($user['rol'] ?? 'usuario'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($blocked) : ?>
                                    <span class="badge bg-danger">Bloqueado</span>
                                    <small>
                                        <?= htmlspecialchars((string) ($user['bloqueo_motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <?= isset($user['bloqueado_hasta']) && $user['bloqueado_hasta'] !== null ? '(hasta ' . htmlspecialchars((string) $user['bloqueado_hasta'], ENT_QUOTES, 'UTF-8') . ')' : '(indefinido)' ?>
                                    </small>
                                <?php else : ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canManageRoles) : ?>
                                    <form method="post" action="<?= route('moderacion') ?>" class="moderation-inline-form" style="margin-bottom: 0.35rem;">
                                        <input type="hidden" name="moderator_action" value="change_user_role">
                                        <input type="hidden" name="user_id" value="<?= (int) ($user['usuario_id'] ?? 0) ?>">
                                        <select name="new_role" required>
                                            <?php $currentRole = (string) ($user['rol'] ?? 'usuario'); ?>
                                            <option value="usuario" <?= $currentRole === 'usuario' ? 'selected' : '' ?>>usuario</option>
                                            <option value="moderador" <?= $currentRole === 'moderador' ? 'selected' : '' ?>>moderador</option>
                                            <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>admin</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Cambiar rol</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($blocked) : ?>
                                    <form method="post" action="<?= route('moderacion') ?>">
                                        <input type="hidden" name="moderator_action" value="unblock_user">
                                        <input type="hidden" name="user_id" value="<?= (int) ($user['usuario_id'] ?? 0) ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Desbloquear</button>
                                    </form>
                                <?php else : ?>
                                    <form method="post" action="<?= route('moderacion') ?>" class="moderation-inline-form">
                                        <input type="hidden" name="moderator_action" value="block_user">
                                        <input type="hidden" name="user_id" value="<?= (int) ($user['usuario_id'] ?? 0) ?>">
                                        <input type="text" name="reason" placeholder="Motivo" maxlength="255">
                                        <input type="datetime-local" name="blocked_until">
                                        <button type="submit" class="btn btn-sm btn-warning">Bloquear</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="moderation-block">
        <h2 class="titulo">Bloqueo por IP</h2>
        <form method="post" action="<?= route('moderacion') ?>" class="moderation-card-form">
            <input type="hidden" name="moderator_action" value="block_ip">
            <input type="text" name="ip" placeholder="IP (ej. 192.168.1.10)" required>
            <input type="text" name="reason" placeholder="Motivo" maxlength="255">
            <input type="datetime-local" name="blocked_until">
            <button type="submit" class="btn btn-warning">Bloquear IP</button>
        </form>

        <div class="moderation-table-wrap">
            <table class="table moderation-table">
                <thead>
                    <tr>
                        <th>IP</th>
                        <th>Motivo</th>
                        <th>Hasta</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ipBlocks as $block) : ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($block['ip'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($block['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) (($block['bloqueado_hasta'] ?? null) ?? 'Indefinido'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="<?= route('moderacion') ?>">
                                    <input type="hidden" name="moderator_action" value="unblock_ip">
                                    <input type="hidden" name="ip" value="<?= htmlspecialchars((string) ($block['ip'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Desbloquear</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="moderation-block">
        <h2 class="titulo">Foro: borrar posts y comentarios</h2>
        <div class="moderation-grid-2">
            <div>
                <h3>Posts</h3>
                <div class="moderation-table-wrap">
                    <table class="table moderation-table">
                        <thead>
                            <tr><th>ID</th><th>Título</th><th>Autor</th><th>Acción</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post) : ?>
                                <tr>
                                    <td><?= (int) ($post['post_id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string) ($post['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($post['nombre_usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form method="post" action="<?= route('moderacion') ?>" onsubmit="return confirm('¿Eliminar este post?');">
                                            <input type="hidden" name="moderator_action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?= (int) ($post['post_id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3>Comentarios</h3>
                <div class="moderation-table-wrap">
                    <table class="table moderation-table">
                        <thead>
                            <tr><th>ID</th><th>Post</th><th>Autor</th><th>Acción</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comments as $comment) : ?>
                                <tr>
                                    <td><?= (int) ($comment['comentario_id'] ?? 0) ?></td>
                                    <td>#<?= (int) ($comment['post_id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string) ($comment['nombre_usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form method="post" action="<?= route('moderacion') ?>" onsubmit="return confirm('¿Eliminar este comentario?');">
                                            <input type="hidden" name="moderator_action" value="delete_comment">
                                            <input type="hidden" name="comment_id" value="<?= (int) ($comment['comentario_id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="moderation-block">
        <h2 class="titulo">Eventos: crear y modificar</h2>

        <form method="post" action="<?= route('moderacion') ?>" class="moderation-card-form moderation-grid-form">
            <input type="hidden" name="moderator_action" value="create_event">
            <div>
                <label for="createEventTitle">Título del Evento *</label>
                <input type="text" id="createEventTitle" name="title" placeholder="Ej: Torneo Final 2024" required>
            </div>
            <div>
                <label for="createEventDescription">Descripción</label>
                <textarea id="createEventDescription" name="description" placeholder="Describe brevemente el evento..."></textarea>
            </div>
            <div>
                <label for="createEventStartDate">Fecha de Inicio *</label>
                <input type="date" id="createEventStartDate" name="start_date" required>
            </div>
            <div>
                <label for="createEventEndDate">Fecha de Fin</label>
                <input type="date" id="createEventEndDate" name="end_date">
            </div>
            <div>
                <label for="createEventRegDate">Fecha Límite Inscripción</label>
                <input type="date" id="createEventRegDate" name="registration_date">
            </div>
            <div>
                <label for="createEventLocation">Lugar</label>
                <input type="text" id="createEventLocation" name="location" placeholder="Ej: Madrid, Online, Barcelona">
            </div>
            <div>
                <label for="createEventSlots">Plazas</label>
                <input type="number" id="createEventSlots" name="slots" min="1" step="1" placeholder="Ej: 50 (vacío = ilimitado)">
            </div>
            <div>
                <label for="createEventPrice">Precio (EUR)</label>
                <input type="number" id="createEventPrice" name="price" min="0" step="0.01" value="0" placeholder="Ej: 0 = gratuito">
            </div>
            <button type="submit" class="btn btn-primary">Crear evento</button>
        </form>

        <?php foreach ($events as $event) : ?>
            <form method="post" action="<?= route('moderacion') ?>" class="moderation-card-form moderation-grid-form">
                <input type="hidden" name="moderator_action" value="update_event">
                <input type="hidden" name="event_id" value="<?= (int) ($event['evento_id'] ?? 0) ?>">
                <div>
                    <label for="updateEventTitle<?= (int) ($event['evento_id'] ?? 0) ?>">Título del Evento *</label>
                    <input type="text" id="updateEventTitle<?= (int) ($event['evento_id'] ?? 0) ?>" name="title" value="<?= htmlspecialchars((string) ($event['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Torneo Final 2024" required>
                </div>
                <div>
                    <label for="updateEventDescription<?= (int) ($event['evento_id'] ?? 0) ?>">Descripción</label>
                    <textarea id="updateEventDescription<?= (int) ($event['evento_id'] ?? 0) ?>" name="description" placeholder="Describe brevemente el evento..."><?= htmlspecialchars((string) ($event['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div>
                    <label for="updateEventStartDate<?= (int) ($event['evento_id'] ?? 0) ?>">Fecha de Inicio *</label>
                    <input type="date" id="updateEventStartDate<?= (int) ($event['evento_id'] ?? 0) ?>" name="start_date" value="<?= htmlspecialchars((string) ($event['fecha_inicio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label for="updateEventEndDate<?= (int) ($event['evento_id'] ?? 0) ?>">Fecha de Fin</label>
                    <input type="date" id="updateEventEndDate<?= (int) ($event['evento_id'] ?? 0) ?>" name="end_date" value="<?= htmlspecialchars((string) ($event['fecha_fin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                    <label for="updateEventRegDate<?= (int) ($event['evento_id'] ?? 0) ?>">Fecha Límite Inscripción</label>
                    <input type="date" id="updateEventRegDate<?= (int) ($event['evento_id'] ?? 0) ?>" name="registration_date" value="<?= htmlspecialchars((string) ($event['fecha_inscripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div>
                    <label for="updateEventLocation<?= (int) ($event['evento_id'] ?? 0) ?>">Lugar</label>
                    <input type="text" id="updateEventLocation<?= (int) ($event['evento_id'] ?? 0) ?>" name="location" value="<?= htmlspecialchars((string) ($event['lugar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Madrid, Online, Barcelona">
                </div>
                <div>
                    <label for="updateEventSlots<?= (int) ($event['evento_id'] ?? 0) ?>">Plazas</label>
                    <input type="number" id="updateEventSlots<?= (int) ($event['evento_id'] ?? 0) ?>" name="slots" min="1" step="1" value="<?= htmlspecialchars((string) (($event['plazas'] ?? null) ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 50 (vacío = ilimitado)">
                </div>
                <div>
                    <label for="updateEventPrice<?= (int) ($event['evento_id'] ?? 0) ?>">Precio (EUR)</label>
                    <input type="number" id="updateEventPrice<?= (int) ($event['evento_id'] ?? 0) ?>" name="price" min="0" step="0.01" value="<?= htmlspecialchars((string) ($event['precio'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 0 = gratuito">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-secondary" style="flex: 1;">Guardar cambios evento #<?= (int) ($event['evento_id'] ?? 0) ?></button>
                    <form method="post" action="<?= route('moderacion') ?>" style="flex: 1; margin: 0;" onsubmit="return confirm('¿Eliminar este evento? Se cancelarán todas las inscripciones.');">
                        <input type="hidden" name="moderator_action" value="delete_event">
                        <input type="hidden" name="event_id" value="<?= (int) ($event['evento_id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">Borrar</button>
                    </form>
                </div>
            </form>
        <?php endforeach; ?>
    </section>

    <section class="moderation-block">
        <h2 class="titulo">Home: carrusel</h2>
        <form method="post" action="<?= route('moderacion') ?>" class="moderation-card-form moderation-grid-form" enctype="multipart/form-data">
            <input type="hidden" name="moderator_action" value="create_carousel">
            <div>
                <label for="createCarouselTitle">Título *</label>
                <input type="text" id="createCarouselTitle" name="title" placeholder="Ej: Victory Road" required>
            </div>
            <div>
                <label for="createCarouselDescription">Descripción</label>
                <textarea id="createCarouselDescription" name="description" placeholder="Descripción breve del slide..."></textarea>
            </div>
            <div>
                <label for="createCarouselImage">Imagen *</label>
                <input type="file" id="createCarouselImage" name="image" accept="image/*" required>
            </div>
            <div>
                <label for="createCarouselOrder">Orden</label>
                <input type="number" id="createCarouselOrder" name="order" min="1" step="1" value="1" placeholder="Orden">
            </div>
            <label class="moderation-checkbox"><input type="checkbox" name="active" checked> Activo</label>
            <button type="submit" class="btn btn-primary">Añadir slide</button>
        </form>

        <?php foreach ($carouselItems as $item) : ?>
            <form method="post" action="<?= route('moderacion') ?>" class="moderation-card-form moderation-grid-form" enctype="multipart/form-data">
                <input type="hidden" name="moderator_action" value="update_carousel">
                <input type="hidden" name="carousel_id" value="<?= (int) ($item['carousel_id'] ?? 0) ?>">
                <div>
                    <label for="updateCarouselTitle<?= (int) ($item['carousel_id'] ?? 0) ?>">Título *</label>
                    <input type="text" id="updateCarouselTitle<?= (int) ($item['carousel_id'] ?? 0) ?>" name="title" value="<?= htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Victory Road" required>
                </div>
                <div>
                    <label for="updateCarouselDescription<?= (int) ($item['carousel_id'] ?? 0) ?>">Descripción</label>
                    <textarea id="updateCarouselDescription<?= (int) ($item['carousel_id'] ?? 0) ?>" name="description" placeholder="Descripción breve del slide..."><?= htmlspecialchars((string) ($item['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div>
                    <label for="updateCarouselImage<?= (int) ($item['carousel_id'] ?? 0) ?>">Imagen (dejar en blanco para mantener actual)</label>
                    <input type="file" id="updateCarouselImage<?= (int) ($item['carousel_id'] ?? 0) ?>" name="image" accept="image/*">
                    <small>Imagen actual: <?= htmlspecialchars((string) ($item['imagen'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <div>
                    <label for="updateCarouselOrder<?= (int) ($item['carousel_id'] ?? 0) ?>">Orden</label>
                    <input type="number" id="updateCarouselOrder<?= (int) ($item['carousel_id'] ?? 0) ?>" name="order" min="1" step="1" value="<?= (int) ($item['orden'] ?? 1) ?>">
                </div>
                <label class="moderation-checkbox"><input type="checkbox" name="active" <?= (int) ($item['activo'] ?? 0) === 1 ? 'checked' : '' ?>> Activo</label>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-secondary" style="flex: 1;">Guardar slide #<?= (int) ($item['carousel_id'] ?? 0) ?></button>
                    <form method="post" action="<?= route('moderacion') ?>" style="flex: 1; margin: 0;" onsubmit="return confirm('¿Eliminar este slide?');">
                        <input type="hidden" name="moderator_action" value="delete_carousel">
                        <input type="hidden" name="carousel_id" value="<?= (int) ($item['carousel_id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">Borrar</button>
                    </form>
                </div>
            </form>
        <?php endforeach; ?>
    </section>

    <section class="moderation-block">
        <h2 class="titulo">Home: noticias</h2>
        <form method="post" action="<?= route('moderacion') ?>" class="moderation-card-form moderation-grid-form" enctype="multipart/form-data">
            <input type="hidden" name="moderator_action" value="create_news">
            <div>
                <label for="createNewsTitle">Título noticia *</label>
                <input type="text" id="createNewsTitle" name="title" placeholder="Ej: Novedad principal" required>
            </div>
            <div>
                <label for="createNewsText">Texto *</label>
                <textarea id="createNewsText" name="text" placeholder="Contenido de la noticia..." required></textarea>
            </div>
            <div>
                <label for="createNewsImage">Imagen *</label>
                <input type="file" id="createNewsImage" name="image" accept="image/*" required>
            </div>
            <div>
                <label for="createNewsOrder">Orden</label>
                <input type="number" id="createNewsOrder" name="order" min="1" step="1" value="1" placeholder="Orden">
            </div>
            <label class="moderation-checkbox"><input type="checkbox" name="active" checked> Activo</label>
            <button type="submit" class="btn btn-primary">Añadir noticia</button>
        </form>

        <?php foreach ($newsItems as $item) : ?>
            <form method="post" action="<?= route('moderacion') ?>" class="moderation-card-form moderation-grid-form" enctype="multipart/form-data">
                <input type="hidden" name="moderator_action" value="update_news">
                <input type="hidden" name="news_id" value="<?= (int) ($item['noticia_id'] ?? 0) ?>">
                <div>
                    <label for="updateNewsTitle<?= (int) ($item['noticia_id'] ?? 0) ?>">Título noticia *</label>
                    <input type="text" id="updateNewsTitle<?= (int) ($item['noticia_id'] ?? 0) ?>" name="title" value="<?= htmlspecialchars((string) ($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Novedad principal" required>
                </div>
                <div>
                    <label for="updateNewsText<?= (int) ($item['noticia_id'] ?? 0) ?>">Texto *</label>
                    <textarea id="updateNewsText<?= (int) ($item['noticia_id'] ?? 0) ?>" name="text" placeholder="Contenido de la noticia..." required><?= htmlspecialchars((string) ($item['texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div>
                    <label for="updateNewsImage<?= (int) ($item['noticia_id'] ?? 0) ?>">Imagen (dejar en blanco para mantener actual)</label>
                    <input type="file" id="updateNewsImage<?= (int) ($item['noticia_id'] ?? 0) ?>" name="image" accept="image/*">
                    <small>Imagen actual: <?= htmlspecialchars((string) ($item['imagen'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <div>
                    <label for="updateNewsOrder<?= (int) ($item['noticia_id'] ?? 0) ?>">Orden</label>
                    <input type="number" id="updateNewsOrder<?= (int) ($item['noticia_id'] ?? 0) ?>" name="order" min="1" step="1" value="<?= (int) ($item['orden'] ?? 1) ?>">
                </div>
                <label class="moderation-checkbox"><input type="checkbox" name="active" <?= (int) ($item['activo'] ?? 0) === 1 ? 'checked' : '' ?>> Activo</label>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-secondary" style="flex: 1;">Guardar noticia #<?= (int) ($item['noticia_id'] ?? 0) ?></button>
                    <form method="post" action="<?= route('moderacion') ?>" style="flex: 1; margin: 0;" onsubmit="return confirm('¿Eliminar esta noticia?');">
                        <input type="hidden" name="moderator_action" value="delete_news">
                        <input type="hidden" name="news_id" value="<?= (int) ($item['noticia_id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">Borrar</button>
                    </form>
                </div>
            </form>
        <?php endforeach; ?>
    </section>
</div>
