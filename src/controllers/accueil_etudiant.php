<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

// Fonction utilitaire pour sécuriser l'affichage HTML
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Étudiant — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
            --bs-primary: #1B4F9B;
            --bs-primary-rgb: 27,79,155;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        /* Navbar */
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        /* Dashboard Cards */
        .dashboard-card {
            display: flex; align-items: center; gap: 1rem;
            padding: 1.5rem; border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px; background: #fff;
            text-decoration: none; color: inherit;
            transition: all 0.25s ease-in-out;
            box-shadow: 0 4px 18px rgba(27,79,155,.04);
            height: 100%;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(27,79,155,.12);
            border-color: var(--bleu-clair);
            color: inherit;
        }
        .dashboard-card .icon-box {
            width: 56px; height: 56px; border-radius: 14px;
            background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; flex-shrink: 0;
            transition: background 0.25s, color 0.25s;
        }
        .dashboard-card:hover .icon-box {
            background: var(--bleu); color: #fff;
        }
        .dashboard-card i.bi-chevron-right {
            transition: transform 0.2s ease;
        }
        .dashboard-card:hover i.bi-chevron-right {
            transform: translateX(4px); color: var(--bleu) !important;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-5">
    <div class="container-fluid px-4">

        <a class="navbar-brand" href="#">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navEtudiant">
            <span class="navbar-toggler-icon" style="filter:invert(1);"></span>
        </button>

        <div class="collapse navbar-collapse" id="navEtudiant">
            <span class="navbar-text mx-auto fw-bold text-white">
                <i class="bi bi-mortarboard-fill me-2"></i>
                <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill fw-semibold ms-lg-auto mt-3 mt-lg-0">
                <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
            </a>
        </div>
    </div>
</nav>

<!-- CONTENU PRINCIPAL -->
<div class="container mb-5" style="max-width:950px;">

    <!-- En-tête -->
    <div class="text-center text-md-start mb-5">
        <h1 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif; font-size: 2.2rem;">
            Tableau de bord Étudiant
        </h1>
        <p class="text-muted" style="font-size:.95rem;">
            Bienvenue sur votre espace. Que souhaitez-vous faire aujourd'hui ?
        </p>
    </div>

    <!-- Grille d'options -->
    <div class="row g-4">

        <!-- Profil -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="profil_etudiant.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-person-vcard"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827; font-size:1.1rem;">Profil</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Gérez vos informations</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Offres de stage -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="offres_etudiant.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-search"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827; font-size:1.1rem;">Offres de stage</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Parcourez les annonces</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Dossier -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="dossier_etudiant.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827; font-size:1.1rem;">Mon Dossier</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">CV, lettres, documents</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Avancement -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="avancement_etudiant.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827; font-size:1.1rem;">Mon Stage</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Suivi et évaluation</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Candidatures déposées -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="candidatures_etudiant.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-send-check"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827; font-size:1.1rem;">Candidatures</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Suivez vos postulations</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Favoris -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="favoris_etudiant.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-heart"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827; font-size:1.1rem;">Offres Favorites</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Vos annonces sauvegardées</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Notifications -->
        <div class="col-12 col-md-6 col-lg-4">
            <a href="notifications_etudiant.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-bell"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827; font-size:1.1rem;">Notifications</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Alertes et messages</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>