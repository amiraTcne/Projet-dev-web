<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — CY Stage</title>

    <!-- Votre CSS existant conservé -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Bootstrap 5 -->
    <?php include 'frameworks.php'; ?>
</head>
<body>

    <img class="logo_connexion" src="assets/img/logo.png" alt="CY Stage">
    <h2 style="font-family: 'Montserrat Alternates', sans-serif;font-weight: 600;font-size: 0.78rem;letter-spacing: 0.22em;color: var(--bleu-horizon);text-align: center;text-transform: uppercase;margin-bottom: 20px;">Stage</h2>
    <h1 style="font-weight: 700;font-size: 1.3rem;color: var(--text-main);text-align: center;letter-spacing: -0.2px;margin-bottom: 26px;">Connexion</h1><br>

    <?php
    /* Affichage des erreurs de connexion via Bootstrap alerts */
    $erreur = $_GET['erreur'] ?? '';
    $messages = [
        '1' => 'Veuillez remplir tous les champs.',
        '2' => 'Erreur de connexion à la base de données.',
        '3' => 'Email ou mot de passe incorrect.',
        '4' => 'Accès non autorisé. Veuillez vous reconnecter.',
    ];
    if ($erreur && isset($messages[$erreur])) : ?>
        <!-- Alert Bootstrap (remplace les anciens messages d'erreur) -->
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert"
             style="max-width:400px; margin:0 auto 16px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($messages[$erreur]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <form action="../src/controllers/verifierConnexion.php" method="POST">

        <!-- Champ email avec icône Bootstrap Icons -->
        <div class="input-group mb-3" style="max-width:400px; margin:0 auto;">
            <span class="input-group-text" style="background:#eef1f7; border-color:#dde4ef;">
                <i class="bi bi-envelope" style="color:#5686D9;"></i>
            </span>
            <input
                type="email"
                id="login"
                name="login"
                class="form-control"
                placeholder="Adresse mail @"
                required
                style="border-color:#dde4ef; background:#eef1f7;"
            >
        </div>

        <!-- Champ mot de passe avec toggle show/hide via Alpine.js -->
        <div class="input-group mb-3" style="max-width:400px; margin:0 auto;"
             x-data="{ showPwd: false }">
            <span class="input-group-text" style="background:#eef1f7; border-color:#dde4ef; height:40px;">
                <i class="bi bi-lock" style="color:#5686D9;"></i>
            </span>
            <input
                :type="showPwd ? 'text' : 'password'"
                id="mdp"
                name="mdp"
                class="form-control"
                placeholder="Mot de passe"
                required
                style="border-color:#dde4ef; background:#eef1f7;height:40px;"
            >
            <!-- Bouton œil pour afficher/masquer le mot de passe -->
            <button
                type="button"
                class="input-group-text"
                style="background:#eef1f7; border-color:#dde4ef; cursor:pointer;height:40px;"
                @click="showPwd = !showPwd"
                :title="showPwd ? 'Masquer' : 'Afficher'"
            >
                <i :class="showPwd ? 'bi bi-eye-slash' : 'bi bi-eye'" style="color:#5686D9;"></i>
            </button>
        </div>

        <a href="#" style="display:block; text-align:right; max-width:400px; margin:0 auto 16px; font-size:.82rem;">
            Mot de passe oublié ?
        </a>

        <input type="submit" value="Connexion">
    </form>

    <a href="../src/controllers/index.php"
       style="display:inline-block; margin-top:20px; text-decoration:none; color:#255FAA; font-weight:bold;">
       Retour
    </a>

</body>
</html>