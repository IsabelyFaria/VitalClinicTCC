<?php

/**
 * Tela exclusiva do super admin: lista todas as clínicas cadastradas
 * e deixa mudar o status de assinatura de cada uma. O site funciona
 * por assinatura — uma clínica marcada como "Suspensa" tem o acesso
 * de todo admin/médico dela bloqueado na hora (ver attempt_login() e
 * current_user(), em app/auth.php), até ser reativada aqui.
 */
function render_admin_clinics(array $user): void
{
    $clinics = clinics_with_stats();
    $statusLabels = ['trial' => 'Em teste', 'active' => 'Ativa', 'suspended' => 'Suspensa'];
    $statusClasses = ['trial' => 'pending', 'active' => 'confirmed', 'suspended' => 'cancelled'];
    ?>
    <section class="page-head page-head-inline">
        <div>
            <p class="eyebrow">Administração</p>
            <h1>Clínicas</h1>
        </div>
        <a class="button small" href="<?= h(app_url(['page' => 'dashboard'])) ?>">Voltar</a>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Clínica</th>
                        <th>CNPJ</th>
                        <th>Admins</th>
                        <th>Médicos</th>
                        <th>Assinatura</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clinics as $clinic): ?>
                        <tr>
                            <td><?= h($clinic['name']) ?></td>
                            <td><?= h($clinic['cnpj']) ?></td>
                            <td><?= (int) $clinic['admin_count'] ?></td>
                            <td><?= (int) $clinic['doctor_count'] ?></td>
                            <td>
                                <span class="status <?= h($statusClasses[$clinic['subscription_status']]) ?>">
                                    <?= h($statusLabels[$clinic['subscription_status']]) ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <?php if ($clinic['subscription_status'] !== 'active'): ?>
                                        <form method="post" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="admin_set_clinic_status">
                                            <input type="hidden" name="clinic_id" value="<?= (int) $clinic['id'] ?>">
                                            <input type="hidden" name="status" value="active">
                                            <input type="hidden" name="page_after" value="admin_clinics">
                                            <button class="button small primary" type="submit">Ativar</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($clinic['subscription_status'] !== 'suspended'): ?>
                                        <form method="post" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="admin_set_clinic_status">
                                            <input type="hidden" name="clinic_id" value="<?= (int) $clinic['id'] ?>">
                                            <input type="hidden" name="status" value="suspended">
                                            <input type="hidden" name="page_after" value="admin_clinics">
                                            <button class="button small danger" type="submit" data-confirm="Suspender o acesso de <?= h($clinic['name']) ?>? Todo admin/médico dela será desconectado.">Suspender</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
}