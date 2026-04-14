<?php
// On affiche les erreurs pour comprendre ce qui ne va pas
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Vérification du fichier de config
if (!file_exists('../../config.php')) {
    die("Erreur : Le fichier config.php est introuvable au chemin ../../config.php");
}
require_once '../../config.php'; 

// 2. Récupération des données (On teste la requête)
try {
    $stmt = $pdo->query("SELECT id, nom, prenom, role_premier FROM Utilisateur ORDER BY nom ASC");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // On log l'action (Obligatoire point 4 du cahier des charges) [cite: 79]
    if (function_exists('loggerAction')) {
        loggerAction("Consultation de la gestion des espaces.");
    }
} catch (Exception $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Espaces</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
    <div class="card">
        <div class="card-header" style="background:white; border-bottom:1px solid #dde6ff; padding:20px;">
             <h2 style="color:#255FAA; font-family:sans-serif;">Gestion des Utilisateurs</h2>
        </div>
        <div class="card-body" style="padding:20px;">
            <p style="color:gray; font-size:12px; margin-bottom:15px;">
                Nombre d'utilisateurs : <?php echo count($utilisateurs); ?>
            </p>
            
            <div class="nav-list">
                <?php foreach ($utilisateurs as $user): ?>
                    <div style="border:1px solid #dde6ff; padding:10px; border-radius:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="display:block;"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></strong>
                            <span style="font-size:11px; color:white; background:#255FAA; padding:2px 6px; border-radius:5px;">
                                <?php echo htmlspecialchars($user['role_premier']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="accueil_admin.php" style="display:inline-block; margin-top:20px; text-decoration:none; color:#255FAA; font-weight:bold;">
                ← Retour
            </a>
        </div>
    </div>
</body>
</html>