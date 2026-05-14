<?php
declare(strict_types=1);

$currentPage = $currentPage ?? 'home';
$sessionUser = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;
$isAuthenticated = $sessionUser !== null;
$displayName = $isAuthenticated ? (string) ($sessionUser['nombre_usuario'] ?? 'Usuario') : '';
$avatarPath = $isAuthenticated ? trim((string) ($sessionUser['avatar'] ?? '')) : '';
$avatarSrc = '';
if ($avatarPath !== '') {
    if (preg_match('/^(https?:)?\/\//', $avatarPath) === 1) {
        $avatarSrc = $avatarPath;
    } elseif (str_starts_with($avatarPath, 'uploads/avatars/')) {
        $avatarSrc = asset($avatarPath);
    }
}
$avatarInitial = $displayName !== '' ? strtoupper(substr($displayName, 0, 1)) : 'U';
$sessionRole = $isAuthenticated ? (string) ($sessionUser['rol'] ?? 'usuario') : 'usuario';
$isModeratorOrAdmin = in_array($sessionRole, ['moderador', 'admin'], true);
// Centraliza la clase de activo para no repetir condiciones en cada enlace del menu.
$isActive = static function (string $pageName) use ($currentPage): string {
    return $currentPage === $pageName ? 'is-active' : '';
};
$cartCount = $isAuthenticated && isset($_SESSION['carrito']) && is_array($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;
$navGames = is_array($navGames ?? null) ? $navGames : [];
?>
<nav>
    <ul>
        <li class="nav-brand">
            <a href="<?= route('home') ?>">
                <img src="<?= asset('estilos/src/Inazuma_Eleven_Logo_1.webp') ?>" alt="Logo Inazuma Eleven">
            </a>
        </li>
        <li class="nav-menu-wrapper">
            <button
                class="nav-mobile-toggle navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavLinks"
                aria-controls="mainNavLinks"
                aria-expanded="false"
                aria-label="Abrir menú principal"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul id="mainNavLinks" class="main-nav-links collapse d-md-flex">
                <li class="menu">
                    <a href="<?= route('juegos') ?>" class="site-nav-link <?= $isActive('juegos') ?>">Juegos</a>
                    <ul class="submenu">
                        <?php foreach ($navGames as $gameMenuItem) : ?>
                            <?php
                            $routeKey = (string) ($gameMenuItem['route'] ?? '');
                            $title = (string) ($gameMenuItem['title'] ?? 'Juego');
                            if ($routeKey === '') {
                                continue;
                            }
                            ?>
                            <li><a href="<?= route($routeKey) ?>" class="site-nav-link"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></a></li>
                        <?php endforeach; ?>
                        <?php if ($navGames === []) : ?>
                            <li><span class="site-nav-link" aria-disabled="true">Sin juegos disponibles</span></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <li><a href="<?= route('foro') ?>" class="site-nav-link <?= $isActive('foro') ?>">Foro</a></li>
                <?php if ($currentPage === 'eventos') : ?>
                    <li>
                        <a href="<?= $isAuthenticated && $cartCount > 0 ? route('carrito') : route('eventos') ?>" class="site-nav-link <?= $isActive('eventos') ?>" aria-label="Eventos">
                            <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                            <?php if ($isAuthenticated && $cartCount > 0) : ?>
                                <span class="cart-badge"><?= htmlspecialchars((string) $cartCount, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span class="visually-hidden">Eventos<?php if ($cartCount > 0) : ?> - <?= htmlspecialchars((string) $cartCount, ENT_QUOTES, 'UTF-8') ?> en carrito<?php endif; ?></span>
                        </a>
                    </li>
                <?php else : ?>
                    <!-- Fuera de eventos, el carrito cuelga del submenu para no sobrecargar la barra principal. -->
                    <li class="menu events-menu <?= $isAuthenticated && $cartCount > 0 ? 'has-cart' : '' ?>">
                        <a href="<?= route('eventos') ?>" class="site-nav-link <?= $isActive('eventos') ?>" aria-label="Eventos">Eventos</a>
                        <?php if ($isAuthenticated && $cartCount > 0) : ?>
                            <ul class="submenu events-submenu">
                                <li>
                                    <a href="<?= route('carrito') ?>" class="site-nav-link" aria-label="Carrito">
                                        Carrito
                                        <span class="cart-badge"><?= htmlspecialchars((string) $cartCount, ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
                <?php if ($isModeratorOrAdmin) : ?>
                    <li class="menu">
                        <a href="<?= route('moderacion') ?>" class="site-nav-link <?= $isActive('moderacion') ?>">Moderación</a>
                        <ul class="submenu">
                            <li><a href="<?= route('moderacion') ?>" class="site-nav-link">Panel de Moderación</a></li>
                            <li><a href="<?= route('admin') ?>" class="site-nav-link">Control de Contenido</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if ($isAuthenticated) : ?>
                    <li class="menu user-menu">
                        <a href="#profileModal" class="log-in site-nav-link user-menu-trigger" data-bs-toggle="modal" data-bs-target="#profileModal" data-profile-trigger="modal">
                            <span class="user-menu-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="user-avatar">
                                <?php if ($avatarSrc !== '') : ?>
                                    <img src="<?= htmlspecialchars($avatarSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar de <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
                                <?php else : ?>
                                    <span class="user-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                        <ul class="submenu user-submenu">
                            <li><a href="<?= route('foro') . '&view=favorites' ?>" class="site-nav-link">Favoritos</a></li>
                            <li><a href="<?= route('eventos') ?>#eventos-inscritos" class="site-nav-link">Eventos</a></li>
                            <li><a href="#profileModal" class="site-nav-link" data-bs-toggle="modal" data-bs-target="#profileModal" data-profile-trigger="modal">Perfil</a></li>
                            <li>
                                <form action="<?= route('login') ?>" method="post">
                                    <input type="hidden" name="auth_action" value="logout">
                                    <button type="submit" class="site-nav-link log-in user-logout-link">Cerrar sesion</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else : ?>
                    <li><a href="#authModal" class="log-in site-nav-link" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-mode="login" data-auth-trigger="modal">Log in</a></li>
                <?php endif; ?>
            </ul>
        </li>
    </ul>
</nav>
