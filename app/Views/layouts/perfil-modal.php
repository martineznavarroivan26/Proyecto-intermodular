<?php
declare(strict_types=1);

$sessionUser = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;
$profileUser = is_array($profileUser ?? null) ? $profileUser : [];
$profileErrors = is_array($profileErrors ?? null) ? $profileErrors : [];
$profileFormData = is_array($profileFormData ?? null) ? $profileFormData : [];
$profileModalOpen = !empty($openProfileModal);
$currentRole = (string) ($profileUser['rol'] ?? ($sessionUser['rol'] ?? 'usuario'));
$isAdminUser = $currentRole === 'admin';
$selectedRole = (string) ($profileFormData['rol'] ?? $currentRole);

$displayName = (string) ($profileFormData['username'] ?? $profileUser['nombre_usuario'] ?? ($sessionUser['nombre_usuario'] ?? 'Usuario'));
$email = (string) ($profileUser['email'] ?? ($sessionUser['email'] ?? ''));
$avatarPath = trim((string) ($profileUser['avatar'] ?? ($sessionUser['avatar'] ?? '')));
$avatarSrc = '';

if ($avatarPath !== '' && str_starts_with($avatarPath, 'uploads/avatars/')) {
    $avatarSrc = asset($avatarPath);
}

$avatarInitial = $displayName !== '' ? strtoupper(substr($displayName, 0, 1)) : 'U';
?>
<div class="modal fade profile-modal" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true" data-profile-open="<?= $profileModalOpen ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content profile-modal-content">
            <div class="modal-header profile-modal-header">
                <div>
                    <h2 class="modal-title profile-modal-title" id="profileModalLabel">Mi perfil</h2>
                    <p class="profile-modal-intro">Actualiza tu nombre de usuario y avatar desde aqui.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body profile-modal-body">
                <?php if ($profileErrors !== []) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?= implode('<br>', array_map('htmlspecialchars', $profileErrors)) ?>
                    </div>
                <?php endif; ?>

                <div class="profile-avatar-preview profile-modal-avatar-preview" aria-label="Avatar actual" id="profile-modal-avatar-preview">
                    <?php if ($avatarSrc !== '') : ?>
                        <img src="<?= htmlspecialchars($avatarSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar de <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>" id="profile-modal-avatar-img">
                    <?php else : ?>
                        <span id="profile-modal-avatar-initial"><?= htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <form action="<?= route('perfil') ?>" method="post" enctype="multipart/form-data" class="profile-form profile-modal-form">
                    <div class="auth-form-group">
                        <label for="profile-modal-username">Nombre de usuario</label>
                        <input
                            type="text"
                            id="profile-modal-username"
                            name="username"
                            maxlength="16"
                            required
                            value="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="auth-form-group">
                        <label for="profile-modal-email">Correo electronico</label>
                        <input
                            type="email"
                            id="profile-modal-email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            disabled
                        >
                    </div>

                    <div class="auth-form-group">
                        <label for="profile-modal-avatar">Avatar</label>
                        <input
                            type="file"
                            id="profile-modal-avatar"
                            name="avatar"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                        >
                    </div>

                    <?php if ($isAdminUser) : ?>
                        <div class="auth-form-group">
                            <label for="profile-modal-role">Rol de usuario</label>
                            <select id="profile-modal-role" name="rol">
                                <option value="usuario"<?= $selectedRole === 'usuario' ? ' selected' : '' ?>>Usuario</option>
                                <option value="moderador"<?= $selectedRole === 'moderador' ? ' selected' : '' ?>>Moderador</option>
                                <option value="admin"<?= $selectedRole === 'admin' ? ' selected' : '' ?>>Administrador</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="submit-btn profile-submit-btn">Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
