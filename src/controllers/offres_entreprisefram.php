<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php');
    exit();
}

$idEntreprise = (int) $_SESSION['id'];

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);

if (!$connect) {
    die("Erreur : connexion impossible à la base de données.");
}

mysqli_set_charset($connect, "utf8mb4");

$message = '';
$erreur = '';
$offres = [];
$nomEntreprise = 'Entreprise';

// Récupération du nom de l'entreprise connectée
$sqlEntreprise = "SELECT nom_entreprise
                  FROM Utilisateur
                  WHERE id = ? AND role_premier = 'Entreprise'";

$stmtEntreprise = mysqli_prepare($connect, $sqlEntreprise);

if ($stmtEntreprise) {
    mysqli_stmt_bind_param($stmtEntreprise, "i", $idEntreprise);
    mysqli_stmt_execute($stmtEntreprise);
    $resultEntreprise = mysqli_stmt_get_result($stmtEntreprise);
    $entreprise = mysqli_fetch_assoc($resultEntreprise);

    if ($entreprise && !empty($entreprise['nom_entreprise'])) {
        $nomEntreprise = $entreprise['nom_entreprise'];
    }

    mysqli_stmt_close($stmtEntreprise);
}

if (isset($_GET['message']) && $_GET['message'] === 'offre_supprimee') {
    $message = "L'offre a bien été supprimée.";
}

