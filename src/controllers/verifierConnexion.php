<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'ambre';
$db_pass = 'Mdp4Sql!';

// Connexion avec mysqli
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

$tables = [
    'Entreprise' => ['id_col' => 'numeroSiret', 'extra' => ['filiere', 'nbStagiaire']],
    'Admin'      => ['id_col' => 'id',          'extra' => ['nom', 'prenom']],
    'Etudiant'   => ['id_col' => 'id_etu',      'extra' => ['nom', 'prenom', 'filiere', 'niveau']],
    'Tuteur'     => ['id_col' => 'id',          'extra' => ['nom', 'prenom']],
    'Jurys'      => ['id_col' => 'id',          'extra' => ['nom', 'prenom']],
];

$trouve = false;

foreach ($tables as $table => $config) {
    $id_col   = $config['id_col'];
    $colonnes = array_merge([$id_col, 'email'], $config['extra']);
    $select   = implode(', ', $colonnes);

    // Préparation de la requête avec mysqli
    $stmt = mysqli_prepare($connect, "SELECT $select FROM $table WHERE email = ? AND mdp = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $email, $mdp);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);

    if ($row) {
        $_SESSION['role']  = $table;
        $_SESSION['email'] = $row['email'];
        $_SESSION['id']    = $row[$id_col];

        switch ($table) {
            case 'Entreprise':
                $_SESSION['numeroSiret'] = $row['numeroSiret'];
                $_SESSION['filiere']     = $row['filiere'];
                $_SESSION['nbStagiaire'] = $row['nbStagiaire'];
                break;
            case 'Admin':
                $_SESSION['nom']    = $row['nom'];
                $_SESSION['prenom'] = $row['prenom'];
                break;
            case 'Etudiant':
                $_SESSION['nom']     = $row['nom'];
                $_SESSION['prenom']  = $row['prenom'];
                $_SESSION['filiere'] = $row['filiere'];
                $_SESSION['niveau']  = $row['niveau'];
                break;
            case 'Tuteur':
            case 'Jurys':
                $_SESSION['nom']    = $row['nom'];
                $_SESSION['prenom'] = $row['prenom'];
                break;
        }

        $trouve = true;
        mysqli_stmt_close($stmt);
        break;
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($connect);
if ($trouve) {
    switch ($_SESSION['role']) {
        case 'Entreprise': header('Location: accueil_entreprise.php'); break;
        case 'Admin':      header('Location: accueil_admin.php');      break;
        case 'Etudiant':   header('Location: accueil_etudiant.php');   break;
        case 'Tuteur':     header('Location: accueil_tuteur.php');     break;
        case 'Jurys':      header('Location: accueil_jury.php');       break;
    }
} else {
    header('Location: ../../public/login.php?erreur=3');
}
exit();
?>