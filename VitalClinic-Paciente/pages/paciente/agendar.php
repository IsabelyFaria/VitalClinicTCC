<?php

function render_agendar_clinicas(array $paciente, array $clinicas, ?string $busca, array $especialidades, array $todosOsMedicos, ?string $especialidadeInicial = null): void
{
    // TELA 1: lista de clínicas pra escolher. 'agendar' como página atual
    // é usado nas 3 telas desse fluxo, pra o link "Agendar" ficar
    // destacado no menu em qualquer uma delas
    render_header($paciente, 'agendar');
     ?>
    <h1>Agendar consulta</h1>
    <p class="text-muted" style="margin-bottom: 20px;">Escolha uma clínica ou busque direto por um médico</p>

    <?php if (empty($clinicas)): ?>
        <div class="card">
            <p>Nenhuma clínica disponível no momento.</p>
        </div>
    <?php else: ?>
        <!-- as duas abas: "Clínicas" (visão de sempre) e "Médicos" (lista
             de TODOS os médicos, de qualquer clínica). A busca e o filtro
             de especialidade logo abaixo são COMPARTILHADOS pelas duas,
             o JavaScript decide em qual lista aplicar, dependendo de qual
             aba está ativa no momento -->
        <div class="tabs-secundarias">
            <button type="button" id="tab-clinicas" class="tab-secundaria tab-ativa">Clínicas</button>
            <button type="button" id="tab-medicos-tela1" class="tab-secundaria">Médicos</button>
        </div>

        <div class="agendar-busca-linha">
            <div class="search-box" style="flex: 1; margin-bottom: 0;">
                <input type="text" id="busca-clinicas" placeholder="Buscar clínica, médico ou especialidade..." autocomplete="off">
            </div>

            <div class="filtros-wrapper">
                <button type="button" id="filtros-trigger" class="periodo-trigger">
                    Filtros
                </button>

                <div id="filtros-dropdown" class="filtros-dropdown" hidden>
                    <label for="filtro-especialidade" style="display:block; font-weight:700; margin-bottom:6px;">
                        Especialidade
                    </label>
                    <select id="filtro-especialidade">
                        <option value="">Todas as especialidades</option>
                        <?php foreach ($especialidades as $especialidade): ?>
                            <option value="<?= h($especialidade['specialty_name']) ?>">
                                <?= h($especialidade['specialty_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card" id="sem-resultado-clinicas" hidden>
            <p>Desculpa, não encontramos nenhum resultado para essa busca.</p>
        </div>

        <!-- VISÃO 1: grade de clínicas (igual já era) -->
        <div id="visao-clinicas">
            <div id="lista-clinicas" class="clinicas-grid">
                <?php foreach ($clinicas as $clinica): ?>
                    <?php
                        $totalMedicos = (int) $clinica['total_medicos'];
                        $textoMedicos = $totalMedicos === 1
                            ? '1 médico disponível'
                            : $totalMedicos . ' médicos disponíveis';
                        $textoBusca = $clinica['clinic_name'] . ' ' . $clinica['nomes_medicos'] . ' ' . $clinica['nomes_especialidades'];
                        $inicialClinica = mb_strtoupper(mb_substr($clinica['clinic_name'], 0, 1));
                    ?>
                    <div class="appointment-card clinica-card"
                         data-nome="<?= h($textoBusca) ?>"
                         data-especialidades="<?= h($clinica['nomes_especialidades']) ?>">
                        <div class="clinica-avatar"><?= h($inicialClinica) ?></div>
                        <div class="clinica-card-info">
                            <div class="appointment-doctor"><?= h($clinica['clinic_name']) ?></div>
                            <div class="appointment-meta">
                                <?= h($clinica['clinic_address'] ?: 'Endereço não informado') ?>
                            </div>

                            <!-- um style só nessa linha — font-weight: 700 deixa em negrito, 
                             margin-bottom: 12px cria o respiro antes do botão "Ver médicos" que vem logo depois. -->
                           <div class="appointment-meta" style="font-weight: 700; margin-bottom: 12px; color: var(--color-text-dark);">
                                <?= h($textoMedicos) ?>
                            </div>
                            <a href="<?= h(app_url(['page' => 'agendar', 'clinica' => $clinica['clinic_id']])) ?>" class="btn btn-primary btn-sm" style="margin-top: auto; width: 100%; text-align: center;">
                                Ver médicos
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- VISÃO 2: lista de TODOS os médicos, de qualquer clínica.
             Começa ESCONDIDA, só aparece ao clicar na aba "Médicos" -->
        <div id="visao-medicos-tela1" hidden>
            <?php if (empty($todosOsMedicos)): ?>
                <div class="card">
                    <p>Nenhum médico disponível no momento.</p>
                </div>
            <?php else: ?>
                <div id="lista-medicos-tela1">
                    <?php foreach ($todosOsMedicos as $medico): ?>
                        <div class="medico-row"
                             data-nome="<?= h($medico['doctor_name'] . ' ' . $medico['specialty_name'] . ' ' . $medico['clinic_name']) ?>"
                             data-especialidades="<?= h($medico['specialty_name']) ?>">
                            <div class="clinica-avatar medico-avatar">
                                <?= h(mb_strtoupper(mb_substr($medico['doctor_name'], 0, 1))) ?>
                            </div>
                            <div class="medico-info">
                                <div class="appointment-doctor"><?= h($medico['doctor_name']) ?></div>
                                <div class="appointment-meta"><strong style="color: var(--color-text-dark);"><?= h($medico['specialty_name']) ?></strong> · <?= h($medico['clinic_name']) ?></div>
                            </div>
                            <div class="medico-crm">CRM <?= h($medico['doctor_crm']) ?></div>
                            <a href="<?= h(app_url(['page' => 'agendar', 'medico' => $medico['doctor_id']])) ?>" class="btn btn-primary btn-sm">
                                Agendar consulta
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
            var campoBusca = document.getElementById('busca-clinicas');
            var selectEspecialidade = document.getElementById('filtro-especialidade');
            var avisoSemResultado = document.getElementById('sem-resultado-clinicas');

            var abaClinicas = document.getElementById('tab-clinicas');
            var abaMedicosTela1 = document.getElementById('tab-medicos-tela1');
            var visaoClinicas = document.getElementById('visao-clinicas');
            var visaoMedicosTela1 = document.getElementById('visao-medicos-tela1');

            var listaClinicas = document.getElementById('lista-clinicas');
            var listaMedicosTela1 = document.getElementById('lista-medicos-tela1');

            var botaoFiltros = document.getElementById('filtros-trigger');
            var painelFiltros = document.getElementById('filtros-dropdown');

            // essa função olha em qual aba a pessoa está AGORA, e filtra
            // só a lista correspondente (clínicas OU médicos), usando o
            // MESMO texto de busca e a MESMA especialidade escolhida,
            // é isso que faz o filtro "funcionar pros dois",
            // sem precisar de dois campos de busca separados
            function aplicarFiltros() {
                var termo = campoBusca.value.toLowerCase();
                var especialidadeEscolhida = selectEspecialidade.value.toLowerCase();

                // "!visaoClinicas.hidden" pergunta: "a visão de clínicas
                // está VISÍVEL agora?", se estiver, filtramos ela; se não
                // (ou seja, a de médicos que está visível), filtramos a
                // outra
                var estaNaAbaClinicas = !visaoClinicas.hidden;
                var listaAtual = estaNaAbaClinicas ? listaClinicas : listaMedicosTela1;

                if (!listaAtual) {
                    // acontece só se a lista de médicos estiver vazia (o
                    // "if (empty($todosOsMedicos))" lá do PHP), nesse caso
                    // não tem nem o elemento #lista-medicos-tela1 na
                    // página, então não tem nada pra filtrar
                    return;
                }

                var seletorCard = estaNaAbaClinicas ? '.clinica-card' : '.medico-row';
                var cards = listaAtual.querySelectorAll(seletorCard);
                var visiveis = 0;

                cards.forEach(function (card) {
                    var nomeDoCard = card.getAttribute('data-nome').toLowerCase();
                    var especialidadesDoCard = card.getAttribute('data-especialidades').toLowerCase();

                    var bateComTexto = nomeDoCard.includes(termo);
                    var bateComEspecialidade = especialidadeEscolhida === '' || especialidadesDoCard.includes(especialidadeEscolhida);

                    if (bateComTexto && bateComEspecialidade) {
                        card.style.display = '';
                        visiveis++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                avisoSemResultado.hidden = visiveis !== 0;
            }

            // troca de aba: mostra uma visão, esconde a outra, marca o
            // botão certo como ativo, e reaplica o filtro atual na lista
            // que acabou de aparecer (assim, se você já tinha escolhido
            // uma especialidade na aba Clínicas, ao trocar pra aba
            // Médicos ela já aparece filtrada igual, sem precisar
            // escolher de novo)
            function mostrarAba(nomeDaAba) {
                var ehClinicas = nomeDaAba === 'clinicas';

                visaoClinicas.hidden = !ehClinicas;
                visaoMedicosTela1.hidden = ehClinicas;

                abaClinicas.classList.toggle('tab-ativa', ehClinicas);
                abaMedicosTela1.classList.toggle('tab-ativa', !ehClinicas);

                aplicarFiltros();
            }

            abaClinicas.addEventListener('click', function () {
                mostrarAba('clinicas');
            });
            abaMedicosTela1.addEventListener('click', function () {
                mostrarAba('medicos');
            });

            campoBusca.addEventListener('input', aplicarFiltros);
            selectEspecialidade.addEventListener('change', aplicarFiltros);

            botaoFiltros.addEventListener('click', function (evento) {
                evento.stopPropagation();
                painelFiltros.hidden = !painelFiltros.hidden;
            });

            document.addEventListener('click', function (evento) {
                if (!painelFiltros.contains(evento.target) && evento.target !== botaoFiltros) {
                    painelFiltros.hidden = true;
                }
            });
            
            <?php if ($especialidadeInicial): ?>
                // a Home já mandou a gente pra cá com uma especialidade escolhida:
                // marca ela no filtro, troca pra aba "Médicos" e filtra na hora,
                // sem o paciente precisar clicar em mais nada
                selectEspecialidade.value = <?= json_encode($especialidadeInicial) ?>;
                mostrarAba('medicos');
            <?php endif; ?>

        </script>
    <?php endif; ?>
    <?php
    render_footer();
}

function render_agendar_medicos(array $paciente, array $clinica, array $medicos, ?string $busca): void
{
    // TELA 2: médicos de UMA clínica específica, já escolhida na tela 1
    render_header($paciente, 'agendar');
    ?>
    <a href="<?= h(app_url(['page' => 'agendar'])) ?>" class="btn btn-outline btn-sm btn-voltar" style="display:inline-block;margin-bottom:12px;">
        ‹ Voltar para lista de clínicas
    </a>

    <h1><?= h($clinica['clinic_name']) ?></h1>
    <p class="text-muted" style="margin-bottom: 20px;">
        <?= h($clinica['clinic_address'] ?: 'Endereço não informado') ?>
        <?php if (!empty($clinica['clinic_phone'])): ?> · <?= h($clinica['clinic_phone']) ?><?php endif; ?>
    </p>

    <?php if (empty($medicos)): ?>
        <div class="card">
            <p>Essa clínica não tem médicos disponíveis no momento.</p>
        </div>
    <?php else: ?>
        <?php
            // array_column($medicos, 'specialty_name') pega só a coluna
            // "specialty_name" de CADA médico do array, virando uma lista
            // tipo ['Clínico geral', 'Cardiologia', 'Clínico geral', ...]
            // (repetida, se dois médicos forem da mesma área).
            // array_unique tira as repetidas. array_values renumera as
            // chaves do array depois do array_unique (que deixa "buracos"
            // nos índices), sem isso, um foreach mais na frente ainda
            // funcionaria, mas é boa prática deixar limpo
            $especialidadesDaClinica = array_values(array_unique(array_column($medicos, 'specialty_name')));
            sort($especialidadesDaClinica);
            // sort() coloca a lista em ordem alfabética
        ?>

        <!-- as duas "abas": Médicos (lista de verdade) e Especialidades
             (pilulas clicáveis). Só uma fica visível por vez, o
             JavaScript lá embaixo decide qual, ao clicar em cada uma -->
        <div class="tabs-secundarias">
            <button type="button" id="tab-medicos" class="tab-secundaria tab-ativa">Médicos</button>
            <button type="button" id="tab-especialidades" class="tab-secundaria">Especialidades</button>
        </div>

        <div class="search-box">
            <input type="text" id="busca-medicos" placeholder="Buscar médico ou especialidade..." autocomplete="off">
        </div>

        <div class="card" id="sem-resultado-medicos" hidden>
            <p>Desculpa, não encontramos nenhum médico ou especialidade com esse nome.</p>
        </div>

        <!-- VISÃO 1: a lista de médicos de verdade -->
        <div id="visao-medicos">
            <div id="lista-medicos">
                <?php foreach ($medicos as $medico): ?>
                    <div class="medico-row" data-nome="<?= h($medico['doctor_name'] . ' ' . $medico['specialty_name']) ?>">
                        <div class="clinica-avatar medico-avatar">
                            <?= h(mb_strtoupper(mb_substr($medico['doctor_name'], 0, 1))) ?>
                        </div>
                        <div class="medico-info">
                            <div class="appointment-doctor"><?= h($medico['doctor_name']) ?></div>
                            <!-- <strong> é a tag HTML de negrito, uso ela em vez de mexer na classe .appointment-meta, 
                                assim só a especialidade fica em negrito, sem afetar a linha "Atendimento presencial" 
                                logo abaixo, que usa a mesma classe -->
                            <div class="appointment-meta"><strong style="color: var(--color-text-dark);"><?= h($medico['specialty_name']) ?></strong></div>
                            <div class="appointment-meta">Atendimento presencial</div>
                        </div>
                        <div class="medico-crm">CRM <?= h($medico['doctor_crm']) ?></div>
                        <a href="<?= h(app_url(['page' => 'agendar', 'medico' => $medico['doctor_id']])) ?>" class="btn btn-primary btn-sm">
                            Agendar consulta
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- VISÃO 2: as pilulas de especialidade, começa ESCONDIDA -->
        <div id="visao-especialidades" hidden>
            <div class="especialidades-pilulas">
                <?php foreach ($especialidadesDaClinica as $especialidade): ?>
                    <button type="button" class="pilula-especialidade" data-especialidade="<?= h($especialidade) ?>">
                        <?= h($especialidade) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
            var campoBusca = document.getElementById('busca-medicos');
            var listaMedicos = document.getElementById('lista-medicos');
            var avisoSemResultado = document.getElementById('sem-resultado-medicos');

            var abaMedicos = document.getElementById('tab-medicos');
            var abaEspecialidades = document.getElementById('tab-especialidades');
            var visaoMedicos = document.getElementById('visao-medicos');
            var visaoEspecialidades = document.getElementById('visao-especialidades');

            // função que troca qual "visão" está visível, e deixa a aba
            // certa marcada como ativa (cor preenchida)
            function mostrarAba(nomeDaAba) {
                var ehMedicos = nomeDaAba === 'medicos';

                visaoMedicos.hidden = !ehMedicos;
                visaoEspecialidades.hidden = ehMedicos;

                // classList.toggle(classe, condicao): se "condicao" for
                // true, GARANTE que a classe existe; se for false,
                // GARANTE que ela não existe. É um jeito de "ligar/desligar"
                // uma classe sem precisar escrever um if/else pra isso
                abaMedicos.classList.toggle('tab-ativa', ehMedicos);
                abaEspecialidades.classList.toggle('tab-ativa', !ehMedicos);
            }

            abaMedicos.addEventListener('click', function () {
                mostrarAba('medicos');
            });
            abaEspecialidades.addEventListener('click', function () {
                mostrarAba('especialidades');
            });

            // essa função filtra os médicos pelo texto do campo de busca,
            // ela virou uma função "nomeada" (em vez de ficar direto dentro
            // do addEventListener) porque vamos chamá-la de DOIS lugares
            // diferentes: quando a pessoa digita, E quando clica numa
            // pílula de especialidade
            function aplicarFiltro() {
                var termo = campoBusca.value.toLowerCase();
                var cards = listaMedicos.querySelectorAll('.medico-row');
                var visiveis = 0;

                cards.forEach(function (card) {
                    var nomeDoCard = card.getAttribute('data-nome').toLowerCase();
                    if (nomeDoCard.includes(termo)) {
                        card.style.display = '';
                        visiveis++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                avisoSemResultado.hidden = visiveis !== 0;
            }

            campoBusca.addEventListener('input', aplicarFiltro);

            // cada pílula de especialidade, ao ser clicada: escreve o nome
            // dela no campo de busca (como se a pessoa tivesse digitado),
            // roda o MESMO filtro de sempre, e volta pra aba "Médicos" pra
            // já mostrar o resultado. Assim reaproveitamos o filtro de
            // texto que já existe, em vez de criar um filtro separado só
            // pra especialidade
            document.querySelectorAll('.pilula-especialidade').forEach(function (pilula) {
                pilula.addEventListener('click', function () {
                    campoBusca.value = pilula.getAttribute('data-especialidade');
                    aplicarFiltro();
                    mostrarAba('medicos');
                });
            });
        </script>
    <?php endif; ?>
    <?php
    render_footer();
}

function render_agendar_horarios(array $paciente, array $medico, array $horarios, ?string $dataSelecionada, ?string $mesSelecionado): void
{
    // TELA 3: horários livres de UM médico específico já escolhido
    render_header($paciente, 'agendar');
    ?>
    <a href="<?= h(app_url(['page' => 'agendar', 'clinica' => $medico['clinic_id']])) ?>" class="btn btn-outline btn-sm btn-voltar" style="display:inline-block;margin-bottom:12px;">
        ‹ Voltar para lista de médicos dessa clínica
    </a>
    <!-- ANTES esse link voltava pra lista de médicos de TODAS as clínicas.
         Agora que existe a etapa da clínica no meio do caminho, ele volta
         pra lista de médicos DAQUELA clínica específica (usamos
         $medico['clinic_id'], que a find_doctor_details() já traz) -->

    <h1><?= h($medico['doctor_name']) ?></h1>
    <p class="text-muted" style="margin-bottom: 20px;">
        <?= h($medico['specialty_name']) ?> · <?= h($medico['clinic_name']) ?>
    </p>

    <?php if (empty($horarios)): ?>
        <div class="card">
            <p>Esse médico não tem horários livres no momento.</p>
        </div>
    <?php else: ?>
        <?php
            // agrupa os horários por data, igual antes, só que agora a CHAVE
            // do array é no formato "2026-09-20" (ano-mês-dia), em vez de
            // "20/09/2026". Esse formato (ano primeiro) é o "formato universal"
            // de data, mais fácil de comparar e de colocar numa URL sem
            // confusão. A gente só transforma isso no formato bonito (dd/mm)
            // na hora de mostrar pro usuário, mais abaixo
            $horariosPorData = [];
            foreach ($horarios as $horario) {
                $chaveData = (new DateTime($horario['slot_start']))->format('Y-m-d');
                $horariosPorData[$chaveData][] = $horario;
            }

            // array_keys() pega só as CHAVES de um array (nesse caso, só as
            // datas), descartando os valores, isso vira nossa lista de "quais
            // dias têm horário livre", pra desenhar uma pilula pra cada um
            $datasDisponiveis = array_keys($horariosPorData);

            // se ninguém escolheu uma data ainda (a pessoa acabou de entrar
            // nessa tela, sem clicar em nenhuma pilula), ou escolheu uma data
            // que não existe mais (por exemplo, alguém marcou o último horário
            // daquele dia enquanto ela estava pensando), usa a primeira data
            // disponível como padrão, pra tela nunca abrir vazia sem motivo
            if ($dataSelecionada === null || !isset($horariosPorData[$dataSelecionada])) {
                $dataSelecionada = $datasDisponiveis[0];
            }

            // confere se o "mês" que veio na URL tem um formato válido
            // (AAAA-MM, tipo "2026-09"). preg_match confere se o texto BATE
            // com esse padrão, devolve 1 se bateu, 0 se não bateu
            if ($mesSelecionado === null || !preg_match('/^\d{4}-\d{2}$/', $mesSelecionado)) {
                // sem mês válido na URL: mostra o mês da data selecionada.
                // substr($texto, 0, 7) pega só os 7 primeiros caracteres de
                // uma string, ou seja, de "2026-09-20" sobra só "2026-09"
                $mesSelecionado = substr($dataSelecionada, 0, 7);
            }

            // monta as informações do calendário desse mês
            $primeiroDiaDoMes = new DateTime($mesSelecionado . '-01');

            $totalDiasNoMes = (int) $primeiroDiaDoMes->format('t');
            // format('t') devolve quantos dias tem esse mês (28, 29, 30 ou 31)

            $diaDaSemanaInicio = (int) $primeiroDiaDoMes->format('w');
            // format('w') devolve em que dia da semana cai o dia 1 desse mês:
            // 0 = domingo, 1 = segunda... 6 = sábado. É esse número que diz
            // quantas "casinhas vazias" o calendário precisa antes do dia 1

            // clone cria uma CÓPIA do objeto DateTime, pra gente poder usar
            // modify() sem alterar o $primeiroDiaDoMes original (sem o clone,
            // modify() mudaria a mesma variável que ainda vamos usar embaixo)
            $mesAnterior = (clone $primeiroDiaDoMes)->modify('-1 month')->format('Y-m');
            $mesSeguinte = (clone $primeiroDiaDoMes)->modify('+1 month')->format('Y-m');

            // nomes dos meses em português (o PHP só sabe os nomes em inglês
            // por padrão, format('F') devolveria "August", não "Agosto")
            $nomesDosMeses = [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
            ];
            $nomeDoMesAtual = $nomesDosMeses[(int) $primeiroDiaDoMes->format('n')] . ' ' . $primeiroDiaDoMes->format('Y');
        ?>

        <!-- id="calendario" é o "alvo" da âncora #calendario usada nos links
             abaixo: depois de recarregar a página, o navegador rola sozinho
             até aqui, em vez de voltar pro topo -->
        <div class="agendar-grid" id="calendario">
            <!-- legenda explicando as 3 cores do calendário. Fica fora dos
                 dois cards, direto dentro do .agendar-grid, com uma regra
                 de CSS que obriga ela a ocupar a linha inteira sozinha
                 (repare no CSS: flex: 0 0 100%), é isso que garante que
                 ela sempre fica numa fileira própria, acima, empurrando o
                 calendário e os horários pra ficarem lado a lado embaixo -->
            <div class="calendario-legenda">
                <span class="legenda-item">
                    <span class="legenda-bolinha selected"></span>
                    Dia selecionado
                </span>
                <span class="legenda-item">
                    <span class="legenda-bolinha available"></span>
                    Dia com horário disponível
                </span>
                <span class="legenda-item">
                    <span class="legenda-bolinha disabled"></span>
                    Sem horário disponível
                </span>
            </div>

            <div class="card calendar-card">
                <div class="calendar-header">
                    <!-- seta "mês anterior": recarrega a mesma tela, trocando
                         só o "mes" na URL pro mês de antes, mas mantendo a
                         data selecionada como estava -->
                    <a href="<?= h(app_url(['page' => 'agendar', 'medico' => $medico['doctor_id'], 'data' => $dataSelecionada, 'mes' => $mesAnterior])) ?>#calendario" class="calendar-nav-arrow">‹</a>

                    <span><?= h($nomeDoMesAtual) ?></span>

                    <!-- mesma ideia, só que avançando pro mês seguinte -->
                    <a href="<?= h(app_url(['page' => 'agendar', 'medico' => $medico['doctor_id'], 'data' => $dataSelecionada, 'mes' => $mesSeguinte])) ?>#calendario" class="calendar-nav-arrow">›</a>
                </div>

                <!-- cabeçalho fixo com as abreviações dos dias da semana,
                     sempre na mesma ordem (domingo a sábado), só texto, sem
                     nenhum PHP dinâmico aqui -->
                <div class="calendar-weekdays">
                    <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                </div>

                <div class="calendar-days">
                    <?php for ($i = 0; $i < $diaDaSemanaInicio; $i++): ?>
                        <span class="calendar-day empty"></span>
                        <!-- casinhas vazias antes do dia 1, só pra empurrar
                             ele pra coluna certa da semana -->
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $totalDiasNoMes; $dia++): ?>
                        <?php
                            // str_pad garante que o dia 5 vire "05" (com zero
                            // na frente), pra bater com o formato "2026-09-05"
                            // que usamos como chave no $horariosPorData
                            $dataDaCelula = $mesSelecionado . '-' . str_pad((string) $dia, 2, '0', STR_PAD_LEFT);
                        ?>

                        <?php if (isset($horariosPorData[$dataDaCelula])): ?>
                            <!-- esse dia TEM horário livre: vira um link
                                 clicável (classe "available"), e ganha a
                                 classe extra "selected" só se for o dia que
                                 está sendo mostrado no card da direita agora -->
                            <a href="<?= h(app_url(['page' => 'agendar', 'medico' => $medico['doctor_id'], 'data' => $dataDaCelula, 'mes' => $mesSelecionado])) ?>#calendario"
                               class="calendar-day available <?= $dataDaCelula === $dataSelecionada ? 'selected' : '' ?>">
                                <?= $dia ?>
                            </a>
                        <?php else: ?>
                            <!-- esse dia NÃO tem horário: vira um <span> comum,
                                 sem link nenhum (classe "disabled"), pra ficar
                                 visualmente apagado e realmente não clicável -->
                            <span class="calendar-day disabled"><?= $dia ?></span>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="card horarios-card">
                <div class="section-title">Horários disponíveis</div>
                <p class="text-muted" style="margin-bottom: 16px;">
                    <!-- mostra a data selecionada por extenso (dd/mm/aaaa),
                         só pra confirmar visualmente qual dia está aberto -->
                    <?= h((new DateTime($dataSelecionada))->format('d/m/Y')) ?>
                </p>

                <div class="time-slot-grid">
                    <?php foreach ($horariosPorData[$dataSelecionada] as $horario): ?>
                        <!-- "abre a gaveta" só do dia selecionado e desenha um
                             botão pra cada horário livre dentro dela -->
                        <form method="post" action="<?= h(app_url()) ?>" onsubmit="return confirm('Confirma marcar essa consulta?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="marcar_consulta">
                            <input type="hidden" name="slot_id" value="<?= (int) $horario['id'] ?>">
                            <!-- slot_id: diz EXATAMENTE qual horário marcar -->
                            <input type="hidden" name="medico_id" value="<?= (int) $medico['doctor_id'] ?>">
                            <!-- medico_id: se der erro (ex: alguém foi mais
                                 rápido), volta pra tela DESSE médico, não pra
                                 lista geral -->
                            <input type="hidden" name="data" value="<?= h($dataSelecionada) ?>">
                            <input type="hidden" name="mes" value="<?= h($mesSelecionado) ?>">
                            <!-- data e mes: se der erro, volta pra essa MESMA
                                 data/mês que estavam abertos, em vez de
                                 resetar o calendário pro padrão -->
                            <button type="submit" class="time-slot-btn">
                                <?= h((new DateTime($horario['slot_start']))->format('H:i')) ?>
                            </button>
                            <!-- Cada horário vira um <form> pequenininho, sozinho, com o botão dentro, assim, ao
                                 clicar num horário, ele já manda um POST direto pra marcar aquela consulta
                                 específica (o slot_id escondido diz qual). -->
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php
    render_footer();
}