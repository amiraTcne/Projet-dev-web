<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Archives – CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
    <div class="card">
        <div class="card-header" style="background: white; border-bottom: 1px solid #dde6ff;">
            <div class="logo-row">
                <img src="../../public/assets/img/logo.png" alt="CY Tech" style="height: 40px; width: auto;">
                <span class="badge-admin" style="background: #255FAA; color: white;">Archives</span>
            </div>
            <h2 style="margin-top:15px; font-family:'Syne'; color: #0f172a;">Historique des stages</h2>
        </div>

        <div class="card-body">
          <p class="section-label">Stockage durable</p>
            <div class="nav-item">
              <span class="nav-text"><h4>Dossiers de stage</h4><p>Consulter les rapports passés </p></span>
            </div>
            <div class="nav-item">
               <span class="nav-text"><h4>Données Étudiants</h4><p>Réutiliser les infos d'une année sur l'autre </p></span>
            </div>
            <div class="nav-item">
              <span class="nav-text"><h4>Anciennes Entreprises</h4><p>Historique des boîtes ayant pris des stagiaires</p></span>
            </div>

            <a href="accueil_admin.php" class="btn-logout" style="margin-top: 20px; border-color: #dde6ff;">← Retour</a>
        </div>
    </div>
</body>
</html>