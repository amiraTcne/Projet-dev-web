<?php
/* on démarre la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on récupère l'identifiant de l'offre depuis l'URL et on force le type entier
   pour éviter les injections SQL */
$id_offre = (int)($_GET['id'] ?? 0);

/* si l'ID est invalide, on retourne à la liste des offres */
if ($id_offre <= 0) {
    header('Location: offres_etudiant.php');
    exit();
}

/* les variables qui nous permettent de gérer l'état de la page */
$conn         = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$offre        = null;  /* les données de l'offre */
$est_favori   = false; /* est-ce que l'offre est en favori ? */
$deja_postule = false; /* est-ce que l'étudiant a déjà postulé ? */
$msg_ok       = '';    /* message de succès affiché si la candidature est envoyée */
$msg_err      = '';    /* message d'erreur affiché si quelque chose se passe mal */

/* on traite la candidature quand l'étudiant soumet le formulaire */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    /* on vérifie d'abord que l'étudiant n'a pas déjà postulé */
    $chk = mysqli_prepare($conn, "SELECT 1 FROM Stage WHERE id_etudiant = ? AND num_offre = ?");
    mysqli_stmt_bind_param($chk, 'ii', $_SESSION['id'], $id_offre);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);

    if (mysqli_stmt_num_rows($chk) > 0) {
        $msg_err = 'Tu as déjà postulé à cette offre.';
    } else {
        /* on récupère l'id de l'entreprise pour pouvoir créer le Stage correctement */
        $ge = mysqli_prepare($conn, "SELECT id_entreprise, titre FROM Offre_Stage WHERE num_offre = ?");
        mysqli_stmt_bind_param($ge, 'i', $id_offre);
        mysqli_stmt_execute($ge);
        $ent = mysqli_fetch_assoc(mysqli_stmt_get_result($ge));
        mysqli_stmt_close($ge);

        if ($ent) {
            /* on prépare la requête SQL avec des '?' pour la sécurité */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Stage (titre, id_etudiant, id_entreprise, num_offre, statut)
                 VALUES (?, ?, ?, ?, 'en_attente')"
            );

            /* on lie les variables aux '?' */
            mysqli_stmt_bind_param($ins, 'siii',
                $ent['titre'],        /* le titre du stage */
                $_SESSION['id'],      /* l'ID de l'étudiant récupéré depuis la session */
                $ent['id_entreprise'],/* l'ID de l'entreprise */
                $id_offre             /* le numéro de l'offre concernée */
            );

            /* on exécute la requête — le trigger SQL va créer le Dossier_Stage automatiquement */
            if (mysqli_stmt_execute($ins)) {
                $msg_ok       = 'Candidature envoyée !';
                $deja_postule = true;
            } else {
                $msg_err = "Erreur lors de l'envoi.";
            }
            mysqli_stmt_close($ins);
        }
    }
    mysqli_stmt_close($chk);
}

/* on charge les données de l'offre depuis la base */
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT o.num_offre, o.titre, o.mission, o.competences,
                o.filiere_ciblee, o.duree_semaines, o.date_debut,
                u.nom_entreprise, u.secteur, u.ville
         FROM Offre_Stage o
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE o.num_offre = ? AND o.statut = 'ouverte'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_offre);
    mysqli_stmt_execute($stmt);
    $offre = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($offre) {
        /* on vérifie si l'offre est déjà en favori pour colorier le cœur */
        $cf = mysqli_prepare($conn, "SELECT 1 FROM Favori WHERE id_user = ? AND num_offre = ?");
        mysqli_stmt_bind_param($cf, 'ii', $_SESSION['id'], $id_offre);
        mysqli_stmt_execute($cf);
        mysqli_stmt_store_result($cf);
        $est_favori = mysqli_stmt_num_rows($cf) > 0;
        mysqli_stmt_close($cf);

        /* on vérifie aussi si l'étudiant a déjà postulé (utile si la page est chargée sans POST) */
        if (!$deja_postule) {
            $cp = mysqli_prepare($conn, "SELECT 1 FROM Stage WHERE id_etudiant = ? AND num_offre = ?");
            mysqli_stmt_bind_param($cp, 'ii', $_SESSION['id'], $id_offre);
            mysqli_stmt_execute($cp);
            mysqli_stmt_store_result($cp);
            $deja_postule = mysqli_stmt_num_rows($cp) > 0;
            mysqli_stmt_close($cp);
        }
    }
    mysqli_close($conn);
}

