# `migrations/` — Histórico de mudanças no banco

Cada arquivo é um script SQL **incremental**: aplica, num banco que já
existe, a mesma mudança que já está em `../db/vitalclinic_estrutura.sql`
(que só serve pra instalações novas — apaga e recria tudo). Rode **em
ordem**, pela aba SQL do phpMyAdmin, pulando as que já aplicou.

> Não existe "001" — a numeração começa em 002 porque a criação inicial
> das tabelas já é o próprio `vitalclinic_estrutura.sql`.

| Nº | O que faz |
|---|---|
| `002` | Coluna calculada `is_admin` em `users` (deriva de `role`). |
| `003` | Cria `password_resets` (recuperação por e-mail — **removida** depois, ver 006). |
| `004` | Coluna `modality` (presencial/teleconsulta) em `appointments`. |
| `005` | `security_question` e `security_answer_hash` em `users`. |
| `006` | Remove `password_resets` (recuperação vira 100% por Pergunta de Segurança). |
| `007` | Exemplo de `INSERT` pra cadastrar o primeiro administrador de uma clínica nova direto no banco (hoje, prefira o fluxo de **convite** — ver README principal, seção 7 — mas este script continua funcionando se preferir). |
| `008` | Coluna `tutorial_seen` em `users`. |
| `009` | Coluna `terms_accepted` em `users`. |
| `010` | Coluna `is_super_admin` em `users`, e marca `admin@clinica.local` como o super admin do sistema. |
| `011` | Cria a tabela `admin_invites` (convites de primeiro acesso). |

## Como saber quais eu já rodei

```sql
SHOW COLUMNS FROM users;
SHOW TABLES LIKE 'admin_invites';
```

Se a coluna/tabela da migration já aparecer, ela já foi aplicada —
pule pra próxima.