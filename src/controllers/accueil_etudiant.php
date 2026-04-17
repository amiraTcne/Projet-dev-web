<?php
/* Démarrage de la session pour accéder aux variables de connexion */
session_start();

/* Si l'utilisateur n'est pas connecté ou n'est pas étudiant, 
on le renvoie vers la page de login avec le code erreur 4 */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Etudiant</title>
    <!-- On réutilise le même CSS que les autres pages d'accueil du projet -->
    <link rel="stylesheet" href="../../public/assets/css/style_acceuil.css">
</head>
<body>
<div class="page">
    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>
    <h3 class="name">Etudiant</h3>

    <!-- Bandeau avec le nom de l'étudiant connecté -->
    <div class="nom-entreprise">
        <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
    </div>

    <h3 class="options-title">Options</h3>

    <!-- Grille de navigation vers les 5 pages de l'espace étudiant -->
    <div class="nav-grid">

        <!-- profil etudiant -->
        <a href="profil_etudiant.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </span>
            <h4>Profil</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <!-- offres de stages -->
        <a href="offres_etudiant.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                </svg>
            </span>
            <h4>Offres de stage</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <!-- dépôt et suivi des documents de stage -->
        <a href="dossier_etudiant.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                    <path d="M14 2v5a1 1 0 0 0 1 1h5"/>
                    <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                </svg>
            </span>
            <h4>Dossier</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <!-- avancement hebdomadaire du stage -->
        <a href="avancement_etudiant.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 5h8"/><path d="M13 12h8"/><path d="M13 19h8"/>
                    <path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/>
                </svg>
            </span>
            <h4>Avancement Stage</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <!-- offres sauvegardées en favoris par l'étudiant -->
        <a href="favoris_etudiant.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/>
                </svg>
            </span>
            <h4>Offres de stage favoris</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

    </div>
</div>

<!-- déconnexion -->
<div class="deconnexion">
    <a href="deconnexion.php">Se déconnecter</a>
</div>

</body>
</html>
