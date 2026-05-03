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
$msgOk = '';
$msgErr = '';

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

// 1. Suppression de l'offre
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

// 2. Modification de l'offre (INCLUANT LE STATUT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_offre'])) {
    $titre = trim($_POST['titre']);
    $mission = trim($_POST['mission']);
    $filiere = trim($_POST['filiere_ciblee']);
    $competences = trim($_POST['competences']);
    $duree = (int)$_POST['duree_semaines'];
    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
    $statut = trim($_POST['statut']); // Nouvelle variable pour le statut

    $sqlUpdate = "UPDATE Offre_Stage 
                  SET titre = ?, mission = ?, filiere_ciblee = ?, competences = ?, duree_semaines = ?, date_debut = ?, statut = ? 
                  WHERE num_offre = ? AND id_entreprise = ?";
    
    $stmtUpdate = mysqli_prepare($connect, $sqlUpdate);
    if ($stmtUpdate) {
        // Ajout du statut ("s") dans le bind_param : ssssissii
        mysqli_stmt_bind_param($stmtUpdate, "ssssissii", $titre, $mission, $filiere, $competences, $duree, $date_debut, $statut, $numOffre, $idEntreprise);
        if (mysqli_stmt_execute($stmtUpdate)) {
            $msgOk = "L'offre a été modifiée avec succès.";
        } else {
            $msgErr = "Une erreur est survenue lors de la modification.";
        }
        mysqli_stmt_close($stmtUpdate);
    }
}

// 3. Récupération des détails de l'offre (placé APRES l'update pour afficher les nouvelles données)
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

// Petite fonction pour définir la couleur du badge selon le statut
function getBadgeClass($statut) {
    $s = strtolower($statut);
    if (strpos($s, 'ouverte') !== false || strpos($s, 'publié') !== false) return 'bg-success-subtle text-success border-success-subtle';
    if (strpos($s, 'fermé') !== false || strpos($s, 'annulé') !== false) return 'bg-danger-subtle text-danger border-danger-subtle';
    if (strpos($s, 'pourvue') !== false) return 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
    return 'bg-secondary-subtle text-secondary border-secondary-subtle';
}
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

        /* Overlay Modales */
        .overlay-cy {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center;
            z-index: 2000; backdrop-filter: blur(4px); padding: 20px;
        }
        .overlay-cy.show { display: flex; }
        
        .modal-cy {
            background: white; border-radius: 20px; width: 100%; max-width: 400px;
            padding: 2rem; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-height: 90vh; overflow-y: auto;
        }
        
        /* Modale de modification plus large */
        .modal-edit { max-width: 600px; text-align: left; }
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

    <!-- Alertes de succès ou d'erreur -->
    <?php if ($msgOk): ?>
        <div class="alert alert-success d-flex align-items-center rounded-4 mb-4 gap-2 shadow-sm">
            <i class="bi bi-check-circle-fill fs-5"></i> <strong><?php echo h($msgOk); ?></strong>
        </div>
    <?php endif; ?>
    <?php if ($msgErr): ?>
        <div class="alert alert-danger d-flex align-items-center rounded-4 mb-4 gap-2 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i> <strong><?php echo h($msgErr); ?></strong>
        </div>
    <?php endif; ?>

    <div class="card-cy">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4 border-bottom pb-4">
            <div>
                <h2 class="fw-bold mb-2" style="font-family:'Syne',sans-serif; color:#111827;"><?php echo h($offre['titre']); ?></h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="info-pill"><i class="bi bi-clock"></i> <?php echo h($offre['duree_semaines']); ?> semaines</span>
                    <?php if($offre['date_debut']): ?>
                        <span class="info-pill"><i class="bi bi-calendar-event"></i> Début : <?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?></span>
                    <?php endif; ?>
                    <span class="badge rounded-pill border px-3 py-2 <?php echo getBadgeClass($offre['statut']); ?>" style="font-size: .8rem;">
                        <i class="bi bi-record-circle me-1"></i> <?php echo h($offre['statut']); ?>
                    </span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary rounded-pill fw-bold btn-sm px-3" onclick="ouvrirModalModif()">
                    <i class="bi bi-pencil me-1"></i> Modifier
                </button>
                <button class="btn btn-outline-danger rounded-pill fw-bold btn-sm px-3" onclick="ouvrirModalSupp()">
                    <i class="bi bi-trash3 me-1"></i> Supprimer
                </button>
            </div>
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

<!-- Modale de Suppression -->
<div id="overlay-delete" class="overlay-cy">
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
                <button type="button" class="btn btn-light border rounded-pill fw-bold py-2" onclick="fermerModalSupp()">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modale de Modification -->
<div id="overlay-edit" class="overlay-cy">
    <div class="modal-cy modal-edit">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="font-family:'Syne',sans-serif; color:var(--bleu);">Modifier l'offre</h4>
            <button type="button" class="btn-close" aria-label="Close" onclick="fermerModalModif()"></button>
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Titre de l'offre *</label>
                <input type="text" name="titre" class="form-control rounded-3" value="<?php echo h($offre['titre']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Mission *</label>
                <textarea name="mission" class="form-control rounded-3" rows="5" required><?php echo h($offre['mission']); ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold small text-muted">Statut *</label>
                    <select name="statut" class="form-select rounded-3" required>
                        <option value="Ouverte" <?php echo ($offre['statut'] === 'Ouverte') ? 'selected' : ''; ?>>Ouverte</option>
                        <option value="Pourvue" <?php echo ($offre['statut'] === 'Pourvue') ? 'selected' : ''; ?>>Pourvue</option>
                        <option value="Fermée" <?php echo ($offre['statut'] === 'Fermée') ? 'selected' : ''; ?>>Fermée</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold small text-muted">Durée (semaines) *</label>
                    <input type="number" name="duree_semaines" class="form-control rounded-3" value="<?php echo (int)$offre['duree_semaines']; ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold small text-muted">Date de début</label>
                    <input type="date" name="date_debut" class="form-control rounded-3" value="<?php echo h($offre['date_debut']); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Filière ciblée</label>
                <input type="text" name="filiere_ciblee" class="form-control rounded-3" value="<?php echo h($offre['filiere_ciblee']); ?>">
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-muted">Compétences clés</label>
                <input type="text" name="competences" class="form-control rounded-3" value="<?php echo h($offre['competences']); ?>">
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <button type="button" class="btn btn-light border rounded-pill fw-bold px-4" onclick="fermerModalModif()">Annuler</button>
                <button type="submit" name="modifier_offre" class="btn btn-primary rounded-pill fw-bold px-4" style="background:var(--bleu); border-color:var(--bleu);">
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Gestion Modale Suppression
    function ouvrirModalSupp() { document.getElementById('overlay-delete').classList.add('show'); }
    function fermerModalSupp() { document.getElementById('overlay-delete').classList.remove('show'); }
    
    // Gestion Modale Modification
    function ouvrirModalModif() { document.getElementById('overlay-edit').classList.add('show'); }
    function fermerModalModif() { document.getElementById('overlay-edit').classList.remove('show'); }

    // Fermer les modales si on clique en dehors
    window.onclick = function(event) {
        if (event.target == document.getElementById('overlay-delete')) fermerModalSupp();
        if (event.target == document.getElementById('overlay-edit')) fermerModalModif();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>