# `db/` — Banco de dados (SQL)

## `vitalclinic_estrutura.sql`

Só estrutura: `DROP DATABASE` / `CREATE DATABASE`, todos os
`CREATE TABLE` (incluindo `admin_invites`, a tabela dos convites de
primeiro acesso), chaves estrangeiras e índices. **Nenhum dado.** Rode
este primeiro — ele apaga e recria o banco `vitalclinic` do zero.

## `vitalclinic_dados.sql`

Só `INSERT INTO` — todos os dados fictícios do projeto, mesclados num
arquivo só: os 4 registros fixos de demonstração (admin, 2 médicos,
paciente) + vários lotes de clínicas/médicos/pacientes/consultas
fictícios, gerados em momentos diferentes do desenvolvimento. Rode
**depois** do `vitalclinic_estrutura.sql`.

Senha de login de **todos** os usuários deste arquivo: `password`.
`admin@clinica.local` é marcado como **super admin**
(`is_super_admin = 1`) — o único que enxerga todas as clínicas, os
demais ficam isolados na própria.

## Como ver as credenciais de cada clínica

Como os lotes foram gerados em momentos diferentes (e um script
gerador, `scripts/seed_producao.php`, cria contas novas a cada
execução), a forma confiável de ver **quem pertence a qual clínica
agora** é consultar direto o banco:

```sql
SELECT c.name AS clinica, u.role AS perfil, u.name AS nome, u.email AS email
FROM users u
JOIN clinics c ON c.id = u.clinic_id
WHERE u.role IN ('admin', 'doctor')
ORDER BY c.name, u.role DESC, u.name;
```

## Como rodar

Pela aba **Importar** do phpMyAdmin (não pela aba SQL — o arquivo de
dados tem alguns MB, e colar textos muito grandes na caixa de SQL pode
cortar no meio sem avisar):

1. phpMyAdmin → aba **Importar** → `vitalclinic_estrutura.sql` → Executar
2. Repita com `vitalclinic_dados.sql`

## Se você já tem um banco e não quer apagar nada

**Não rode `vitalclinic_estrutura.sql`** (ele apaga o banco inteiro).
Use as migrations em `../migrations/` para aplicar só as mudanças que
ainda faltam, e rode `vitalclinic_dados.sql` normalmente (ele é
aditivo, nunca apaga nada).