<?php

const REPOSITORY_TABLES = [
    'clinics', 'specialties', 'users', 'doctors', 'doctor_schedules',
    'schedule_blocks', 'appointment_slots', 'appointments',
    'medical_records', 'payments', 'notifications', 'password_resets',
];

function repo_assert_table(string $table): void
{
    if (!in_array($table, REPOSITORY_TABLES, true)) {
        throw new RuntimeException('Tabela desconhecida: ' . $table);
    }
}

function repository_find(string $table, int $id): ?array
{
    repo_assert_table($table);

    $stmt = db()->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");

    $stmt->execute([$id]);

    $row = $stmt->fetch();

    return $row ?: null;
}

function repository_append(string $table, array $data): int
{
    repo_assert_table($table);

    unset($data['id']);

    $columns = array_keys($data);

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));

    $columnList = implode('`, `', $columns);

    $stmt = db()->prepare("INSERT INTO `$table` (`$columnList`) VALUES ($placeholders)");

    $stmt->execute(array_values($data));

    return (int) db()->lastInsertId();
}

function repository_replace(string $table, int $id, array $data): void
{
    repo_assert_table($table);

    unset($data['id']);

    if (!$data) {
        return;
    }

    $set = implode(', ', array_map(static fn(string $c): string => "`$c` = ?", array_keys($data)));

    $stmt = db()->prepare("UPDATE `$table` SET $set WHERE id = ?");

    $stmt->execute([...array_values($data), $id]);
}

function repository_find_user(int $id): ?array
{
    return repository_find('users', $id);
}

function repository_user_with_clinic(array $user): array
{
    $clinic = !empty($user['clinic_id']) ? repository_find('clinics', (int) $user['clinic_id']) : null;

    $user['clinic_name'] = $clinic['name'] ?? null;

    return $user;
}

function repository_update_user(int $id, array $changes): void
{
    $changes['updated_at'] = now_sql();

    repository_replace('users', $id, $changes);
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');

    $stmt->execute([strtolower($email)]);

    $row = $stmt->fetch();

    return $row ?: null;
}

function email_in_use(string $email): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)');

    $stmt->execute([$email]);

    return (int) $stmt->fetchColumn() > 0;
}

function next_appointment_for_patient(int $pacienteId): ?array
{
    $sql = "
        SELECT
            a.id,
            a.status,
            a.modality,
            slot.slot_start,
            slot.slot_end,
            medico.name AS doctor_name,
            esp.name AS specialty_name,
            clin.name AS clinic_name
        FROM appointments a
        JOIN appointment_slots slot ON slot.id = a.slot_id
        JOIN doctors doc ON doc.id = a.doctor_id
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = a.specialty_id
        JOIN clinics clin ON clin.id = a.clinic_id
        WHERE a.patient_id = ?
          AND a.status IN ('pending', 'confirmed')
          AND slot.slot_start >= ?
        ORDER BY slot.slot_start ASC
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);

    $stmt->execute([$pacienteId, now_sql()]);

    $linha = $stmt->fetch();

    return $linha ?: null;
}

function appointments_for_patient(int $pacienteId, array $statuses, string $ordem = 'ASC', bool $apenasFuturas = false, string $ordenarPor = 'slot.slot_start'): array
{
    if (!in_array($ordenarPor, ['slot.slot_start', 'a.updated_at'], true)) {
        $ordenarPor = 'slot.slot_start';
    }

    $ordem = strtoupper($ordem) === 'DESC' ? 'DESC' : 'ASC';

    $interrogacoes = implode(',', array_fill(0, count($statuses), '?'));

    $sql = "
        SELECT
            a.id,
            a.status,
            a.modality,
            slot.slot_start,
            slot.slot_end,
            medico.name AS doctor_name,
            esp.name AS specialty_name,
            clin.name AS clinic_name
        FROM appointments a
        JOIN appointment_slots slot ON slot.id = a.slot_id
        JOIN doctors doc ON doc.id = a.doctor_id
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = a.specialty_id
        JOIN clinics clin ON clin.id = a.clinic_id
        WHERE a.patient_id = ?
          AND a.status IN ($interrogacoes)
        ORDER BY $ordenarPor $ordem
    ";

    $stmt = db()->prepare($sql);

    $stmt->execute([$pacienteId, ...$statuses]);

    return $stmt->fetchAll();
}

