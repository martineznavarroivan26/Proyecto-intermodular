<?php
declare(strict_types=1);

$authMode = ($authMode ?? 'login') === 'register' ? 'register' : 'login';
$openAuthModal = !empty($openAuthModal);
$authErrors = is_array($authErrors ?? null) ? $authErrors : [];
$authSuccess = isset($authSuccess) ? (string) $authSuccess : null;
$authFormData = is_array($authFormData ?? null) ? $authFormData : [];
?>
<div
    class="modal fade"
    id="authModal"
    tabindex="-1"
    aria-labelledby="authModalLabel"
    aria-hidden="true"
    data-auth-default-mode="<?= $authMode ?>"
    data-auth-open="<?= $openAuthModal ? '1' : '0' ?>"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="authModalLabel">INAMANIA</h2>
                    <p class="auth-intro">Accede a la comunidad o crea tu cuenta en unos segundos.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php if ($authErrors !== []) : ?>
                    <div class="alert alert-danger js-auth-temporary-alert" role="alert">
                        <?= implode('<br>', array_map('htmlspecialchars', $authErrors)) ?>
                    </div>
                <?php endif; ?>

                <?php if ($authSuccess !== null && $authSuccess !== '') : ?>
                    <div class="alert alert-success js-auth-temporary-alert" role="alert">
                        <?= htmlspecialchars($authSuccess) ?>
                    </div>
                <?php endif; ?>

                <div class="nav nav-pills auth-switch" id="authTabs" role="tablist">
                    <button
                        class="nav-link<?= $authMode === 'login' ? ' active' : '' ?>"
                        id="auth-login-tab"
                        type="button"
                        role="tab"
                        aria-controls="auth-login-pane"
                        aria-selected="<?= $authMode === 'login' ? 'true' : 'false' ?>"
                        data-auth-tab="login"
                    >
                        Iniciar sesión
                    </button>
                    <button
                        class="nav-link<?= $authMode === 'register' ? ' active' : '' ?>"
                        id="auth-register-tab"
                        type="button"
                        role="tab"
                        aria-controls="auth-register-pane"
                        aria-selected="<?= $authMode === 'register' ? 'true' : 'false' ?>"
                        data-auth-tab="register"
                    >
                        Registrarse
                    </button>
                </div>

                <div class="tab-content" id="authTabsContent">
                    <div class="tab-pane fade<?= $authMode === 'login' ? ' show active' : '' ?>" id="auth-login-pane" role="tabpanel" aria-labelledby="auth-login-tab" tabindex="0">
                        <div class="auth-pane">
                            <h3 class="auth-pane-title">Bienvenido de nuevo</h3>
                            <p class="auth-pane-subtitle">Entra para participar en el foro, eventos y contenido exclusivo.</p>
                            <form action="<?= route('login') ?>" method="post">
                                <input type="hidden" name="auth_action" value="login">
                                <div class="auth-form-group">
                                    <label for="modal-login-email">Correo electrónico</label>
                                    <input type="email" id="modal-login-email" name="email" required placeholder="tu@email.com" value="<?= htmlspecialchars((string) ($authFormData['email'] ?? '')) ?>">
                                </div>
                                <div class="auth-form-group">
                                    <label for="modal-login-password">Contraseña</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="modal-login-password" name="password" required placeholder="••••••••">
                                        <button type="button" class="password-toggle" aria-label="Mostrar contraseña">
                                            <i class="fa fa-eye-slash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="submit-btn">INICIAR SESIÓN</button>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade<?= $authMode === 'register' ? ' show active' : '' ?>" id="auth-register-pane" role="tabpanel" aria-labelledby="auth-register-tab" tabindex="0">
                        <div class="auth-pane">
                            <h3 class="auth-pane-title">Crea tu cuenta</h3>
                            <p class="auth-pane-subtitle">Únete a la comunidad para comentar, seguir eventos y guardar favoritos.</p>
                            <form action="<?= route('login') ?>" method="post">
                                <input type="hidden" name="auth_action" value="register">
                                <div class="auth-form-group">
                                    <label for="modal-register-username">Nombre de usuario</label>
                                    <input type="text" id="modal-register-username" name="username" maxlength="16" required placeholder="Ej: MarkEvans10" value="<?= htmlspecialchars((string) ($authFormData['username'] ?? '')) ?>">
                                </div>
                                <div class="auth-form-group">
                                    <label for="modal-register-email">Correo electrónico</label>
                                    <input type="email" id="modal-register-email" name="email" required placeholder="tu@email.com" value="<?= htmlspecialchars((string) ($authFormData['email'] ?? '')) ?>">
                                </div>
                                <div class="auth-form-group">
                                    <label for="modal-register-password">Contraseña</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="modal-register-password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                                        <button type="button" class="password-toggle" aria-label="Mostrar contraseña">
                                            <i class="fa fa-eye-slash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="auth-form-group">
                                    <label for="modal-register-confirm">Confirmar contraseña</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="modal-register-confirm" name="password_confirmation" required minlength="6" placeholder="Repite tu contraseña">
                                        <button type="button" class="password-toggle" aria-label="Mostrar contraseña">
                                            <i class="fa fa-eye-slash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="submit-btn">CREAR CUENTA</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>