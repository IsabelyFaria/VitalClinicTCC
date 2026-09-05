<?php

/** Relatórios agregados (consultas por status, faltas, faturamento)
 * dentro de um período escolhido pelo filtro De/Até. */
function render_admin_reports(): void
{
    $from = $_GET['from'] ?? (new DateTime('first day of this month'))->format('Y-m-d');
    $to = $_GET['to'] ?? (new DateTime('last day of this month'))->format('Y-m-d');
    $report = report_data($from, $to);
    $summary = $report['summary'];
    $total = (int) ($summary['total'] ?? 0);
    $noShows = (int) ($summary['no_shows'] ?? 0);
    $noShowRate = $total ? round(($noShows / $total) * 100, 1) : 0;

    // Card "Movimentação mensal" — independente do filtro De/Até acima
    // (aquele filtra um período livre; este é sempre um mês inteiro).
    // Calculamos aqui o mês atual só pra o gráfico já nascer preenchido
    // na tela; trocar de mês depois é feito via AJAX, sem recarregar a
    // página (ver setupMovementChart() em app.js e monthly_movement_
    // endpoint() em index.php).
    $currentMonth = (new DateTime('first day of this month'))->format('Y-m');
    $movementReport = report_data(
        (new DateTime('first day of this month'))->format('Y-m-d'),
        (new DateTime('last day of this month'))->format('Y-m-d')
    );
    $movementTotal = (int) ($movementReport['summary']['total'] ?? 0);
    $movementNoShows = (int) ($movementReport['summary']['no_shows'] ?? 0);
    $movementLow = (int) (config('rules.movement_low') ?: 40);
    $movementHigh = (int) (config('rules.movement_high') ?: 120);
    if ($movementTotal >= $movementHigh) {
        $movementClass = 'high';
        $movementLabel = 'Alta movimentação';
    } elseif ($movementTotal < $movementLow) {
        $movementClass = 'low';
        $movementLabel = 'Baixa movimentação';
    } else {
        $movementClass = 'good';
        $movementLabel = 'Boa movimentação';
    }
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow">Administração</p>
            <h1>Relatórios</h1>
        </div>
    </section>

    <section class="panel">
        <form method="get" class="filters">
            <input type="hidden" name="page" value="admin_reports">
            <label>De <input type="date" name="from" value="<?= h($from) ?>"></label>
            <label>Até <input type="date" name="to" value="<?= h($to) ?>"></label>
            <button class="button" type="submit">Atualizar</button>
        </form>
        <div class="grid two stats">
            <div class="stat"><span>Consultas</span><strong><?= $total ?></strong></div>
            <div class="stat"><span>Faltas</span><strong><?= $noShowRate ?>%</strong></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Médico</th>
                        <th>Consultas</th>
                        <th>Realizadas</th>
                        <th>Faltas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['by_doctor'] as $row): ?>
                        <tr>
                            <td><?= h($row['doctor_name']) ?></td>
                            <td><?= (int) $row['total'] ?></td>
                            <td><?= (int) $row['completed'] ?></td>
                            <td><?= (int) $row['no_shows'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="page-head">
            <div>
                <h2>Movimentação mensal</h2>
                <p class="muted">Quantidade de consultas e faltas de um mês, com uma classificação rápida de movimento da clínica.</p>
            </div>
        </div>
        <form class="filters compact" data-movement-form onsubmit="return false;">
            <label>Mês <input type="month" data-movement-month value="<?= h($currentMonth) ?>" max="<?= h($currentMonth) ?>"></label>
        </form>
        <p class="movement-badge movement-<?= h($movementClass) ?>" data-movement-badge>
            <?= h($movementLabel) ?> — <?= $movementTotal ?> consultas, <?= $movementNoShows ?> faltas
        </p>
        <canvas data-movement-chart-canvas height="90"></canvas>
        <div class="actions" style="margin-top: 14px;">
            <button type="button" class="button" data-refresh-movement-chart>Atualizar informações</button>
            <button type="button" class="button primary" data-generate-report-chart>Gerar gráfico e imprimir</button>
        </div>
        <script type="application/json" data-movement-initial>
            <?= json_encode([
                'total' => $movementTotal,
                'no_shows' => $movementNoShows,
                'classification' => $movementClass,
                'classification_label' => $movementLabel,
                'month_label' => (new DateTime('first day of this month'))->format('m/Y'),
            ], JSON_UNESCAPED_UNICODE) ?>
        </script>
    </section>

    <!-- Relatório imprimível: fica escondido até o botão acima ser
         clicado; no momento de imprimir, o CSS (@media print, em
         styles.css) esconde todo o resto da página e mostra só isto. -->
    <section class="print-report" data-print-report hidden>
        <header class="print-report-head">
            <h1><?= h(config('app_name')) ?> — Relatório Administrativo</h1>
            <p>Período: <?= h(format_date($from)) ?> a <?= h(format_date($to)) ?></p>
        </header>

        <div class="print-report-summary">
            <div><span>Consultas no período</span><strong><?= $total ?></strong></div>
            <div><span>Taxa de faltas</span><strong><?= $noShowRate ?>%</strong></div>
            <div><span>Médicos na clínica</span><strong><?= count($report['by_doctor']) ?></strong></div>
        </div>

        <canvas data-report-chart-canvas height="90"></canvas>

        <table class="print-report-table">
            <thead>
                <tr>
                    <th>Médico</th>
                    <th>Consultas</th>
                    <th>Realizadas</th>
                    <th>Faltas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['by_doctor'] as $row): ?>
                    <tr>
                        <td><?= h($row['doctor_name']) ?></td>
                        <td><?= (int) $row['total'] ?></td>
                        <td><?= (int) $row['completed'] ?></td>
                        <td><?= (int) $row['no_shows'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="print-report-footer">Gerado em <?= h(format_datetime(now_sql())) ?> pelo painel Vital Clinic.</p>
    </section>

    <!-- Dados do relatório, para o gráfico ser montado em JavaScript
         (ver setupReportsChart() em app.js) sem precisar de uma nova
         chamada ao servidor — os números já foram calculados acima. -->
    <script type="application/json" data-report-data>
        <?= json_encode([
            'period' => format_date($from) . ' a ' . format_date($to),
            'doctors' => array_map(static function (array $row): array {
                return [
                    'name' => $row['doctor_name'],
                    'total' => (int) $row['total'],
                    'completed' => (int) $row['completed'],
                    'no_shows' => (int) $row['no_shows'],
                ];
            }, $report['by_doctor']),
        ], JSON_UNESCAPED_UNICODE) ?>
    </script>
    <?php
}