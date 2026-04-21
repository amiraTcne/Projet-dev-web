<?php 
session_start(); 
// Sécurité : Si les variables de session ne sont pas définies, on met des valeurs par défaut pour éviter les erreurs
$prenom = isset($_SESSION['prenom']) ? $_SESSION['prenom'] : 'Admin';
$nom = isset($_SESSION['nom']) ? $_SESSION['nom'] : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Administrateur – CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_acceuil.css">
</head>
<body>

<div class="page">

    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">
        <?php echo htmlspecialchars($prenom . ' ' . $nom); ?>
    </div>

    <h3 class="options-title">Gestion de la plateforme</h3>

    <div class="nav-grid">

        <a href="gestion_espaces.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <h4>Gestion Utilisateurs</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <a href="gestion_stages.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            </span>
            <h4>Gestion des stages</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <a href="archives.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </span>
            <h4>Archives</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <a href="notifications.php" class="nav">
            <span class="icon">
                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </span>
            <h4>Notifications</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

    </div>

    <div class="deconnexion">
        <a href="deconnexion.php" style="text-decoration:none; color: #ef4444; font-weight:700;">Déconnexion</a>
    </div>

</div>

</body>
</html>