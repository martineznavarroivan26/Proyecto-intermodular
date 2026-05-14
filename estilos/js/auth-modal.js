document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();

            var wrapper = toggle.closest('.password-input-wrapper');
            if (!wrapper) {
                return;
            }

            var input = wrapper.querySelector('input[type="password"], input[type="text"]');
            var icon = toggle.querySelector('i');

            if (!input || !icon) {
                return;
            }

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                toggle.setAttribute('aria-label', 'Ocultar contrasena');
                return;
            }

            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            toggle.setAttribute('aria-label', 'Mostrar contrasena');
        });
    });

    var modalElement = document.getElementById('authModal');

    if (!modalElement || typeof bootstrap === 'undefined') {
        return;
    }

    var showTab = function (mode) {
        var normalizedMode = mode === 'register' ? 'register' : 'login';
        var loginTab = modalElement.querySelector('[data-auth-tab="login"]');
        var registerTab = modalElement.querySelector('[data-auth-tab="register"]');
        var loginPane = document.getElementById('auth-login-pane');
        var registerPane = document.getElementById('auth-register-pane');

        if (!loginTab || !registerTab || !loginPane || !registerPane) {
            return;
        }

        var showRegister = normalizedMode === 'register';

        loginTab.classList.toggle('active', !showRegister);
        loginTab.setAttribute('aria-selected', showRegister ? 'false' : 'true');
        registerTab.classList.toggle('active', showRegister);
        registerTab.setAttribute('aria-selected', showRegister ? 'true' : 'false');

        loginPane.classList.toggle('show', !showRegister);
        loginPane.classList.toggle('active', !showRegister);
        registerPane.classList.toggle('show', showRegister);
        registerPane.classList.toggle('active', showRegister);
    };

    modalElement.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var mode = trigger && trigger.getAttribute('data-auth-mode')
            ? trigger.getAttribute('data-auth-mode')
            : (modalElement.getAttribute('data-auth-default-mode') || 'login');
        showTab(mode);
    });

    document.querySelectorAll('[data-auth-trigger="modal"]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            var mode = trigger.getAttribute('data-auth-mode') || 'login';
            modalElement.setAttribute('data-auth-default-mode', mode);
            showTab(mode);
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    });

    modalElement.querySelectorAll('[data-auth-tab]').forEach(function (tabTrigger) {
        tabTrigger.addEventListener('click', function () {
            showTab(tabTrigger.getAttribute('data-auth-tab'));
        });
    });

    modalElement.querySelectorAll('.js-auth-temporary-alert').forEach(function (alertElement) {
        setTimeout(function () {
            alertElement.style.transition = 'opacity 0.35s ease';
            alertElement.style.opacity = '0';

            setTimeout(function () {
                alertElement.remove();
            }, 350);
        }, 5000);
    });

    if (modalElement.getAttribute('data-auth-open') === '1') {
        var defaultMode = modalElement.getAttribute('data-auth-default-mode') || 'login';
        showTab(defaultMode);
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
});