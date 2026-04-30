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
-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: cyStages
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Archive`
--

DROP TABLE IF EXISTS `Archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Archive` (
  `id_archive` int unsigned NOT NULL AUTO_INCREMENT,
  `date_archivage` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motif` varchar(255) DEFAULT NULL,
  `annee_academique` varchar(9) DEFAULT NULL COMMENT 'Ex: 2025-2026',
  `num_dossier` int unsigned NOT NULL,
  `id_user_admin` int unsigned NOT NULL,
  PRIMARY KEY (`id_archive`),
  KEY `fk_arch_dossier` (`num_dossier`),
  KEY `fk_arch_admin` (`id_user_admin`),
  CONSTRAINT `fk_arch_admin` FOREIGN KEY (`id_user_admin`) REFERENCES `Utilisateur` (`id`),
  CONSTRAINT `fk_arch_dossier` FOREIGN KEY (`num_dossier`) REFERENCES `Dossier_Stage` (`num_dossier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Archive`
--

LOCK TABLES `Archive` WRITE;
/*!40000 ALTER TABLE `Archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `Archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Demande_Filiere`
--

DROP TABLE IF EXISTS `Demande_Filiere`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Demande_Filiere` (
  `id_demande` int unsigned NOT NULL AUTO_INCREMENT,
  `filiere_demandee` varchar(150) NOT NULL,
  `justification` text,
  `statut` enum('en_attente','approuvee','rejetee') NOT NULL DEFAULT 'en_attente',
  `date_demande` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_traitement` datetime DEFAULT NULL,
  `id_etudiant` int unsigned NOT NULL,
  `id_user_admin` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id_demande`),
  KEY `fk_dem_etu` (`id_etudiant`),
  KEY `fk_dem_admin` (`id_user_admin`),
  CONSTRAINT `fk_dem_admin` FOREIGN KEY (`id_user_admin`) REFERENCES `Utilisateur` (`id`),
  CONSTRAINT `fk_dem_etu` FOREIGN KEY (`id_etudiant`) REFERENCES `Utilisateur` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Demande_Filiere`
--

LOCK TABLES `Demande_Filiere` WRITE;
/*!40000 ALTER TABLE `Demande_Filiere` DISABLE KEYS */;
/*!40000 ALTER TABLE `Demande_Filiere` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Dossier_Stage`
--

DROP TABLE IF EXISTS `Dossier_Stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Dossier_Stage` (
  `num_dossier` int unsigned NOT NULL AUTO_INCREMENT,
  `statut` enum('incomplet','en_cours','soumis','valide','rejete') NOT NULL DEFAULT 'incomplet',
  `rapport_url` varchar(500) DEFAULT NULL,
  `resume_url` varchar(500) DEFAULT NULL,
  `fiche_eval_url` varchar(500) DEFAULT NULL,
  `convention_url` varchar(500) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `num_stage` int unsigned NOT NULL,
  `id_etudiant` int unsigned NOT NULL,
  PRIMARY KEY (`num_dossier`),
  UNIQUE KEY `num_stage` (`num_stage`),
  KEY `fk_doss_etu` (`id_etudiant`),
  KEY `idx_dossier_stage` (`num_stage`),
  CONSTRAINT `fk_doss_etu` FOREIGN KEY (`id_etudiant`) REFERENCES `Utilisateur` (`id`),
  CONSTRAINT `fk_doss_stage` FOREIGN KEY (`num_stage`) REFERENCES `Stage` (`num_stage`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Dossier_Stage`
--

LOCK TABLES `Dossier_Stage` WRITE;
/*!40000 ALTER TABLE `Dossier_Stage` DISABLE KEYS */;
INSERT INTO `Dossier_Stage` VALUES (1,'soumis','uploads/1/rapport_stage_jean_dupont.pdf','uploads/1/resume_stage_jean_dupont.pdf','uploads/1/fiche_evaluation_jean_dupont.pdf','uploads/1/convention_stage_jean_dupont.pdf','2026-04-23 08:46:50','2026-04-23 08:47:24',5,1);
/*!40000 ALTER TABLE `Dossier_Stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Evaluation_Jury`
--

DROP TABLE IF EXISTS `Evaluation_Jury`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Evaluation_Jury` (
  `id_eval` int unsigned NOT NULL AUTO_INCREMENT,
  `note` decimal(4,2) DEFAULT NULL COMMENT 'Note sur 20',
  `appreciation` text,
  `valide` tinyint(1) NOT NULL DEFAULT '0',
  `date_eval` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `num_dossier` int unsigned NOT NULL,
  `id_jury` int unsigned NOT NULL,
  PRIMARY KEY (`id_eval`),
  KEY `fk_eval_dossier` (`num_dossier`),
  KEY `fk_eval_jury` (`id_jury`),
  CONSTRAINT `fk_eval_dossier` FOREIGN KEY (`num_dossier`) REFERENCES `Dossier_Stage` (`num_dossier`),
  CONSTRAINT `fk_eval_jury` FOREIGN KEY (`id_jury`) REFERENCES `Utilisateur` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Evaluation_Jury`
--

LOCK TABLES `Evaluation_Jury` WRITE;
/*!40000 ALTER TABLE `Evaluation_Jury` DISABLE KEYS */;
INSERT INTO `Evaluation_Jury` VALUES (1,16.50,'Très bon dossier, rapport bien structuré. Stage validé.',1,'2026-04-23 08:47:25',1,4);
/*!40000 ALTER TABLE `Evaluation_Jury` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Favori`
--

DROP TABLE IF EXISTS `Favori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Favori` (
  `id_user` int unsigned NOT NULL,
  `num_offre` int unsigned NOT NULL,
  `date_ajout` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`,`num_offre`),
  KEY `fk_fav_offre` (`num_offre`),
  CONSTRAINT `fk_fav_offre` FOREIGN KEY (`num_offre`) REFERENCES `Offre_Stage` (`num_offre`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`id_user`) REFERENCES `Utilisateur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Favori`
--

LOCK TABLES `Favori` WRITE;
/*!40000 ALTER TABLE `Favori` DISABLE KEYS */;
/*!40000 ALTER TABLE `Favori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Offre_Stage`
--

DROP TABLE IF EXISTS `Offre_Stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Offre_Stage` (
  `num_offre` int unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `mission` text NOT NULL,
  `competences` text,
  `filiere_ciblee` varchar(100) DEFAULT NULL,
  `duree_semaines` tinyint unsigned NOT NULL,
  `date_debut` date DEFAULT NULL,
  `date_publication` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('ouverte','pourvue','archivee') NOT NULL DEFAULT 'ouverte',
  `id_entreprise` int unsigned NOT NULL COMMENT 'FK vers Utilisateur (rôle Entreprise)',
  PRIMARY KEY (`num_offre`),
  KEY `fk_offre_ent` (`id_entreprise`),
  KEY `idx_offre_statut` (`statut`),
  KEY `idx_offre_filiere` (`filiere_ciblee`),
  CONSTRAINT `fk_offre_ent` FOREIGN KEY (`id_entreprise`) REFERENCES `Utilisateur` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Offre_Stage`
--

LOCK TABLES `Offre_Stage` WRITE;
/*!40000 ALTER TABLE `Offre_Stage` DISABLE KEYS */;
INSERT INTO `Offre_Stage` VALUES (1,'Développeur Web Full-Stack','Développement et maintenance d une application web interne.','PHP, MySQL, JavaScript','Informatique',12,'2026-06-01','2026-04-12 13:40:30','ouverte',3);
/*!40000 ALTER TABLE `Offre_Stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Remarque`
--

DROP TABLE IF EXISTS `Remarque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Remarque` (
  `id_remarque` int unsigned NOT NULL AUTO_INCREMENT,
  `contenu` text NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `num_dossier` int unsigned NOT NULL,
  `id_auteur` int unsigned NOT NULL,
  PRIMARY KEY (`id_remarque`),
  KEY `fk_rem_auteur` (`id_auteur`),
  KEY `idx_remarque_doss` (`num_dossier`),
  CONSTRAINT `fk_rem_auteur` FOREIGN KEY (`id_auteur`) REFERENCES `Utilisateur` (`id`),
  CONSTRAINT `fk_rem_dossier` FOREIGN KEY (`num_dossier`) REFERENCES `Dossier_Stage` (`num_dossier`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Remarque`
--

LOCK TABLES `Remarque` WRITE;
/*!40000 ALTER TABLE `Remarque` DISABLE KEYS */;
INSERT INTO `Remarque` VALUES (1,'Bonjour Jean, noublie pas de remplir ton rapport de mi-stage avant la fin du mois.','2026-04-23 08:47:24',1,5),(2,'Bonjour M. Lefebvre, bien noté ! Je dépose le rapport cette semaine.','2026-04-23 08:47:24',1,1),(3,'[AVANCEMENT SEMAINE] Finalisation du module d authentification et début de l\'intégration de l\'API REST.','2026-04-23 08:47:24',1,1);
/*!40000 ALTER TABLE `Remarque` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Stage`
--

DROP TABLE IF EXISTS `Stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Stage` (
  `num_stage` int unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `mission` text,
  `avancement` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Pourcentage 0-100',
  `duree_semaines` tinyint unsigned DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `profil_recherche` text,
  `competences` text,
  `statut` enum('en_attente','en_cours','termine','annule') NOT NULL DEFAULT 'en_attente',
  `id_etudiant` int unsigned NOT NULL,
  `id_entreprise` int unsigned NOT NULL COMMENT 'FK vers Utilisateur (rôle Entreprise)',
  `num_offre` int unsigned DEFAULT NULL,
  `id_tuteur` int unsigned DEFAULT NULL,
  PRIMARY KEY (`num_stage`),
  KEY `fk_stage_offre` (`num_offre`),
  KEY `fk_stage_tuteur` (`id_tuteur`),
  KEY `idx_stage_etu` (`id_etudiant`),
  KEY `idx_stage_ent` (`id_entreprise`),
  CONSTRAINT `fk_stage_ent` FOREIGN KEY (`id_entreprise`) REFERENCES `Utilisateur` (`id`),
  CONSTRAINT `fk_stage_etu` FOREIGN KEY (`id_etudiant`) REFERENCES `Utilisateur` (`id`),
  CONSTRAINT `fk_stage_offre` FOREIGN KEY (`num_offre`) REFERENCES `Offre_Stage` (`num_offre`),
  CONSTRAINT `fk_stage_tuteur` FOREIGN KEY (`id_tuteur`) REFERENCES `Utilisateur` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Stage`
--

LOCK TABLES `Stage` WRITE;
/*!40000 ALTER TABLE `Stage` DISABLE KEYS */;
INSERT INTO `Stage` VALUES (5,'Développeur Web Full-Stack','Développement et maintenance d une application web interne.',75,12,'2026-06-01','2026-08-22',NULL,NULL,'en_cours',1,3,1,5);
/*!40000 ALTER TABLE `Stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Trace_Log`
--

DROP TABLE IF EXISTS `Trace_Log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Trace_Log` (
  `id_log` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(100) NOT NULL COMMENT 'Ex: CONNEXION, DEPOT_OFFRE, SOUMISSION_DOSSIER...',
  `entite` varchar(50) DEFAULT NULL COMMENT 'Table concernée',
  `entite_id` int DEFAULT NULL COMMENT 'ID de l''enregistrement concerné',
  `description` text,
  `date_heure` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `id_user` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_datetime` (`date_heure`),
  KEY `idx_log_user` (`id_user`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `Utilisateur` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Trace_Log`
--

LOCK TABLES `Trace_Log` WRITE;
/*!40000 ALTER TABLE `Trace_Log` DISABLE KEYS */;
/*!40000 ALTER TABLE `Trace_Log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Utilisateur`
--

DROP TABLE IF EXISTS `Utilisateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Utilisateur` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actif` int DEFAULT '0',
  `role_premier` enum('Admin','Tuteur','Jury','Entreprise','Etudiant') NOT NULL,
  `role_second` enum('Admin','Tuteur','Jury','Entreprise') DEFAULT NULL,
  `role_troisieme` enum('Admin','Tuteur','Jury','Entreprise') DEFAULT NULL,
  `filiere` varchar(100) DEFAULT NULL,
  `niveau` varchar(50) DEFAULT NULL COMMENT 'Ex: L3, M1, ING1...',
  `annee_promo` year DEFAULT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `departement` varchar(100) DEFAULT NULL,
  `commission` varchar(100) DEFAULT NULL,
  `annee_jury` year DEFAULT NULL,
  `num_siret` char(14) DEFAULT NULL COMMENT 'SIRET officiel 14 chiffres',
  `nom_entreprise` varchar(200) DEFAULT NULL,
  `secteur` varchar(100) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `code_postal` char(5) DEFAULT NULL,
  `site_web` varchar(200) DEFAULT NULL,
  `nb_stagiere` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `num_siret` (`num_siret`),
  KEY `idx_user_role` (`role_premier`),
  KEY `idx_user_siret` (`num_siret`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE Utilisateur ADD COLUMN description VARCHAR(1000) DEFAULT NULL;



--
-- Table structure for table `Validation_Convention`
--

DROP TABLE IF EXISTS `Validation_Convention`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Validation_Convention` (
  `id_validation` int unsigned NOT NULL AUTO_INCREMENT,
  `num_dossier` int unsigned NOT NULL,
  `validee_par` enum('tuteur','entreprise') NOT NULL,
  `id_validateur` int unsigned NOT NULL,
  `date_validation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `commentaire` text,
  PRIMARY KEY (`id_validation`),
  KEY `fk_val_dossier` (`num_dossier`),
  KEY `fk_val_user` (`id_validateur`),
  CONSTRAINT `fk_val_dossier` FOREIGN KEY (`num_dossier`) REFERENCES `Dossier_Stage` (`num_dossier`),
  CONSTRAINT `fk_val_user` FOREIGN KEY (`id_validateur`) REFERENCES `Utilisateur` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Validation_Convention`
--

LOCK TABLES `Validation_Convention` WRITE;
/*!40000 ALTER TABLE `Validation_Convention` DISABLE KEYS */;
INSERT INTO `Validation_Convention` VALUES (1,1,'tuteur',5,'2026-04-23 08:47:24','Convention vérifiée et validée. Bon courage pour ce stage !');
/*!40000 ALTER TABLE `Validation_Convention` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_offres_disponibles`
--

DROP TABLE IF EXISTS `v_offres_disponibles`;
/*!50001 DROP VIEW IF EXISTS `v_offres_disponibles`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_offres_disponibles` AS SELECT 
 1 AS `num_offre`,
 1 AS `titre`,
 1 AS `mission`,
 1 AS `competences`,
 1 AS `filiere_ciblee`,
 1 AS `duree_semaines`,
 1 AS `date_debut`,
 1 AS `entreprise`,
 1 AS `secteur`,
 1 AS `ville`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_tableau_bord_stage`
--

DROP TABLE IF EXISTS `v_tableau_bord_stage`;
/*!50001 DROP VIEW IF EXISTS `v_tableau_bord_stage`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_tableau_bord_stage` AS SELECT 
 1 AS `id_etudiant`,
 1 AS `etudiant`,
 1 AS `filiere`,
 1 AS `num_stage`,
 1 AS `titre_stage`,
 1 AS `statut_stage`,
 1 AS `avancement`,
 1 AS `num_dossier`,
 1 AS `statut_dossier`,
 1 AS `entreprise`,
 1 AS `tuteur`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_utilisateurs`
--

DROP TABLE IF EXISTS `v_utilisateurs`;
/*!50001 DROP VIEW IF EXISTS `v_utilisateurs`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_utilisateurs` AS SELECT 
 1 AS `id`,
 1 AS `nom_complet`,
 1 AS `email`,
 1 AS `role_premier`,
 1 AS `role_second`,
 1 AS `role_troisieme`,
 1 AS `actif`,
 1 AS `date_inscription`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_offres_disponibles`
--

/*!50001 DROP VIEW IF EXISTS `v_offres_disponibles`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`userpro`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_offres_disponibles` AS select `o`.`num_offre` AS `num_offre`,`o`.`titre` AS `titre`,`o`.`mission` AS `mission`,`o`.`competences` AS `competences`,`o`.`filiere_ciblee` AS `filiere_ciblee`,`o`.`duree_semaines` AS `duree_semaines`,`o`.`date_debut` AS `date_debut`,`ent`.`nom_entreprise` AS `entreprise`,`ent`.`secteur` AS `secteur`,`ent`.`ville` AS `ville` from (`Offre_Stage` `o` join `Utilisateur` `ent` on((`ent`.`id` = `o`.`id_entreprise`))) where (`o`.`statut` = 'ouverte') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_tableau_bord_stage`
--

/*!50001 DROP VIEW IF EXISTS `v_tableau_bord_stage`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`userpro`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tableau_bord_stage` AS select `e`.`id` AS `id_etudiant`,concat(`e`.`prenom`,' ',`e`.`nom`) AS `etudiant`,`e`.`filiere` AS `filiere`,`s`.`num_stage` AS `num_stage`,`s`.`titre` AS `titre_stage`,`s`.`statut` AS `statut_stage`,`s`.`avancement` AS `avancement`,`d`.`num_dossier` AS `num_dossier`,`d`.`statut` AS `statut_dossier`,`ent`.`nom_entreprise` AS `entreprise`,concat(`t`.`prenom`,' ',`t`.`nom`) AS `tuteur` from ((((`Utilisateur` `e` join `Stage` `s` on((`s`.`id_etudiant` = `e`.`id`))) left join `Dossier_Stage` `d` on((`d`.`num_stage` = `s`.`num_stage`))) left join `Utilisateur` `ent` on((`ent`.`id` = `s`.`id_entreprise`))) left join `Utilisateur` `t` on((`t`.`id` = `s`.`id_tuteur`))) where (`e`.`role_premier` = 'Etudiant') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_utilisateurs`
--

/*!50001 DROP VIEW IF EXISTS `v_utilisateurs`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`userpro`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_utilisateurs` AS select `Utilisateur`.`id` AS `id`,concat(`Utilisateur`.`prenom`,' ',`Utilisateur`.`nom`) AS `nom_complet`,`Utilisateur`.`email` AS `email`,`Utilisateur`.`role_premier` AS `role_premier`,`Utilisateur`.`role_second` AS `role_second`,`Utilisateur`.`role_troisieme` AS `role_troisieme`,`Utilisateur`.`actif` AS `actif`,`Utilisateur`.`date_inscription` AS `date_inscription` from `Utilisateur` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-23  9:09:17

-- Offre 2 : Développeur Mobile (Android/iOS)
INSERT INTO Offre_Stage (titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut, id_entreprise)
VALUES (
    'Développeur Mobile Android & iOS',
    'Conception et développement d\'une application mobile de suivi de livraisons en temps réel. Intégration d\'une API REST existante, gestion des notifications push et tests sur devices physiques.',
    'Flutter, Dart, Android Studio, API REST, Git',
    'Informatique',
    16,
    '2026-06-15',
    'ouverte',
    3
);

-- Offre 3 : Data Analyst
INSERT INTO Offre_Stage (titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut, id_entreprise)
VALUES (
    'Data Analyst - Visualisation de données',
    'Analyse des données clients et création de tableaux de bord interactifs. Nettoyage et traitement de données issues de différentes sources, rédaction de rapports hebdomadaires à destination des équipes métier.',
    'Python, Pandas, SQL, Power BI, Excel',
    'Mathématiques',
    12,
    '2026-07-01',
    'ouverte',
    3
);

-- Offre 4 : Administrateur Systèmes & Réseaux
INSERT INTO Offre_Stage (titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut, id_entreprise)
VALUES (
    'Administrateur Systèmes & Réseaux',
    'Maintenance et supervision de l\'infrastructure réseau de l\'entreprise. Configuration de serveurs Linux, gestion des sauvegardes, surveillance des performances et rédaction de la documentation technique.',
    'Linux, Bash, VMware, Cisco, TCP/IP, Active Directory',
    'Informatique',
    14,
    '2026-06-01',
    'ouverte',
    3
);


-- Table des notifications pour les étudiants (et autres rôles)
CREATE TABLE IF NOT EXISTS `Notification` (
  `id_notif`    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_user`     INT UNSIGNED    NOT NULL COMMENT 'Destinataire',
  `type`        ENUM(
                  'candidature_validee',
                  'candidature_refusee',
                  'stage_cree',
                  'remarque',
                  'autre'
                ) NOT NULL DEFAULT 'autre',
  `titre`       VARCHAR(200)    NOT NULL,
  `message`     TEXT            NOT NULL,
  `lien`        VARCHAR(300)    DEFAULT NULL COMMENT 'URL optionnelle vers la page concernée',
  `lu`          TINYINT(1)      NOT NULL DEFAULT 0,
  `date_creation` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notif`),
  KEY `idx_notif_user` (`id_user`),
  KEY `idx_notif_lu`   (`lu`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`id_user`) REFERENCES `Utilisateur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- Colonne pour tracker l'état côté étudiant sur le Stage
-- ('en_attente' = candidature soumise, 'acceptee_entreprise' = entreprise a validé,
--  'confirmee_etudiant' = étudiant a confirmé → stage réel créé,
--  'refusee_entreprise', 'refusee_etudiant')
ALTER TABLE `Stage`
  ADD COLUMN `statut_candidature` 
    ENUM(
      'en_attente',
      'acceptee_entreprise',
      'confirmee_etudiant',
      'refusee_entreprise',
      'refusee_etudiant'
    ) NOT NULL DEFAULT 'en_attente' 
    AFTER `statut`;

-- 1. Mise à jour du stage
UPDATE Stage 
SET statut = 'en_cours', 
    statut_candidature = 'confirmee_etudiant' 
WHERE num_stage = 5 AND id_etudiant = 1;

-- 2. Création du dossier
INSERT INTO Dossier_Stage (statut, num_stage, id_etudiant, date_creation)
VALUES ('incomplet', 5, 1, NOW());


CREATE TABLE IF NOT EXISTS Double_Authentification (
    id_2fa INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_user INT UNSIGNED NOT NULL,
    code_verification CHAR(4) NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME NOT NULL,
    utilise TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id_2fa),
    KEY idx_2fa_user (id_user),
    CONSTRAINT fk_2fa_user
        FOREIGN KEY (id_user) REFERENCES Utilisateur(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO Utilisateur (
    nom, prenom, email, mot_de_passe, actif, role_premier, filiere, niveau, annee_promo
) VALUES (
    'Amira', 'Ta', 'mira.tcne@gmail.com', 'projetStage26.', 1, 'Etudiant', 'Informatique', 'ING1', 2026
);

-- =============================================================
-- INSERTION DES 10 ENTREPRISES (Secteurs variés)
-- =============================================================
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, num_siret, nom_entreprise, secteur, nb_stagiere) VALUES 
('VOLT', 'Eco', 'ecovolt@yopmail.com', 'ecovolt2026!', 1, 'Entreprise', '10000000000001', 'EcoVolt', 'Énergie', 0),
('SEC', 'Cyber', 'cybersec@yopmail.com', 'cybersec2026!', 1, 'Entreprise', '10000000000002', 'CyberSec', 'Cybersécurité', 0),
('MIND', 'Data', 'datamind@yopmail.com', 'datamind2026!', 1, 'Entreprise', '10000000000003', 'DataMind', 'IA / Data', 0),
('IT', 'Build', 'buildit@yopmail.com', 'buildit2026!', 1, 'Entreprise', '10000000000004', 'BuildIt', 'BTP', 0),
('LAB', 'Bio', 'biolab@yopmail.com', 'biolab2026!', 1, 'Entreprise', '10000000000005', 'BioLab', 'Santé', 0),
('X', 'FinTech', 'fintechx@yopmail.com', 'fintechx2026!', 1, 'Entreprise', '10000000000006', 'FinTechX', 'Finance', 0),
('SPACE', 'Green', 'greenspace@yopmail.com', 'greenspace2026!', 1, 'Entreprise', '10000000000007', 'GreenSpace', 'Écologie', 0),
('OPS', 'Cloud', 'cloudops@yopmail.com', 'cloudops2026!', 1, 'Entreprise', '10000000000008', 'CloudOps', 'Cloud', 0),
('DRIVE', 'Auto', 'autodrive@yopmail.com', 'autodrive2026!', 1, 'Entreprise', '10000000000009', 'AutoDrive', 'Automobile', 0),
('FLOW', 'Media', 'mediaflow@yopmail.com', 'mediaflow2026!', 1, 'Entreprise', '10000000000010', 'MediaFlow', 'Marketing', 0);

-- =============================================================
-- INSERTION DES 12 ÉTUDIANTS (Matières différentes)
-- =============================================================
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, filiere, niveau, annee_promo) VALUES 
('Lemoine', 'Lucas', 'lucas.lemoine@yopmail.com', 'lucas2026!', 1, 'Etudiant', 'Informatique', 'ING1', 2026),
('Petit', 'Sarah', 'sarah.petit@yopmail.com', 'sarah2026!', 1, 'Etudiant', 'Mathématiques', 'M1', 2026),
('Garnier', 'Thomas', 'thomas.garnier@yopmail.com', 'thomas2026!', 1, 'Etudiant', 'Cybersécurité', 'ING2', 2026),
('Rousseau', 'Emma', 'emma.rousseau@yopmail.com', 'emma2026!', 1, 'Etudiant', 'Génie Civil', 'ING1', 2026),
('Moreau', 'Hugo', 'hugo.moreau@yopmail.com', 'hugo2026!', 1, 'Etudiant', 'IA & Big Data', 'M2', 2026),
('Blanc', 'Chloé', 'chloe.blanc@yopmail.com', 'chloé2026!', 1, 'Etudiant', 'Électronique', 'L3', 2026),
('Faure', 'Nathan', 'nathan.faure@yopmail.com', 'nathan2026!', 1, 'Etudiant', 'Informatique', 'ING3', 2026),
('Mercier', 'Léa', 'lea.mercier@yopmail.com', 'léa2026!', 1, 'Etudiant', 'Finance', 'M1', 2026),
('Guerin', 'Axel', 'axel.guerin@yopmail.com', 'axel2026!', 1, 'Etudiant', 'Réseaux', 'ING1', 2026),
('Boyer', 'Inès', 'ines.boyer@yopmail.com', 'inès2026!', 1, 'Etudiant', 'Bio-informatique', 'M2', 2026),
('Fontaine', 'Enzo', 'enzo.fontaine@yopmail.com', 'enzo2026!', 1, 'Etudiant', 'Mathématiques', 'L3', 2026),
('Robin', 'Clara', 'clara.robin@yopmail.com', 'clara2026!', 1, 'Etudiant', 'Management Tech', 'ING2', 2026);

-- =============================================================
-- INSERTION DES 5 TUTEURS ET 5 JURYS
-- =============================================================
-- Tuteurs
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, specialite, departement) VALUES 
('Lefebvre', 'Marc', 'm.lefebvre@yopmail.com', 'marc2026!', 1, 'Tuteur', 'Algorithmique', 'Informatique'),
('Cordier', 'Alice', 'a.cordier@yopmail.com', 'alice2026!', 1, 'Tuteur', 'Structure des données', 'Informatique'),
('Masson', 'Julien', 'j.masson@yopmail.com', 'julien2026!', 1, 'Tuteur', 'Réseaux IP', 'Télécoms'),
('Vallet', 'Sophie', 's.vallet@yopmail.com', 'sophie2026!', 1, 'Tuteur', 'Statistiques', 'Mathématiques'),
('Roux', 'Damien', 'd.roux@yopmail.com', 'damien2026!', 1, 'Tuteur', 'Développement Web', 'Informatique');

-- Jurys
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, specialite, commission, annee_jury) VALUES 
('Martin', 'Hélène', 'h.martin@yopmail.com', 'hélène2026!', 1, 'Jury', 'Systèmes', 'Commission Systèmes', 2026),
('Legrand', 'Bruno', 'b.legrand@yopmail.com', 'bruno2026!', 1, 'Jury', 'Mathématiques', 'Commission Mathématiques', 2026),
('Dumas', 'Céline', 'c.dumas@yopmail.com', 'céline2026!', 1, 'Jury', 'Intelligence Artificielle', 'Commission IA', 2026),
('Hugo', 'Victor', 'v.hugo@yopmail.com', 'victor2026!', 1, 'Jury', 'Éthique & Tech', 'Commission Éthique', 2026),
('Morel', 'Sabine', 's.morel@yopmail.com', 'sabine2026!', 1, 'Jury', 'Innovation', 'Commission Innovation', 2026);

-- =============================================================
-- INSERTION DES OFFRES DE STAGE (1 à 2 par entreprise)
-- =============================================================
INSERT INTO Offre_Stage (titre, mission, filiere_ciblee, duree_semaines, date_debut, statut, id_entreprise) VALUES 
('Ingénieur Smart Grid', 'Optimisation réseau', 'Énergie', 12, '2026-05-01', 'ouverte', 7),
('Analyste Performance', 'Analyse énergétique', 'Énergie', 12, '2026-05-01', 'ouverte', 7),
('Pentester Junior', 'Tests d''intrusion', 'Cybersécurité', 12, '2026-05-01', 'ouverte', 8),
('Analyste SOC', 'Surveillance réseau', 'Cybersécurité', 12, '2026-05-01', 'ouverte', 8),
('Data Scientist', 'Modèles prédictifs', 'IA / Data', 12, '2026-05-01', 'ouverte', 9),
('Ingénieur ML Ops', 'Déploiement modèles', 'IA / Data', 12, '2026-05-01', 'ouverte', 9),
('Conducteur de Travaux', 'Suivi de chantier', 'BTP', 12, '2026-05-01', 'ouverte', 10),
('Dessinateur BIM', 'Modélisation 3D', 'BTP', 12, '2026-05-01', 'ouverte', 10),
('Assistant Bio-informatique', 'Analyse génomique', 'Santé', 12, '2026-05-01', 'ouverte', 11),
('Développeur Blockchain', 'Smart contracts', 'Finance', 12, '2026-05-01', 'ouverte', 12),
('Consultant RSE', 'Audit environnemental', 'Écologie', 12, '2026-05-01', 'ouverte', 13),
('Auditeur Carbone', 'Bilan carbone', 'Écologie', 12, '2026-05-01', 'ouverte', 13),
('Ingénieur Cloud', 'Architecture AWS', 'Cloud', 12, '2026-05-01', 'ouverte', 14),
('Admin Sys Linux', 'Maintenance serveurs', 'Cloud', 12, '2026-05-01', 'ouverte', 14),
('Ingénieur Systèmes Embarqués', 'C++ temps réel', 'Automobile', 12, '2026-05-01', 'ouverte', 15),
('Chef de Projet Digital', 'Gestion de campagne', 'Marketing', 12, '2026-05-01', 'ouverte', 16),
('UX Designer', 'Design d''interface', 'Marketing', 12, '2026-05-01', 'ouverte', 16);