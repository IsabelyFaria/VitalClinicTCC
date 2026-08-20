<?php

function render_admin_payments(): void
{
    $filters = [
        'date' => $_GET['date'] ?? '',
        'status' => $_GET['status'] ?? '',
    ];
    $summary = payment_summary();
    $payments = payments_for_admin($filters);
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow">Administração</p>
            <h1>Gestão de pagamentos</h1>
        </div>
    </section>

    <section class="grid stats">
        <div class="stat"><span>Recebido hoje</span><strong><?= h(format_money($summary['paid_today'] ?? 0)) ?></strong></div>
        <div class="stat"><span>Pendente</span><strong><?= h(format_money($summary['pending_total'] ?? 0)) ?></strong></div>
        <div class="stat"><span>Total recebido</span><strong><?= h(format_money($summary['paid_total'] ?? 0)) ?></strong></div>
        <div class="stat"><span>Pagamentos pendentes</span><strong><?= (int) ($summary['pending_count'] ?? 0) ?></strong></div>
    </section>

    <section class="panel">
        <form method="get" class="filters">
            <input type="hidden" name="page" value="admin_payments">
            <label>Data <input type="date" name="date" value="<?= h($filters['date']) ?>"></label>
            <label>Status
                <select name="status">
                    <option value="">Todos</option>
                    <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Pago</option>
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </label>
            <button class="button" type="submit">Filtrar</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Paciente</th>
                        <th>Serviço</th>
                        <th>Valor</th>
                        <th>Método</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= h(format_datetime($payment['slot_start'])) ?></td>
                            <td><?= h($payment['patient_name']) ?></td>
                            <td><?= h($payment['specialty_name']) ?></td>
                            <td><?= h(format_money($payment['amount'])) ?></td>
                            <td><?= h($payment['method'] ?: '-') ?></td>
                            <td><span class="status <?= h($payment['payment_status']) ?>"><?= h($payment['payment_status'] === 'paid' ? 'Pago' : ($payment['payment_status'] === 'cancelled' ? 'Cancelado' : 'Pendente')) ?></span></td>
                            <td>
                                <div class="actions">
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="set_payment_status">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $payment['appointment_id'] ?>">
                                        <input type="hidden" name="status" value="paid">
                                        <input type="hidden" name="method" value="Cartao">
                                        <button class="button small" type="submit">Registrar pago</button>
                                    </form>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="set_payment_status">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $payment['appointment_id'] ?>">
                                        <input type="hidden" name="status" value="pending">
                                        <button class="button small warning" type="submit">Pendente</button>
                                    </form>
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
