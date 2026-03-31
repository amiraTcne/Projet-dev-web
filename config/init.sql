-- Script permettant la création de la base de données
CREATE DATABASE IF NOT EXISTS cyStages;
USE cyStages;

CREATE TABLE Entreprise (
    numeroSiret VARCHAR(14) PRIMARY KEY,
    nbStagiaire INT DEFAULT 0,
    filiere VARCHAR(100),
    email VARCHAR(100),
    mdp VARCHAR(50)
);

CREATE TABLE Admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    email VARCHAR(100),
    mdp VARCHAR(50)
);

CREATE TABLE Etudiant (
    id_etu INT PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    filiere VARCHAR(100),
    niveau VARCHAR(20),
    email VARCHAR(100),
    mdp VARCHAR(50)
);

CREATE TABLE Tuteur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    email VARCHAR(100),
    mdp VARCHAR(50)
);

CREATE TABLE Jurys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    email VARCHAR(100),
    mdp VARCHAR(50)
);

CREATE TABLE OffreDeStage (
    numero_offre INT PRIMARY KEY,
    favoris BOOLEAN DEFAULT FALSE,
    field VARCHAR(100),
    entreprise_siret VARCHAR(14),
    FOREIGN KEY (entreprise_siret) REFERENCES Entreprise(numeroSiret) -- ✅ corrigé
);

CREATE TABLE Stage (
    numero_stage INT PRIMARY KEY,
    mission_description TEXT,
    avancement VARCHAR(50),
    titre VARCHAR(100),
    duree VARCHAR(20),
    competence TEXT,
    profil_rechercher TEXT,
    entreprise_siret VARCHAR(14),
    id_etu INT,
    tuteur_id INT,
    FOREIGN KEY (entreprise_siret) REFERENCES Entreprise(numeroSiret), -- ✅ corrigé
    FOREIGN KEY (id_etu) REFERENCES Etudiant(id_etu),
    FOREIGN KEY (tuteur_id) REFERENCES Tuteur(id)
);

CREATE TABLE DossierDeStage (
    numero_dossier INT PRIMARY KEY,
    statut VARCHAR(50),
    rapport_de_stage VARCHAR(255),
    resume_de_stage TEXT,
    fiche_evaluation VARCHAR(255),
    remarque TEXT,
    stage_id INT,
    FOREIGN KEY (stage_id) REFERENCES Stage(numero_stage)
);

INSERT INTO Entreprise (numeroSiret, nbStagiaire, filiere, email, mdp)
VALUES ('01234567891234', 5, 'info', 'entreprise@mail.com', 'entreprise26.');