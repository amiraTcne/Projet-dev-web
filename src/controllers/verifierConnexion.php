<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);
if (!$connect) {
    header('Location: ../../public/login.php?erreur=2');
    exit();
}

$email = $_POST['login'] ?? '';
$mdp   = $_POST['mdp']   ?? '';

if (empty($email) || empty($mdp)) {
    header('Location: ../../public/login.php?erreur=1');
    exit();
}

$stmt = mysqli_prepare($connect,
    "SELECT id, nom, prenom, email, mot_de_passe,
            role_premier, role_second, role_troisieme,
            filiere, niveau, annee_promo,
            specialite, departement, commission, annee_jury,
            num_siret, nom_entreprise, secteur, ville, nb_stagiere
     FROM Utilisateur
     WHERE email = ? AND actif = 1"
);

mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($connect);

if (!$row) {
    header('Location: ../../public/login.php?erreur=3');
    exit();
}

$hash = $row['mot_de_passe'];

if (strlen($hash) >= 60 && strpos($hash, '$2y$') === 0) {
    if (!password_verify($mdp, $hash)) {
        header('Location: ../../public/login.php?erreur=3');
        exit();
    }
} else {
    if ($mdp !== $hash) {
        header('Location: ../../public/login.php?erreur=3');
        exit();
    }
}

unset($_SESSION['tmp_2fa_user_id']);
unset($_SESSION['tmp_2fa_email']);
unset($_SESSION['tmp_2fa_nom']);
unset($_SESSION['tmp_2fa_prenom']);
unset($_SESSION['tmp_2fa_role']);
unset($_SESSION['tmp_2fa_role_second']);
unset($_SESSION['tmp_2fa_role_troisieme']);
unset($_SESSION['tmp_2fa_code_sent']);

$_SESSION['tmp_2fa_user_id']        = $row['id'];
$_SESSION['tmp_2fa_email']          = $row['email'];
$_SESSION['tmp_2fa_nom']            = $row['nom'];
$_SESSION['tmp_2fa_prenom']         = $row['prenom'];
$_SESSION['tmp_2fa_role']           = $row['role_premier'];
$_SESSION['tmp_2fa_role_second']    = $row['role_second'];
$_SESSION['tmp_2fa_role_troisieme'] = $row['role_troisieme'];

$_SESSION['tmp_2fa_filiere']        = $row['filiere'] ?? null;
$_SESSION['tmp_2fa_niveau']         = $row['niveau'] ?? null;
$_SESSION['tmp_2fa_annee_promo']    = $row['annee_promo'] ?? null;
$_SESSION['tmp_2fa_specialite']     = $row['specialite'] ?? null;
$_SESSION['tmp_2fa_departement']    = $row['departement'] ?? null;
$_SESSION['tmp_2fa_commission']     = $row['commission'] ?? null;
$_SESSION['tmp_2fa_annee_jury']     = $row['annee_jury'] ?? null;
$_SESSION['tmp_2fa_num_siret']      = $row['num_siret'] ?? null;
$_SESSION['tmp_2fa_nom_entreprise'] = $row['nom_entreprise'] ?? null;
$_SESSION['tmp_2fa_secteur']        = $row['secteur'] ?? null;
$_SESSION['tmp_2fa_ville']          = $row['ville'] ?? null;
$_SESSION['tmp_2fa_nb_stagiere']    = $row['nb_stagiere'] ?? null;

// Redirection vers la seconde étape
header('Location: ../../public/double_auth.php');
exit();
?>