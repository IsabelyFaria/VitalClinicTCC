# Vital Clinic — Painel Web (Admin + Médico) — MedAdmin

Sistema de agendamento e gestão de consultas para clínicas médicas. Esta
pasta (`VitalClinic-MedAdmin`) contém o **painel web** (PHP + MySQL)
usado por **administradores de clínica** e **médicos** para gerenciar
consultas, pacientes, prontuários, relatórios e a própria agenda —
agora com **múltiplas clínicas isoladas entre si** e um fluxo de
**convite de primeiro acesso** para ativar cada clínica nova.

> Este projeto tem uma pasta irmã, `VitalClinic-Paciente`, com o
> portal de acesso do próprio paciente (login, agendamento, histórico,
> notificações). Este README documenta só o **MedAdmin**.

Cada pasta principal tem seu próprio `README.md`:

- [`app/README.md`](app/README.md) — a lógica do sistema (PHP puro)
- [`pages/README.md`](pages/README.md) — as telas (uma função por página)
- [`assets/README.md`](assets/README.md) — CSS, JavaScript e a logo
- [`db/README.md`](db/README.md) — os dois arquivos SQL (estrutura + dados)
- [`migrations/README.md`](migrations/README.md) — histórico de mudanças no banco
- [`scripts/README.md`](scripts/README.md) — scripts de linha de comando

---

## Índice

