<?php
declare(strict_types=1);
//   já vimos isso no index.php: liga o modo "estrito" de tipos do PHP, pra pegar erros
//   de tipo (ex.: passar um número onde se espera um texto) o quanto antes
 
function render_header(?array $paciente, string $paginaAtual = ''): void
{
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
       <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Vital Clinic — Paciente</title>
        <link rel="icon" href="<?= h(asset_url('assets/brand/vital-clinic-mark.svg')) ?>" type="image/svg+xml">
        <!-- esse é o ícone que aparece do lado do título, na abinha do
             navegador. asset_url() é a mesma função que já usamos pro
             logo do topbar e pro paciente.css, ela monta o caminho certo
             e já cuida do cache-busting sozinha -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= h(asset_url('assets/css/paciente.css')) ?>">
    </head>
    <body>

    <div class="topbar">
        <!-- asset_url() já existe no seu helpers.php, é a mesma função que a tela de Histórico/CSS já usa pra 
         carregar o paciente.css, ela monta o caminho certo do arquivo -->
        <div class="topbar-logo">
            <img src="<?= h(asset_url('assets/brand/vital-clinic-logo.svg')) ?>" alt="Vital Clinic">
        </div>
        <?php if ($paciente !== null): ?>
            <nav class="topbar-nav">
                <?php
                $links = [
                    'dashboard'  => 'Início',
                    'consultas'  => 'Consultas',
                    'agendar'    => 'Agendar',
                    'historico'  => 'Histórico',
                    'notificacoes'  => 'Notificações',
                ];

                foreach ($links as $slug => $rotulo):
                    $classe = ($paginaAtual === $slug) ? 'active' : '';
                    ?>
                    <a href="<?= h(app_url(['page' => $slug])) ?>" class="<?= h($classe) ?>">
                        <?= h($rotulo) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- NOVO: no lugar do botão "Sair" solto, agora tem essa div
                 "topbar-profile", que embrulha o avatar clicável + o painel
                 que abre quando clica nele -->
            <div class="topbar-profile">
    <button type="button" id="perfil-trigger" class="perfil-avatar">
        <?= h(mb_strtoupper(mb_substr($paciente['name'], 0, 1))) ?>
    </button>

    <!-- painel bem mais enxuto agora: sem nome, sem email, só os dois
         itens de ação (Perfil e Sair). A identificação de quem está
         logado fica só na letrinha da bolinha mesmo -->
    <div id="perfil-dropdown" class="perfil-dropdown" hidden>
        <a href="<?= h(app_url(['page' => 'perfil'])) ?>" class="perfil-dropdown-item">
            Perfil
        </a>
        <a href="<?= h(app_url(['page' => 'sobre'])) ?>" class="perfil-dropdown-item">
            Sobre nós
        </a>
        <!-- mesma classe "perfil-dropdown-item" do link de Perfil acima,
             então já sai com a aparência certinha, sem precisar de CSS novo
             pra esse link em si -->
             
        <!-- repara que isso já era um link pra ?page=perfil antes também
             (só que chamado de "Ver meu perfil" e com uma aparência de
             link padrão de navegador). A FUNÇÃO já estava certa, só a
             ROUPA dele que vai mudar agora, com a classe nova
             "perfil-dropdown-item" -->

        <form method="post" action="<?= h(app_url()) ?>" style="margin:0;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="perfil-dropdown-item perfil-dropdown-sair">Sair</button>
        </form>
    </div>
</div>

            <script>
                // pega o botão do avatar e o painel escondido, os dois pelo id
                var botaoAvatar = document.getElementById('perfil-trigger');
                var painelPerfil = document.getElementById('perfil-dropdown');

                // ao clicar no avatar, alterna entre mostrar/esconder o
                // painel. "hidden" é um atributo que ou EXISTE (esconde) ou
                // NÃO existe (mostra); "!painelPerfil.hidden" pega o estado
                // atual e inverte ele
                botaoAvatar.addEventListener('click', function (evento) {
                    // impede que esse clique "vaze" e seja contado TAMBÉM
                    // como um clique "fora do painel" pelo código logo
                    // abaixo (senão o painel abriria e fecharia no mesmo
                    // clique, sem a gente nem ver ele aparecer)
                    evento.stopPropagation();

                    painelPerfil.hidden = !painelPerfil.hidden;
                });

                // clicou em QUALQUER outro lugar da página (fora do avatar e
                // fora do painel)? fecha o painel, se estiver aberto. É
                // assim que menus desse tipo costumam se comportar: abre ao
                // clicar no botão, fecha ao clicar em qualquer outro lugar
                document.addEventListener('click', function (evento) {
                    // .contains() pergunta "esse elemento que foi clicado
                    // está DENTRO do painel?". Se o clique não foi nem no
                    // botão nem dentro do painel, a gente fecha
                    if (!painelPerfil.contains(evento.target) && evento.target !== botaoAvatar) {
                        painelPerfil.hidden = true;
                    }
                });
            </script>
        <?php endif; ?>
    </div>

    <div class="container" style="padding-top: 24px; padding-bottom: 40px;">
        <?php foreach (take_flash() as $mensagem): ?>
            <div class="flash-<?= h($mensagem['type']) ?>">
                <?= h($mensagem['message']) ?>
            </div>
        <?php endforeach; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </div>
    <!-- fecha o .container que a render_header() deixou aberto -->
    <?php
    // current_user() é "static" por dentro (só busca no banco uma vez por
    // página), então chamar ela de novo aqui não tem custo nenhum. Se tiver
    // alguém logado e essa pessoa ainda não aceitou os termos (coluna
    // terms_accepted = 0), desenha o modal bloqueante por cima da página
    $usuarioLogado = current_user();
    // o tutorial só aparece se os Termos já foram aceitos, assim os dois 
    // modais bloqueantes nunca se empilham, um de cada vez.
    if ($usuarioLogado !== null && (int) ($usuarioLogado['terms_accepted'] ?? 1) === 0) {
        render_terms_modal();
    } elseif ($usuarioLogado !== null && (int) ($usuarioLogado['tutorial_seen'] ?? 1) === 0) {
        render_tutorial_modal();
    }
    ?>
    </body>
    </html>
    <?php
}

