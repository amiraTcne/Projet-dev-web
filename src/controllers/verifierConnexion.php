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

// Une seule table Utilisateur — on récupère tous les champs utiles
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

// Vérification du mot de passe (hash bcrypt)
//if (!$row || !password_verify($mdp, $row['mot_de_passe'])) {
    //header('Location: ../../public/login.php?erreur=3');
  //  exit();
//}

// Données communes à tous les rôles
$_SESSION['id']             = $row['id'];
$_SESSION['email']          = $row['email'];
$_SESSION['nom']            = $row['nom'];
$_SESSION['prenom']         = $row['prenom'];
$_SESSION['role']           = $row['role_premier'];
$_SESSION['role_second']    = $row['role_second'];
$_SESSION['role_troisieme'] = $row['role_troisieme'];

// Données spécifiques selon le rôle principal
switch ($row['role_premier']) {

    case 'Etudiant':
        $_SESSION['filiere']     = $row['filiere'];
        $_SESSION['niveau']      = $row['niveau'];
        $_SESSION['annee_promo'] = $row['annee_promo'];
        break;

    case 'Tuteur':
        $_SESSION['specialite']  = $row['specialite'];
        $_SESSION['departement'] = $row['departement'];
        break;

    case 'Jury':
        $_SESSION['specialite'] = $row['specialite'];
        $_SESSION['commission'] = $row['commission'];
        $_SESSION['annee_jury'] = $row['annee_jury'];
        break;

    case 'Entreprise':
        $_SESSION['num_siret']      = $row['num_siret'];
        $_SESSION['nom_entreprise'] = $row['nom_entreprise'];
        $_SESSION['secteur']        = $row['secteur'];
        $_SESSION['ville']          = $row['ville'];
        $_SESSION['nb_stagiere']    = $row['nb_stagiere'];
        break;

    case 'Admin':
        // Pas de données supplémentaires spécifiques
        break;
}

// Redirection selon le rôle principal
switch ($row['role_premier']) {
    case 'Entreprise': header('Location: accueil_entreprise.php'); break;
    case 'Admin':      header('Location: accueil_admin.php');      break;
    case 'Etudiant':   header('Location: accueil_etudiant.php');   break;
    case 'Tuteur':     header('Location: accueil_tuteur.php');     break;
    case 'Jury':       header('Location: accueil_jury.php');       break;
    default:           header('Location: ../../public/login.php?erreur=3'); break;
}
exit();
?>
