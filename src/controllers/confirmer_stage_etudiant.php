<?php
session_start();

// 1. Vérification de sécurité
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$id_etudiant = (int)$_SESSION['id'];
$msg_ok = '';
$msg_err = '';

$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirmer_final') {
    $num_stage = (int)$_POST['num_stage'];

    mysqli_begin_transaction($conn);

    try {
        // A. Mise à jour du stage et statut candidature
        $sql_update = "UPDATE Stage 
                       SET statut = IF(date_debut > CURDATE(), 'en_attente', 'en_cours'), 
                           statut_candidature = 'confirmee_etudiant' 
                       WHERE num_stage = ? AND id_etudiant = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, "ii", $num_stage, $id_etudiant);
        mysqli_stmt_execute($stmt);

        // B. Attribution aléatoire d'un Tuteur au Stage
        $sql_tuteur = "SELECT id FROM Utilisateur 
                       WHERE role_premier = 'Tuteur' OR role_second = 'Tuteur' OR role_troisieme = 'Tuteur' 
                       ORDER BY RAND() LIMIT 1";
        $res_tuteur = mysqli_query($conn, $sql_tuteur);
        if ($tuteur = mysqli_fetch_assoc($res_tuteur)) {
            $id_tuteur = $tuteur['id'];
            $sql_assign_t = "UPDATE Stage SET id_tuteur = ? WHERE num_stage = ?";
            $stmt_t = mysqli_prepare($conn, $sql_assign_t);
            mysqli_stmt_bind_param($stmt_t, "ii", $id_tuteur, $num_stage);
            mysqli_stmt_execute($stmt_t);
        }

        // C. Création du Dossier de Stage (Correction : ajout id_etudiant et date_creation)
        $sql_dossier = "INSERT INTO Dossier_Stage (num_stage, id_etudiant, statut, date_creation) 
                        VALUES (?, ?, 'incomplet', NOW())";
        $stmt_dos = mysqli_prepare($conn, $sql_dossier);
        mysqli_stmt_bind_param($stmt_dos, "ii", $num_stage, $id_etudiant);
        mysqli_stmt_execute($stmt_dos);

        // On récupère l'ID du dossier créé
        $num_dossier = mysqli_insert_id($conn);

        // D. Sélection aléatoire d'un Jury
        $sql_jury = "SELECT id FROM Utilisateur 
                     WHERE role_premier = 'Jury' OR role_second = 'Jury' OR role_troisieme = 'Jury' 
                     ORDER BY RAND() LIMIT 1";
        $res_jury = mysqli_query($conn, $sql_jury);
        $jury = mysqli_fetch_assoc($res_jury);

        if (!$jury) {
            throw new Exception("Aucun membre du jury disponible pour l'affectation.");
        }
        $id_jury_aleatoire = $jury['id'];

        // E. Création de l'évaluation Jury
        $sql_eval = "INSERT INTO Evaluation_Jury (id_jury, num_dossier, valide) 
                     VALUES (?, ?, 0)";
        $stmt_eval = mysqli_prepare($conn, $sql_eval);
        mysqli_stmt_bind_param($stmt_eval, "ii", $id_jury_aleatoire, $num_dossier);
        mysqli_stmt_execute($stmt_eval);

        mysqli_commit($conn);
        $msg_ok = "Félicitations ! Votre stage est confirmé. Un tuteur et un jury ont été affectés à votre dossier.";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $msg_err = "Erreur technique : " . $e->getMessage();
    }
}

// Récupération des candidatures pour l'affichage
$query = "SELECT s.*, u.nom_entreprise 
          FROM Stage s 
          JOIN Utilisateur u ON s.id_entreprise = u.id 
          WHERE s.id_etudiant = $id_etudiant AND s.statut_candidature = 'acceptee_entreprise'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmer mon Stage</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .container { max-width: 800px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stage-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .btn-confirm { background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .alert-success { color: #155724; background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Confirmation de Stage</h2>

    <?php if ($msg_ok): ?>
        <div class="alert-success"><?php echo $msg_ok; ?></div>
    <?php endif; ?>

    <?php if ($msg_err): ?>
        <div class="alert-error"><?php echo $msg_err; ?></div>
    <?php endif; ?>

    <h3>Mes candidatures acceptées par l'entreprise :</h3>

    <?php if (mysqli_num_rows($result) === 0 && !$msg_ok): ?>
        <p>Aucune candidature en attente de confirmation.</p>
    <?php else: ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="stage-card">
                <div>
                    <strong><?php echo htmlspecialchars($row['titre']); ?></strong><br>
                    <small>Entreprise : <?php echo htmlspecialchars($row['nom_entreprise']); ?></small>
                </div>
                <form method="POST">
                    <input type="hidden" name="num_stage" value="<?php echo $row['num_stage']; ?>">
                    <input type="hidden" name="action" value="confirmer_final">
                    <button type="submit" class="btn-confirm" onclick="return confirm('Confirmer générera votre dossier et votre jury. Continuer ?')">
                        Confirmer mon Stage
                    </button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
    
    <p><a href="accueil_etudiant.php">← Retour à l'accueil</a></p>
</div>

</body>
</html>