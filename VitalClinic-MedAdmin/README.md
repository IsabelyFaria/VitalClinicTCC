# Vital Clinic — Painel Web (Admin + Médico) — MedAdmin

Sistema de agendamento e gestão de consultas para clínicas médicas. Esta
pasta (`VitalClinic-MedAdmin`) contém o **painel web** (PHP + MySQL)
usado por **administradores da clínica** e **médicos** para gerenciar
consultas, pacientes, prontuários, relatórios e a própria agenda.

> Este projeto tem uma pasta irmã, `VitalClinic-Paciente`, com o
> portal de acesso do próprio paciente (login, agendamento,
> notificações). Este README documenta só o **MedAdmin** — o painel
> administrativo/médico, que foi o escopo deste desenvolvimento.

Cada pasta principal tem seu próprio `README.md` com detalhes de
arquivo por arquivo:

- [`app/README.md`](app/README.md) — a "lógica" do sistema (PHP puro)
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
6. [Recursos de experiência do usuário](#6-recursos-de-experiência-do-usuário)
7. [Recuperação de senha](#7-recuperação-de-senha)
8. [Banco de dados](#8-banco-de-dados)
9. [Scripts de população (dados fictícios)](#9-scripts-de-população-dados-fictícios)
10. [Segurança](#10-segurança)
11. [Estrutura de pastas](#11-estrutura-de-pastas)
12. [Referência das ações do sistema](#12-referência-das-ações-do-sistema)
13. [Limitações conhecidas](#13-limitações-conhecidas)

---

## 1. O que é o sistema

O Vital Clinic MedAdmin é o painel administrativo de uma rede de
clínicas. Nele, a equipe consegue:

- Cadastrar e gerenciar **médicos** (especialidade, CRM, horários de
  atendimento semanais, bloqueios pontuais de agenda);
- Cadastrar **pacientes**;
- **Agendar consultas**, com um calendário visual que já mostra os dias
  em que cada médico atende;
- Acompanhar a **agenda do dia/mês** de toda a clínica ou de um médico
  específico;
- Registrar o **prontuário** de cada consulta concluída;
- Acompanhar **relatórios** com gráficos (consultas x faltas, um
  indicador de "movimentação mensal" — alta/boa/baixa — e um relatório
  pronto pra imprimir em A4);
- Fazer um **tour guiado** na primeira vez que loga, e aceitar os
  **Termos de Uso** antes de usar o sistema;
- Gerenciar o próprio **perfil**, senha e pergunta de segurança.

Não existe fluxo de cadastro público neste painel — todo administrador
ou médico é cadastrado por um administrador já existente (ou, para o
primeiro admin de uma clínica nova, direto no banco — veja a seção de
[migrations](#12-referência-das-ações-do-sistema)).

---

## 2. Instalação

**Requisitos:** XAMPP (Apache + MySQL/MariaDB + PHP 8+), navegador atual.

1. Copie esta pasta (`VitalClinic-MedAdmin`) para dentro do seu
   `htdocs`.
2. Ligue **Apache** e **MySQL** no painel do XAMPP.
3. Abra o **phpMyAdmin** e execute, **nesta ordem**, os dois arquivos
   da pasta `db/`:
   1. `db/vitalclinic_estrutura.sql` — cria o banco `vitalclinic` do
      zero (⚠️ apaga qualquer banco de mesmo nome que já exista) e
      todas as tabelas.
   2. `db/vitalclinic_dados.sql` — popula com os dados de demonstração
      e vários lotes de dados fictícios (veja a
      [seção 9](#9-scripts-de-população-dados-fictícios)).

   Arquivo grande (alguns MB)? Prefira a aba **Importar** do
   phpMyAdmin em vez de colar o conteúdo na aba SQL — colar textos
   muito grandes pode cortar no meio sem avisar.
4. Acesse a URL da pasta no navegador (ex.:
   `http://localhost/VitalClinic-SITE.v3.03/VitalClinicTCC/VitalClinic-MedAdmin/`).

> **Já tem um banco criado e não quer perder dados?** Não rode
> `db/vitalclinic_estrutura.sql` de novo (ele apaga tudo). Aplique só
> as migrations que ainda faltam — veja
> [`migrations/README.md`](migrations/README.md).

---

## 3. Credenciais de acesso

Senha de **todas** as contas abaixo: **`password`**.

### Contas fixas de demonstração

| Perfil | E-mail | Observação |
|---|---|---|
| Administrador | `admin@clinica.local` | Clínica Central |
| Médico | `medico@clinica.local` | Dra. Ana Souza — Clínico geral |
| Médico | `carlos.lima@clinicanorte.local` | Dr. Carlos Lima — Cardiologia, Clínica Norte |

Na tela de login, escolha o botão **"Clínica"** para entrar como
administrador, ou **"Médico"** para entrar como médico.

Essas 3 contas já têm uma **pergunta de segurança** cadastrada, pra
testar a recuperação de senha sem configurar nada antes (veja a
[seção 7](#7-recuperação-de-senha)):

| Conta | Pergunta | Resposta |
|---|---|---|
| `admin@clinica.local` | Qual o nome do seu primeiro animal de estimação? | `Rex` |
| `medico@clinica.local` | Qual foi o nome da sua primeira escola? | `Colégio Santa Rita` |
| `carlos.lima@clinicanorte.local` | Qual é a sua cidade natal? | `Recife` |

### Contas fictícias (geradas pelo `db/vitalclinic_dados.sql`)

Todas com senha `password` também — extraídas direto do arquivo de
dados que está no seu projeto agora:

### Administradores gerados por seed (24)

| Nome | E-mail |
|---|---|
| Alexandre Carvalho Ribeiro | `alexandre.carvalho.ribeiro.8498@seed5.local` |
| Aline Pinto Ferreira | `aline.pinto.ferreira.8673@seed5.local` |
| André Andrade Ferreira | `andre.andrade.ferreira.7402@seed5.local` |
| Carlos Almeida Cardoso | `carlos.almeida.cardoso.5607@seed4.local` |
| Carlos Barbosa Cavalcanti | `carlos.barbosa.cavalcanti.9449@seed4.local` |
| Carlos Martins Rocha | `carlos.martins.rocha.adm.383@seed2.local` |
| Cristiano Pereira Ramos | `cristiano.pereira.ramos.adm.309@seed2.local` |
| Daniel Cavalcanti Rocha | `daniel.cavalcanti.rocha.5903@seed4.local` |
| Eliane Rodrigues Pinto | `eliane.rodrigues.pinto.adm.465@seed2.local` |
| Fábio Ramos Ribeiro | `fabio.ramos.ribeiro.5722@seed4.local` |
| Henrique Pinto Gomes | `henrique.pinto.gomes.6619@seed4.local` |
| Larissa Carvalho Nascimento | `larissa.carvalho.nascimento.6167@seed5.local` |
| Marcelo Ferreira Ribeiro | `marcelo.ferreira.ribeiro.adm.161@seed2.local` |
| Marcos Reis Cavalcanti | `marcos.reis.cavalcanti.7977@seed5.local` |
| Maria Dias Castro | `maria.dias.castro.2732@seed5.local` |
| Natalia Santos Pereira | `natalia.santos.pereira.8107@seed5.local` |
| Paulo Cardoso Fernandes | `paulo.cardoso.fernandes.4648@seed4.local` |
| Pedro Rodrigues Gomes | `pedro.rodrigues.gomes.9906@seed5.local` |
| Priscila Lima Gomes | `priscila.lima.gomes.8220@seed4.local` |
| Priscila Nascimento Machado | `priscila.nascimento.machado.7559@seed5.local` |
| Ricardo Pinto Fernandes | `ricardo.pinto.fernandes.5176@seed5.local` |
| Roberto Almeida Correia | `roberto.almeida.correia.1933@seed5.local` |
| Sandra Almeida Vieira | `sandra.almeida.vieira.7638@seed4.local` |
| Sérgio Lima Andrade | `sergio.lima.andrade.4718@seed4.local` |

### Médicos gerados por seed (38)

| Nome | E-mail |
|---|---|
| Dr(a). Adriana Silva Pinto | `adriana.silva.pinto.915@seed2.local` |
| Dr(a). Daniel Marques Soares | `daniel.marques.soares.365@seed2.local` |
| Dr(a). Gustavo Oliveira Vieira | `gustavo.oliveira.vieira.973@seed2.local` |
| Dr(a). Igor Cavalcanti Andrade | `igor.cavalcanti.andrade.427@seed2.local` |
| Dr(a). Leonardo Freitas Marques | `leonardo.freitas.marques.290@seed2.local` |
| Dr(a). Otávio Nascimento Marques | `otavio.nascimento.marques.780@seed2.local` |
| Dr(a). Paulo Almeida Santos | `paulo.almeida.santos.745@seed2.local` |
| Dr(a). Roberto Fernandes Carvalho | `roberto.fernandes.carvalho.156@seed2.local` |
| Dr(a). Sérgio Lopes Martins | `sergio.lopes.martins.611@seed2.local` |
| Dr(a). Sérgio Ramos Andrade | `sergio.ramos.andrade.716@seed2.local` |
| Dr(a). Talita Lima Machado | `talita.lima.machado.471@seed2.local` |
| Dr(a). Viviane Dias Cavalcanti | `viviane.dias.cavalcanti.307@seed2.local` |
| Dr. André Vieira Lima | `andre.vieira.lima.4626@seed5.local` |
| Dr. Antônio Santos Gomes | `antonio.santos.gomes.1526@seed5.local` |
| Dr. Cristiano Castro Alves | `cristiano.castro.alves.3413@seed4.local` |
| Dr. Cristiano Souza Teixeira | `cristiano.souza.teixeira.5708@seed5.local` |
| Dr. Felipe Lima Monteiro | `felipe.lima.monteiro.9035@seed5.local` |
| Dr. Gilberto Silva Almeida | `gilberto.silva.almeida.3189@seed5.local` |
| Dr. Gustavo Souza Ramos | `gustavo.souza.ramos.2558@seed5.local` |
| Dr. Igor Freitas Fernandes | `igor.freitas.fernandes.5692@seed5.local` |
| Dr. Matheus Soares Reis | `matheus.soares.reis.9928@seed5.local` |
| Dr. Pedro Alves Santos | `pedro.alves.santos.7617@seed5.local` |
| Dr. Rafael Andrade Marques | `rafael.andrade.marques.7422@seed5.local` |
| Dr. Roberto Alves Monteiro | `roberto.alves.monteiro.8729@seed4.local` |
| Dr. Sérgio Cardoso Pereira | `sergio.cardoso.pereira.1075@seed5.local` |
| Dr. Thiago Ramos Fernandes | `thiago.ramos.fernandes.6758@seed4.local` |
| Dr. Vinícius Ribeiro Vieira | `vinicius.ribeiro.vieira.9365@seed4.local` |
| Dra. Adriana Lopes Nascimento | `adriana.lopes.nascimento.6231@seed4.local` |
| Dra. Amanda Ribeiro Gomes | `amanda.ribeiro.gomes.9408@seed4.local` |
| Dra. Camila Cavalcanti Nascimento | `camila.cavalcanti.nascimento.7691@seed5.local` |
| Dra. Camila Nascimento Soares | `camila.nascimento.soares.5869@seed5.local` |
| Dra. Debora Reis Fernandes | `debora.reis.fernandes.9883@seed4.local` |
| Dra. Gabriela Soares Teixeira | `gabriela.soares.teixeira.9631@seed4.local` |
| Dra. Leticia Alves Rodrigues | `leticia.alves.rodrigues.5308@seed5.local` |
| Dra. Leticia Santos Martins | `leticia.santos.martins.6725@seed4.local` |
| Dra. Maria Cavalcanti Fernandes | `maria.cavalcanti.fernandes.9536@seed4.local` |
| Dra. Talita Reis Ferreira | `talita.reis.ferreira.2357@seed4.local` |
| Dra. Talita Ribeiro Nunes | `talita.ribeiro.nunes.8398@seed4.local` |

> Se você rodar `scripts/seed_producao.php` depois, ele gera **mais**
> contas (com e-mails aleatórios a cada execução, terminados em
> `@seed3.local`) — essas não entram nesta lista porque mudam a cada
> vez. Veja quais existem com:
> ```sql
> SELECT name, email, role FROM users WHERE email LIKE '%@seed3.local';
> ```

**Cadastrando o primeiro admin de uma clínica nova:** não existe tela
de "criar conta" — o primeiro administrador é inserido direto no
banco. Veja o exemplo em `migrations/007_criar_primeiro_admin.sql`.

---

## 4. Como o sistema funciona por dentro

O projeto **não usa nenhum framework** — é PHP puro (sem Composer),
com JavaScript "vanilla" (sem React/Vue) e CSS puro (sem Tailwind).

- **`index.php`** — único ponto de entrada. Toda URL passa por ele
  (`?page=...` decide a tela, `?action=...` processa um formulário ou
  uma chamada AJAX). Ele decide se a pessoa pode ver aquela página e
  desenha o layout comum (topo + menu + conteúdo + modais de primeiro
  acesso).
- **`app/`** — a lógica de negócio (ver `app/README.md`).
- **`pages/`** — uma função por tela, agrupadas por área (ver
  `pages/README.md`).
- **`assets/`** — CSS, JavaScript e a logo (ver `assets/README.md`).
- **`db/`** — os dois arquivos SQL: estrutura e dados (ver `db/README.md`).

**Como uma ação típica funciona, de ponta a ponta** (exemplo: agendar
uma consulta):

1. O admin clica em **"+ Adicionar Nova Consulta"** — abre um modal,
   sem recarregar a página.
2. Digita o nome do paciente e do médico — autocompletar local (sem ida
   ao servidor a cada letra).
3. Ao escolher o médico, um **calendário visual** dentro do modal busca
   (`?action=doctor_weekdays`) os dias da semana em que ele atende, e
   já destaca esses dias.
4. Ao clicar num dia disponível, busca (`?action=slots`) os horários
   livres daquele médico naquele dia.
5. Ao confirmar, o formulário é enviado via `fetch()` para
   `index.php?action=admin_create_appointment`, que grava numa única
   transação (`appointment_slots` + `appointments` + `payments`).
6. O servidor responde em **JSON**; o modal fecha, um aviso de sucesso
   aparece, e a lista de consultas se atualiza sozinha.

---

## 5. Funcionalidades — tela por tela

### Administrador

| Menu | O que faz |
|---|---|
| **Geral** | Números do dia (consultas de hoje, pendentes, total de pacientes, total de médicos) e a agenda do dia. |
| **Calendário** | Visão do mês inteiro, com rolagem horizontal em telas estreitas. |
| **Consultas** | Lista com filtros + modal "Adicionar Nova Consulta" (autocompletar, calendário visual de dias disponíveis, horário, tipo, observações). |
| **Pacientes** | Lista/busca, cadastro, edição, histórico de consultas de cada um. |
| **Médicos** | Lista, cadastro/edição, agenda semanal e bloqueios pontuais. |
| **Relatórios** | Tabela com filtro De/Até; card de **"Movimentação mensal"** com gráfico (Consultas x Faltas) e classificação automática (Alta/Boa/Baixa movimentação) para qualquer mês escolhido; botão para **imprimir um relatório em A4** com os mesmos dados. |
| **Perfil** | Dados da conta, trocar senha, pergunta de segurança. |
| **Notificações** | Avisos do sistema sobre consultas. |

### Médico

| Menu | O que faz |
|---|---|
| **Geral** | Agenda do dia deste médico. |
| **Calendário** | Igual ao do admin, só com a agenda deste médico. |
| **Consultas** | Lista das consultas deste médico, com filtros. |
| **Pacientes** | Pacientes já atendidos por este médico, com busca. |
| **Prontuário / Histórico** | Registro do prontuário (peso, sinais vitais, diagnóstico, prescrição) e histórico de prontuários anteriores de cada paciente. |
| **Perfil** | Dados da conta, trocar senha, pergunta de segurança. |
| **Notificações** | Avisos sobre as consultas do médico. |

---

## 6. Recursos de experiência do usuário

- **Menu centralizado em pílula** — no desktop, fica centralizado no
  topo, num formato de pílula flutuante (matematicamente centralizado
  entre a logo e o espaço reservado do botão "Sair", via flexbox — não
  é um valor fixo em pixel). No celular/tablet vira uma gaveta com ☰.
- **Botão "Sair"** fixo no canto superior direito, em vermelho, sempre
  visível, em qualquer tamanho de tela.
- **Tutorial guiado de primeiro acesso** — na primeira vez que um
  admin ou médico loga, um tour destaca (com uma "luz" ao redor) os
  itens do menu, explicando o que cada um faz. Diferente por perfil.
  Não aparece de novo depois de concluído ou pulado.
- **Aceite de Termos de Uso** — no primeiro login, um modal bloqueante
  (sem X, sem Esc, sem clique fora) mostra os Termos de Uso e a
  Política de Privacidade; só libera o uso do site depois de aceitar.
  Vem antes do tutorial.
- **Calendário visual no agendamento** — o modal de nova consulta
  mostra um calendário de verdade, destacando os dias em que o médico
  escolhido atende.
- **Relatórios com gráfico** — usa Chart.js (carregado só nessa tela)
  pra montar o gráfico de "Movimentação mensal" e o relatório
  imprimível, formatado especificamente pra folha A4 (`@page` com
  margem definida, sem cortar linhas de tabela ao meio).
- **Busca padronizada** — os campos de busca de Pacientes e Médicos
  têm o mesmo visual: mesma altura, ícone de lupa, cantos arredondados.
- **Totalmente responsivo** — mobile-first, 3 níveis: celular
  (< 640px), tablet (640–1024px), desktop (> 1024px). Tabelas viram
  cartões empilhados no celular; o calendário mensal ganha rolagem
  horizontal em vez de espremer os 7 dias.
- **Fonte Nunito** (Google Fonts) em todo o site.

---

## 7. Recuperação de senha

O sistema **não envia código por e-mail** — a recuperação de senha é
feita inteiramente pela **Pergunta de Segurança** cadastrada no
perfil de cada administrador/médico.

1. Na tela de login, clique em **"Esqueci minha senha"**.
2. Digite o e-mail cadastrado.
3. O sistema mostra a pergunta de segurança daquela conta (ou uma
   mensagem genérica, se a conta não existir/não tiver pergunta — pra
   não revelar quais e-mails existem no sistema).
4. Digite a resposta — maiúsculas/minúsculas, espaços extras e
   acentos não importam.
5. Se bater, libera a tela de nova senha.

A resposta nunca é salva em texto puro — só o hash. Errar várias vezes
bloqueia temporariamente essa etapa.

**Login (diferente do "esqueci a senha"):** as mensagens de erro do
login em si são específicas — conta não encontrada, conta inativa,
perfil errado (botão Clínica/Médico trocado) ou senha incorreta. Isso é
seguro aqui porque quem loga é sempre uma conta provisionada pela
própria clínica, não um cadastro público.

---

## 8. Banco de dados

| Tabela | O que guarda |
|---|---|
| `clinics` | Unidades/clínicas cadastradas (multi-clínica). |
| `specialties` | Especialidades médicas. |
| `users` | Tabela única para administradores, médicos e pacientes — `role` diferencia quem é quem, `is_admin` é calculado automaticamente. Inclui pergunta de segurança, aceite de termos e status do tutorial. |
| `doctors` | Dados profissionais do médico — ligado 1:1 a um registro em `users`. |
| `doctor_schedules` | Grade semanal fixa de atendimento. |
| `schedule_blocks` | Bloqueios pontuais (férias, congresso, feriado). |
| `appointment_slots` | Cada horário específico gerado (disponível, reservado ou bloqueado). |
| `appointments` | As consultas — liga paciente, médico, clínica e horário. |
| `medical_records` | O prontuário de cada consulta concluída. |
| `payments` | Cobrança de cada consulta. |
| `notifications` | Avisos enviados a um usuário sobre uma consulta. |

Veja a lista completa de colunas em `db/vitalclinic_estrutura.sql`, e
o histórico de como cada uma foi adicionada em
[`migrations/README.md`](migrations/README.md).

---

## 9. Scripts de população (dados fictícios)

| Onde | Como rodar | O que gera |
|---|---|---|
| `db/vitalclinic_dados.sql` | Já roda junto na instalação (seção 2) | Todos os lotes de dados fictícios já mesclados num arquivo só — veja `db/README.md` |
| `scripts/seed_producao.php` | `php scripts/seed_producao.php` no terminal | Mais um lote, com quantidades ajustáveis (`--clinics=8 --patients=200`), e-mails aleatórios a cada execução |

Todos os usuários fictícios usam senha **`password`**, com e-mail em
domínio próprio por lote (fácil de identificar/remover depois — veja
`db/README.md` para a lista de domínios usados).

---

## 10. Segurança

- Senhas: hash com `password_hash()` (bcrypt), nunca texto puro.
- Resposta da pergunta de segurança: mesmo hash, com normalização
  (ignora maiúsculas/acentos/espaços).
- Todo formulário usa **token CSRF**.
- Mensagens do "Esqueci minha senha" são genéricas (evita revelar
  quais e-mails existem); mensagens de **login** já são específicas
  (ver seção 7).

---

## 11. Estrutura de pastas

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

## 12. Referência das ações do sistema

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
| `admin_create_doctor` / `admin_update_doctor` / `admin_delete_doctor` | Gerenciar médicos |
| `admin_add_schedule` / `admin_delete_schedule` | Grade semanal do médico |
| `admin_add_block` | Bloqueio pontual na agenda de um médico |
| `admin_create_patient` / `admin_update_patient` | Gerenciar pacientes |
| `save_medical_record` | Salvar o prontuário de uma consulta |
| `admin_update_user_role` | Alterar perfil/status de um usuário |
| `mark_notifications_read` | Marcar notificações como lidas |

Ações do tipo `GET` (só consultam, não gravam):
`?action=slots` (horários livres de um médico num dia),
`?action=doctor_weekdays` (dias da semana em que um médico atende),
`?action=monthly_movement` (dados do gráfico de movimentação mensal,
por mês).

---

## 13. Limitações conhecidas

- Este README documenta só o **MedAdmin** (painel admin/médico). O
  acesso do paciente é feito pelo projeto irmão `VitalClinic-Paciente`
  — não documentado aqui, por não fazer parte deste desenvolvimento.
- `app/mailer.php` e `app/api_client.php` continuam no projeto, mas
  não são chamados no funcionamento atual.
- As migrations `007`, `008` e `009` (primeiro admin, tutorial,
  termos de uso) já estão refletidas em `db/vitalclinic_estrutura.sql`
  — só precisa rodá-las manualmente se você tiver um banco criado
  **antes** dessas mudanças (veja `migrations/README.md`).