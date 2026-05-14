<?php
declare(strict_types=1);

$forumPosts = is_array($forumPosts ?? null) ? $forumPosts : [];
$forumCategories = is_array($forumCategories ?? null) ? $forumCategories : [];
$forumPopularPosts = is_array($forumPopularPosts ?? null) ? $forumPopularPosts : [];
$forumCommentsByPost = is_array($forumCommentsByPost ?? null) ? $forumCommentsByPost : [];
$forumSearch = (string) ($forumSearch ?? '');
$forumCategory = (string) ($forumCategory ?? '');
$forumView = (string) ($forumView ?? '');
$forumBaseRoute = (string) ($forumBaseRoute ?? route('foro'));
$forumErrors = is_array($forumErrors ?? null) ? $forumErrors : [];
$postErrors = is_array($postErrors ?? null) ? $postErrors : [];
$postFormData = is_array($postFormData ?? null) ? $postFormData : [];
$isAuthenticated = (bool) ($isAuthenticated ?? false);
$allowedCategories = is_array($allowedCategories ?? null) ? $allowedCategories : [];
$openCreatePostPanel = isset($openCreatePostPanel) ? (bool) $openCreatePostPanel : ($postErrors !== []);
$currentForumPage = (int) ($currentForumPage ?? 1);
$totalForumPages = (int) ($totalForumPages ?? 1);
$perPageOptions = is_array($perPageOptions ?? null) ? $perPageOptions : [5, 15, 25, 50];
$currentPerPage = (int) ($currentPerPage ?? 5);
$totalForumPosts = (int) ($totalForumPosts ?? count($forumPosts));

$formTitle = (string) ($postFormData['title'] ?? '');
$formCategory = (string) ($postFormData['category'] ?? 'General');
$formContent = (string) ($postFormData['content'] ?? '');