// Ajout d'une offre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_offre'])) {
    $titre = trim($_POST['titre'] ?? '');
    $duree = trim($_POST['duree'] ?? '');
    $date_debut = trim($_POST['date_debut'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $profil = trim($_POST['profil'] ?? '');
    $competences = trim($_POST['competences'] ?? '');

    if ($titre === '' || $duree === '' || $description === '') {
        $erreur = "Veuillez remplir les champs obligatoires : titre, durée et description.";
    } elseif (!ctype_digit($duree) || (int)$duree <= 0) {
        $erreur = "La durée doit être un nombre entier positif.";
    } else {
        $sqlInsert = "INSERT INTO Offre_Stage
                      (titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut, id_entreprise)
                      VALUES (?, ?, ?, ?, ?, ?, 'ouverte', ?)";

        $stmtInsert = mysqli_prepare($connect, $sqlInsert);

        if ($stmtInsert) {
            $dateSql = ($date_debut !== '') ? $date_debut : null;
            $dureeInt = (int) $duree;

            mysqli_stmt_bind_param(
                $stmtInsert,
                "ssssisi",
                $titre,
                $description,
                $competences,
                $profil,
                $dureeInt,
                $dateSql,
                $idEntreprise
            );

            if (mysqli_stmt_execute($stmtInsert)) {
                $message = "L'offre de stage a bien été publiée.";
            } else {
                $erreur = "Erreur lors de l'ajout de l'offre.";
            }

            mysqli_stmt_close($stmtInsert);
        } else {
            $erreur = "Erreur dans la préparation de la requête.";
        }
    }
}

$sqlOffres = "SELECT num_offre, titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, date_publication, statut
              FROM Offre_Stage
              WHERE id_entreprise = ?
              ORDER BY date_publication DESC, num_offre DESC";

$stmtOffres = mysqli_prepare($connect, $sqlOffres);

if ($stmtOffres) {
    mysqli_stmt_bind_param($stmtOffres, "i", $idEntreprise);
    mysqli_stmt_execute($stmtOffres);
    $resultOffres = mysqli_stmt_get_result($stmtOffres);

    while ($row = mysqli_fetch_assoc($resultOffres)) {
        $offres[] = $row;
    }

    mysqli_stmt_close($stmtOffres);
}

// Fonction utilitaire
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de stage — CY Stage</title>
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

        /* Cards */
        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.08);
            background: #fff;
        }

        /* Cartes d'offres (liste) */
        .offer-card {
            display: block; text-decoration: none; color: inherit;
            padding: 20px; border: 1px solid #e5e7eb;
            border-radius: 12px; background: #fbfdff;
            transition: all 0.2s ease-in-out;
        }
        .offer-card:hover {
            background: #f0f4fa; border-color: var(--bleu-clair);
            transform: translateY(-3px); color: inherit;
            box-shadow: 0 4px 12px rgba(27,79,155,.08);
        }

        .badge-statut {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 10px; border-radius: 999px;
            background: #eef2ff; color: var(--bleu);
            border: 1px solid #c7d2fe; font-size: .76rem; font-weight: 700; text-transform: capitalize;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--bleu-clair);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 199, 0.15);
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: .9rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="navbar-brand" href="accueil_entreprise.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <span class="fw-bold text-white">
            <i class="bi bi-building me-2"></i>
            <?php echo h($nomEntreprise); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container mb-5" style="max-width:1100px;">

    <!-- En-tête page -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_entreprise.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Offres de stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Gérez vos offres existantes et publiez-en de nouvelles</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($message): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo h($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo h($erreur); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Colonne de gauche : Liste des offres -->
        <div class="col-lg-7">
            <div class="card-cy p-4 h-100">
                <h5 class="fw-bold mb-4" style="color:var(--bleu); font-family:'Syne',sans-serif;">
                    <i class="bi bi-list-ul me-2"></i> Offres publiées
                </h5>

                <?php if (empty($offres)): ?>
                    <div class="p-5 text-center bg-light rounded-4 border border-dashed">
                        <i class="bi bi-file-earmark-x fs-1 text-muted opacity-50 mb-3 d-block"></i>
                        <p class="text-muted mb-0 fw-semibold">Vous n'avez publié aucune offre pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($offres as $offre): ?>
                            <a href="detail_offre_entreprise.php?id=<?php echo (int) $offre['num_offre']; ?>" class="offer-card">
                                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                    <h6 class="fw-bold mb-0" style="font-family:'Syne',sans-serif; color:var(--bleu); font-size:1.1rem;">
                                        <?php echo h($offre['titre']); ?>
                                    </h6>
                                    <span class="badge-statut">
                                        <?php echo h($offre['statut']); ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted mb-3" style="font-size: .88rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo h($offre['mission']); ?>
                                </p>

                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge rounded-pill bg-light text-dark border fw-medium" style="font-size:.76rem;">
                                        <i class="bi bi-clock me-1"></i> <?php echo h($offre['duree_semaines']); ?> semaines
                                    </span>
                                    <?php if (!empty($offre['date_debut'])): ?>
                                    <span class="badge rounded-pill bg-light text-dark border fw-medium" style="font-size:.76rem;">
                                        <i class="bi bi-calendar-event me-1"></i> Début : <?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($offre['filiere_ciblee'])): ?>
                                    <span class="badge rounded-pill bg-light text-dark border fw-medium" style="font-size:.76rem;">
                                        <i class="bi bi-mortarboard me-1"></i> <?php echo h($offre['filiere_ciblee']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Colonne de droite : Formulaire d'ajout -->
        <div class="col-lg-5">
            <div class="card-cy p-4">
                <h5 class="fw-bold mb-4" style="color:var(--bleu); font-family:'Syne',sans-serif;">
                    <i class="bi bi-plus-circle me-2"></i> Ajouter une offre
                </h5>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre du stage <span class="text-danger">*</span></label>
                        <input type="text" id="titre" name="titre" class="form-control" placeholder="Ex: Développeur Web Fullstack" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duree" class="form-label">Durée (semaines) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="duree" name="duree" class="form-control" placeholder="Ex: 12" min="1" required>
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-clock"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" id="date_debut" name="date_debut" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Mission / Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Décrivez les missions confiées au stagiaire..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="profil" class="form-label">Profil recherché</label>
                        <textarea id="profil" name="profil" class="form-control" rows="2" placeholder="Ex: Étudiant en Master 1 Informatique..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="competences" class="form-label">Compétences clés</label>
                        <input type="text" id="competences" name="competences" class="form-control" placeholder="Ex: PHP, JavaScript, Gestion de projet...">
                    </div>

                    <button type="submit" name="ajouter_offre" class="btn btn-primary w-100 rounded-pill fw-bold" style="background:var(--bleu); border-color:var(--bleu); padding: 10px;">
                        <i class="bi bi-send me-1"></i> Publier l'offre
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>