/* si l'offre n'existe pas ou n'est plus disponible, on retourne à la liste */
if (!$offre) {
    header('Location: offres_etudiant.php');
    exit();
}

/* on prépare les données d'affichage */
$techs = array_filter(array_map('trim', explode(',', $offre['competences'] ?? '')));
$duree = $offre['duree_semaines'] ? round($offre['duree_semaines'] / 4) . ' mois' : '';

// Fonction utilitaire pour sécuriser l'affichage HTML
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($offre['titre']); ?> — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        /* Navbar */
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        /* Cards */
        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06);
            background: #fff;
            padding: 2rem;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--bleu);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-row {
            display: flex; align-items: center; gap: 15px;
            padding: 12px 0; border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child { border-bottom: none; }
        .icon-box {
            width: 38px; height: 38px; border-radius: 10px;
            background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }

        /* Bouton Favori */
        .btn-coeur {
            width: 44px; height: 44px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #e5e7eb; background: #fff; color: #9ca3af;
            transition: all 0.2s; font-size: 1.3rem;
        }
        .btn-coeur:hover, .btn-coeur.actif { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }

        /* Toast stylisé */
        .toast-cy {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: #111827; color: #fff; padding: 12px 24px; border-radius: 999px;
            font-size: .9rem; font-weight: 600; opacity: 0; transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 1050; display: flex; align-items: center; gap: 8px;
        }
        .toast-cy.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast-cy.ok { background: #1B4F9B; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_etudiant.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline">
                <i class="bi bi-mortarboard-fill me-2"></i>
                <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i>
                <span class="d-none d-sm-inline">Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width: 850px;">
    
    <!-- En-tête -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="offres_etudiant.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div>
                <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Détails de l'offre</h1>
            </div>
        </div>
        
        <!-- Bouton Cœur[cite: 10] -->
        <button class="btn-coeur <?php echo $est_favori ? 'actif' : ''; ?>" id="btn-fav" data-id="<?php echo $id_offre; ?>" aria-label="Favori">
            <i class="bi <?php echo $est_favori ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
        </button>
    </div>

    <!-- Alertes[cite: 10] -->
    <?php if ($msg_ok): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i> <strong><?php echo h($msg_ok); ?></strong>
        </div>
    <?php endif; ?>
    
    <?php if ($msg_err): ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i> <strong><?php echo h($msg_err); ?></strong>
        </div>
    <?php endif; ?>

    <div class="card-cy">
        <!-- Bandeau Titre et Entreprise[cite: 10] -->
        <div class="border-bottom pb-4 mb-4">
            <h2 class="fw-bold mb-2" style="font-family:'Syne',sans-serif; color:#111827;"><?php echo h($offre['titre']); ?></h2>
            <p class="text-muted fw-semibold mb-3 fs-6">
                <i class="bi bi-building me-1"></i> <?php echo h($offre['nom_entreprise']); ?>
                <?php if ($offre['ville']): ?> · <i class="bi bi-geo-alt mx-1"></i> <?php echo h($offre['ville']); ?><?php endif; ?>
            </p>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($offre['filiere_ciblee']): ?>
                    <span class="badge rounded-pill text-white" style="background-color: var(--bleu); font-size:.75rem;">
                        <i class="bi bi-mortarboard me-1"></i> <?php echo h($offre['filiere_ciblee']); ?>
                    </span>
                <?php endif; ?>
                <?php if ($duree): ?>
                    <span class="badge rounded-pill bg-success text-white" style="font-size:.75rem;">
                        <i class="bi bi-clock me-1"></i> <?php echo $duree; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Informations Rapides[cite: 10] -->
            <div class="col-md-5">
                <div class="section-title"><i class="bi bi-info-circle"></i> Informations</div>
                <div class="bg-light rounded-4 p-3 border">
                    <?php if ($offre['ville']): ?>
                        <div class="info-row">
                            <div class="icon-box" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-geo-alt"></i></div>
                            <div>
                                <p class="text-muted mb-0" style="font-size:.7rem; font-weight:600; text-transform:uppercase;">Localisation</p>
                                <p class="mb-0 fw-bold" style="font-size:.9rem; color:#374151;"><?php echo h($offre['ville']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($offre['duree_semaines']): ?>
                        <div class="info-row">
                            <div class="icon-box" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-calendar3"></i></div>
                            <div>
                                <p class="text-muted mb-0" style="font-size:.7rem; font-weight:600; text-transform:uppercase;">Durée</p>
                                <p class="mb-0 fw-bold" style="font-size:.9rem; color:#374151;"><?php echo $offre['duree_semaines']; ?> semaines (~<?php echo $duree; ?>)</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($offre['date_debut']): ?>
                        <div class="info-row border-0 pb-0">
                            <div class="icon-box" style="width: 32px; height: 32px; font-size: 1rem;"><i class="bi bi-play-circle"></i></div>
                            <div>
                                <p class="text-muted mb-0" style="font-size:.7rem; font-weight:600; text-transform:uppercase;">Début du stage</p>
                                <p class="mb-0 fw-bold" style="font-size:.9rem; color:#374151;"><?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description Mission[cite: 10] -->
            <?php if ($offre['mission']): ?>
            <div class="col-md-7">
                <div class="section-title"><i class="bi bi-card-text"></i> Mission</div>
                <p class="text-secondary" style="font-size:.9rem; line-height:1.7; white-space:pre-line;">
                    <?php echo h($offre['mission']); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Compétences[cite: 10] -->
            <?php if (!empty($techs)): ?>
            <div class="col-12 mt-4 pt-4 border-top">
                <div class="section-title"><i class="bi bi-stars"></i> Compétences recherchées</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php foreach ($techs as $t): ?>
                        <span class="badge rounded-pill bg-light text-dark border px-3 py-2" style="font-size:.8rem;">
                            <?php echo h($t); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Bouton (Postuler)[cite: 10] -->
        <div class="mt-5 text-center text-md-start">
            <?php if ($deja_postule && !$msg_err): ?>
                <button class="btn btn-light border text-success fw-bold rounded-pill px-4 py-2" disabled>
                    <i class="bi bi-check-circle-fill me-2"></i> Candidature déjà envoyée
                </button>
            <?php else: ?>
                <form method="POST" action="detail_offre.php?id=<?php echo $id_offre; ?>">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4 py-2" style="background:var(--bleu); border:none; font-size: 1.05rem;">
                        <i class="bi bi-send me-2"></i> Postuler à cette offre
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Toast Notifications[cite: 10] -->
<div class="toast-cy" id="toast">
    <i class="bi" id="toast-icon"></i>
    <span id="toast-text"></span>
</div>

<script>
    /* Toggle favori asynchrone[cite: 10] */
    document.getElementById('btn-fav').addEventListener('click', async function () {
        var action = this.classList.contains('actif') ? 'remove' : 'add';
        var icon   = this.querySelector('i');

        try {
            var r = await fetch('api_favori.php', {
                method  : 'POST',
                headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
                body    : 'num_offre=' + this.dataset.id + '&action=' + action
            });
            var d = await r.json();

            if (d.success) {
                this.classList.toggle('actif');

                // Switch de l'icône Bootstrap
                if (action === 'add') {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                } else {
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                }

                /* Affichage du toast */
                var t = document.getElementById('toast');
                var tText = document.getElementById('toast-text');
                var tIcon = document.getElementById('toast-icon');

                tText.textContent = action === 'add' ? 'Ajouté à vos favoris' : 'Retiré de vos favoris';
                t.className = 'toast-cy show ' + (action === 'add' ? 'ok' : '');
                tIcon.className = action === 'add' ? 'bi bi-check-circle-fill' : 'bi bi-info-circle-fill';

                setTimeout(function () { t.classList.remove('show'); }, 2500);
            }
        } catch (e) {
            console.error(e);
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>