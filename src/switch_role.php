<?php
session_start();

/* 1. Récupération du rôle demandé depuis l'URL */
$nouveau_role = $_GET['role'] ?? '';

/* 2. Liste des rôles que l'utilisateur possède réellement en base de données */
// Ces variables doivent avoir été définies dans $_SESSION lors du login[cite: 7]
$roles_possibles = [
    $_SESSION['role_premier'] ?? '',
    $_SESSION['role_second'] ?? '',
    $_SESSION['role_troisieme'] ?? ''
];

/* 3. Vérification de sécurité : le rôle demandé fait-il partie de ses rôles autorisés ? */
if (in_array($nouveau_role, $roles_possibles) && !empty($nouveau_role)) {
    
    // On met à jour le rôle "actif" pour cette session[cite: 1]
    $_SESSION['role'] = $nouveau_role; 
    
    // Redirection vers la page d'accueil correspondante au nouveau rôle
    $direction = match($nouveau_role) {
        'Admin'      => 'controllers/accueil_admin.php',
        'Tuteur'     => 'controlleurs/accueil_tuteur.php',
        'Jury'       => 'controllers/accueil_jury.php',
        'Etudiant'   => 'controllers/accueil_etudiant.php',
        'Entreprise' => 'controllers/accueil_entreprise.php',
        default      => '../../public/login.php'
    };
    
    header("Location: " . $direction);
    exit();
} else {
    // Si tentative de fraude ou rôle inexistant
    header('Location: ../../public/login.php?erreur=4');
    exit();
}