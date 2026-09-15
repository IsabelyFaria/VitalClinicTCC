<?php

function render_notificacoes(array $paciente, array $notificacoes): void
{
    render_header($paciente, 'notificacoes');
    ?>
    <h1>Notificações</h1>
    <p class="text-muted" style="margin-bottom: 20px;">Avisos sobre suas consultas</p>

    <?php if (empty($notificacoes)): ?>
        <div class="card">
            <p>Você não tem nenhuma notificação.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notificacoes as $notificacao): ?>
        <!-- segundo <span> com a data de envio, logo depois do selinho, como
             os dois estão dentro do mesmo flex com "space-between", o selinho 
             fica na esquerda e essa data vai sozinha pra direita): -->
            <div class="appointment-card">
                 <div class="appointment-card-top">
                    <span class="<?= h(notificacao_badge_class($notificacao['title'])) ?>">
                        <?= h($notificacao['title']) ?>
                    </span>

                    <!-- data em que a NOTIFICAÇÃO foi enviada (sent_at), não
                         é a data da consulta (essa já aparece na linha de
                         baixo, junto com médico e clínica). É só a informação
                         de "quando esse aviso chegou pra você" -->
                    <span class="text-muted" style="font-size: 13px;">
                        <?= h(format_datetime($notificacao['sent_at'])) ?>
                    </span>
                </div>

                <!-- frase amigável em negrito (ex.: "Sua consulta foi
                     confirmada"), no lugar do "message" cru do banco -->
                <div class="appointment-doctor"><?= h(notificacao_titulo_amigavel($notificacao['title'])) ?></div>

                <?php if (!empty($notificacao['doctor_name'])): ?>
                    <!-- só entra aqui quando a notificação está ligada a uma
                         consulta de verdade (doctor_name veio preenchido do
                         LEFT JOIN). Médico, clínica e data juntos numa linha
                         só, centralizada, mesma ideia da coluna do meio do
                         Histórico (.historico-meta-central) -->
                    <div class="appointment-meta">
                        Consulta com <?= h($notificacao['doctor_name']) ?> · <?= h($notificacao['clinic_name']) ?> · <?= h(format_datetime($notificacao['slot_start'])) ?>
                    </div>
                <?php else: ?>
                    <!-- notificação sem consulta ligada (aviso geral do
                         sistema, por exemplo), mostra o texto original -->
                    <div class="appointment-meta"><?= h($notificacao['message']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    render_footer();
}