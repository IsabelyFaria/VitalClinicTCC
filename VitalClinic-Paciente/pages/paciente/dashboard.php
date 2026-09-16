<?php

// render_dashboard() desenha a tela de início do paciente logado

// $resultadoBusca = ['medicos' => [...], 'clinicas' => [...]] (busca da Home)
// $clinicasHome = até 5 clínicas reais, pro carrossel "Clínicas parceiras"
// $especialidadesHome = todas as especialidades que têm médico de verdade
function render_dashboard(array $paciente, ?array $proximaConsulta, array $resultadoBusca, array $clinicasHome, array $especialidadesHome): void
{
    render_header($paciente, 'dashboard');
    ?>
    <h1>Olá, <?= h($paciente['name']) ?>!</h1>
    <p class="text-muted" style="margin-bottom: 20px;">O que você precisa hoje?</p>

<!-- BANNER -->
     <!--
        Esse bloco é o "banner" bonito lá do topo da Home.
        Pensa nele como uma caixa com 4 pedaços lado a lado:
        seta pra esquerda | texto+botão | desenho | seta pra direita
        e, por cima de tudo, flutuando embaixo, uma fileira de bolinhas.
    -->
    <div class="home-banner">
        <!--
            Botão da seta esquerda. type="button" evita que ele tente
            "enviar formulário" (comportamento padrão de botão dentro de
            forms, que aqui a gente não quer). O id é o "nome" que o
            JavaScript vai usar pra achar esse botão específico depois.
            aria-label é um texto que só leitor de tela lê, explicando o
            que o botão faz, já que ele só mostra o símbolo "‹", sem
            nenhuma palavra.
        -->
        <button type="button" class="home-banner-seta home-banner-seta-esq" id="banner-seta-esq" aria-label="Anterior">‹</button>

        <!--
            Essa div é a "coluna do meio": onde fica o texto que troca
            sozinho. Cada elemento aqui dentro tem um id, que funciona
            como uma etiqueta única, é assim que o JavaScript acha
            exatamente ESSE elemento no meio da página inteira pra trocar
            o texto dele depois.
        -->
        <div class="home-banner-conteudo">
            <!-- etiquetinha em cima do título, tipo uma "categoria" do slide -->
            <span class="home-banner-eyebrow" id="banner-eyebrow">Saúde e bem-estar</span>
            <!-- o título grande do banner -->
            <h2 class="home-banner-titulo" id="banner-titulo">Cuide da sua saúde com facilidade</h2>
            <!-- o textinho explicando, embaixo do título -->
            <p class="home-banner-texto" id="banner-texto">Consultas de qualidade, profissionais especializados e todo o cuidado que você merece.</p>
            <!--
                botão/link que leva direto pra tela de Agendar.
                app_url(['page' => 'agendar']) monta sozinho o endereço
                certo (tipo "index.php?page=agendar"), sem a gente ter
                que escrever essa string na mão
            -->
            <a href="<?= h(app_url(['page' => 'agendar'])) ?>" class="btn btn-primary">Agendar agora →</a>
        </div>

        <!--
            aria-hidden="true" avisa pra leitor de tela: "isso aqui é só
            decoração, pode ignorar", porque é um desenho, não uma
            informação real que o paciente precisa ouvir.
        -->
        <div class="home-banner-desenho" aria-hidden="true">
            <!--
                <svg> é como abrir uma "folha de papel quadriculada"
                pra desenhar. viewBox="0 0 200 200" define o tamanho
                dessa folha em unidades imaginárias: começa em (0,0),
                canto superior esquerdo, e vai até (200,200),
                canto inferior direito. width/height (180x180) é o
                tamanho REAL que essa folha vai ocupar na tela, o
                navegador estica ou encolhe o desenho pra caber
                nesse tamanho, mantendo as proporções.
            -->
            <svg viewBox="0 0 200 200" width="180" height="180">

                <!--
                    <circle> desenha um círculo, tipo usar um compasso:
                    - cx="100" cy="100"  -> onde fica a "pontinha fixa"
                      do compasso (o centro do círculo). Como a folha é
                      200x200, o ponto (100,100) é bem no meio dela.
                    - r="90"             -> o "raio": quão longe do
                      centro o lápis do compasso risca. Com folha de
                      200 de largura e raio 90, o círculo quase encosta
                      nas bordas (deixa uma margem de 10 de cada lado).
                    - fill="..."         -> a cor de dentro do círculo
                      (aqui, um azul bem clarinho). Isso forma o
                      "fundo" redondo atrás do coração.
                -->
                <circle cx="100" cy="100" r="90" fill="var(--color-primary-light, #d6f0f3)"></circle>

                <!--
                    <path> é o elemento mais flexível do SVG: em vez de
                    uma forma pronta (como círculo ou retângulo), você
                    dá uma sequência de instruções pra uma "caneta
                    invisível" andar pela folha e desenhar. O atributo
                    "d" (de "data"/desenho) guarda essas instruções,
                    uma atrás da outra. Vamos separar o "d" desse
                    coração em pedaços:

                    1) "M100 60"
                       M = "Move" (mover). Levanta a caneta e pousa ela
                       no ponto (100, 60) SEM desenhar nada ainda, é
                       só posicionar onde o desenho vai começar (bem no
                       meio, um pouco acima do centro da folha).

                    2) "a20 20 0 0 1 40 0"
                       a = "arc" (arco/curva de círculo), em letra
                       minúscula significa que as próximas medidas são
                       RELATIVAS à posição atual (não são coordenadas
                       fixas da folha, e sim "ande tanto a partir daqui").
                       Isso desenha a "bolinha" (lóbulo) direita do
                       coração, terminando 40 unidades mais à direita e
                       na mesma altura de onde começou.

                    3) "c0 25 -40 45 -40 60"
                       c = "curve" (curva suave, sem cantos). Essa parte
                       desenha a descida do lado direito do coração até
                       a pontinha debaixo — é o que dá aquele formato
                       "afunilado" característico de coração, em vez de
                       uma linha reta.

                    4) "c0 -15 -40 -35 -40 -60"
                       Outra curva suave, agora subindo pelo lado
                       esquerdo, voltando na direção de onde o desenho
                       tinha começado.

                    5) "a20 20 0 0 1 40 0"
                       Outro arco, formando a "bolinha" (lóbulo) esquerda
                       do coração, espelhada da primeira.

                    6) "z"
                       Fecha o desenho: puxa uma linha de volta pro
                       ponto inicial (M100 60), fechando a forma pra
                       poder ser toda pintada de uma cor só.

                    - fill="..."  -> pinta esse coração fechado com a cor
                      principal do site (o azul-esverdeado/teal).
                -->
                <path d="M100 60 a20 20 0 0 1 40 0 c0 25 -40 45 -40 60 c0 -15 -40 -35 -40 -60 a20 20 0 0 1 40 0 z" fill="var(--color-primary, #0aa6bd)"></path>

                <!--
                    <polyline> é o mais simples dos três: é uma lista de
                    pontos (x,y) que vão sendo ligados por linhas retas,
                    um atrás do outro, exatamente como um "liga os
                    pontos". Cada par de números em "points" é uma
                    posição na folha:
                    (40,140) -> (65,140) -> (75,120) -> (90,155) ->
                    (100,130) -> (110,140) -> (160,140)
                    Repara que a altura (segundo número) sobe e desce de
                    forma irregular no meio, é isso que faz a linha
                    parecer um "bip" de monitor de batimento cardíaco,
                    em vez de uma linha reta chata.

                    - fill="none"              -> não pinta por dentro
                      (afinal, é só uma linha, não uma forma fechada)
                    - stroke="#ffffff"        -> a cor do traço em si
                      (branco, pra aparecer por cima do coração escuro)
                    - stroke-width="4"         -> a grossura da linha
                    - stroke-linecap="round"   -> as PONTAS da linha
                      ficam arredondadas, em vez de cortadas retas
                    - stroke-linejoin="round"  -> os CANTOS onde a linha
                      muda de direção também ficam arredondados, em vez
                      de pontudos/quadrados
                -->
                <polyline points="40,140 65,140 75,120 90,155 100,130 110,140 160,140" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>
            </svg>
        </div>

        <!-- botão da seta direita, mesma lógica da esquerda -->
        <button type="button" class="home-banner-seta home-banner-seta-dir" id="banner-seta-dir" aria-label="Próximo">›</button>

        <!--
            fileira de bolinhas mostrando qual "slide" (frase) está ativo
            agora. data-slide guarda o número de cada bolinha (0, 1, 2),
            um "data-*" é um jeitinho de guardar uma informação extra
            dentro do próprio HTML, que o JavaScript consegue ler depois.
            A primeira já nasce com a classe "ponto-ativo" porque o
            banner sempre começa mostrando o slide de número 0.
        -->
        <div class="home-banner-pontos" id="banner-pontos">
            <span class="home-banner-ponto ponto-ativo" data-slide="0"></span>
            <span class="home-banner-ponto" data-slide="1"></span>
            <span class="home-banner-ponto" data-slide="2"></span>
        </div>
    </div>

    <!-- BUSCA que já tínhamos: continua igual, só mudou de lugar na página -->
    <div class="search-box" style="margin: 20px 0;">
        <input type="text" id="busca-home" placeholder="Buscar médico, especialidade ou clínica..." autocomplete="off">
    </div>

    <div id="resultados-busca-home" hidden>
        <div id="secao-medicos-home">
            <h3 style="margin-bottom: 10px;">Médicos</h3>
            <div id="lista-medicos-home">
                <?php foreach ($resultadoBusca['medicos'] as $medico): ?>
                    <div class="medico-row" data-nome="<?= h($medico['doctor_name'] . ' ' . $medico['specialty_name']) ?>">
                        <div class="clinica-avatar medico-avatar">
                            <?= h(mb_strtoupper(mb_substr($medico['doctor_name'], 0, 1))) ?>
                        </div>
                        <div class="medico-info">
                            <div class="appointment-doctor"><?= h($medico['doctor_name']) ?></div>
                            <div class="appointment-meta"><?= h($medico['specialty_name']) ?> · <?= h($medico['clinic_name']) ?></div>
                        </div>
                        <a href="<?= h(app_url(['page' => 'agendar', 'medico' => $medico['doctor_id']])) ?>" class="btn btn-primary btn-sm">Agendar</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="secao-clinicas-home" style="margin-top: 20px;">
            <h3 style="margin-bottom: 10px;">Clínicas</h3>
            <div id="lista-clinicas-home" class="clinicas-grid">
                <?php foreach ($resultadoBusca['clinicas'] as $clinica): ?>
                    <div class="appointment-card clinica-card" data-nome="<?= h($clinica['clinic_name']) ?>">
                        <div class="clinica-avatar"><?= h(mb_strtoupper(mb_substr($clinica['clinic_name'], 0, 1))) ?></div>
                        <div class="clinica-card-info">
                            <div class="appointment-doctor"><?= h($clinica['clinic_name']) ?></div>
                            <div class="appointment-meta"><?= h($clinica['clinic_address'] ?: 'Endereço não informado') ?></div>
                            <a href="<?= h(app_url(['page' => 'agendar', 'clinica' => $clinica['clinic_id']])) ?>" class="btn btn-primary btn-sm" style="margin-top: auto; width: 100%; text-align: center;">Ver médicos</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" id="sem-resultado-home" hidden>
            <p>Desculpa, não encontramos nenhum resultado para essa busca.</p>
        </div>
    </div>

    <!-- PÍLULAS DE ESPECIALIDADE: só texto (sem ícone), vêm do banco de
         verdade, e ao clicar já manda pro Agendar filtrado -->
    <?php if (!empty($especialidadesHome)): ?>
        <div class="especialidades-pilulas-home">
            <?php foreach ($especialidadesHome as $especialidade): ?>
                <a class="pilula-especialidade" href="<?= h(app_url(['page' => 'agendar', 'especialidade' => $especialidade['specialty_name']])) ?>">
                    <?= h($especialidade['specialty_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- CLÍNICAS PARCEIRAS: cards reais, sem estrela/avaliação fictícia,
         com selo "Disponível" só quando é verdade -->
    <?php if (!empty($clinicasHome)): ?>
        <div class="clinicas-parceiras-topo">
            <h2 style="margin: 0;">Clínicas parceiras</h2>
            <a href="<?= h(app_url(['page' => 'agendar'])) ?>" class="ver-todas-link">Ver todas →</a>
        </div>

        <div class="clinicas-grid" style="margin-bottom: 24px;">
            <?php foreach ($clinicasHome as $clinica): ?>
                <div class="appointment-card clinica-card">
                    <div class="clinica-avatar"><?= h(mb_strtoupper(mb_substr($clinica['clinic_name'], 0, 1))) ?></div>
                    <div class="clinica-card-info">
                        <div class="appointment-doctor"><?= h($clinica['clinic_name']) ?></div>
                        <div class="appointment-meta"><strong style="color: var(--color-text-dark);"><?= h($clinica['nomes_especialidades']) ?></strong></div>
                        <div class="appointment-meta"><?= h($clinica['clinic_address'] ?: 'Endereço não informado') ?></div>
                        <?php if ((int) $clinica['tem_horario_disponivel'] === 1): ?>
                            <span class="badge-disponivel"><span class="bolinha-verde"></span> Disponível</span>
                        <?php endif; ?>
                        <a href="<?= h(app_url(['page' => 'agendar', 'clinica' => $clinica['clinic_id']])) ?>" class="btn btn-primary btn-sm" style="margin-top: auto; width: 100%; text-align: center;">
                            Agendar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- PRÓXIMAS CONSULTAS: igual já era -->
    <h2 style="margin-bottom: 10px;">Próxima consultas</h2>
    <?php if ($proximaConsulta): ?>
        <div class="appointment-card">
            <div class="clinica-avatar medico-avatar">
                <?= h(mb_strtoupper(mb_substr($proximaConsulta['doctor_name'], 0, 1))) ?>
            </div>
            <div class="clinica-card-info">
                <div class="appointment-doctor">Dr(a). <?= h($proximaConsulta['doctor_name']) ?></div>
                <div class="appointment-meta"><strong style="color: var(--color-text-dark);"><?= h($proximaConsulta['specialty_name']) ?></strong> · <?= h($proximaConsulta['clinic_name']) ?></div>
                <div class="appointment-meta"><?= h(format_datetime($proximaConsulta['slot_start'])) ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <p>Você não tem nenhuma consulta agendada no momento.</p>
        </div>
    <?php endif; ?>

    <script>
        // ---------- BUSCA DA HOME (igual já era, só reorganizado aqui) ----------
        var campoBuscaHome = document.getElementById('busca-home');
        var containerResultados = document.getElementById('resultados-busca-home');
        var secaoMedicos = document.getElementById('secao-medicos-home');
        var secaoClinicas = document.getElementById('secao-clinicas-home');
        var avisoSemResultadoHome = document.getElementById('sem-resultado-home');

        campoBuscaHome.addEventListener('input', function () {
            var termo = campoBuscaHome.value.toLowerCase().trim();

            if (termo === '') {
                containerResultados.hidden = true;
                return;
            }
            containerResultados.hidden = false;

            var visiveisMedicos = filtrarLista('lista-medicos-home', '.medico-row', termo);
            var visiveisClinicas = filtrarLista('lista-clinicas-home', '.clinica-card', termo);

            secaoMedicos.hidden = visiveisMedicos === 0;
            secaoClinicas.hidden = visiveisClinicas === 0;
            avisoSemResultadoHome.hidden = (visiveisMedicos + visiveisClinicas) !== 0;
        });

        function filtrarLista(idLista, seletorCard, termo) {
            var lista = document.getElementById(idLista);
            if (!lista) {
                return 0;
            }
            var cards = lista.querySelectorAll(seletorCard);
            var visiveis = 0;
            cards.forEach(function (card) {
                var nome = card.getAttribute('data-nome').toLowerCase();
                if (nome.includes(termo)) {
                    card.style.display = '';
                    visiveis++;
                } else {
                    card.style.display = 'none';
                }
            });
            return visiveis;
        }

        // ---------- BANNER (troca de frase sozinho + setas + bolinhas) ----------

        // Isso aqui é uma "pilha de fichas": cada ficha é um objeto com
        // 3 informações (eyebrow, titulo, texto). slidesDoBanner é a
        // lista com as 3 fichas que vão se revezar no banner.
        var slidesDoBanner = [
            { eyebrow: 'Saúde e bem-estar', titulo: 'Cuide da sua saúde com facilidade', texto: 'Consultas de qualidade, profissionais especializados e todo o cuidado que você merece.' },
            { eyebrow: 'Praticidade', titulo: 'Agende em poucos cliques', texto: 'Escolha a clínica, o médico e o horário que forem melhores pra você.' },
            { eyebrow: 'Organização', titulo: 'Seu histórico sempre à mão', texto: 'Acompanhe suas consultas passadas e futuras em um só lugar.' }
        ];

        // slideAtual é como um "post-it" marcando em qual ficha da pilha
        // a gente está agora. Começa em 0, ou seja, na primeira ficha.
        var slideAtual = 0;

        // Aqui a gente "guarda numa caixinha" (variável) cada elemento da
        // tela que precisa ser atualizado quando o slide trocar, assim
        // não precisa ficar procurando eles de novo toda vez.
        var elEyebrow = document.getElementById('banner-eyebrow');
        var elTitulo = document.getElementById('banner-titulo');
        var elTexto = document.getElementById('banner-texto');
        // querySelectorAll pega TODOS os elementos que têm essa classe
        // (as 3 bolinhas), e não só um, por isso o nome está no plural
        var pontosDoBanner = document.querySelectorAll('.home-banner-ponto');

        // Essa função é o "coração" do carrossel: ela recebe um número
        // (0, 1 ou 2) e troca o conteúdo do banner pra ficha daquele número
        function mostrarSlide(indice) {
            // primeiro, atualiza o post-it pra apontar pro novo número
            slideAtual = indice;

            // pega a ficha certa dentro da pilha, usando esse número
            // como "posição" (índice) dentro da lista
            var slide = slidesDoBanner[slideAtual];

            // .textContent troca o texto de dentro daquele elemento na
            // tela, pelo texto guardado na ficha
            elEyebrow.textContent = slide.eyebrow;
            elTitulo.textContent = slide.titulo;
            elTexto.textContent = slide.texto;

            // agora passa por CADA bolinha (forEach = "pra cada uma
            // dessas, faça...") e decide se ela fica colorida ou não
            pontosDoBanner.forEach(function (ponto, i) {
                // "i" é a posição dessa bolinha (0, 1 ou 2).
                // classList.toggle(classe, condicao) funciona assim: se a
                // condição for verdadeira, ADICIONA essa classe; se for
                // falsa, REMOVE. Ou seja: só a bolinha cujo número bate
                // com o slide atual ganha "ponto-ativo" (que é o que
                // deixa ela colorida lá no CSS)
                ponto.classList.toggle('ponto-ativo', i === slideAtual);
            });
        }

        // quando clicar na seta direita, chama mostrarSlide pedindo o
        // PRÓXIMO número. O "% slidesDoBanner.length" é uma continha de
        // resto de divisão: quando "slideAtual + 1" passar do total de
        // fichas (3), ele volta pra 0 sozinho, tipo um relógio que,
        // depois de "23 horas", vira "0 hora" em vez de virar "24"
        document.getElementById('banner-seta-dir').addEventListener('click', function () {
            mostrarSlide((slideAtual + 1) % slidesDoBanner.length);
        });

        // mesma ideia da seta direita, só que pro lado contrário (-1).
        // o "+ slidesDoBanner.length" antes do "%" só garante que a
        // conta nunca fique negativa (evita bug quando está na ficha 0 e
        // clica em "voltar")
        document.getElementById('banner-seta-esq').addEventListener('click', function () {
            mostrarSlide((slideAtual - 1 + slidesDoBanner.length) % slidesDoBanner.length);
        });

        // passa por cada bolinha e ensina ela: "quando alguém clicar em
        // você, mostre o slide do SEU número (indice)", assim dá pra
        // pular direto pra qualquer uma das 3 fichas, sem precisar ir
        // clicando nas setas uma de cada vez
        pontosDoBanner.forEach(function (ponto, indice) {
            ponto.addEventListener('click', function () {
                mostrarSlide(indice);
            });
        });

        // setInterval é tipo um alarme que toca de tempos em tempos. Aqui,
        // a cada 6000 milissegundos (6 segundos), ele chama essa função
        // sozinho, avançando pro próximo slide, é isso que faz o banner
        // trocar de frase automaticamente, sem o paciente precisar clicar
        // em nada
        setInterval(function () {
            mostrarSlide((slideAtual + 1) % slidesDoBanner.length);
        }, 6000);
    </script>
    <?php
    render_footer();
}