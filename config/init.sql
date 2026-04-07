-- =============================================================
--  BASE DE DONNÉES : Suivi et archivage des stages - Cy Tech
--  Projet Dev Web ING1 - Année 2025-2026
--  v3 : Utilisateur unique (sans table Role séparée)
--  Encodage : UTF-8
-- =============================================================

CREATE DATABASE IF NOT EXISTS cyStages
      CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cyStages;

-- =============================================================
-- 1. UTILISATEUR
--    Entité unique pour tous les acteurs du système.
--    Rôles possibles : 'Admin', 'Tuteur', 'Jury', 'Entreprise', 'Etudiant'
--    Contrainte métier :
--      - role_premier  : obligatoire
--      - role_second   : optionnel (null si un seul rôle)
--      - role_troisieme: optionnel (null si moins de 3 rôles)
--      - Le rôle 'Etudiant' est exclusif (role_second et role_troisieme NULL)
--      - Max 3 rôles pour les non-étudiants
--
--    Champs requis selon le rôle :
--      Etudiant   → filiere, niveau, annee_promo
--      Tuteur     → specialite, departement
--      Jury       → specialite, commission, annee_jury
--      Entreprise → num_siret, nom_entreprise, secteur, adresse,
--                   ville, code_postal, site_web, nb_stagiere
-- =============================================================
CREATE TABLE Utilisateur (
    id               INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(100) NOT NULL,
    prenom           VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255) NOT NULL,
    date_inscription DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actif            INT(1)   DEFAULT 0,

    -- Rôles (Etudiant exclusif, max 3 rôles sinon)
    role_premier     ENUM('Admin','Tuteur','Jury','Entreprise','Etudiant') NOT NULL,
    role_second      ENUM('Admin','Tuteur','Jury','Entreprise')            DEFAULT NULL,
    role_troisieme   ENUM('Admin','Tuteur','Jury','Entreprise')            DEFAULT NULL,

    -- Profil Etudiant (NULL si non étudiant)
    filiere          VARCHAR(100) DEFAULT NULL,
    niveau           VARCHAR(50)  DEFAULT NULL              COMMENT 'Ex: L3, M1, ING1...',
    annee_promo      YEAR         DEFAULT NULL,

    -- Profil Tuteur / Jury (NULL si autre rôle)
    specialite       VARCHAR(100) DEFAULT NULL,
    departement      VARCHAR(100) DEFAULT NULL,             -- Tuteur uniquement
    commission       VARCHAR(100) DEFAULT NULL,             -- Jury uniquement
    annee_jury       YEAR         DEFAULT NULL,             -- Jury uniquement

    -- Profil Entreprise (NULL si non entreprise)
    num_siret        CHAR(14)     DEFAULT NULL UNIQUE       COMMENT 'SIRET officiel 14 chiffres',
    nom_entreprise   VARCHAR(200) DEFAULT NULL,
    secteur          VARCHAR(100) DEFAULT NULL,
    adresse          VARCHAR(255) DEFAULT NULL,
    ville            VARCHAR(100) DEFAULT NULL,
    code_postal      CHAR(5)      DEFAULT NULL,
    site_web         VARCHAR(200) DEFAULT NULL,
    nb_stagiere      TINYINT UNSIGNED NOT NULL DEFAULT 0

) ENGINE=InnoDB;