function cancel_appointment_patient(int $consultaId, int $pacienteId): void
{
    $sql = "
        SELECT a.id, a.status, a.patient_id, a.slot_id, a.doctor_id, a.clinic_id, slot.slot_start
        FROM appointments a
        JOIN appointment_slots slot ON slot.id = a.slot_id
        WHERE a.id = ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$consultaId]);
    $consulta = $stmt->fetch();

    if (!$consulta) {
        throw new RuntimeException('Consulta não encontrada.');
    }

    if ((int) $consulta['patient_id'] !== $pacienteId) {
        throw new RuntimeException('Você não tem permissão para cancelar essa consulta.');
    }

    if (!in_array($consulta['status'], ['pending', 'confirmed'], true)) {
        throw new RuntimeException('Essa consulta não pode mais ser cancelada.');
    }

    $agora = new DateTime();
    $inicioConsulta = new DateTime($consulta['slot_start']);

    $horasAteConsulta = ($inicioConsulta->getTimestamp() - $agora->getTimestamp()) / 3600;

    if ($horasAteConsulta < 24) {
        throw new RuntimeException('Só é possível cancelar com pelo menos 24 horas de antecedência.');
    }

    $pdo = db();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        $stmt->execute(['cancelled', $consultaId]);

        $stmt = $pdo->prepare('UPDATE appointment_slots SET status = ? WHERE id = ?');
        $stmt->execute(['available', $consulta['slot_id']]);

        notificar_paciente($pacienteId, $consultaId, 'Consulta cancelada', 'Sua consulta foi cancelada.');

        notificar_equipe_clinica(
            (int) $consulta['doctor_id'],
            (int) $consulta['clinic_id'],
            $consultaId,
            'Consulta cancelada pelo paciente',
            'O paciente cancelou a consulta pelo site.'
        );

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();

        throw new RuntimeException('Não foi possível cancelar a consulta. Tente novamente.');
    }
}

