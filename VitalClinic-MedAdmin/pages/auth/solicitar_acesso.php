<?php

/**
 * Formulário público (sem login) para uma clínica interessada pedir
 * acesso ao sistema. NUNCA cria conta nem clínica ativa sozinho — só
 * registra um pedido pendente, que o super admin revisa depois (ver
 * "Convites e pedidos", tela exclusiva dele). Se aprovado, o pedido
 * vira automaticamente um convite de primeiro acesso de verdade.
 */
function render_request_access(): void
{
    $submitted = isset($_GET['enviado']);
    ?>
    <section class="request-hero">
        <img class="request-hero-logo" src="<?= asset_url('assets/brand/vital-clinic-logo.svg') ?>" alt="Vital Clinic">
        <h1>Leve o Vital Clinic para a sua clínica</h1>
        <p>Preencha os dados abaixo — nossa equipe analisa o pedido e, se aprovado, envia um link para você ativar o acesso de administrador.</p>
    </section>

    <section class="request-form-wrap">
        <?php if ($submitted): ?>
            <div class="panel form-card">
                <h2>Pedido enviado!</h2>
                <p>Recebemos os dados da sua clínica. Assim que o pedido for analisado, você receberá um link de ativação no e-mail informado.</p>
                <a class="muted-link" href="<?= h(app_url(['page' => 'login'])) ?>">Ir para o login</a>
            </div>
        <?php else: ?>
            <form method="post" class="panel form-card">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="submit_clinic_request">
                <h2>Dados da clínica</h2>
                <div class="grid two">
                    <label>Nome da clínica <input name="clinic_name" required></label>
                    <label>CNPJ <input name="clinic_cnpj" required placeholder="00.000.000/0001-00"></label>
                    <label>Telefone <input name="clinic_phone"></label>
                    <label>WhatsApp <input name="clinic_whatsapp" placeholder="55DDDNÚMERO"></label>
                    <label>E-mail da clínica <input type="email" name="clinic_email"></label>
                </div>
                <label>Endereço <input name="clinic_address"></label>

                <h2>Responsável pelo acesso</h2>
                <div class="grid two">
                    <label>Seu nome <input name="contact_name" required></label>
                    <label>Seu e-mail <input type="email" name="contact_email" required></label>
                </div>
                <label>Mensagem (opcional) <textarea name="message" rows="3" placeholder="Conte um pouco sobre sua clínica, se quiser."></textarea></label>

                <button class="button primary" type="submit">Enviar pedido</button>
            </form>
        <?php endif; ?>
    </section>
    <?php
}