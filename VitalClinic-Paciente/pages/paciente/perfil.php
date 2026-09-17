<?php

function render_perfil(array $paciente): void
{
    render_header($paciente, 'perfil');

    // pega a primeira letra do nome do paciente, é a mesma "receita"
    // que já usamos pra fazer a bolinha do canto superior direito, e
    // agora vai servir de "foto" aqui no Perfil também, no lugar dos
    // ícones de pessoa que tinham no print
    $inicialPaciente = mb_strtoupper(mb_substr($paciente['name'], 0, 1));
    ?>

    <!-- CABEÇALHO DA PÁGINA: bolinha grande + título + subtítulo,
         lado a lado (por isso a div em volta tem a classe que usa flex) -->
    <div class="perfil-cabecalho">
        <div class="perfil-avatar-grande"><?= h($inicialPaciente) ?></div>
        <div>
            <h1 style="margin: 0;">Meu perfil</h1>
            <p class="text-muted" style="margin: 0;">Veja e edite suas informações</p>
        </div>
    </div>

    <div class="card">
        <!-- CABEÇALHO DE DENTRO DO CARD: mesma bolinha (só que um pouco
             menor, por isso tem a classe extra "perfil-avatar-media")
             + título + subtítulo do card -->
        <div class="perfil-card-cabecalho">
            <div class="perfil-avatar-grande perfil-avatar-media"><?= h($inicialPaciente) ?></div>
            <div>
                <h2 style="margin: 0;">Informações pessoais</h2>
                <p class="text-muted" style="margin: 0;">Confira e mantenha seus dados sempre atualizados</p>
            </div>
        </div>

        <form method="post" action="<?= h(app_url()) ?>" onsubmit="return confirm('Tem certeza que deseja fazer essas alterações?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="atualizar_perfil">

            <!-- Nome completo sozinho, ocupando a largura toda -->
            <label class="field-label" for="name">Nome completo *</label>
            <input class="field-input" type="text" id="name" name="name" value="<?= h($paciente['name']) ?>" required>

            <!-- E-mail e Telefone lado a lado: a div "perfil-linha-dupla"
                 usa display:flex pra colocar as duas "perfil-campo" uma do
                 lado da outra, e cada uma delas ocupa metade do espaço
                 (isso é definido lá na CSS) -->
            <div class="perfil-linha-dupla">
                <div class="perfil-campo">
                    <label class="field-label" for="email">E-mail *</label>
                    <input class="field-input" type="email" id="email" name="email" value="<?= h($paciente['email']) ?>" required>
                </div>
                <div class="perfil-campo">
                    <label class="field-label" for="phone">Telefone</label>
                    <input class="field-input" type="text" id="phone" name="phone" value="<?= h($paciente['phone'] ?? '') ?>">
                </div>
            </div>

            <!-- CPF e Data de nascimento lado a lado, mesma ideia de cima -->
            <div class="perfil-linha-dupla">
                <div class="perfil-campo">
                    <label class="field-label" for="document">CPF</label>
                    <input class="field-input" type="text" id="document" name="document" value="<?= h($paciente['document'] ?? '') ?>">
                </div>
                <div class="perfil-campo">
                    <label class="field-label" for="birth_date">Data de nascimento</label>
                    <input class="field-input" type="date" id="birth_date" name="birth_date" value="<?= h($paciente['birth_date'] ?? '') ?>">
                </div>
            </div>

            <!-- Endereço sozinho de novo, ocupando a largura toda -->
            <label class="field-label" for="address">Endereço</label>
            <input class="field-input" type="text" id="address" name="address" value="<?= h($paciente['address'] ?? '') ?>">

            <!-- linha final com os 2 botões, um em cada ponta (isso é o
                 "justify-content: space-between" lá na CSS).
                 type="reset" é um tipo especial de botão que já existe
                 pronto no HTML: ele devolve todos os campos do formulário
                 pro valor que estava escrito no "value" original (ou
                 seja, desfaz qualquer edição que você tenha feito na tela,
                 sem precisar recarregar a página nem escrever nenhum
                 JavaScript pra isso) -->
            <div class="perfil-botoes">
                <button type="reset" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </div>
        </form>
    </div>
    <?php
    render_footer();
}