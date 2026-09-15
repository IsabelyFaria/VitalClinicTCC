<?php
declare(strict_types=1);
//   liga o modo "rígido" de tipos do PHP. Sem isso, o PHP às vezes converte um tipo de
//   dado pra outro sozinho, escondendo bugs. Com strict_types=1, se uma função espera um
//   tipo e chega outro incompatível, dá erro na hora, mais fácil de perceber o problema
//   exemplo: string "5" e int 5 são diferentes.

ob_start();
//   "liga" um buffer de saída: tudo que a gente mandar imprimir a partir daqui fica
//   guardado numa "gaveta" em vez de ir direto pra tela. Isso é o que permite a gente
//   usar redirect() (header('Location: ...')) depois de já ter começado a desenhar
//   alguma coisa, sem isso, tentar redirecionar depois de já ter impresso algo daria erro

session_name('vctcc_patient_session');
//   dá um nome próprio pro cookie de sessão desse site. Se um dia esse site rodar no
//   mesmo domínio que o VitalClinic-SITE (o de admin/médico), os dois teriam por padrão
//   o mesmo nome de cookie (PHPSESSID), e logar num derrubaria a sessão do outro,
//   com um nome diferente, cada site guarda seu próprio "crachá" de login, sem conflito

session_start();
//   liga de vez a sessão. É essa linha que faz o superglobal $_SESSION realmente
//   funcionar daqui pra baixo (guardar e ler dados entre uma página e outra)

require __DIR__ . '/app/helpers.php';
//   importa o arquivo de funções utilitárias pra dentro desse script. __DIR__ é uma
//   "palavra mágica" do PHP que sempre aponta pra pasta onde esse arquivo (index.php)
//   está, não importa de onde ele foi chamado, assim o caminho nunca quebra.
//   A partir daqui já dá pra usar config(), h(), flash(), redirect(), csrf_field(), etc.

date_default_timezone_set(config('timezone') ?: 'America/Sao_Paulo');
//   configura o fuso horário que o PHP vai usar em qualquer cálculo de data/hora daqui
//   pra frente (tipo o now_sql() do helpers.php). config('timezone') busca esse valor
//   lá do config.php; o "?:" é o operador Elvis, se config('timezone') vier vazio/null
//   por algum motivo, usa 'America/Sao_Paulo' como plano B, em vez de quebrar

require __DIR__ . '/app/db.php';
//   importa a função db() (conexão PDO com o banco) e db_transaction()

require __DIR__ . '/app/repository.php';
//   importa as funções que leem/gravam no banco (repository_find, repository_append...).
//   Precisa vir antes de auth.php, porque auth.php usa funções de dentro desse arquivo

require __DIR__ . '/app/auth.php';
//   importa current_user(), login_user(), logout_user(), register_patient()

require __DIR__ . '/pages/layout.php';
//   NOVO: importa render_header() e render_footer(). Precisa vir depois de helpers.php
//   (usa h(), asset_url(), app_url(), csrf_field(), take_flash() de lá) e antes de
//   login.php (que agora usa render_header()/render_footer() por dentro)

require __DIR__ . '/pages/auth/login.php';
//   importa render_login(), a função que desenha a tela de login

require __DIR__ . '/pages/auth/registro.php';
//   importa render_registro(), a função que desenha a tela cadastro

require __DIR__ . '/pages/paciente/dashboard.php';
// importa render_dashboard()

require __DIR__ . '/pages/paciente/consultas.php';
// importa consultas.php

require __DIR__ . '/pages/paciente/agendar.php';
// importa render_agendar_medicos() e render_agendar_horarios()

require __DIR__ . '/pages/paciente/historico.php';
// importa render_historico()

require __DIR__ . '/pages/paciente/perfil.php';
// importa render_perfil()

require __DIR__ . '/pages/paciente/sobre.php';
// importa render_sobre()

require __DIR__ . '/pages/paciente/notificacoes.php';
// importa render_notificacoes()

$page = $_GET['page'] ?? 'dashboard';
//   $_GET pega valores que vieram pela URL (tipo index.php?page=consultas). Se ninguém
//   mandou nenhum "page" na URL, o "??" usa 'dashboard' como página padrão

