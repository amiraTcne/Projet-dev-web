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
 ┃ ┗ 📜 db.php              # 🔌 Connexion Base de Données (PDO)
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
 ┃ ┗ 📜 index.php           # 🚀 POINT D'ENTRÉE UNIQUE (Routeur)
 ┃
 ┣ 📂 src                   # 🧠 COEUR DE L'APPLICATION (Privé)
 ┃ ┣ 📂 Controllers         # 🎮 Logique des pages
 ┃ ┣ 📂 Models              # 🗄️ Gestion des données (SQL)
 ┃ ┗ 📜 functions.php       # 🛠️ Utilitaires globaux
 ┃
 ┣ 📂 frameworks                # 📦 Dépendances Framework (Auto-généré)
 ┃
 ┣ ⚙️ .env                  # 🔑 Secrets & Identifiants (Privé)
 ┣ ⚙️ .htaccess             # 🗺️ Réécriture d'URL (URL Clean)
 ┣ 📜 composer.json         # 📑 Liste des packages
 ┗ 📜 README.md             # 📖 Documentation projet
```

### 🛠️ Fichiers de configuration

* `📄 .env` : **Secrets** (Mots de passe DB, Clés API). *Ne jamais commiter ce fichier !*
* `📄 composer.json` : Liste des dépendances et packages du projet.
* `📄 .htaccess` : Règles de réécriture pour des URLs propres (ex: `/login` au lieu de `index.php?p=login`).
* `📄 README.md` : Guide de survie et documentation du projet.
---

## 👥 L'Équipe (Groupe 1)
* **Sirine AJIMI** - *Coordination & Rédaction*
* **Amina ATTAF** - *Design IHM & Organisation*
* **Ambre FLORETTE** - *Analyse & Rédaction*
* **Amira TARCHOUNE** - *Design IHM & Conception*

**Enseignant référent :** M. FASSI Dieudonné
--- 
# 📋 Résumé des profils utilisateurs — Base de données

Récapitulatif des profils insérés dans la table `Utilisateur`.

---

## 👤 Profils créés

### 🎓 Etudiant

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

### 🛡️ Admin

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

### 🏢 Entreprise

| Champ | Valeur |
|-------|--------|
| **Nom** | Dupont |
| **Prénom** | Jean |
| **Email** | contact@techcorp.fr |
| **Mot de passe** | entreprise26. |
| **Actif** | 1 |
| **role_premier** | Entreprise |
| **num_siret** | 12345678901234 |
| **nom_entreprise** | TechCorp SAS |
| **secteur** | Informatique |
| **adresse** | 12 rue de la Paix |
| **ville** | Paris |
| **code_postal** | 75001 |
| **site_web** | https://techcorp.fr |
| **nb_stagiere** | 0 |

```sql
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, num_siret, nom_entreprise, secteur, adresse, ville, code_postal, site_web, nb_stagiere)
VALUES ('Dupont', 'Jean', 'contact@techcorp.fr', 'entreprise26.', 1, 'Entreprise', '12345678901234', 'TechCorp SAS', 'Informatique', '12 rue de la Paix', 'Paris', '75001', 'https://techcorp.fr', 0);
```

---

### ⚖️ Jury

| Champ | Valeur |
|-------|--------|
| **Nom** | Bernard |
| **Prénom** | Sophie |
| **Email** | sophie.bernard@universite.fr |
| **Mot de passe** | jurys26. |
| **Actif** | 1 |
| **role_premier** | Jury |
| **specialite** | Informatique & IA |
| **commission** | Commission Ingénierie |
| **annee_jury** | 2024 |

```sql
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, specialite, commission, annee_jury)
VALUES ('Bernard', 'Sophie', 'sophie.bernard@universite.fr', 'jurys26.', 1, 'Jury', 'Informatique & IA', 'Commission Ingénierie', 2024);
```

---

### 🧑‍🏫 Tuteur

| Champ | Valeur |
|-------|--------|
| **Nom** | Lefebvre |
| **Prénom** | Pierre |
| **Email** | pierre.lefebvre@universite.fr |
| **Mot de passe** | tuteur26. |
| **Actif** | 1 |
| **role_premier** | Tuteur |
| **specialite** | Mathématiques Appliquées |
| **departement** | Département Sciences |

```sql
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, actif, role_premier, specialite, departement)
VALUES ('Lefebvre', 'Pierre', 'pierre.lefebvre@universite.fr', 'tuteur26.', 1, 'Tuteur', 'Mathématiques Appliquées', 'Département Sciences');
```

---

## 📊 Tableau récapitulatif

| Profil | Nom | Prénom | Email | Mdp (clair) | Attributs renseignés |
|--------|-----|--------|-------|-------------|----------------------|
| **Etudiant** | DUPONT | Jean | jean.dupont@cy-tech.fr | jeanD26. | `actif`, `role_premier`, `filiere`, `niveau`, `annee_promo` |
| **Admin** | Martin | Marc | marc.martin@universite.fr | admin26. | `actif`, `role_premier`, `nb_stagiere=0` |
| **Entreprise** | Dupont | Jean | contact@techcorp.fr | entreprise26. | `actif`, `role_premier`, `num_siret`, `nom_entreprise`, `secteur`, `adresse`, `ville`, `code_postal`, `site_web`, `nb_stagiere` |
| **Jury** | Bernard | Sophie | sophie.bernard@universite.fr | jurys26. | `actif`, `role_premier`, `specialite`, `commission`, `annee_jury` |
| **Tuteur** | Lefebvre | Pierre | pierre.lefebvre@universite.fr | tuteur26. | `actif`, `role_premier`, `specialite`, `departement` |

---

> ⚠️ **Note de sécurité** : Les mots de passe sont affichés en clair à titre de documentation uniquement. En production, utiliser un hashage sécurisé (`bcrypt`, `Argon2`) côté applicatif avant insertion en base.
---

## 🚀 Installation & Lancement
```bash
# Avant de commencer : 
Creer un user sur mysql dans le terminal avec comme nom : userpro et le mdp : projetStage26. pour se connecter a la base de données
# 1. Cloner le projet
git clone [https://github.com/votre-compte/projet-web-cytech.git](https://github.com/votre-compte/projet-web-cytech.git)

# 2. Se déplacer dans le dossier
cd projet-web-cytech

# 3. Lancer avec un serveur local (ex: XAMPP ou PHP CLI)
php -S localhost:8000
