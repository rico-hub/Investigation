(function () {
    var openPanel = null; // het dropdown-paneel dat nu open staat

    // Tekst van een cel, netjes (spaties weg). Lege cel = "(leeg)"
    function cellText(cell) {
        var text = cell.textContent.replace(/\s+/g, ' ').trim();
        if (text === '') {
            return '(leeg)';
        }
        return text;
    }

    // Alleen echte datarijen (niet de rij "Geen ... gevonden" met colspan)
    function dataRows(table) {
        var rows = [];
        var all = table.tBodies[0].rows;
        for (var i = 0; i < all.length; i++) {
            if (!all[i].querySelector('td[colspan]') && !all[i].classList.contains('cf-empty')) {
                rows.push(all[i]);
            }
        }
        return rows;
    }

    // Opslaan / ophalen (sessionStorage kan geblokkeerd zijn -> try/catch)
    function storageKey(tableIndex) {
        return 'cf:' + location.pathname + ':' + tableIndex;
    }
    function load(tableIndex) {
        try {
            var raw = sessionStorage.getItem(storageKey(tableIndex));
            if (raw) {
                return JSON.parse(raw);
            }
        } catch (e) {}
        return {};
    }
    function save(tableIndex, filters) {
        try {
            sessionStorage.setItem(storageKey(tableIndex), JSON.stringify(filters));
        } catch (e) {}
    }

    function closePanel() {
        if (openPanel) {
            openPanel.remove();
            openPanel = null;
        }
    }

    function setupTable(table, tableIndex) {
        if (!table.tHead || !table.tBodies[0]) {
            return;
        }
        var headRow = table.tHead.rows[table.tHead.rows.length - 1];
        var rows = dataRows(table);
        if (rows.length < 2) {
            return; // 0 of 1 rij: filteren heeft geen zin
        }

        // filters = { kolomnummer: [waarden die je WEL wilt zien] }
        var filters = load(tableIndex);

        // Teller boven de tabel ("12 van de 20 ... gevonden") meenemen
        var counter = null;
        var prev = table.previousElementSibling;
        if (prev && prev.classList.contains('count')) {
            counter = prev.querySelector('strong');
        }

        function apply() {
            var visible = 0;
            rows.forEach(function (row) {
                var show = true;
                for (var col in filters) {
                    var cell = row.cells[col];
                    if (cell && filters[col].indexOf(cellText(cell)) === -1) {
                        show = false;
                    }
                }
                row.hidden = !show;
                if (show) {
                    visible++;
                }
            });

            // Melding als het filter alles verbergt
            var empty = table.tBodies[0].querySelector('.cf-empty');
            if (visible === 0 && !empty) {
                empty = table.tBodies[0].insertRow();
                empty.className = 'cf-empty';
                var td = empty.insertCell();
                td.colSpan = headRow.cells.length;
                td.className = 'empty';
                td.textContent = 'Niets gevonden met dit kolomfilter.';
            } else if (visible > 0 && empty) {
                empty.remove();
            }

            if (counter) {
                counter.textContent = visible;
            }

            // Pijltjes groen maken als die kolom gefilterd is
            table.querySelectorAll('.cf-btn').forEach(function (btn) {
                btn.classList.toggle('active', filters[btn.dataset.col] !== undefined);
            });
            save(tableIndex, filters);
        }

        // Pijltje toevoegen aan elke kolomkop
        for (var c = 0; c < headRow.cells.length; c++) {
            var th = headRow.cells[c];
            var hasButtons = rows.some(function (row) {
                return row.cells[c] && row.cells[c].querySelector('button, .btn, form');
            });
            if (hasButtons || th.textContent.trim() === '') {
                continue; // kolom met knoppen of zonder naam: overslaan
            }

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cf-btn';
            btn.dataset.col = c;
            btn.setAttribute('aria-label', 'Filter ' + th.textContent.trim());
            btn.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';

            var wrap = document.createElement('span');
            wrap.className = 'cf-head';
            while (th.firstChild) {
                wrap.appendChild(th.firstChild);
            }
            wrap.appendChild(btn);
            th.appendChild(wrap);

            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                var col = this.dataset.col;
                var alreadyOpen = openPanel && openPanel.dataset.owner === tableIndex + ':' + col;
                closePanel();
                if (!alreadyOpen) {
                    openFor(this, col);
                }
            });
        }

        // Het dropdown-paneel voor 1 kolom
        function openFor(btn, col) {
            // Alle unieke waarden uit deze kolom (gesorteerd, getallen slim)
            var values = [];
            rows.forEach(function (row) {
                var text = cellText(row.cells[col]);
                if (values.indexOf(text) === -1) {
                    values.push(text);
                }
            });
            values.sort(function (a, b) {
                return a.localeCompare(b, 'nl', { numeric: true });
            });

            var panel = document.createElement('div');
            panel.className = 'cf-panel';
            panel.dataset.owner = tableIndex + ':' + col;
            panel.innerHTML =
                '<input type="text" class="cf-search" placeholder="Zoek in lijst...">' +
                '<label class="cf-all"><input type="checkbox"> Alles</label>' +
                '<div class="cf-list"></div>' +
                '<button type="button" class="cf-clear">Filter wissen</button>';

            var list = panel.querySelector('.cf-list');
            values.forEach(function (value) {
                var label = document.createElement('label');
                var box = document.createElement('input');
                box.type = 'checkbox';
                box.value = value;
                if (filters[col] === undefined) {
                    box.checked = true;
                } else {
                    box.checked = filters[col].indexOf(value) !== -1;
                }
                label.appendChild(box);
                label.appendChild(document.createTextNode(' ' + value));
                list.appendChild(label);
            });

            var allBox = panel.querySelector('.cf-all input');

            function readChecks() {
                var boxes = list.querySelectorAll('input');
                var chosen = [];
                boxes.forEach(function (box) {
                    if (box.checked) {
                        chosen.push(box.value);
                    }
                });
                if (chosen.length === boxes.length) {
                    delete filters[col]; // alles aan = geen filter
                } else {
                    filters[col] = chosen;
                }
                allBox.checked = chosen.length === boxes.length;
                allBox.indeterminate = chosen.length > 0 && chosen.length < boxes.length;
                apply();
            }

            list.addEventListener('change', readChecks);
            allBox.addEventListener('change', function () {
                list.querySelectorAll('input').forEach(function (box) {
                    if (box.parentNode.style.display !== 'none') {
                        box.checked = allBox.checked;
                    }
                });
                readChecks();
            });
            panel.querySelector('.cf-search').addEventListener('input', function () {
                var term = this.value.toLowerCase();
                list.querySelectorAll('label').forEach(function (label) {
                    if (label.textContent.toLowerCase().indexOf(term) === -1) {
                        label.style.display = 'none';
                    } else {
                        label.style.display = '';
                    }
                });
            });
            panel.querySelector('.cf-clear').addEventListener('click', function () {
                delete filters[col];
                apply();
                closePanel();
            });
            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            // Onder het pijltje zetten (op de pagina, zodat de tabel hem niet afknipt)
            document.body.appendChild(panel);
            var r = btn.getBoundingClientRect();
            var left = r.left + window.scrollX;
            var maxLeft = document.documentElement.clientWidth - panel.offsetWidth - 8 + window.scrollX;
            if (left > maxLeft) {
                left = maxLeft;
            }
            panel.style.left = left + 'px';
            panel.style.top = (r.bottom + window.scrollY + 6) + 'px';
            openPanel = panel;
            readChecks();
            panel.querySelector('.cf-search').focus();
        }

        apply();
    }

    document.querySelectorAll('table.table').forEach(setupTable);
    document.addEventListener('click', closePanel);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closePanel();
        }
    });
})();
