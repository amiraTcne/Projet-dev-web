<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Jury') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn     = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$dossiers = [];

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* On récupère uniquement le nécessaire pour les cartes :
       num_dossier (pour le lien), nom/prénom/filière/niveau de l'étudiant, statut du dossier */
    $stmt = mysqli_prepare($conn,
        "SELECT DISTINCT
            d.num_dossier,
            d.statut        AS statut_dossier,
            e.nom           AS etudiant_nom,
            e.prenom        AS etudiant_prenom,
            e.filiere,
            e.niveau
         FROM Dossier_Stage d
         JOIN Stage s           ON s.num_stage    = d.num_stage
         JOIN Utilisateur e     ON e.id           = d.id_etudiant
         LEFT JOIN Evaluation_Jury ev
                                ON ev.num_dossier = d.num_dossier
                               AND ev.id_jury     = ?
         WHERE
             ev.id_jury = ?
             OR (d.statut = 'soumis' AND ev.id_jury IS NULL)
         ORDER BY e.nom, e.prenom"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['id'], $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $dossiers[] = $row;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

$statuts = [
    'incomplet' => ['Incomplet', 'bg-danger-subtle text-danger border-danger-subtle'],
    'en_cours'  => ['En cours',  'bg-warning-subtle text-warning border-warning-subtle'],
    'soumis'    => ['Soumis',    'bg-primary-subtle text-primary border-primary-subtle'],
    'valide'    => ['Validé ✓',  'bg-success-subtle text-success border-success-subtle'],
    'rejete'    => ['Refusé',    'bg-danger-subtle text-danger border-danger-subtle'],
];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Jury — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        
        .nav-etudiant {
            display: flex; align-items: center; gap: 1rem;
            padding: 1.2rem; border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px; background: #fff;
            text-decoration: none; color: inherit;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 18px rgba(27,79,155,.04);
            height: 100%;
        }
        .nav-etudiant:hover {
            transform: translateY(-3px); box-shadow: 0 10px 20px rgba(27,79,155,.1);
            border-color: var(--bleu-clair); color: inherit;
        }
        .avatar {
            width: 50px; height: 50px; border-radius: 50%;
            background: linear-gradient(135deg, #1B4F9B, #2563c7); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.1rem; flex-shrink: 0;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-5">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="#"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-badge-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?> (Jury)</span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:1000px;">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Dossiers à évaluer</h1>
            <p class="text-muted mb-0">Consultez et évaluez les rapports de stage soumis.</p>
        </div>
        <span class="badge bg-primary rounded-pill fs-6"><?php echo count($dossiers); ?> dossier(s)</span>
    </div>

    <?php if (empty($dossiers)) : ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-folder-check text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucun dossier à évaluer</h5>
            <p class="text-muted mb-0">Les dossiers soumis par les étudiants apparaîtront ici.</p>
        </div>
    <?php else : ?>
        <div class="row g-4">
            <?php foreach ($dossiers as $d) :
                $initiales = strtoupper(mb_substr($d['etudiant_prenom'] ?? '?', 0, 1) . mb_substr($d['etudiant_nom'] ?? '?', 0, 1));
                $st_classes = $statuts[$d['statut_dossier']] ?? ['Inconnu', 'bg-secondary text-white'];
            ?>
                <div class="col-md-6 col-lg-6">
                    <a href="dossier_jury.php?id=<?php echo (int)$d['num_dossier']; ?>" class="nav-etudiant">
                        <div class="avatar"><?php echo h($initiales); ?></div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1" style="color:#111827; font-family:'Syne',sans-serif;"><?php echo h($d['etudiant_prenom'] . ' ' . $d['etudiant_nom']); ?></h6>
                            <?php if ($d['filiere'] || $d['niveau']) : ?>
                                <p class="text-muted mb-1" style="font-size:.8rem;"><?php echo h(implode(' · ', array_filter([$d['filiere'], $d['niveau']]))); ?></p>
                            <?php endif; ?>
                            <span class="text-primary fw-bold" style="font-size:.75rem;">Consulter et évaluer <i class="bi bi-arrow-right ms-1"></i></span>
                        </div>
                        <span class="badge rounded-pill border <?php echo $st_classes[1]; ?>" style="font-size:.7rem;"><?php echo $st_classes[0]; ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>