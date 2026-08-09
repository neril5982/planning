CREATE TABLE IF NOT EXISTS users (
    id                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    email                  VARCHAR(255)  NOT NULL,
    password               VARCHAR(255)  NOT NULL,
    nom                    VARCHAR(128)  DEFAULT NULL,
    prenom                 VARCHAR(128)  DEFAULT NULL,
    role                   ENUM('admin','direction','user') NOT NULL DEFAULT 'user',
    reset_token            VARCHAR(128)  DEFAULT NULL,
    reset_token_expires_at DATETIME      DEFAULT NULL,
    last_login_at          DATETIME      DEFAULT NULL,
    created_at             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chantiers (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nom         VARCHAR(255)  NOT NULL,
    ville       VARCHAR(255)  DEFAULT NULL,
    entreprise  ENUM('Moncomble','RVM') NOT NULL DEFAULT 'Moncomble',
    couleur     CHAR(7)       NOT NULL DEFAULT '#ADBF5E',
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chantier_creneaux (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    chantier_id INT UNSIGNED  NOT NULL,
    date_debut  DATE          NOT NULL,
    date_fin    DATE          NOT NULL,
    PRIMARY KEY (id),
    KEY idx_dates (date_debut, date_fin),
    CONSTRAINT fk_creneau_chantier FOREIGN KEY (chantier_id) REFERENCES chantiers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
