document.addEventListener('DOMContentLoaded', function () {
    var normalize = function (value) {
        // Normaliza para busqueda tolerante a mayusculas/acentos.
        return (value || '')
            .toString()
            .toLocaleLowerCase('es-ES')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    // Toggle de comentarios
    var commentsToggleButtons = document.querySelectorAll('.comments-toggle-btn');

    commentsToggleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var postId = button.getAttribute('data-post-id');
            var panel = document.getElementById('comments-panel-' + postId);

            if (!panel) {
                return;
            }

            var isHidden = panel.hasAttribute('hidden');

            if (isHidden) {
                panel.removeAttribute('hidden');
                button.setAttribute('aria-expanded', 'true');
                return;
            }

            panel.setAttribute('hidden', 'hidden');
            button.setAttribute('aria-expanded', 'false');
        });
    });

    // Toggle del panel de crear post
    var toggleButton = document.getElementById('toggle-create-post');
    var createPanel = document.getElementById('create-post-panel');

    if (toggleButton && createPanel) {
        toggleButton.addEventListener('click', function () {
            var isHidden = createPanel.hasAttribute('hidden');

            if (isHidden) {
                createPanel.removeAttribute('hidden');
                toggleButton.setAttribute('aria-expanded', 'true');
                return;
            }

            createPanel.setAttribute('hidden', 'hidden');
            toggleButton.setAttribute('aria-expanded', 'false');
        });
    }

    // Búsqueda de foro en tiempo real
    var forumBrowser = document.querySelector('[data-forum-browser]');
    var searchForm = document.querySelector('[data-forum-search-form]');
    var searchInput = document.querySelector('[data-forum-search-input]');

    if (!forumBrowser || !searchInput) {
        return;
    }

    var postCards = Array.prototype.slice.call(forumBrowser.querySelectorAll('[data-forum-post]'));
    var emptyState = forumBrowser.querySelector('[data-forum-empty]');

    if (postCards.length === 0) {
        return;
    }

    var applyForumSearch = function () {
        var query = normalize(searchInput.value);
        var visibleCount = 0;

        postCards.forEach(function (card) {
            var blob = normalize(card.getAttribute('data-search'));
            var matches = query === '' || blob.indexOf(query) !== -1;

            card.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            // Solo muestra estado vacio cuando ningun post cumple el filtro.
            emptyState.hidden = visibleCount !== 0;
        }
    };

    searchInput.addEventListener('input', applyForumSearch);

    if (searchForm) {
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            applyForumSearch();
        });
    }

    applyForumSearch();
});
