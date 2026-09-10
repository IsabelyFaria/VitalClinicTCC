# `app/` — Lógica de negócio

Esta pasta reúne tudo que **não é tela**: conexão com o banco,
autenticação, regras de negócio e funções utilitárias. Nenhum arquivo
aqui produz HTML diretamente — quem desenha a tela é a pasta `pages/`
(veja `pages/README.md`). Todo arquivo é `require`ido a partir de
`index.php`.

---

## `config.php`

Configurações do sistema, num único array PHP retornado pela função
`config()` (definida em `helpers.php`). Contém:

- `app_name`, `timezone` — identidade e fuso horário do sistema.
- `db` — host, nome do banco, usuário e senha do MySQL.
- `data.mode` — `mysql` (modo atual), `api` (alternativo, não usado)
  ou `demo` (legado).
- `rules` — regras de negócio: horas de antecedência para
  cancelar/reagendar, dias máximos pra agendar no futuro, tentativas
  da pergunta de segurança, e os limites de classificação da
  "Movimentação mensal" (`movement_low` / `movement_high`).
- `security_questions` — lista fixa de perguntas de segurança.
- `mail` — configuração de e-mail (não usada no fluxo atual).

## `db.php`

Abre a conexão com o MySQL via PDO (`db()`), reaproveitando a mesma
conexão durante toda a requisição. Expõe `db_transaction(callable $fn)`
— roda `$fn` dentro de uma transação: se qualquer coisa lançar uma
exceção lá dentro, nada é gravado (usado, por exemplo, ao criar uma
consulta — grava em 3 tabelas de uma vez).

## `helpers.php`

Funções pequenas, usadas em quase todo arquivo do projeto:

| Função | Para que serve |
|---|---|
| `config($key)` | Lê uma configuração (aceita `'rules.movement_low'`, por exemplo) |
| `app_version()` | A versão exibida no rodapé (`app-footer`) |
| `h($value)` | Escapa texto para exibir em HTML com segurança |
| `app_url($params)` / `asset_url($path)` | Monta a URL de uma página / de um arquivo estático |
| `is_ajax_request()` | Detecta se a requisição veio de `fetch()` |
| `send_json($payload, $status)` | Responde em JSON e encerra a execução |
| `redirect($params)` | Redireciona (ou responde em JSON, se AJAX) |
| `csrf_token()` / `csrf_field()` / `verify_csrf()` | Token anti-CSRF |
| `flash($type, $msg)` / `take_flash()` | Mensagens de sucesso/erro entre páginas |
| `post_value($key)` | Lê um campo de `$_POST` já tratado |
| `format_datetime` / `format_date` / `format_time` / `format_money` / `weekday_name` / `status_label` | Formatação para exibição |
| `current_date_value()` / `now_sql()` | Data/hora atual nos formatos do sistema/banco |

## `auth.php`

Login, logout e todo o fluxo de "Esqueci minha senha".

- `current_user()` / `require_login()` / `require_role($roles)` —
  quem está logado, e trava de acesso por perfil.
- `attempt_login()` — autentica de verdade e devolve o **motivo
  exato** da falha (`not_found`, `inactive`, `wrong_role`,
  `wrong_password`) ou `null` se deu certo — é isso que permite a tela
  de login mostrar uma mensagem específica. `login_user()` é um
  wrapper que devolve `true`/`false`.
- `register_patient()` — cadastro de paciente feito pelo admin.
- `request_password_reset()` → `confirm_password_reset_security_answer()`
  → `complete_password_reset()` — as 3 etapas da recuperação por
  pergunta de segurança, com estado em `$_SESSION['pwd_reset']`.

## `repository.php`

O arquivo mais longo do projeto — é aqui que toda consulta SQL do
sistema acontece. Organizado por assunto:

- **Usuários e perfis** — buscar/atualizar, senha, pergunta de
  segurança, `mark_tutorial_seen()`, `accept_terms()`.
- **Clínicas e especialidades** — listagens para formulários.
- **Médicos** (`doctors`) — CRUD, agenda semanal
  (`doctor_working_weekdays()`, usada tanto pelo calendário do
  agendamento quanto pela ação `doctor_weekdays`), bloqueios pontuais.
- **Pacientes** — CRUD, busca, histórico.
- **Agenda e consultas** — geração de horários (`ensure_slots()`),
  horários livres (`available_slots()`), criação de consulta
  (`create_appointment()`, numa transação), mudança de status.
- **Prontuários**, **pagamentos**, **notificações**.
- **`report_data($from, $to)`** — números agregados para a tela de
  Relatórios (tabela De/Até e o gráfico de "Movimentação mensal").
  ⚠️ Consulta o banco **direto**, com o intervalo de data filtrado em
  SQL — não reaproveita `appointments_for_admin()` (que tem um
  `LIMIT 300, mais recentes primeiro` pensado pra tela de listagem).
  Reaproveitar aquela função aqui já causou um bug real: com mais de
  300 consultas no banco, qualquer mês fora das "300 mais recentes"
  simplesmente não aparecia no relatório, não importa o filtro de
  data escolhido.

## `mailer.php`

Envio de e-mail via `mail()`/SMTP. **Não é chamado por nenhuma tela
hoje** — era usado pelo antigo fluxo de recuperação de senha por
código, substituído pela Pergunta de Segurança.

## `api_client.php`

Cliente HTTP para um modo alternativo de operação via API central
externa. **Não está em uso** — o modo ativo é `mysql` (ver
`config.php`, `data.mode`).