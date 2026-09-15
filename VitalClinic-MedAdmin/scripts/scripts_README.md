# `scripts/` — Ferramentas de linha de comando

Arquivos PHP que **não** são acessados pelo navegador — rodam via
terminal (`php nome-do-arquivo.php`), fora do fluxo normal do site.

## `seed_producao.php`

Gera dados fictícios (clínicas, médicos, pacientes e um histórico de
consultas) direto no banco, pra testar o sistema com um volume
realista. Quantidades ajustáveis por parâmetro:

```bash
php scripts/seed_producao.php --clinics=8 --patients=200 --appointments=700
```

Todos os registros usam e-mail `@seed3.local` e CNPJ com prefixo `98.`
— fáceis de identificar/remover depois. Senha de login: `password`.
É **aditivo**: nunca apaga dados existentes.

## `send_reminders.php`

Cria lembretes de consulta (notificações) para as consultas que
acontecem dentro das próximas N horas. Pensado pra ser agendado como
tarefa recorrente (cron job / Agendador de Tarefas do Windows):

```bash
php scripts/send_reminders.php 24
```

O parâmetro é quantas horas de antecedência considerar — 24 por
padrão, se omitido.