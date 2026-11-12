/* =====================================================================
   filter-bar.js - filterbalk: zoekrij bovenaan + "chips" eronder
   ---------------------------------------------------------------------
   Universeel: werkt op elk <form class="filter-bar" method="get"> met
   een zoekveld (name="search"). De PHP/HTML blijft gewoon hetzelfde:
   - het zoekveld + de knop komen in de bovenste rij
   - elke <select> wordt een "chip" met een eigen dropdown-lijst
   - de "Filters wissen" link wordt "Alles wissen" rechts in de chip-rij
   Kies je iets in een chip, dan wordt het formulier direct verstuurd.
   Zonder JavaScript werkt het formulier gewoon zoals eerst.
   ===================================================================== */

(function () {
    var openPanel = null;

    var SEARCH_ICON = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
    var CHEVRON = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    var CROSS = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
    var CHECK = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

    function closePanel() {
        if (openPanel) {
            openPanel.remove();
            openPanel = null;
        }
    }

    // Naam van een chip: data-label="..." op de <select>, anders uit de
    // eerste optie: "-- Alle statussen --" -> "Statussen"
    function chipName(select) {
        if (select.dataset.label) {
            return select.dataset.label;
        }
        var text = select.options[0].text.replace(/-/g, '').trim();
        text = text.replace(/^alle\s+/i, '');
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function makeChip(select, form) {
        var name = chipName(select);
        var chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'fb-chip';

        var current = select.options[select.selectedIndex];
        if (select.value !== '') {
            chip.classList.add('is-set');
            chip.innerHTML = '<span></span><span class="fb-x" title="Wissen">' + CROSS + '</span>';
            chip.firstChild.textContent = current.text;
            chip.querySelector('.fb-x').addEventListener('click', function (event) {
                event.stopPropagation();
                select.value = '';
                form.submit();
            });
        } else {
            chip.innerHTML = '<span></span>' + CHEVRON;
            chip.firstChild.textContent = name;
        }

        chip.addEventListener('click', function (event) {
            event.stopPropagation();
            var wasOpen = openPanel && openPanel.owner === chip;
            closePanel();
            if (!wasOpen) {
                openFor(chip, select, form, name);
            }
        });
        return chip;
    }

    function openFor(chip, select, form, name) {
        var panel = document.createElement('div');
        panel.className = 'fb-panel';
        panel.owner = chip;
        panel.innerHTML =
            '<div class="fb-panel-head"><strong></strong><button type="button" class="fb-link">Wissen</button></div>' +
            '<label class="fb-panel-search">' + SEARCH_ICON + '<input type="text" placeholder="Zoeken"></label>' +
            '<div class="fb-options"></div>';
        panel.querySelector('strong').textContent = name;

        var list = panel.querySelector('.fb-options');
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            if (option.value === '') {
                continue; // de "-- Alle ... --" optie = Wissen
            }
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'fb-option';
            row.dataset.value = option.value;
            row.innerHTML = '<span class="fb-box"></span><span></span>';
            row.lastChild.textContent = option.text;
            if (option.value === select.value) {
                row.classList.add('is-on');
                row.firstChild.innerHTML = CHECK;
            }
            row.addEventListener('click', function () {
                select.value = this.dataset.value;
                form.submit();
            });
            list.appendChild(row);
        }

        panel.querySelector('.fb-link').addEventListener('click', function () {
            select.value = '';
            form.submit();
        });
        panel.querySelector('input').addEventListener('input', function () {
            var term = this.value.toLowerCase();
            list.querySelectorAll('.fb-option').forEach(function (row) {
                if (row.textContent.toLowerCase().indexOf(term) === -1) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
        panel.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        // Onder de chip zetten
        document.body.appendChild(panel);
        var r = chip.getBoundingClientRect();
        var left = r.left + window.scrollX;
        var maxLeft = document.documentElement.clientWidth - panel.offsetWidth - 8 + window.scrollX;
        if (left > maxLeft) {
            left = maxLeft;
        }
        panel.style.left = left + 'px';
        panel.style.top = (r.bottom + window.scrollY + 6) + 'px';
        openPanel = panel;
        panel.querySelector('input').focus();
    }

    function setupForm(form) {
        var search = form.querySelector('input[name="search"]');
        if (!search || form.method.toLowerCase() !== 'get') {
            return;
        }
        var submit = form.querySelector('button[type="submit"]');
        var clear = form.querySelector('a.btn');
        var selects = form.querySelectorAll('select');

        form.classList.remove('card');
        form.classList.add('fb');

        // Rij 1: zoekveld + knop
        var top = document.createElement('div');
        top.className = 'fb-top';
        var label = document.createElement('label');
        label.className = 'fb-search';
        label.innerHTML = SEARCH_ICON;
        label.appendChild(search);
        top.appendChild(label);
        if (submit) {
            submit.textContent = 'Zoeken';
            top.appendChild(submit);
        }

        // Rij 2: chips + Alles wissen
        var chips = document.createElement('div');
        chips.className = 'fb-chips';
        selects.forEach(function (select) {
            select.hidden = true; // blijft in het formulier, waarde gaat mee
            chips.appendChild(makeChip(select, form));
        });
        if (clear) {
            clear.className = 'fb-clear-all';
            clear.textContent = 'Alles wissen';
            chips.appendChild(clear);
        }

        form.insertBefore(top, form.firstChild);
        if (selects.length > 0 || clear) {
            form.insertBefore(chips, top.nextSibling);
        }
    }

    document.querySelectorAll('form.filter-bar').forEach(setupForm);
    document.addEventListener('click', closePanel);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closePanel();
        }
    });
})();
