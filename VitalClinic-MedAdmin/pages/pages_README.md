# `pages/` — Telas do sistema

Cada arquivo aqui contém **uma ou mais funções `render_*()`**, chamadas
por `render_page()` em `index.php` de acordo com o `?page=` da URL.
Todo valor vindo do banco ou do usuário passa pela função `h()` antes
de ir pra tela.

---

## `auth/` — Telas públicas (sem login)

| Arquivo | Função | Tela |
|---|---|---|
| `login.php` | `render_login()` | Formulário de entrada, com o seletor "Clínica / Médico" |
| `recuperar_senha.php` | `render_forgot_password()`, `render_reset_security_question()`, `render_reset_password()` | As 3 etapas do "Esqueci minha senha" |
| `aceitar_convite.php` | `render_accept_invite()` | Tela de primeiro acesso a partir de um link de convite — mostra o formulário (nome + senha) se o token for válido, ou uma mensagem de link inválido/expirado |

## `admin/` — Painel do administrador

| Arquivo | Função | Tela |
|---|---|---|
| `dashboard.php` | `render_admin_dashboard($user)` | "Geral" — números do dia + agenda de hoje, restritos à clínica do admin (`admin_clinic_scope()`) |
| `calendario.php` | `render_admin_calendar($user)` | Calendário mensal, com o seletor de mês/ano além das setas de navegação |
| | `render_calendar_component(...)` | O componente de grade em si — compartilhado com `pages/medico/calendario.php` |
| `consultas.php` | `render_admin_appointments($user)` | Lista de consultas + o modal "Adicionar Nova Consulta" |
| `pacientes.php` | `render_admin_patients($user)` | Lista/busca, cadastro e edição — verifica posse (mesma clínica) antes de mostrar/editar um paciente específico |
| `medicos.php` | `render_admin_doctors($user)` | Lista, cadastro/edição, agenda semanal, bloqueios e a tabela "Gestão de acessos" (conceder/revogar ADM) |
| | `render_doctor_fields(...)` | Campos do formulário de médico — clínica travada (readonly) pra admin comum, ou um `<select>` de verdade pro super admin |
| `relatorios.php` | `render_admin_reports($user)` | Tabela De/Até + card "Movimentação mensal" (gráfico via Chart.js) + o relatório imprimível (nunca visível na tela, só ao imprimir) |
| `convites.php` | `render_admin_invites($user)` | *(só super admin)* Cadastra clínica nova + gera link de convite de primeiro acesso; lista os convites já gerados com status |

## `medico/` — Painel do médico

| Arquivo | Função | Tela |
|---|---|---|
| `dashboard.php` | `render_doctor_dashboard($user)` | Agenda do dia deste médico |
| `calendario.php` | `render_doctor_calendar($user)` | Calendário mensal só deste médico |
| `consultas.php` | `render_doctor_appointments($user)` | Lista de consultas deste médico |
| `pacientes.php` | `render_doctor_patients($user)` | Pacientes já atendidos por este médico |
| `historico.php` | `render_doctor_patient_history($user)`, `render_medical_record_cards(...)` | Histórico de prontuários de um paciente |
| `prontuario.php` | `render_doctor_detail($user)`, `render_doctor_consultation($user)` | Detalhe de consulta + formulário de registro do prontuário |

---

## Telas que **não** ficam em `pages/`

Comuns a qualquer usuário logado — `render_profile()`,
`render_notifications()`, `render_about()` (a página "Sobre nós", nova,
acessível pelo menu do avatar) — e os modais de primeiro acesso
(`render_tutorial_modal()`, `render_terms_modal()`) ficam direto em
`index.php`.