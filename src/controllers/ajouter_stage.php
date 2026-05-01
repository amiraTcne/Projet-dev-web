<?php
/* on démarre la session */
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';

/* on traite le formulaire d'ajout d'une offre */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $titre         = trim($_POST['titre'] ?? '');
    $mission       = trim($_POST['mission'] ?? '');
    $competences   = trim($_POST['competences'] ?? '');
    $filiere       = trim($_POST['filiere_ciblee'] ?? '');
    $duree         = (int)($_POST['duree_semaines'] ?? 0);
    $date_debut    = trim($_POST['date_debut'] ?? '');
    $id_entreprise = (int)($_POST['id_entreprise'] ?? 0);

    // 1. On vérifie les champs obligatoires
    if (empty($titre) || empty($mission) || $duree <= 0 || $id_entreprise <= 0) {
        $msg_err = 'Titre, mission, durée et entreprise sont obligatoires.';
    } else {
        // 2. On prépare la requête
        $ins = mysqli_prepare($conn,
            "INSERT INTO Offre_Stage (titre, mission, competences, filiere_ciblee,
             duree_semaines, date_debut, statut, id_entreprise)
             VALUES (?, ?, ?, ?, ?, ?, 'ouverte', ?)"
        );

        if ($ins) {
            $date_val = empty($date_debut) ? null : $date_debut;
            
            // ssssisi
            mysqli_stmt_bind_param($ins, 'ssssisi',
                $titre, $mission, $competences, $filiere, $duree, $date_val, $id_entreprise
            );
            
            // On exécute
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Offre de stage ajoutée avec succès !';
            } else {
                $msg_err = "Erreur lors de l'exécution de la requête : " . mysqli_stmt_error($ins);
            }
            mysqli_stmt_close($ins);
        } else {
            $msg_err = "Erreur SQL interne : " . mysqli_error($conn);
        }
    }
}

/* on charge la liste des entreprises pour la liste déroulante */
$entreprises = [];
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    $q = mysqli_query($conn,
        "SELECT id, nom_entreprise FROM Utilisateur
         WHERE role_premier = 'Entreprise' AND actif = 1
         ORDER BY nom_entreprise ASC"
    );
    while ($row = mysqli_fetch_assoc($q)) $entreprises[] = $row;
    mysqli_close($conn);
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une offre — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy {
            border: 1px solid rgba(171,186,205,.4); border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 2rem;
        }
        .form-label { font-weight: 600; color: #111827; font-size: 0.9rem; }
        .form-control:focus, .form-select:focus { border-color: var(--bleu-clair); box-shadow: 0 0 0 0.25rem rgba(37,99,199,0.15); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_admin.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-shield-lock-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:850px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="gestion_stages.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Ajouter une offre de stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Création d'une nouvelle opportunité pour les étudiants</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <div class="card-cy">
        <form method="POST" action="ajouter_stage.php">
            
            <h5 class="fw-bold text-primary mb-4 pb-2 border-bottom" style="font-family:'Syne',sans-serif;"><i class="bi bi-info-circle me-2"></i>Informations générales</h5>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="titre" class="form-label">Titre du poste <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="titre" id="titre" placeholder="Ex : Développeur web front-end" required>
                </div>

                <div class="col-md-6">
                    <label for="id_entreprise" class="form-label">Entreprise <span class="text-danger">*</span></label>
                    <select class="form-select bg-light" name="id_entreprise" id="id_entreprise" required>
                        <option value="">— Sélectionner une entreprise —</option>
                        <?php foreach ($entreprises as $e) : ?>
                            <option value="<?php echo (int)$e['id']; ?>"><?php echo h($e['nom_entreprise']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($entreprises)) : ?>
                        <div class="form-text text-danger mt-1"><i class="bi bi-exclamation-triangle"></i> Aucune entreprise active. Ajoutez d'abord une entreprise via Gestion Utilisateurs.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="duree_semaines" class="form-label">Durée (semaines) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar-week"></i></span>
                        <input type="number" class="form-control bg-light" name="duree_semaines" id="duree_semaines" placeholder="Ex : 12" min="1" max="52" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="date_debut" class="form-label">Date de début (optionnelle)</label>
                    <input type="date" class="form-control bg-light" name="date_debut" id="date_debut">
                </div>
            </div>

            <h5 class="fw-bold text-primary mb-4 pb-2 border-bottom mt-5" style="font-family:'Syne',sans-serif;"><i class="bi bi-card-text me-2"></i>Détails de la mission</h5>

            <div class="row g-4 mb-4">
                <div class="col-12">
                    <label for="mission" class="form-label">Description de la mission <span class="text-danger">*</span></label>
                    <textarea class="form-control bg-light" name="mission" id="mission" placeholder="Décris les missions et responsabilités du stage…" rows="5" required></textarea>
                </div>

                <div class="col-md-6">
                    <label for="filiere_ciblee" class="form-label">Filière ciblée (optionnel)</label>
                    <input type="text" class="form-control bg-light" name="filiere_ciblee" id="filiere_ciblee" placeholder="Ex : Informatique, Réseaux, IA…">
                </div>

                <div class="col-md-6">
                    <label for="competences" class="form-label">Compétences requises (optionnel)</label>
                    <input type="text" class="form-control bg-light" name="competences" id="competences" placeholder="Ex : PHP, JavaScript, MySQL...">
                </div>
            </div>

            <hr class="my-4 text-muted">

            <div class="d-flex justify-content-end gap-3">
                <a href="gestion_stages.php" class="btn btn-light border rounded-pill px-4 fw-bold text-muted">Annuler</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background:var(--bleu); border:none;">
                    <i class="bi bi-plus-circle me-1"></i> Publier l'offre
                </button>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>