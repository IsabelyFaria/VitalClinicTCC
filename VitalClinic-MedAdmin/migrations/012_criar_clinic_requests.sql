-- =====================================================================
-- Migration 012: cria a tabela clinic_requests — pedidos de acesso de
-- clínicas interessadas, vindos de um formulário público (sem login).
-- Um pedido NUNCA vira conta/clínica ativa sozinho — só depois que o
-- super admin revisa e aprova (o que gera a clínica + o convite de
-- primeiro acesso de verdade, em admin_invites).
-- =====================================================================

USE vitalclinic;

CREATE TABLE clinic_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_name     VARCHAR(150)        NOT NULL,
    clinic_cnpj     VARCHAR(20)         NOT NULL,
    clinic_address  VARCHAR(255)        NULL,
    clinic_phone    VARCHAR(20)         NULL,
    clinic_whatsapp VARCHAR(20)         NULL,
    clinic_email    VARCHAR(150)        NULL,
    contact_name    VARCHAR(150)        NOT NULL,
    contact_email   VARCHAR(150)        NOT NULL,
    message         TEXT                NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by     INT UNSIGNED        NULL,
    reviewed_at     TIMESTAMP           NULL,
    resulting_invite_id INT UNSIGNED    NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_requests_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_requests_invite
        FOREIGN KEY (resulting_invite_id) REFERENCES admin_invites(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_requests_status ON clinic_requests(status);
