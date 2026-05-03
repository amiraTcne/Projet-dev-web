<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$dossiers = [];
$msg_ok  = '';
$msg_err = '';

/* Valider ou refuser une convention */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $num_dossier = (int)($_POST['num_dossier'] ?? 0);
    $action      = $_POST['action_val'] ?? '';

    if ($num_dossier > 0 && in_array($action, ['valider', 'refuser'])) {
        $commentaire = trim($_POST['commentaire'] ?? '');

        /* Vérifier que le dossier appartient bien à un des étudiants de ce tuteur */
        $chk = mysqli_prepare($conn,
            "SELECT d.num_dossier FROM Dossier_Stage d
             JOIN Stage s ON s.num_stage = d.num_stage
             WHERE d.num_dossier = ? AND s.id_tuteur = ?"
        );
        mysqli_stmt_bind_param($chk, 'ii', $num_dossier, $_SESSION['id']);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $ok = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);

        if ($ok) {
            /* Insérer la validation */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Validation_Convention (num_dossier, validee_par, id_validateur, commentaire)
                 VALUES (?, 'tuteur', ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'iis', $num_dossier, $_SESSION['id'], $commentaire);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            /* Mettre à jour le statut du dossier */
            $nouveau_statut = $action === 'valider' ? 'valide' : 'rejete';
            $upd = mysqli_prepare($conn,
                "UPDATE Dossier_Stage SET statut = ?, date_modification = NOW() WHERE num_dossier = ?"
            );
            mysqli_stmt_bind_param($upd, 'si', $nouveau_statut, $num_dossier);
            if (mysqli_stmt_execute($upd)) {
                $msg_ok = $action === 'valider' ? 'Convention validée avec succès ✓' : 'Convention refusée.';
            }
            mysqli_stmt_close($upd);
        } else {
            $msg_err = 'Action non autorisée.';
        }
    }
}

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Tous les dossiers des étudiants suivis par ce tuteur */
    $stmt = mysqli_prepare($conn,
        "SELECT d.num_dossier, d.statut, d.convention_url, d.date_modification,
                s.titre AS titre_stage, s.date_debut, s.date_fin,
                CONCAT(e.prenom, ' ', e.nom) AS nom_etudiant,
                ent.nom_entreprise,
                (SELECT COUNT(*) FROM Validation_Convention vc
                 WHERE vc.num_dossier = d.num_dossier AND vc.validee_par = 'tuteur') AS deja_valide
         FROM Dossier_Stage d
         JOIN Stage s ON s.num_stage = d.num_stage
         JOIN Utilisateur e   ON e.id  = s.id_etudiant
         JOIN Utilisateur ent ON ent.id = s.id_entreprise
         WHERE s.id_tuteur = ?
         ORDER BY d.date_creation DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $dossiers[] = $row;
    mysqli_stmt_close($stmt);

    mysqli_close($conn);
}

// Couleurs Bootstrap pour les statuts
$statuts_labels = [
    'incomplet' => ['Incomplet', 'bg-danger text-white'],
    'en_cours'  => ['En cours',  'bg-warning text-dark'],
    'soumis'    => ['Soumis',    'bg-primary text-white'],
    'valide'    => ['Validé ✓',  'bg-success text-white'],
    'rejete'    => ['Refusé',    'bg-danger text-white'],
];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valider Conventions — CY Stage</title>
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
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_tuteur.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-workspace me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_tuteur.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Valider les Conventions</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Examinez et approuvez les conventions de vos étudiants</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <?php if (empty($dossiers)) : ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-file-earmark-check text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucune convention en attente</h5>
            <p class="text-muted mb-0">Vous n'avez pas encore d'étudiants avec un dossier actif.</p>
        </div>
    <?php else : ?>
        <div class="mb-3 text-muted fw-bold" style="font-size:.9rem;"><?php echo count($dossiers); ?> dossier(s) trouvé(s)</div>
        <div class="row g-4">
            <?php foreach ($dossiers as $d) : 
                $st_data = $statuts_labels[$d['statut']] ?? [$d['statut'], 'bg-secondary text-white'];
            ?>
            <div class="col-12">
                <div class="card-cy">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif; color:#111827;"><i class="bi bi-person-badge text-muted me-2"></i><?php echo h($d['nom_etudiant']); ?></h5>
                        <span class="badge rounded-pill <?php echo $st_data[1]; ?>"><?php echo $st_data[0]; ?></span>
                    </div>
                    
                    <div class="row mb-4" style="font-size: .85rem;">
                        <div class="col-md-6 mb-2"><strong class="text-muted d-block">Stage</strong> <span class="fw-bold"><?php echo h($d['titre_stage']); ?></span></div>
                        <div class="col-md-6 mb-2"><strong class="text-muted d-block">Entreprise</strong> <span class="fw-bold"><?php echo h($d['nom_entreprise']); ?></span></div>
                        <div class="col-md-6 mb-2"><strong class="text-muted d-block">Période</strong> <span class="fw-bold"><?php echo $d['date_debut'] ? date('d/m/Y', strtotime($d['date_debut'])) : '—'; ?> au <?php echo $d['date_fin'] ? date('d/m/Y', strtotime($d['date_fin'])) : '—'; ?></span></div>
                        <div class="col-md-6 mb-2">
                            <strong class="text-muted d-block">Convention PDF</strong>
                            <?php if (!empty($d['convention_url'])) : ?>
                                <a href="/<?php echo h($d['convention_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill mt-1"><i class="bi bi-download me-1"></i> Télécharger le document</a>
                            <?php else : ?>
                                <span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Non déposée</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($d['deja_valide'] == 0) : ?>
                        <form method="POST" action="validation_convention_tuteur.php" class="bg-light p-3 rounded-4 border">
                            <input type="hidden" name="num_dossier" value="<?php echo (int)$d['num_dossier']; ?>">
                            <label class="form-label fw-bold text-dark" style="font-size: .85rem;">Commentaire <span class="text-muted fw-normal">(optionnel)</span></label>
                            <textarea class="form-control rounded-3 mb-3" name="commentaire" rows="2" placeholder="Ajoutez une remarque justifiant votre décision..."></textarea>
                            <div class="d-flex gap-2">
                                <button type="submit" name="action_val" value="valider" class="btn btn-success rounded-pill fw-bold px-4 flex-grow-1"><i class="bi bi-check-lg me-1"></i> Valider la convention</button>
                                <button type="submit" name="action_val" value="refuser" class="btn btn-outline-danger rounded-pill fw-bold px-4 flex-grow-1"><i class="bi bi-x-lg me-1"></i> Refuser</button>
                            </div>
                        </form>
                    <?php else : ?>
                        <div class="alert alert-success m-0 rounded-3 py-2 d-inline-block"><i class="bi bi-check2-all me-1"></i> Vous avez déjà statué sur ce dossier.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>