<?php
session_start();

// 1. Vérification de sécurité[cite: 30]
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
        // A. Mise à jour du stage et statut candidature[cite: 30]
        $sql_update = "UPDATE Stage 
                       SET statut = IF(date_debut > CURDATE(), 'en_attente', 'en_cours'), 
                           statut_candidature = 'confirmee_etudiant' 
                       WHERE num_stage = ? AND id_etudiant = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, "ii", $num_stage, $id_etudiant);
        mysqli_stmt_execute($stmt);

        // B. Attribution aléatoire d'un Tuteur au Stage[cite: 30]
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

        // C. Création du Dossier de Stage[cite: 30]
        $sql_dossier = "INSERT INTO Dossier_Stage (num_stage, id_etudiant, statut, date_creation) 
                        VALUES (?, ?, 'incomplet', NOW())";
        $stmt_dos = mysqli_prepare($conn, $sql_dossier);
        mysqli_stmt_bind_param($stmt_dos, "ii", $num_stage, $id_etudiant);
        mysqli_stmt_execute($stmt_dos);

        // On récupère l'ID du dossier créé
        $num_dossier = mysqli_insert_id($conn);

        // D. Sélection aléatoire d'un Jury[cite: 30]
        $sql_jury = "SELECT id FROM Utilisateur 
                     WHERE role_premier = 'Jury' OR role_second = 'Jury' OR role_troisieme = 'Jury' 
                     ORDER BY RAND() LIMIT 1";
        $res_jury = mysqli_query($conn, $sql_jury);
        $jury = mysqli_fetch_assoc($res_jury);

        if (!$jury) {
            throw new Exception("Aucun membre du jury disponible pour l'affectation.");
        }
        $id_jury_aleatoire = $jury['id'];

        // E. Création de l'évaluation Jury[cite: 30]
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

// Récupération des candidatures pour l'affichage[cite: 30]
$query = "SELECT s.*, u.nom_entreprise 
          FROM Stage s 
          JOIN Utilisateur u ON s.id_entreprise = u.id 
          WHERE s.id_etudiant = $id_etudiant AND s.statut_candidature = 'acceptee_entreprise'";
$result = mysqli_query($conn, $query);

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer mon Stage — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy {
            border: 1px solid rgba(171,186,205,.4); border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem;
            transition: transform 0.2s;
        }
        .card-cy:hover { transform: translateY(-3px); border-color: var(--bleu-clair); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-5">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_etudiant.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:800px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_etudiant.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Confirmation de Stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Validez votre candidature acceptée pour générer votre dossier.</p>
        </div>
    </div>

    <?php if ($msg_ok): ?>
        <div class="alert alert-success d-flex align-items-center rounded-4 mb-4 gap-2 shadow-sm"><i class="bi bi-check-circle-fill fs-5"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>

    <?php if ($msg_err): ?>
        <div class="alert alert-danger d-flex align-items-center rounded-4 mb-4 gap-2 shadow-sm"><i class="bi bi-exclamation-triangle-fill fs-5"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-building-check text-success me-2"></i>Mes candidatures acceptées :</h5>

    <?php if (mysqli_num_rows($result) === 0 && !$msg_ok): ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-hourglass-split text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h6 class="fw-bold text-dark">Aucune candidature en attente de confirmation</h6>
            <p class="text-muted mb-0 small">Vous devez d'abord recevoir une réponse positive d'une entreprise.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="card-cy d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    <div>
                        <h6 class="fw-bold text-primary mb-1" style="font-family:'Syne',sans-serif;"><?php echo h($row['titre']); ?></h6>
                        <p class="text-muted mb-0 fw-semibold" style="font-size:.85rem;"><i class="bi bi-building me-1"></i> <?php echo h($row['nom_entreprise']); ?></p>
                    </div>
                    
                    <form method="POST" class="m-0">
                        <input type="hidden" name="num_stage" value="<?php echo $row['num_stage']; ?>">
                        <input type="hidden" name="action" value="confirmer_final">
                        <button type="submit" class="btn btn-success rounded-pill fw-bold px-4 shadow-sm" onclick="return confirm('Attention : Confirmer générera officiellement votre dossier et affectera votre jury. Voulez-vous continuer ?')">
                            <i class="bi bi-check2-circle me-1"></i> Confirmer mon Stage
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>