/**
 * Modal bloqueante de aceite dos Termos de Uso e Política de Privacidade.
 * Só é desenhado quando users.terms_accepted = 0 (ver render_footer()
 * acima). Diferente de um modal comum, este NÃO tem botão de fechar nem
 * "x" — é obrigatório aceitar pra continuar usando o site.
 */
function render_terms_modal(): void
{
    ?>
    <div class="terms-overlay"></div>
    <div class="terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-modal-title">
        <h2 id="terms-modal-title">Termos de Uso e Política de Privacidade</h2>
        <p class="text-muted">Antes de continuar, leia e aceite os termos abaixo. Isso é necessário apenas uma vez.</p>

        <div class="terms-text" tabindex="0">
            <h3>Termo de Uso do Paciente – Vital Clinic</h3>

            <h4>1. Aceitação</h4>
            <p>Ao realizar seu cadastro e utilizar a plataforma Vital Clinic, o usuário declara que leu, compreendeu e concorda integralmente com os termos e condições aqui estabelecidos.</p>

            <h4>2. Serviços disponibilizados</h4>
            <ul>
                <li>Cadastro de usuário;</li>
                <li>Pesquisa de médicos e especialidades;</li>
                <li>Consulta de horários disponíveis;</li>
                <li>Agendamento de consultas;</li>
                <li>Cancelamento de consultas;</li>
                <li>Recebimento de notificações e lembretes.</li>
            </ul>

            <h4>3. Direitos do paciente</h4>
            <ul>
                <li>Acessar sua conta e informações cadastradas;</li>
                <li>Corrigir seus dados pessoais;</li>
                <li>Solicitar exclusão da conta;</li>
                <li>Solicitar informações sobre seus dados armazenados;</li>
                <li>Utilizar os recursos disponibilizados pela plataforma.</li>
            </ul>

            <h4>4. Deveres do paciente</h4>
            <ul>
                <li>Fornecer informações verdadeiras e atualizadas;</li>
                <li>Manter seus dados cadastrais corretos;</li>
                <li>Preservar o sigilo de sua senha de acesso;</li>
                <li>Comparecer às consultas agendadas;</li>
                <li>Cancelar consultas quando não puder comparecer.</li>
            </ul>

            <h4>5. Condutas proibidas</h4>
            <p>É expressamente proibido ao usuário: utilizar informações falsas; compartilhar contas de acesso com terceiros; praticar fraudes ou atos ilícitos; tentar invadir ou comprometer a segurança da plataforma; ou utilizar o sistema para finalidades ilegais.</p>

            <h4>6. Cancelamento de conta</h4>
            <p>A conta pode ser suspensa ou excluída em caso de descumprimento deste Termo. O paciente pode solicitar o encerramento da conta a qualquer momento, em conformidade com a LGPD. Ainda assim, o histórico médico e os dados clínicos gerados pelos atendimentos são retidos pelas clínicas/médicos pelo período exigido por lei e pelo CFM, sendo excluídos somente após esse prazo.</p>

            <h4>7. Limitação de responsabilidade</h4>
            <p>A Vital Clinic atua exclusivamente como intermediadora do agendamento de consultas. A plataforma não se responsabiliza por diagnósticos, tratamentos, prescrições ou condutas médicas dos profissionais cadastrados.</p>

            <h4>8. Vigência</h4>
            <p>Este Termo permanece válido enquanto o usuário mantiver cadastro ativo na plataforma.</p>

            <h4>9. Aceite</h4>
            <p>Ao utilizar a plataforma, o paciente declara estar de acordo com todas as disposições deste Termo de Uso.</p>

            <h3>Política de Privacidade e Proteção de Dados (LGPD)</h3>

            <h4>1. Dados coletados</h4>
            <p>Nome completo, CPF, data de nascimento, telefone, e-mail e histórico de agendamentos.</p>

            <h4>2. Finalidades do tratamento</h4>
            <p>Cadastro de usuários; agendamento e confirmação de consultas; envio de notificações; atendimento ao usuário; cumprimento de obrigações legais.</p>

            <h4>3. Compartilhamento de dados</h4>
            <p>Os dados podem ser compartilhados entre pacientes e clínicas, entre pacientes e médicos, com fornecedores tecnológicos, ou mediante obrigação legal.</p>

            <h4>4. Segurança</h4>
            <p>A Vital Clinic adota medidas técnicas e administrativas para proteger os dados contra acesso não autorizado, perda, vazamento e alteração indevida.</p>

            <h4>5. Direitos dos titulares</h4>
            <p>O usuário pode solicitar confirmação do tratamento, acesso aos dados, correção, portabilidade, anonimização, exclusão e revogação do consentimento.</p>

            <h4>6. Canal LGPD</h4>
            <p>Solicitações relacionadas à proteção de dados podem ser encaminhadas para: <strong>privacidade@vitalclinic.com</strong></p>

            <h4>7. Alterações</h4>
            <p>Esta Política pode ser atualizada a qualquer momento para adequação legal ou melhoria dos serviços.</p>

            <p class="text-muted">Versão 1.0 – Vital Clinic</p>
        </div>

        <form method="post" action="<?= h(app_url()) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="aceitar_termos">
            <input type="hidden" name="agree" value="0" id="terms-agree-value">
            <label class="terms-checkbox">
                <input type="checkbox" id="terms-checkbox">
                Li e aceito os Termos de Uso e a Política de Privacidade.
            </label>
            <button type="submit" class="btn btn-primary" id="terms-submit" disabled>Prosseguir</button>
        </form>
    </div>

    <script>
        // botão "Prosseguir" só destrava depois que a pessoa marca a caixa.
        // O input escondido "agree" é o que o PHP confere do outro lado
        // (post_value('agree') !== '1'), então precisa ficar sincronizado
        // com o estado do checkbox de verdade
        var termosCheckbox = document.getElementById('terms-checkbox');
        var termosAgreeValue = document.getElementById('terms-agree-value');
        var termosBotao = document.getElementById('terms-submit');

        termosCheckbox.addEventListener('change', function () {
            termosBotao.disabled = !termosCheckbox.checked;
            termosAgreeValue.value = termosCheckbox.checked ? '1' : '0';
        });
    </script>
    <?php
}

