<?php
session_start();

// Sécurité : Rôle Entreprise[cite: 22]
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php');
    exit();
}

$idEntreprise = (int) $_SESSION['id'];
$numOffre = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);

if (!$connect) {
    die("Erreur : connexion impossible à la base de données.");
}

mysqli_set_charset($connect, "utf8mb4");

$erreur = '';
$message = '';
$offre = null;
$nomEntreprise = 'Entreprise';

// Nom entreprise connectée[cite: 22]
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

// Récupération de l'offre[cite: 22]
$sqlOffre = "SELECT num_offre, titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut
             FROM Offre_Stage
             WHERE num_offre = ? AND id_entreprise = ?";

$stmtOffre = mysqli_prepare($connect, $sqlOffre);

if ($stmtOffre) {
    mysqli_stmt_bind_param($stmtOffre, "ii", $numOffre, $idEntreprise);
    mysqli_stmt_execute($stmtOffre);
    $resultOffre = mysqli_stmt_get_result($stmtOffre);
    $offre = mysqli_fetch_assoc($resultOffre);
    mysqli_stmt_close($stmtOffre);
}

if (!$offre) {
    die("Offre introuvable ou accès interdit.");
}

// Traitement modification[cite: 22]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_offre'])) {
    $titre = trim($_POST['titre'] ?? '');
    $duree = trim($_POST['duree'] ?? '');
    $date_debut = trim($_POST['date_debut'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $profil = trim($_POST['profil'] ?? '');
    $competences = trim($_POST['competences'] ?? '');
    $statut = trim($_POST['statut'] ?? '');

    $statutsAutorises = ['ouverte', 'pourvue', 'archivee'];

    if ($titre === '' || $duree === '' || $description === '') {
        $erreur = "Veuillez remplir les champs obligatoires : titre, durée et description.";
    } elseif (!ctype_digit($duree) || (int)$duree <= 0) {
        $erreur = "La durée doit être un nombre entier positif.";
    } elseif (!in_array($statut, $statutsAutorises, true)) {
        $erreur = "Le statut sélectionné est invalide.";
    } else {
        $sqlUpdate = "UPDATE Offre_Stage
                      SET titre = ?, mission = ?, competences = ?, filiere_ciblee = ?, duree_semaines = ?, date_debut = ?, statut = ?
                      WHERE num_offre = ? AND id_entreprise = ?";

        $stmtUpdate = mysqli_prepare($connect, $sqlUpdate);

        if ($stmtUpdate) {
            $dureeInt = (int) $duree;
            $dateSql = ($date_debut !== '') ? $date_debut : null;

            mysqli_stmt_bind_param(
                $stmtUpdate,
                "ssssissii",
                $titre,
                $description,
                $competences,
                $profil,
                $dureeInt,
                $dateSql,
                $statut,
                $numOffre,
                $idEntreprise
            );

            if (mysqli_stmt_execute($stmtUpdate)) {
                header("Location: detail_offre_entreprise.php?id=" . $numOffre . "&message=offre_modifiee");
                exit();
            } else {
                $erreur = "Erreur lors de la mise à jour de l'offre.";
            }

            mysqli_stmt_close($stmtUpdate);
        } else {
            $erreur = "Erreur dans la préparation de la requête.";
        }
    }

    // Réinjecter les valeurs saisies si erreur[cite: 22]
    $offre['titre'] = $titre;
    $offre['mission'] = $description;
    $offre['competences'] = $competences;
    $offre['filiere_ciblee'] = $profil;
    $offre['duree_semaines'] = $duree;
    $offre['date_debut'] = $date_debut;
    $offre['statut'] = $statut;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'offre — CY Stage</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        
        .card-cy {
            border: 1px solid rgba(171, 186, 205, 0.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27, 79, 155, 0.06);
            background-color: #ffffff;
        }
        .form-label { font-weight: 600; color: #111827; font-size: 0.9rem; }
        .form-control:focus, .form-select:focus { border-color: var(--bleu-clair); box-shadow: 0 0 0 0.25rem rgba(37,99,199,0.15); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="#">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline">
                <i class="bi bi-building me-2"></i> <?php echo h($nomEntreprise); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i> <span class="d-none d-sm-inline">Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:800px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="detail_offre_entreprise.php?id=<?php echo (int) $numOffre; ?>" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Modifier l'offre</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Mettez à jour les informations de votre annonce</p>
        </div>
    </div>

    <?php if (!empty($erreur)) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($erreur); ?></strong>
        </div>
    <?php endif; ?>

    <div class="card-cy p-4 p-md-5">
        <form method="POST" action="">
            
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <label for="titre" class="form-label">Titre de l'offre <span class="text-danger">*</span></label>
                    <input type="text" id="titre" name="titre" class="form-control rounded-3" value="<?php echo h($offre['titre']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="duree" class="form-label">Durée (semaines) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar-week"></i></span>
                        <input type="number" id="duree" name="duree" class="form-control" min="1" value="<?php echo h($offre['duree_semaines']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="date_debut" class="form-label">Date de début estimée</label>
                    <input type="date" id="date_debut" name="date_debut" class="form-control rounded-3" value="<?php echo h($offre['date_debut'] ?? ''); ?>">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Mission / Description <span class="text-danger">*</span></label>
                    <textarea id="description" name="description" class="form-control rounded-3" rows="5" required><?php echo h($offre['mission']); ?></textarea>
                </div>

                <div class="col-12">
                    <label for="profil" class="form-label">Filière / Profil recherché</label>
                    <input type="text" id="profil" name="profil" class="form-control rounded-3" value="<?php echo h($offre['filiere_ciblee'] ?? ''); ?>" placeholder="Ex: Informatique, Ingénieur Data...">
                </div>

                <div class="col-12">
                    <label for="competences" class="form-label">Compétences clés (séparées par des virgules)</label>
                    <input type="text" id="competences" name="competences" class="form-control rounded-3" value="<?php echo h($offre['competences'] ?? ''); ?>" placeholder="Ex: PHP, SQL, Gestion de projet">
                </div>

                <div class="col-12">
                    <label for="statut" class="form-label">Statut de l'offre</label>
                    <select id="statut" name="statut" class="form-select rounded-3 bg-light" required>
                        <option value="ouverte" <?php echo ($offre['statut'] === 'ouverte') ? 'selected' : ''; ?>>🟢 Ouverte aux candidatures</option>
                        <option value="pourvue" <?php echo ($offre['statut'] === 'pourvue') ? 'selected' : ''; ?>>🔴 Pourvue (Stage trouvé)</option>
                        <option value="archivee" <?php echo ($offre['statut'] === 'archivee') ? 'selected' : ''; ?>>⚪ Archivée (Désactivée)</option>
                    </select>
                </div>
            </div>

            <hr class="my-4 text-muted">

            <div class="d-flex justify-content-end gap-3">
                <a href="detail_offre_entreprise.php?id=<?php echo (int) $numOffre; ?>" class="btn btn-light border rounded-pill px-4 fw-bold text-muted">Annuler</a>
                <button type="submit" name="modifier_offre" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background:var(--bleu); border:none;">
                    <i class="bi bi-save me-1"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>