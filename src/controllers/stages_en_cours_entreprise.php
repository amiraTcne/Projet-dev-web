<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);

if (!$connect) {
    die("Erreur : connexion impossible à la base de données.");
}

mysqli_set_charset($connect, "utf8mb4");

// Fonction utilitaire pour sécuriser l'affichage HTML
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$stages = [];
$idEntreprise = $_SESSION['id']; 

$sql = "SELECT 
            s.num_stage,
            s.titre,
            s.statut,
            s.id_etudiant,
            u.nom,
            u.prenom
        FROM Stage s
        INNER JOIN Utilisateur u ON s.id_etudiant = u.id
        WHERE s.id_entreprise = ?
          AND s.statut = 'en_cours'
        ORDER BY s.titre ASC, u.nom ASC";

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $idEntreprise);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $titre = $row['titre'];

        if (!isset($stages[$titre])) {
            $stages[$titre] = [
                'num_stage' => $row['num_stage'],
                'titre' => $row['titre'],
                'stagiaires' => []
            ];
        }

        $stages[$titre]['stagiaires'][] = [
            'id_etudiant' => $row['id_etudiant'],
            'nom_complet' => $row['prenom'] . ' ' . $row['nom'],
            'num_stage' => $row['num_stage']
        ];
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stages en cours — CY Stage</title>
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

        /* Avatar icône de stage */
        .avatar-initiales {
            width: 46px; height: 46px; border-radius: 50%;
            background: linear-gradient(135deg, #1B4F9B, #2563c7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif;
            font-weight: 800; font-size: 1.1rem; flex-shrink: 0;
        }

        /* Lignes étudiants interactives */
        .student-row {
            display: flex; align-items: center; padding: 12px 16px;
            border: 1px solid #e5e7eb; border-radius: 12px;
            background: #fbfdff; text-decoration: none; color: inherit;
            transition: all 0.2s ease-in-out;
        }
        .student-row:hover {
            background: #f0f4fa; border-color: var(--bleu-clair);
            transform: translateX(4px); color: inherit;
        }
        .student-row i.bi-chevron-right {
            transition: transform 0.2s ease;
        }
        .student-row:hover i.bi-chevron-right {
            transform: translateX(3px); color: var(--bleu) !important;
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
            <?php echo h($_SESSION['nom_entreprise'] ?? 'Entreprise'); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container" style="max-width:900px;">

    <!-- En-tête page -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_entreprise.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Stages en cours</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Consultez et gérez vos stages actuellement en cours</p>
        </div>
    </div>

    <!-- Contenu Principal -->
    <?php if (empty($stages)): ?>
        <div class="card-cy p-5 text-center mb-4">
            <i class="bi bi-briefcase fs-1 text-muted opacity-50 mb-3 d-block"></i>
            <h5 class="fw-bold mb-1" style="color:var(--bleu);">Aucun stage en cours</h5>
            <p class="text-muted mb-0">Vous n'avez aucun stage en cours pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-4 mb-5">
            <?php foreach ($stages as $stage): ?>
                <div class="card-cy p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="avatar-initiales">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div class="flex-grow-1 w-100">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h5 class="mb-0 fw-bold" style="font-family:'Syne',sans-serif;">
                                    <a href="gestion_stages_en_cours_entreprise.php?num_stage=<?php echo urlencode($stage['num_stage']); ?>" class="text-decoration-none text-dark hover-primary">
                                        <?php echo h($stage['titre']); ?>
                                    </a>
                                </h5>
                            </div>
                            
                            <p class="text-muted fw-semibold mb-3 mt-2" style="font-size:.85rem; color:var(--bleu) !important;">
                                <i class="bi bi-people me-1"></i> 
                                Étudiant stagiaire<?php echo count($stage['stagiaires']) > 1 ? 's' : ''; ?> :
                            </p>
                            
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($stage['stagiaires'] as $stagiaire): ?>
                                    <a href="gestion_stages_en_cours_entreprise.php?num_stage=<?php echo urlencode($stagiaire['num_stage']); ?>" class="student-row">
                                        <div class="d-flex align-items-center w-100">
                                            <i class="bi bi-person-circle me-3 text-secondary fs-5"></i>
                                            <span class="fw-bold" style="font-size:.95rem; color:#374151;">
                                                <?php echo h($stagiaire['nom_complet']); ?>
                                            </span>
                                            <i class="bi bi-chevron-right ms-auto text-muted" style="font-size:.85rem;"></i>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>