<?php

function render_historico(array $paciente, array $consultas): void
{
    render_header($paciente, 'historico');

    // lista de anos que aparecem nas consultas desse paciente, pra montar
    // as opções do filtro de Período sem inventar ano que não existe.
    // array_map pega só o ano (4 dígitos) de cada consulta, array_unique
    // tira repetidos, rsort ordena do mais recente pro mais antigo
    $anosComConsulta = array_unique(array_map(
        static fn(array $consulta): string => (new DateTime($consulta['slot_start']))->format('Y'),
        $consultas
    ));
    rsort($anosComConsulta);

    ?>
    <h1>Histórico de consultas</h1>
    <p class="text-muted" style="margin-bottom: 20px;">Suas consultas já finalizadas ou canceladas</p>

    <?php if (empty($consultas)): ?>
        <div class="card">
            <p>Você ainda não tem nenhuma consulta no histórico.</p>
        </div>
    <?php else: ?>
        <!-- linha de filtros: as 4 abas de status à esquerda (Todas / Realizadas
             / Canceladas / Ausências), o botão "Período" à direita. Os dois
             filtros se combinam, um cartão só fica visível se bater com a aba
             ATUAL e com o período ATUAL, quem decide isso é o JS lá no final -->
        <div class="historico-filtros-linha">
            <div class="tabs-secundarias" id="historico-abas">
                <button type="button" class="tab-secundaria tab-ativa" data-status="">
                    Todas
                </button>
                <button type="button" class="tab-secundaria" data-status="completed">
                    Realizadas
                </button>
                <button type="button" class="tab-secundaria" data-status="cancelled">
                    Canceladas
                </button>
                <button type="button" class="tab-secundaria" data-status="no_show">
                    Ausências
                </button>
            </div>

            <div class="filtros-wrapper">
                <button type="button" id="periodo-trigger" class="periodo-trigger">
                    <span id="periodo-trigger-texto">Todos os períodos</span>
                </button>

                <div id="periodo-dropdown" class="filtros-dropdown" hidden>
                    <label for="filtro-periodo" style="display:block; font-weight:700; margin-bottom:6px;">
                        Período
                    </label>
                    <select id="filtro-periodo">
                        <option value="">Todos os períodos</option>
                        <?php foreach ($anosComConsulta as $ano): ?>
                            <option value="<?= h($ano) ?>"><?= h($ano) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- aparece só quando os filtros escolhidos não baterem com NENHUM
             cartão (ex: escolheu "Ausências" e nesse período não teve
             nenhuma). Começa escondido, o JS decide quando mostrar -->
        <div class="card" id="historico-sem-resultado" hidden>
            <p>Nenhuma consulta encontrada para esse filtro.</p>
        </div>

    <div id="historico-lista">

        <?php foreach ($consultas as $consulta): ?>
            <?php
                // inicial do médico pro círculo colorido (avatar), e o ano
                // da consulta, usado no atributo data-ano abaixo, é isso
                // que o filtro de Período lê pra saber o que mostrar
                $inicialMedico = mb_strtoupper(mb_substr($consulta['doctor_name'], 0, 1));
                $ano = (new DateTime($consulta['slot_start']))->format('Y');
            ?>
            <!-- data-status e data-ano não aparecem pro usuário, são só
                 "etiquetas" que o JavaScript lá embaixo lê pra decidir se
                 esse cartão fica visível de acordo com a aba/período
                 escolhidos -->
            <div class="appointment-card" data-status="<?= h($consulta['status']) ?>" data-ano="<?= h($ano) ?>">
                <div class="historico-card-top">
                    <!-- coluna 1 (esquerda): avatar + nome do médico, só isso -->
                    <div class="historico-medico-info">
                        <div class="clinica-avatar medico-avatar">
                            <?= h($inicialMedico) ?>
                        </div>
                        <div class="appointment-doctor"><?= h($consulta['doctor_name']) ?></div>
                    </div>

                    <!-- coluna 2 (meio): especialidade, clínica e data juntas,
                         sem ícone nenhum, o CSS (.historico-meta-central) é
                         que faz essa coluna "flutuar" no meio do cartão -->
                    <div class="historico-meta-central text-muted">
                        <strong style="color: var(--color-text-dark);"><?= h($consulta['specialty_name']) ?></strong> · <?= h($consulta['clinic_name']) ?> · <?= h(format_datetime($consulta['slot_start'])) ?>
                    </div>

                    <!-- coluna 3 (direita): só o selo de status, sem botão,
                         é ele que ocupa esse lugar -->
                    <span class="<?= h(status_badge_class($consulta['status'])) ?>">
                        <?= h(status_label($consulta['status'])) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


        <script>
            var listaHistorico = document.getElementById('historico-lista');
            var avisoSemResultado = document.getElementById('historico-sem-resultado');
            var abas = document.querySelectorAll('#historico-abas .tab-secundaria');

            var botaoPeriodo = document.getElementById('periodo-trigger');
            var painelPeriodo = document.getElementById('periodo-dropdown');
            var selectPeriodo = document.getElementById('filtro-periodo');
            var textoPeriodoAtual = document.getElementById('periodo-trigger-texto');

            // status da aba selecionada agora ('' = Todas), fica numa
            // variável porque tanto o clique na aba quanto a troca de
            // período precisam saber o status atual pra aplicar os DOIS
            // filtros ao mesmo tempo
            var statusAtivo = '';

            // percorre todos os cartões e só deixa visível quem bate com o
            // status escolhido E com o ano escolhido ao mesmo tempo
            function aplicarFiltrosHistorico() {
                var anoEscolhido = selectPeriodo.value;
                var cards = listaHistorico.querySelectorAll('.appointment-card');
                var visiveis = 0;

                cards.forEach(function (card) {
                    var bateComStatus = statusAtivo === '' || card.getAttribute('data-status') === statusAtivo;
                    var bateComAno = anoEscolhido === '' || card.getAttribute('data-ano') === anoEscolhido;

                    if (bateComStatus && bateComAno) {
                        card.style.display = '';
                        visiveis++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                avisoSemResultado.hidden = visiveis !== 0;
            }

            abas.forEach(function (aba) {
                aba.addEventListener('click', function () {
                    abas.forEach(function (outraAba) {
                        outraAba.classList.remove('tab-ativa');
                    });
                    aba.classList.add('tab-ativa');

                    statusAtivo = aba.getAttribute('data-status');
                    aplicarFiltrosHistorico();
                });
            });

            botaoPeriodo.addEventListener('click', function (evento) {
                evento.stopPropagation();
                painelPeriodo.hidden = !painelPeriodo.hidden;
            });

            document.addEventListener('click', function (evento) {
                if (!painelPeriodo.contains(evento.target) && evento.target !== botaoPeriodo) {
                    painelPeriodo.hidden = true;
                }
            });

            selectPeriodo.addEventListener('change', function () {
                var opcaoEscolhida = selectPeriodo.options[selectPeriodo.selectedIndex];
                textoPeriodoAtual.textContent = opcaoEscolhida.text;

                aplicarFiltrosHistorico();
                painelPeriodo.hidden = true;
            });
        </script>

    <?php endif; ?>
<?php
    render_footer();
}