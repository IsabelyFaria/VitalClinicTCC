<?php

/**
 * Repositório sem banco de dados local.
 *
 * O modo demo usa JSON somente para manter a aplicação navegável durante o
 * desenvolvimento. Quando a API central estiver pronta, o cliente em
 * app/api_client.php poderá ser ativado por configuração.
 */
function repository_data_path(): string
{
    return __DIR__ . '/../data/demo-state.json';
}

function repository_seed(): array
{
    $nextMonday = (new DateTime('monday next week'))->format('Y-m-d');
    $slotStart = $nextMonday . ' 09:00:00';
    $slotEnd = $nextMonday . ' 09:30:00';
    $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    return [
        'clinics' => [
            ['id' => 1, 'name' => 'Clínica Central', 'cnpj' => '00.000.000/0001-00', 'address' => 'Rua das Flores, 120', 'phone' => '(11) 4000-1000', 'whatsapp' => '551140001000', 'email' => 'contato@clinicacentral.local'],
            ['id' => 2, 'name' => 'Clínica Norte', 'cnpj' => '00.000.000/0002-00', 'address' => 'Avenida Norte, 850', 'phone' => '(11) 4000-2000', 'whatsapp' => '551140002000', 'email' => 'contato@clinicanorte.local'],
        ],
        'specialties' => [
            ['id' => 1, 'name' => 'Clínico geral', 'description' => 'Atendimento médico inicial e acompanhamento.'],
            ['id' => 2, 'name' => 'Cardiologia', 'description' => 'Consultas e acompanhamento cardiológico.'],
            ['id' => 3, 'name' => 'Dermatologia', 'description' => 'Consultas dermatológicas.'],
            ['id' => 4, 'name' => 'Pediatria', 'description' => 'Atendimento infantil.'],
        ],
        'users' => [
            ['id' => 1, 'name' => 'Administrador da Clínica', 'email' => 'admin@clinica.local', 'password_hash' => $password, 'role' => 'admin', 'phone' => '(11) 90000-0001', 'document' => null, 'birth_date' => null, 'address' => null, 'clinic_id' => 1, 'status' => 'active', 'created_at' => now_sql(), 'updated_at' => null, 'last_login_at' => null],
            ['id' => 2, 'name' => 'Dra. Ana Souza', 'email' => 'medico@clinica.local', 'password_hash' => $password, 'role' => 'doctor', 'phone' => '(11) 90000-0002', 'document' => null, 'birth_date' => null, 'address' => null, 'clinic_id' => 1, 'status' => 'active', 'created_at' => now_sql(), 'updated_at' => null, 'last_login_at' => null],
            ['id' => 3, 'name' => 'Paciente de Demonstração', 'email' => 'paciente.demo@clinica.local', 'password_hash' => $password, 'role' => 'patient', 'phone' => '(11) 90000-0003', 'document' => null, 'birth_date' => '1995-05-10', 'address' => 'Endereço de demonstração', 'clinic_id' => 1, 'status' => 'active', 'created_at' => now_sql(), 'updated_at' => null, 'last_login_at' => null],
        ],
        'doctors' => [
            ['id' => 1, 'user_id' => 2, 'clinic_id' => 1, 'specialty_id' => 1, 'crm' => 'CRM-SP 123456', 'bio' => 'Atendimento clínico com foco em acompanhamento preventivo.', 'appointment_duration' => 30, 'active' => 1, 'created_at' => now_sql()],
        ],
        'doctor_schedules' => [
            ['id' => 1, 'doctor_id' => 1, 'weekday' => 1, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'active' => 1],
            ['id' => 2, 'doctor_id' => 1, 'weekday' => 1, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'active' => 1],
            ['id' => 3, 'doctor_id' => 1, 'weekday' => 2, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'active' => 1],
            ['id' => 4, 'doctor_id' => 1, 'weekday' => 2, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'active' => 1],
            ['id' => 5, 'doctor_id' => 1, 'weekday' => 3, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'active' => 1],
            ['id' => 6, 'doctor_id' => 1, 'weekday' => 3, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'active' => 1],
            ['id' => 7, 'doctor_id' => 1, 'weekday' => 4, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'active' => 1],
            ['id' => 8, 'doctor_id' => 1, 'weekday' => 4, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'active' => 1],
            ['id' => 9, 'doctor_id' => 1, 'weekday' => 5, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'active' => 1],
            ['id' => 10, 'doctor_id' => 1, 'weekday' => 5, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'active' => 1],
        ],
        'schedule_blocks' => [],
        'appointment_slots' => [
            ['id' => 1, 'doctor_id' => 1, 'slot_start' => $slotStart, 'slot_end' => $slotEnd, 'status' => 'booked', 'block_reason' => null],
        ],
        'appointments' => [
            ['id' => 1, 'slot_id' => 1, 'patient_id' => 3, 'doctor_id' => 1, 'clinic_id' => 1, 'specialty_id' => 1, 'status' => 'confirmed', 'notes' => 'Consulta de demonstração.', 'cancel_reason' => null, 'confirmed_at' => now_sql(), 'cancelled_at' => null, 'completed_at' => null, 'created_at' => now_sql(), 'updated_at' => now_sql()],
        ],
        'notifications' => [
            ['id' => 1, 'user_id' => 2, 'appointment_id' => 1, 'type' => 'in_app', 'title' => 'Agenda de demonstração', 'message' => 'Esta consulta é um exemplo para apresentar o sistema.', 'status' => 'sent', 'send_at' => now_sql(), 'sent_at' => now_sql(), 'read_at' => null, 'created_at' => now_sql()],
        ],
        'medical_records' => [],
        'payments' => [
            ['id' => 1, 'appointment_id' => 1, 'patient_id' => 3, 'clinic_id' => 1, 'amount' => 250.00, 'method' => null, 'status' => 'pending', 'paid_at' => null, 'created_at' => now_sql(), 'updated_at' => null],
        ],
    ];
}

