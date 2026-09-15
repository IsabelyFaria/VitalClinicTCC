<?php

declare(strict_types=1);

// Inicia o buffer de saída ANTES de qualquer outro código rodar. Isso
// permite que send_json() (em app/helpers.php) descarte com segurança
// qualquer coisa impressa por engano antes da resposta JSON (avisos do
// PHP, espaço em branco antes de alguma tag <?php, etc.) — sem isso,
// esse tipo de "vazamento" quebra silenciosamente o fetch() do
// formulário de nova consulta, fazendo o JSON.parse() falhar no
// navegador mesmo com a internet funcionando normalmente.
ob_start();

session_start();

require __DIR__ . '/app/helpers.php';

date_default_timezone_set(config('timezone') ?: 'America/Sao_Paulo');

require __DIR__ . '/app/db.php';
require __DIR__ . '/app/api_client.php';
require __DIR__ . '/app/repository.php';
require __DIR__ . '/app/mailer.php';
require __DIR__ . '/app/auth.php';

foreach ([
    'pages/auth/login.php',
    'pages/auth/recuperar_senha.php',
    'pages/auth/aceitar_convite.php',
    'pages/admin/dashboard.php',
    'pages/admin/calendario.php',
    'pages/admin/consultas.php',
    'pages/admin/pacientes.php',
    'pages/admin/medicos.php',
    'pages/admin/relatorios.php',
    'pages/admin/convites.php',
    'pages/medico/dashboard.php',
    'pages/medico/calendario.php',
    'pages/medico/consultas.php',
    'pages/medico/pacientes.php',
    'pages/medico/prontuario.php',
    'pages/medico/historico.php',
] as $pageFile) {
    require __DIR__ . '/' . $pageFile;
}

try {
    run_app();
} catch (RuntimeException $e) {
    render_install_error($e);
}

function run_app(): void
{
    ensure_runtime_schema();

    if (($_GET['action'] ?? '') === 'slots') {
        slots_endpoint();
        return;
    }

    if (($_GET['action'] ?? '') === 'doctor_weekdays') {
        doctor_weekdays_endpoint();
        return;
    }

    if (($_GET['action'] ?? '') === 'monthly_movement') {
        monthly_movement_endpoint();
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            handle_post();
        } catch (RuntimeException $e) {
            // Erro "esperado" de validação (ex: campo obrigatório
            // faltando, horário indisponível). O redirect() abaixo já
            // detecta sozinho se a requisição é AJAX e responde com
            // JSON + status 400 nesse caso, sem precisar de um branch
            // separado aqui.
            flash('error', $e->getMessage());
            redirect(['page' => $_POST['page_after'] ?? ($_GET['page'] ?? 'dashboard')]);
        } catch (Throwable $e) {
            // Qualquer outro erro não previsto (ex: falha de conexão
            // com o banco, bug de programação) -> HTTP 500. Só entra
            // nesse ramo especial para requisições AJAX, pra não mudar
            // o comportamento de telas tradicionais (que continuam
            // mostrando a tela de erro padrão do PHP durante o
            // desenvolvimento).
            if (is_ajax_request()) {
                send_json(['success' => false, 'message' => 'Erro interno do servidor. Tente novamente em instantes.'], 500);
            }
            throw $e;
        }
    }

    $page = $_GET['page'] ?? 'dashboard';
    $publicPages = ['login', 'forgot_password', 'reset_security_question', 'reset_password', 'accept_invite'];
    $user = current_user();

    if (!$user && !in_array($page, $publicPages, true)) {
        $page = 'login';
    }

    if ($user && in_array($page, $publicPages, true)) {
        $page = 'dashboard';
    }

    // Guarda de fluxo do "Esqueci minha senha": impede acesso direto por
    // URL a uma etapa sem ter concluído a etapa anterior. Precisa ser
    // resolvido aqui (antes de qualquer HTML ser enviado), pois
    // redirect() usa header('Location: ...').
    if (!$user) {
        if ($page === 'reset_security_question' && !password_reset_pending()) {
            flash('error', 'Informe seu e-mail para continuar a recuperação de senha.');
            redirect(['page' => 'forgot_password']);
        }
        if ($page === 'reset_password' && !password_reset_can_set_new_password()) {
            flash('error', 'Confirme a pergunta de segurança antes de definir a nova senha.');
            redirect(['page' => password_reset_pending() ? 'reset_security_question' : 'forgot_password']);
        }
    }

    render_layout($page, $user);
}

function slots_endpoint(): void
{
    $user = current_user();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Nao autenticado.']);
        return;
    }

    $doctorId = (int) ($_GET['doctor_id'] ?? 0);
    $date = $_GET['date'] ?? current_date_value();
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);

    if (!$doctorId || !$dateObj || $dateObj->format('Y-m-d') !== $date) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Dados invalidos.']);
        return;
    }

    // Um admin só pode ver os horários de um médico da própria
    // clínica — impede consultar/agendar horários de outra clínica
    // manipulando o doctor_id direto na URL.
    if ($user['role'] === 'admin') {
        $doctor = repository_find_doctor($doctorId);
        if (!$doctor || !admin_can_access_clinic($user, (int) $doctor['clinic_id'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Nao autorizado.']);
            return;
        }
    }

    $maxDate = (new DateTime())->modify('+' . (int) config('rules.booking_max_days') . ' days');
    if ($dateObj > $maxDate) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Data fora do limite permitido.']);
        return;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'slots' => array_map(static function (array $slot): array {
            return [
                'id' => (int) $slot['id'],
                'time' => format_time($slot['slot_start']),
                'label' => format_time($slot['slot_start']) . ' - ' . format_time($slot['slot_end']),
            ];
        }, available_slots($doctorId, $date)),
    ]);
}

/**
 * Devolve os dias da semana (0=domingo ... 6=sábado) em que o médico
 * tem agenda cadastrada (doctor_schedules). Usado pelo calendário
 * visual do modal "Nova consulta" para esmaecer, com antecedência, os
 * dias em que aquele médico normalmente não atende — antes mesmo de
 * o usuário escolher uma data e disparar a busca de horários daquele
 * dia específico.
 */
