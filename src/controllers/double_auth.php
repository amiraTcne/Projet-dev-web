<?php
session_start();

if (!isset($_SESSION['tmp_user_id'], $_SESSION['tmp_code'], $_SESSION['tmp_code_expire'])) {
    header('Location: login.php');
    exit();
}

$erreur = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeSaisi = trim($_POST['code'] ?? '');

    if ($codeSaisi === '') {
        $erreur = "Veuillez saisir le code reçu par email.";
    } elseif (time() > $_SESSION['tmp_code_expire']) {
        $erreur = "Le code a expiré. Veuillez vous reconnecter.";
    } elseif ($codeSaisi !== $_SESSION['tmp_code']) {
        $erreur = "Code incorrect.";
    } else {
        $_SESSION['id'] = $_SESSION['tmp_user_id'];
        $_SESSION['role'] = $_SESSION['tmp_role'];

        if (!empty($_SESSION['tmp_nom_entreprise'])) {
            $_SESSION['nom_entreprise'] = $_SESSION['tmp_nom_entreprise'];
        }

        unset($_SESSION['tmp_user_id']);
        unset($_SESSION['tmp_email']);
        unset($_SESSION['tmp_role']);
        unset($_SESSION['tmp_nom_entreprise']);
        unset($_SESSION['tmp_code']);
        unset($_SESSION['tmp_code_expire']);

        if ($_SESSION['role'] === 'Entreprise') {
            header('Location: accueil_entreprise.php');
        } else {
            header('Location: accueil.php');
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Double authentification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-3 text-primary fw-bold">Vérification du code</h1>
                        <p class="text-muted">
                            Un code à 6 chiffres a été envoyé à votre adresse email.
                        </p>

                        <?php if ($erreur !== '') : ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($erreur); ?></div>
                        <?php endif; ?>

                        <?php if ($message !== '') : ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="code" class="form-label">Code reçu</label>
                                <input type="text" class="form-control form-control-lg text-center" id="code" name="code" maxlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Valider le code</button>
                        </form>

                        <div class="mt-3 text-center">
                            <a href="login.php" class="text-decoration-none">Retour à la connexion</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>