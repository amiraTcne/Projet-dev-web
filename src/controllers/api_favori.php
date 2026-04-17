<?php
/* Démarrage de la session pour accéder aux variables de connexion */
session_start();

/* On indique au navigateur que la réponse sera du JSON en UTF-8 */
header('Content-Type: application/json; charset=utf-8');

/* on vérifie les droits d'accès, seul un étudiant connecté peut gérer ses favoris */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    echo json_encode(['success' => false]);
    exit();
}

/* on récupère et valide les paramètres POST */

/* Identifiant de l'offre ciblée, on force le type entier pour la sécurité */
$num_offre = (int)($_POST['num_offre'] ?? 0);

/* on traite les actions qui sont demandée : soit 'add' pour ajouter, soit 'remove' pour retirer */
$action = trim($_POST['action'] ?? '');

/* Si les paramètres sont invalides, on arrête immédiatement */
if ($num_offre <= 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false]);
    exit();
}

/* Connexion à la base de données */
$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');

/* Si la connexion échoue, on renvoie une erreur JSON */
if (!$conn) {
    echo json_encode(['success' => false]);
    exit();
}

/* on force l'encodage UTF-8 pour les accents */
mysqli_set_charset($conn, 'utf8mb4');

/*pour le traitement de l'action */
if ($action === 'add') {

    /* avant q'on insère, on vérifie que le favori n'existe pas déjà pour éviter une erreur (le fait que la clé soit dupliquée dans notre base de données) */
    $chk = mysqli_prepare($conn, "SELECT 1 FROM Favori WHERE id_user = ? AND num_offre = ?");
    mysqli_stmt_bind_param($chk, 'ii', $_SESSION['id'], $num_offre);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);

    if (mysqli_stmt_num_rows($chk) > 0) {
        /* si e favori existe déjà : on répond success sans rien faire */
        echo json_encode(['success' => true]);
    } else {
        /* si le favori n'existe pas encore : on l'insère */
        $ins = mysqli_prepare($conn, "INSERT INTO Favori (id_user, num_offre) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, 'ii', $_SESSION['id'], $num_offre);
        echo json_encode(['success' => mysqli_stmt_execute($ins)]);
        mysqli_stmt_close($ins);
    }

    mysqli_stmt_close($chk);

} else {
    /* pour l'action 'remove' : on supprime le favori de la base */
    $del = mysqli_prepare($conn, "DELETE FROM Favori WHERE id_user = ? AND num_offre = ?");
    mysqli_stmt_bind_param($del, 'ii', $_SESSION['id'], $num_offre);
    echo json_encode(['success' => mysqli_stmt_execute($del)]);
    mysqli_stmt_close($del);
}

/* Fermeture de la connexion */
mysqli_close($conn);
