(function () {
    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function setupPwa() {
        if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('./service-worker.js').catch(function () {
                    // A aplicação continua funcionando mesmo sem service worker.
                });
            });
        }
    }

    function setupConfirmations() {
        qsa('[data-confirm]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (!window.confirm(button.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });
    }

    function setupNetworkBanner() {
        var banner = document.createElement('div');
        banner.className = 'alert alert-error';
        banner.style.display = 'none';
        banner.textContent = 'Sem internet. As telas abertas continuam visiveis, mas novas consultas dependem de conexao.';

        var shell = qs('.shell');
        if (!shell) {
            return;
        }

        shell.insertBefore(banner, shell.firstChild);

        function update() {
            banner.style.display = navigator.onLine ? 'none' : 'block';
        }

        window.addEventListener('online', update);
        window.addEventListener('offline', update);
        update();
    }

    function setupOrientationWarning() {
        var overlay = document.createElement('div');
        overlay.className = 'orientation-lock';
        overlay.textContent = 'Modo retrato apenas.';
        document.body.appendChild(overlay);

        function update() {
            var landscapePhone = window.innerWidth < 920 && window.innerWidth > window.innerHeight;
            overlay.style.display = landscapePhone ? 'grid' : 'none';
        }

        window.addEventListener('resize', update);
        window.addEventListener('orientationchange', update);
        update();
    }

    function renderSlots(container, slots, hiddenInput, submitButton) {
        container.innerHTML = '';
        hiddenInput.value = '';
        if (submitButton) {
            submitButton.disabled = true;
        }

        if (!slots.length) {
            var empty = document.createElement('p');
            empty.className = 'muted';
            empty.textContent = 'Nenhum horario livre para esta data.';
            container.appendChild(empty);
            return;
        }

        slots.forEach(function (slot) {
            var label = document.createElement('label');
            var input = document.createElement('input');
            var span = document.createElement('span');

            input.type = 'radio';
            input.name = 'slot_choice';
            input.value = String(slot.id);
            span.textContent = slot.label;

            input.addEventListener('change', function () {
                hiddenInput.value = input.value;
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });

            label.appendChild(input);
            label.appendChild(span);
            container.appendChild(label);
        });
    }

    function setupSlots() {
        qsa('[data-slot-loader]').forEach(function (container) {
            var form = container.closest('form');
            var hiddenInput = form ? qs('[data-selected-slot]', form) : null;
            var submitButton = form ? qs('button[type="submit"]', form) : null;
            var dateInput = document.getElementById(container.getAttribute('data-date-input'));
            var doctorId = container.getAttribute('data-doctor-id');

            if (!hiddenInput || !dateInput || !doctorId) {
                return;
            }

            function load() {
                var date = dateInput.value;
                if (!date) {
                    return;
                }

                container.innerHTML = '<span class="muted">Carregando horarios...</span>';
                hiddenInput.value = '';
                if (submitButton) {
                    submitButton.disabled = true;
                }

                fetch('index.php?action=slots&doctor_id=' + encodeURIComponent(doctorId) + '&date=' + encodeURIComponent(date), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Falha ao buscar horarios.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        renderSlots(container, data.slots || [], hiddenInput, submitButton);
                    })
                    .catch(function () {
                        container.innerHTML = '<p class="muted">Nao foi possivel carregar os horarios agora.</p>';
                    });
            }

            dateInput.addEventListener('change', load);
            form.addEventListener('submit', function (event) {
                if (!hiddenInput.value) {
                    event.preventDefault();
                    window.alert('Selecione um horario livre.');
                }
            });
            load();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupPwa();
        setupConfirmations();
        setupNetworkBanner();
        setupOrientationWarning();
        setupSlots();
    });
})();
