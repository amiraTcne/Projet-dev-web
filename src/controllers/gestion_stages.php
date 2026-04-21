<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Stages – CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
    <div class="card">
        <div class="card-header" style="background: white; border-bottom: 1px solid #dde6ff;">
            <div class="logo-row">
                <img src="../../public/assets/img/logo.png" alt="CY Tech" style="height: 40px; width: auto;">
                <span class="badge-admin" style="background: #255FAA; color: white;">Stages</span>
            </div>
            <h2 style="margin-top:15px; font-family:'Syne'; color: #0f172a;">Offres de stages</h2>
        </div>

        <div class="card-body">
            <p class="section-label">Actions sur les offres</p>
            <div class="nav-item">
               <span class="nav-text"><h4>Diffuser une offre</h4><p>Publier une nouvelle mission </p></span>
            </div>
           <p class="section-label" style="margin-top:15px;">Filtres de recherche </p>
            <div class="nav-item">
               <span class="nav-text"><h4>Recherche par filière</h4><p>Informatique, Mathématiques, etc. </p></span>
            </div>
            <div class="nav-item">
               <span class="nav-text"><h4>Filtrer par durée</h4><p>Stages courts ou longs </p></span>
            </div>

            <a href="accueil_admin.php" class="btn-logout" style="margin-top: 20px; border-color: #dde6ff;">← Retour</a>
        </div>
    </div>
</body>
</html>