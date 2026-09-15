# `assets/` — CSS, JavaScript e identidade visual

## `css/styles.css`

Todo o estilo visual, num arquivo só (CSS puro, com variáveis nativas).
Principais blocos:

- **`:root`** — as cores do sistema, como variáveis.
- **Layout base** (`.topbar`, `.shell`, `.nav`) — cabeçalho, menu em
  pílula centralizada (desktop, via flexbox: logo e o menu do avatar
  como dois lados elásticos de peso igual — sem valor fixo em pixel) /
  gaveta com hambúrguer (mobile).
- **`.topbar-profile` / `.topbar-profile-dropdown`** — a bolinha do
  avatar e o menu (Perfil, Sobre nós, Sair) que abre nela.
- **`.calendar-jump`** — o seletor de mês/ano do calendário, ao lado
  das setas de navegação.
- **Componentes reutilizáveis** — `.panel`, `.button`, `.form-card`,
  `.grid`, `.filters` / `.filters-search` (busca esticando o espaço
  disponível), `.modal`, `.search-field`, `.slot-picker`.
- **`.movement-badge`** — etiqueta colorida de classificação (Alta/
  Boa/Baixa movimentação) na tela de Relatórios.
- **`.invite-link`** — campo de link clicável na tela "Convites".
- **`.print-report` e `@media print`, ao final** — o relatório
  imprimível. Fica **fora da tela** (`position: absolute; left:
  -9999px`, nunca `display: none` — um elemento invisível assim teria
  tamanho zero, e o gráfico Chart.js não conseguiria se desenhar nele)
  até o momento de imprimir.
- **Blocos `@media`, ao final** — os 3 níveis de tela (mobile-first).

## `js/app.js`

JavaScript "vanilla", uma função `setupX()` por funcionalidade,
registradas em `DOMContentLoaded`:

| Função | O que faz |
|---|---|
| `setupPwa()` | Registra o service worker |
| `setupConfirmations()` | Confirma antes de ações destrutivas |
| `setupNetworkBanner()` | Aviso quando a internet cai |
| `setupRolePicker()` / `setupRoleSwitches()` | Seletor "Clínica / Médico" no login |
| `setupProfileMenu()` | O menu do avatar — abre no hover (CSS) e também no clique/toque (JS, com uma pequena tolerância ao tirar o mouse, pra não fechar cedo demais) |
| `setupMobileNav()` | Abre/fecha a gaveta do menu no celular/tablet |
| `setupResponsiveTables()` | Tabelas em "cartão empilhado" no celular |
| `setupTermsGate()` | O modal bloqueante de Termos de Uso |
| `setupTutorial()` | O tour guiado de primeiro acesso |
| `setupModals()` | Abrir/fechar modais genéricos (`<dialog>`) |
| `setupAppointmentCalendar()` | Calendário visual do modal "Nova consulta" |
| `setupSlots()` | Horários livres de um médico numa data |
| `setupAutocomplete()` | Campo de busca com sugestões |
| `setupMovementChart()` | Card "Movimentação mensal": gráfico (Chart.js), atualização por mês, e o botão "Imprimir relatório" — usa `buildMovementChartConfig()` (função interna) pra montar o gráfico da tela e o de impressão a partir da mesma configuração, pra nunca ficarem com números diferentes um do outro |
| `setupNewAppointmentForm()` | Envio da nova consulta via `fetch()` |

No topo do arquivo: helpers `qs()`/`qsa()` (atalhos pra
`querySelector`/`querySelectorAll`).

## `brand/`

`vital-clinic-logo.svg` (logo completa) e `vital-clinic-mark.svg` (só
o símbolo, usado como ícone/favicon).