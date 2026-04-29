<?php
session_start();

/* Activation des erreurs SQL pour le débuggage */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* Sécurité : Vérification du rôle Entreprise */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$id_entreprise = $_SESSION['id'];
$msg_ok = '';
$msg_err = '';

try {
    $conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
    mysqli_set_charset($conn, 'utf8mb4');

    /**
     * LOGIQUE DE VALIDATION
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider') {
        $num_stage = (int)$_POST['num_stage']; // CORRIGÉ : num_stage

        mysqli_begin_transaction($conn);

        // 1. On récupère le num_offre. Plus besoin de jointure car Stage contient id_entreprise !
        $sql_info = "SELECT num_offre FROM Stage WHERE num_stage = ? AND id_entreprise = ? AND statut = 'en_attente'";
        $stmt_info = mysqli_prepare($conn, $sql_info);
        mysqli_stmt_bind_param($stmt_info, 'ii', $num_stage, $id_entreprise);
        mysqli_stmt_execute($stmt_info);
        $res_info = mysqli_stmt_get_result($stmt_info);
        $stage_data = mysqli_fetch_assoc($res_info);

        if (!$stage_data) {
            throw new Exception("Candidature introuvable ou vous n'avez pas les droits sur cette offre.");
        }

        $num_offre = $stage_data['num_offre']; // CORRIGÉ : num_offre

        // 2. On valide le stage de l'étudiant choisi
        $sql_accept = "UPDATE Stage SET statut = 'en_cours' WHERE num_stage = ?";
        $stmt_accept = mysqli_prepare($conn, $sql_accept);
        mysqli_stmt_bind_param($stmt_accept, 'i', $num_stage);
        mysqli_stmt_execute($stmt_accept);

        // 3. On refuse TOUTES les autres candidatures pour cette même offre
        $sql_refuse = "UPDATE Stage SET statut = 'refuse' WHERE num_offre = ? AND num_stage != ? AND statut = 'en_attente'";
        $stmt_refuse = mysqli_prepare($conn, $sql_refuse);
        mysqli_stmt_bind_param($stmt_refuse, 'ii', $num_offre, $num_stage);
        mysqli_stmt_execute($stmt_refuse);

        // 4. On passe l'offre en statut 'pourvue'
        $sql_offre = "UPDATE Offre_Stage SET statut = 'pourvue' WHERE num_offre = ?";
        $stmt_offre = mysqli_prepare($conn, $sql_offre);
        mysqli_stmt_bind_param($stmt_offre, 'i', $num_offre);
        mysqli_stmt_execute($stmt_offre);

        mysqli_commit($conn);
        $msg_ok = "Candidature validée avec succès ! L'offre est maintenant pourvue et les autres candidats ont été refusés.";
    }

    /**
     * RÉCUPÉRATION DES CANDIDATURES (Stages en attente)
     */
    $candidatures = [];
    // CORRIGÉ : s.num_stage
    $sql_list = "SELECT s.num_stage, u.nom, u.prenom, u.filiere, s.titre 
                 FROM Stage s
                 JOIN Utilisateur u ON s.id_etudiant = u.id
                 WHERE s.id_entreprise = ? AND s.statut = 'en_attente'";

    $stmt_list = mysqli_prepare($conn, $sql_list);
    mysqli_stmt_bind_param($stmt_list, 'i', $id_entreprise);
    mysqli_stmt_execute($stmt_list);
    $result = mysqli_stmt_get_result($stmt_list);

    while ($row = mysqli_fetch_assoc($result)) {
        $candidatures[] = $row;
    }

} catch (mysqli_sql_exception $e) {
    if (isset($conn)) mysqli_rollback($conn);
    $msg_err = "Erreur SQL : " . $e->getMessage();
} catch (Exception $e) {
    if (isset($conn)) mysqli_rollback($conn);
    $msg_err = "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recrutement - Entreprise</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 30px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #1a73e8; margin-bottom: 20px; }
        .cand-card { border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
        .cand-card:hover { background: #f8f9fa; border-color: #1a73e8; }
        .info-etudiant strong { font-size: 1.1em; color: #333; }
        .info-etudiant p { margin: 5px 0; color: #666; }
        .btn-valider { background: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-valider:hover { background: #218838; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .sql-error { display:block; margin-top:10px; font-family: monospace; color: #fff; background: #333; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Gestion des candidatures</h1>

    <?php if($msg_ok): ?> <div class="alert success"><?php echo $msg_ok; ?></div> <?php endif; ?>
    <?php if($msg_err): ?> 
        <div class="alert error">
            Attention : <span class="sql-error"><?php echo htmlspecialchars($msg_err); ?></span>
        </div> 
    <?php endif; ?>

    <?php if (empty($candidatures) && !$msg_err): ?>
        <p>Aucun étudiant n'a postulé pour le moment.</p>
    <?php elseif (!empty($candidatures)): ?>
        <?php foreach ($candidatures as $c): ?>
            <div class="cand-card">
                <div class="info-etudiant">
                    <strong><?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?></strong>
                    <p>Filière : <?php echo htmlspecialchars($c['filiere'] ?? 'Non renseignée'); ?></p>
                    <p>Offre : <em><?php echo htmlspecialchars($c['titre']); ?></em></p>
                </div>
                <form method="POST">
                    <input type="hidden" name="num_stage" value="<?php echo $c['num_stage']; ?>">
                    <button type="submit" name="action" value="valider" class="btn-valider" onclick="return confirm('Accepter cet étudiant ? Les autres candidats pour cette offre seront refusés.')">
                        Valider la candidature
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>