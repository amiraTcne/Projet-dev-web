<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Jury') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$num_dossier = (int)($_GET['id'] ?? 0);
if ($num_dossier <= 0) {
    header('Location: accueil_jury.php');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$dossier = null;
$eval    = null;
$msg_ok  = '';
$msg_err = '';

/* ── Traitement du formulaire d'évaluation ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $note        = isset($_POST['note'])        ? (float)$_POST['note']        : null;
    $appreciation = trim($_POST['appreciation'] ?? '');
    $valide      = isset($_POST['valide'])      ? 1 : 0;

    if ($note === null || $note < 0 || $note > 20) {
        $msg_err = 'La note doit être comprise entre 0 et 20.';
    } else {
        /* Vérification : ce jury a-t-il déjà évalué ce dossier ? */
        $chk = mysqli_prepare($conn,
            "SELECT id_eval FROM Evaluation_Jury WHERE num_dossier = ? AND id_jury = ?"
        );
        mysqli_stmt_bind_param($chk, 'ii', $num_dossier, $_SESSION['id']);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $existe = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);

        if ($existe) {
            /* Mise à jour de l'évaluation existante */
            $upd = mysqli_prepare($conn,
                "UPDATE Evaluation_Jury
                 SET note = ?, appreciation = ?, valide = ?, date_eval = NOW()
                 WHERE num_dossier = ? AND id_jury = ?"
            );
            mysqli_stmt_bind_param($upd, 'dsiii', $note, $appreciation, $valide, $num_dossier, $_SESSION['id']);
            if (mysqli_stmt_execute($upd)) {
                $msg_ok = 'Évaluation mise à jour avec succès.';
            } else {
                $msg_err = 'Erreur lors de la mise à jour.';
            }
            mysqli_stmt_close($upd);
        } else {
            /* Nouvelle évaluation */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Evaluation_Jury (note, appreciation, valide, num_dossier, id_jury)
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'dsiii', $note, $appreciation, $valide, $num_dossier, $_SESSION['id']);
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Évaluation enregistrée avec succès.';
            } else {
                $msg_err = 'Erreur lors de l\'enregistrement.';
            }
            mysqli_stmt_close($ins);
        }

        /* Si validé, on met à jour le statut du dossier */
        if ($valide && !$msg_err) {
            $upd_d = mysqli_prepare($conn,
                "UPDATE Dossier_Stage SET statut = 'valide' WHERE num_dossier = ?"
            );
            mysqli_stmt_bind_param($upd_d, 'i', $num_dossier);
            mysqli_stmt_execute($upd_d);
            mysqli_stmt_close($upd_d);
        }
    }
}

/* ── Récupération du dossier complet ── */
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT
            d.num_dossier,
            d.statut            AS statut_dossier,
            d.rapport_url,
            d.resume_url,
            d.fiche_eval_url,
            d.convention_url,
            d.date_creation,
            d.date_modification,

            s.num_stage,
            s.titre             AS titre_stage,
            s.mission           AS mission_stage,
            s.date_debut,
            s.date_fin,
            s.avancement,
            s.statut            AS statut_stage,
            s.duree_semaines    AS duree_stage,

            e.nom               AS etudiant_nom,
            e.prenom            AS etudiant_prenom,
            e.email             AS etudiant_email,
            e.filiere,
            e.niveau,
            e.annee_promo,

            o.num_offre,
            o.titre             AS titre_offre,
            o.mission           AS mission_offre,
            o.competences,
            o.duree_semaines    AS duree_offre,
            o.date_debut        AS debut_offre,

            ent.nom_entreprise,
            ent.secteur,
            ent.ville,
            ent.site_web

         FROM Dossier_Stage d
         JOIN Stage s              ON s.num_stage  = d.num_stage
         JOIN Utilisateur e        ON e.id         = d.id_etudiant
         LEFT JOIN Offre_Stage o   ON o.num_offre  = s.num_offre
         LEFT JOIN Utilisateur ent ON ent.id       = s.id_entreprise
         WHERE d.num_dossier = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $num_dossier);
    mysqli_stmt_execute($stmt);
    $dossier = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    /* Évaluation éventuelle déjà saisie par CE jury */
    if ($dossier) {
        $se = mysqli_prepare($conn,
            "SELECT note, appreciation, valide, date_eval
             FROM Evaluation_Jury WHERE num_dossier = ? AND id_jury = ?"
        );
        mysqli_stmt_bind_param($se, 'ii', $num_dossier, $_SESSION['id']);
        mysqli_stmt_execute($se);
        $eval = mysqli_fetch_assoc(mysqli_stmt_get_result($se));
        mysqli_stmt_close($se);
    }

    mysqli_close($conn);
}

