document.addEventListener('DOMContentLoaded', function () {
    var ITEMS_PER_PAGE = 4;
    var activeRelationSourceId = null;

    // Normaliza texto para que buscar con o sin tildes sea equivalente.
    var normalize = function (value) {
        return (value || '')
            .toString()
            .toLocaleLowerCase('es-ES')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    // Convierte "1,2,3" en Set para filtrar rapido por IDs relacionados.
    var parseIdSet = function (csvValue) {
        var values = (csvValue || '')
            .toString()
            .split(',')
            .map(function (value) {
                return value.trim();
            })
            .filter(function (value) {
                return value !== '';
            });

        return new Set(values);
    };

    var stateConfigs = [
        { id: 'team-browser', type: 'team' },
        { id: 'character-browser', type: 'character' },
        { id: 'technique-browser', type: 'technique' },
        { id: 'object-browser', type: 'object' },
    ];

    var states = [];
    var statesById = {};

    stateConfigs.forEach(function (config) {
        var browser = document.getElementById(config.id);
        if (!browser) {
            return;
        }

        var searchInput = browser.querySelector('[data-wiki-search-input], [data-character-search]');
        var searchForm = browser.querySelector('[data-wiki-search-form], [data-character-search-form]');
        var toolbar = browser.querySelector('.wiki-browser-toolbar');
        var cards = Array.prototype.slice.call(browser.querySelectorAll('[data-wiki-card], [data-character-card]'));
        var emptyState = browser.querySelector('[data-wiki-empty], [data-character-empty]');

        var pagination = browser.querySelector('[data-wiki-pagination]');
        if (!pagination) {
            pagination = document.createElement('div');
            pagination.className = 'pagination-container';
            pagination.setAttribute('data-wiki-pagination', '');
            browser.appendChild(pagination);
        }

        var paginationRow = document.createElement('div');
        paginationRow.className = 'pagination';
        pagination.appendChild(paginationRow);

        var state = {
            id: config.id,
            type: config.type,
            browser: browser,
            searchInput: searchInput,
            searchForm: searchForm,
            toolbar: toolbar,
            cards: cards,
            emptyState: emptyState,
            paginationRow: paginationRow,
            currentPage: 1,
            matchedCards: [],
            externalFilterIds: null,
        };

        states.push(state);
        statesById[state.id] = state;
    });

    var applyBrowserState = function (state, options) {
        var opts = options || {};
        if (opts.resetPage) {
            state.currentPage = 1;
        }

        var query = normalize(state.searchInput ? state.searchInput.value : '');
        var matchedCards = [];

        state.cards.forEach(function (card) {
            var cardSearch = normalize(card.getAttribute('data-search'));
            var matchesSearch = query === '' || cardSearch.indexOf(query) !== -1;

            var matchesExternal = true;
            // Si hay filtro cruzado activo, la tarjeta debe pertenecer a los IDs permitidos.
            if (state.externalFilterIds !== null) {
                var entityId = (card.getAttribute('data-entity-id') || '').toString().trim();
                matchesExternal = state.externalFilterIds.has(entityId);
            }

            if (matchesSearch && matchesExternal) {
                matchedCards.push(card);
            }
        });

        state.matchedCards = matchedCards;

        var totalPages = Math.max(1, Math.ceil(matchedCards.length / ITEMS_PER_PAGE));
        if (state.currentPage > totalPages) {
            state.currentPage = totalPages;
        }
        if (state.currentPage < 1) {
            state.currentPage = 1;
        }

        var startIndex = (state.currentPage - 1) * ITEMS_PER_PAGE;
        var endIndex = startIndex + ITEMS_PER_PAGE;

        state.cards.forEach(function (card) {
            card.hidden = true;
        });

        matchedCards.forEach(function (card, index) {
            if (index >= startIndex && index < endIndex) {
                card.hidden = false;
            }
        });

        if (state.emptyState) {
            state.emptyState.hidden = matchedCards.length !== 0;
        }

        state.paginationRow.innerHTML = '';

        var prevButton = document.createElement('button');
        prevButton.type = 'button';
        prevButton.className = 'pagination-btn';
        prevButton.title = 'Pagina anterior';
        prevButton.innerHTML = '<i class="fa fa-chevron-left"></i>';
        prevButton.disabled = state.currentPage <= 1;
        prevButton.addEventListener('click', function () {
            if (state.currentPage > 1) {
                state.currentPage -= 1;
                applyBrowserState(state);
            }
        });

        var status = document.createElement('button');
        status.type = 'button';
        status.className = 'pagination-current';
        status.disabled = true;
        status.textContent = String(state.currentPage);
        status.title = 'Pagina actual';

        var nextButton = document.createElement('button');
        nextButton.type = 'button';
        nextButton.className = 'pagination-btn';
        nextButton.title = 'Pagina siguiente';
        nextButton.innerHTML = '<i class="fa fa-chevron-right"></i>';
        nextButton.disabled = state.currentPage >= totalPages;
        nextButton.addEventListener('click', function () {
            if (state.currentPage < totalPages) {
                state.currentPage += 1;
                applyBrowserState(state);
            }
        });

        state.paginationRow.appendChild(prevButton);
        state.paginationRow.appendChild(status);
        state.paginationRow.appendChild(nextButton);
    };

    // Al salir del modo relacional, vuelve a mostrar todas las secciones y su buscador.
    var resetCrossFilters = function () {
        activeRelationSourceId = null;

        states.forEach(function (state) {
            state.externalFilterIds = null;
            if (state.toolbar) {
                state.toolbar.hidden = false;
            }
            applyBrowserState(state);
        });
    };

    var applyCrossFiltersFrom = function (sourceState, relationMap) {
        activeRelationSourceId = sourceState.id;

        states.forEach(function (state) {
            if (state.id === sourceState.id) {
                state.externalFilterIds = null;
                if (state.toolbar) {
                    state.toolbar.hidden = false;
                }
                applyBrowserState(state);
                return;
            }

            var relationIds = relationMap[state.type] || null;
            state.externalFilterIds = relationIds;

            // Limpia el input de la seccion filtrada para no mezclar filtro manual y relacional.
            if (state.externalFilterIds !== null && state.searchInput) {
                state.searchInput.value = '';
            }

            state.currentPage = 1;

            if (state.toolbar) {
                // Si una seccion esta filtrada por relacion, oculta su barra para que quede claro.
                state.toolbar.hidden = state.externalFilterIds !== null;
            }

            applyBrowserState(state);
        });
    };

    var maybeActivateRelations = function (sourceState) {
        var query = normalize(sourceState.searchInput ? sourceState.searchInput.value : '');
        // Solo activa relaciones cuando la busqueda deja exactamente una tarjeta.
        if (query === '' || sourceState.matchedCards.length !== 1) {
            if (activeRelationSourceId !== null) {
                resetCrossFilters();
            }
            return;
        }

        var selectedCard = sourceState.matchedCards[0];
        var relationMap = {};

        // Reglas de propagacion por tipo de entidad.
        if (sourceState.type === 'team') {
            relationMap.character = parseIdSet(selectedCard.getAttribute('data-rel-character-ids'));
            relationMap.object = parseIdSet(selectedCard.getAttribute('data-rel-object-ids'));
        }

        if (sourceState.type === 'character') {
            relationMap.team = parseIdSet(selectedCard.getAttribute('data-rel-team-ids'));
            relationMap.technique = parseIdSet(selectedCard.getAttribute('data-rel-technique-ids'));
            relationMap.object = parseIdSet(selectedCard.getAttribute('data-rel-object-ids'));
        }

        if (sourceState.type === 'technique') {
            relationMap.character = parseIdSet(selectedCard.getAttribute('data-rel-character-ids'));
        }

        if (Object.keys(relationMap).length === 0) {
            if (activeRelationSourceId !== null) {
                resetCrossFilters();
            }
            return;
        }

        applyCrossFiltersFrom(sourceState, relationMap);
    };

    states.forEach(function (state) {
        if (state.searchInput) {
            state.searchInput.addEventListener('input', function () {
                applyBrowserState(state, { resetPage: true });
                maybeActivateRelations(state);
            });
        }

        if (state.searchForm) {
            state.searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
                applyBrowserState(state, { resetPage: true });
                maybeActivateRelations(state);
            });
        }

        applyBrowserState(state);
    });
});
