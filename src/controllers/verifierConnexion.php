<?php
// ============================================================
//  verifierConnexion.php
//  Vérifie le login parmi les 5 types d'utilisateurs
// ============================================================
 
session_start();
 
// ── Paramètres de connexion BDD ──────────────────────────────
$host = 'localhost';
$dbname = 'cyStages';
$user = 'ambre';
$pass = 'Mdp4Sql!';
 
// ── Récupération des données du formulaire ───────────────────
$email = trim($_POST['login'] ?? '');
$mdp   = $_POST['mdp'] ?? '';
 
if ($email === '' || $mdp === '') {
    header('Location: logo.php?erreur=champs_vides');
    exit;
}
 
// ── Connexion PDO ────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données.');
}
 
// ── Tables à interroger et rôle associé ──────────────────────
// [ 'table', 'colonne_id', 'role' ]
$tables = [
    ['Admin',      'id',            'admin'],
    ['Etudiant',   'id_etu',        'etudiant'],
    ['Tuteur',     'id',            'tuteur'],
    ['Jurys',      'id',            'jury'],
    ['Entreprise', 'numeroSiret',   'entreprise'],
];
 
$utilisateur = null;
$role        = null;
 
foreach ($tables as [$table, $colId, $roleNom]) {
    $stmt = $pdo->prepare(
        "SELECT * FROM `$table` WHERE email = :email AND mdp = :mdp LIMIT 1"
    );
    $stmt->execute([':email' => $email, ':mdp' => $mdp]);
    $ligne = $stmt->fetch();
 
    if ($ligne) {
        $utilisateur = $ligne;
        $role        = $roleNom;
        break;
    }
}
 
// ── Résultat ─────────────────────────────────────────────────
if ($utilisateur === null) {
    // Aucun utilisateur trouvé → retour avec erreur
    header('Location: index.php?erreur=identifiants_incorrects');
    exit;
}
 
// Authentification réussie → stockage en session
$_SESSION['connecte']   = true;
$_SESSION['role']       = $role;
$_SESSION['email']      = $email;
 
// Stockage des infos selon le type
switch ($role) {
    case 'entreprise':
        $_SESSION['id']  = $utilisateur['numeroSiret'];
        $_SESSION['nom'] = $utilisateur['filiere'] ?? 'Entreprise';
        break;
    case 'etudiant':
        $_SESSION['id']     = $utilisateur['id_etu'];
        $_SESSION['nom']    = $utilisateur['prenom'] . ' ' . $utilisateur['nom'];
        $_SESSION['filiere'] = $utilisateur['filiere'];
        $_SESSION['niveau']  = $utilisateur['niveau'];
        break;
    default:
        $_SESSION['id']  = $utilisateur['id'];
        $_SESSION['nom'] = $utilisateur['prenom'] . ' ' . $utilisateur['nom'];
}
 
session_regenerate_id(true);
 
// Redirection selon le rôle
switch ($role) {
    case 'admin':
        header('Location: accueil_admin.php');
        break;
    case 'etudiant':
        header('Location: accueil_etudiant.php');
        break;
    case 'tuteur':
        header('Location: acceuil_tuteur.php');
        break;
    case 'jury':
        header('Location: acceuil_jury.php');
        break;
    case 'entreprise':
        header('Location: acceuil_entreprise.php');
        break;
    default:
        header('Location: login.php');
}
exit;

?>