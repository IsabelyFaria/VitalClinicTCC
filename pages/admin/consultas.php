<?php

function render_admin_appointments(): void
{
    $filters = [
        'date' => $_GET['date'] ?? '',
        'status' => $_GET['status'] ?? '',
        'doctor_id' => (int) ($_GET['doctor_id'] ?? 0),
    ];
    $appointments = appointments_for_admin($filters);
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow">Administração</p>
            <h1>Consultas</h1>
        </div>
    </section>
    <section class="panel">
        <form method="get" class="filters">
            <input type="hidden" name="page" value="admin_appointments">
            <label>Data <input type="date" name="date" value="<?= h($filters['date']) ?>"></label>
            <label>Status
                <select name="status">
                    <option value="">Todos</option>
                    <?php foreach (['pending', 'confirmed', 'completed', 'cancelled', 'no_show'] as $status): ?>
                        <option value="<?= h($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= h(status_label($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Médico
                <select name="doctor_id">
                    <option value="">Todos</option>
                    <?php foreach (active_doctors() as $doctor): ?>
                        <option value="<?= (int) $doctor['id'] ?>" <?= (int) $filters['doctor_id'] === (int) $doctor['id'] ? 'selected' : '' ?>>
                            <?= h($doctor['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button" type="submit">Filtrar</button>
        </form>
        <?php render_appointment_table($appointments, ['role' => 'admin', 'id' => 0], 'admin_appointments'); ?>
        <?php if (!empty($filters['date']) && !empty($filters['doctor_id'])): ?>
            <div class="split-line"></div>
            <h2>Agenda completa do médico</h2>
            <div class="timeline">
                <?php foreach (doctor_day_slots((int) $filters['doctor_id'], $filters['date']) as $slot): ?>
                    <div class="timeline-row <?= h($slot['status']) ?>">
                        <time><?= h(format_time($slot['slot_start'])) ?></time>
                        <span>
                            <?= h(status_label($slot['appointment_status'] ?: $slot['status'])) ?>
                            <?= $slot['patient_name'] ? ' - ' . h($slot['patient_name']) : '' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}
