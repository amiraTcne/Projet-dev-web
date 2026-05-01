<?php
session_start();

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

$offre = null;
$nomEntreprise = 'Entreprise';

// Récupération du nom de l'entreprise
$sqlEntreprise = "SELECT nom_entreprise FROM Utilisateur WHERE id = ? AND role_premier = 'Entreprise'";
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

// Suppression de l'offre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_offre'])) {
    $sqlDelete = "DELETE FROM Offre_Stage WHERE num_offre = ? AND id_entreprise = ?";
    $stmtDelete = mysqli_prepare($connect, $sqlDelete);
    if ($stmtDelete) {
        mysqli_stmt_bind_param($stmtDelete, "ii", $numOffre, $idEntreprise);
        if (mysqli_stmt_execute($stmtDelete)) {
            header('Location: offres_entreprisefram.php?message=offre_supprimee');
            exit();
        }
        mysqli_stmt_close($stmtDelete);
    }
}

// Récupération des détails de l'offre
$sqlOffre = "SELECT * FROM Offre_Stage WHERE num_offre = ? AND id_entreprise = ?";
$stmtOffre = mysqli_prepare($connect, $sqlOffre);

if ($stmtOffre) {
    mysqli_stmt_bind_param($stmtOffre, "ii", $numOffre, $idEntreprise);
    mysqli_stmt_execute($stmtOffre);
    $resultOffre = mysqli_stmt_get_result($stmtOffre);
    $offre = mysqli_fetch_assoc($resultOffre);
    mysqli_stmt_close($stmtOffre);
}

if (!$offre) {
    die("Offre introuvable ou vous n'avez pas l'autorisation d'y accéder.");
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'offre — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
            --rouge-cy: #dc3545;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.08);
            background: #fff;
            padding: 2rem;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--bleu);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-pill {
            background: #eef2ff;
            color: var(--bleu);
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Overlay Modale Suppression */
        #overlay-delete {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center;
            z-index: 2000; backdrop-filter: blur(4px);
        }
        #overlay-delete.show { display: flex; }
        .modal-cy {
            background: white; border-radius: 20px; width: 90%; max-width: 400px;
            padding: 2rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="navbar-brand" href="accueil_entreprise.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <span class="fw-bold text-white">
            <i class="bi bi-building me-2"></i> <?php echo h($nomEntreprise); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Déconnexion</a>
    </div>
</nav>

<div class="container mb-5" style="max-width: 850px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="offres_entreprisefram.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Détails de l'offre</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Référence #<?php echo (int)$offre['num_offre']; ?></p>
        </div>
    </div>

    <div class="card-cy">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4 border-bottom pb-4">
            <div>
                <h2 class="fw-bold mb-2" style="font-family:'Syne',sans-serif; color:#111827;"><?php echo h($offre['titre']); ?></h2>
                <div class="d-flex flex-wrap gap-2">
                    <span class="info-pill"><i class="bi bi-clock"></i> <?php echo h($offre['duree_semaines']); ?> semaines</span>
                    <?php if($offre['date_debut']): ?>
                        <span class="info-pill"><i class="bi bi-calendar-event"></i> Début : <?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?></span>
                    <?php endif; ?>
                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2" style="font-size: .8rem;">
                        <i class="bi bi-check-circle me-1"></i> <?php echo h($offre['statut']); ?>
                    </span>
                </div>
            </div>
            <button class="btn btn-outline-danger rounded-pill fw-bold btn-sm px-3" onclick="ouvrirModal()">
                <i class="bi bi-trash3 me-1"></i> Supprimer l'offre
            </button>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="section-title"><i class="bi bi-card-text"></i> Mission & Description</div>
                <div class="p-3 bg-light rounded-3 text-dark" style="line-height: 1.7; white-space: pre-line;">
                    <?php echo h($offre['mission']); ?>
                </div>
            </div>

            <?php if($offre['filiere_ciblee']): ?>
            <div class="col-md-6">
                <div class="section-title"><i class="bi bi-mortarboard"></i> Profil recherché</div>
                <p class="mb-0 fw-medium text-secondary"><?php echo h($offre['filiere_ciblee']); ?></p>
            </div>
            <?php endif; ?>

            <?php if($offre['competences']): ?>
            <div class="col-md-6">
                <div class="section-title"><i class="bi bi-stars"></i> Compétences clés</div>
                <p class="mb-0 fw-medium text-secondary"><?php echo h($offre['competences']); ?></p>
            </div>
            <?php endif; ?>

            <div class="col-12 mt-5 pt-4 border-top">
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i> Offre publiée le <?php echo date('d/m/Y', strtotime($offre['date_publication'])); ?>.
                </p>
            </div>
        </div>
    </div>
</div>

<div id="overlay-delete">
    <div class="modal-cy">
        <div class="mb-3 text-danger">
            <i class="bi bi-exclamation-octagon fs-1"></i>
        </div>
        <h4 class="fw-bold mb-3" style="font-family:'Syne',sans-serif;">Supprimer l'offre ?</h4>
        <p class="text-muted mb-4">Cette action est irréversible. Toutes les candidatures liées seront également impactées.</p>
        
        <form method="POST">
            <div class="d-grid gap-2">
                <button type="submit" name="supprimer_offre" class="btn btn-danger rounded-pill fw-bold py-2">
                    Confirmer la suppression
                </button>
                <button type="button" class="btn btn-light border rounded-pill fw-bold py-2" onclick="fermerModal()">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function ouvrirModal() {
        document.getElementById('overlay-delete').classList.add('show');
    }
    function fermerModal() {
        document.getElementById('overlay-delete').classList.remove('show');
    }
    // Fermer si clic en dehors
    window.onclick = function(event) {
        let overlay = document.getElementById('overlay-delete');
        if (event.target == overlay) fermerModal();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>