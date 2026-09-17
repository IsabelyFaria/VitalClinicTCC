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
    $requests = clinic_requests_list();
    $validHours = (int) (config('rules.invite_valid_hours') ?: 72);
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow">Administração</p>
            <h1>Convites e pedidos de acesso</h1>
        </div>
    </section>

    <section class="panel">
        <h2>Pedidos de acesso recebidos</h2>
        <p class="muted">Vindos do formulário público (uma clínica interessada preenche os próprios dados) — aprovar aqui gera automaticamente a clínica e o convite de primeiro acesso; nada fica ativo antes de você revisar.</p>
        <div class="list">
            <?php if (!$requests): ?>
                <p class="muted">Nenhum pedido recebido ainda.</p>
            <?php endif; ?>
            <?php foreach ($requests as $request): ?>
                <?php
                $reqStatusLabel = ['pending' => 'Aguardando revisão', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado'][$request['status']];
                $reqStatusClass = ['pending' => 'pending', 'approved' => 'confirmed', 'rejected' => 'cancelled'][$request['status']];
                ?>
                <article class="list-row">
                    <div>
                        <strong><?= h($request['clinic_name']) ?></strong>
                        <span>CNPJ <?= h($request['clinic_cnpj']) ?> — <span class="status <?= h($reqStatusClass) ?>"><?= h($reqStatusLabel) ?></span></span>
                        <span>Responsável: <?= h($request['contact_name']) ?> (<?= h($request['contact_email']) ?>)</span>
                        <?php if ($request['message']): ?>
                            <p class="muted"><?= h($request['message']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($request['status'] === 'pending'): ?>
                        <div class="actions">
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve_clinic_request">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <input type="hidden" name="page_after" value="admin_invites">
                                <button class="button small primary" type="submit" data-confirm="Aprovar este pedido e gerar o convite para <?= h($request['clinic_name']) ?>?">Aprovar</button>
                            </form>
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject_clinic_request">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <input type="hidden" name="page_after" value="admin_invites">
                                <button class="button small danger" type="submit" data-confirm="Rejeitar este pedido?">Rejeitar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
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
                            <option value="<?= (int) $clinic['id'] ?>"><?= h($clinic['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="muted">Escolhendo uma clínica já existente, o convite adiciona mais um administrador a ela (não cria uma clínica nova).</span>
                </label>

                <h3>Dados da clínica nova <span class="muted">— só necessário se você optou por cadastrar uma clínica nova acima</span></h3>
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