/**
 * Tour guiado de primeiro acesso: 1 passo de boas-vindas (centralizado) +
 * 1 passo pra cada item do menu de cima, apontando com um destaque
 * (highlight) + balão de texto. Só é desenhado quando
 * users.tutorial_seen = 0 (ver render_footer() acima).
 */
function render_tutorial_modal(): void
{
    // 'target' é a mesma chave usada no array $links da render_header()
    // (dashboard, consultas, agendar...), o JavaScript usa isso pra achar
    // o link certo no menu e desenhar o destaque em cima dele. O primeiro
    // passo não tem 'target' (fica centralizado na tela, sem apontar pra nada)
    $passos = [
        [
            'target' => null,
            'titulo' => 'Bem-vindo(a) à Vital Clinic!',
            'texto' => 'Vamos te mostrar rapidinho os principais pontos do site. Use "Próximo" para avançar.',
        ],
        [
            'target' => 'dashboard',
            'titulo' => 'Sua tela inicial',
            'texto' => 'Aqui em "Início" você vê sua próxima consulta, busca médicos e conhece as clínicas parceiras.',
        ],
        [
            'target' => 'consultas',
            'titulo' => 'Suas consultas marcadas',
            'texto' => 'Aqui em "Consultas" você acompanha e cancela suas consultas futuras.',
        ],
        [
            'target' => 'agendar',
            'titulo' => 'Marque uma nova consulta',
            'texto' => 'Aqui em "Agendar" você escolhe a clínica, o médico e o melhor horário pra você.',
        ],
        [
            'target' => 'historico',
            'titulo' => 'Seu histórico completo',
            'texto' => 'Aqui em "Histórico" você vê consultas já realizadas, canceladas ou ausências.',
        ],
        [
            'target' => 'notificacoes',
            'titulo' => 'Fique por dentro',
            'texto' => 'Aqui em "Notificações" você recebe avisos de confirmação, lembretes e cancelamentos.',
        ],
    ];
    ?>
    <div class="tour-overlay" id="tour-overlay" hidden></div>
    <div class="tour-highlight" id="tour-highlight" hidden></div>
    <div class="tour-tooltip card" id="tour-tooltip" hidden>
        <div class="tour-tooltip-topo">
            <h2 id="tour-titulo" style="margin:0;"></h2>
            <button type="button" class="modal-close" id="tour-pular-x" aria-label="Pular tutorial">&times;</button>
        </div>
        <p id="tour-texto" class="text-muted"></p>

        <div class="tutorial-dots" id="tour-pontos">
            <?php foreach ($passos as $indice => $passo): ?>
                <span class="tutorial-dot<?= $indice === 0 ? ' is-active' : '' ?>"></span>
            <?php endforeach; ?>
        </div>

        <div class="tutorial-actions">
            <button type="button" class="btn btn-outline btn-sm" id="tour-pular">Pular</button>
            <div class="tutorial-nav-buttons">
                <button type="button" class="btn btn-outline btn-sm" id="tour-anterior" disabled>Anterior</button>
                <button type="button" class="btn btn-primary btn-sm" id="tour-proximo">Próximo</button>
            </div>
        </div>
    </div>

    <script type="application/json" id="tour-passos-dados"><?= json_encode($passos, JSON_UNESCAPED_UNICODE) ?></script>
    <script>
        (function () {
            var passos = JSON.parse(document.getElementById('tour-passos-dados').textContent);
            var overlay = document.getElementById('tour-overlay');
            var destaque = document.getElementById('tour-highlight');
            var balao = document.getElementById('tour-tooltip');
            var elTitulo = document.getElementById('tour-titulo');
            var elTexto = document.getElementById('tour-texto');
            var pontos = document.querySelectorAll('#tour-pontos .tutorial-dot');
            var botaoAnterior = document.getElementById('tour-anterior');
            var botaoProximo = document.getElementById('tour-proximo');
            var nav = document.querySelector('.topbar-nav');
            var passoAtual = 0;

            // acha, dentro do menu de cima, o link cujo href contenha
            // "page=dashboard" (ou "page=consultas", etc), é a mesma
            // "chave" usada no array $links da render_header()
            function acharAlvo(chaveDaPagina) {
                if (!chaveDaPagina || !nav) {
                    return null;
                }
                return nav.querySelector('a[href*="page=' + chaveDaPagina + '"]');
            }

            // passo sem alvo (o de boas-vindas): esconde o destaque e
            // centraliza o balão bem no meio da tela
            function posicionarCentralizado() {
                destaque.hidden = true;
                balao.style.transform = 'translate(-50%, -50%)';
                balao.style.top = '50%';
                balao.style.left = '50%';
            }

            // passo COM alvo: desenha o retângulo de destaque em cima do
            // link de verdade, e posiciona o balão logo abaixo dele (ou
            // acima, se não sobrar espaço embaixo)
            function posicionarPertoDoAlvo(alvo) {
                var retangulo = alvo.getBoundingClientRect();
                var respiro = 6;

                destaque.hidden = false;
                destaque.style.top = (retangulo.top - respiro) + 'px';
                destaque.style.left = (retangulo.left - respiro) + 'px';
                destaque.style.width = (retangulo.width + respiro * 2) + 'px';
                destaque.style.height = (retangulo.height + respiro * 2) + 'px';

                var alturaBalao = balao.offsetHeight || 220;
                var espacoEmbaixo = window.innerHeight - retangulo.bottom;
                var topo = espacoEmbaixo > alturaBalao + 24
                    ? retangulo.bottom + 14
                    : Math.max(14, retangulo.top - alturaBalao - 14);
                var esquerda = Math.min(
                    Math.max(14, retangulo.left),
                    window.innerWidth - balao.offsetWidth - 14
                );

                balao.style.transform = 'none';
                balao.style.top = topo + 'px';
                balao.style.left = esquerda + 'px';
            }

            function reposicionar() {
                var passo = passos[passoAtual];
                var alvo = acharAlvo(passo.target);
                if (alvo) {
                    alvo.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                    posicionarPertoDoAlvo(alvo);
                } else {
                    posicionarCentralizado();
                }
            }

            function mostrarPasso(indice) {
                passoAtual = indice;
                var passo = passos[indice];

                elTitulo.textContent = passo.titulo;
                elTexto.textContent = passo.texto;
                pontos.forEach(function (ponto, i) {
                    ponto.classList.toggle('is-active', i === indice);
                });
                botaoAnterior.disabled = indice === 0;
                botaoProximo.textContent = indice === passos.length - 1 ? 'Concluir' : 'Próximo';

                // espera o layout "assentar" antes de medir a posição real
                // do elemento na tela
                requestAnimationFrame(reposicionar);
            }

            function concluir() {
                // avisa o servidor em segundo plano, sem esperar resposta
                // nem recarregar a página, é só pra gravar tutorial_seen=1
                var dados = new FormData();
                dados.append('action', 'marcar_tutorial_visto');
                dados.append('csrf_token', '<?= h(csrf_token()) ?>');
                fetch(window.location.href, { method: 'POST', body: dados });

                overlay.hidden = true;
                destaque.hidden = true;
                balao.hidden = true;
                window.removeEventListener('resize', reposicionar);
                window.removeEventListener('scroll', reposicionar, true);
            }

            botaoProximo.addEventListener('click', function () {
                if (passoAtual === passos.length - 1) {
                    concluir();
                    return;
                }
                mostrarPasso(passoAtual + 1);
            });

            botaoAnterior.addEventListener('click', function () {
                if (passoAtual > 0) {
                    mostrarPasso(passoAtual - 1);
                }
            });

            document.getElementById('tour-pular').addEventListener('click', concluir);
            document.getElementById('tour-pular-x').addEventListener('click', concluir);

            window.addEventListener('resize', reposicionar);
            window.addEventListener('scroll', reposicionar, true);

            overlay.hidden = false;
            balao.hidden = false;
            mostrarPasso(0);
        })();
    </script>
    <?php
}