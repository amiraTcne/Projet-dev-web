<?php
session_start();

$host = "localhost";
$dbname = "cyStages";
$user = "userpro";
$pass = "projetStage26.";

// Vérifier qu'une entreprise est connectée[cite: 4]
if (!isset($_SESSION['id'])) {
    die("Utilisateur non connecté.");
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$idEntreprise = $_SESSION['id'];
$message = "";

// Mise à jour de la description[cite: 4]
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['description'])) {
    $description = trim($_POST['description']);

    $sqlUpdate = "UPDATE Utilisateur SET description = :description WHERE id = :id AND role_premier = 'Entreprise'";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':description' => $description,
        ':id' => $idEntreprise
    ]);

    $message = "Description mise à jour avec succès.";
}

// Récupération des infos de l'entreprise[cite: 4]
$sql = "SELECT nom_entreprise, email, description, secteur, ville, date_inscription 
        FROM Utilisateur 
        WHERE id = :id AND role_premier = 'Entreprise'";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $idEntreprise]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    die("Entreprise introuvable.");
}

// Comptage des offres publiées par l'entreprise[cite: 4]
$stmtOffres = $pdo->prepare("SELECT COUNT(*) FROM Offre_Stage WHERE id_entreprise = :id");
$stmtOffres->execute([':id' => $idEntreprise]);
$nb_offres = $stmtOffres->fetchColumn();

// Comptage des stages liés à cette entreprise[cite: 4]
$stmtStages = $pdo->prepare("SELECT COUNT(*) FROM Stage WHERE id_entreprise = :id");
$stmtStages->execute([':id' => $idEntreprise]);
$nb_stages = $stmtStages->fetchColumn();

// Initiales pour l'avatar (2 premières lettres du nom de l'entreprise)[cite: 4]
$nomEnt = $entreprise['nom_entreprise'] ?? 'EN';
$initiales = strtoupper(mb_substr($nomEnt, 0, 2));

// Fonction utilitaire pour sécuriser l'affichage HTML
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Entreprise — CY Stage</title>
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

        /* Avatar */
        .avatar-profil {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800;
            margin: 0 auto 15px; box-shadow: 0 4px 16px rgba(27, 79, 155, 0.25);
        }

        /* Stats Blocks */
        .stat-box {
            background: #fbfdff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
        }
        .stat-number {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--bleu);
            line-height: 1;
            margin-bottom: 5px;
        }

        /* Icons List */
        .icon-box {
            width: 38px; height: 38px; border-radius: 10px;
            background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .info-row {
            display: flex; align-items: center; gap: 15px;
            padding: 12px 0; border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child { border-bottom: none; }
    </style>

    <script>
        function activerEdition() {
            document.getElementById('mode-affichage').style.display = 'none';
            document.getElementById('mode-edition').style.display = 'block';
        }
    </script>
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
            <?php echo h($nomEnt); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container mb-5" style="max-width:800px;">

    <!-- En-tête page -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_entreprise.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Mon Profil Entreprise</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Consultez et modifiez les informations de votre entreprise</p>
        </div>
    </div>

    <!-- Alertes de succès[cite: 4] -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo h($message); ?>
        </div>
    <?php endif; ?>

    <!-- Identité principale -->
    <div class="card-cy p-4 text-center mb-4">
        <div class="avatar-profil"><?php echo h($initiales); ?></div>
        <h3 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;">
            <?php echo h($entreprise['nom_entreprise'] ?? 'Non renseigné'); ?>
        </h3>
        <div class="mb-2">
            <span class="badge rounded-pill" style="background-color: var(--bleu); font-size:.75rem;">
                <i class="bi bi-building me-1"></i> Entreprise
            </span>
        </div>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Inscrite le <?php echo date('d/m/Y', strtotime($entreprise['date_inscription'])); ?>
        </p>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="stat-box h-100">
                <div class="stat-number"><?php echo (int)$nb_offres; ?></div>
                <div class="text-muted fw-semibold" style="font-size:.85rem;">Offre<?php echo $nb_offres > 1 ? 's' : ''; ?> publiée<?php echo $nb_offres > 1 ? 's' : ''; ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-box h-100">
                <div class="stat-number"><?php echo (int)$nb_stages; ?></div>
                <div class="text-muted fw-semibold" style="font-size:.85rem;">Stage<?php echo $nb_stages > 1 ? 's' : ''; ?> suivi<?php echo $nb_stages > 1 ? 's' : ''; ?></div>
            </div>
        </div>
    </div>

    <!-- Description modifiable[cite: 4] -->
    <h5 class="fw-bold mb-3" style="font-size: .95rem; color: var(--bleu); text-transform: uppercase; letter-spacing: 1px;">À propos</h5>
    <div class="card-cy p-4 mb-4">
        <div id="mode-affichage">
            <p class="text-dark" style="font-size: .95rem; line-height: 1.6; white-space: pre-line;">
                <?php echo !empty($entreprise['description']) ? h($entreprise['description']) : "<span class='text-muted fst-italic'>Aucune description renseignée pour le moment.</span>"; ?>
            </p>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mt-2 fw-semibold" onclick="activerEdition()">
                <i class="bi bi-pencil-square me-1"></i> Modifier la description
            </button>
        </div>

        <div id="mode-edition" style="display:none;">
            <form method="POST" action="">
                <textarea name="description" class="form-control rounded-3 mb-3" rows="5" placeholder="Présentez votre entreprise..." required><?php echo h($entreprise['description'] ?? ''); ?></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill fw-semibold px-4" style="background:var(--bleu); border-color:var(--bleu);">
                        Enregistrer
                    </button>
                    <button type="button" class="btn btn-light border rounded-pill fw-semibold px-4" onclick="document.getElementById('mode-edition').style.display='none'; document.getElementById('mode-affichage').style.display='block';">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Informations Générales[cite: 4] -->
    <h5 class="fw-bold mb-3" style="font-size: .95rem; color: var(--bleu); text-transform: uppercase; letter-spacing: 1px;">Informations générales</h5>
    <div class="card-cy p-4 mb-5">
        <div class="info-row">
            <div class="icon-box"><i class="bi bi-briefcase"></i></div>
            <div>
                <p class="text-muted mb-0" style="font-size:.75rem; font-weight:600; text-transform:uppercase;">Secteur d'activité</p>
                <p class="mb-0 fw-bold" style="font-size:.95rem; color:#374151;"><?php echo h($entreprise['secteur'] ?? '—'); ?></p>
            </div>
        </div>

        <div class="info-row">
            <div class="icon-box"><i class="bi bi-geo-alt"></i></div>
            <div>
                <p class="text-muted mb-0" style="font-size:.75rem; font-weight:600; text-transform:uppercase;">Ville de rattachement</p>
                <p class="mb-0 fw-bold" style="font-size:.95rem; color:#374151;"><?php echo h($entreprise['ville'] ?? '—'); ?></p>
            </div>
        </div>

        <div class="info-row">
            <div class="icon-box"><i class="bi bi-envelope"></i></div>
            <div>
                <p class="text-muted mb-0" style="font-size:.75rem; font-weight:600; text-transform:uppercase;">Adresse email de contact</p>
                <p class="mb-0 fw-bold" style="font-size:.95rem; color:#374151;"><?php echo h($entreprise['email'] ?? '—'); ?></p>
            </div>
        </div>
    </div>

    <!-- Déconnexion en bas de page -->
    <div class="text-center mb-5">
        <a href="deconnexion.php" class="btn btn-outline-danger rounded-pill fw-semibold px-4">
            <i class="bi bi-box-arrow-right me-1"></i> Se déconnecter
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>