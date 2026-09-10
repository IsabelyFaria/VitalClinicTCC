-- =====================================================================
-- Migration 008: adiciona a coluna de controle do tutorial de
-- primeiro acesso em `users`.
--   0 = ainda não viu o tutorial (modal aparece no próximo login)
--   1 = já viu (ou pulou) o tutorial — nunca mais aparece
-- =====================================================================

USE vitalclinic;

ALTER TABLE users
    ADD COLUMN tutorial_seen TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER status;
