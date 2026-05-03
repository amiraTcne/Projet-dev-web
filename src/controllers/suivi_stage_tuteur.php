<?php
session_start();

// Vérification Tuteur
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn     = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$stages   = [];
$stage    = null; 
$remarques = [];
$msg_ok   = '';
$msg_err  = '';

/* ID du stage sélectionné dans l'URL */
$id_stage_sel = (int)($_GET['stage'] ?? 0);

/* MAJ du statut d'avancement */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'avancement' && $conn) {
    $num_stage   = (int)($_POST['num_stage'] ?? 0);
    $avancement  = min(100, max(0, (int)($_POST['avancement'] ?? 0)));
    $statut      = $_POST['statut'] ?? '';
    $statuts_ok  = ['en_attente', 'en_cours', 'termine', 'annule'];

    if ($num_stage > 0 && in_array($statut, $statuts_ok)) {
        $upd = mysqli_prepare($conn,
            "UPDATE Stage SET avancement = ?, statut = ? WHERE num_stage = ? AND id_tuteur = ?"
        );
        mysqli_stmt_bind_param($upd, 'isii', $avancement, $statut, $num_stage, $_SESSION['id']);
        if (mysqli_stmt_execute($upd)) {
            $msg_ok = 'Avancement mis à jour avec succès !';
            $id_stage_sel = $num_stage;
        }
        mysqli_stmt_close($upd);
    }
}

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Tous les stages dont le tuteur est responsable */
    $stmt = mysqli_prepare($conn,
        "SELECT s.num_stage, s.titre, s.statut, s.avancement, s.date_debut, s.date_fin,
                ent.nom_entreprise, ent.ville,
                CONCAT(e.prenom, ' ', e.nom) AS nom_etudiant, e.filiere, e.niveau
         FROM Stage s
         JOIN Utilisateur e   ON e.id  = s.id_etudiant
         JOIN Utilisateur ent ON ent.id = s.id_entreprise
         WHERE s.id_tuteur = ?
         ORDER BY s.date_debut DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $stages[] = $row;
    mysqli_stmt_close($stmt);

    /* Charger le détail du stage sélectionné */
    if ($id_stage_sel > 0) {
        foreach ($stages as $s) {
            if ($s['num_stage'] === $id_stage_sel) { $stage = $s; break; }
        }

        if ($stage) {
            /* les remarques du dossier lié */
            $sr = mysqli_prepare($conn,
                "SELECT r.contenu, r.date_creation, u.nom, u.prenom
                 FROM Remarque r
                 JOIN Dossier_Stage d ON d.num_dossier = r.num_dossier
                 JOIN Utilisateur u   ON u.id = r.id_auteur
                 WHERE d.num_stage = ?
                 ORDER BY r.date_creation DESC LIMIT 10"
            );
            mysqli_stmt_bind_param($sr, 'i', $id_stage_sel);
            mysqli_stmt_execute($sr);
            $rr = mysqli_stmt_get_result($sr);
            while ($row = mysqli_fetch_assoc($rr)) $remarques[] = $row;
            mysqli_stmt_close($sr);
        }
    }
    mysqli_close($conn);
}

