-- =====================================================================
-- Migration 010: adiciona a coluna is_super_admin em `users` e marca
-- a conta fixa de demonstração (admin@clinica.local) como super admin.
--
-- Um super admin enxerga e gerencia os dados de TODAS as clínicas
-- cadastradas no sistema — os demais administradores continuam
-- vendo só a própria clínica (isolamento por users.clinic_id).
-- =====================================================================

USE vitalclinic;

ALTER TABLE users
    ADD COLUMN is_super_admin TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER terms_accepted;

UPDATE users SET is_super_admin = 1 WHERE email = 'admin@clinica.local';

-- Pra tornar QUALQUER outra conta em super admin depois, rode:
--   UPDATE users SET is_super_admin = 1 WHERE email = 'e-mail@daconta.com';