function repository_state(): array
{
    if (isset($GLOBALS['vctcc_repository_state']) && is_array($GLOBALS['vctcc_repository_state'])) {
        return $GLOBALS['vctcc_repository_state'];
    }

    if (api_is_enabled()) {
        $state = api_state();
        $GLOBALS['vctcc_repository_state'] = $state;
        return $state;
    }

    $path = repository_data_path();
    if (is_file($path)) {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (is_array($decoded)) {
            $GLOBALS['vctcc_repository_state'] = $decoded;
            return $decoded;
        }
    }

    $state = repository_seed();
    repository_save($state);
    return $state;
}

function repository_save(array $state): void
{
    if (api_is_enabled()) {
        api_save_state($state);
        $GLOBALS['vctcc_repository_state'] = $state;
        return;
    }

    $path = repository_data_path();
    $directory = dirname($path);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    $GLOBALS['vctcc_repository_state'] = $state;
}

function repository_next_id(array $rows): int
{
    $ids = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows);
    return ($ids ? max($ids) : 0) + 1;
}

function repository_find(string $collection, int $id): ?array
{
    foreach (repository_state()[$collection] ?? [] as $row) {
        if ((int) ($row['id'] ?? 0) === $id) {
            return $row;
        }
    }
    return null;
}

function repository_find_user(int $id): ?array
{
    return repository_find('users', $id);
}

function repository_find_doctor(int $id): ?array
{
    return repository_find('doctors', $id);
}

function repository_user_with_clinic(array $user): array
{
    $clinic = repository_find('clinics', (int) ($user['clinic_id'] ?? 0));
    $user['clinic_name'] = $clinic['name'] ?? null;
    return $user;
}

function repository_doctor_row(array $doctor): array
{
    $user = repository_find_user((int) $doctor['user_id']) ?: [];
    $clinic = repository_find('clinics', (int) $doctor['clinic_id']) ?: [];
    $specialty = repository_find('specialties', (int) $doctor['specialty_id']) ?: [];
    return array_merge($doctor, [
        'name' => $user['name'] ?? '',
        'email' => $user['email'] ?? '',
        'phone' => $user['phone'] ?? '',
        'clinic_name' => $clinic['name'] ?? '',
        'specialty_name' => $specialty['name'] ?? '',
    ]);
}

function repository_slot(int $id): ?array
{
    return repository_find('appointment_slots', $id);
}

function repository_appointment_row(array $appointment): array
{
    $state = repository_state();
    $slot = repository_slot((int) $appointment['slot_id']) ?: [];
    $patient = repository_find_user((int) $appointment['patient_id']) ?: [];
    $doctor = repository_find_doctor((int) $appointment['doctor_id']) ?: [];
    $doctorUser = repository_find_user((int) ($doctor['user_id'] ?? 0)) ?: [];
    $clinic = repository_find('clinics', (int) $appointment['clinic_id']) ?: [];
    $specialty = repository_find('specialties', (int) $appointment['specialty_id']) ?: [];
    $payment = null;
    foreach ($state['payments'] ?? [] as $row) {
        if ((int) $row['appointment_id'] === (int) $appointment['id']) {
            $payment = $row;
            break;
        }
    }

    return array_merge($appointment, [
        'slot_start' => $slot['slot_start'] ?? null,
        'slot_end' => $slot['slot_end'] ?? null,
        'slot_status' => $slot['status'] ?? null,
        'patient_name' => $patient['name'] ?? '',
        'patient_email' => $patient['email'] ?? '',
        'patient_phone' => $patient['phone'] ?? '',
        'patient_document' => $patient['document'] ?? '',
        'patient_birth_date' => $patient['birth_date'] ?? null,
        'patient_address' => $patient['address'] ?? '',
        'doctor_name' => $doctorUser['name'] ?? '',
        'doctor_email' => $doctorUser['email'] ?? '',
        'doctor_phone' => $doctorUser['phone'] ?? '',
        'doctor_document' => $doctorUser['document'] ?? '',
        'doctor_address' => $doctorUser['address'] ?? '',
        'doctor_crm' => $doctor['crm'] ?? '',
        'clinic_name' => $clinic['name'] ?? '',
        'specialty_name' => $specialty['name'] ?? '',
        'payment_status' => $payment['status'] ?? 'pending',
        'amount' => $payment['amount'] ?? 250.00,
        'payment_method' => $payment['method'] ?? null,
        'paid_at' => $payment['paid_at'] ?? null,
    ]);
}

function repository_find_appointment(int $id): ?array
{
    foreach (repository_state()['appointments'] ?? [] as $appointment) {
        if ((int) $appointment['id'] === $id) {
            return $appointment;
        }
    }
    return null;
}

function repository_replace(string $collection, int $id, array $replacement): void
{
    $state = repository_state();
    foreach ($state[$collection] ?? [] as $index => $row) {
        if ((int) ($row['id'] ?? 0) === $id) {
            $state[$collection][$index] = $replacement;
            repository_save($state);
            return;
        }
    }
}

function repository_append(string $collection, array $row): int
{
    $state = repository_state();
    $row['id'] = $row['id'] ?? repository_next_id($state[$collection] ?? []);
    $state[$collection][] = $row;
    repository_save($state);
    return (int) $row['id'];
}

