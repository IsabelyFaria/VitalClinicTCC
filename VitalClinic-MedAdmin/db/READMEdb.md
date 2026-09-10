# `db/` — Banco de dados (SQL)

## `vitalclinic_estrutura.sql`

Só estrutura: `DROP DATABASE` / `CREATE DATABASE`, todos os
`CREATE TABLE`, chaves estrangeiras e índices. **Nenhum dado.** Rode
este primeiro — ele apaga e recria o banco `vitalclinic` do zero.

## `vitalclinic_dados.sql`

Só `INSERT INTO` — todos os dados fictícios do projeto, mesclados num
arquivo só, nesta ordem:

1. Dados de demonstração originais (3 contas fixas + exemplos)
2. Lote com e-mails `@seed2.local`
3. Lote com e-mails `@seed4.local`
4. Lote com e-mails `@seed5.local`

Rode **depois** do `vitalclinic_estrutura.sql` (ele começa com
`USE vitalclinic;`, então não recria nada, só popula).

Senha de login de **todos** os usuários deste arquivo: `password`.
Cada lote usa um domínio de e-mail e um prefixo de CNPJ só dele, pra
dar pra identificar/remover depois, se quiser:

```sql
-- ver quantos registros cada lote tem
SELECT COUNT(*) FROM users WHERE email LIKE '%@seed2.local';
SELECT COUNT(*) FROM users WHERE email LIKE '%@seed4.local';
SELECT COUNT(*) FROM users WHERE email LIKE '%@seed5.local';

-- remover um lote específico, se quiser
DELETE FROM users   WHERE email LIKE '%@seed4.local';
DELETE FROM clinics WHERE cnpj  LIKE '99.%';
```

Veja a lista completa de credenciais geradas no `README.md` da raiz do
projeto (seção "Credenciais de acesso").

## Como rodar

Pela aba **Importar** do phpMyAdmin (não pela aba SQL — o arquivo de
dados tem alguns MB, e colar textos muito grandes na caixa de SQL pode
cortar no meio sem avisar):

1. phpMyAdmin → banco `vitalclinic` (crie-o rodando `vitalclinic_estrutura.sql` primeiro) → aba **Importar**
2. Selecione `vitalclinic_estrutura.sql` → Executar
3. Repita com `vitalclinic_dados.sql`

## Se você já tem um banco e não quer apagar nada

**Não rode `vitalclinic_estrutura.sql`** (ele apaga o banco inteiro).
Use as migrations em `../migrations/` para aplicar só as mudanças que
ainda faltam, e rode `vitalclinic_dados.sql` normalmente (ele é
aditivo, nunca apaga nada).