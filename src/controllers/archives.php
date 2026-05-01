<?php
/* on démarre la session */
session_start();

/* on vérifie que c'est bien un admin connecté */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on se connecte à la base de données */
$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';

/* on traite le formulaire quand l'admin soumet un dossier à archiver */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $num_dossier      = (int)($_POST['num_dossier'] ?? 0);
    $motif            = trim($_POST['motif'] ?? '');
    $annee_academique = trim($_POST['annee_academique'] ?? '');

    if ($num_dossier <= 0) {
        $msg_err = 'Sélectionnez un dossier à archiver.';
    } elseif (empty($annee_academique)) {
        $msg_err = "L'année académique est obligatoire.";
    } else {
        /* on vérifie que ce dossier n'est pas déjà archivé pour éviter les doublons */
        $chk = mysqli_prepare($conn, "SELECT 1 FROM Archive WHERE num_dossier = ?");
        mysqli_stmt_bind_param($chk, 'i', $num_dossier);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $msg_err = 'Ce dossier est déjà archivé.';
        } else {
            /* on insère l'archive dans la table Archive */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Archive (num_dossier, id_user_admin, motif, annee_academique)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'iiss',
                $num_dossier,      /* le dossier qu'on archive */
                $_SESSION['id'],   /* l'admin qui effectue l'archivage */
                $motif,            /* le motif (optionnel) */
                $annee_academique  /* ex: 2025-2026 */
            );
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Dossier archivé avec succès !';
            } else {
                $msg_err = "Erreur lors de l'archivage.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($chk);
    }
}

