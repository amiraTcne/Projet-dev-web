<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$tuteur  = null;
$nb_etudiants = 0;
$nb_stages    = 0;

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT nom, prenom, email, specialite, departement, date_inscription
         FROM Utilisateur WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $tuteur = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    /* nombre d'étudiants suivis */
    $se = mysqli_prepare($conn, "SELECT COUNT(DISTINCT id_etudiant) FROM Stage WHERE id_tuteur = ?");
    mysqli_stmt_bind_param($se, 'i', $_SESSION['id']);
    mysqli_stmt_execute($se);
    mysqli_stmt_bind_result($se, $nb_etudiants);
    mysqli_stmt_fetch($se);
    mysqli_stmt_close($se);

    /* nombre de stages en cours */
    $ss = mysqli_prepare($conn, "SELECT COUNT(*) FROM Stage WHERE id_tuteur = ? AND statut = 'en_cours'");
    mysqli_stmt_bind_param($ss, 'i', $_SESSION['id']);
    mysqli_stmt_execute($ss);
    mysqli_stmt_bind_result($ss, $nb_stages);
    mysqli_stmt_fetch($ss);
    mysqli_stmt_close($ss);

    mysqli_close($conn);
}

$initiales = strtoupper(
    mb_substr($tuteur['prenom'] ?? '?', 0, 1) .
    mb_substr($tuteur['nom']    ?? '?', 0, 1)
);

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
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy {
            border: 1px solid rgba(171,186,205,.4); border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem;
        }
        
        .avatar-lg {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 800; font-family: 'Syne', sans-serif;
            margin: 0 auto 15px; box-shadow: 0 4px 16px rgba(27,79,155,.2);
        }

        .stat-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 1.2rem; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,.03);
            height: 100%;
        }
        .stat-card .display-5 { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--bleu); }
        
        .icon-box {
            width: 40px; height: 40px; border-radius: 10px;
            background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-5">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_tuteur.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-workspace me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:700px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_tuteur.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Mon Profil</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Informations personnelles et statistiques</p>
        </div>
    </div>

    <!-- En-tête profil -->
    <div class="card-cy text-center mb-4">
        <div class="avatar-lg"><?php echo h($initiales); ?></div>
        <h3 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;"><?php echo h($tuteur['prenom'] . ' ' . $tuteur['nom']); ?></h3>
        <span class="badge bg-primary rounded-pill px-3 py-2 fw-semibold">Tuteur Pédagogique</span>
        <p class="text-muted mt-3 mb-0" style="font-size:.85rem;"><i class="bi bi-calendar3 me-1"></i> Inscrit le <?php echo date('d/m/Y', strtotime($tuteur['date_inscription'])); ?></p>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="stat-card">
                <div class="display-5 mb-1"><?php echo (int)$nb_etudiants; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.75rem; letter-spacing:1px;">Étudiant<?php echo $nb_etudiants > 1 ? 's' : ''; ?> suivi<?php echo $nb_etudiants > 1 ? 's' : ''; ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-card">
                <div class="display-5 mb-1"><?php echo (int)$nb_stages; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.75rem; letter-spacing:1px;">Stage<?php echo $nb_stages > 1 ? 's' : ''; ?> en cours</div>
            </div>
        </div>
    </div>

    <!-- Informations professionnelles -->
    <h6 class="fw-bold text-muted text-uppercase mb-3 ps-2" style="font-size:.85rem; letter-spacing:1px;">Informations professionnelles</h6>
    <div class="card-cy mb-4 p-0 overflow-hidden">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex align-items-center gap-3 p-3">
                <div class="icon-box"><i class="bi bi-mortarboard"></i></div>
                <div>
                    <p class="text-muted mb-0 fw-semibold" style="font-size:.75rem;">Spécialité</p>
                    <p class="fw-bold mb-0 text-dark"><?php echo h($tuteur['specialite'] ?? '—'); ?></p>
                </div>
            </li>
            <li class="list-group-item d-flex align-items-center gap-3 p-3">
                <div class="icon-box"><i class="bi bi-building"></i></div>
                <div>
                    <p class="text-muted mb-0 fw-semibold" style="font-size:.75rem;">Département</p>
                    <p class="fw-bold mb-0 text-dark"><?php echo h($tuteur['departement'] ?? '—'); ?></p>
                </div>
            </li>
        </ul>
    </div>

    <!-- Contact -->
    <h6 class="fw-bold text-muted text-uppercase mb-3 ps-2" style="font-size:.85rem; letter-spacing:1px;">Contact</h6>
    <div class="card-cy p-0 overflow-hidden">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex align-items-center gap-3 p-3">
                <div class="icon-box"><i class="bi bi-envelope"></i></div>
                <div>
                    <p class="text-muted mb-0 fw-semibold" style="font-size:.75rem;">Adresse email</p>
                    <p class="fw-bold mb-0 text-dark"><?php echo h($tuteur['email'] ?? '—'); ?></p>
                </div>
            </li>
        </ul>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>