# `assets/` — CSS, JavaScript e identidade visual

## `css/styles.css`

Todo o estilo visual do site, num arquivo só (CSS puro, com variáveis
nativas — sem Tailwind, sem pré-processador). Principais blocos:

- **`:root`** — as cores do sistema, como variáveis (`--bg`,
  `--surface`, `--text`, `--primary` etc.).
- **Layout base** (`.topbar`, `.shell`, `.nav`) — cabeçalho, menu em
  pílula centralizada (desktop) / gaveta com hambúrguer (mobile), e o
  botão "Sair" fixo (`position: fixed`) no canto superior direito. No
  desktop, o menu é centralizado via **flexbox**, com a logo e o
  espaço reservado do "Sair" como dois lados elásticos de peso igual
  (`flex: 1 1 0`) — assim o menu fica sempre exatamente no meio entre
  os dois, sem depender de nenhum valor fixo em pixel.
- **Componentes reutilizáveis** — `.panel`, `.button`, `.form-card`,
  `.grid`, `.filters`, `.modal`, `.search-field` (busca com lupa),
  `.slot-picker` (chips de horário).
- **Calendários** — `.calendar-grid` (visão de mês) e
  `.appointment-calendar` (calendário compacto do modal "Nova
  consulta").
- **Tutorial guiado** (`.tour-*`) e **Termos de Uso** (`.terms-*`) —
  os dois modais de primeiro acesso.
- **`.movement-badge`** — a etiqueta colorida de classificação
  (Alta/Boa/Baixa movimentação) na tela de Relatórios.
- **`.print-report` e `@media print`, ao final do arquivo** — o
  relatório imprimível. Fica posicionado **fora da tela**
  (`position: absolute; left: -9999px`, nunca `display: none`) até o
  momento de imprimir — um elemento com `display: none` tem tamanho
  zero, e o gráfico (Chart.js) não conseguiria se desenhar nele. O
  bloco `@media print` também define `@page { size: A4; margin: ...; }`
  e usa `break-inside: avoid` para não cortar linhas de tabela ao meio
  entre páginas.
- **Blocos `@media`, ao final** — os 3 níveis de tela (mobile-first):
  base = celular, `min-width: 640px` = tablet, `min-width: 1024px` =
  desktop.

## `js/app.js`

Todo o comportamento interativo, em JavaScript "vanilla" (sem
framework). Uma função `setupX()` por funcionalidade, todas registradas
no fim do arquivo, dentro de `DOMContentLoaded`:

| Função | O que faz |
|---|---|
| `setupPwa()` | Registra o service worker |
| `setupConfirmations()` | Confirma antes de ações destrutivas |
| `setupNetworkBanner()` | Aviso quando a internet cai |
| `setupRolePicker()` / `setupRoleSwitches()` | Seletor "Clínica / Médico" e campos que mudam conforme o perfil |
| `setupMobileNav()` | Abre/fecha a gaveta do menu no celular/tablet |
| `setupResponsiveTables()` | Ativa o modo "cartão empilhado" das tabelas no celular |
| `setupTermsGate()` | O modal bloqueante de aceite dos Termos de Uso |
| `setupTutorial()` | O tour guiado de primeiro acesso (destaque + balão sobre o menu real) |
| `setupModals()` | Abrir/fechar modais genéricos (`<dialog>`) |
| `setupAppointmentCalendar()` | O calendário visual do modal "Nova consulta" |
| `setupSlots()` | Busca os horários livres de um médico numa data |
| `setupAutocomplete()` | Campo de busca com sugestões (paciente/médico) |
| `setupMovementChart()` | O card "Movimentação mensal": gráfico Consultas x Faltas (Chart.js), atualização por mês, e o botão "Imprimir relatório" (monta o `.print-report` com os mesmos dados do mês selecionado e chama `window.print()`) |
| `setupNewAppointmentForm()` | Envio do formulário de nova consulta via `fetch()` |

Dentro de `setupMovementChart()`, a função interna
`buildMovementChartConfig(data, extraOptions)` monta a configuração do
gráfico Chart.js **uma vez só**, reaproveitada tanto pelo gráfico da
tela quanto pelo de impressão (com `extraOptions` ajustando só o que
precisa ser diferente — resolução e `responsive: false` no de
impressão) — assim os dois nunca ficam com números/legenda diferentes
um do outro.

No topo do arquivo estão os helpers `qs()`/`qsa()` (atalhos para
`querySelector`/`querySelectorAll`).

## `brand/`

`vital-clinic-logo.svg` (logo completa) e `vital-clinic-mark.svg` (só
o símbolo, usado como ícone/favicon).