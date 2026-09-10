-- =====================================================================
-- Migration 007: cadastra o primeiro usuário Administrador de uma
-- clínica nova (entrega inicial do sistema para o cliente).
--
-- Não cria rota, tela nem lógica de setup — é só um INSERT direto na
-- tabela `users`. O perfil de administrador é definido pelo campo
-- `role = 'admin'`; a coluna `is_admin` é gerada automaticamente pelo
-- MySQL a partir dele (não existe coluna pra preencher à mão).
--
-- IMPORTANTE: o MySQL/MariaDB não tem função nativa de bcrypt. A senha
-- precisa ser criptografada FORA do banco (com PHP) antes de rodar este
-- script — veja o Passo 1 abaixo.
-- =====================================================================


-- ---------------------------------------------------------------------
-- PASSO 1 — Gerar o hash da senha (rodar no terminal, na pasta do
-- projeto, com o PHP do XAMPP). Troque 'TrocarSenha123!' pela senha
-- real que o administrador vai usar:
--
--   C:\xampp\php\php.exe -r "echo password_hash('TrocarSenha123!', PASSWORD_DEFAULT);"
--
-- Isso imprime um hash tipo:
--   $2y$10$abcXYZ....................................
--
-- Copie esse hash e cole no lugar de PASSWORD_HASH_AQUI, duas linhas
-- abaixo.
-- ---------------------------------------------------------------------


-- ---------------------------------------------------------------------
-- PASSO 2 — Editar os 4 valores abaixo antes de rodar:
--   'Nome do Administrador'  -> nome da pessoa responsável na clínica
--   'admin@nomedaclinica.com' -> e-mail de login dela
--   PASSWORD_HASH_AQUI        -> o hash gerado no Passo 1
--   (11) 90000-0000           -> telefone (opcional, pode deixar NULL)
-- ---------------------------------------------------------------------

USE vitalclinic;

INSERT INTO users (clinic_id, name, email, password_hash, role, phone, status)
VALUES (
    NULL,                             -- clinic_id: veja a nota abaixo
    'Nome do Administrador',
    'admin@nomedaclinica.com',
    'PASSWORD_HASH_AQUI',             -- cole aqui o hash do Passo 1
    'admin',                          -- perfil ADM (is_admin = 1 é calculado sozinho)
    '(11) 90000-0000',
    'active'
);


-- ---------------------------------------------------------------------
-- NOTA sobre clinic_id: o painel de administrador NÃO filtra dados por
-- clínica (todas as consultas/pacientes/médicos do banco aparecem pra
-- qualquer admin logado), então deixar NULL não quebra nada.
--
-- Se você já tem a clínica cadastrada na tabela `clinics` e quer
-- vincular o admin a ela mesmo assim, troque o NULL acima por uma
-- subconsulta com o nome ou CNPJ da clínica, por exemplo:
--
--   (SELECT id FROM clinics WHERE cnpj = '00.000.000/0001-00' LIMIT 1)
--
-- Se a clínica ainda não existe no banco, cadastre-a primeiro:
--
--   INSERT INTO clinics (name, cnpj, address, phone, whatsapp, email)
--   VALUES ('Nome da Clínica', '00.000.000/0001-00', 'Endereço completo',
--           '(11) 4000-0000', '5511940000000', 'contato@nomedaclinica.com');
-- ---------------------------------------------------------------------
