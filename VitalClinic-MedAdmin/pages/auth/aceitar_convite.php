<?php

/**
 * Tela pública (sem login) aberta a partir do link de convite gerado
 * pelo super admin (?page=accept_invite&token=...). Revalida o token
 * na hora — se estiver inválido/expirado/já usado, mostra uma
 * mensagem em vez do formulário, sem expor qual desses três motivos é
 * (mesma cautela de não vazar detalhe já usada no "esqueci minha
 * senha").
 */
function render_accept_invite(): void
{
    $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
    $invite = $token !== '' ? find_pending_invite($token) : null;
    ?>
    <section class="auth-grid">
        <div class="auth-copy">
            <img class="auth-logo" src="<?= asset_url('assets/brand/vital-clinic-logo.svg') ?>" alt="Vital Clinic">
            <h1>Bem-vindo(a) ao Vital Clinic</h1>
            <?php if ($invite): ?>
                <p>Você foi convidado(a) para ser o administrador de <strong><?= h($invite['clinic_name']) ?></strong>. Defina seu nome e uma senha para ativar o acesso.</p>
            <?php else: ?>
                <p>Este link de convite é inválido, já foi usado, ou expirou. Peça um novo link a quem te convidou.</p>
            <?php endif; ?>
        </div>
        <?php if ($invite): ?>
            <form method="post" class="panel form-card">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="accept_invite">
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <h2>Ativar acesso de administrador</h2>
                <label>E-mail <input value="<?= h($invite['invitee_email']) ?>" readonly disabled></label>
                <label>Seu nome <input name="name" required autofocus></label>
                <label>Senha <input type="password" name="password" minlength="6" required autocomplete="new-password"></label>
                <button class="button primary" type="submit">Ativar minha conta</button>
            </form>
        <?php else: ?>
            <div class="panel form-card">
                <h2>Link indisponível</h2>
                <p class="muted">Se você acha que isso é um engano, entre em contato com quem te enviou o convite para gerar um novo link.</p>
                <a class="muted-link" href="<?= h(app_url(['page' => 'login'])) ?>">Ir para o login</a>
            </div>
        <?php endif; ?>
    </section>
    <?php
}
