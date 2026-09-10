# `pages/` — Telas do sistema

Cada arquivo aqui contém **uma ou mais funções `render_*()`**, chamadas
por `render_page()` em `index.php` de acordo com o `?page=` da URL.
Nenhum arquivo desta pasta é acessado diretamente pelo navegador.

O HTML é escrito diretamente misturado com PHP (sem template engine).
Todo valor vindo do banco ou do usuário passa pela função `h()` antes
de ir para a tela, para evitar XSS.

---

## `auth/` — Telas públicas (sem login)

| Arquivo | Função | Tela |
|---|---|---|
| `login.php` | `render_login()` | Formulário de entrada, com o seletor "Clínica / Médico" |
| `recuperar_senha.php` | `render_forgot_password()` | Passo 1: informar o e-mail |
| | `render_reset_security_question()` | Passo 2: responder a pergunta de segurança |
| | `render_reset_password()` | Passo 3: definir a nova senha |

## `admin/` — Painel do administrador

| Arquivo | Função | Tela |
|---|---|---|
| `dashboard.php` | `render_admin_dashboard()` | "Geral" — números do dia + agenda de hoje |
| `calendario.php` | `render_admin_calendar()` | Calendário mensal de toda a clínica |
| | `render_calendar_component(...)` | O componente de grade do calendário — **compartilhado** com `pages/medico/calendario.php` |
| `consultas.php` | `render_admin_appointments()` | Lista de consultas + filtros + o modal "Adicionar Nova Consulta" (autocompletar, calendário visual de dias disponíveis, seleção de horário) |
| `pacientes.php` | `render_admin_patients()` | Lista/busca de pacientes, cadastro e edição |
| `medicos.php` | `render_admin_doctors()` | Lista de médicos, cadastro/edição, agenda semanal e bloqueios |
| | `render_doctor_fields(...)` | Os campos do formulário de médico — reaproveitados entre "cadastrar" e "editar" |
| `relatorios.php` | `render_admin_reports()` | Tabela De/Até + card **"Movimentação mensal"** (gráfico Consultas x Faltas via Chart.js, classificação Alta/Boa/Baixa, botões "Atualizar gráfico" e "Imprimir relatório") + o bloco `.print-report` (nunca visível na tela — só aparece no papel, ao imprimir) |

## `medico/` — Painel do médico

| Arquivo | Função | Tela |
|---|---|---|
| `dashboard.php` | `render_doctor_dashboard($user)` | "Geral" — agenda do dia deste médico |
| `calendario.php` | `render_doctor_calendar($user)` | Calendário mensal só deste médico |
| `consultas.php` | `render_doctor_appointments($user)` | Lista de consultas deste médico, com filtros |
| `pacientes.php` | `render_doctor_patients($user)` | Lista/busca dos pacientes já atendidos por este médico |
| `historico.php` | `render_doctor_patient_history($user)` | Histórico de prontuários de um paciente específico |
| | `render_medical_record_cards(...)` | Os cartões de prontuário em si — reaproveitados aqui e em `prontuario.php` |
| `prontuario.php` | `render_doctor_detail($user)` | Detalhe de uma consulta específica |
| | `render_doctor_consultation($user)` | Formulário de registro do prontuário |

---

## Telas que **não** ficam em `pages/`

Telas comuns a qualquer usuário logado (perfil, notificações) e os
modais que aparecem em cima do layout (tutorial guiado, aceite de
Termos de Uso) são renderizados direto em `index.php` (funções
`render_profile()`, `render_notifications()`, `render_tutorial_modal()`,
`render_terms_modal()`).