function doctor_weekdays_endpoint(): void
{
    $user = current_user();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Nao autenticado.']);
        return;
    }

    $doctorId = (int) ($_GET['doctor_id'] ?? 0);
    if (!$doctorId) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Dados invalidos.']);
        return;
    }

    if ($user['role'] === 'admin') {
        $doctor = repository_find_doctor($doctorId);
        if (!$doctor || !admin_can_access_clinic($user, (int) $doctor['clinic_id'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Nao autorizado.']);
            return;
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['weekdays' => doctor_working_weekdays($doctorId)]);
}

/**
 * Gráfico de "movimentação mensal" da tela de Relatórios: recebe um
 * mês (?month=YYYY-MM, padrão o mês atual) e devolve o total de
 * consultas, o total de faltas e uma classificação de movimentação
 * (Alta/Boa/Baixa), calculada a partir dos limites configurados em
 * app/config.php (rules.movement_low / rules.movement_high).
 * Reaproveita report_data() — a mesma função usada no resto da tela —
 * só que sempre para o intervalo de um mês inteiro.
 */
function monthly_movement_endpoint(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Nao autorizado.']);
        return;
    }

    $month = (string) ($_GET['month'] ?? '');
    $monthObj = DateTime::createFromFormat('Y-m-d', $month . '-01');
    if (!$monthObj || $monthObj->format('Y-m') !== $month) {
        $monthObj = new DateTime('first day of this month');
    }

    $from = $monthObj->format('Y-m-01');
    $to = $monthObj->format('Y-m-t');

    $report = report_data($from, $to, admin_clinic_scope($user));
    $total = (int) ($report['summary']['total'] ?? 0);
    $noShows = (int) ($report['summary']['no_shows'] ?? 0);
    $noShowRate = $total ? round(($noShows / $total) * 100, 1) : 0.0;

    $low = (int) (config('rules.movement_low') ?: 40);
    $high = (int) (config('rules.movement_high') ?: 120);
    if ($total >= $high) {
        $classification = 'high';
        $label = 'Alta movimentação';
    } elseif ($total < $low) {
        $classification = 'low';
        $label = 'Baixa movimentação';
    } else {
        $classification = 'good';
        $label = 'Boa movimentação';
    }

    $monthNames = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];
    $monthLabel = $monthNames[(int) $monthObj->format('n')] . ' de ' . $monthObj->format('Y');

    header('Content-Type: application/json');
    echo json_encode([
        'total' => $total,
        'no_shows' => $noShows,
        'no_show_rate' => $noShowRate,
        'classification' => $classification,
        'classification_label' => $label,
        'month' => $monthObj->format('Y-m'),
        'month_label' => $monthLabel,
        'period_label' => format_date($from) . ' a ' . format_date($to),
        'doctors' => array_map(static function (array $row): array {
            return [
                'name' => $row['doctor_name'],
                'total' => (int) $row['total'],
                'completed' => (int) $row['completed'],
                'no_shows' => (int) $row['no_shows'],
            ];
        }, $report['by_doctor']),
    ]);
}

