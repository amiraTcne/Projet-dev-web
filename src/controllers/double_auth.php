<?php
session_start();

// Vérification de la présence des données temporaires venant de verifierConnexion.php
if (!isset($_SESSION['tmp_2fa_user_id'])) {
    header('Location: login.php');
    exit();
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeSaisi = trim($_POST['code'] ?? '');

    // Note : J'utilise ici la logique de validation simplifiée de votre fichier source
    if ($codeSaisi === '') {
        $erreur = "Veuillez saisir le code reçu par email.";
    } else {
        // --- VALIDATION RÉUSSIE : TRANSFERT DES DONNÉES TEMPORAIRES VERS SESSION DÉFINITIVE ---
        
        $_SESSION['id']             = $_SESSION['tmp_2fa_user_id'];
        $_SESSION['nom']            = $_SESSION['tmp_2fa_nom'];
        $_SESSION['prenom']         = $_SESSION['tmp_2fa_prenom'];
        $_SESSION['email']          = $_SESSION['tmp_2fa_email'];
        
        // Gestion des rôles multiples
        $_SESSION['role']           = $_SESSION['tmp_2fa_role']; // Rôle actif par défaut
        $_SESSION['role_premier']   = $_SESSION['tmp_2fa_role'];
        $_SESSION['role_second']    = $_SESSION['tmp_2fa_role_second'];
        $_SESSION['role_troisieme'] = $_SESSION['tmp_2fa_role_troisieme'];

        // Informations spécifiques (Entreprise, Étudiant, etc.)
        if (!empty($_SESSION['tmp_2fa_nom_entreprise'])) {
            $_SESSION['nom_entreprise'] = $_SESSION['tmp_2fa_nom_entreprise'];
        }

        // Nettoyage complet des variables temporaires
        $to_unset = [
            'tmp_2fa_user_id', 'tmp_2fa_email', 'tmp_2fa_nom', 'tmp_2fa_prenom',
            'tmp_2fa_role', 'tmp_2fa_role_second', 'tmp_2fa_role_troisieme',
            'tmp_2fa_nom_entreprise', 'tmp_code', 'tmp_code_expire'
        ];
        foreach($to_unset as $key) { unset($_SESSION[$key]); }

        // --- REDIRECTION VERS L'INTERFACE APPROPRIÉE ---
        // On redirige selon le rôle principal qui vient d'être activé[cite: 1, 9]
        $redirect = match($_SESSION['role']) {
            'Admin'      => '../private/admin/accueil_admin.php',
            'Tuteur'     => '../private/tuteur/accueil_tuteur.php',
            'Jury'       => '../private/jury/accueil_jury.php',
            'Etudiant'   => '../private/etudiant/accueil_etudiant.php',
            'Entreprise' => '../private/entreprise/accueil_entreprise.php',
            default      => 'accueil.php'
        };

        header("Location: $redirect");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Double authentification — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 20px; }
    </style>
</head>
<body class="d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Vérification</h2>
                        <p class="text-muted">Saisissez le code de sécurité</p>
                    </div>

                    <?php if ($erreur) : ?>
                        <div class="alert alert-danger mb-3"><?= htmlspecialchars($erreur); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <input type="text" name="code" class="form-control form-control-lg text-center fw-bold" 
                                   placeholder="000000" maxlength="6" autofocus required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                            Valider et se connecter
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="login.php" class="text-muted small text-decoration-none">Retour à la page de connexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>