function active_clinics_with_doctor_count(?string $busca = null): array
{
    $sql = "
        SELECT clin.id AS clinic_id, clin.name AS clinic_name,
               clin.address AS clinic_address, clin.phone AS clinic_phone,
               COUNT(doc.id) AS total_medicos,
               GROUP_CONCAT(DISTINCT medico.name SEPARATOR ', ') AS nomes_medicos,
               GROUP_CONCAT(DISTINCT esp.name SEPARATOR ', ') AS nomes_especialidades,
               EXISTS (
                   SELECT 1
                   FROM appointment_slots vaga
                   JOIN doctors doc_vaga ON doc_vaga.id = vaga.doctor_id
                   WHERE doc_vaga.clinic_id = clin.id
                     AND doc_vaga.active = 1
                     AND vaga.status = 'available'
                     AND vaga.slot_start >= NOW()
               ) AS tem_horario_disponivel
        FROM clinics clin
        JOIN doctors doc ON doc.clinic_id = clin.id AND doc.active = 1
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
    ";

    $params = [];

    if ($busca !== null && trim($busca) !== '') {
        $sql .= ' WHERE clin.name LIKE ?';
        $params[] = '%' . trim($busca) . '%';
    }

    $sql .= ' GROUP BY clin.id ORDER BY clin.name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function active_specialties(): array
{
    $sql = "
        SELECT DISTINCT esp.id AS specialty_id, esp.name AS specialty_name
        FROM specialties esp
        JOIN doctors doc ON doc.specialty_id = esp.id AND doc.active = 1
        ORDER BY esp.name ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function find_clinic_details(int $clinicaId): ?array
{
    $stmt = db()->prepare('
        SELECT id AS clinic_id, name AS clinic_name, address AS clinic_address, phone AS clinic_phone
        FROM clinics
        WHERE id = ?
    ');
    $stmt->execute([$clinicaId]);
    $linha = $stmt->fetch();
    return $linha ?: null;
}

function active_doctors_with_details(int $clinicaId, ?string $busca = null): array
{
    $sql = "
        SELECT doc.id AS doctor_id, medico.name AS doctor_name,
               esp.name AS specialty_name, clin.name AS clinic_name,
               doc.crm AS doctor_crm
        FROM doctors doc
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
        JOIN clinics clin ON clin.id = doc.clinic_id
        WHERE doc.active = 1 AND doc.clinic_id = ?
    ";
    $params = [$clinicaId];

    if ($busca !== null && trim($busca) !== '') {
        $sql .= ' AND medico.name LIKE ?';
        $params[] = '%' . trim($busca) . '%';
    }

    $sql .= ' ORDER BY medico.name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_doctor_details(int $medicoId): ?array
{
    $sql = "
        SELECT doc.id AS doctor_id, medico.name AS doctor_name,
               esp.name AS specialty_name, clin.name AS clinic_name,
               clin.id AS clinic_id
        FROM doctors doc
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
        JOIN clinics clin ON clin.id = doc.clinic_id
        WHERE doc.id = ? AND doc.active = 1
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$medicoId]);
    $linha = $stmt->fetch();
    return $linha ?: null;
}

function ensure_slots_for_doctor(int $doctorId, string $fromDate, string $toDate): void
{
    $medico = repository_find('doctors', $doctorId);
    if (!$medico || !(int) ($medico['active'] ?? 0)) {
        return;
    }

    $limiteInicial = $fromDate . ' 00:00:00';
    $limiteFinal = (new DateTime($toDate))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

    $stmtLimpeza = db()->prepare(
        "DELETE FROM appointment_slots
         WHERE doctor_id = ?
           AND slot_start >= ?
           AND slot_start < ?
           AND status IN ('available', 'blocked')
           AND NOT EXISTS (
               SELECT 1 FROM appointments ap WHERE ap.slot_id = appointment_slots.id
           )"
    );
    $stmtLimpeza->execute([$doctorId, $limiteInicial, $limiteFinal]);

    $stmtGrade = db()->prepare('SELECT * FROM doctor_schedules WHERE doctor_id = ? AND active = 1');
    $stmtGrade->execute([$doctorId]);
    $grades = $stmtGrade->fetchAll();

    $stmtBloqueios = db()->prepare('SELECT * FROM schedule_blocks WHERE doctor_id = ? AND block_date >= ? AND block_date <= ?');
    $stmtBloqueios->execute([$doctorId, $fromDate, $toDate]);
    $bloqueios = $stmtBloqueios->fetchAll();

    $stmtExistentes = db()->prepare('SELECT slot_start FROM appointment_slots WHERE doctor_id = ?');
    $stmtExistentes->execute([$doctorId]);
    $existentes = array_fill_keys(array_column($stmtExistentes->fetchAll(), 'slot_start'), true);

    $duracao = max(10, (int) ($medico['appointment_duration'] ?? 30));

    $dataAtual = new DateTime($fromDate);
    $dataFinal = new DateTime($limiteFinal);
    $novosSlots = [];

    while ($dataAtual < $dataFinal) {
        $diaDaSemana = (int) $dataAtual->format('w');

        foreach ($grades as $grade) {
            if ((int) $grade['weekday'] !== $diaDaSemana) {
                continue;
            }

            $inicioSlot = new DateTime($dataAtual->format('Y-m-d') . ' ' . $grade['start_time']);
            $limite = new DateTime($dataAtual->format('Y-m-d') . ' ' . $grade['end_time']);

            while ($inicioSlot < $limite) {
                $fimSlot = (clone $inicioSlot)->modify('+' . $duracao . ' minutes');
                if ($fimSlot > $limite) {
                    break;
                }

                $inicio = $inicioSlot->format('Y-m-d H:i:s');

                if (!isset($existentes[$inicio])) {
                    $bloqueioEncontrado = null;
                    foreach ($bloqueios as $bloqueio) {
                        $inicioBloqueio = new DateTime($bloqueio['block_date'] . ' ' . $bloqueio['start_time']);
                        $fimBloqueio = new DateTime($bloqueio['block_date'] . ' ' . $bloqueio['end_time']);
                        if ($inicioSlot < $fimBloqueio && $fimSlot > $inicioBloqueio) {
                            $bloqueioEncontrado = $bloqueio;
                            break;
                        }
                    }

                    $novosSlots[] = [
                        'doctor_id' => $doctorId,
                        'slot_start' => $inicio,
                        'slot_end' => $fimSlot->format('Y-m-d H:i:s'),
                        'status' => $bloqueioEncontrado ? 'blocked' : 'available',
                        'block_reason' => $bloqueioEncontrado['reason'] ?? null,
                    ];
                    $existentes[$inicio] = true;
                }

                $inicioSlot = $fimSlot;
            }
        }

        $dataAtual->modify('+1 day');
    }

    foreach ($novosSlots as $slot) {
        repository_append('appointment_slots', $slot);
    }
}

function available_slots_for_doctor(int $medicoId): array
{
    ensure_slots_for_doctor($medicoId, date('Y-m-d'), date('Y-m-d', strtotime('+90 days')));

    $sql = "
        SELECT id, slot_start, slot_end
        FROM appointment_slots
        WHERE doctor_id = ? AND status = 'available' AND slot_start >= NOW()
        ORDER BY slot_start ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$medicoId]);
    return $stmt->fetchAll();
}