// FORMULÁRIOS DE TELAS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //   $_SERVER['REQUEST_METHOD'] diz como o navegador pediu essa página: 'GET' (só
    //   visitando/clicando num link) ou 'POST' (enviando um formulário). Só entra
    //   nesse bloco inteiro quando foi um POST, ou seja, quando algum formulário
    //   (login ou logout) acabou de ser enviado

    verify_csrf();
    //   confere se o token de segurança que veio junto do formulário bate com o que
    //   está guardado na sessão. Se não bater, essa função já redireciona e para tudo
    //   sozinha (vimos isso no helpers.php), então, se o código continuar depois
    //   dessa linha, é porque o token estava correto

    $action = $_POST['action'] ?? '';
    //   pega o valor do campo escondido <input type="hidden" name="action" ...> que
    //   cada formulário manda, dizendo qual ação executar. Se não vier nada, vira
    //   texto vazio (não deveria acontecer, mas evita erro se acontecer)

    if ($action === 'login') {
        //   só entra aqui se o formulário enviado foi o de login (o action="login")

        if (login_user(post_value('email'), (string) ($_POST['password'] ?? ''))) {
            //   chama a função que já testamos no auth.php, passando o e-mail (lido
            //   com post_value(), que já tira espaço em branco) e a senha (lida direto
            //   de $_POST, convertida pra string por garantia). Se login_user()
            //   devolver true, entra no "if"; se devolver false, cai no "else"

            flash('success', 'Login realizado!');
            //   guarda uma mensagem de sucesso na sessão, pra aparecer na próxima
            //   página (a mensagem "sobrevive" ao redirecionamento)

            redirect(['page' => 'dashboard']);
            //   manda o navegador pra index.php?page=dashboard. Essa função já faz
            //   header('Location: ...') + exit por dentro, então nada depois dela roda
        } else {
            flash('error', 'E-mail ou senha inválidos.');
            redirect(['page' => 'login']);
            //   se a senha/e-mail estavam errados: guarda mensagem de erro e manda de
            //   volta pra tela de login (pra pessoa tentar de novo)
        }
    }

    if ($action === 'registro') {
        //  só entra aqui se o formulário enviado foi o de cadastro

        try {
            //  "try" significa "tenta rodar esse pedaço de código, mas fica de olho se
            //   der algum erro". Usamos isso porque register_patient() pode lançar uma
            //   RuntimeException (lembra? "throw new RuntimeException(...)") se algum
            //   dado estiver inválido, e a gente quer capturar esse erro, em vez de
            //   deixar o site quebrar com uma tela de erro feia

            register_patient($_POST);
            //   passamos o $_POST inteiro direto. Isso funciona porque register_patient()
            //   só vai procurar as chaves que ELE precisa ($data['name'], $data['email']...)
            //   e ignora qualquer chave a mais que vier junto (tipo 'action' e 'csrf_token')

            login_user(post_value('email'), (string) ($_POST['password'] ?? ''));
            //   se o cadastro deu certo (não caiu no catch), já loga a pessoa
            //   automaticamente, chamando a mesma função que usamos no login, assim ela
            //   não precisa digitar tudo de novo numa segunda tela

            flash('success', 'Conta criada com sucesso!');
            redirect(['page' => 'dashboard']);
            //   manda direto pro dashboard, já logada

        } catch (RuntimeException $e) {
            //  "catch" significa "se dentro do try acima algo der errado (uma
            //   RuntimeException), executa isso aqui em vez de deixar o site quebrar".
            //   $e é o "objeto" do erro que foi lançado lá dentro do register_patient()

            flash('error', $e->getMessage());
            //   $e->getMessage() pega o TEXTO do erro que foi passado lá no
            //   "throw new RuntimeException('Informe um e-mail válido.')", por exemplo,
            //   assim a pessoa vê exatamente qual foi o problema (nome vazio, e-mail
            //   inválido, senha curta, e-mail já cadastrado...)

            redirect(['page' => 'registro']);
            //  manda de volta pro formulário de cadastro, pra tentar de novo
        }
    }

    if ($action === 'logout') {
        //   só entra aqui se o formulário enviado foi o de logout (o action="logout",
        //   que agora vem do formulário "Sair" dentro da render_header())

        logout_user();
        //   destrói a sessão da pessoa (função que já vimos no auth.php)

        flash('success', 'Você saiu.');
        redirect(['page' => 'login']);
        //   guarda mensagem e manda de volta pra tela de login
    }

    if ($action === 'cancelar_consulta') {
        // só entra aqui se o formulário enviado foi o do botão "Cancelar" de uma consulta

        // não dá pra usar a variável $user aqui embaixo, porque ela só é criada
        // mais tarde nesse arquivo (na linha "$user = current_user();"). Então
        // perguntamos de novo, direto, quem está logado nesse exato momento
        $usuarioLogado = current_user();

        try {
            cancel_appointment_patient((int) ($_POST['consulta_id'] ?? 0), (int) $usuarioLogado['id']);
            // (int) ($_POST['consulta_id'] ?? 0) -> pega o id da consulta que veio
            // escondido no formulário; se por algum motivo não vier nada, usa 0 (que
            // não existe como id de verdade, então a função vai barrar dizendo
            // "consulta não encontrada")

            flash('success', 'Consulta cancelada com sucesso.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            // pega o texto exato do motivo do cancelamento ter sido negado (não
            // encontrada, não é sua, já passou, faltou menos de 24h...) e mostra
            // essa mensagem específica pra pessoa
        }

        redirect(['page' => 'consultas']);
        // depois de cancelar (ou tentar), volta pra tela de "Minhas Consultas" pra
        // pessoa ver o resultado
    }

    if ($action === 'marcar_consulta') {
        // não dá pra usar $user aqui (mesmo motivo do cancelar_consulta: ele só
        // é criado mais tarde no arquivo), então perguntamos de novo, na hora
        $usuarioLogado = current_user();

        try {
            book_appointment_patient((int) $usuarioLogado['id'], (int) ($_POST['slot_id'] ?? 0));

            flash('success', 'Consulta marcada com sucesso!');
            redirect(['page' => 'consultas']);
            // depois de marcar com sucesso, manda pra "Minhas Consultas", pra
            // pessoa já ver a consulta nova na lista
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect(['page' => 'agendar', 'medico' => (int) ($_POST['medico_id'] ?? 0), 'data' => (string) ($_POST['data'] ?? ''), 'mes' => (string) ($_POST['mes'] ?? '')]);
            // se deu erro (por exemplo, alguém foi mais rápido e pegou esse
            // horário primeiro), volta pra tela de horários DESSE MESMO médico
            // (não pra lista geral), usando o medico_id que também veio escondido
            // no formulário, assim a pessoa não perde o contexto de quem ela
            // estava tentando marcar, e preserva a data/mês também
        }
    }

    if ($action === 'atualizar_perfil') {
        // de novo, current_user() direto aqui, porque $user só existe mais tarde
        $usuarioLogado = current_user();

        try {
            update_patient_profile((int) $usuarioLogado['id'], $_POST);
            // passamos o $_POST inteiro de novo, igual fizemos no cadastro, a
            // função só vai pegar as chaves que ela precisa (name, email, phone...)

            flash('success', 'Perfil atualizado com sucesso!');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }

        redirect(['page' => 'perfil']);
    }

    if ($action === 'aceitar_termos') {
        // de novo, current_user() direto aqui (mesmo motivo dos outros
        // "action" acima: $user só existe mais tarde nesse arquivo)
        $usuarioLogado = current_user();

        if (post_value('agree') !== '1') {
            // segurança extra: mesmo o botão "Prosseguir" já vindo desabilitado
            // no HTML até marcar a caixa, alguém poderia tentar mandar esse POST
            // direto, sem passar pelo checkbox. Aqui a gente barra de novo, no
            // servidor, que é onde a segurança de verdade precisa estar
            flash('error', 'É necessário marcar a caixa de aceite para continuar.');
            redirect(['page' => $_GET['page'] ?? 'dashboard']);
        }

        accept_terms((int) $usuarioLogado['id']);
        // grava terms_accepted = 1 pra esse paciente. A partir de agora o
        // render_terms_modal()(no layout.php) não desenha
        // mais o modal pra ele

        redirect(['page' => $_GET['page'] ?? 'dashboard']);
        // volta pra MESMA página que a pessoa estava vendo quando aceitou,
        // em vez de forçar ir pro dashboard
    }

    if ($action === 'marcar_tutorial_visto') {
        $usuarioLogado = current_user();
        mark_tutorial_seen((int) $usuarioLogado['id']);
        redirect(['page' => $_GET['page'] ?? 'dashboard']);
        // esse aqui o JavaScript vai chamar sozinho, em segundo plano
        // (via fetch), sem a pessoa notar, não precisa de mensagem de
        // flash nenhuma, só gravar que ela já viu o tutorial
    }


}
//   fim do bloco "só roda se foi POST". Se o pedido era um GET normal (só visitando
//   uma página), esse bloco inteiro é pulado

