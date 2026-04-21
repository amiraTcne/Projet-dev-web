<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Étudiant — CY Stage</title>
    <!-- CSS existant conservé -->
    <link rel="stylesheet" href="../../public/assets/css/style_acceuil.css">
    <!-- Bootstrap 5 + Alpine.js -->
    <?php include '../../public/frameworks.php'; ?>
</head>
<body>

<!-- ═══ NAVBAR Bootstrap (nouveau) ═══ -->
<nav class="navbar navbar-expand-lg shadow-sm mb-4"
     style="background: linear-gradient(135deg, #1B4F9B, #2563c7);">
    <div class="container-fluid px-4">

        <!-- Logo -->
        <a class="navbar-brand" href="#">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>

        <!-- Bouton hamburger mobile -->
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navEtudiant">
            <span class="navbar-toggler-icon" style="filter:invert(1);"></span>
        </button>

        <div class="collapse navbar-collapse" id="navEtudiant">
            <!-- Nom de l'étudiant centré -->
            <span class="navbar-text mx-auto fw-bold text-white">
                <i class="bi bi-mortarboard-fill me-2"></i>
                <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <!-- Déconnexion -->
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm ms-auto">
                <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
            </a>
        </div>
    </div>
</nav>

<!-- ═══ CONTENU PRINCIPAL ═══ -->
<div class="container" style="max-width:900px;">

    <!-- Titre section -->
    <h3 class="options-title mb-3">
        <i class="bi bi-grid-fill me-2" style="color:#1B4F9B;"></i>Options
    </h3>

    <!-- Grille de navigation Bootstrap (remplace .nav-grid custom) -->
    <div class="row g-3">

        <!-- Profil -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="profil_etudiant.php" class="nav text-decoration-none d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm"
               style="transition: transform .15s, box-shadow .15s;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(27,79,155,.15)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <span class="icon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#1B4F9B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                <h4 class="mb-0 flex-grow-1 fs-6 fw-semibold text-dark">Profil</h4>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <!-- Offres de stage -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="offres_etudiant.php" class="nav text-decoration-none d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm"
               style="transition: transform .15s, box-shadow .15s;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(27,79,155,.15)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <span class="icon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#1B4F9B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2"/>
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                    </svg>
                </span>
                <h4 class="mb-0 flex-grow-1 fs-6 fw-semibold text-dark">Offres de stage</h4>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <!-- Dossier -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="dossier_etudiant.php" class="nav text-decoration-none d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm"
               style="transition: transform .15s, box-shadow .15s;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(27,79,155,.15)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <span class="icon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#1B4F9B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                        <path d="M14 2v6h6"/>
                    </svg>
                </span>
                <h4 class="mb-0 flex-grow-1 fs-6 fw-semibold text-dark">Dossier</h4>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <!-- Avancement -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="avancement_etudiant.php" class="nav text-decoration-none d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm"
               style="transition: transform .15s, box-shadow .15s;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(27,79,155,.15)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <span class="icon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#1B4F9B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 5h8"/><path d="M13 12h8"/><path d="M13 19h8"/>
                        <path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/>
                    </svg>
                </span>
                <h4 class="mb-0 flex-grow-1 fs-6 fw-semibold text-dark">Avancement Stage</h4>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

        <!-- Favoris -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="favoris_etudiant.php" class="nav text-decoration-none d-flex align-items-center gap-3 p-3 rounded-3 border bg-white shadow-sm"
               style="transition: transform .15s, box-shadow .15s;"
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(27,79,155,.15)'"
               onmouseout="this.style.transform='';this.style.boxShadow=''">
                <span class="icon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#1B4F9B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </span>
                <h4 class="mb-0 flex-grow-1 fs-6 fw-semibold text-dark">Favoris</h4>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        </div>

    </div><!-- /row -->
</div><!-- /container -->

</body>
</html>