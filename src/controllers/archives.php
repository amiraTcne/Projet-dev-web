<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives – CY Tech</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>

<div class="page">
    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Tech">
    </div>

    <h1 class="nom-page">Archives</h1>

    <h3 class="options-title">Actions d'archivage</h3>

    <div class="nav-grid">
        
        <a href="#" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <h4>Dossiers de stage</h4>
            <div class="arrow">
                <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg>
            </div>
        </a>

        <a href="#" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 21h18M3 7v1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7M4 21V10m16 11V10"></path></svg>
            </div>
            <h4>Anciennes Entreprises</h4>
            <div class="arrow">
                <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg>
            </div>
        </a>

        <a href="#" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <h4>Données Étudiants</h4>
            <div class="arrow">
                <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg>
            </div>
        </a>

    </div>

    <div class="deconnexion">
        <a href="accueil_admin.php" class="btn-retour">← Retour au menu</a>
    </div>
</div>

</body>
</html>