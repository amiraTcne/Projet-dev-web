# 🎓 Plateforme d'Archivage et de Suivi de Stages (PASS)

![Statut](https://img.shields.io/badge/Statut-En%20Cours-orange?style=for-the-badge)
![Tech](https://img.shields.io/badge/Stack-PHP%20%7C%20MySQL%20%7C%20JS-blue?style=for-the-badge)
![School](https://img.shields.io/badge/CY_Tech-ING1--GI-red?style=for-the-badge)

> **Projet de Développement Web (2025-2026)** > Une solution centralisée pour la gestion durable des stages, facilitant la liaison entre étudiants, entreprises et enseignants.

---

## 📖 Présentation du Projet
Aujourd'hui, la recherche de stage repose sur des recherches personnelles et des propositions éparses. Le projet **PASS** vise à pallier l'absence d'historique en créant un outil d'archivage intelligent.

**Les enjeux :**
* 🔍 **Informatiser** la recherche de stage par compétences.
* 📂 **Stocker** les données des entreprises partenaires.
* 📊 **Suivre** l'évolution administrative et pédagogique des étudiants.
* 🏛️ **Pérenniser** les informations pour les promotions futures.

---

## 👥 Rôles & Interfaces
Le système est conçu autour de 6 piliers fonctionnels :

| Icône | Rôle | Description |
| :--- | :--- | :--- |
| 👨‍🎓 | **Étudiant** | Recherche d'offres, dépôt de documents et suivi de dossier. |
| 🏢 | **Entreprise** | Publication d'offres et gestion des conventions en ligne. |
| 👨‍🏫 | **Tuteur** | Encadrement pédagogique et validation des étapes. |
| ⚖️ | **Jury** | Consultation des rapports et validation finale du stage. |
| ⚙️ | **Admin** | Gestion des utilisateurs, modération et suivi des logs. |
| 🌐 | **Commun** | Portail d'accueil et authentification sécurisée. |

---

## 🛠️ Stack Technique

### **Frontend & UX**
* **Langages :** `HTML5` / `CSS3` / `JavaScript`
* **Design :** Approche **Mobile-First** & Grilles flexibles (**CSS Grid**).
* **Accessibilité :** Conformité aux normes **WCAG** (textes alternatifs, contrastes).

### **Backend & Data**
* **Serveur :** `PHP`
* **Base de données :** `MySQL` 
* **Optimisation :** Lazy loading, compression des assets et gestion du cache serveur.

---

## 📈 Organisation & Méthodologie
Nous appliquons la méthodologie **Agile (Scrum)** avec un suivi rigoureux :

* **Versionnage :** GitHub pour la gestion du code.
* **Collaboration :** Google Drive pour les livrables et la documentation.
* **Suivi :** Diagramme de Gantt pour le respect des deadlines.

### **Planning Prévisionnel**
1.  **Phase de Cadrage :** Analyse des besoins et spécifications.
2.  **Phase de Conception :** Maquettage IHM (Figma) et modélisation BDD.
3.  **Phase de Développement :** Implémentation Front & Back.
4.  **Optimisation :** Tests de performance et compatibilité navigateurs.
   
![Diagramm de Gantt](./img/diagramme.png)

![Maquette de projet]([https://lien-vers-l-image.com/logo.png](https://www.figma.com/design/Reaf0zOPxGnkdWiyCBb53w/Projet_DEv_Web?node-id=0-1&t=lkvmPQYINIxXBvR7-1))


## 🌳 Arborescence du Projet

```text
📦 Projet-dev-web
 ┣ 📂 config
 ┃ ┗ 📜 init.sql              # Base de Données 
 ┃
 ┣ 📂 includes
 ┃ ┣ 📜 header.php          # 🧱 Navigation & Head
 ┃ ┗ 📜 footer.php          # 🧱 Pied de page & Scripts
 ┃
 ┣ 📂 public                # 🌐 RACINE SERVEUR (Public)
 ┃ ┣ 📂 assets
 ┃ ┃ ┣ 📂 css               # 🎨 Feuilles de style
 ┃ ┃ ┣ 📂 js                # ⚡ Logique Client
 ┃ ┃ ┗ 📂 img               # 🖼️ Médias & Icônes
 ┃ ┗ 📜 login.php           # 🚀 POINT D'ENTRÉE CONNEXION
 ┃ ┗ 📜 frameworks.php           # utilisation framework
 ┃ ┗ 📜 mail.php           
 ┃
 ┣ 📂 src                   # 🧠 COEUR DE L'APPLICATION (Privé)
 ┃ ┣ 📂 Controllers         # 🎮 Page des application
 ┗ 📜 README.md             # 📖 Documentation projet
```

### 🛠️ Fichiers de configuration
* `📄 README.md` : Guide de survie et documentation du projet.
---

## 👥 L'Équipe (Groupe 1)
* **Sirine AJIMI** - *Developpeuse & Rédaction*
* **Amina ATTAF** - *Design IHM & Organisation & Rédaction*
* **Ambre FLORETTE** - *Organisation & gestion projet & developpeuse php*
* **Amira TARCHOUNE** - *Design IHM & Conception*

##### **Enseignant référent :** M. FASSI Dieudonné
--- 
## 📋 Résumé des profils utilisateurs — Base de données

Récapitulatif des profils insérés dans la table `Utilisateur`.

---

### 👤 Profils créés

#### 🎓 Etudiant

| Champ | Valeur |
|-------|--------|
| **Nom** | DUPONT |
| **Prénom** | Jean |
| **Email** | jean.dupont@cy-tech.fr |
| **Mot de passe** | jeanD26. |
| **Actif** | 1 |
| **role_premier** | Etudiant |
| **role_second** | NULL |
| **role_troisieme** | NULL |
| **filiere** | Informatique |
| **niveau** | ING1 |
| **annee_promo** | 2026 |

```sql
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, role_second, role_troisieme, filiere, niveau, annee_promo)
VALUES ('DUPONT', 'Jean', 'jean.dupont@cy-tech.fr', 'jeanD26.', 1, 'Etudiant', NULL, NULL, 'Informatique', 'ING1', 2026);
```

---

#### 🛡️ Admin

| Champ | Valeur |
|-------|--------|
| **Nom** | Martin |
| **Prénom** | Marc |
| **Email** | marc.martin@universite.fr |
| **Mot de passe** | admin26. |
| **Actif** | 1 |
| **role_premier** | Admin |
| **role_second** | NULL |
| **role_troisieme** | NULL |
| **Tous autres champs** | NULL |
| **nb_stagiere** | 0 |

```sql
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, role_second, role_troisieme, filiere, niveau, annee_promo, specialite, departement, commission, annee_jury, num_siret, nom_entreprise, secteur, adresse, ville, code_postal, site_web, nb_stagiere)
VALUES ('Martin', 'Marc', 'marc.martin@universite.fr', 'admin26.', 1, 'Admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0);
```

---

#### 🏢 Entreprise

---

## 1. Identifiants de Connexion
Tous les comptes utilisent l'extension `@yopmail.com`. Les mots de passe sont en clair pour faciliter vos tests.

| Symbole | Type | Nom / Entreprise | Email | Mot de passe |
| :--- | :--- | :--- | :--- | :--- |
| 🏢 | **Entreprise** | EcoVolt | ecovolt@yopmail.com | ecovolt2026! |
| 🏢 |**Entreprise** | CyberSec | cybersec@yopmail.com | cybersec2026! |
| 🏢 |**Entreprise** | DataMind | datamind@yopmail.com | datamind2026! |
| 🏢 |**Entreprise** | BuildIt | buildit@yopmail.com | buildit2026! |
| 🏢 |**Entreprise** | BioLab | biolab@yopmail.com | biolab2026! |
| 🏢 |**Entreprise** | FinTechX | fintechx@yopmail.com | fintechx2026! |
| 🏢 |**Entreprise** | GreenSpace | greenspace@yopmail.com | greenspace2026! |
| 🏢 |**Entreprise** | CloudOps | cloudops@yopmail.com | cloudops2026! |
| 🏢 |**Entreprise** | AutoDrive | autodrive@yopmail.com | autodrive2026! |
| 🏢 |**Entreprise** | MediaFlow | mediaflow@yopmail.com | mediaflow2026! |
| 🏢 |**Étudiant** | Lucas Lemoine | lucas.lemoine@yopmail.com | lucas2026! |
| |**Étudiant** | Sarah Petit | sarah.petit@yopmail.com | sarah2026! |
| |**Étudiant** | Thomas Garnier | thomas.garnier@yopmail.com | thomas2026! |
| **Étudiant** | Emma Rousseau | emma.rousseau@yopmail.com | emma2026! |
| **Étudiant** | Hugo Moreau | hugo.moreau@yopmail.com | hugo2026! |
| **Étudiant** | Chloé Blanc | chloe.blanc@yopmail.com | chloé2026! |
| **Étudiant** | Nathan Faure | nathan.faure@yopmail.com | nathan2026! |
| **Étudiant** | Léa Mercier | lea.mercier@yopmail.com | léa2026! |
| **Étudiant** | Axel Guerin | axel.guerin@yopmail.com | axel2026! |
| **Étudiant** | Inès Boyer | ines.boyer@yopmail.com | inès2026! |
| **Étudiant** | Enzo Fontaine | enzo.fontaine@yopmail.com | enzo2026! |
| **Étudiant** | Clara Robin | clara.robin@yopmail.com | clara2026! |
| 🧑‍🏫 | **Tuteur** | Marc Lefebvre | m.lefebvre@yopmail.com | marc2026! |
| 🧑‍🏫 | **Tuteur** | Alice Cordier | a.cordier@yopmail.com | alice2026! |
| 🧑‍🏫 | **Tuteur** | Julien Masson | j.masson@yopmail.com | julien2026! |
| 🧑‍🏫 | **Tuteur** | Sophie Vallet | s.vallet@yopmail.com | sophie2026! |
| 🧑‍🏫 |**Tuteur** | Damien Roux | d.roux@yopmail.com | damien2026! |
| ⚖️ |**Jury** | Hélène Martin | h.martin@yopmail.com | hélène2026! |
| ⚖️ | **Jury** | Bruno Legrand | b.legrand@yopmail.com | bruno2026! |
| ⚖️ |**Jury** | Céline Dumas | c.dumas@yopmail.com | céline2026! |
| ⚖️ |**Jury** | Victor Hugo | v.hugo@yopmail.com | victor2026! |
| ⚖️ |**Jury** | Sabine Morel | s.morel@yopmail.com | sabine2026! |

## 2. Détails des Offres de Stage
Chaque entreprise possède une ou deux offres prêtes à être postulées.

- **EcoVolt** : Ingénieur Smart Grid, Analyste Performance
- **CyberSec** : Pentester Junior, Analyste SOC
- **DataMind** : Data Scientist, Ingénieur ML Ops
- **BuildIt** : Conducteur de Travaux, Dessinateur BIM
- **BioLab** : Assistant Bio-informatique
- **FinTechX** : Développeur Blockchain
- **GreenSpace** : Consultant RSE, Auditeur Carbone
- **CloudOps** : Ingénieur Cloud, Admin Sys Linux
- **AutoDrive** : Ingénieur Systèmes Embarqués
- **MediaFlow** : Chef de Projet Digital, UX Designer

## 3. Spécialités des Tuteurs et Jurys
- **Tuteurs** : Algorithmique, Structures de données, Réseaux, Statistiques, Web.
- **Jurys** : Systèmes, Mathématiques, IA, Éthique, Innovation.


---

> ⚠️ **Note de sécurité** : Les mots de passe sont affichés en clair à titre de documentation uniquement. En production, utiliser un hashage sécurisé (`bcrypt`, `Argon2`) côté applicatif avant insertion en base.
---

## 🚀 Installation & Lancement
```bash
# Avant de commencer : 
Creer un user sur mysql dans le terminal avec comme nom : userpro et le mdp : projetStage26. pour se connecter a la base de données et telecharger dans la session Sql le fichier init.sql qui se trouve dans config
# 1. Cloner le projet
git clone [https://github.com/votre-compte/projet-web-cytech.git](https://github.com/votre-compte/projet-web-cytech.git)

# 2. Se déplacer dans le dossier
cd projet-web-cytech

# 3. Lancer avec un serveur local (ex: XAMPP ou PHP CLI)
php -S localhost:8000
