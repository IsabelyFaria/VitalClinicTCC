-- =====================================================================
-- Migration 009: adiciona a coluna de aceite dos Termos de Uso e
-- Política de Privacidade em `users`.
--   0 = ainda não aceitou (modal bloqueante aparece no próximo login)
--   1 = já aceitou — nunca mais aparece
-- =====================================================================

USE vitalclinic;

ALTER TABLE users
    ADD COLUMN terms_accepted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER tutorial_seen;
