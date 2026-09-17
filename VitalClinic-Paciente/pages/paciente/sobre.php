<?php
declare(strict_types=1);
//   mesmo "modo rígido" de tipos que todos os outros arquivos do projeto usam

function render_sobre(array $paciente): void
{
    render_header($paciente, 'sobre');
    //   'sobre' como segundo parâmetro é só o "nome" dessa página pro
    //   render_header() saber se deve marcar algum link do menu como
    //   ativo. Como "Sobre nós" não é um item do menu de cima (só existe
    //   dentro do dropdown do perfil), nenhum link fica destacado, e tá
    //   tudo bem, é o mesmo que já acontece hoje na tela de Perfil
    ?>

    <p class="sobre-eyebrow">VITAL CLINIC</p>
    <h1>Sobre nós</h1>
    <!-- esse "eyebrow" (a palavra em inglês pra esse tipo de textinho
         pequeno acima do título) é uma classe nova, só usada nessa
         página, então não corre o risco de bagunçar nenhum estilo
         existente em outro lugar do site -->

    <div class="card" style="margin-top: 20px;">
        <p style="margin-bottom: 16px;">
            O <strong>Vital Clinic</strong> é uma plataforma de gestão para clínicas médicas,
            pensada para simplificar o dia a dia de pacientes, administradores e médicos:
            agenda, prontuário eletrônico, relatórios e agendamento de consultas, tudo num só lugar.
        </p>

        <p style="margin-bottom: 16px;">
            Este espaço (paciente) é um dos componentes do projeto — administradores e médicos
            têm seu próprio painel, para gerenciar clínicas, agendas e prontuários.
        </p>

        <p class="text-muted" style="margin-bottom: 0;">Vital Clinic v.3.03</p>
        <!-- reaproveito a classe "text-muted" que já existe no CSS
             (a mesma usada, por exemplo, no textinho de baixo do modal de
             Termos de Uso), pra deixar essa linha de versão mais discreta -->
    </div>

    <?php
    render_footer();
}