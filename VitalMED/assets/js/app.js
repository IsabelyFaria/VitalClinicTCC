(function () {
    // Marcador de versão: se esta linha NÃO aparecer no Console assim
    // que a página carregar, o navegador está rodando um app.js
    // DIFERENTE deste arquivo — ou seja, o problema é de deploy/cache,
    // não de código. Ajuste o texto/data a cada mudança relevante pra
    // facilitar a conferência.
    console.log('%c[app.js] versão carregada: 2026-09-01-responsive-mobile-first-v2.09.5', 'color:#0aa6bd;font-weight:bold');

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

    function setupRolePicker() {
        var picker = qs('.role-picker');
        if (!picker) {
            return;
        }
        var hiddenInput = qs('#role_context');
        var buttons = qsa('.role-option', picker);

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                buttons.forEach(function (b) { b.classList.remove('active'); });
                button.classList.add('active');
                if (hiddenInput) {
                    hiddenInput.value = button.getAttribute('data-role');
                }
            });
        });
    }

    function setupRoleSwitches() {
        qsa('[data-role-switch]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var form = checkbox.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });
    }

    /**
     * Menu hambúrguer (mobile/tablet, < 1024px): abre/fecha a gaveta de
     * navegação, fecha automaticamente ao navegar ou ao clicar fora, e
     * mantém o atributo aria-expanded sincronizado para acessibilidade.
     * Em telas >= 1024px o CSS já força o menu visível (ver styles.css),
     * então este JS não interfere no desktop.
     */
    function setupMobileNav() {
        var toggle = qs('[data-nav-toggle]');
        var nav = qs('[data-nav]');
        if (!toggle || !nav) {
            return;
        }

        function closeNav() {
            nav.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        function toggleNav() {
            var isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleNav();
        });

        qsa('a', nav).forEach(function (link) {
            link.addEventListener('click', closeNav);
        });

        document.addEventListener('click', function (event) {
            if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                closeNav();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeNav();
            }
        });

        // Se a tela for redimensionada para desktop com o menu aberto no
        // mobile, garante que o estado não fique "preso" ao voltar pro
        // mobile depois.
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024) {
                closeNav();
            }
        });
    }

    /**
     * Ativa o modo "cartão empilhado" das tabelas em telas pequenas
     * (< 640px, ver styles.css): copia o texto de cada <th> para um
     * atributo data-label na célula correspondente, sem precisar tocar
     * em nenhuma página PHP que renderiza tabelas. Em telas >= 640px o
     * CSS ignora essa classe e a tabela usa a rolagem horizontal normal
     * (.table-wrap { overflow-x: auto }).
     */
    function setupResponsiveTables() {
        qsa('.table-wrap table').forEach(function (table) {
            var headers = qsa('thead th', table).map(function (th) {
                return th.textContent.trim();
            });
            if (!headers.length) {
                return;
            }
            table.classList.add('responsive-cards');
            qsa('tbody tr', table).forEach(function (row) {
                qsa('td', row).forEach(function (cell, index) {
                    if (headers[index]) {
                        cell.setAttribute('data-label', headers[index]);
                    }
                });
            });
        });
    }

    function setupModals() {
        qsa('[data-open-modal]').forEach(function (trigger) {
            var modal = document.getElementById(trigger.getAttribute('data-open-modal'));
            if (!modal) {
                return;
            }
            trigger.addEventListener('click', function () {
                modal.showModal();
            });
        });

        qsa('dialog.modal').forEach(function (modal) {
            qsa('[data-close-modal]', modal).forEach(function (closeButton) {
                closeButton.addEventListener('click', function () {
                    modal.close();
                });
            });
            // Fecha ao clicar fora da caixa (no backdrop do <dialog>).
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.close();
                }
            });
        });
    }

    function setupSlots() {
        qsa('[data-slot-loader]').forEach(function (container) {
            var form = container.closest('form');
            var hiddenInput = form ? qs('[data-selected-slot]', form) : null;
            var submitButton = form ? qs('button[type="submit"]', form) : null;
            var dateInput = document.getElementById(container.getAttribute('data-date-input'));
            var doctorSelect = form ? qs('[data-appointment-doctor]', form) : null;
            var staticDoctorId = container.getAttribute('data-doctor-id');

            if (!hiddenInput || !dateInput || (!doctorSelect && !staticDoctorId)) {
                return;
            }

            function currentDoctorId() {
                return doctorSelect ? doctorSelect.value : staticDoctorId;
            }

            function load() {
                var date = dateInput.value;
                var doctorId = currentDoctorId();
                if (!date || !doctorId) {
                    container.innerHTML = '<span class="muted">Selecione o médico e a data para ver os horários.</span>';
                    hiddenInput.value = '';
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
            if (doctorSelect) {
                doctorSelect.addEventListener('change', load);
            }
            form.addEventListener('submit', function (event) {
                if (!hiddenInput.value) {
                    event.preventDefault();
                    window.alert('Selecione um horario livre.');
                }
            });
            if (currentDoctorId() && dateInput.value) {
                load();
            }
        });
    }

    /**
     * Campo de busca com sugestões (autocomplete) para seleção de
     * Paciente/Médico no formulário de nova consulta. Faz a filtragem
     * localmente, sobre a lista de opções já embutida na página em
     * <script type="application/json">, sem precisar de requisição
     * extra ao servidor a cada letra digitada.
     */
    function setupAutocomplete() {
        qsa('[data-autocomplete]').forEach(function (wrapper) {
            var searchInput = qs('[data-autocomplete-search]', wrapper);
            var valueInput = qs('[data-autocomplete-value]', wrapper);
            var list = qs('[data-autocomplete-list]', wrapper);
            var optionsScript = qs('[data-autocomplete-options]', wrapper);
            var form = wrapper.closest('form');

            if (!searchInput || !valueInput || !list || !optionsScript) {
                return;
            }

            var options = [];
            try {
                options = JSON.parse(optionsScript.textContent || '[]');
            } catch (parseError) {
                options = [];
            }

            var filtered = [];
            var highlighted = -1;

            function closeList() {
                list.hidden = true;
                list.innerHTML = '';
                highlighted = -1;
            }

            function selectOption(option) {
                valueInput.value = option.id;
                searchInput.value = option.label;
                closeList();
                // Definir .value por JavaScript NÃO dispara o evento
                // "change" sozinho — precisamos disparar manualmente,
                // já que o carregador de horários do médico
                // (setupSlots) escuta esse evento para recarregar os
                // horários disponíveis assim que um médico é escolhido.
                valueInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function renderList() {
                list.innerHTML = '';

                if (filtered.length === 0) {
                    var empty = document.createElement('li');
                    empty.className = 'autocomplete-empty';
                    empty.textContent = 'Nenhum resultado encontrado.';
                    list.appendChild(empty);
                    list.hidden = false;
                    return;
                }

                filtered.forEach(function (option, index) {
                    var item = document.createElement('li');
                    item.className = 'autocomplete-item' + (index === highlighted ? ' is-active' : '');
                    item.setAttribute('role', 'option');

                    var title = document.createElement('strong');
                    title.textContent = option.label;
                    item.appendChild(title);

                    if (option.sub) {
                        var sub = document.createElement('span');
                        sub.className = 'muted';
                        sub.textContent = option.sub;
                        item.appendChild(sub);
                    }

                    // "mousedown" (em vez de "click") garante que a
                    // seleção seja registrada ANTES do evento "blur"
                    // do campo de texto fechar a lista.
                    item.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                        selectOption(option);
                    });

                    list.appendChild(item);
                });

                list.hidden = false;
            }

            function search(term) {
                var normalized = term.trim().toLowerCase();
                if (!normalized) {
                    filtered = [];
                    closeList();
                    return;
                }
                filtered = options
                    .filter(function (option) {
                        return option.terms.indexOf(normalized) !== -1;
                    })
                    .slice(0, 8);
                highlighted = filtered.length ? 0 : -1;
                renderList();
            }

            searchInput.addEventListener('input', function () {
                if (valueInput.value) {
                    valueInput.value = '';
                    valueInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                search(searchInput.value);
            });

            searchInput.addEventListener('keydown', function (event) {
                if (list.hidden) {
                    return;
                }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    highlighted = Math.min(highlighted + 1, filtered.length - 1);
                    renderList();
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    highlighted = Math.max(highlighted - 1, 0);
                    renderList();
                } else if (event.key === 'Enter') {
                    if (highlighted >= 0 && filtered[highlighted]) {
                        event.preventDefault();
                        selectOption(filtered[highlighted]);
                    }
                } else if (event.key === 'Escape') {
                    closeList();
                }
            });

            searchInput.addEventListener('blur', function () {
                window.setTimeout(closeList, 100);
            });
            // A validação de "opção realmente selecionada" foi centralizada
            // em setupNewAppointmentForm() — manter também aqui causava
            // MÚLTIPLOS listeners de "submit" concorrentes no mesmo
            // formulário (um por campo de autocomplete + o do horário +
            // o novo, unificado), o que gerava comportamento
            // inconsistente (às vezes nenhum feedback aparecia).
        });
    }

    /**
     * Cria (uma vez só) a "pilha" de toasts flutuantes no canto da tela,
     * reaproveitada por qualquer chamada de showToast().
     */
    function toastStack() {
        var stack = qs('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        return stack;
    }

    function showToast(message, type) {
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + (type === 'error' ? 'error' : 'success');
        toast.textContent = message;
        toastStack().appendChild(toast);
        window.setTimeout(function () {
            toast.remove();
        }, 4000);
    }

    /**
     * Busca a página atual de novo (sem navegar pra ela) e substitui só
     * o painel da tabela de consultas pelo conteúdo novo — é assim que
     * a lista se atualiza sem precisar de F5 manual.
     */
    function refreshAppointmentsPanel() {
        var current = qs('[data-appointments-panel]');
        if (!current) {
            return;
        }
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest-Fragment' } })
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var fresh = doc.querySelector('[data-appointments-panel]');
                if (fresh) {
                    current.replaceWith(fresh);
                    // A tabela recém-inserida ainda não passou pelo
                    // realce de acessibilidade/mobile do setup inicial.
                    setupResponsiveTables();
                }
            })
            .catch(function (error) {
                console.error('[nova-consulta] falha ao atualizar a lista', error);
                // Se a atualização "silenciosa" falhar, o pior caso é a
                // lista ficar desatualizada até o próximo F5 — não é
                // motivo pra mostrar erro ao usuário, já a consulta em
                // si já foi salva com sucesso nesse ponto.
            });
    }

    /**
     * Validação, envio via AJAX e feedback do formulário "Adicionar Nova
     * Consulta". Centraliza num único lugar a checagem dos 3 campos que
     * dependem de seleção (paciente, médico e horário), o disparo da
     * requisição, o estado de carregamento do botão e o toast final —
     * evitando o reload de página tradicional.
     */
    function setupNewAppointmentForm() {
        var form = document.querySelector('#new-appointment-modal form');
        if (!form) {
            return;
        }

        var modal = document.getElementById('new-appointment-modal');
        var patientInput = qs('input[name="patient_id"]', form);
        var doctorInput = qs('input[name="doctor_id"]', form);
        var slotInput = qs('[data-selected-slot]', form);
        var submitButton = qs('button[type="submit"]', form);

        var errorBox = document.createElement('p');
        errorBox.className = 'alert alert-error';
        errorBox.style.display = 'none';
        form.insertBefore(errorBox, form.firstChild);

        function showError(message) {
            errorBox.textContent = message;
            errorBox.style.display = 'block';
        }

        function hideError() {
            errorBox.style.display = 'none';
        }

        function setLoading(isLoading) {
            if (!submitButton) {
                return;
            }
            submitButton.disabled = isLoading;
            submitButton.textContent = isLoading ? 'Agendando...' : 'Agendar consulta';
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            try {
                var faltando = [];
                if (!patientInput || !patientInput.value) {
                    faltando.push('paciente');
                }
                if (!doctorInput || !doctorInput.value) {
                    faltando.push('médico');
                }
                if (!slotInput || !slotInput.value) {
                    faltando.push('horário');
                }

                console.debug('[nova-consulta] tentativa de envio', {
                    patient_id: patientInput ? patientInput.value : null,
                    doctor_id: doctorInput ? doctorInput.value : null,
                    slot_id: slotInput ? slotInput.value : null,
                    faltando: faltando,
                });

                if (faltando.length > 0) {
                    showError('Antes de agendar, selecione: ' + faltando.join(', ') + '.');
                    return;
                }

                hideError();
                setLoading(true);

                fetch(form.action || window.location.href, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (response) {
                        return response.text().then(function (rawText) {
                            var data;
                            try {
                                data = JSON.parse(rawText);
                            } catch (parseError) {
                                // O servidor respondeu algo que NÃO é JSON —
                                // geralmente um aviso/warning do PHP impresso
                                // antes do json_encode(), ou uma página de
                                // erro HTML. Mostramos o conteúdo bruto no
                                // console pra dar visibilidade real do que
                                // quebrou, em vez de um erro genérico.
                                console.error('[nova-consulta] resposta do servidor não é JSON válido:', rawText);
                                throw new Error('O servidor respondeu algo inesperado (não era JSON). Veja o Console (F12) para o conteúdo completo.');
                            }
                            return { ok: response.ok, status: response.status, data: data };
                        });
                    })
                    .then(function (result) {
                        setLoading(false);

                        if (!result.ok) {
                            var mensagem = (result.data && result.data.message)
                                || 'Não foi possível agendar a consulta (erro ' + result.status + ').';
                            showError(mensagem);
                            console.error('[nova-consulta] backend recusou o envio', result);
                            return;
                        }

                        hideError();
                        if (modal) {
                            modal.close();
                        }
                        var mensagemSucesso = (result.data.flash && result.data.flash.length)
                            ? result.data.flash[result.data.flash.length - 1].message
                            : 'Consulta agendada com sucesso.';
                        showToast(mensagemSucesso, 'success');
                        refreshAppointmentsPanel();
                        form.reset();
                        if (patientInput) { patientInput.value = ''; }
                        if (doctorInput) { doctorInput.value = ''; }
                        if (slotInput) { slotInput.value = ''; }
                    })
                    .catch(function (error) {
                        setLoading(false);
                        console.error('[nova-consulta] falha ao enviar', error);
                        showError(error && error.message
                            ? error.message
                            : 'Falha de conexão ao tentar agendar. Verifique sua internet e tente novamente.');
                    });
            } catch (error) {
                console.error('[nova-consulta] erro inesperado ao validar o envio', error);
                setLoading(false);
                showError('Ocorreu um erro inesperado. Veja o console do navegador (F12) para detalhes.');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupPwa();
        setupConfirmations();
        setupNetworkBanner();
        setupRolePicker();
        setupRoleSwitches();
        setupMobileNav();
        setupResponsiveTables();
        setupModals();
        setupSlots();
        setupAutocomplete();
        setupNewAppointmentForm();
    });
})();
