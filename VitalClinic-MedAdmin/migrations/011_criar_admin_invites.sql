-- =====================================================================
-- Migration 011: cria a tabela admin_invites — convites de primeiro
-- acesso para o administrador de uma clínica nova (link com token de
-- uso único, gerado pelo super admin, sem depender de envio de e-mail
-- automático).
-- =====================================================================

USE vitalclinic;

CREATE TABLE admin_invites (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id       INT UNSIGNED        NOT NULL,
    invitee_email   VARCHAR(150)        NOT NULL,
    token           VARCHAR(64)         NOT NULL UNIQUE,
    status          ENUM('pending','used','revoked') NOT NULL DEFAULT 'pending',
    created_by      INT UNSIGNED        NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at      DATETIME            NOT NULL,
    used_at         DATETIME            NULL,
    CONSTRAINT fk_invites_clinic
        FOREIGN KEY (clinic_id) REFERENCES clinics(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_invites_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_invites_token ON admin_invites(token);
