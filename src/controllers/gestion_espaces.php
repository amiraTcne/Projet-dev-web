<?php
session_start();
$connect = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$query = "SELECT * FROM v_utilisateurs ORDER BY date_inscription DESC";
$result = mysqli_query($connect, $query);
$utilisateurs = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
<div class="page">
    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-page">Gestion des Utilisateurs</div>

    <h3 class="options-title">Liste des inscrits (<?php echo count($utilisateurs); ?>)</h3>

    <div class="nav-grid">
        <?php foreach ($utilisateurs as $user): ?>
        <div class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h4>
                <?php echo htmlspecialchars($user['nom_complet']); ?><br>
                <small style="color:var(--gris-texte); font-weight:400; font-size:12px;">
                    <?php echo $user['role_premier']; ?> • <?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?>
                </small>
            </h4>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="deconnexion">
        <a href="accueil_admin.php" class="btn-retour">← Retour au menu</a>
    </div>
</div>
</body>
</html>