/* on prépare les listes et les stats qu'on va afficher */
$dossiers_disponibles = []; /* les dossiers validés qu'on peut encore archiver */
$archives             = []; /* la liste de toutes les archives existantes */
$nb_total             = 0;  /* nombre total d'archives */
$nb_annee             = 0;  /* nombre d'archives pour l'année en cours */
$annee_courante       = date('Y') . '-' . (date('Y') + 1); /* ex: 2025-2026 */

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* on récupère les dossiers validés qui ne sont pas encore dans la table Archive */
    $q1 = mysqli_query($conn,
        "SELECT d.num_dossier,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant,
                s.titre AS titre_stage,
                ent.nom_entreprise,
                u.filiere,
                u.annee_promo
         FROM Dossier_Stage d
         JOIN Stage s         ON s.num_stage   = d.num_stage
         JOIN Utilisateur u   ON u.id           = d.id_etudiant
         JOIN Utilisateur ent ON ent.id          = s.id_entreprise
         WHERE d.statut = 'valide'
           AND d.num_dossier NOT IN (SELECT num_dossier FROM Archive)
         ORDER BY d.date_modification DESC"
    );
    while ($row = mysqli_fetch_assoc($q1)) {
        $dossiers_disponibles[] = $row;
    }

    /* on récupère toutes les archives avec les infos de l'étudiant, du stage et de l'admin */
    $q2 = mysqli_query($conn,
        "SELECT a.id_archive,
                a.date_archivage,
                a.motif,
                a.annee_academique,
                CONCAT(u.prenom, ' ', u.nom)    AS etudiant,
                u.filiere,
                s.titre                          AS titre_stage,
                ent.nom_entreprise,
                CONCAT(adm.prenom, ' ', adm.nom) AS admin_nom
         FROM Archive a
         JOIN Dossier_Stage d  ON d.num_dossier = a.num_dossier
         JOIN Stage s          ON s.num_stage   = d.num_stage
         JOIN Utilisateur u    ON u.id           = d.id_etudiant
         JOIN Utilisateur ent  ON ent.id          = s.id_entreprise
         JOIN Utilisateur adm  ON adm.id          = a.id_user_admin
         ORDER BY a.date_archivage DESC"
    );
    while ($row = mysqli_fetch_assoc($q2)) {
        $archives[] = $row;
    }

    /* on calcule les deux stats */
    $nb_total = count($archives);
    foreach ($archives as $a) {
        if ($a['annee_academique'] === $annee_courante) {
            $nb_annee++;
        }
    }

    mysqli_close($conn);
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives — CY Stage</title>
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
        
        .stat-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 1.5rem; text-align: center; box-shadow: 0 4px 12px rgba(27,79,155,.05);
        }
        .stat-card .display-4 { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--bleu); }

        .archive-item {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 1.2rem; margin-bottom: 0.8rem; box-shadow: 0 2px 8px rgba(0,0,0,.03);
            transition: transform 0.2s;
        }
        .archive-item:hover { transform: translateY(-2px); border-color: var(--bleu-clair); }
        
        .section-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; color: var(--bleu); font-weight: 700; margin-bottom: 1rem; border-bottom: 2px solid var(--bleu); display: inline-block; padding-bottom: 0.3rem;}
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_admin.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-shield-lock-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_admin.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div class="flex-grow-1">
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Archives des Stages</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Archivage des dossiers validés · Année <?php echo h($annee_courante); ?></p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="display-4 mb-1"><?php echo $nb_total; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.8rem; letter-spacing:1px;">Dossier(s) archivé(s) au total</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="display-4 mb-1 text-primary"><?php echo $nb_annee; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.8rem; letter-spacing:1px;">Cette année académique</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- Formulaire d'archivage -->
        <div class="col-lg-5">
            <h2 class="section-title">Archiver un dossier</h2>
            <div class="card-cy mt-2">
                <?php if (empty($dossiers_disponibles)) : ?>
                    <div class="text-center p-4">
                        <i class="bi bi-archive text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mb-0 fw-semibold">Aucun dossier validé en attente d'archivage.</p>
                    </div>
                <?php else : ?>
                    <form method="POST" action="archives.php">
                        <div class="mb-3">
                            <label for="num_dossier" class="form-label fw-bold text-dark">Dossier à archiver</label>
                            <select class="form-select bg-light" name="num_dossier" id="num_dossier" required>
                                <option value="">— Sélectionner un dossier —</option>
                                <?php foreach ($dossiers_disponibles as $d) : ?>
                                    <option value="<?php echo (int)$d['num_dossier']; ?>">
                                        <?php echo h($d['etudiant'] . ' · ' . $d['titre_stage']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="annee_academique" class="form-label fw-bold text-dark">Année académique</label>
                            <input type="text" class="form-control bg-light" name="annee_academique" id="annee_academique" value="<?php echo h($annee_courante); ?>" pattern="\d{4}-\d{4}" title="Format attendu : 2025-2026" required>
                        </div>

                        <div class="mb-4">
                            <label for="motif" class="form-label fw-bold text-dark">Motif <span class="text-muted fw-normal">(optionnel)</span></label>
                            <input type="text" class="form-control bg-light" name="motif" id="motif" placeholder="Ex : Fin de stage validé par le jury">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm" style="background:var(--bleu); border:none;">
                            <i class="bi bi-archive-fill me-1"></i> Archiver le dossier
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Liste des archives existantes -->
        <div class="col-lg-7">
            <h2 class="section-title">Archives existantes</h2>
            <div class="mt-2">
                <?php if (empty($archives)) : ?>
                    <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
                        <i class="bi bi-folder-x text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
                        <p class="text-muted mb-0">Aucune archive pour le moment.</p>
                    </div>
                <?php else : ?>
                    <div class="d-flex flex-column">
                        <?php foreach ($archives as $a) : ?>
                            <div class="archive-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;"><?php echo h($a['etudiant']); ?></h6>
                                        <p class="text-primary fw-semibold mb-0" style="font-size:.8rem;"><?php echo h($a['titre_stage']); ?> · <span class="text-muted"><?php echo h($a['nom_entreprise']); ?></span></p>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"><?php echo h($a['annee_academique']); ?></span>
                                </div>
                                
                                <div class="d-flex flex-wrap gap-3 text-muted mt-3" style="font-size:.75rem;">
                                    <?php if ($a['filiere']) : ?>
                                        <span><i class="bi bi-mortarboard-fill"></i> <?php echo h($a['filiere']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="bi bi-calendar-event"></i> Archivé le <?php echo date('d/m/Y', strtotime($a['date_archivage'])); ?></span>
                                    <span><i class="bi bi-person-fill-lock"></i> Par <?php echo h($a['admin_nom']); ?></span>
                                </div>

                                <?php if (!empty($a['motif'])) : ?>
                                    <div class="mt-2 pt-2 border-top text-secondary fst-italic" style="font-size:.75rem;">
                                        "<?php echo h($a['motif']); ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>