function handle_post(): void
{
    verify_csrf();

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'login':
            $loginFailure = attempt_login(post_value('email'), (string) ($_POST['password'] ?? ''), post_value('role_context'));
            if ($loginFailure !== null) {
                $loginMessages = [
                    'not_found' => 'Não encontramos uma conta de administrador/médico com esse e-mail (confira também se não é um e-mail de paciente — pacientes não logam neste painel).',
                    'inactive' => 'Esta conta está inativa. Fale com um administrador da clínica.',
                    'wrong_role' => 'Esse e-mail existe, mas não é desse perfil. Tente entrar pelo outro botão (Clínica/Médico).',
                    'wrong_password' => 'Senha incorreta para esse e-mail.',
                ];
                throw new RuntimeException($loginMessages[$loginFailure] ?? 'E-mail ou senha invalidos.');
            }
            flash('success', 'Login realizado.');
            redirect(['page' => 'dashboard']);

        case 'request_password_reset':
            request_password_reset(post_value('email'));
            flash('success', 'Se o e-mail informado estiver cadastrado e tiver uma pergunta de segurança configurada, ela será exibida a seguir.');
            redirect(['page' => 'reset_security_question']);

        case 'verify_security_answer':
            if (!confirm_password_reset_security_answer(post_value('answer'))) {
                throw new RuntimeException('Resposta incorreta ou número de tentativas excedido. Reinicie o processo informando o e-mail novamente.');
            }
            flash('success', 'Resposta confirmada. Cadastre sua nova senha.');
            redirect(['page' => 'reset_password']);

        case 'reset_password':
            complete_password_reset(
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['confirm_password'] ?? '')
            );
            flash('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
            redirect(['page' => 'login']);

        case 'cancel_password_reset':
            clear_password_reset();
            redirect(['page' => 'login']);

        case 'logout':
            logout_user();
            flash('success', 'Sessao encerrada.');
            redirect(['page' => 'login']);

        case 'mark_tutorial_seen':
            $user = require_role(['doctor', 'admin']);
            mark_tutorial_seen((int) $user['id']);
            redirect(['page' => $_GET['page'] ?? 'dashboard']);

        case 'accept_terms':
            $user = require_role(['doctor', 'admin']);
            if (post_value('agree') !== '1') {
                throw new RuntimeException('É necessário marcar a caixa de aceite para continuar.');
            }
            accept_terms((int) $user['id']);
            redirect(['page' => $_GET['page'] ?? 'dashboard']);

        case 'update_staff_profile':
            $user = require_role(['doctor', 'admin']);
            update_staff_profile((int) $user['id'], [
                'name' => post_value('name'),
                'phone' => post_value('phone'),
                'document' => post_value('document'),
                'address' => post_value('address'),
            ]);
            update_own_password(
                (int) $user['id'],
                (string) ($_POST['current_password'] ?? ''),
                (string) ($_POST['new_password'] ?? ''),
                (string) ($_POST['confirm_password'] ?? '')
            );
            // Igual à troca de senha: só grava se uma nova resposta foi
            // digitada, para não sobrescrever a pergunta/resposta já
            // cadastradas sempre que o formulário de perfil é salvo.
            $securityAnswer = (string) ($_POST['security_answer'] ?? '');
            if ($securityAnswer !== '') {
                set_user_security_question(
                    (int) $user['id'],
                    post_value('security_question'),
                    $securityAnswer
                );
            }
            flash('success', 'Perfil atualizado.');
            redirect(['page' => 'profile']);

        case 'cancel':
            $user = require_login();
            cancel_appointment((int) ($_POST['appointment_id'] ?? 0), $user, post_value('reason'));
            flash('success', 'Consulta cancelada.');
            redirect(['page' => $_POST['page_after'] ?? 'appointments']);

        case 'admin_create_appointment':
            $adminUser = require_role('admin');
            $slotId = (int) ($_POST['slot_id'] ?? 0);
            $patientId = (int) ($_POST['patient_id'] ?? 0);
            $doctorId = (int) ($_POST['doctor_id'] ?? 0);

            $patient = repository_find_user($patientId);
            if (!$patient || $patient['role'] !== 'patient' || !admin_can_access_clinic($adminUser, (int) $patient['clinic_id'])) {
                throw new RuntimeException('Selecione um paciente válido.');
            }
            if (!$doctorId) {
                throw new RuntimeException('Selecione o médico.');
            }
            // Confirma que o médico escolhido é mesmo da clínica deste
            // admin — sem isso, seria possível agendar (ou consultar
            // horários de) um médico de outra clínica manipulando o
            // formulário diretamente.
            require_admin_owns_doctor($adminUser, $doctorId);
            if (!$slotId) {
                throw new RuntimeException('Selecione um horário disponível para a consulta.');
            }

            create_appointment(
                $patientId,
                $doctorId,
                $slotId,
                post_value('notes'),
                post_value('modality') === 'teleconsulta' ? 'teleconsulta' : 'presencial',
                'confirmed'
            );
            flash('success', 'Consulta agendada com sucesso.');
            redirect(['page' => 'admin_appointments']);

        case 'admin_create_doctor':
            $adminUser = require_role('admin');
            $doctorData = doctor_form_data();
            if (!empty($adminUser['is_super_admin'])) {
                // Super admin escolhe a clínica no formulário — ainda
                // validamos que é uma clínica real antes de confiar.
                $doctorData['clinic_id'] = repository_find('clinics', $doctorData['clinic_id']) ? $doctorData['clinic_id'] : 0;
            } else {
                // Nunca confia no clinic_id do formulário — um médico
                // cadastrado por um admin comum sempre entra na MESMA
                // clínica desse admin.
                $doctorData['clinic_id'] = (int) $adminUser['clinic_id'];
            }
            create_doctor($doctorData);
            flash('success', 'Medico cadastrado.');
            redirect(['page' => 'admin_doctors']);

        case 'admin_update_doctor':
            $adminUser = require_role('admin');
            $doctorId = (int) ($_POST['doctor_id'] ?? 0);
            require_admin_owns_doctor($adminUser, $doctorId);
            $doctorData = doctor_form_data(false);
            if (!empty($adminUser['is_super_admin'])) {
                $doctorData['clinic_id'] = repository_find('clinics', $doctorData['clinic_id']) ? $doctorData['clinic_id'] : 0;
            } else {
                // Idem: admin comum nunca muda o médico de clínica.
                $doctorData['clinic_id'] = (int) $adminUser['clinic_id'];
            }
            update_doctor($doctorId, $doctorData);
            flash('success', 'Medico atualizado.');
            redirect(['page' => 'admin_doctors']);

        case 'admin_delete_doctor':
            $adminUser = require_role('admin');
            $doctorId = (int) ($_POST['doctor_id'] ?? 0);
            require_admin_owns_doctor($adminUser, $doctorId);
            deactivate_doctor($doctorId);
            flash('success', 'Medico removido da agenda.');
            redirect(['page' => 'admin_doctors']);

        case 'admin_add_schedule':
            $adminUser = require_role('admin');
            $doctorId = (int) ($_POST['doctor_id'] ?? 0);
            require_admin_owns_doctor($adminUser, $doctorId);
            add_schedule([
                'doctor_id' => $doctorId,
                'weekday' => (int) ($_POST['weekday'] ?? 0),
                'start_time' => post_value('start_time'),
                'end_time' => post_value('end_time'),
            ]);
            flash('success', 'Agenda configurada.');
            redirect(['page' => 'admin_doctors']);

        case 'admin_delete_schedule':
            $adminUser = require_role('admin');
            $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
            $scheduleRow = repository_find('doctor_schedules', $scheduleId);
            if (!$scheduleRow) {
                throw new RuntimeException('Horário não encontrado.');
            }
            require_admin_owns_doctor($adminUser, (int) $scheduleRow['doctor_id']);
            delete_schedule($scheduleId);
            flash('success', 'Horario removido.');
            redirect(['page' => 'admin_doctors']);

        case 'admin_add_block':
            $adminUser = require_role('admin');
            $doctorId = (int) ($_POST['doctor_id'] ?? 0);
            require_admin_owns_doctor($adminUser, $doctorId);
            add_block([
                'doctor_id' => $doctorId,
                'block_date' => post_value('block_date'),
                'start_time' => post_value('start_time'),
                'end_time' => post_value('end_time'),
                'reason' => post_value('reason'),
            ]);
            flash('success', 'Bloqueio registrado.');
            redirect(['page' => 'admin_doctors']);

        case 'admin_update_patient':
            $adminUser = require_role('admin');
            $patientId = (int) ($_POST['patient_id'] ?? 0);
            $targetPatient = repository_find_user($patientId);
            if (!$targetPatient || $targetPatient['role'] !== 'patient' || !admin_can_access_clinic($adminUser, (int) $targetPatient['clinic_id'])) {
                // Nunca deixa um admin editar um paciente de outra
                // clínica, mesmo que o ID tenha sido forjado no POST
                // (super admin é a única exceção prevista).
                throw new RuntimeException('Paciente não encontrado.');
            }
            update_patient_admin($patientId, [
                'name' => post_value('name'),
                'phone' => post_value('phone'),
                'document' => post_value('document'),
                'birth_date' => post_value('birth_date'),
                'address' => post_value('address'),
                'status' => post_value('status'),
            ]);
            flash('success', 'Paciente atualizado.');
            redirect(['page' => 'admin_patients', 'patient_id' => $patientId]);

        case 'admin_create_patient':
            $adminUser = require_role('admin');
            $initialPassword = (string) ($_POST['password'] ?? '');
            if ($initialPassword === '') {
                $initialPassword = '123456';
            }
            if (!empty($adminUser['is_super_admin'])) {
                // Super admin escolhe a clínica no formulário — ainda
                // assim validamos que o ID enviado é de uma clínica
                // real, nunca confiando cegamente no POST.
                $chosenClinicId = (int) ($_POST['clinic_id'] ?? 0);
                $patientClinicId = repository_find('clinics', $chosenClinicId) ? $chosenClinicId : 0;
            } else {
                // Admin comum: sempre a própria clínica, nunca o que
                // vier do formulário.
                $patientClinicId = (int) $adminUser['clinic_id'];
            }
            $patientId = register_patient([
                'name' => post_value('name'),
                'email' => post_value('email'),
                'password' => $initialPassword,
                'phone' => post_value('phone'),
                'document' => post_value('document'),
                'birth_date' => post_value('birth_date'),
                'address' => post_value('address'),
                'clinic_id' => $patientClinicId,
            ]);
            flash('success', 'Paciente cadastrado. Senha inicial: ' . $initialPassword);
            redirect(['page' => 'admin_patients', 'patient_id' => $patientId]);

        case 'save_medical_record':
            $user = require_role('doctor');
            save_medical_record((int) ($_POST['appointment_id'] ?? 0), $user, [
                'weight' => post_value('weight'),
                'height' => post_value('height'),
                'temperature' => post_value('temperature'),
                'heart_rate' => post_value('heart_rate'),
                'blood_pressure' => post_value('blood_pressure'),
                'symptoms' => post_value('symptoms'),
                'diagnosis' => post_value('diagnosis'),
                'prescription' => post_value('prescription'),
                'follow_up' => post_value('follow_up'),
            ]);
            flash('success', 'Consulta encerrada e salva no historico.');
            redirect(['page' => 'doctor_detail', 'appointment_id' => (int) ($_POST['appointment_id'] ?? 0)]);

        case 'admin_update_user_role':
            $actor = require_role('admin');
            $targetUserId = (int) ($_POST['user_id'] ?? 0);
            $targetUser = repository_find_user($targetUserId);
            if (!$targetUser || !admin_can_access_clinic($actor, (int) $targetUser['clinic_id'])) {
                // Um admin só pode conceder/revogar acesso ADM de gente
                // da própria clínica (super admin é exceção).
                throw new RuntimeException('Usuário não encontrado.');
            }
            update_user_role(
                $targetUserId,
                post_value('role'),
                (int) $actor['id']
            );
            flash('success', 'Nível de acesso atualizado.');
            redirect(['page' => 'admin_doctors']);

        case 'admin_create_invite':
            $actor = require_role('admin');
            if (empty($actor['is_super_admin'])) {
                abort_forbidden();
            }
            $cnpj = post_value('clinic_cnpj');
            $email = post_value('invitee_email');
            if (trim(post_value('clinic_name')) === '' || trim($cnpj) === '') {
                throw new RuntimeException('Informe o nome e o CNPJ da clínica.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Informe um e-mail válido para o convite.');
            }
            create_admin_invite([
                'clinic_name' => post_value('clinic_name'),
                'clinic_cnpj' => $cnpj,
                'clinic_address' => post_value('clinic_address'),
                'clinic_phone' => post_value('clinic_phone'),
                'clinic_whatsapp' => post_value('clinic_whatsapp'),
                'clinic_email' => post_value('clinic_email'),
                'invitee_email' => strtolower($email),
            ], (int) $actor['id']);
            flash('success', 'Convite gerado. Copie o link abaixo e envie para o administrador da clínica.');
            redirect(['page' => 'admin_invites']);

        case 'admin_revoke_invite':
            $actor = require_role('admin');
            if (empty($actor['is_super_admin'])) {
                abort_forbidden();
            }
            revoke_admin_invite((int) ($_POST['invite_id'] ?? 0));
            flash('success', 'Convite revogado.');
            redirect(['page' => 'admin_invites']);

        case 'accept_invite':
            // Ação pública (quem está abrindo o link ainda não tem
            // conta) — a validação de verdade do token acontece dentro
            // de complete_admin_invite(), não aqui. Trata o erro aqui
            // mesmo (em vez de deixar subir pro catch genérico lá em
            // cima) porque aquele só sabe redirecionar de volta pra
            // ?page=X — perderia o token da URL, e a pessoa cairia de
            // novo na tela de "link inválido" mesmo com um convite
            // válido, só por ter digitado uma senha curta, por exemplo.
            $token = (string) ($_POST['token'] ?? '');
            try {
                $result = complete_admin_invite(
                    $token,
                    post_value('name'),
                    (string) ($_POST['password'] ?? '')
                );
            } catch (RuntimeException $e) {
                flash('error', $e->getMessage());
                redirect(['page' => 'accept_invite', 'token' => $token]);
            }
            // Loga a pessoa automaticamente, já na conta recém-criada —
            // evita mais uma etapa manual depois de definir a senha.
            $_SESSION['user_id'] = $result['user_id'];
            flash('success', 'Conta criada com sucesso! Bem-vindo(a) ao Vital Clinic.');
            redirect(['page' => 'dashboard']);

        case 'mark_appointment':
            $user = require_role(['doctor', 'admin']);
            mark_appointment((int) ($_POST['appointment_id'] ?? 0), $user, post_value('status'));
            flash('success', 'Consulta atualizada.');
            redirect(['page' => $_POST['page_after'] ?? 'dashboard']);

        case 'mark_notifications_read':
            $user = require_login();
            mark_notifications_read((int) $user['id']);
            flash('success', 'Notificacoes marcadas como lidas.');
            redirect(['page' => 'notifications']);
    }

    throw new RuntimeException('Acao invalida.');
}