$user = current_user();
//   pergunta "quem está logado agora?", roda em TODA visita à página (GET ou depois
//   de um POST que não deu redirect), não só dentro do bloco de cima

$paginasPublicas = ['login', 'registro'];
//   antes só existia a página 'login' como exceção à regra de "precisa estar
//   logado". Agora 'registro' também precisa ser uma exceção, afinal, é justamente
//   pra quem ainda não tem conta. Por isso viraram uma lista, em vez de comparar
//   direto com o texto 'login' feito antes

if (!$user && !in_array($page, $paginasPublicas, true)) {
    $page = 'login';
}
//   in_array($page, $paginasPublicas, true) confere se $page está dentro dessa lista.
//   O terceiro parâmetro "true" liga a comparação "estrita" (confere tipo também, não
//   só o valor), boa prática, mesma ideia do "===" que já usamos bastante.
//   Tradução: "se ninguém está logado E a página pedida NÃO é uma das públicas,
//   força ir pro login"

if ($user && in_array($page, $paginasPublicas, true)) {
    $page = 'dashboard';
}
//   o contrário: se JÁ está logado e tenta ver login OU registro, manda pro dashboard
//   (não faz sentido uma pessoa logada preencher cadastro de novo)

if ($page === 'login') {
    render_login();
    //   se a página final é 'login', é só chamar essa função, ela cuida de tudo
    //   sozinha agora (chama render_header(null, 'login') no começo, desenha o
    //   formulário, chama render_footer() no final). O index.php não precisa mais
    //   saber como a tela de login é desenhada, só que ela existe
} elseif ($page === 'registro'){
    render_registro();
     //   mais uma opção na "escada" de decisão, do mesmo jeito que o login
} elseif ($page === 'dashboard'){
   $proximaConsulta = next_appointment_for_patient((int) $user['id']);

    $resultadoBusca = search_doctors_and_clinics('');
    // busca vazia ('') traz TODOS os médicos ativos e TODAS as clínicas com
    // pelo menos 1 médico ativo, sem filtrar nada ainda. É esse conjunto
    // completo que vai ficar escondido no HTML da Home, esperando o
    // JavaScript da busca ao vivo filtrar ele conforme a pessoa digita,
    // a mesma ideia que já usamos no Agendar

    // pega até 5 clínicas (pra não lotar a Home) e todas as especialidades
    // que têm médico de verdade, pra montar as pílulas e os cards
    $clinicasHome = array_slice(active_clinics_with_doctor_count(), 0, 5);
    $especialidadesHome = active_specialties();

    render_dashboard($user, $proximaConsulta, $resultadoBusca, $clinicasHome, $especialidadesHome);

} elseif ($page === 'agendar') {
    // pega os 3 possíveis "filtros" que podem vir na URL: qual médico, qual
    // clínica, e o texto de busca. Todos são opcionais (podem não vir)
    $medicoId = isset($_GET['medico']) ? (int) $_GET['medico'] : null;
    $clinicaId = isset($_GET['clinica']) ? (int) $_GET['clinica'] : null;
    $busca = isset($_GET['busca']) && trim((string) $_GET['busca']) !== '' ? trim((string) $_GET['busca']) : null;
    // essa linha da $busca é um pouco mais cheia: além de conferir se
    // "busca" veio na URL, já tira os espaços em branco (trim) e transforma
    // uma busca vazia (a pessoa clicou em "Buscar" sem digitar nada) em
    // null, é mais fácil de checar "if ($busca)" depois do que "if
    // ($busca !== '')"

    if ($medicoId) {
        // TELA 3: horários de um médico específico já escolhido
        $medico = find_doctor_details($medicoId);

        if (!$medico) {
            flash('error', 'Médico não encontrado.');
            redirect(['page' => 'agendar']);
        }

        $horarios = available_slots_for_doctor($medicoId);
        $dataSelecionada = isset($_GET['data']) ? (string) $_GET['data'] : null;
        $mesSelecionado = isset($_GET['mes']) ? (string) $_GET['mes'] : null;

        render_agendar_horarios($user, $medico, $horarios, $dataSelecionada, $mesSelecionado);

    } elseif ($clinicaId) {
        // TELA 2: lista de médicos de UMA clínica específica já escolhida
        $clinica = find_clinic_details($clinicaId);

        if (!$clinica) {
            flash('error', 'Clínica não encontrada.');
            redirect(['page' => 'agendar']);
        }

        $medicos = active_doctors_with_details($clinicaId, $busca);
        render_agendar_medicos($user, $clinica, $medicos, $busca);

    } else {
        // TELA 1: lista de clínicas OU lista de médicos (duas abas)
        $clinicas = active_clinics_with_doctor_count($busca);
        $especialidades = active_specialties();
        $todosOsMedicos = search_doctors_and_clinics('')['medicos'];
        // busca vazia ('') traz TODOS os médicos ativos, de QUALQUER
        // clínica, é a lista completa que vai alimentar a aba "Médicos"
        // dessa tela, escondida até a pessoa clicar nessa aba

        // se a pessoa clicou numa pílula de especialidade lá na Home, ela
        // chega aqui com "?especialidade=Pediatria" na URL, por exemplo
        $especialidadeInicial = isset($_GET['especialidade']) && trim((string) $_GET['especialidade']) !== ''
        ? trim((string) $_GET['especialidade'])
        : null;

        render_agendar_clinicas($user, $clinicas, $busca, $especialidades, $todosOsMedicos, $especialidadeInicial);
    }
} elseif ($page === 'historico') {
    // reaproveitando a mesma função de sempre, só que pedindo os status de
    // consultas já ENCERRADAS (completed = aconteceu, cancelled = cancelada,
    // no_show = paciente faltou), em vez das que ainda estão por vir
    $historico = appointments_for_patient((int) $user['id'], ['completed', 'cancelled', 'no_show'], 'DESC', false, 'a.updated_at');
    render_historico($user, $historico);

} elseif ($page === 'consultas') {
    $consultas = appointments_for_patient((int) $user['id'], ['pending', 'confirmed'], 'ASC');
    render_consultas($user, $consultas);

} elseif ($page === 'notificacoes') {
    $notificacoes = notifications_for_patient((int) $user['id']);
    render_notificacoes($user, $notificacoes);

    // só marca como lidas DEPOIS de já termos desenhado a tela, assim, a
    // lista que a pessoa está vendo agora ainda mostra corretamente quais
    // eram novas. Só na PRÓXIMA visita que elas vão aparecer sem o selinho
    mark_notifications_as_read((int) $user['id']);

} elseif ($page === 'perfil') {
    render_perfil($user);
    
} elseif ($page === 'sobre') {
    render_sobre($user);
    // página só de leitura, não precisa buscar nada no banco antes,
    // por isso passamos $user direto pra função, sem nenhuma consulta

} else {
    //   pega qualquer página que a gente ainda não construiu (consultas,
    //   agendar, historico, perfil...), em vez de dar erro ou tela em branco,
    //   manda educadamente de volta pro dashboard
    redirect(['page' => 'dashboard']);
}