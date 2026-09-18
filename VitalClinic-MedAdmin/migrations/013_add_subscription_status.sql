-- =====================================================================
-- Migration 013: adiciona o controle de assinatura por clínica.
--   'trial'     -> acesso liberado, período de teste
--   'active'    -> acesso liberado, assinatura em dia
--   'suspended' -> login BLOQUEADO para admin/médico dessa clínica
--                  (o super admin sempre continua acessando)
--
-- Não há cobrança automática integrada — o super admin muda esse
-- status manualmente (tela "Clínicas") ao confirmar o pagamento por
-- fora do sistema (Pix, boleto, o que for combinado).
-- =====================================================================

USE vitalclinic;

ALTER TABLE clinics
    ADD COLUMN subscription_status ENUM('trial','active','suspended') NOT NULL DEFAULT 'trial' AFTER email;

-- Todas as clínicas que já existem no seu banco começam como 'active'
-- (presumindo que já estavam em uso normalmente antes desta migration
-- existir) — só as clínicas criadas DEPOIS desta migration nascem em
-- 'trial'. Ajuste manualmente se preferir outro comportamento.
UPDATE clinics SET subscription_status = 'active';