/* Redirection si dossier introuvable */
if (!$dossier) {
    header('Location: accueil_jury.php');
    exit();
}

/* Helpers d'affichage */
$statuts_labels = [
    'incomplet' => ['Incomplet', 'bg-danger text-white'],
    'en_cours'  => ['En cours',  'bg-warning text-dark'],
    'soumis'    => ['Soumis',    'bg-primary text-white'],
    'valide'    => ['Validé ✓',  'bg-success text-white'],
    'rejete'    => ['Refusé',    'bg-danger text-white'],
];
$st_data = $statuts_labels[$dossier['statut_dossier']] ?? [$dossier['statut_dossier'], 'bg-secondary text-white'];

$initiales = strtoupper(
    mb_substr($dossier['etudiant_prenom'] ?? '?', 0, 1) .
    mb_substr($dossier['etudiant_nom']    ?? '?', 0, 1)
);

$docs = [
    'Rapport de stage'   => $dossier['rapport_url'],
    'Résumé de stage'    => $dossier['resume_url'],
    "Fiche d'évaluation" => $dossier['fiche_eval_url'],
    'Convention'         => $dossier['convention_url'],
];

$techs = array_filter(array_map('trim', explode(',', $dossier['competences'] ?? '')));

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier étudiant — CY Stage</title>
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

        .avatar-lg {
            width: 70px; height: 70px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            color: #fff; font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 10px; box-shadow: 0 4px 16px rgba(27,79,155,.2);
        }
        
        .icon-box {
            width: 38px; height: 38px; border-radius: 10px; background: #eef2ff;
            color: var(--bleu); display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_jury.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-badge-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_jury.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Dossier étudiant</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Examen et évaluation du rapport de stage</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- Colonne Informations -->
        <div class="col-lg-7">
            
            <!-- Identité -->
            <div class="card-cy text-center mb-4">
                <div class="avatar-lg"><?php echo h($initiales); ?></div>
                <h3 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;"><?php echo h($dossier['etudiant_prenom'] . ' ' . $dossier['etudiant_nom']); ?></h3>
                <p class="text-muted mb-3" style="font-size:.85rem;"><?php echo h($dossier['etudiant_email']); ?></p>
                <span class="badge rounded-pill <?php echo $st_data[1]; ?> fs-6">Dossier : <?php echo $st_data[0]; ?></span>
            </div>

            <!-- Profil & Stage -->
            <h6 class="fw-bold text-muted text-uppercase mb-3 ps-2" style="font-size:.85rem; letter-spacing:1px;">Profil académique & Stage</h6>
            <div class="card-cy p-0 overflow-hidden mb-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex align-items-center gap-3 p-3">
                        <div class="icon-box"><i class="bi bi-mortarboard"></i></div>
                        <div>
                            <p class="text-muted mb-0 fw-semibold" style="font-size:.75rem;">Filière · Niveau · Promotion</p>
                            <p class="fw-bold mb-0 text-dark"><?php echo h(($dossier['filiere'] ?? '—') . ' · ' . ($dossier['niveau'] ?? '—') . ' · ' . ($dossier['annee_promo'] ?? '—')); ?></p>
                        </div>
                    </li>
                    <li class="list-group-item d-flex align-items-center gap-3 p-3">
                        <div class="icon-box"><i class="bi bi-briefcase"></i></div>
                        <div>
                            <p class="text-muted mb-0 fw-semibold" style="font-size:.75rem;">Poste</p>
                            <p class="fw-bold mb-0 text-dark"><?php echo h($dossier['titre_offre'] ?? $dossier['titre_stage']); ?></p>
                        </div>
                    </li>
                    <?php if ($dossier['nom_entreprise']) : ?>
                    <li class="list-group-item d-flex align-items-center gap-3 p-3">
                        <div class="icon-box"><i class="bi bi-building"></i></div>
                        <div>
                            <p class="text-muted mb-0 fw-semibold" style="font-size:.75rem;">Entreprise<?php echo $dossier['ville'] ? ' · Ville' : ''; ?></p>
                            <p class="fw-bold mb-0 text-dark"><?php echo h($dossier['nom_entreprise']); ?><?php if ($dossier['ville']) : ?> — <?php echo h($dossier['ville']); ?><?php endif; ?></p>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if ($dossier['date_debut'] || $dossier['date_fin']) : ?>
                    <li class="list-group-item d-flex align-items-center gap-3 p-3">
                        <div class="icon-box"><i class="bi bi-calendar-event"></i></div>
                        <div>
                            <p class="text-muted mb-0 fw-semibold" style="font-size:.75rem;">Période</p>
                            <p class="fw-bold mb-0 text-dark">
                                <?php echo h(($dossier['date_debut'] ? date('d/m/Y', strtotime($dossier['date_debut'])) : '—') . ' → ' . ($dossier['date_fin'] ? date('d/m/Y', strtotime($dossier['date_fin'])) : '—')); ?>
                                <?php if ($dossier['duree_stage'] ?? $dossier['duree_offre']) : ?><span class="text-muted fw-normal ms-1">(<?php echo (int)($dossier['duree_stage'] ?? $dossier['duree_offre']); ?> sem.)</span><?php endif; ?>
                            </p>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
                <?php if ($dossier['mission_offre'] || !empty($techs)) : ?>
                    <div class="p-4 bg-light border-top">
                        <?php if ($dossier['mission_offre']) : ?>
                            <h6 class="fw-bold text-dark mb-2" style="font-size:.85rem;">Mission</h6>
                            <p class="text-muted mb-3" style="font-size:.85rem; line-height: 1.5;"><?php echo nl2br(h($dossier['mission_offre'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($techs)) : ?>
                            <h6 class="fw-bold text-dark mb-2" style="font-size:.85rem;">Compétences clés</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($techs as $t) : ?><span class="badge bg-white text-dark border rounded-pill"><?php echo h($t); ?></span><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Colonne Évaluation & Docs -->
        <div class="col-lg-5">
            
            <!-- Documents -->
            <h6 class="fw-bold text-muted text-uppercase mb-3 ps-2" style="font-size:.85rem; letter-spacing:1px;">Documents déposés</h6>
            <div class="card-cy mb-4">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($docs as $label => $url) : $depose = !empty($url); ?>
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded-3 <?php echo $depose ? 'bg-white' : 'bg-light opacity-75'; ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?php echo $depose ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-file-earmark-x text-muted'; ?> fs-4"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size:.85rem;"><?php echo h($label); ?></h6>
                                    <span class="badge <?php echo $depose ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; ?> rounded-pill" style="font-size:.7rem;"><?php echo $depose ? '✓ Déposé' : 'Non déposé'; ?></span>
                                </div>
                            </div>
                            <?php if ($depose) : ?>
                                <a href="/<?php echo h($url); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill fw-bold">Voir</a>
                            <?php else : ?>
                                <button class="btn btn-sm btn-light border text-muted rounded-pill" disabled><i class="bi bi-dash"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Formulaire -->
            <h6 class="fw-bold text-muted text-uppercase mb-3 ps-2" style="font-size:.85rem; letter-spacing:1px;"><?php echo $eval ? 'Modifier mon évaluation' : 'Évaluer ce dossier'; ?></h6>
            <div class="card-cy">
                <form method="POST" action="dossier_jury.php?id=<?php echo $num_dossier; ?>">
                    
                    <div class="mb-3">
                        <label for="note" class="form-label fw-bold text-dark">Note globale /20 <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg bg-light fw-bold text-primary" id="note" name="note" min="0" max="20" step="0.5" required value="<?php echo $eval ? h($eval['note']) : ''; ?>" placeholder="Ex : 14.5">
                    </div>

                    <?php if ($eval) : ?>
                        <p class="text-muted fst-italic mb-3" style="font-size:.75rem;"><i class="bi bi-clock-history me-1"></i> Dernière évaluation : <?php echo date('d/m/Y', strtotime($eval['date_eval'])); ?></p>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="appreciation" class="form-label fw-bold text-dark">Appréciation / Commentaires</label>
                        <textarea class="form-control bg-light" id="appreciation" name="appreciation" rows="4" placeholder="Commentaires sur le dossier, la soutenance..."><?php echo h($eval['appreciation'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="valide" name="valide" value="1" <?php echo ($eval && $eval['valide']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold text-dark ms-2" for="valide">Valider ce stage</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm" style="background:var(--bleu); border:none;">
                        <i class="bi bi-check2-circle me-1"></i> <?php echo $eval ? 'Mettre à jour' : 'Enregistrer l\'évaluation'; ?>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>