<?php 
session_start(); 
$nom_entreprise = isset($_SESSION['nom_entreprise']) ? $_SESSION['nom_entreprise'] : 'Entreprise';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Entreprise — CY Stage</title>
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

<!-- Navbar[cite: 5] -->
<nav class="navbar navbar-cy shadow-sm mb-5">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="navbar-brand" href="#">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <span class="fw-bold text-white">
            <i class="bi bi-building me-2"></i>
            <?php echo h($nom_entreprise); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill fw-semibold">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">

    <!-- En-tête -->
    <div class="text-center text-md-start mb-5">
        <h1 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif; font-size: 2.2rem;">
            Tableau de bord Entreprise
        </h1>
        <p class="text-muted" style="font-size:.95rem;">
            Bienvenue sur votre espace. Que souhaitez-vous faire aujourd'hui ?
        </p>
    </div>

    <!-- Grille d'options[cite: 5] -->
    <div class="row g-4">
        
        <!-- Profil -->
        <div class="col-12 col-md-6">
            <a href="profil_entreprise.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-person-vcard"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;">Mon Profil</h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;">Consultez et modifiez vos informations</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Offres de stage -->
        <div class="col-12 col-md-6">
            <a href="offres_entreprisefram.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-megaphone"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;">Offres de stage</h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;">Publier et gérer vos annonces</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Candidatures -->
        <div class="col-12 col-md-6">
            <a href="recrutement_entreprise.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-inbox"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;">Gestion candidatures</h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;">Suivi des candidatures déposées</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

        <!-- Stages en cours -->
        <div class="col-12 col-md-6">
            <a href="stages_en_cours_entreprise.php" class="dashboard-card">
                <div class="icon-box">
                    <i class="bi bi-briefcase"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;">Stages en cours</h5>
                    <p class="text-muted mb-0" style="font-size:.85rem;">Suivi des stagiaires actuels</p>
                </div>
                <i class="bi bi-chevron-right text-muted fs-5"></i>
            </a>
        </div>

    </div>

    <!-- Message d'aide[cite: 5] -->
    <div class="mt-5 p-4 rounded-4 text-center" style="background:#f8fafc; border: 1px dashed #cbd5e1;">
        <i class="bi bi-info-circle text-muted fs-4 mb-2 d-block"></i>
        <p class="text-muted fw-medium mb-0" style="font-size: .9rem;">
            Besoin d'aide pour recruter ? Contactez le support CY Stage.
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>