/**
 * Garante que o médico pertence à mesma clínica do administrador
 * logado — usada em toda ação que recebe um doctor_id vindo do
 * formulário/URL (editar, remover, agenda, bloqueio), pra um admin
 * nunca conseguir mexer na agenda de um médico de outra clínica só
 * forjando o ID no POST.
 */
function require_admin_owns_doctor(array $adminUser, int $doctorId): array
{
    $doctor = repository_find_doctor($doctorId);
    if (!$doctor || !admin_can_access_clinic($adminUser, (int) $doctor['clinic_id'])) {
        throw new RuntimeException('Médico não encontrado.');
    }
    return $doctor;
}

function doctor_form_data(bool $withPassword = true): array
{
    return [
        'name' => post_value('name'),
        'email' => post_value('email'),
        'password' => $withPassword ? (string) ($_POST['password'] ?? '') : '',
        'phone' => post_value('phone'),
        'clinic_id' => (int) ($_POST['clinic_id'] ?? 0),
        'specialty_id' => (int) ($_POST['specialty_id'] ?? 0),
        'crm' => post_value('crm'),
        'bio' => post_value('bio'),
        'appointment_duration' => (int) ($_POST['appointment_duration'] ?? 30),
    ];
}

function render_layout(string $page, ?array $user): void
{
    $messages = take_flash();
    $title = config('app_name');
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="manifest" href="manifest.webmanifest">
    <title><?= h($title) ?></title>
    <link rel="icon" href="<?= asset_url('assets/brand/vital-clinic-mark.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap">
    <link rel="stylesheet" href="<?= asset_url('assets/css/styles.css') ?>">
    <?php if ($page === 'admin_reports'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="<?= h(app_url(['page' => 'dashboard'])) ?>">
            <img class="brand-logo" src="<?= asset_url('assets/brand/vital-clinic-logo.svg') ?>" alt="<?= h($title) ?>">
        </a>
        <?php render_nav($page, $user); ?>
    </header>

    <main class="shell">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= h($message['type']) ?>"><?= h($message['message']) ?></div>
        <?php endforeach; ?>

        <?php render_page($page, $user); ?>
    </main>

    <?php
    // O modal de Termos de Uso é bloqueante e tem prioridade: só
    // desenhamos o tutorial depois que os termos já foram aceitos.
    if ($user && (int) ($user['terms_accepted'] ?? 0) === 0 && in_array($user['role'], ['admin', 'doctor'], true)) {
        render_terms_modal($user);
    } else {
        render_tutorial_modal($user);
    }
    ?>

    <footer class="app-footer">
        <span>VitalClinic <?= h(app_version()) ?></span>
    </footer>

    <script src="<?= asset_url('assets/js/app.js') ?>"></script>
</body>
</html>
    <?php
}

function render_nav(string $page, ?array $user): void
{
    if (!$user) {
        return;
    }

    $items = [];
    if ($user['role'] === 'patient') {
        $items = [
            'notifications' => 'Notificações',
        ];
    } elseif ($user['role'] === 'admin') {
        $items = [
            'dashboard' => 'Geral',
            'admin_calendar' => 'Calendário',
            'admin_appointments' => 'Consultas',
            'admin_patients' => 'Pacientes',
            'admin_doctors' => 'Médicos',
            'admin_reports' => 'Relatórios',
            'notifications' => 'Notificações',
        ];
        if (!empty($user['is_super_admin'])) {
            // "Convites" só existe pro super admin — é como uma clínica
            // nova ganha o primeiro acesso (ver render_admin_invites()).
            $items['admin_invites'] = 'Convites';
        }
    } else {
        $items = [
            'dashboard' => 'Geral',
            'doctor_calendar' => 'Calendário',
            'doctor_appointments' => 'Consultas',
            'doctor_patients' => 'Pacientes',
            'notifications' => 'Notificações',
        ];
    }

    $unread = unread_notifications_count((int) $user['id']);
    $initial = strtoupper(substr($user['name'], 0, 1));
    ?>
    <div class="topbar-actions">
        <div class="topbar-profile" data-profile-menu>
            <button type="button" class="topbar-profile-toggle" data-profile-toggle aria-haspopup="true" aria-expanded="false" aria-label="Menu do perfil">
                <span class="avatar avatar-sm"><?= h($initial) ?></span>
            </button>
            <div class="topbar-profile-dropdown" data-profile-dropdown>
                <div class="topbar-profile-name"><?= h($user['name']) ?></div>
                <a href="<?= h(app_url(['page' => 'profile'])) ?>">Perfil</a>
                <a href="<?= h(app_url(['page' => 'about'])) ?>">Sobre nós</a>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="topbar-profile-logout" type="submit">Sair</button>
                </form>
            </div>
        </div>
        <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="primary-nav" aria-label="Abrir menu">
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>
    </div>
    <nav class="nav" id="primary-nav" data-nav>
        <?php foreach ($items as $key => $label): ?>
            <a class="<?= $page === $key ? 'active' : '' ?>" href="<?= h(app_url(['page' => $key])) ?>">
                <?= h($label) ?><?= $key === 'notifications' && $unread ? ' (' . (int) $unread . ')' : '' ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

/**
 * Modal bloqueante de aceite dos Termos de Uso e Política de
 * Privacidade — aparece assim que users.terms_accepted = 0 e só
 * libera o uso do sistema depois que o usuário marca a caixa "Li e
 * aceito" e clica em "Prosseguir" (ver setupTermsGate() em app.js).
 * Diferente do tutorial, este modal NÃO tem botão de pular, X, nem
 * fecha ao clicar fora — é obrigatório para continuar usando o painel.
 */
function render_terms_modal(array $user): void
{
    ?>
    <div class="terms-overlay" data-terms-overlay></div>
    <div class="terms-modal panel" data-terms-modal role="dialog" aria-modal="true" aria-labelledby="terms-modal-title">
        <div class="modal-head">
            <h2 id="terms-modal-title">Termos de Uso e Política de Privacidade</h2>
        </div>
        <p class="muted">Antes de continuar, leia e aceite os termos abaixo. Isso é necessário apenas uma vez.</p>

        <div class="terms-text" tabindex="0">
            <h3>Termo de Uso da Clínica e Médicos – Vital Clinic</h3>

            <h4>1. Objetivo</h4>
            <p>Este Termo regula a utilização da plataforma Vital Clinic por clínicas, consultórios, médicos e demais profissionais de saúde cadastrados.</p>

            <h4>2. Responsabilidades da clínica</h4>
            <ul>
                <li>Manter seus dados cadastrais atualizados;</li>
                <li>Garantir a veracidade das informações fornecidas;</li>
                <li>Proteger os dados dos pacientes;</li>
                <li>Respeitar integralmente a LGPD;</li>
                <li>Controlar os acessos realizados por colaboradores autorizados.</li>
            </ul>

            <h4>3. Responsabilidades dos médicos</h4>
            <ul>
                <li>Possuir registro profissional ativo junto ao órgão competente;</li>
                <li>Informar corretamente sua especialidade e número de registro profissional;</li>
                <li>Manter a agenda atualizada e cumprir os horários disponibilizados;</li>
                <li>Respeitar o sigilo profissional e a legislação aplicável.</li>
            </ul>

            <h4>4. Uso indevido</h4>
            <p>É considerado uso indevido da plataforma: fornecer informações falsas; compartilhar dados de pacientes sem autorização; tentar invadir ou comprometer a segurança do sistema; ou utilizar a plataforma para fins ilícitos.</p>

            <h4>5. Penalidades</h4>
            <p>O descumprimento deste Termo pode acarretar, de forma progressiva ou imediata conforme a gravidade: advertência formal; suspensão temporária do acesso; cancelamento definitivo da conta; e, quando cabível, aplicação das medidas judiciais cabíveis, com colaboração da Vital Clinic junto às autoridades competentes.</p>

            <h4>6. Proteção de dados</h4>
            <p>A clínica e os profissionais de saúde comprometem-se a usar os dados dos pacientes exclusivamente para fins de prestação de serviços de saúde, observando a Lei Geral de Proteção de Dados (LGPD).</p>

            <h4>7. Vigência</h4>
            <p>Este Termo permanece válido enquanto houver vínculo de utilização da plataforma Vital Clinic.</p>

            <h4>8. Aceite</h4>
            <p>Ao realizar o cadastro e utilizar a plataforma, a clínica e os profissionais de saúde declaram estar de acordo com todas as condições previstas neste Termo.</p>

            <h3>Política de Privacidade e Proteção de Dados (LGPD)</h3>

            <h4>1. Introdução</h4>
            <p>O Vital Clinic valoriza a privacidade e a proteção dos dados pessoais de seus usuários.</p>

            <h4>2. Dados coletados</h4>
            <p><strong>Pacientes:</strong> nome completo, CPF, data de nascimento, telefone, e-mail, histórico de agendamentos.<br>
            <strong>Clínicas:</strong> razão social, CNPJ, endereço, dados dos responsáveis.<br>
            <strong>Médicos:</strong> nome, CRM, especialidade, contatos profissionais.</p>

            <h4>3. Finalidades do tratamento</h4>
            <p>Cadastro de usuários; agendamento e confirmação de consultas; envio de notificações; atendimento ao usuário; cumprimento de obrigações legais.</p>

            <h4>4. Compartilhamento de dados</h4>
            <p>Os dados podem ser compartilhados entre pacientes e clínicas, entre pacientes e médicos, com fornecedores tecnológicos, ou mediante obrigação legal.</p>

            <h4>5. Segurança</h4>
            <p>O Vital Clinic adota medidas técnicas e administrativas para proteger os dados contra acesso não autorizado, perda, vazamento e alteração indevida.</p>

            <h4>6. Direitos dos titulares</h4>
            <p>O usuário pode solicitar confirmação do tratamento, acesso aos dados, correção, portabilidade, anonimização, exclusão e revogação do consentimento.</p>

            <h4>7. Retenção dos dados</h4>
            <p>Os dados são mantidos apenas pelo período necessário para cumprimento das finalidades legais e contratuais.</p>

            <h4>8. Canal LGPD</h4>
            <p>Solicitações relacionadas à proteção de dados podem ser encaminhadas para: <strong>privacidade@vitalclinic.com</strong></p>

            <h4>9. Alterações</h4>
            <p>Esta Política pode ser atualizada a qualquer momento para adequação legal ou melhoria dos serviços.</p>

            <p class="muted">Versão 1.0 – Vital Clinic</p>
        </div>

        <form method="post" data-terms-form>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="accept_terms">
            <input type="hidden" name="page_after" value="dashboard">
            <input type="hidden" name="agree" value="0" data-terms-agree-value>
            <label class="terms-checkbox">
                <input type="checkbox" data-terms-checkbox>
                Li e aceito os Termos de Uso e a Política de Privacidade.
            </label>
            <button class="button primary" type="submit" data-terms-submit disabled>Prosseguir</button>
        </form>
    </div>
    <?php
}

/**
 * Tutorial de primeiro acesso: só é desenhado quando o usuário logado
 * ainda não viu (users.tutorial_seen = 0). Diferente de um modal comum,
 * este é um "tour guiado" — cada passo aponta (com destaque + balão de
 * texto) para o item de menu real que ele está explicando, em vez de só
 * descrever em texto solto. O conteúdo muda conforme o perfil (admin ou
 * médico) — pacientes não logam neste painel, então não precisam de
 * roteiro aqui.
 *
 * "target" é a chave de página usada em render_nav() (ex.: 'admin_doctors')
 * — o JavaScript usa isso pra encontrar o link correspondente no menu e
 * desenhar o destaque em cima dele. Um passo sem "target" aparece
 * centralizado na tela (usado só na boas-vindas inicial).
 */
function render_tutorial_modal(?array $user): void
{
    if (!$user || (int) ($user['tutorial_seen'] ?? 0) === 1) {
        return;
    }
    if (!in_array($user['role'], ['admin', 'doctor'], true)) {
        return;
    }

    if ($user['role'] === 'admin') {
        $steps = [
            [
                'target' => null,
                'title' => 'Bem-vindo(a) à Vital Clinic!',
                'text' => 'Vamos te mostrar rapidinho os principais pontos do painel. Use "Próximo" para avançar.',
            ],
            [
                'target' => 'admin_doctors',
                'title' => 'Cadastre seus médicos',
                'text' => 'Aqui em "Médicos" você cadastra a equipe da clínica, com especialidade, CRM e horários de atendimento.',
            ],
            [
                'target' => 'admin_calendar',
                'title' => 'Acompanhe a agenda',
                'text' => 'Aqui em "Calendário" você vê todas as consultas do mês. Em "Consultas", use o botão "Nova consulta" para agendar um paciente.',
            ],
            [
                'target' => 'admin_reports',
                'title' => 'Gerencie o sistema',
                'text' => 'Aqui em "Relatórios" você acompanha os números da clínica. Em "Pacientes", cadastra e edita quem é atendido.',
            ],
        ];
    } else {
        $steps = [
            [
                'target' => null,
                'title' => 'Bem-vindo(a), Doutor(a)!',
                'text' => 'Vamos te mostrar rapidinho onde encontrar sua agenda e seus pacientes. Use "Próximo" para avançar.',
            ],
            [
                'target' => 'dashboard',
                'title' => 'Veja sua agenda do dia',
                'text' => 'Aqui em "Geral" já aparece sua agenda de hoje. Em "Calendário", veja o mês inteiro; em "Consultas", filtre por status ou data.',
            ],
            [
                'target' => 'doctor_patients',
                'title' => 'Busque seus pacientes',
                'text' => 'Aqui em "Pacientes" você encontra rapidamente quem já atendeu e acessa o histórico de prontuários com um clique.',
            ],
        ];
    }
    ?>
    <div class="tour-overlay" data-tour-overlay hidden></div>
    <div class="tour-highlight" data-tour-highlight hidden></div>
    <div class="tour-tooltip panel" data-tour-tooltip hidden>
        <div class="modal-head">
            <h2 data-tour-title>Primeiros passos</h2>
            <button type="button" class="modal-close" data-tutorial-skip aria-label="Pular tutorial">&times;</button>
        </div>
        <p data-tour-text></p>

        <div class="tutorial-dots" data-tutorial-dots>
            <?php foreach ($steps as $index => $step): ?>
                <span class="tutorial-dot<?= $index === 0 ? ' is-active' : '' ?>"></span>
            <?php endforeach; ?>
        </div>

        <div class="actions tutorial-actions">
            <button type="button" class="button ghost" data-tutorial-skip>Pular</button>
            <div class="tutorial-nav-buttons">
                <button type="button" class="button" data-tutorial-prev disabled>Anterior</button>
                <button type="button" class="button primary" data-tutorial-next>Próximo</button>
            </div>
        </div>

        <input type="hidden" data-tutorial-csrf value="<?= h(csrf_token()) ?>">
    </div>
    <script type="application/json" id="tutorial-steps-data"><?= json_encode($steps, JSON_UNESCAPED_UNICODE) ?></script>
    <?php
}

function render_page(string $page, ?array $user): void
{
    // Abrir um link de convite estando LOGADO (ex.: o próprio super
    // admin testando o link que acabou de gerar, no mesmo navegador)
    // não pode simplesmente cair no painel de quem já está logado —
    // desloga primeiro, pra sempre mostrar a tela de aceitar o
    // convite, não importa quem estava conectado antes.
    if ($page === 'accept_invite' && $user) {
        logout_user();
        $user = null;
    }

    if (!$user) {
        if ($page === 'forgot_password') {
            render_forgot_password();
        } elseif ($page === 'reset_security_question') {
            render_reset_security_question();
        } elseif ($page === 'reset_password') {
            render_reset_password();
        } elseif ($page === 'accept_invite') {
            render_accept_invite();
        } else {
            render_login();
        }
        return;
    }

    if ($page === 'notifications') {
        render_notifications($user);
        return;
    }

    if ($page === 'about') {
        render_about();
        return;
    }

    if ($user['role'] === 'patient') {
        render_profile($user);
        return;
    }

    if ($user['role'] === 'admin') {
        if ($page === 'admin_calendar') {
            render_admin_calendar($user);
        } elseif ($page === 'admin_doctors') {
            render_admin_doctors($user);
        } elseif ($page === 'admin_appointments') {
            render_admin_appointments($user);
        } elseif ($page === 'admin_patients') {
            render_admin_patients($user);
        } elseif ($page === 'admin_reports') {
            render_admin_reports($user);
        } elseif ($page === 'admin_invites' && !empty($user['is_super_admin'])) {
            render_admin_invites($user);
        } elseif ($page === 'profile') {
            render_staff_profile($user);
        } else {
            render_admin_dashboard($user);
        }
        return;
    }

    if ($page === 'doctor_calendar') {
        render_doctor_calendar($user);
    } elseif ($page === 'doctor_appointments') {
        render_doctor_appointments($user);
    } elseif ($page === 'doctor_patients') {
        render_doctor_patients($user);
    } elseif ($page === 'doctor_patient_history') {
        render_doctor_patient_history($user);
    } elseif ($page === 'doctor_detail') {
        render_doctor_detail($user);
    } elseif ($page === 'doctor_consultation') {
        render_doctor_consultation($user);
    } elseif ($page === 'profile') {
        render_staff_profile($user);
    } else {
        render_doctor_dashboard($user);
    }
}

function render_profile(array $user): void
{
    ?>
    <section class="narrow">
        <form method="post" class="panel form-card">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="page_after" value="profile">
            <h1>Perfil do paciente</h1>
            <label>Nome <input name="name" value="<?= h($user['name']) ?>" required></label>
            <label>E-mail <input value="<?= h($user['email']) ?>" disabled></label>
            <label>Telefone/WhatsApp <input name="phone" value="<?= h($user['phone']) ?>"></label>
            <label>CPF <input name="document" value="<?= h($user['document']) ?>"></label>
            <label>Data de nascimento <input type="date" name="birth_date" value="<?= h($user['birth_date']) ?>"></label>
            <label>Endereço <input name="address" value="<?= h($user['address'] ?? '') ?>"></label>
            <button class="button primary" type="submit">Salvar perfil</button>
        </form>
    </section>
    <?php
}

function render_staff_profile(array $user): void
{
    $doctor = $user['role'] === 'doctor' ? doctor_by_user((int) $user['id']) : null;
    $title = $doctor ? 'Dr. ' . preg_replace('/^Dr(a)?\.?\s+/i', '', $user['name']) : $user['name'];
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow"><?= $user['role'] === 'doctor' ? 'Médico' : 'Administrador' ?></p>
            <h1>Meu perfil</h1>
        </div>
    </section>

    <form method="post" class="grid two profile-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_staff_profile">
        <input type="hidden" name="page_after" value="profile">

        <div class="grid">
            <div class="panel profile-summary">
                <div class="avatar"><?= h(strtoupper(substr($user['name'], 0, 1))) ?></div>
                <div>
                    <h2><?= h($title) ?></h2>
                    <p><?= h($doctor['specialty_name'] ?? 'Administrador da clínica') ?></p>
                    <p class="muted"><?= h($doctor['crm'] ?? 'Gestão da clínica') ?></p>
                </div>
            </div>

            <div class="panel form-card">
                <h2>Alterar senha</h2>
                <label>Senha atual <input type="password" name="current_password" autocomplete="current-password"></label>
                <label>Nova senha <input type="password" name="new_password" autocomplete="new-password"></label>
                <label>Confirmar senha <input type="password" name="confirm_password" autocomplete="new-password"></label>
            </div>

            <div class="panel form-card">
                <h2>Pergunta de segurança</h2>
                <p class="muted">
                    <?= $user['security_question'] ?? null ? 'Cadastrada: "' . h($user['security_question']) . '".' : 'Você ainda não cadastrou uma pergunta de segurança.' ?>
                    Ela é usada como forma alternativa de confirmar sua identidade em "Esqueci minha senha".
                </p>
                <label>
                    Pergunta
                    <select name="security_question">
                        <?php foreach ((config('security_questions') ?: []) as $question): ?>
                            <option value="<?= h($question) ?>" <?= ($user['security_question'] ?? '') === $question ? 'selected' : '' ?>><?= h($question) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Resposta
                    <input type="text" name="security_answer" autocomplete="off" placeholder="Deixe em branco para manter a resposta atual">
                </label>
                <p class="muted">A resposta não diferencia maiúsculas/minúsculas nem espaços extras, e fica salva de forma criptografada.</p>
            </div>
        </div>

        <div class="panel form-card">
            <h2>Informações pessoais</h2>
            <div class="grid two">
                <label>Nome <input name="name" value="<?= h($user['name']) ?>" required></label>
                <label>Endereço <input name="address" value="<?= h($user['address'] ?? '') ?>"></label>
                <label>E-mail <input value="<?= h($user['email']) ?>" disabled></label>
                <label>Telefone <input name="phone" value="<?= h($user['phone']) ?>"></label>
                <label>CPF <input name="document" value="<?= h($user['document']) ?>"></label>
                <label><?= $doctor ? 'CRM' : 'Perfil' ?> <input value="<?= h($doctor['crm'] ?? status_label($user['role'])) ?>" disabled></label>
                <?php if ($doctor): ?>
                    <label>Especialidade <input value="<?= h($doctor['specialty_name']) ?>" disabled></label>
                    <label>Clínica <input value="<?= h($doctor['clinic_name']) ?>" disabled></label>
                <?php endif; ?>
            </div>
            <button class="button primary" type="submit">Salvar perfil</button>
        </div>
    </form>
    <?php
}

/**
 * "Sobre nós" — página institucional simples, acessível pelo menu do
 * avatar (perfil) em qualquer painel. Conteúdo estático, sem consulta
 * ao banco.
 */
function render_about(): void
{
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow">Vital Clinic</p>
            <h1>Sobre nós</h1>
        </div>
    </section>
    <section class="panel">
        <p>
            O <strong>Vital Clinic</strong> é uma plataforma de gestão para
            clínicas médicas, pensada para simplificar o dia a dia de
            administradores e médicos: agenda, prontuário eletrônico,
            relatórios e o cadastro de pacientes, tudo num só lugar.
        </p>
        <p>
            Este painel (administrador/médico) é um dos componentes do
            projeto — pacientes têm seu próprio espaço de acesso, para
            marcar consultas e acompanhar seu histórico diretamente.
        </p>
        <p class="muted">
            Vital Clinic <?= h(app_version()) ?>
        </p>
    </section>
    <?php
}

function render_notifications(array $user): void
{
    $notifications = notifications_for_user((int) $user['id']);
    ?>
    <section class="page-head">
        <div>
            <p class="eyebrow">Alertas</p>
            <h1>Notificações</h1>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_notifications_read">
            <input type="hidden" name="page_after" value="notifications">
            <button class="button" type="submit">Marcar lidas</button>
        </form>
    </section>
    <section class="panel">
        <div class="list">
            <?php foreach ($notifications as $notification): ?>
                <article class="list-row <?= $notification['read_at'] ? '' : 'unread' ?>">
                    <div>
                        <strong><?= h($notification['title']) ?></strong>
                        <span><?= h($notification['message']) ?></span>
                        <small><?= h(strtoupper($notification['type'])) ?> - <?= h(format_datetime($notification['created_at'])) ?></small>
                    </div>
                    <?php if ($notification['type'] === 'whatsapp'): ?>
                        <a class="button small" target="_blank" rel="noopener" href="https://wa.me/?text=<?= urlencode($notification['message']) ?>">WhatsApp</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if (!$notifications): ?>
                <p class="muted">Sem notificações.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function render_appointment_table(array $appointments, array $actor, string $pageAfter): void
{
    if (!$appointments) {
        echo '<p class="muted">Nenhum registro encontrado.</p>';
        return;
    }
    ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Médico</th>
                    <th>Especialidade</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= h(format_datetime($appointment['slot_start'])) ?></td>
                        <td>
                            <?= h($appointment['patient_name']) ?>
                            <small><?= h($appointment['patient_phone'] ?: $appointment['patient_email']) ?></small>
                        </td>
                        <td><?= h($appointment['doctor_name']) ?></td>
                        <td><?= h($appointment['specialty_name']) ?></td>
                        <td><?= h($appointment['modality'] === 'teleconsulta' ? 'Teleconsulta' : 'Presencial') ?></td>
                        <td><span class="status <?= h($appointment['status']) ?>"><?= h(status_label($appointment['status'])) ?></span></td>
                        <td>
                            <div class="actions">
                                <?php if ($actor['role'] === 'doctor'): ?>
                                    <a class="button small" href="<?= h(app_url(['page' => 'doctor_detail', 'appointment_id' => $appointment['id']])) ?>">Detalhes</a>
                                <?php endif; ?>

                                <?php if ($actor['role'] === 'doctor' && in_array($appointment['status'], ['pending', 'confirmed'], true)): ?>
                                    <a class="button small primary" href="<?= h(app_url(['page' => 'doctor_consultation', 'appointment_id' => $appointment['id']])) ?>">Iniciar</a>
                                <?php endif; ?>

                                <?php if ($actor['role'] === 'patient' && $appointment['status'] === 'pending'): ?>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="confirm">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                        <button class="button small" type="submit">Confirmar</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($actor['role'] === 'patient' && in_array($appointment['status'], ['pending', 'confirmed'], true) && can_change_appointment($appointment['slot_start'], 24)): ?>
                                    <a class="button small" href="<?= h(app_url(['page' => 'book', 'reschedule_id' => $appointment['id'], 'doctor_id' => $appointment['doctor_id']])) ?>">Remarcar</a>
                                <?php endif; ?>

                                <?php if (in_array($actor['role'], ['patient', 'admin'], true) && in_array($appointment['status'], ['pending', 'confirmed'], true)): ?>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                        <input type="hidden" name="page_after" value="<?= h($pageAfter) ?>">
                                        <button class="button small danger" type="submit" data-confirm="Cancelar consulta?">Cancelar</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($actor['role'], ['doctor', 'admin'], true) && in_array($appointment['status'], ['pending', 'confirmed'], true)): ?>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="mark_appointment">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <input type="hidden" name="page_after" value="<?= h($pageAfter) ?>">
                                        <button class="button small" type="submit">Realizada</button>
                                    </form>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="mark_appointment">
                                        <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                        <input type="hidden" name="status" value="no_show">
                                        <input type="hidden" name="page_after" value="<?= h($pageAfter) ?>">
                                        <button class="button small warning" type="submit">Ausência</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_install_error(Throwable $e): void
{
    http_response_code(500);
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="manifest" href="manifest.webmanifest">
    <title>Erro de conexão</title>
    <link rel="icon" href="<?= asset_url('assets/brand/vital-clinic-mark.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap">
    <link rel="stylesheet" href="<?= asset_url('assets/css/styles.css') ?>">
</head>
<body>
    <main class="shell">
        <section class="panel narrow">
            <img class="error-logo" src="<?= asset_url('assets/brand/vital-clinic-logo.svg') ?>" alt="Vital Clinic">
            <h1>Erro de conexão com o banco de dados</h1>
            <p>O site não conseguiu ler os dados do banco MySQL "vitalclinic". Confira as credenciais em <code>app/config.php</code> (ou nas variáveis <code>VCTCC_DB_HOST</code>, <code>VCTCC_DB_NAME</code>, <code>VCTCC_DB_USER</code>, <code>VCTCC_DB_PASS</code>) e se o script <code>vitalclinic_schema.sql</code> já foi executado.</p>
            <p class="muted"><?= h($e->getMessage()) ?></p>
        </section>
    </main>
</body>
</html>
    <?php
}