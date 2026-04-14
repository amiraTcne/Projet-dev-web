<?php 
session_start(); 
// Optionnel : vérifier ici si l'utilisateur est admin
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Notifications – CY Stage</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Syne:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/public/assets/css/style-admin.css">
</head>
<body>

<div class="card">
  <div class="card-header">
    <div class="logo-row">
      <img src="/public/assets/img/logo.png" alt="CY Stage">
      <span class="badge-admin">Notifications</span>
    </div>
    <p class="welcome-label">Flux des requêtes étudiantes</p>
  </div>

  <div class="card-body">
    <p class="section-label">Demandes d'ajouts de formations </p>
    
    <div class="nav-item" style="cursor: default; border-color: #fee2e2;">
       <span class="nav-text">
          <h4>Nouvelle formation </h4>
          <p>Un étudiant souhaite ajouter la filière "IA & Data". </p>
       </span>
    </div>

    <a href="accueil_admin.php" class="btn-logout" style="margin-top: 20px; border-color: var(--border);">
      ← Retour a a la page admin
    </a>
  </div>
</div>

</body>
</html>