// Couleurs Bootstrap pour les statuts
$statuts_labels = [
    'en_attente' => ['En attente', 'bg-warning text-dark'],
    'en_cours'   => ['En cours',   'bg-primary text-white'],
    'termine'    => ['Terminé',    'bg-success text-white'],
    'annule'     => ['Annulé',     'bg-danger text-white'],
];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Stage — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06);
            background: #fff;
            padding: 1.5rem;
        }

        .stage-item {
            display: flex; align-items: center; gap: 15px;
            padding: 15px; border: 1px solid #e5e7eb;
            border-radius: 14px; background: #fff;
            text-decoration: none; color: inherit;
            transition: all 0.2s ease; margin-bottom: 10px;
        }
        .stage-item:hover, .stage-item.actif {
            border-color: var(--bleu-clair); box-shadow: 0 4px 12px rgba(27,79,155,.08);
            transform: translateY(-2px);
        }
        .stage-item.actif { background: #f0f4fa; border-color: var(--bleu); }

        .stage-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 800; flex-shrink: 0;
        }

        .section-title {
            font-family: 'Syne', sans-serif; font-size: 0.95rem; text-transform: uppercase;
            letter-spacing: 1px; color: var(--bleu); margin-bottom: 1rem; margin-top: 1.5rem;
        }
        
        /* Personnalisation de la progress bar */
        .progress { height: 8px; border-radius: 10px; background-color: #e5e7eb; }
        .progress-bar { background: linear-gradient(90deg, var(--bleu), var(--bleu-clair)); }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_tuteur.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline">
                <i class="bi bi-person-workspace me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i> <span class="d-none d-sm-inline">Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">

    <!-- En-tête -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_tuteur.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Suivi de Stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Suivez l'avancement des étudiants sous votre tutorat</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- Colonne de gauche : Liste des étudiants -->
        <div class="col-lg-5">
            <div class="card-cy h-100">
                <h5 class="fw-bold mb-4" style="color:var(--bleu); font-family:'Syne',sans-serif;">
                    <i class="bi bi-people me-2"></i> Étudiants assignés <span class="badge bg-secondary rounded-pill ms-2"><?php echo count($stages); ?></span>
                </h5>

                <?php if (empty($stages)) : ?>
                    <div class="text-center p-4 bg-light rounded-4 border border-dashed">
                        <i class="bi bi-inbox text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mb-0 fw-semibold">Aucun étudiant à suivre pour le moment.</p>
                    </div>
                <?php else : ?>
                    <div class="d-flex flex-column">
                        <?php foreach ($stages as $s) :
                            $noms = explode(' ', $s['nom_etudiant']);
                            $initiales_etu = strtoupper(mb_substr($noms[0], 0, 1) . mb_substr($noms[1] ?? '?', 0, 1));
                            $st_data = $statuts_labels[$s['statut']] ?? [$s['statut'], 'bg-secondary text-white'];
                        ?>
                            <a href="suivi_stage_tuteur.php?stage=<?php echo (int)$s['num_stage']; ?>" class="stage-item <?php echo $id_stage_sel === $s['num_stage'] ? 'actif' : ''; ?>">
                                <div class="stage-avatar"><?php echo h($initiales_etu); ?></div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo h($s['nom_etudiant']); ?></h6>
                                        <span class="badge rounded-pill <?php echo $st_data[1]; ?>" style="font-size:.65rem;"><?php echo $st_data[0]; ?></span>
                                    </div>
                                    <p class="text-muted mb-2 text-truncate" style="font-size:.75rem; max-width: 200px;"><?php echo h($s['titre']); ?></p>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo (int)$s['avancement']; ?>%;" aria-valuenow="<?php echo (int)$s['avancement']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Colonne de droite : Détails du stage sélectionné -->
        <div class="col-lg-7">
            <?php if (!$stage) : ?>
                <div class="card-cy h-100 d-flex flex-column align-items-center justify-content-center text-center p-5 bg-light" style="border-style: dashed;">
                    <i class="bi bi-hand-index-thumb text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                    <h5 class="fw-bold text-muted" style="font-family:'Syne',sans-serif;">Sélectionnez un étudiant</h5>
                    <p class="text-muted mb-0 small">Cliquez sur un étudiant dans la liste pour voir et modifier les détails de son stage.</p>
                </div>
            <?php else : ?>
                <div class="card-cy">
                    <h4 class="fw-bold border-bottom pb-3" style="font-family:'Syne',sans-serif; color:#111827;">
                        <i class="bi bi-person-lines-fill text-muted me-2"></i> <?php echo h($stage['nom_etudiant']); ?>
                    </h4>

                    <!-- Détails du stage -->
                    <div class="bg-light rounded-4 p-3 my-4 border">
                        <div class="row g-3" style="font-size: .85rem;">
                            <div class="col-12 d-flex"><strong class="text-muted" style="width:100px;">Stage :</strong> <span class="fw-bold"><?php echo h($stage['titre']); ?></span></div>
                            <div class="col-12 d-flex"><strong class="text-muted" style="width:100px;">Entreprise :</strong> <span class="fw-bold"><?php echo h($stage['nom_entreprise']); ?><?php if ($stage['ville']) echo ' · ' . h($stage['ville']); ?></span></div>
                            <div class="col-12 d-flex"><strong class="text-muted" style="width:100px;">Filière :</strong> <span class="fw-bold"><?php echo h($stage['filiere'] . ' ' . $stage['niveau']); ?></span></div>
                            <?php if ($stage['date_debut']) : ?>
                                <div class="col-6 d-flex"><strong class="text-muted" style="width:100px;">Début :</strong> <span class="fw-bold"><?php echo date('d/m/Y', strtotime($stage['date_debut'])); ?></span></div>
                            <?php endif; ?>
                            <?php if ($stage['date_fin']) : ?>
                                <div class="col-6 d-flex"><strong class="text-muted" style="width:100px;">Fin :</strong> <span class="fw-bold"><?php echo date('d/m/Y', strtotime($stage['date_fin'])); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Formulaire de mise à jour -->
                    <div class="section-title mt-0"><i class="bi bi-sliders"></i> Mettre à jour l'avancement</div>
                    <form method="POST" action="suivi_stage_tuteur.php?stage=<?php echo $stage['num_stage']; ?>" class="bg-white border rounded-4 p-4 mb-4">
                        <input type="hidden" name="action" value="avancement">
                        <input type="hidden" name="num_stage" value="<?php echo $stage['num_stage']; ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between">
                                Progression de l'étudiant 
                                <span class="badge bg-primary rounded-pill"><span id="val-av"><?php echo (int)$stage['avancement']; ?></span>%</span>
                            </label>
                            <input type="range" class="form-range" name="avancement" min="0" max="100" step="5" value="<?php echo (int)$stage['avancement']; ?>" oninput="document.getElementById('val-av').textContent = this.value">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Statut actuel du stage</label>
                            <select name="statut" class="form-select rounded-3">
                                <?php foreach ($statuts_labels as $key => $data) : ?>
                                    <option value="<?php echo $key; ?>" <?php echo $stage['statut'] === $key ? 'selected' : ''; ?>>
                                        <?php echo $data[0]; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4" style="background:var(--bleu); border:none;">
                            <i class="bi bi-save me-1"></i> Enregistrer les modifications
                        </button>
                    </form>

                    <!-- Échanges récents -->
                    <?php if (!empty($remarques)) : ?>
                        <div class="section-title"><i class="bi bi-chat-text"></i> Historique des échanges</div>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($remarques as $rem) : ?>
                                <div class="p-3 bg-light rounded-4 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-dark" style="font-size:.85rem;"><i class="bi bi-person-circle me-1"></i> <?php echo h($rem['prenom'] . ' ' . $rem['nom']); ?></span>
                                        <span class="text-muted" style="font-size:.7rem;"><i class="bi bi-clock me-1"></i> <?php echo date('d/m/Y', strtotime($rem['date_creation'])); ?></span>
                                    </div>
                                    <p class="mb-0 text-secondary" style="font-size:.85rem; line-height:1.5;">
                                        <?php echo nl2br(h($rem['contenu'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>