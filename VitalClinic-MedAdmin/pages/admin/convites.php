<?php

/**
 * Tela exclusiva do super admin: cadastra uma clínica nova e gera um
 * link de convite de uso único para o primeiro administrador dela.
 * Não envia e-mail sozinho — o link é gerado na tela pra ser copiado
 * e enviado por fora do sistema (WhatsApp, e-mail, o que for mais
 * prático).
 */
function render_admin_invites(array $user): void
{
    $invites = admin_invites_list();
    $validHours = (int) (config('rules.invite_valid_hours') ?: 72);
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow">Administração</p>
            <h1>Convites de primeiro acesso</h1>
        </div>
    </section>

    <section class="grid two">
        <div class="panel">
            <h2>Nova clínica + convite</h2>
            <p class="muted">Cadastra a clínica e gera um link de uso único, válido por <?= $validHours ?>h, para a pessoa definir a própria senha e virar administradora dela.</p>
            <form method="post" class="subform">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="admin_create_invite">
                <input type="hidden" name="page_after" value="admin_invites">

                <label>Clínica
                    <select name="existing_clinic_id">
                        <option value="">— Cadastrar clínica nova (preencha abaixo) —</option>
                        <?php foreach (clinics() as $clinic): ?>
                            <option value="<?= (int) $clinic['id'] ?>"><?= h($clinic['name']) ?> (já existe — adiciona mais um admin a ela)</option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <h3>Dados da clínica nova <span class="muted">(ignorado se você escolheu uma já existente acima)</span></h3>
                <div class="grid two">
                    <label>Nome da clínica <input name="clinic_name"></label>
                    <label>CNPJ <input name="clinic_cnpj" placeholder="00.000.000/0001-00"></label>
                    <label>Telefone <input name="clinic_phone"></label>
                    <label>WhatsApp <input name="clinic_whatsapp" placeholder="55DDDNÚMERO"></label>
                    <label>E-mail da clínica <input type="email" name="clinic_email"></label>
                </div>
                <label>Endereço <input name="clinic_address"></label>
                <h3>Convite</h3>
                <label>E-mail do futuro administrador <input type="email" name="invitee_email" required></label>
                <button class="button primary" type="submit">Gerar convite</button>
            </form>
        </div>

        <div class="panel">
            <h2>Convites gerados</h2>
            <div class="list">
                <?php if (!$invites): ?>
                    <p class="muted">Nenhum convite gerado ainda.</p>
                <?php endif; ?>
                <?php foreach ($invites as $invite): ?>
                    <?php
                    $isExpired = $invite['status'] === 'pending' && strtotime($invite['expires_at']) < time();
                    $statusLabel = [
                        'pending' => $isExpired ? 'Expirado' : 'Aguardando',
                        'used' => 'Já ativado',
                        'revoked' => 'Revogado',
                    ][$invite['status']];
                    $statusClass = [
                        'pending' => $isExpired ? 'cancelled' : 'pending',
                        'used' => 'confirmed',
                        'revoked' => 'cancelled',
                    ][$invite['status']];
                    ?>
                    <article class="list-row">
                        <div>
                            <strong><?= h($invite['clinic_name']) ?></strong>
                            <span><?= h($invite['invitee_email']) ?> — <span class="status <?= h($statusClass) ?>"><?= h($statusLabel) ?></span></span>
                            <?php if ($invite['status'] === 'pending' && !$isExpired): ?>
                                <div class="invite-link">
                                    <input type="text" readonly value="<?= h(full_url(['page' => 'accept_invite', 'token' => $invite['token']])) ?>" onclick="this.select()">
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($invite['status'] === 'pending' && !$isExpired): ?>
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="admin_revoke_invite">
                                <input type="hidden" name="invite_id" value="<?= (int) $invite['id'] ?>">
                                <input type="hidden" name="page_after" value="admin_invites">
                                <button class="button small danger" type="submit" data-confirm="Revogar este convite?">Revogar</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}