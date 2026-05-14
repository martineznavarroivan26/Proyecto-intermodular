<?php
declare(strict_types=1);
?>
<div class="caja2">
    <div>
        <h1 class="titulo">INAMANIA</h1>
        <p class="subtitulo">¡Únete a la comunidad!</p>
    </div>

    <div class="linea"></div>

    <div class="noticias">
        <div class="noticia margin-top">
            <p class="thread-title">Log in</p>
            <form action="#" method="post">
                <div class="form-group">
                    <label for="login-email">Correo Electrónico</label>
                    <input type="email" id="login-email" name="email" required placeholder="tu@email.com">
                </div>
                <div class="form-group">
                    <label for="login-password">Contraseña</label>
                    <input type="password" id="login-password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="submit-btn">INICIAR SESIÓN</button>
            </form>
        </div>

        <div class="noticia margin-top">
            <p class="thread-title">Register</p>
            <form action="#" method="post">
                <div class="form-group"><label for="register-username">Nombre de Usuario</label><input type="text" id="register-username" name="username" required></div>
                <div class="form-group"><label for="register-email">Correo Electrónico</label><input type="email" id="register-email" name="email" required></div>
                <div class="form-group"><label for="register-password">Contraseña</label><input type="password" id="register-password" name="password" required minlength="6"></div>
                <div class="form-group"><label for="register-confirm">Confirmar Contraseña</label><input type="password" id="register-confirm" name="password_confirmation" required minlength="6"></div>
                <button type="submit" class="submit-btn">CREAR CUENTA</button>
            </form>
        </div>
    </div>

    <div class="back-home"><a href="<?= route('home') ?>">← Volver al inicio</a></div>
</div>