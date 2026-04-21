<?php 
session_start(); 
// On récupère le nom de l'entreprise, avec une valeur par défaut par sécurité
$nom_entreprise = isset($_SESSION['nom_entreprise']) ? $_SESSION['nom_entreprise'] : 'Entreprise';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Entreprise — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_acceuil.css">
    <?php include '../../public/frameworks.php'; ?>
</head>
<body>

<nav class="navbar shadow-sm mb-4" style="background: linear-gradient(135deg, #1B4F9B, #2563c7);">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="navbar-brand" href="#">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <span class="fw-bold text-white">
            <i class="bi bi-building me-2"></i>
            <?php echo htmlspecialchars($nom_entreprise); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container" style="max-width:900px;">

    <div class="nom-page mb-4 text-center text-sm-start" style="font-size: 1.8rem; font-weight: 700; color: #1B4F9B;">
        Tableau de bord Entreprise
    </div>

    <h3 class="options-title mb-4">
        <i class="bi bi-grid-fill me-2" style="color:#1B4F9B;"></i>Options de gestion
    </h3>

    <div class="row g-4">

        <div class="col-12 col-sm-6">
            <a href="#" 
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-person-badge-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Profil</div>
                    <div class="text-muted" style="font-size:.8rem;">Modifier vos informations</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <div class="col-12 col-sm-6">
            <a href="#" 
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-megaphone-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Offres de stage</div>
                    <div class="text-muted" style="font-size:.8rem;">Publier et gérer vos annonces</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <div class="col-12 col-sm-6">
            <a href="#" 
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-file-earmark-text-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Stages en cours</div>
                    <div class="text-muted" style="font-size:.8rem;">Suivi des stagiaires actuels</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

    </div><div class="mt-5 p-4 rounded-4 bg-light text-center border-dashed">
        <p class="text-muted mb-0 small">
            Besoin d'aide pour recruter ? Contactez le support CY Stage.
        </p>
    </div>

</div></body>
</html>