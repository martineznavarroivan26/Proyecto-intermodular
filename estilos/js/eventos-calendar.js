(function () {
    'use strict';

    var calendarRoot = document.querySelector('[data-calendar-root]');

    if (!calendarRoot) {
        return;
    }

    var eventsScript = document.getElementById('calendar-events-data');
    var titleElement = calendarRoot.querySelector('[data-calendar-title]');
    var gridElement = calendarRoot.querySelector('[data-calendar-grid]');
    var prevButton = calendarRoot.querySelector('[data-calendar-prev]');
    var nextButton = calendarRoot.querySelector('[data-calendar-next]');
    var selectedLabelElement = calendarRoot.querySelector('[data-calendar-selected-label]');
    var eventsListElement = calendarRoot.querySelector('[data-calendar-events-list]');

    if (!eventsScript || !titleElement || !gridElement || !prevButton || !nextButton || !selectedLabelElement || !eventsListElement) {
        return;
    }

    var rawEvents = [];

    try {
        rawEvents = JSON.parse(eventsScript.textContent || '[]');
        if (!Array.isArray(rawEvents)) {
            rawEvents = [];
        }
    } catch (_error) {
        rawEvents = [];
    }

    var now = new Date();
    var startMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    var endMonth = new Date(startMonth.getFullYear(), startMonth.getMonth() + 11, 1);
    var currentViewMonth = new Date(startMonth);
    var selectedDate = formatDate(new Date(now.getFullYear(), now.getMonth(), now.getDate()));

    var monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    var eventsByDate = buildEventsByDate(rawEvents);

    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function parseDate(value) {
        if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return null;
        }

        var parts = value.split('-');
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        var day = parseInt(parts[2], 10);

        var date = new Date(year, month - 1, day);

        if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
            return null;
        }

        return date;
    }

    function compareMonth(a, b) {
        if (a.getFullYear() !== b.getFullYear()) {
            return a.getFullYear() - b.getFullYear();
        }
        return a.getMonth() - b.getMonth();
    }

    function clampDateToWindow(date) {
        var minDate = new Date(startMonth.getFullYear(), startMonth.getMonth(), 1);
        var maxDate = new Date(endMonth.getFullYear(), endMonth.getMonth() + 1, 0);

        if (date < minDate) {
            return formatDate(minDate);
        }

        if (date > maxDate) {
            return formatDate(maxDate);
        }

        return formatDate(date);
    }

    function expandEventDays(startDate, endDate) {
        var items = [];
        var cursor = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
        var safeGuard = 0;

        // Expande eventos de varios dias para indexarlos por cada fecha intermedia.
        while (cursor <= endDate && safeGuard < 400) {
            items.push(formatDate(cursor));
            cursor.setDate(cursor.getDate() + 1);
            safeGuard += 1;
        }

        return items;
    }

    function buildEventsByDate(events) {
        var map = {};

        events.forEach(function (eventItem) {
            var start = parseDate(eventItem.fecha_inicio);
            var end = parseDate(eventItem.fecha_fin || eventItem.fecha_inicio);

            if (!start || !end) {
                return;
            }

            if (end < start) {
                end = start;
            }

            expandEventDays(start, end).forEach(function (dateKey) {
                if (!map[dateKey]) {
                    map[dateKey] = [];
                }
                map[dateKey].push(eventItem);
            });
        });

        return map;
    }

    function getMondayBasedOffset(jsWeekDay) {
        return (jsWeekDay + 6) % 7;
    }

    function render() {
        // Render maestro: cabecera, rejilla de dias, detalle del dia y estado de navegacion.
        titleElement.textContent = monthNames[currentViewMonth.getMonth()] + ' ' + currentViewMonth.getFullYear();
        renderGrid();
        renderSelectedDay();
        updateNavigationState();
    }

    function renderGrid() {
        gridElement.innerHTML = '';

        var year = currentViewMonth.getFullYear();
        var month = currentViewMonth.getMonth();
        var firstDay = new Date(year, month, 1);
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var offset = getMondayBasedOffset(firstDay.getDay());

        for (var i = 0; i < offset; i += 1) {
            var emptyCell = document.createElement('div');
            emptyCell.className = 'calendar-day is-empty';
            gridElement.appendChild(emptyCell);
        }

        for (var day = 1; day <= daysInMonth; day += 1) {
            var date = new Date(year, month, day);
            var dateKey = formatDate(date);
            var dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = 'calendar-day';
            dayButton.setAttribute('data-date', dateKey);

            if (dateKey === selectedDate) {
                dayButton.classList.add('is-selected');
            }

            if (eventsByDate[dateKey] && eventsByDate[dateKey].length > 0) {
                dayButton.classList.add('has-event');
            }

            if (formatDate(date) === formatDate(now)) {
                dayButton.classList.add('is-today');
            }

            dayButton.innerHTML = '<span class="calendar-day-number">' + day + '</span>';

            if (eventsByDate[dateKey] && eventsByDate[dateKey].length > 0) {
                var badge = document.createElement('span');
                badge.className = 'calendar-event-badge';
                badge.textContent = String(eventsByDate[dateKey].length);
                dayButton.appendChild(badge);
            }

            dayButton.addEventListener('click', function (event) {
                selectedDate = event.currentTarget.getAttribute('data-date') || selectedDate;
                render();
            });

            gridElement.appendChild(dayButton);
        }
    }

    function renderSelectedDay() {
        var parsedSelectedDate = parseDate(selectedDate);
        if (!parsedSelectedDate) {
            eventsListElement.innerHTML = '<p class="calendar-empty">No se ha seleccionado un dia valido.</p>';
            selectedLabelElement.textContent = 'Eventos del dia';
            return;
        }

        selectedLabelElement.textContent = 'Eventos - ' + parsedSelectedDate.getDate() + ' ' + monthNames[parsedSelectedDate.getMonth()] + ' ' + parsedSelectedDate.getFullYear();

        var dayEvents = eventsByDate[selectedDate] || [];

        if (dayEvents.length === 0) {
            eventsListElement.innerHTML = '<p class="calendar-empty">No hay eventos para este dia.</p>';
            return;
        }

        eventsListElement.innerHTML = '';

        dayEvents.forEach(function (eventItem) {
            var item = document.createElement('article');
            item.className = 'calendar-event-item';

            var title = document.createElement('h4');
            title.textContent = eventItem.titulo || 'Evento sin titulo';
            item.appendChild(title);

            if (eventItem.descripcion) {
                var description = document.createElement('p');
                description.textContent = eventItem.descripcion;
                item.appendChild(description);
            }

            var details = document.createElement('p');
            details.className = 'calendar-event-meta';

            var parts = [];
            if (eventItem.lugar) {
                parts.push('Lugar: ' + eventItem.lugar);
            }
            if (eventItem.fecha_inicio) {
                parts.push('Inicio: ' + eventItem.fecha_inicio);
            }
            if (eventItem.fecha_fin) {
                parts.push('Fin: ' + eventItem.fecha_fin);
            }
            if (eventItem.precio !== null && eventItem.precio !== undefined) {
                parts.push('Precio: ' + eventItem.precio + ' EUR');
            }

            details.textContent = parts.join(' | ');
            item.appendChild(details);

            eventsListElement.appendChild(item);
        });
    }

    function updateNavigationState() {
        // Bloquea navegacion fuera de la ventana de 12 meses configurada.
        prevButton.disabled = compareMonth(currentViewMonth, startMonth) <= 0;
        nextButton.disabled = compareMonth(currentViewMonth, endMonth) >= 0;
    }

    prevButton.addEventListener('click', function () {
        var previousMonth = new Date(currentViewMonth.getFullYear(), currentViewMonth.getMonth() - 1, 1);

        if (compareMonth(previousMonth, startMonth) < 0) {
            return;
        }

        currentViewMonth = previousMonth;

        var selectedInMonth = parseDate(selectedDate);
        if (!selectedInMonth || selectedInMonth.getMonth() !== currentViewMonth.getMonth() || selectedInMonth.getFullYear() !== currentViewMonth.getFullYear()) {
            selectedDate = clampDateToWindow(new Date(currentViewMonth.getFullYear(), currentViewMonth.getMonth(), 1));
        }

        render();
    });

    nextButton.addEventListener('click', function () {
        var followingMonth = new Date(currentViewMonth.getFullYear(), currentViewMonth.getMonth() + 1, 1);

        if (compareMonth(followingMonth, endMonth) > 0) {
            return;
        }

        currentViewMonth = followingMonth;

        var selectedInMonth = parseDate(selectedDate);
        if (!selectedInMonth || selectedInMonth.getMonth() !== currentViewMonth.getMonth() || selectedInMonth.getFullYear() !== currentViewMonth.getFullYear()) {
            selectedDate = clampDateToWindow(new Date(currentViewMonth.getFullYear(), currentViewMonth.getMonth(), 1));
        }

        render();
    });

    var selectedParsedDate = parseDate(selectedDate);
    if (!selectedParsedDate) {
        selectedDate = formatDate(startMonth);
    }

    render();
})();