function repository_update_user(int $id, array $changes): void
{
    $user = repository_find_user($id);
    if ($user) {
        repository_replace('users', $id, array_merge($user, $changes, ['updated_at' => now_sql()]));
    }
}

function ensure_runtime_schema(): void
{
    // O modo sem banco não precisa criar tabelas. Esta função é mantida para
    // que o restante do controlador continue compatível com a versão anterior.
}

function clinics(): array
{
    $rows = repository_state()['clinics'] ?? [];
    usort($rows, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
    return $rows;
}

function specialties(): array
{
    $rows = repository_state()['specialties'] ?? [];
    usort($rows, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
    return $rows;
}

function active_doctors(array $filters = []): array
{
    $rows = [];
    foreach (repository_state()['doctors'] ?? [] as $doctor) {
        if (!(int) ($doctor['active'] ?? 0)) {
            continue;
        }
        $user = repository_find_user((int) $doctor['user_id']);
        if (!$user || $user['status'] !== 'active') {
            continue;
        }
        if (!empty($filters['clinic_id']) && (int) $doctor['clinic_id'] !== (int) $filters['clinic_id']) {
            continue;
        }
        if (!empty($filters['specialty_id']) && (int) $doctor['specialty_id'] !== (int) $filters['specialty_id']) {
            continue;
        }
        if (!empty($filters['doctor_id']) && (int) $doctor['id'] !== (int) $filters['doctor_id']) {
            continue;
        }
        $rows[] = repository_doctor_row($doctor);
    }
    usort($rows, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
    return $rows;
}

function doctor_by_user(int $userId): ?array
{
    foreach (active_doctors() as $doctor) {
        if ((int) $doctor['user_id'] === $userId) {
            return $doctor;
        }
    }
    return null;
}

function doctor_detail(int $doctorId): ?array
{
    return active_doctors(['doctor_id' => $doctorId])[0] ?? null;
}

function slot_overlaps_blocks(DateTime $start, DateTime $end, array $blocks): ?array
{
    foreach ($blocks as $block) {
        $blockStart = new DateTime($block['block_date'] . ' ' . $block['start_time']);
        $blockEnd = new DateTime($block['block_date'] . ' ' . $block['end_time']);
        if ($start < $blockEnd && $end > $blockStart) {
            return $block;
        }
    }
    return null;
}

function ensure_slots(int $doctorId, string $fromDate, string $toDate): void
{
    $doctor = repository_find_doctor($doctorId);
    if (!$doctor || !(int) ($doctor['active'] ?? 0)) {
        return;
    }

    $state = repository_state();
    $schedules = array_values(array_filter($state['doctor_schedules'] ?? [], static fn(array $row): bool => (int) $row['doctor_id'] === $doctorId && (int) $row['active'] === 1));
    $blocks = array_values(array_filter($state['schedule_blocks'] ?? [], static fn(array $row): bool => (int) $row['doctor_id'] === $doctorId && $row['block_date'] >= $fromDate && $row['block_date'] <= $toDate));
    $existing = [];
    foreach ($state['appointment_slots'] ?? [] as $slot) {
        $existing[$doctorId . '|' . $slot['slot_start']] = true;
    }

    $duration = max(10, (int) ($doctor['appointment_duration'] ?? 30));
    $current = new DateTime($fromDate);
    $endDate = (new DateTime($toDate))->modify('+1 day');
    while ($current < $endDate) {
        $weekday = (int) $current->format('w');
        foreach ($schedules as $schedule) {
            if ((int) $schedule['weekday'] !== $weekday) {
                continue;
            }
            $slotStart = new DateTime($current->format('Y-m-d') . ' ' . $schedule['start_time']);
            $limit = new DateTime($current->format('Y-m-d') . ' ' . $schedule['end_time']);
            while ($slotStart < $limit) {
                $slotEnd = (clone $slotStart)->modify('+' . $duration . ' minutes');
                if ($slotEnd > $limit) {
                    break;
                }
                $start = $slotStart->format('Y-m-d H:i:s');
                $key = $doctorId . '|' . $start;
                if (!isset($existing[$key])) {
                    $block = slot_overlaps_blocks($slotStart, $slotEnd, $blocks);
                    $state['appointment_slots'][] = [
                        'id' => repository_next_id($state['appointment_slots']),
                        'doctor_id' => $doctorId,
                        'slot_start' => $start,
                        'slot_end' => $slotEnd->format('Y-m-d H:i:s'),
                        'status' => $block ? 'blocked' : 'available',
                        'block_reason' => $block['reason'] ?? null,
                    ];
                    $existing[$key] = true;
                }
                $slotStart = $slotEnd;
            }
        }
        $current->modify('+1 day');
    }
    repository_save($state);
}

function ensure_slots_for_all(string $fromDate, string $toDate): void
{
    foreach (active_doctors() as $doctor) {
        ensure_slots((int) $doctor['id'], $fromDate, $toDate);
    }
}

function available_slots(int $doctorId, string $date): array
{
    ensure_slots($doctorId, $date, $date);
    $now = new DateTime();
    $rows = [];
    foreach (repository_state()['appointment_slots'] ?? [] as $slot) {
        if ((int) $slot['doctor_id'] === $doctorId && substr($slot['slot_start'], 0, 10) === $date && $slot['status'] === 'available' && new DateTime($slot['slot_start']) > $now) {
            $rows[] = ['id' => (int) $slot['id'], 'slot_start' => $slot['slot_start'], 'slot_end' => $slot['slot_end']];
        }
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($a['slot_start'], $b['slot_start']));
    return $rows;
}

function doctor_day_slots(int $doctorId, string $date): array
{
    ensure_slots($doctorId, $date, $date);
    $rows = [];
    foreach (repository_state()['appointment_slots'] ?? [] as $slot) {
        if ((int) $slot['doctor_id'] !== $doctorId || substr($slot['slot_start'], 0, 10) !== $date) {
            continue;
        }
        $appointment = null;
        foreach (repository_state()['appointments'] ?? [] as $candidate) {
            if ((int) $candidate['slot_id'] === (int) $slot['id'] && $candidate['status'] !== 'cancelled') {
                $appointment = $candidate;
                break;
            }
        }
        $row = $slot;
        $row['appointment_id'] = $appointment['id'] ?? null;
        $row['appointment_status'] = $appointment['status'] ?? null;
        $patient = $appointment ? repository_find_user((int) $appointment['patient_id']) : null;
        $row['patient_name'] = $patient['name'] ?? null;
        $row['patient_phone'] = $patient['phone'] ?? null;
        $rows[] = $row;
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($a['slot_start'], $b['slot_start']));
    return $rows;
}

function can_change_appointment(string $slotStart, int $hours): bool
{
    return (new DateTime())->modify('+' . $hours . ' hours') <= new DateTime($slotStart);
}

function create_notification(int $userId, ?int $appointmentId, string $type, string $title, string $message, ?string $sendAt = null): void
{
    repository_append('notifications', [
        'user_id' => $userId,
        'appointment_id' => $appointmentId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'status' => 'sent',
        'send_at' => $sendAt ?: now_sql(),
        'sent_at' => now_sql(),
        'read_at' => null,
        'created_at' => now_sql(),
    ]);
}

function doctor_user_id(int $doctorId): ?int
{
    $doctor = repository_find_doctor($doctorId);
    return $doctor ? (int) $doctor['user_id'] : null;
}

function create_appointment(int $patientId, int $doctorId, int $slotId, string $notes = ''): int
{
    $slot = repository_slot($slotId);
    $doctor = repository_find_doctor($doctorId);
    if (!$slot || !$doctor || $slot['status'] !== 'available') {
        throw new RuntimeException('Horário não encontrado ou indisponível.');
    }
    $state = repository_state();
    foreach ($state['appointments'] as $appointment) {
        if ((int) $appointment['slot_id'] === $slotId && in_array($appointment['status'], ['pending', 'confirmed'], true)) {
            throw new RuntimeException('Este horário já foi reservado.');
        }
    }
    $appointmentId = repository_next_id($state['appointments']);
    $state['appointment_slots'] = array_map(static function (array $row) use ($slotId): array {
        if ((int) $row['id'] === $slotId) {
            $row['status'] = 'booked';
        }
        return $row;
    }, $state['appointment_slots']);
    $appointment = ['id' => $appointmentId, 'slot_id' => $slotId, 'patient_id' => $patientId, 'doctor_id' => $doctorId, 'clinic_id' => $doctor['clinic_id'], 'specialty_id' => $doctor['specialty_id'], 'status' => 'pending', 'notes' => $notes ?: null, 'cancel_reason' => null, 'confirmed_at' => null, 'cancelled_at' => null, 'completed_at' => null, 'created_at' => now_sql(), 'updated_at' => now_sql()];
    $state['appointments'][] = $appointment;
    $state['payments'][] = ['id' => repository_next_id($state['payments']), 'appointment_id' => $appointmentId, 'patient_id' => $patientId, 'clinic_id' => $doctor['clinic_id'], 'amount' => 250.00, 'method' => null, 'status' => 'pending', 'paid_at' => null, 'created_at' => now_sql(), 'updated_at' => null];
    repository_save($state);
    return $appointmentId;
}

function appointment_query_base(): string
{
    return '';
}

function appointments_for_user(array $user, string $scope = 'future'): array
{
    $doctor = $user['role'] === 'doctor' ? doctor_by_user((int) $user['id']) : null;
    $rows = [];
    foreach (repository_state()['appointments'] ?? [] as $appointment) {
        if ($user['role'] === 'doctor' && (!$doctor || (int) $appointment['doctor_id'] !== (int) $doctor['id'])) {
            continue;
        }
        $row = repository_appointment_row($appointment);
        $future = $row['slot_start'] >= now_sql() && in_array($row['status'], ['pending', 'confirmed'], true);
        if ($scope === 'future' && !$future) {
            continue;
        }
        if ($scope === 'history' && $future) {
            continue;
        }
        $rows[] = $row;
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($a['slot_start'], $b['slot_start']));
    return $rows;
}

function appointments_for_admin(array $filters = []): array
{
    $rows = [];
    foreach (repository_state()['appointments'] ?? [] as $appointment) {
        $row = repository_appointment_row($appointment);
        if (!empty($filters['date']) && substr((string) $row['slot_start'], 0, 10) !== $filters['date']) {
            continue;
        }
        if (!empty($filters['status']) && $row['status'] !== $filters['status']) {
            continue;
        }
        if (!empty($filters['doctor_id']) && (int) $row['doctor_id'] !== (int) $filters['doctor_id']) {
            continue;
        }
        $rows[] = $row;
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($b['slot_start'], $a['slot_start']));
    return array_slice($rows, 0, 300);
}

function appointment_by_id(int $appointmentId): ?array
{
    $appointment = repository_find_appointment($appointmentId);
    return $appointment ? repository_appointment_row($appointment) : null;
}

function cancel_appointment(int $appointmentId, array $actor, string $reason = ''): void
{
    $appointment = repository_find_appointment($appointmentId);
    if (!$appointment || !in_array($appointment['status'], ['pending', 'confirmed'], true)) {
        throw new RuntimeException('Consulta não pode ser cancelada.');
    }
    $doctor = $actor['role'] === 'doctor' ? doctor_by_user((int) $actor['id']) : null;
    if ($actor['role'] === 'doctor' && (!$doctor || (int) $appointment['doctor_id'] !== (int) $doctor['id'])) {
        abort_forbidden();
    }
    $appointment['status'] = 'cancelled';
    $appointment['cancel_reason'] = $reason ?: null;
    $appointment['cancelled_at'] = now_sql();
    $appointment['updated_at'] = now_sql();
    repository_replace('appointments', $appointmentId, $appointment);
    $slot = repository_slot((int) $appointment['slot_id']);
    if ($slot && new DateTime($slot['slot_start']) > new DateTime()) {
        $slot['status'] = 'available';
        repository_replace('appointment_slots', (int) $slot['id'], $slot);
    }
    create_notification((int) $appointment['doctor_id'], $appointmentId, 'in_app', 'Consulta cancelada', 'O cancelamento da consulta foi registrado.');
}

function reschedule_appointment(int $appointmentId, int $newSlotId, array $actor): void
{
    throw new RuntimeException('A remarcação é realizada pelo administrador nesta versão.');
}

function confirm_appointment(int $appointmentId, int $patientId): void
{
    throw new RuntimeException('A confirmação pelo paciente foi desativada.');
}

function mark_appointment(int $appointmentId, array $actor, string $status): void
{
    if (!in_array($status, ['completed', 'no_show'], true)) {
        throw new RuntimeException('Status inválido.');
    }
    $appointment = repository_find_appointment($appointmentId);
    if (!$appointment) {
        throw new RuntimeException('Consulta não encontrada.');
    }
    if ($actor['role'] === 'doctor') {
        $doctor = doctor_by_user((int) $actor['id']);
        if (!$doctor || (int) $appointment['doctor_id'] !== (int) $doctor['id']) {
            abort_forbidden();
        }
    } elseif ($actor['role'] !== 'admin') {
        abort_forbidden();
    }
    $appointment['status'] = $status;
    $appointment['completed_at'] = now_sql();
    $appointment['updated_at'] = now_sql();
    repository_replace('appointments', $appointmentId, $appointment);
}

function update_profile(int $userId, array $data): void
{
    repository_update_user($userId, ['name' => $data['name'], 'phone' => $data['phone'] ?: null, 'document' => $data['document'] ?: null, 'birth_date' => $data['birth_date'] ?: null, 'address' => $data['address'] ?: null]);
}

function update_patient_admin(int $patientId, array $data): void
{
    $patient = repository_find_user($patientId);
    if (!$patient || $patient['role'] !== 'patient') {
        throw new RuntimeException('Paciente não encontrado.');
    }
    update_profile($patientId, $data);
    repository_update_user($patientId, ['status' => $data['status'] === 'inactive' ? 'inactive' : 'active']);
}

function update_staff_profile(int $userId, array $data): void
{
    $user = repository_find_user($userId);
    if (!$user || !in_array($user['role'], ['admin', 'doctor'], true)) {
        abort_forbidden();
    }
    update_profile($userId, $data);
}

function update_own_password(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): void
{
    if ($newPassword === '' && $confirmPassword === '') {
        return;
    }
    if (strlen($newPassword) < 6 || $newPassword !== $confirmPassword) {
        throw new RuntimeException('Confira a nova senha e use pelo menos 6 caracteres.');
    }
    $user = repository_find_user($userId);
    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        throw new RuntimeException('Senha atual inválida.');
    }
    repository_update_user($userId, ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);
}

function create_doctor(array $data): int
{
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail válido para o médico.');
    }
    foreach (repository_state()['users'] as $user) {
        if (strtolower($user['email']) === strtolower($data['email'])) {
            throw new RuntimeException('Já existe usuário com este e-mail.');
        }
    }
    $userId = repository_append('users', ['name' => $data['name'], 'email' => strtolower($data['email']), 'password_hash' => password_hash($data['password'] ?: '123456', PASSWORD_DEFAULT), 'role' => 'doctor', 'phone' => $data['phone'] ?: null, 'document' => null, 'birth_date' => null, 'address' => null, 'clinic_id' => (int) $data['clinic_id'], 'status' => 'active', 'created_at' => now_sql(), 'updated_at' => null, 'last_login_at' => null]);
    return repository_append('doctors', ['user_id' => $userId, 'clinic_id' => (int) $data['clinic_id'], 'specialty_id' => (int) $data['specialty_id'], 'crm' => $data['crm'], 'bio' => $data['bio'] ?: null, 'appointment_duration' => (int) ($data['appointment_duration'] ?: 30), 'active' => 1, 'created_at' => now_sql()]);
}

function update_doctor(int $doctorId, array $data): void
{
    $doctor = repository_find_doctor($doctorId);
    if (!$doctor) {
        throw new RuntimeException('Médico não encontrado.');
    }
    repository_update_user((int) $doctor['user_id'], ['name' => $data['name'], 'phone' => $data['phone'] ?: null, 'clinic_id' => (int) $data['clinic_id']]);
    repository_replace('doctors', $doctorId, array_merge($doctor, ['clinic_id' => (int) $data['clinic_id'], 'specialty_id' => (int) $data['specialty_id'], 'crm' => $data['crm'], 'bio' => $data['bio'] ?: null, 'appointment_duration' => (int) ($data['appointment_duration'] ?: 30)]));
}

function deactivate_doctor(int $doctorId): void
{
    $doctor = repository_find_doctor($doctorId);
    if (!$doctor) {
        throw new RuntimeException('Médico não encontrado.');
    }
    repository_replace('doctors', $doctorId, array_merge($doctor, ['active' => 0]));
    repository_update_user((int) $doctor['user_id'], ['status' => 'inactive']);
}

function add_schedule(array $data): void
{
    if ($data['start_time'] >= $data['end_time']) {
        throw new RuntimeException('Horário inicial deve ser antes do final.');
    }
    repository_append('doctor_schedules', ['doctor_id' => (int) $data['doctor_id'], 'weekday' => (int) $data['weekday'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'active' => 1]);
}

function delete_schedule(int $scheduleId): void
{
    $schedule = repository_find('doctor_schedules', $scheduleId);
    if ($schedule) {
        repository_replace('doctor_schedules', $scheduleId, array_merge($schedule, ['active' => 0]));
    }
}

function add_block(array $data): void
{
    if ($data['start_time'] >= $data['end_time']) {
        throw new RuntimeException('Horário inicial deve ser antes do final.');
    }
    $start = $data['block_date'] . ' ' . $data['start_time'];
    $end = $data['block_date'] . ' ' . $data['end_time'];
    foreach (repository_state()['appointment_slots'] as $slot) {
        if ((int) $slot['doctor_id'] === (int) $data['doctor_id'] && $slot['status'] === 'booked' && $slot['slot_start'] < $end && $slot['slot_end'] > $start) {
            throw new RuntimeException('Não é possível bloquear horário com consulta agendada.');
        }
    }
    repository_append('schedule_blocks', ['doctor_id' => (int) $data['doctor_id'], 'block_date' => $data['block_date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'reason' => $data['reason'] ?: null, 'created_at' => now_sql()]);
    $state = repository_state();
    foreach ($state['appointment_slots'] as &$slot) {
        if ((int) $slot['doctor_id'] === (int) $data['doctor_id'] && $slot['status'] === 'available' && $slot['slot_start'] < $end && $slot['slot_end'] > $start) {
            $slot['status'] = 'blocked';
            $slot['block_reason'] = $data['reason'] ?: 'Bloqueado pela clínica';
        }
    }
    unset($slot);
    repository_save($state);
}

function patient_list(): array
{
    $rows = [];
    foreach (repository_state()['users'] as $patient) {
        if ($patient['role'] !== 'patient') {
            continue;
        }
        $total = 0;
        $noShows = 0;
        foreach (repository_state()['appointments'] as $appointment) {
            if ((int) $appointment['patient_id'] === (int) $patient['id']) {
                $total++;
                $noShows += $appointment['status'] === 'no_show' ? 1 : 0;
            }
        }
        $patient['total_appointments'] = $total;
        $patient['no_shows'] = $noShows;
        $rows[] = $patient;
    }
    usort($rows, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
    return $rows;
}

function doctor_patient_list(int $doctorId, string $search = ''): array
{
    $ids = [];
    foreach (repository_state()['appointments'] as $appointment) {
        if ((int) $appointment['doctor_id'] === $doctorId) {
            $ids[(int) $appointment['patient_id']] = true;
        }
    }
    $rows = [];
    foreach (array_keys($ids) as $patientId) {
        $patient = repository_find_user((int) $patientId);
        if (!$patient || ($search !== '' && stripos($patient['name'], $search) === false)) {
            continue;
        }
        $patientAppointments = array_values(array_filter(repository_state()['appointments'], static fn(array $row): bool => (int) $row['doctor_id'] === $doctorId && (int) $row['patient_id'] === (int) $patientId));
        $dates = array_map(static fn(array $row): string => (string) (repository_slot((int) $row['slot_id'])['slot_start'] ?? ''), $patientAppointments);
        sort($dates);
        $future = array_values(array_filter($dates, static fn(string $date): bool => $date >= now_sql()));
        $past = array_values(array_filter($dates, static fn(string $date): bool => $date < now_sql()));
        $patient['last_appointment'] = $past ? end($past) : null;
        $patient['next_appointment'] = $future[0] ?? null;
        $patient['total_appointments'] = count($patientAppointments);
        $rows[] = $patient;
    }
    usort($rows, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
    return $rows;
}

function patient_detail_for_doctor(int $doctorId, int $patientId): ?array
{
    foreach (doctor_patient_list($doctorId) as $patient) {
        if ((int) $patient['id'] === $patientId) {
            return $patient;
        }
    }
    return null;
}

function medical_record_by_appointment(int $appointmentId): ?array
{
    foreach (repository_state()['medical_records'] as $record) {
        if ((int) $record['appointment_id'] === $appointmentId) {
            return $record;
        }
    }
    return null;
}

function medical_records_for_patient(int $patientId, ?int $doctorId = null): array
{
    $rows = [];
    foreach (repository_state()['medical_records'] as $record) {
        if ((int) $record['patient_id'] !== $patientId || ($doctorId && (int) $record['doctor_id'] !== $doctorId)) {
            continue;
        }
        $appointment = repository_find_appointment((int) $record['appointment_id']);
        $slot = $appointment ? repository_slot((int) $appointment['slot_id']) : null;
        $doctor = $appointment ? repository_find_doctor((int) $appointment['doctor_id']) : null;
        $doctorUser = $doctor ? repository_find_user((int) $doctor['user_id']) : null;
        $rows[] = array_merge($record, ['appointment_status' => $appointment['status'] ?? null, 'slot_start' => $slot['slot_start'] ?? null, 'doctor_name' => $doctorUser['name'] ?? '']);
    }
    usort($rows, static fn(array $a, array $b): int => strcmp((string) $b['slot_start'], (string) $a['slot_start']));
    return $rows;
}

function save_medical_record(int $appointmentId, array $actor, array $data): void
{
    $appointment = repository_find_appointment($appointmentId);
    if (!$appointment || !in_array($appointment['status'], ['pending', 'confirmed'], true)) {
        throw new RuntimeException('Consulta não pode ser encerrada.');
    }
    $doctor = doctor_by_user((int) $actor['id']);
    if (!$doctor || (int) $appointment['doctor_id'] !== (int) $doctor['id']) {
        abort_forbidden();
    }
    $state = repository_state();
    $record = ['appointment_id' => $appointmentId, 'patient_id' => $appointment['patient_id'], 'doctor_id' => $appointment['doctor_id'], 'created_by' => $actor['id'], 'created_at' => now_sql(), 'updated_at' => now_sql()];
    foreach (['weight', 'height', 'temperature', 'heart_rate', 'blood_pressure', 'symptoms', 'diagnosis', 'prescription', 'follow_up'] as $field) {
        $record[$field] = $data[$field] ?: null;
    }
    $found = false;
    foreach ($state['medical_records'] as $index => $existing) {
        if ((int) $existing['appointment_id'] === $appointmentId) {
            $record = array_merge($existing, $record);
            $state['medical_records'][$index] = $record;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $state['medical_records'][] = array_merge(['id' => repository_next_id($state['medical_records'])], $record);
    }
    foreach ($state['appointments'] as &$row) {
        if ((int) $row['id'] === $appointmentId) {
            $row['status'] = 'completed';
            $row['completed_at'] = now_sql();
            $row['updated_at'] = now_sql();
        }
    }
    unset($row);
    foreach ($state['payments'] as &$payment) {
        if ((int) $payment['appointment_id'] === $appointmentId && $payment['status'] === 'pending') {
            $payment['status'] = 'paid';
            $payment['paid_at'] = now_sql();
            $payment['updated_at'] = now_sql();
        }
    }
    unset($payment);
    repository_save($state);
}

function age_from_birth(?string $birthDate): string
{
    return $birthDate ? (new DateTime($birthDate))->diff(new DateTime())->y . ' anos' : '-';
}

function doctor_schedules(int $doctorId): array
{
    $rows = array_values(array_filter(repository_state()['doctor_schedules'], static fn(array $row): bool => (int) $row['doctor_id'] === $doctorId && (int) $row['active'] === 1));
    usort($rows, static fn(array $a, array $b): int => [$a['weekday'], $a['start_time']] <=> [$b['weekday'], $b['start_time']]);
    return $rows;
}

function doctor_blocks(int $doctorId): array
{
    $today = current_date_value();
    $rows = array_values(array_filter(repository_state()['schedule_blocks'], static fn(array $row): bool => (int) $row['doctor_id'] === $doctorId && $row['block_date'] >= $today));
    usort($rows, static fn(array $a, array $b): int => [$a['block_date'], $a['start_time']] <=> [$b['block_date'], $b['start_time']]);
    return $rows;
}

function notifications_for_user(int $userId): array
{
    $rows = [];
    foreach (repository_state()['notifications'] as $notification) {
        if ((int) $notification['user_id'] !== $userId) {
            continue;
        }
        $appointment = $notification['appointment_id'] ? repository_find_appointment((int) $notification['appointment_id']) : null;
        $slot = $appointment ? repository_slot((int) $appointment['slot_id']) : null;
        $rows[] = array_merge($notification, ['appointment_status' => $appointment['status'] ?? null, 'slot_start' => $slot['slot_start'] ?? null]);
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));
    return array_slice($rows, 0, 80);
}

function unread_notifications_count(int $userId): int
{
    return count(array_filter(repository_state()['notifications'], static fn(array $row): bool => (int) $row['user_id'] === $userId && empty($row['read_at'])));
}

function mark_notifications_read(int $userId): void
{
    $state = repository_state();
    foreach ($state['notifications'] as &$row) {
        if ((int) $row['user_id'] === $userId && empty($row['read_at'])) {
            $row['read_at'] = now_sql();
        }
    }
    unset($row);
    repository_save($state);
}

function calendar_appointments(int $year, int $month): array
{
    $prefix = sprintf('%04d-%02d-', $year, $month);
    $days = [];
    foreach (appointments_for_admin() as $appointment) {
        if (str_starts_with((string) $appointment['slot_start'], $prefix)) {
            $day = (int) (new DateTime($appointment['slot_start']))->format('j');
            $days[$day][] = $appointment;
        }
    }
    return $days;
}

function payments_for_admin(array $filters = []): array
{
    $rows = [];
    foreach (repository_state()['appointments'] as $appointment) {
        $row = repository_appointment_row($appointment);
        if (!empty($filters['status']) && $row['payment_status'] !== $filters['status']) {
            continue;
        }
        if (!empty($filters['date']) && substr((string) $row['slot_start'], 0, 10) !== $filters['date']) {
            continue;
        }
        $row['payment_status'] = $row['payment_status'];
        $row['method'] = $row['payment_method'];
        $rows[] = $row;
    }
    usort($rows, static fn(array $a, array $b): int => strcmp($b['slot_start'], $a['slot_start']));
    return array_slice($rows, 0, 200);
}

function payment_summary(): array
{
    $paidToday = 0.0;
    $pendingTotal = 0.0;
    $paidTotal = 0.0;
    $pendingCount = 0;
    foreach (repository_state()['payments'] as $payment) {
        $amount = (float) $payment['amount'];
        if ($payment['status'] === 'pending') {
            $pendingTotal += $amount;
            $pendingCount++;
        }
        if ($payment['status'] === 'paid') {
            $paidTotal += $amount;
            if (substr((string) ($payment['paid_at'] ?? ''), 0, 10) === current_date_value()) {
                $paidToday += $amount;
            }
        }
    }
    return ['paid_today' => $paidToday, 'pending_total' => $pendingTotal, 'paid_total' => $paidTotal, 'pending_count' => $pendingCount];
}

function set_payment_status(int $appointmentId, string $status, string $method = ''): void
{
    if (!in_array($status, ['pending', 'paid', 'cancelled'], true)) {
        throw new RuntimeException('Status de pagamento inválido.');
    }
    $appointment = repository_find_appointment($appointmentId);
    if (!$appointment) {
        throw new RuntimeException('Consulta não encontrada.');
    }
    $state = repository_state();
    $found = false;
    foreach ($state['payments'] as $index => $payment) {
        if ((int) $payment['appointment_id'] === $appointmentId) {
            $state['payments'][$index] = array_merge($payment, ['method' => $method ?: null, 'status' => $status, 'paid_at' => $status === 'paid' ? ($payment['paid_at'] ?: now_sql()) : null, 'updated_at' => now_sql()]);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $state['payments'][] = ['id' => repository_next_id($state['payments']), 'appointment_id' => $appointmentId, 'patient_id' => $appointment['patient_id'], 'clinic_id' => $appointment['clinic_id'], 'amount' => 250.00, 'method' => $method ?: null, 'status' => $status, 'paid_at' => $status === 'paid' ? now_sql() : null, 'created_at' => now_sql(), 'updated_at' => now_sql()];
    }
    repository_save($state);
}

function report_data(string $fromDate, string $toDate): array
{
    ensure_slots_for_all($fromDate, $toDate);
    $appointments = array_values(array_filter(appointments_for_admin(), static fn(array $row): bool => substr((string) $row['slot_start'], 0, 10) >= $fromDate && substr((string) $row['slot_start'], 0, 10) <= $toDate));
    $summary = ['total' => count($appointments), 'completed' => 0, 'no_shows' => 0, 'active' => 0];
    foreach ($appointments as $row) {
        if ($row['status'] === 'completed') $summary['completed']++;
        if ($row['status'] === 'no_show') $summary['no_shows']++;
        if (in_array($row['status'], ['pending', 'confirmed'], true)) $summary['active']++;
    }
    $slots = array_values(array_filter(repository_state()['appointment_slots'], static fn(array $row): bool => substr($row['slot_start'], 0, 10) >= $fromDate && substr($row['slot_start'], 0, 10) <= $toDate));
    $slotSummary = ['total_slots' => count($slots), 'booked_slots' => count(array_filter($slots, static fn(array $row): bool => $row['status'] === 'booked')), 'blocked_slots' => count(array_filter($slots, static fn(array $row): bool => $row['status'] === 'blocked'))];
    $byDoctor = [];
    foreach (active_doctors() as $doctor) {
        $doctorAppointments = array_values(array_filter($appointments, static fn(array $row): bool => (int) $row['doctor_id'] === (int) $doctor['id']));
        $byDoctor[] = ['doctor_name' => $doctor['name'], 'total' => count($doctorAppointments), 'no_shows' => count(array_filter($doctorAppointments, static fn(array $row): bool => $row['status'] === 'no_show')), 'completed' => count(array_filter($doctorAppointments, static fn(array $row): bool => $row['status'] === 'completed'))];
    }
    return ['summary' => $summary, 'slots' => $slotSummary, 'by_doctor' => $byDoctor];
}

function dashboard_metrics(): array
{
    $today = current_date_value();
    $appointments = appointments_for_admin();
    return [
        'today' => count(array_filter($appointments, static fn(array $row): bool => substr((string) $row['slot_start'], 0, 10) === $today && in_array($row['status'], ['pending', 'confirmed'], true))),
        'pending' => count(array_filter($appointments, static fn(array $row): bool => $row['status'] === 'pending')),
        'patients' => count(array_filter(repository_state()['users'], static fn(array $row): bool => $row['role'] === 'patient' && $row['status'] === 'active')),
        'doctors' => count(array_filter(repository_state()['doctors'], static fn(array $row): bool => (int) $row['active'] === 1)),
    ];
}

function create_due_reminders(int $hoursAhead = 24): int
{
    $from = (new DateTime())->modify('+' . max(0, $hoursAhead - 1) . ' hours');
    $to = (new DateTime())->modify('+' . ($hoursAhead + 1) . ' hours');
    $created = 0;
    foreach (appointments_for_admin() as $appointment) {
        $slot = new DateTime($appointment['slot_start']);
        if (!in_array($appointment['status'], ['pending', 'confirmed'], true) || $slot < $from || $slot > $to) {
            continue;
        }
        create_notification((int) $appointment['doctor_id'], (int) $appointment['id'], 'in_app', 'Lembrete de consulta', 'Você tem uma consulta marcada para ' . format_datetime($appointment['slot_start']) . '.');
        $created++;
    }
    return $created;
}