-- Trigger : contraintes métier sur les rôles
DELIMITER $$
CREATE TRIGGER trg_controle_roles_insert
BEFORE INSERT ON Utilisateur
FOR EACH ROW
BEGIN
    -- Règle 1 : Etudiant exclusif → role_second et role_troisieme doivent être NULL
    IF NEW.role_premier = 'Etudiant' AND (NEW.role_second IS NOT NULL OR NEW.role_troisieme IS NOT NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le rôle Etudiant est exclusif : role_second et role_troisieme doivent être NULL.';
    END IF;

    -- Règle 2 : role_troisieme nécessite role_second
    IF NEW.role_troisieme IS NOT NULL AND NEW.role_second IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'role_troisieme ne peut pas être défini sans role_second.';
    END IF;

    -- Règle 3 : Entreprise doit avoir un num_siret
    IF (NEW.role_premier = 'Entreprise' OR NEW.role_second = 'Entreprise' OR NEW.role_troisieme = 'Entreprise')
        AND NEW.num_siret IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un utilisateur avec le rôle Entreprise doit avoir un num_siret.';
    END IF;

    -- Règle 4 : Etudiant doit avoir filiere, niveau et annee_promo
    IF NEW.role_premier = 'Etudiant' AND (NEW.filiere IS NULL OR NEW.niveau IS NULL OR NEW.annee_promo IS NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un étudiant doit avoir filiere, niveau et annee_promo renseignés.';
    END IF;
END$$

CREATE TRIGGER trg_controle_roles_update
BEFORE UPDATE ON Utilisateur
FOR EACH ROW
BEGIN
    IF NEW.role_premier = 'Etudiant' AND (NEW.role_second IS NOT NULL OR NEW.role_troisieme IS NOT NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le rôle Etudiant est exclusif : role_second et role_troisieme doivent être NULL.';
    END IF;

    IF NEW.role_troisieme IS NOT NULL AND NEW.role_second IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'role_troisieme ne peut pas être défini sans role_second.';
    END IF;

    IF (NEW.role_premier = 'Entreprise' OR NEW.role_second = 'Entreprise' OR NEW.role_troisieme = 'Entreprise')
        AND NEW.num_siret IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un utilisateur avec le rôle Entreprise doit avoir un num_siret.';
    END IF;
END$$
DELIMITER ;


-- =============================================================
-- 2. OFFRE_STAGE
--    Déposée par un utilisateur de rôle 'Entreprise'.
-- =============================================================
CREATE TABLE Offre_Stage (
    num_offre        INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre            VARCHAR(200) NOT NULL,
    mission          TEXT         NOT NULL,
    competences      TEXT,
    filiere_ciblee   VARCHAR(100),
    duree_semaines   TINYINT      UNSIGNED NOT NULL,
    date_debut       DATE,
    date_publication DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut           ENUM('ouverte','pourvue','archivee') NOT NULL DEFAULT 'ouverte',
    id_entreprise    INT          UNSIGNED NOT NULL        COMMENT 'FK vers Utilisateur (rôle Entreprise)',
    CONSTRAINT fk_offre_ent FOREIGN KEY (id_entreprise) REFERENCES Utilisateur(id)
) ENGINE=InnoDB;


-- =============================================================
-- 3. FAVORI  (Etudiant ↔ Offre_Stage)
-- =============================================================
CREATE TABLE Favori (
    id_user    INT UNSIGNED NOT NULL,
    num_offre  INT UNSIGNED NOT NULL,
    date_ajout DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user, num_offre),
    CONSTRAINT fk_fav_user  FOREIGN KEY (id_user)   REFERENCES Utilisateur(id)   ON DELETE CASCADE,
    CONSTRAINT fk_fav_offre FOREIGN KEY (num_offre) REFERENCES Offre_Stage(num_offre) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =============================================================
-- 4. STAGE
--    Affecté à un Étudiant, lié à une Entreprise et une Offre.
--    id_etudiant et id_tuteur référencent Utilisateur directement.
-- =============================================================
CREATE TABLE Stage (
    num_stage        INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre            VARCHAR(200) NOT NULL,
    mission          TEXT,
    avancement       TINYINT      UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pourcentage 0-100',
    duree_semaines   TINYINT      UNSIGNED,
    date_debut       DATE,
    date_fin         DATE,
    profil_recherche TEXT,
    competences      TEXT,
    statut           ENUM('en_attente','en_cours','termine','annule') NOT NULL DEFAULT 'en_attente',
    id_etudiant      INT          UNSIGNED NOT NULL,
    id_entreprise    INT          UNSIGNED NOT NULL            COMMENT 'FK vers Utilisateur (rôle Entreprise)',
    num_offre        INT          UNSIGNED,
    id_tuteur        INT          UNSIGNED,
    CONSTRAINT fk_stage_etu    FOREIGN KEY (id_etudiant)   REFERENCES Utilisateur(id),
    CONSTRAINT fk_stage_ent    FOREIGN KEY (id_entreprise) REFERENCES Utilisateur(id),
    CONSTRAINT fk_stage_offre  FOREIGN KEY (num_offre)     REFERENCES Offre_Stage(num_offre),
    CONSTRAINT fk_stage_tuteur FOREIGN KEY (id_tuteur)     REFERENCES Utilisateur(id)
) ENGINE=InnoDB;


-- =============================================================
-- 5. DOSSIER_STAGE
--    Créé automatiquement via trigger à chaque nouveau Stage.
-- =============================================================
CREATE TABLE Dossier_Stage (
    num_dossier       INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    statut            ENUM('incomplet','en_cours','soumis','valide','rejete') NOT NULL DEFAULT 'incomplet',
    rapport_url       VARCHAR(500),
    resume_url        VARCHAR(500),
    fiche_eval_url    VARCHAR(500),
    convention_url    VARCHAR(500),
    date_creation     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME     ON UPDATE CURRENT_TIMESTAMP,
    num_stage         INT          UNSIGNED NOT NULL UNIQUE,
    id_etudiant       INT          UNSIGNED NOT NULL,
    CONSTRAINT fk_doss_stage FOREIGN KEY (num_stage)   REFERENCES Stage(num_stage) ON DELETE CASCADE,
    CONSTRAINT fk_doss_etu   FOREIGN KEY (id_etudiant) REFERENCES Utilisateur(id)
) ENGINE=InnoDB;

-- Trigger : crée automatiquement un dossier à chaque nouveau stage
DELIMITER $$
CREATE TRIGGER trg_creer_dossier
AFTER INSERT ON Stage
FOR EACH ROW
BEGIN
    INSERT INTO Dossier_Stage (num_stage, id_etudiant)
    VALUES (NEW.num_stage, NEW.id_etudiant);
END$$
DELIMITER ;


-- =============================================================
-- 6. VALIDATION_CONVENTION
--    Trace les validations de convention (Tuteur + Entreprise).
-- =============================================================
CREATE TABLE Validation_Convention (
    id_validation   INT      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    num_dossier     INT      UNSIGNED NOT NULL,
    validee_par     ENUM('tuteur','entreprise') NOT NULL,
    id_validateur   INT      UNSIGNED NOT NULL,
    date_validation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire     TEXT,
    CONSTRAINT fk_val_dossier FOREIGN KEY (num_dossier)   REFERENCES Dossier_Stage(num_dossier),
    CONSTRAINT fk_val_user    FOREIGN KEY (id_validateur) REFERENCES Utilisateur(id)
) ENGINE=InnoDB;


-- =============================================================
-- 7. REMARQUE
--    Commentaires sur un dossier, par n'importe quel acteur.
-- =============================================================
CREATE TABLE Remarque (
    id_remarque   INT      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contenu       TEXT     NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    num_dossier   INT      UNSIGNED NOT NULL,
    id_auteur     INT      UNSIGNED NOT NULL,
    CONSTRAINT fk_rem_dossier FOREIGN KEY (num_dossier) REFERENCES Dossier_Stage(num_dossier),
    CONSTRAINT fk_rem_auteur  FOREIGN KEY (id_auteur)   REFERENCES Utilisateur(id)
) ENGINE=InnoDB;


-- =============================================================
-- 8. EVALUATION_JURY
--    Note et avis du Jury sur un dossier de stage.
-- =============================================================
CREATE TABLE Evaluation_Jury (
    id_eval      INT         UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    note         DECIMAL(4,2)         COMMENT 'Note sur 20',
    appreciation TEXT,
    valide       TINYINT(1)  NOT NULL DEFAULT 0,
    date_eval    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    num_dossier  INT         UNSIGNED NOT NULL,
    id_jury      INT         UNSIGNED NOT NULL,
    CONSTRAINT fk_eval_dossier FOREIGN KEY (num_dossier) REFERENCES Dossier_Stage(num_dossier),
    CONSTRAINT fk_eval_jury    FOREIGN KEY (id_jury)     REFERENCES Utilisateur(id)
) ENGINE=InnoDB;


-- =============================================================
-- 9. ARCHIVE
--    Archivage d'un dossier par un Admin.
-- =============================================================
CREATE TABLE Archive (
    id_archive       INT      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_archivage   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motif            VARCHAR(255),
    annee_academique VARCHAR(9)         COMMENT 'Ex: 2025-2026',
    num_dossier      INT      UNSIGNED NOT NULL,
    id_user_admin    INT      UNSIGNED NOT NULL,
    CONSTRAINT fk_arch_dossier FOREIGN KEY (num_dossier)  REFERENCES Dossier_Stage(num_dossier),
    CONSTRAINT fk_arch_admin   FOREIGN KEY (id_user_admin) REFERENCES Utilisateur(id)
) ENGINE=InnoDB;


-- =============================================================
-- 10. TRACE_LOG
--     Fichier trace de toutes les actions.
-- =============================================================
CREATE TABLE Trace_Log (
    id_log      BIGINT       UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action      VARCHAR(100) NOT NULL COMMENT 'Ex: CONNEXION, DEPOT_OFFRE, SOUMISSION_DOSSIER...',
    entite      VARCHAR(50)           COMMENT 'Table concernée',
    entite_id   INT                   COMMENT 'ID de l''enregistrement concerné',
    description TEXT,
    date_heure  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address  VARCHAR(45),
    id_user     INT          UNSIGNED DEFAULT NULL,
    CONSTRAINT fk_log_user FOREIGN KEY (id_user) REFERENCES Utilisateur(id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- =============================================================
-- 11. DEMANDE_FILIERE
--     Requête étudiant → Admin pour ajouter une filière.
-- =============================================================
CREATE TABLE Demande_Filiere (
    id_demande       INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filiere_demandee VARCHAR(150) NOT NULL,
    justification    TEXT,
    statut           ENUM('en_attente','approuvee','rejetee') NOT NULL DEFAULT 'en_attente',
    date_demande     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement  DATETIME,
    id_etudiant      INT          UNSIGNED NOT NULL,
    id_user_admin    INT          UNSIGNED DEFAULT NULL,
    CONSTRAINT fk_dem_etu   FOREIGN KEY (id_etudiant)   REFERENCES Utilisateur(id),
    CONSTRAINT fk_dem_admin FOREIGN KEY (id_user_admin) REFERENCES Utilisateur(id)
) ENGINE=InnoDB;


-- =============================================================
-- VUES UTILES
-- =============================================================

-- Vue : tableau de bord stage étudiant
CREATE OR REPLACE VIEW v_tableau_bord_stage AS
SELECT
    e.id                                AS id_etudiant,
    CONCAT(e.prenom, ' ', e.nom)        AS etudiant,
    e.filiere,
    s.num_stage,
    s.titre                             AS titre_stage,
    s.statut                            AS statut_stage,
    s.avancement,
    d.num_dossier,
    d.statut                            AS statut_dossier,
    ent.nom_entreprise                  AS entreprise,
    CONCAT(t.prenom, ' ', t.nom)        AS tuteur
FROM Utilisateur e
JOIN Stage s          ON s.id_etudiant   = e.id
LEFT JOIN Dossier_Stage d ON d.num_stage = s.num_stage
LEFT JOIN Utilisateur ent ON ent.id      = s.id_entreprise
LEFT JOIN Utilisateur t   ON t.id        = s.id_tuteur
WHERE e.role_premier = 'Etudiant';

-- Vue : offres de stage disponibles
CREATE OR REPLACE VIEW v_offres_disponibles AS
SELECT
    o.num_offre,
    o.titre,
    o.mission,
    o.competences,
    o.filiere_ciblee,
    o.duree_semaines,
    o.date_debut,
    ent.nom_entreprise  AS entreprise,
    ent.secteur,
    ent.ville
FROM Offre_Stage o
JOIN Utilisateur ent ON ent.id = o.id_entreprise
WHERE o.statut = 'ouverte';

-- Vue : liste des utilisateurs avec leurs rôles lisibles
CREATE OR REPLACE VIEW v_utilisateurs AS
SELECT
    id,
    CONCAT(prenom, ' ', nom)    AS nom_complet,
    email,
    role_premier,
    role_second,
    role_troisieme,
    actif,
    date_inscription
FROM Utilisateur;


-- =============================================================
-- INDEX SUPPLÉMENTAIRES (performances)
-- =============================================================
CREATE INDEX idx_user_role          ON Utilisateur(role_premier);
CREATE INDEX idx_user_siret         ON Utilisateur(num_siret);
CREATE INDEX idx_stage_etu          ON Stage(id_etudiant);
CREATE INDEX idx_stage_ent          ON Stage(id_entreprise);
CREATE INDEX idx_dossier_stage      ON Dossier_Stage(num_stage);
CREATE INDEX idx_remarque_doss      ON Remarque(num_dossier);
CREATE INDEX idx_log_datetime       ON Trace_Log(date_heure);
CREATE INDEX idx_log_user           ON Trace_Log(id_user);
CREATE INDEX idx_offre_statut       ON Offre_Stage(statut);
CREATE INDEX idx_offre_filiere      ON Offre_Stage(filiere_ciblee);


INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, role_second, role_troisieme, filiere, niveau, annee_promo) VALUES ('DUPONT', 'Jean', 'jean.dupont@cy-tech.fr','jeanD26.',1, 'Etudiant', NULL, NULL, 'Informatique', 'ING1', 2026);  
INSERT INTO Utilisateur (nom, prenom, email,  mot_de_passe, actif, role_premier, role_second, role_troisieme,filiere,niveau, annee_promo,num_siret) VALUES ('ADMIN', 'Directeur', 'admin.cy@cy-tech.fr', 'admin26.', 1,'Admin',NULL,NULL, NULL, NULL,  NULL);   