// Convierte timestamp a etiqueta relativa para UI (horas, dias, semanas).
$timeAgoLabel = static function (?string $dateTime): string {
    if ($dateTime === null || $dateTime === '') {
        return '1 hora';
    }

    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return '1 hora';
    }

    $seconds = time() - $timestamp;
    if ($seconds < 0) {
        $seconds = 0;
    }

    $hours = (int) floor($seconds / 3600);
    if ($hours < 24) {
        $hours = max(1, $hours);
        return $hours . ' ' . ($hours === 1 ? 'hora' : 'horas');
    }

    $days = (int) floor($seconds / 86400);
    if ($days < 7) {
        $days = max(1, $days);
        return $days . ' ' . ($days === 1 ? 'dia' : 'dias');
    }

    $weeks = (int) floor($seconds / 604800);
    $weeks = max(1, $weeks);

    return $weeks . ' ' . ($weeks === 1 ? 'semana' : 'semanas');
};
?>
<div class="caja">
    <h1 class="titulo"><?= $forumView === 'favorites' ? 'Foro - Tus favoritos' : 'Foro de la Comunidad' ?></h1>
    <div class="linea"></div>

    <div style="margin: 20px 5%;">
        <form action="<?= $forumBaseRoute ?>" method="get" class="search-bar" data-forum-search-form>
            <label for="foro-search-input" class="visually-hidden">Buscar en el foro</label>
            <input type="text" id="foro-search-input" name="q" value="<?= htmlspecialchars($forumSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar en el foro..." data-forum-search-input>
            <input type="hidden" name="per_page" value="<?= $currentPerPage ?>">
            <?php if ($forumCategory !== '') : ?>
                <input type="hidden" name="cat" value="<?= htmlspecialchars($forumCategory, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <button type="submit"><i class="fa fa-search"></i> Buscar</button>
        </form>
    </div>

    <?php if ($forumErrors !== []) : ?>
        <div class="alert alert-danger" role="alert" style="margin: 0 5% 1rem;">
            <?= implode('<br>', array_map(static fn (string $error): string => htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $forumErrors)) ?>
        </div>
    <?php endif; ?>

    <div class="foro-container" data-forum-browser>
        <div class="foro-main">
            <div class="forum-top-actions">
                <?php if ($isAuthenticated) : ?>
                    <button
                        type="button"
                        class="new-thread-btn"
                        id="toggle-create-post"
                        aria-expanded="<?= $openCreatePostPanel ? 'true' : 'false' ?>"
                        aria-controls="create-post-panel"
                    >
                        <i class="fa fa-plus"></i> Crea un POST
                    </button>
                <?php else : ?>
                    <a href="#authModal" class="new-thread-btn" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-mode="login" data-auth-trigger="modal"><i class="fa fa-plus"></i> Inicia sesion para crear un POST</a>
                <?php endif; ?>

                <div class="forum-view-filters">
                    <a href="<?= route('foro') ?>" class="btn btn-sm <?= $forumView === 'favorites' ? 'btn-outline-secondary' : 'btn-primary' ?>">Todos los POSTS</a>
                    <?php if ($isAuthenticated) : ?>
                        <a href="<?= route('foro') . '&view=favorites' ?>" class="btn btn-sm <?= $forumView === 'favorites' ? 'btn-primary' : 'btn-outline-secondary' ?>">Mis favoritos</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isAuthenticated) : ?>

                <div class="new-thread-panel" id="create-post-panel"<?= $openCreatePostPanel ? '' : ' hidden' ?>>
                    <h3 class="new-thread-title"><i class="fa fa-plus"></i> Crea un POST</h3>

                    <?php if ($postErrors !== []) : ?>
                        <div class="alert alert-danger" role="alert">
                            <?= implode('<br>', array_map(static fn (string $error): string => htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $postErrors)) ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= route('foro') ?>" method="post" class="new-thread-form">
                        <input type="hidden" name="foro_action" value="create_post">
                        <div class="new-thread-grid">
                            <label>
                                Titulo
                                <input type="text" name="title" maxlength="100" required value="<?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label>
                                Categoria
                                <select name="category" required>
                                    <?php foreach ($allowedCategories as $allowedCategory) : ?>
                                        <option value="<?= htmlspecialchars((string) $allowedCategory, ENT_QUOTES, 'UTF-8') ?>"<?= $formCategory === (string) $allowedCategory ? ' selected' : '' ?>>
                                            <?= htmlspecialchars((string) $allowedCategory, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <label>
                            Contenido
                            <textarea name="content" rows="4" required><?= htmlspecialchars($formContent, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </label>
                        <button type="submit" class="new-thread-btn"><i class="fa fa-paper-plane"></i> Publicar POST</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($forumPosts === []) : ?>
                <div class="thread-card">
                    <div class="thread-content">No hay POSTS para los filtros seleccionados.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($forumPosts as $post) : ?>
                <?php $postId = (int) ($post['post_id'] ?? 0); ?>
                <?php $postComments = is_array($forumCommentsByPost[$postId] ?? null) ? $forumCommentsByPost[$postId] : []; ?>
                <?php $postAuthorName = (string) ($post['nombre_usuario'] ?? 'Usuario'); ?>
                <?php $postSearchBlob = mb_strtolower(trim(
                    // Blob de busqueda cliente para filtrar por titulo, contenido, categoria y autor.
                    (string) ($post['titulo'] ?? '') . ' ' .
                    (string) ($post['contenido'] ?? '') . ' ' .
                    (string) ($post['categoria'] ?? '') . ' ' .
                    $postAuthorName
                ), 'UTF-8'); ?>
                <?php $postAvatarPath = trim((string) ($post['avatar'] ?? '')); ?>
                <?php $postAvatarSrc = ''; ?>
                <?php if ($postAvatarPath !== '') : ?>
                    <?php if (preg_match('/^(https?:)?\/\//', $postAvatarPath) === 1) : ?>
                        <?php $postAvatarSrc = $postAvatarPath; ?>
                    <?php elseif (str_starts_with($postAvatarPath, 'uploads/avatars/')) : ?>
                        <?php $postAvatarSrc = asset($postAvatarPath); ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?php $postAvatarInitial = $postAuthorName !== '' ? strtoupper(substr($postAuthorName, 0, 1)) : 'U'; ?>
                <div class="thread-card" data-forum-post data-search="<?= htmlspecialchars($postSearchBlob, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="thread-author-avatar" aria-label="Avatar del autor">
                        <?php if ($postAvatarSrc !== '') : ?>
                            <img src="<?= htmlspecialchars($postAvatarSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar de <?= htmlspecialchars($postAuthorName, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else : ?>
                            <span class="thread-author-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($postAvatarInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="thread-card-body">
                        <div class="thread-header">
                            <h3 class="thread-title"><?= htmlspecialchars((string) ($post['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                        <div class="thread-meta">
                            <span><i class="fa fa-user"></i> <?= htmlspecialchars($postAuthorName, ENT_QUOTES, 'UTF-8') ?></span>
                            <span><i class="fa fa-clock-o"></i> <?= htmlspecialchars($timeAgoLabel((string) ($post['creado_en'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                            <span><i class="fa fa-tag"></i> <?= htmlspecialchars((string) ($post['categoria'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="thread-content"><?= nl2br(htmlspecialchars((string) ($post['contenido'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                        <div class="thread-stats">
                        <button type="button" class="comments-toggle-btn" data-post-id="<?= $postId ?>" aria-controls="comments-panel-<?= $postId ?>" aria-expanded="false">
                            <i class="fa fa-comments"></i> <?= (int) ($post['total_comentarios'] ?? 0) ?> comentarios
                        </button>
                        <?php if ($isAuthenticated) : ?>
                            <form action="<?= $forumBaseRoute ?>" method="post" class="post-action-form thread-stats-favorite-form">
                                <input type="hidden" name="foro_action" value="toggle_favorite">
                                <input type="hidden" name="post_id" value="<?= $postId ?>">
                                <button type="submit" class="favorite-toggle-btn<?= (int) ($post['user_favorited'] ?? 0) === 1 ? ' is-active' : '' ?>" aria-label="<?= (int) ($post['user_favorited'] ?? 0) === 1 ? 'Quitar favorito' : 'Agregar favorito' ?>">
                                    <span class="favorite-heart<?= (int) ($post['user_favorited'] ?? 0) === 1 ? ' is-active' : '' ?>" aria-hidden="true"><?= (int) ($post['user_favorited'] ?? 0) === 1 ? '♥' : '♡' ?></span>
                                    <?php if ((int) ($post['user_favorited'] ?? 0) === 1) : ?>
                                        <span class="favorite-count"><?= (int) ($post['total_favoritos'] ?? 0) ?></span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php else : ?>
                            <a href="#authModal" class="favorite-toggle-btn" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-mode="login" data-auth-trigger="modal" aria-label="Inicia sesion para agregar favorito">
                                <span class="favorite-heart" aria-hidden="true">♡</span>
                            </a>
                        <?php endif; ?>
                        </div>

                        <div class="comments-panel" id="comments-panel-<?= $postId ?>" hidden>
                            <div class="thread-comments">
                                <h4>Comentarios</h4>
                                <?php if ($postComments === []) : ?>
                                    <p class="comment-empty">Todavia no hay comentarios.</p>
                                <?php endif; ?>

                                <?php foreach ($postComments as $comment) : ?>
                                    <article class="comment-item">
                                        <div class="comment-meta">
                                            <strong><?= htmlspecialchars((string) ($comment['nombre_usuario'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) ($comment['creado_en'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <p><?= nl2br(htmlspecialchars((string) ($comment['contenido'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($isAuthenticated) : ?>
                                <form action="<?= $forumBaseRoute ?>" method="post" class="comment-form">
                                    <input type="hidden" name="foro_action" value="create_comment">
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <textarea name="comment_content" rows="2" maxlength="1000" required placeholder="Escribe tu comentario..."></textarea>
                                    <button type="submit" class="post-action-btn">
                                        <i class="fa fa-comment"></i> Comentar
                                    </button>
                                </form>
                            <?php else : ?>
                                <div style="text-align: center; padding: 0.75rem; color: #555;">
                                    <a href="#authModal" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-mode="login" data-auth-trigger="modal" style="color: #1E90FF; text-decoration: none; font-weight: 600;">
                                        Inicia sesion para comentar
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="thread-card" data-forum-empty hidden>
                <div class="thread-content">No hay POSTS que coincidan con la busqueda.</div>
            </div>

            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($currentForumPage > 1) : ?>
                        <a href="<?= $forumBaseRoute . '&p=1&per_page=' . $currentPerPage . ($forumSearch !== '' ? '&q=' . urlencode($forumSearch) : '') . ($forumCategory !== '' ? '&cat=' . urlencode($forumCategory) : '') ?>" class="pagination-btn" title="Primera página">
                            <i class="fa fa-chevron-left"></i>
                        </a>
                    <?php else : ?>
                        <button type="button" class="pagination-btn" disabled>
                            <i class="fa fa-chevron-left"></i>
                        </button>
                    <?php endif; ?>

                    <button type="button" class="pagination-current" disabled>
                        <?= $currentForumPage ?>
                    </button>

                    <?php if ($currentForumPage < $totalForumPages) : ?>
                        <a href="<?= $forumBaseRoute . '&p=' . $totalForumPages . '&per_page=' . $currentPerPage . ($forumSearch !== '' ? '&q=' . urlencode($forumSearch) : '') . ($forumCategory !== '' ? '&cat=' . urlencode($forumCategory) : '') ?>" class="pagination-btn" title="Última página">
                            <i class="fa fa-chevron-right"></i>
                        </a>
                    <?php else : ?>
                        <button type="button" class="pagination-btn" disabled>
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="pagination-options">
                    <label for="per-page-select">Mostrar por página:</label>
                    <select
                        id="per-page-select"
                        name="per_page"
                        class="per-page-select"
                        onchange="window.location.href = this.value;"
                    >
                        <?php foreach ($perPageOptions as $option) : ?>
                            <option
                                value="<?= $forumBaseRoute . '&p=1&per_page=' . $option . ($forumSearch !== '' ? '&q=' . urlencode($forumSearch) : '') . ($forumCategory !== '' ? '&cat=' . urlencode($forumCategory) : '') ?>"
                                <?= $option === $currentPerPage ? ' selected' : '' ?>
                            >
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($totalForumPosts > 0 && $totalForumPosts < $currentPerPage) : ?>
                        <small class="per-page-hint">Mostrando todos los disponibles: <?= $totalForumPosts ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="foro-sidebar">
            <div class="categoria-box">
                <h3><i class="fa fa-list"></i> Categorias</h3>
                <ul>
                    <li><a href="<?= $forumBaseRoute ?>">Todas</a></li>
                    <?php foreach ($forumCategories as $category) : ?>
                        <li>
                            <a href="<?= $forumBaseRoute . '&cat=' . urlencode((string) $category) ?>">
                                <?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="categoria-box">
                <h3><i class="fa fa-fire"></i> Populares</h3>
                <ul>
                    <?php if ($forumPopularPosts === []) : ?>
                        <li>Sin datos disponibles</li>
                    <?php endif; ?>

                    <?php foreach ($forumPopularPosts as $popularPost) : ?>
                        <li>
                            <a href="<?= $forumBaseRoute . '&q=' . urlencode((string) ($popularPost['titulo'] ?? '')) ?>">
                                <?= htmlspecialchars((string) ($popularPost['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('estilos/js/foro.js') ?>"></script>