<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Espace Administrateur – CY Stage</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">

  <!-- Lien vers CSS -->
  <link rel="stylesheet" href="/public/assets/css/style-admin.css">
</head>

<body>

<div class="card">

  <!-- HEADER -->
  <div class="card-header">
    <div class="logo-row">
      <img src="../../public/assets/img/logo.png" alt="CY Stage">
      <span class="badge-admin">Admin</span>
    </div>
    <p class="welcome-label">Bienvenue,</p>
    <p class="user-name">
      <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
    </p>
  </div>

  <!-- BODY -->
  <div class="card-body">
    <p class="section-label">Gestion de la plateforme</p>

    <nav class="nav-list">

      <a href="gestion_espaces.php" class="nav-item">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </span>
        <span class="nav-text">
          <h4>Gestion des espaces</h4>
          <p>Étudiants, tuteurs, entreprises, jurys</p>
        </span>
        <span class="nav-arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
      </a>

      <a href="gestion_stages.php" class="nav-item">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        </span>
        <span class="nav-text">
          <h4>Gestion des stages proposés</h4>
          <p>Offres, filières, durée, missions</p>
        </span>
        <span class="nav-arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
      </a>

      <a href="archives.php" class="nav-item">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </span>
        <span class="nav-text">
          <h4>Dossiers archivés</h4>
          <p>Rapports, conventions, évaluations</p>
        </span>
        <span class="nav-arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
      </a>

      <a href="notifications.php" class="nav-item">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </span>
        <span class="nav-text">
          <h4>Notifications & requêtes</h4>
          <p>Demandes en attente des étudiants</p>
        </span>
        <span class="nav-arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
      </a>

    </nav>
  </div>

  <div class="divider"></div>

  <!-- FOOTER -->
  <div class="card-footer">
    <a href="deconnexion.php" class="btn-logout">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Se déconnecter
    </a>
  </div>

</div>

</body>
</html>