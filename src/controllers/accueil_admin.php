<?php
session_start();
$prenom = isset($_SESSION['prenom']) ? $_SESSION['prenom'] : 'Admin';
$nom    = isset($_SESSION['nom'])    ? $_SESSION['nom']    : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Administrateur — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_acceuil.css">
    <?php include '../../public/frameworks.php'; ?>
</head>
<body>

<!-- Navbar -->
<nav class="navbar shadow-sm mb-4" style="background: linear-gradient(135deg, #1B4F9B, #2563c7);">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="navbar-brand" href="#">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <span class="fw-bold text-white">
            <i class="bi bi-shield-lock-fill me-2"></i>
            <?php echo htmlspecialchars($prenom . ' ' . $nom); ?> — Admin
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container" style="max-width:900px;">

    <!-- Bandeau titre -->
    <div class="nom-page mb-4">Gestion de la plateforme</div>

    <h3 class="options-title mb-3">
        <i class="bi bi-grid-fill me-2" style="color:#1B4F9B;"></i>Actions
    </h3>

    <div class="row g-3">

        <!-- Gestion Utilisateurs -->
        <div class="col-12 col-sm-6">
            <a href="gestion_espaces.php"
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-people-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Gestion Utilisateurs</div>
                    <div class="text-muted" style="font-size:.8rem;">Voir et gérer les inscrits</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <!-- Gestion des stages -->
        <div class="col-12 col-sm-6">
            <a href="gestion_stages.php"
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-briefcase-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Gestion des Stages</div>
                    <div class="text-muted" style="font-size:.8rem;">Diffuser et rechercher des offres</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <!-- Archives -->
        <div class="col-12 col-sm-6">
            <a href="archives.php"
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <i class="bi bi-archive-fill fs-4" style="color:#1B4F9B;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Archives</div>
                    <div class="text-muted" style="font-size:.8rem;">Dossiers et historiques</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <!-- Notifications -->
        <div class="col-12 col-sm-6">
            <a href="notifications.php"
               class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm text-decoration-none"
               style="transition:.15s"
               onmouseover="this.style.borderColor='#1B4F9B';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='';this.style.transform=''">
                <div class="icon rounded-3 p-2" style="background:#f0f7ff;">
                    <!-- Badge Bootstrap pour simuler des notifs non lues -->
                    <span class="position-relative">
                        <i class="bi bi-bell-fill fs-4" style="color:#1B4F9B;"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              style="font-size:.6rem;">
                            1
                        </span>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">Notifications</div>
                    <div class="text-muted" style="font-size:.8rem;">Alertes & requêtes</div>
                </div>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

    </div><!-- /row -->

    <!-- Toast de confirmation Bootstrap (Alpine.js) -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"
         x-data="{ show: false }" x-init="setTimeout(() => show = false, 3000)">
        <div x-show="show" x-transition class="toast show align-items-center text-bg-primary border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">✓ Action réalisée avec succès</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        @click="show = false"></button>
            </div>
        </div>
    </div>

</div><!-- /container -->

</body>
</html>