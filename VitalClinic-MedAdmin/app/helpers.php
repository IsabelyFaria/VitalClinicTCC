<?php

function config(?string $key = null)
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return null;
        }
        $value = $value[$part];
    }

    return $value;
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_version(): string
{
    return 'v.3.03';
}

function app_url(array $params = []): string
{
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

/**
 * Igual a app_url(), mas devolve o endereço COMPLETO (com ctormínio) —
 * necessário para o link de convite de admin, que é copiado e enviado
 * por fora do site (WhatsApp, e-mail) e precisa funcionar sozinho,
 * sem depender de estar navegando dentro do site no momento do clique.
 */
function full_url(array $params = []): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    return $scheme . '://' . $host . $basePath . '/' . app_url($params);
}

/**
 * Gera a URL de um arquivo estático (imagens, css, js) acrescentando
 * a data de modificação do arquivo como versão (?v=...).
 * Isso evita que o navegador ou o service worker sirvam uma versão
 * antiga em cache quando o arquivo é substituído no servidor.
 */
function asset_url(string $relativePath): string
{
    $fullPath = __DIR__ . '/../' . ltrim($relativePath, '/');
    $version = is_file($fullPath) ? filemtime($fullPath) : time();

    return $relativePath . '?v=' . $version;
}

function is_ajax_request(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function send_json(array $payload, int $statusCode): void
{
    // Descarta qualquer coisa que já tenha sido "impressa" antes disso
    // (avisos/notices do PHP, espaços em branco antes do <?php de algum
    // arquivo, etc.) — sem isso, um simples warning solto em qualquer
    // arquivo incluído quebraria o JSON (o texto do aviso ficaria colado
    // ANTES das chaves, e o navegador não conseguiria interpretar como
    // JSON válido).
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function redirect(array $params = []): void
{
    if (is_ajax_request()) {
        // Requisições disparadas via fetch() (ex: modal de nova consulta)
        // não devem receber um redirecionamento HTTP de verdade — em vez
        // disso, respondemos com JSON e devolvemos as mensagens "flash"
        // (que normalmente só apareceriam depois do reload da página)
        // dentro do próprio corpo da resposta.
        //
        // Algumas rotinas (ex: verify_csrf(), ao expirar a sessão) usam
        // flash('error', ...) + redirect() para sinalizar falha, mesmo
        // fora de uma exceção — por isso inspecionamos o conteúdo do
        // flash aqui para decidir o status HTTP correto, em vez de
        // assumir sucesso sempre que redirect() for chamado.
        $flashMessages = take_flash();
        $hasError = false;
        foreach ($flashMessages as $item) {
            if (($item['type'] ?? '') === 'error') {
                $hasError = true;
                break;
            }
        }

        send_json(['success' => !$hasError, 'flash' => $flashMessages], $hasError ? 400 : 200);
    }

    header('Location: ' . app_url($params));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash('error', 'Sua sessao expirou. Tente novamente.');
        redirect(['page' => $_GET['page'] ?? 'dashboard']);
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function post_value(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    return (new DateTime($value))->format('d/m/Y H:i');
}

function format_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    return (new DateTime($value))->format('d/m/Y');
}

function format_time(?string $value): string
{
    if (!$value) {
        return '-';
    }

    return (new DateTime($value))->format('H:i');
}

function format_money($value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function weekday_name(int $weekday): string
{
    $names = ['Domingo', 'Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado'];

    return $names[$weekday] ?? '-';
}

function status_label(string $status): string
{
    $labels = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmada',
        'completed' => 'Realizada',
        'cancelled' => 'Cancelada',
        'no_show' => 'Ausencia',
        'available' => 'Livre',
        'booked' => 'Reservado',
        'blocked' => 'Bloqueado',
        'active' => 'Ativo',
        'inactive' => 'Inativo',
    ];

    return $labels[$status] ?? ucfirst($status);
}

/**
 * Cor do selinho da notificaÃ§Ã£o, a partir do "title" gravado no banco.
 * Mesma ideia (e as mesmas cores) da tela de NotificaÃ§Ãµes do site do
 * paciente â sÃ³ que aqui a lista tambÃ©m cobre os tÃ­tulos que sÃ³
 * existem no painel da clÃ­nica ("Nova consulta agendada", etc.).
 */
function notificacao_badge_class(string $titulo): string
{
    $classes = [
        'Consulta confirmada'              => 'badge badge-realizada',
        'Consulta agendada'                => 'badge badge-realizada',
        'Nova consulta agendada'           => 'badge badge-realizada',
        'Nova consulta marcada'            => 'badge badge-realizada',
        'Consulta realizada'               => 'badge badge-concluida',
        'Consulta concluída'                => 'badge badge-concluida',
        'Consulta cancelada'               => 'badge badge-cancelada',
        'Consulta cancelada pelo paciente' => 'badge badge-cancelada',
        'Consulta pendente'                => 'badge badge-pendente',
        'Consulta com ausência'            => 'badge badge-ausencia',
        'Falta registrada'                 => 'badge badge-ausencia',
        'Lembrete'                         => 'badge badge-pendente',
        'Lembrete de consulta'             => 'badge badge-pendente',
    ];

    return $classes[$titulo] ?? 'badge';
}

/**
 * Traduz o "title" cru do banco para a frase em negrito do cartão,
 * igual à notificacao_titulo_amigavel() do site do paciente â aqui as
 * frases são escritas do ponto de vista da clínica/médico.
 */
function notificacao_titulo_amigavel(string $tituloBanco): string
{
    $titulos = [
        'Consulta confirmada'              => 'Consulta confirmada',
        'Consulta agendada'                => 'Uma consulta foi agendada',
        'Nova consulta agendada'           => 'Uma nova consulta foi agendada',
        'Nova consulta marcada'            => 'Um paciente marcou uma consulta',
        'Consulta cancelada'               => 'Uma consulta foi cancelada',
        'Consulta cancelada pelo paciente' => 'O paciente cancelou a consulta',
        'Consulta pendente'                => 'Consulta aguardando confirmação',
        'Consulta realizada'               => 'Consulta concluída',
        'Consulta concluída'                => 'Consulta concluída',
        'Consulta com ausência'            => 'Ausência registrada na consulta',
        'Falta registrada'                 => 'Ausência registrada na consulta',
        'Lembrete'                         => 'Consulta chegando',
        'Lembrete de consulta'             => 'Você tem uma consulta chegando',
    ];

    return $titulos[$tituloBanco] ?? $tituloBanco;
}

function current_date_value(): string
{
    return (new DateTime())->format('Y-m-d');
}

function now_sql(): string
{
    return (new DateTime())->format('Y-m-d H:i:s');
}

function abort_forbidden(): void
{
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}