<?php 
session_start(); 
$nom    = isset($_SESSION['nom'])    ? $_SESSION['nom']    : '';
$prenom = isset($_SESSION['prenom']) ? $_SESSION['prenom'] : 'Tuteur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Tuteur — CY Stage</title>
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
            <i class="bi bi-mortarboard-fill me-2"></i>
            Tuteur : <?php echo htmlspecialchars($prenom . ' ' . $nom); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container" style="max-width:900px;">

    <div class="nom-page mb-4" style="font-size: 1.8rem; font-weight: 700; color: #1B4F9B;">Tableau de bord Tuteur</div>

    <h3 class="options-title mb-3">
        <i class="bi bi-journal-check me-2" style="color:#1B4F9B;"></i>Mes étudiants en stage
    </h3>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <a href="#"
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-person-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Nom Prénom Étudiant</div>
                    <div class="text-muted" style="font-size:.8rem;">Suivi de stage & rapports</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <div class="col-12 col-md-6">
            <a href="#"
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-person-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Nom Prénom Étudiant</div>
                    <div class="text-muted" style="font-size:.8rem;">Suivi de stage & rapports</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>
    </div>

</div>
</body>
</html>