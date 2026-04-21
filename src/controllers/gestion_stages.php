<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Stages</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
<div class="page">
    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-page">Gestion des Stages</div>

    <h3 class="options-title">Actions</h3>

    <div class="nav-grid">
        <a href="#" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            </div>
            <h4>Diffuser une offre</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <a href="#" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <h4>Recherche par filière</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>
    </div>

    <div class="deconnexion">
        <a href="accueil_admin.php" class="btn-retour">← Retour au menu</a>
    </div>
</div>
</body>
</html>