function search_doctors_and_clinics(string $busca): array
{
    $termo = '%' . trim($busca) . '%';

    $stmtMedicos = db()->prepare("
        SELECT doc.id AS doctor_id, medico.name AS doctor_name,
               esp.name AS specialty_name, clin.name AS clinic_name,
               clin.id AS clinic_id, doc.crm AS doctor_crm
        FROM doctors doc
        JOIN users medico ON medico.id = doc.user_id
        JOIN specialties esp ON esp.id = doc.specialty_id
        JOIN clinics clin ON clin.id = doc.clinic_id
        WHERE doc.active = 1
          AND (medico.name LIKE ? OR esp.name LIKE ?)
        ORDER BY medico.name ASC
    ");
    $stmtMedicos->execute([$termo, $termo]);

    $stmtClinicas = db()->prepare("
        SELECT clin.id AS clinic_id, clin.name AS clinic_name,
               clin.address AS clinic_address
        FROM clinics clin
        JOIN doctors doc ON doc.clinic_id = clin.id AND doc.active = 1
        WHERE clin.name LIKE ?
        GROUP BY clin.id
        ORDER BY clin.name ASC
    ");
    $stmtClinicas->execute([$termo]);

    return [
        'medicos' => $stmtMedicos->fetchAll(),
        'clinicas' => $stmtClinicas->fetchAll(),
    ];
}

function book_appointment_patient(int $pacienteId, int $slotId): void
{
    $pdo = db();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('
            SELECT id, doctor_id, status
            FROM appointment_slots
            WHERE id = ?
            FOR UPDATE
        ');
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        if (!$slot) {
            throw new RuntimeException('Horário não encontrado.');
        }

        if ($slot['status'] !== 'available') {
            throw new RuntimeException('Esse horário não está mais disponível. Escolha outro.');
        }

        $stmtMedico = $pdo->prepare('SELECT clinic_id, specialty_id, user_id FROM doctors WHERE id = ?');
        $stmtMedico->execute([$slot['doctor_id']]);
        $medico = $stmtMedico->fetch();

        if (!$medico) {
            throw new RuntimeException('Médico não encontrado.');
        }

        $stmtExistente = $pdo->prepare('SELECT id FROM appointments WHERE slot_id = ? FOR UPDATE');
        $stmtExistente->execute([$slotId]);
        $consultaAntiga = $stmtExistente->fetch();

        if ($consultaAntiga) {
            $stmtUpdate = $pdo->prepare('
                UPDATE appointments
                SET patient_id = ?, doctor_id = ?, clinic_id = ?, specialty_id = ?,
                    status = ?, modality = ?
                WHERE id = ?
            ');
            $stmtUpdate->execute([
                $pacienteId,
                $slot['doctor_id'],
                $medico['clinic_id'],
                $medico['specialty_id'],
                'confirmed',
                'presencial',
                $consultaAntiga['id'],
            ]);

            $appointmentId = (int) $consultaAntiga['id'];
        } else {
            $stmtInsert = $pdo->prepare('
                INSERT INTO appointments (slot_id, patient_id, doctor_id, clinic_id, specialty_id, status, modality)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmtInsert->execute([
                $slotId,
                $pacienteId,
                $slot['doctor_id'],
                $medico['clinic_id'],
                $medico['specialty_id'],
                'confirmed',
                'presencial',
            ]);

            $appointmentId = (int) $pdo->lastInsertId();
        }

        $stmtOcupa = $pdo->prepare('UPDATE appointment_slots SET status = ? WHERE id = ?');
        $stmtOcupa->execute(['booked', $slotId]);

        notificar_paciente($pacienteId, $appointmentId, 'Consulta confirmada', 'Sua consulta foi confirmada.');

        notificar_equipe_clinica(
            (int) $slot['doctor_id'],
            (int) $medico['clinic_id'],
            $appointmentId,
            'Nova consulta marcada',
            'Um paciente marcou uma consulta pelo site.'
        );

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();

        if ($e instanceof RuntimeException) {
            throw $e;
        }

        throw new RuntimeException('Não foi possível marcar a consulta. Tente novamente.');
    }
}

function update_patient_profile(int $pacienteId, array $dados): void
{
    $nome = trim((string) ($dados['name'] ?? ''));
    $email = trim((string) ($dados['email'] ?? ''));
    $telefone = trim((string) ($dados['phone'] ?? ''));
    $documento = trim((string) ($dados['document'] ?? ''));
    $dataNascimento = trim((string) ($dados['birth_date'] ?? ''));
    $endereco = trim((string) ($dados['address'] ?? ''));

    if ($nome === '') {
        throw new RuntimeException('Informe seu nome.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail válido.');
    }

    $usuarioComEsseEmail = find_user_by_email($email);

    if ($usuarioComEsseEmail && (int) $usuarioComEsseEmail['id'] !== $pacienteId) {
        throw new RuntimeException('Esse e-mail já está sendo usado por outra conta.');
    }

    $sql = "
        UPDATE users
        SET name = ?, email = ?, phone = ?, document = ?, birth_date = ?, address = ?
        WHERE id = ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([
        $nome,
        $email,
        $telefone !== '' ? $telefone : null,
        $documento !== '' ? $documento : null,
        $dataNascimento !== '' ? $dataNascimento : null,
        $endereco !== '' ? $endereco : null,
        $pacienteId,
    ]);
}

function accept_terms(int $userId): void
{
    repository_update_user($userId, ['terms_accepted' => 1]);
}

function mark_tutorial_seen(int $userId): void
{
    repository_update_user($userId, ['tutorial_seen' => 1]);
}

function notifications_for_patient(int $pacienteId): array
{
    $sql = "
        SELECT
            n.id,
            n.title,
            n.message,
            n.status,
            n.sent_at,
            medico.name AS doctor_name,
            clin.name AS clinic_name,
            slot.slot_start
        FROM notifications n
        LEFT JOIN appointments a ON a.id = n.appointment_id
        LEFT JOIN doctors doc ON doc.id = a.doctor_id
        LEFT JOIN users medico ON medico.id = doc.user_id
        LEFT JOIN clinics clin ON clin.id = a.clinic_id
        LEFT JOIN appointment_slots slot ON slot.id = a.slot_id
        WHERE n.user_id = ? AND n.status IN ('sent', 'read')
        ORDER BY n.sent_at DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$pacienteId]);
    return $stmt->fetchAll();
}

function mark_notifications_as_read(int $pacienteId): void
{
    $sql = "UPDATE notifications SET status = 'read', read_at = NOW() WHERE user_id = ? AND status = 'sent'";
    $stmt = db()->prepare($sql);
    $stmt->execute([$pacienteId]);
}

function notificar_equipe_clinica(int $doctorId, ?int $clinicId, ?int $appointmentId, string $titulo, string $mensagem): void
{
    $destinatarios = [];

    $stmt = db()->prepare('SELECT user_id, clinic_id FROM doctors WHERE id = ?');
    $stmt->execute([$doctorId]);
    $medico = $stmt->fetch();
    if ($medico) {
        $destinatarios[] = (int) $medico['user_id'];
        if (!$clinicId) {
            $clinicId = (int) $medico['clinic_id'];
        }
    }

    $stmt = db()->prepare(
        "SELECT id FROM users
         WHERE role = 'admin' AND status = 'active'
           AND (clinic_id = ? OR is_super_admin = 1)"
    );
    $stmt->execute([$clinicId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
        $destinatarios[] = (int) $adminId;
    }

    foreach (array_unique($destinatarios) as $userId) {
        notificar_paciente($userId, $appointmentId, $titulo, $mensagem);
    }
}

function notificar_paciente(int $pacienteId, ?int $appointmentId, string $titulo, string $mensagem): void
{
    repository_append('notifications', [
        'user_id' => $pacienteId,
        'appointment_id' => $appointmentId,
        'type' => 'in_app',
        'title' => $titulo,
        'message' => $mensagem,
        'status' => 'sent',
        'send_at' => now_sql(),
        'sent_at' => now_sql(),
        'read_at' => null,
        'created_at' => now_sql(),
    ]);
}

function unread_notifications_count(int $pacienteId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'sent'");
    $stmt->execute([$pacienteId]);
    return (int) $stmt->fetchColumn();
}