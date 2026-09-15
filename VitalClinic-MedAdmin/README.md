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
      zero (apaga qualquer banco de mesmo nome que já exista).
   2. `db/vitalclinic_dados.sql` — popula com os dados de demonstração
      e vários lotes de dados fictícios.
4. Acesse a URL da pasta no navegador.

> **Já tem um banco criado e não quer perder dados?** Não rode
> `db/vitalclinic_estrutura.sql` de novo. Aplique só as migrations que
> ainda faltam — veja [`migrations/README.md`](migrations/README.md).

---

## 3. Credenciais de acesso

### Senha de **todas** as contas abaixo: **`password`**.

Extraído diretamente do `db/vitalclinic_dados.sql` (parseando o arquivo
na mesma ordem que o MySQL executaria, resolvendo as variáveis
`@clinicN` de verdade) — reflete exatamente o que entra no banco
quando você importa esse arquivo.

### Clínica Central (contas fixas de demonstração)

| Perfil | Nome | E-mail |
|---|---|---|
| Super admin | Administrador da Clínica | `admin@clinica.local` |

### Clínica Norte (conta fixa de demonstração)

| Perfil | Nome | E-mail |
|---|---|---|
| Médico | Dr. Carlos Lima — Cardiologia | `carlos.lima@clinicanorte.local` |

---

### Demais clínicas (dados fictícios gerados)

### Centro Médico Aurora — Santos/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | André Andrade Ferreira | `andre.andrade.ferreira.7402@seed5.local` |
| Administrador | Roberto Almeida Correia | `roberto.almeida.correia.1933@seed5.local` |
| Médico | Dr. Antônio Santos Gomes | `antonio.santos.gomes.1526@seed5.local` |
| Médico | Dra. Camila Nascimento Soares | `camila.nascimento.soares.5869@seed5.local` |

### Centro Médico Nova Saúde — Belo Horizonte/MG

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Natalia Santos Pereira | `natalia.santos.pereira.8107@seed5.local` |
| Administrador | Pedro Rodrigues Gomes | `pedro.rodrigues.gomes.9906@seed5.local` |
| Médico | Dr. Cristiano Souza Teixeira | `cristiano.souza.teixeira.5708@seed5.local` |
| Médico | Dr. Rafael Andrade Marques | `rafael.andrade.marques.7422@seed5.local` |
| Médico | Dra. Camila Cavalcanti Nascimento | `camila.cavalcanti.nascimento.7691@seed5.local` |

### Centro Médico Nova Saúde — Campinas/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Larissa Carvalho Nascimento | `larissa.carvalho.nascimento.6167@seed5.local` |
| Médico | Dr. Gustavo Souza Ramos | `gustavo.souza.ramos.2558@seed5.local` |
| Médico | Dr. Matheus Soares Reis | `matheus.soares.reis.9928@seed5.local` |

### Centro Médico Santa Clara — São Paulo/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Carlos Almeida Cardoso | `carlos.almeida.cardoso.5607@seed4.local` |
| Administrador | Sérgio Lima Andrade | `sergio.lima.andrade.4718@seed4.local` |
| Médico | Dr. Thiago Ramos Fernandes | `thiago.ramos.fernandes.6758@seed4.local` |
| Médico | Dr. Vinícius Ribeiro Vieira | `vinicius.ribeiro.vieira.9365@seed4.local` |
| Médico | Dra. Debora Reis Fernandes | `debora.reis.fernandes.9883@seed4.local` |
| Médico | Dra. Maria Cavalcanti Fernandes | `maria.cavalcanti.fernandes.9536@seed4.local` |

### Clínica Central

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Carlos Martins Rocha | `carlos.martins.rocha.adm.383@seed2.local` |

### Clínica Harmonia — Sorocaba/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Daniel Cavalcanti Rocha | `daniel.cavalcanti.rocha.5903@seed4.local` |
| Administrador | Paulo Cardoso Fernandes | `paulo.cardoso.fernandes.4648@seed4.local` |
| Médico | Dra. Amanda Ribeiro Gomes | `amanda.ribeiro.gomes.9408@seed4.local` |
| Médico | Dra. Leticia Santos Martins | `leticia.santos.martins.6725@seed4.local` |

### Clínica Renascer

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Eliane Rodrigues Pinto | `eliane.rodrigues.pinto.adm.465@seed2.local` |
| Médico | Dr(a). Daniel Marques Soares | `daniel.marques.soares.365@seed2.local` |
| Médico | Dr(a). Otávio Nascimento Marques | `otavio.nascimento.marques.780@seed2.local` |
| Médico | Dr(a). Sérgio Ramos Andrade | `sergio.ramos.andrade.716@seed2.local` |
| Médico | Dr(a). Talita Lima Machado | `talita.lima.machado.471@seed2.local` |

