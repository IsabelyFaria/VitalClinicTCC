# `app/` — Lógica de negócio

Esta pasta reúne tudo que **não é tela**: conexão com o banco,
autenticação, regras de negócio e funções utilitárias. Nenhum arquivo
aqui produz HTML diretamente — quem desenha a tela é a pasta `pages/`
(veja `pages/README.md`). Todo arquivo é `require`ido a partir de
`index.php`.

---

## `config.php`

Configurações do sistema, num array PHP retornado pela função
`config()`. Contém `app_name`, `timezone`, `db` (conexão), `rules`
(regras de negócio: horas de antecedência para cancelar/reagendar,
dias máximos pra agendar no futuro, tentativas da pergunta de
segurança, limites de classificação da "Movimentação mensal", e
`invite_valid_hours` — validade do link de convite de primeiro acesso)
e `security_questions`.

## `db.php`

Conexão PDO com o MySQL (`db()`), reaproveitada durante toda a
requisição. Expõe `db_transaction(callable $fn)` — roda `$fn` numa
transação (tudo ou nada), usada, por exemplo, ao criar uma consulta ou
ao ativar um convite de primeiro acesso.

## `helpers.php`

Funções pequenas usadas em quase todo arquivo do projeto:

| Função | Para que serve |
|---|---|
| `config($key)` | Lê uma configuração |
| `app_version()` | A versão exibida no rodapé |
| `h($value)` | Escapa texto para HTML com segurança |
| `app_url($params)` | Monta a URL de uma página (relativa) |
| `full_url($params)` | Igual, mas com domínio completo — usada só no link de convite, que precisa funcionar fora do site (copiado pro WhatsApp/e-mail) |
| `asset_url($path)` | URL de um arquivo estático, com versão anti-cache |
| `is_ajax_request()` / `send_json()` / `redirect()` | Resposta de formulários e chamadas `fetch()` |
| `csrf_token()` / `csrf_field()` / `verify_csrf()` | Token anti-CSRF |
| `flash()` / `take_flash()` | Mensagens entre páginas |
| `format_datetime` / `format_date` / `format_time` / `format_money` / `weekday_name` / `status_label` | Formatação para exibição |

## `auth.php`

Login, logout, "Esqueci minha senha" — e o controle de **isolamento
por clínica**:

- `current_user()` / `require_login()` / `require_role($roles)`.
- **`admin_clinic_scope($user)`** — devolve o `clinic_id` do admin
  logado, ou `null` se ele for super admin (sinal de "sem filtro,
  mostra tudo"). Toda tela/relatório do painel admin usa isso, nunca
  lê `users.clinic_id` direto.
- **`admin_can_access_clinic($user, $clinicId)`** — versão booleana da
  mesma checagem, usada nas validações de posse (editar médico,
  paciente, conceder acesso ADM) — super admin sempre passa.
- `attempt_login()` — autentica e devolve o motivo exato da falha.
- `register_patient()` — cadastro de paciente feito pelo admin.
- **`complete_admin_invite($token, $name, $password)`** — revalida o
  token, cria a conta de administrador vinculada à clínica do convite,
  e marca o convite como usado — tudo numa transação.
- `request_password_reset()` → `confirm_password_reset_security_answer()`
  → `complete_password_reset()` — recuperação de senha.

## `repository.php`

O arquivo mais longo do projeto — toda consulta SQL do sistema. Quase
toda função de listagem aceita um `?int $clinicId = null` (ou um
`array $filters` com `clinic_id`) — passar `null` significa "sem
filtro" (só o super admin usa isso). Organizado por assunto:

- **Usuários e perfis**, **clínicas e especialidades**.
- **Médicos** — CRUD, agenda semanal (`doctor_working_weekdays()`),
  bloqueios pontuais, `staff_users($clinicId)` (gestão de acessos).
- **Pacientes** — `patient_list($search, $clinicId)`, CRUD, histórico.
- **Agenda e consultas** — `ensure_slots()`, `available_slots()`,
  `create_appointment()`.
- **`report_data($from, $to, $clinicId)`** — números agregados pra
  Relatórios. ⚠️ Consulta o banco direto (nunca reaproveita
  `appointments_for_admin()`, que tem um `LIMIT 300` pensado pra tela
  de listagem — já causou um bug real de meses "sumindo" do relatório).
- **`admin_invites_list()` / `create_admin_invite()` /
  `find_pending_invite()` / `revoke_admin_invite()`** — o fluxo de
  convite de primeiro acesso.

## `mailer.php` / `api_client.php`

Não são chamados no funcionamento atual (envio de e-mail e modo de
operação via API central, respectivamente — nenhum dos dois está em
uso hoje).