1. [O que é o sistema](#1-o-que-é-o-sistema)
2. [Instalação](#2-instalação)
3. [Credenciais de acesso](#3-credenciais-de-acesso)
4. [Como o sistema funciona por dentro](#4-como-o-sistema-funciona-por-dentro)
5. [Funcionalidades — tela por tela](#5-funcionalidades--tela-por-tela)
6. [Isolamento por clínica e super admin](#6-isolamento-por-clínica-e-super-admin)
7. [Convite de primeiro acesso](#7-convite-de-primeiro-acesso)
8. [Recursos de experiência do usuário](#8-recursos-de-experiência-do-usuário)
9. [Recuperação de senha](#9-recuperação-de-senha)
10. [Banco de dados](#10-banco-de-dados)
11. [Scripts de população (dados fictícios)](#11-scripts-de-população-dados-fictícios)
12. [Segurança](#12-segurança)
13. [Estrutura de pastas](#13-estrutura-de-pastas)
14. [Referência das ações do sistema](#14-referência-das-ações-do-sistema)
15. [Limitações conhecidas](#15-limitações-conhecidas)

---

## 1. O que é o sistema

O Vital Clinic MedAdmin é o painel administrativo de uma **rede de
clínicas** — cada clínica só vê e gerencia os próprios dados (mais
detalhes na [seção 6](#6-isolamento-por-clínica-e-super-admin)). Nele,
a equipe consegue:

- Cadastrar e gerenciar **médicos** (especialidade, CRM, horários de
  atendimento semanais, bloqueios pontuais de agenda);
- Cadastrar **pacientes**;
- **Agendar consultas**, com um calendário visual que já mostra os dias
  em que cada médico atende;
- Acompanhar a **agenda do dia/mês** de toda a clínica ou de um médico
  específico, com um seletor de mês pra pular direto pra qualquer
  período, sem precisar navegar mês a mês;
- Registrar o **prontuário** de cada consulta concluída;
- Acompanhar **relatórios** com gráficos (consultas x faltas, um
  indicador de "movimentação mensal" — alta/boa/baixa — e um relatório
  pronto pra imprimir em A4);
- Fazer um **tour guiado** na primeira vez que loga, e aceitar os
  **Termos de Uso** antes de usar o sistema;
- Gerenciar o próprio **perfil** (agora num menu com a foto/inicial do
  usuário, no canto superior direito — Perfil, Sobre nós e Sair).

---

## 2. Instalação

**Requisitos:** XAMPP (Apache + MySQL/MariaDB + PHP 8+), navegador atual.

1. Copie esta pasta (`VitalClinic-MedAdmin`) para dentro do seu `htdocs`.
2. Ligue **Apache** e **MySQL** no painel do XAMPP.
3. Abra o **phpMyAdmin** e execute, **nesta ordem**, os dois arquivos
   da pasta `db/` (pela aba **Importar**, não colando na aba SQL —
   arquivos grandes podem cortar no meio ao colar):
   1. `db/vitalclinic_estrutura.sql` — cria o banco `vitalclinic` do
      zero (⚠️ apaga qualquer banco de mesmo nome que já exista).
   2. `db/vitalclinic_dados.sql` — popula com os dados de demonstração
      e vários lotes de dados fictícios.
4. Acesse a URL da pasta no navegador.

> **Já tem um banco criado e não quer perder dados?** Não rode
> `db/vitalclinic_estrutura.sql` de novo. Aplique só as migrations que
> ainda faltam — veja [`migrations/README.md`](migrations/README.md).

---

## 3. Credenciais de acesso

Senha de **todas** as contas abaixo: **`password`**.

### Contas fixas — sempre existem, uma por clínica

Essas são as contas mais confiáveis pra testar o isolamento por
clínica: cada uma pertence a uma clínica diferente, então dá pra
comparar lado a lado o que cada uma vê.

| Clínica | Perfil | Nome | E-mail |
|---|---|---|---|
| **Clínica Central** | 👑 Super admin (vê TODAS as clínicas) | Administrador da Clínica | `admin@clinica.local` |
| **Clínica Central** | Médico | Dra. Ana Souza — Clínico geral | `medico@clinica.local` |
| **Clínica Norte** | Médico | Dr. Carlos Lima — Cardiologia | `carlos.lima@clinicanorte.local` |

Essas 3 contas já têm uma **pergunta de segurança** cadastrada, pra
testar a recuperação de senha sem configurar nada antes:

| Conta | Pergunta | Resposta |
|---|---|---|
| `admin@clinica.local` | Qual o nome do seu primeiro animal de estimação? | `Rex` |
| `medico@clinica.local` | Qual foi o nome da sua primeira escola? | `Colégio Santa Rita` |
| `carlos.lima@clinicanorte.local` | Qual é a sua cidade natal? | `Recife` |

### Contas fictícias (dos lotes de seed) — agrupadas por clínica

O banco também vem com **mais de 20 clínicas** e dezenas de médicos e
administradores fictícios, todos com senha `password` — úteis
principalmente pra testar o isolamento (logar com o admin de uma
clínica e confirmar que só aparece o que é dela).

Como esses lotes foram gerados em momentos diferentes do
desenvolvimento, listar cada um manualmente aqui ficaria desatualizado
rápido. Em vez disso, rode esta consulta no phpMyAdmin sempre que
precisar da lista **atual e correta**, já agrupada por clínica:

```sql
SELECT c.name AS clinica, u.role AS perfil, u.name AS nome, u.email AS email
FROM users u
JOIN clinics c ON c.id = u.clinic_id
WHERE u.role IN ('admin', 'doctor')
ORDER BY c.name, u.role DESC, u.name;
```

Isso devolve uma linha por administrador/médico, ordenada por clínica
— é só rolar pra ver, por exemplo, os 2-3 primeiros de cada clínica
diferente e usar um admin e um médico de clínicas diferentes pra testar
se um não está vendo os dados do outro (veja o roteiro de teste na
[seção 6](#6-isolamento-por-clínica-e-super-admin)).

> Rodando `scripts/seed_producao.php`, mais contas são geradas (e-mail
> terminado em `@seed3.local`) — a consulta acima já pega essas também,
> sem precisar de nada extra.

**Cadastrando o admin de uma clínica nova:** não precisa mexer em SQL
— use o fluxo de convite (ver [seção 7](#7-convite-de-primeiro-acesso)),
disponível pro super admin (`admin@clinica.local`).

---

## 4. Como o sistema funciona por dentro

O projeto **não usa nenhum framework** — é PHP puro (sem Composer),
com JavaScript "vanilla" (sem React/Vue) e CSS puro (sem Tailwind).

- **`index.php`** — único ponto de entrada. Toda URL passa por ele
  (`?page=...` decide a tela, `?action=...` processa um formulário ou
  uma chamada AJAX).
- **`app/`** — a lógica de negócio (ver `app/README.md`).
- **`pages/`** — uma função por tela (ver `pages/README.md`).
- **`assets/`** — CSS, JavaScript e a logo (ver `assets/README.md`).
- **`db/`** — os dois arquivos SQL: estrutura e dados (ver `db/README.md`).

---

## 5. Funcionalidades — tela por tela

### Administrador

| Menu | O que faz |
|---|---|
| **Geral** | Números do dia (consultas de hoje, pendentes, total de pacientes, total de médicos) e a agenda do dia — sempre restritos à própria clínica (exceto pro super admin). |
| **Calendário** | Visão do mês inteiro, com um seletor de mês/ano pra pular direto pra qualquer período, além das setas "mês anterior/próximo". |
| **Consultas** | Lista com filtros + modal "Adicionar Nova Consulta" (autocompletar de paciente/médico da própria clínica, calendário visual de dias disponíveis, horário, tipo, observações). |
| **Pacientes** | Lista/busca, cadastro, edição, histórico de consultas de cada um. |
| **Médicos** | Lista, cadastro/edição, agenda semanal e bloqueios pontuais. |
| **Relatórios** | Tabela com filtro De/Até; card de "Movimentação mensal" com gráfico (Consultas x Faltas) e classificação automática (Alta/Boa/Baixa movimentação); botão para imprimir um relatório em A4. |
| **Convites** *(só super admin)* | Cadastra uma clínica nova e gera um link de primeiro acesso pra ela — ver [seção 7](#7-convite-de-primeiro-acesso). |
| **Menu do avatar** (bolinha no canto superior direito) | Perfil, Sobre nós, Sair. |

### Médico

| Menu | O que faz |
|---|---|
| **Geral** | Agenda do dia deste médico. |
| **Calendário** | Igual ao do admin, só com a agenda deste médico — mesmo seletor de mês/ano. |
| **Consultas** | Lista das consultas deste médico, com filtros. |
| **Pacientes** | Pacientes já atendidos por este médico, com busca. |
| **Prontuário / Histórico** | Registro do prontuário e histórico de prontuários anteriores de cada paciente. |
| **Menu do avatar** | Perfil, Sobre nós, Sair. |

---

## 6. Isolamento por clínica e super admin

Cada administrador **só vê os dados da própria clínica** (`users.clinic_id`)
— consultas, pacientes, médicos, calendário e relatórios. Um médico já
era isolado por natureza (só enxerga o que é dele mesmo).

A única exceção é o **super admin** — hoje, só `admin@clinica.local`
(marcado por `users.is_super_admin = 1`) — que enxerga e gerencia
**todas** as clínicas cadastradas, inclusive podendo escolher a clínica
ao cadastrar médico/paciente (os demais admins não têm essa escolha:
todo cadastro que eles fazem entra automaticamente na própria clínica).

Isso vale tanto pra **visualização** quanto pra **escrita** — um admin
não consegue editar/remover um médico, paciente ou conceder acesso ADM
de outra clínica, mesmo tentando forçar o ID direto na URL/formulário.

### Como testar

1. Logue como `admin@clinica.local` (super admin) → confirme que vê
   várias clínicas nos filtros/listas.
2. Logue como um admin comum de uma clínica específica (use a consulta
   SQL da [seção 3](#3-credenciais-de-acesso) pra achar um) → confirme
   que só aparecem pacientes/médicos/consultas daquela clínica.
3. Logue como um admin de **outra** clínica → confirme que os dados
   são diferentes do passo anterior (nenhuma sobreposição).

---

## 7. Convite de primeiro acesso

Em vez de cadastrar o admin de uma clínica nova direto no banco, o
**super admin** consegue fazer isso pela própria tela (**menu
"Convites"**, só visível pra ele):

1. Preenche os dados da clínica (nome, CNPJ, endereço, etc.) e o
   e-mail de quem vai ser a administradora dela.
2. O sistema cadastra a clínica e gera um **link de convite** — único,
   com validade de 72h (configurável em `app/config.php`,
   `rules.invite_valid_hours`), mostrado na tela pra copiar.
3. Esse link é enviado por fora do sistema (WhatsApp, e-mail — não
   depende de nenhum envio automático).
4. Quem recebe o link define o próprio nome e senha, e já entra
   logada como administradora daquela clínica.

Um convite só pode ser usado **uma vez**; dá pra revogar antes disso
se for enviado por engano.

---

## 8. Recursos de experiência do usuário

- **Menu centralizado em pílula** — no desktop, fica centralizado no
  topo, entre a logo e o menu do avatar, via flexbox (não é um valor
  fixo em pixel — se ajusta sozinho). No celular/tablet vira uma
  gaveta com ☰.
- **Menu do avatar** — bolinha com a inicial do nome, no canto
  superior direito; passar o mouse (ou tocar, no celular) abre Perfil
  / Sobre nós / Sair.
- **Tutorial guiado de primeiro acesso** — tour com destaque visual
  sobre os itens reais do menu, diferente por perfil.
- **Aceite de Termos de Uso** — modal bloqueante no primeiro login.
- **Calendário visual no agendamento** — o modal de nova consulta
  mostra um calendário de verdade, destacando os dias em que o médico
  escolhido atende.
- **Seletor de mês no calendário** — além das setas de navegação, um
  campo de mês/ano pra pular direto pra qualquer período.
- **Relatórios com gráfico** — Chart.js (carregado só nessa tela),
  com um relatório imprimível formatado pra A4.
- **Busca padronizada** — campos de busca de Pacientes e Médicos com
  o mesmo visual, ocupando o espaço disponível certinho.
- **Totalmente responsivo** — mobile-first, 3 níveis de tela.
- **Fonte Nunito** (Google Fonts) em todo o site.

---

## 9. Recuperação de senha

O sistema **não envia código por e-mail** — a recuperação é feita
inteiramente pela **Pergunta de Segurança** cadastrada no perfil.

1. Tela de login → "Esqueci minha senha" → informa o e-mail.
2. Sistema mostra a pergunta de segurança (ou uma mensagem genérica,
   se a conta não existir/não tiver pergunta — pra não revelar quais
   e-mails existem).
3. Responde (ignora maiúsculas/acentos/espaços) → libera nova senha.

A resposta nunca é salva em texto puro — só o hash. Login em si já dá
mensagens específicas (conta não encontrada, inativa, perfil errado,
senha incorreta) — seguro aqui porque quem loga é sempre uma conta
provisionada pela própria clínica.

---

## 10. Banco de dados

| Tabela | O que guarda |
|---|---|
| `clinics` | Unidades/clínicas cadastradas (multi-clínica). |
| `specialties` | Especialidades médicas. |
| `users` | Administradores, médicos e pacientes — `role` diferencia quem é quem; `clinic_id` isola por clínica; `is_super_admin` marca a exceção que vê tudo. |
| `doctors` | Dados profissionais do médico — 1:1 com `users`. |
| `doctor_schedules` | Grade semanal fixa de atendimento. |
| `schedule_blocks` | Bloqueios pontuais (férias, congresso, feriado). |
| `appointment_slots` | Cada horário específico gerado. |
| `appointments` | As consultas. |
| `medical_records` | Prontuário de cada consulta concluída. |
| `payments` | Cobrança de cada consulta. |
| `notifications` | Avisos sobre consultas. |
| `admin_invites` | Convites de primeiro acesso (token, clínica, validade, status). |

Veja a lista completa de colunas em `db/vitalclinic_estrutura.sql`, e
o histórico de cada mudança em [`migrations/README.md`](migrations/README.md).

---

## 11. Scripts de população (dados fictícios)

| Onde | Como rodar | O que gera |
|---|---|---|
| `db/vitalclinic_dados.sql` | Já roda junto na instalação (seção 2) | Vários lotes de dados fictícios já mesclados num arquivo só |
| `scripts/seed_producao.php` | `php scripts/seed_producao.php` no terminal | Mais um lote, com quantidades ajustáveis, e-mails aleatórios a cada execução |

Todos usam senha **`password`**. Veja `db/README.md` para os detalhes
de cada lote.

---

## 12. Segurança

- Senhas: hash com `password_hash()` (bcrypt).
- Resposta da pergunta de segurança: mesmo hash, normalizada.
- Todo formulário usa **token CSRF**.
- **Isolamento por clínica** reforçado no servidor (nunca só na tela)
  — ver [seção 6](#6-isolamento-por-clínica-e-super-admin).
- Convite de primeiro acesso: token aleatório de 64 caracteres, uso
  único, com validade.
- Mensagens do "Esqueci minha senha" são genéricas; as de login já são
  específicas (ver seção 9).

---

## 13. Estrutura de pastas

```
VitalClinic-MedAdmin/
├── index.php                  # ponto de entrada único (rotas + layout)
├── manifest.webmanifest       # metadados do PWA
├── service-worker.js          # cache offline do PWA
├── README.md                  # este arquivo
├── db/             # os 2 arquivos SQL (estrutura + dados) → db/README.md
├── app/            # lógica de negócio                     → app/README.md
├── pages/          # telas (uma função por página)         → pages/README.md
├── assets/         # CSS, JS e logo                        → assets/README.md
├── migrations/     # histórico de mudanças no banco         → migrations/README.md
└── scripts/        # scripts de linha de comando             → scripts/README.md
```

> Pasta irmã (fora desta): `VitalClinic-Paciente` — o portal de acesso
> do paciente, um projeto separado deste painel.

---

## 14. Referência das ações do sistema

Toda ação que grava algo no banco passa por `index.php?action=...`
(via `POST`):

| Ação | O que faz |
|---|---|
| `login` / `logout` | Entrar / sair da conta |
| `request_password_reset` / `verify_security_answer` / `reset_password` / `cancel_password_reset` | As etapas do "Esqueci minha senha" |
| `mark_tutorial_seen` | Marca que o tutorial guiado já foi visto/pulado |
| `accept_terms` | Registra o aceite dos Termos de Uso |
| `update_staff_profile` | Salva o perfil (dados, senha, pergunta de segurança) |
| `admin_create_appointment` | Agenda uma nova consulta |
| `cancel` | Cancela uma consulta |
| `mark_appointment` | Muda o status de uma consulta |
| `admin_create_doctor` / `admin_update_doctor` / `admin_delete_doctor` | Gerenciar médicos (sempre restrito à própria clínica) |
| `admin_add_schedule` / `admin_delete_schedule` | Grade semanal do médico |
| `admin_add_block` | Bloqueio pontual na agenda de um médico |
| `admin_create_patient` / `admin_update_patient` | Gerenciar pacientes |
| `save_medical_record` | Salvar o prontuário de uma consulta |
| `admin_update_user_role` | Conceder/revogar acesso ADM (mesma clínica) |
| `admin_create_invite` / `admin_revoke_invite` | Gerar/revogar convite de primeiro acesso *(só super admin)* |
| `accept_invite` | Finalizar o cadastro a partir de um link de convite *(ação pública, sem login)* |
| `mark_notifications_read` | Marcar notificações como lidas |

Ações do tipo `GET` (só consultam): `?action=slots`,
`?action=doctor_weekdays`, `?action=monthly_movement`.

---

## 15. Limitações conhecidas

- Este README documenta só o **MedAdmin**. O acesso do paciente é
  feito pelo projeto irmão `VitalClinic-Paciente`.
- `app/mailer.php` e `app/api_client.php` continuam no projeto, mas
  não são chamados no funcionamento atual — o convite de primeiro
  acesso é copiado manualmente, não enviado por e-mail automático.
- Hoje só existe **um** super admin (`admin@clinica.local`). Pra tornar
  outra conta super admin: `UPDATE users SET is_super_admin = 1 WHERE email = '...';`