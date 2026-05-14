document.addEventListener('DOMContentLoaded', function () {
    // Evita cursor personalizado en tactiles para no interferir con UX movil.
    if (window.matchMedia('(hover: none), (pointer: coarse)').matches) {
        return;
    }

    var root = document.documentElement;
    var body = document.body;

    if (!body) {
        return;
    }

    var cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    cursor.setAttribute('aria-hidden', 'true');
    body.appendChild(cursor);

    var isEnabled = false;
    var hasPosition = false;

    var parseCssUrl = function (rawValue) {
        var value = (rawValue || '').trim();
        var match = /^url\((['\"]?)(.*?)\1\)$/.exec(value);
        return match ? match[2] : '';
    };

    var enableCustomCursor = function () {
        if (isEnabled) {
            return;
        }

        isEnabled = true;
        root.classList.add('custom-cursor-enabled');

        if (hasPosition) {
            cursor.classList.add('is-visible');
        }
    };

    var showCursor = function () {
        if (!isEnabled || !hasPosition) {
            return;
        }

        cursor.classList.add('is-visible');
    };

    var hideCursor = function () {
        cursor.classList.remove('is-visible');
    };

    var updateCursorPosition = function (event) {
        hasPosition = true;
        cursor.style.left = event.clientX + 'px';
        cursor.style.top = event.clientY + 'px';
        showCursor();
    };

    document.addEventListener('pointermove', updateCursorPosition, { passive: true });
    document.addEventListener('mousemove', updateCursorPosition, { passive: true });

    document.addEventListener('mouseleave', hideCursor);
    window.addEventListener('blur', hideCursor);
    window.addEventListener('focus', showCursor);
    window.addEventListener('mouseout', function (event) {
        // relatedTarget null indica salida real de la ventana del navegador.
        if (event.relatedTarget === null) {
            hideCursor();
        }
    });

    var cursorUrl = parseCssUrl(getComputedStyle(root).getPropertyValue('--asset-cursor-url'));
    if (cursorUrl === '') {
        enableCustomCursor();
        return;
    }

    var preloaded = false;
    var activateOnce = function () {
        if (preloaded) {
            return;
        }

        preloaded = true;
        enableCustomCursor();
    };

    var preloadImage = new Image();
    preloadImage.onload = activateOnce;
    preloadImage.onerror = activateOnce;
    preloadImage.src = cursorUrl;

    if (preloadImage.complete) {
        activateOnce();
    }

    // Evita esperar indefinidamente si el navegador retrasa el onload.
    setTimeout(activateOnce, 180);
});
