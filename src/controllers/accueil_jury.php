<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Jurys</title>
    <link rel="stylesheet" href="http://localhost:8080/public/assets/css/style_acceuil.css">
</head>
<body>
<div class="page">

    <div class="logo-wrapper">
        <img src="http://localhost:8080/public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">
        <?php echo $_SESSION['nom']. " ". $_SESSION['prenom']; ?>
    </div>

    <h3 class="options-title">Etudiants suivis</h3>

    <!-- ⬇️ Grille responsive -->
    <div class="nav-grid">

        <a href="#" class="nav">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <h4>Nom prenom etudiant</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <a href="#" class="nav">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <h4>Nom prenom etudiant</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <a href="#" class="nav">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <h4>Nom prenom etudiant</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

    </div>
    <div class="deconnexion">
    <a href="deconnexion.php">Se déconnecter</a>
    </div>
</div>
</body>
</html>