### Clínica Renascer — Campinas/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Fábio Ramos Ribeiro | `fabio.ramos.ribeiro.5722@seed4.local` |
| Administrador | Priscila Lima Gomes | `priscila.lima.gomes.8220@seed4.local` |
| Médico | Dr. Cristiano Castro Alves | `cristiano.castro.alves.3413@seed4.local` |
| Médico | Dra. Gabriela Soares Teixeira | `gabriela.soares.teixeira.9631@seed4.local` |

### Clínica Sul

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Cristiano Pereira Ramos | `cristiano.pereira.ramos.adm.309@seed2.local` |

### Clínica Vitalità

| Perfil | Nome | E-mail |
|---|---|---|
| Médico | Dr(a). Gustavo Oliveira Vieira | `gustavo.oliveira.vieira.973@seed2.local` |
| Médico | Dr(a). Igor Cavalcanti Andrade | `igor.cavalcanti.andrade.427@seed2.local` |
| Médico | Dr(a). Paulo Almeida Santos | `paulo.almeida.santos.745@seed2.local` |
| Médico | Dr(a). Roberto Fernandes Carvalho | `roberto.fernandes.carvalho.156@seed2.local` |

### Espaço Saúde Mais

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Marcelo Ferreira Ribeiro | `marcelo.ferreira.ribeiro.adm.161@seed2.local` |
| Médico | Dr(a). Adriana Silva Pinto | `adriana.silva.pinto.915@seed2.local` |
| Médico | Dr(a). Leonardo Freitas Marques | `leonardo.freitas.marques.290@seed2.local` |
| Médico | Dr(a). Sérgio Lopes Martins | `sergio.lopes.martins.611@seed2.local` |
| Médico | Dr(a). Viviane Dias Cavalcanti | `viviane.dias.cavalcanti.307@seed2.local` |

### Espaço Saúde Raízes — Curitiba/PR

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Maria Dias Castro | `maria.dias.castro.2732@seed5.local` |
| Administrador | Priscila Nascimento Machado | `priscila.nascimento.machado.7559@seed5.local` |
| Médico | Dr. Felipe Lima Monteiro | `felipe.lima.monteiro.9035@seed5.local` |
| Médico | Dra. Leticia Alves Rodrigues | `leticia.alves.rodrigues.5308@seed5.local` |

### Espaço Saúde Santa Clara — Belo Horizonte/MG

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Alexandre Carvalho Ribeiro | `alexandre.carvalho.ribeiro.8498@seed5.local` |
| Administrador | Marcos Reis Cavalcanti | `marcos.reis.cavalcanti.7977@seed5.local` |
| Médico | Dr. André Vieira Lima | `andre.vieira.lima.4626@seed5.local` |
| Médico | Dr. Gilberto Silva Almeida | `gilberto.silva.almeida.3189@seed5.local` |

### Instituto Harmonia — Campinas/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Aline Pinto Ferreira | `aline.pinto.ferreira.8673@seed5.local` |
| Administrador | Ricardo Pinto Fernandes | `ricardo.pinto.fernandes.5176@seed5.local` |
| Médico | Dr. Igor Freitas Fernandes | `igor.freitas.fernandes.5692@seed5.local` |
| Médico | Dr. Pedro Alves Santos | `pedro.alves.santos.7617@seed5.local` |
| Médico | Dr. Sérgio Cardoso Pereira | `sergio.cardoso.pereira.1075@seed5.local` |

### Instituto Primavera — São Paulo/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Carlos Barbosa Cavalcanti | `carlos.barbosa.cavalcanti.9449@seed4.local` |
| Médico | Dra. Adriana Lopes Nascimento | `adriana.lopes.nascimento.6231@seed4.local` |
| Médico | Dra. Talita Ribeiro Nunes | `talita.ribeiro.nunes.8398@seed4.local` |

### Policlínica Aurora — Ribeirão Preto/SP

| Perfil | Nome | E-mail |
|---|---|---|
| Administrador | Henrique Pinto Gomes | `henrique.pinto.gomes.6619@seed4.local` |
| Administrador | Sandra Almeida Vieira | `sandra.almeida.vieira.7638@seed4.local` |
| Médico | Dr. Roberto Alves Monteiro | `roberto.alves.monteiro.8729@seed4.local` |
| Médico | Dra. Talita Reis Ferreira | `talita.reis.ferreira.2357@seed4.local` |


**Cadastrando o admin de uma clínica nova:** use o fluxo de convite (ver [seção 7](#7-convite-de-primeiro-acesso)), disponível pro super admin (`admin@clinica.local`).

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
