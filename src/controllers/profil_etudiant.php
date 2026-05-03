<?php
/* on démarre la session */
session_start();

/* on vérifie que c'est bien un étudiant connecté */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on se connecte à la base de données */
$conn       = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$etudiant   = null;
$nb_favoris = 0;
$nb_stages  = 0;

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* on récupère toutes les infos du profil de l'étudiant */
    $stmt = mysqli_prepare($conn,
        "SELECT nom, prenom, email, filiere, niveau, annee_promo, date_inscription
         FROM Utilisateur WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $etudiant = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    /* on compte les offres en favoris pour les afficher dans les stats */
    $sf = mysqli_prepare($conn, "SELECT COUNT(*) FROM Favori WHERE id_user = ?");
    mysqli_stmt_bind_param($sf, 'i', $_SESSION['id']);
    mysqli_stmt_execute($sf);
    mysqli_stmt_bind_result($sf, $nb_favoris);
    mysqli_stmt_fetch($sf);
    mysqli_stmt_close($sf);

    /* on compte aussi les candidatures envoyées */
    $sc = mysqli_prepare($conn, "SELECT COUNT(*) FROM Stage WHERE id_etudiant = ?");
    mysqli_stmt_bind_param($sc, 'i', $_SESSION['id']);
    mysqli_stmt_execute($sc);
    mysqli_stmt_bind_result($sc, $nb_stages);
    mysqli_stmt_fetch($sc);
    mysqli_stmt_close($sc);

    mysqli_close($conn);
}

/* on construit les initiales pour l'avatar (ex: Jean Dupont donne JD) */
$initiales = strtoupper(
    mb_substr($etudiant['prenom'] ?? '?', 0, 1) .
    mb_substr($etudiant['nom']    ?? '?', 0, 1)
);

// Fonction utilitaire pour sécuriser l'affichage HTML
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
            --bs-primary: #1B4F9B;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        /* Navbar */
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        /* Cards */
        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.08);
            background: #fff;
        }

        /* Avatar */
        .avatar-profil {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800;
            margin: 0 auto 15px; box-shadow: 0 4px 16px rgba(27, 79, 155, 0.25);
        }

        /* Stats Blocks */
        .stat-box {
            background: #fbfdff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
        }
        .stat-number {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--bleu);
            line-height: 1;
            margin-bottom: 5px;
        }

        /* Icons List */
        .icon-box {
            width: 38px; height: 38px; border-radius: 10px;
            background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .info-row {
            display: flex; align-items: center; gap: 15px;
            padding: 12px 0; border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child { border-bottom: none; }
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
                <?php echo h($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i>
                <span class="d-none d-sm-inline">Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:800px;">

    <!-- En-tête page -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_etudiant.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Mon Profil</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Consultez vos informations personnelles</p>
        </div>
    </div>

    <!-- Identité principale -->
    <div class="card-cy p-4 text-center mb-4">
        <div class="avatar-profil"><?php echo h($initiales); ?></div>
        <h3 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;">
            <?php echo h($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>
        </h3>
        <div class="mb-2">
            <span class="badge rounded-pill" style="background-color: var(--bleu); font-size:.75rem;">
                <i class="bi bi-mortarboard-fill me-1"></i> Étudiant
            </span>
        </div>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Inscrit le <?php echo date('d/m/Y', strtotime($etudiant['date_inscription'])); ?>
        </p>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="stat-box h-100">
                <div class="stat-number"><?php echo (int)$nb_favoris; ?></div>
                <div class="text-muted fw-semibold" style="font-size:.85rem;">Offre<?php echo $nb_favoris > 1 ? 's' : ''; ?> en favoris</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-box h-100">
                <div class="stat-number"><?php echo (int)$nb_stages; ?></div>
                <div class="text-muted fw-semibold" style="font-size:.85rem;">Candidature<?php echo $nb_stages > 1 ? 's' : ''; ?> envoyée<?php echo $nb_stages > 1 ? 's' : ''; ?></div>
            </div>
        </div>
    </div>

    <!-- Informations Académiques -->
    <h5 class="fw-bold mb-3 mt-4" style="font-size: .95rem; color: var(--bleu); text-transform: uppercase; letter-spacing: 1px;">Informations académiques</h5>
    <div class="card-cy p-4 mb-4">
        
        <div class="info-row">
            <div class="icon-box"><i class="bi bi-journal-bookmark"></i></div>
            <div>
                <p class="text-muted mb-0" style="font-size:.75rem; font-weight:600; text-transform:uppercase;">Filière</p>
                <p class="mb-0 fw-bold" style="font-size:.95rem; color:#374151;"><?php echo h($etudiant['filiere'] ?? '—'); ?></p>
            </div>
        </div>

        <div class="info-row">
            <div class="icon-box"><i class="bi bi-bar-chart-steps"></i></div>
            <div>
                <p class="text-muted mb-0" style="font-size:.75rem; font-weight:600; text-transform:uppercase;">Niveau</p>
                <p class="mb-0 fw-bold" style="font-size:.95rem; color:#374151;"><?php echo h($etudiant['niveau'] ?? '—'); ?></p>
            </div>
        </div>

        <div class="info-row">
            <div class="icon-box"><i class="bi bi-calendar-event"></i></div>
            <div>
                <p class="text-muted mb-0" style="font-size:.75rem; font-weight:600; text-transform:uppercase;">Promotion</p>
                <p class="mb-0 fw-bold" style="font-size:.95rem; color:#374151;"><?php echo h($etudiant['annee_promo'] ?? '—'); ?></p>
            </div>
        </div>

    </div>

    <!-- Contact -->
    <h5 class="fw-bold mb-3 mt-4" style="font-size: .95rem; color: var(--bleu); text-transform: uppercase; letter-spacing: 1px;">Contact</h5>
    <div class="card-cy p-4 mb-5">
        <div class="info-row border-0">
            <div class="icon-box"><i class="bi bi-envelope"></i></div>
            <div>
                <p class="text-muted mb-0" style="font-size:.75rem; font-weight:600; text-transform:uppercase;">Adresse email</p>
                <p class="mb-0 fw-bold" style="font-size:.95rem; color:#374151;"><?php echo h($etudiant['email'] ?? '—'); ?></p>
            </div>
        </div>
    </div>

    <!-- Déconnexion en bas de page -->
    <div class="text-center mb-5">
        <a href="deconnexion.php" class="btn btn-outline-danger rounded-pill fw-semibold px-4">
            <i class="bi bi-box-arrow-right me-1"></i> Se déconnecter
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>