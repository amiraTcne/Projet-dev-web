<?php
/* on démarre la session */
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on charge les stats pour la page */
$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$nb_offres = $nb_en_cours = $nb_valides = $nb_filieres = 0;

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    $q = mysqli_query($conn, "SELECT COUNT(*) FROM Offre_Stage WHERE statut = 'ouverte'"); [$nb_offres]   = mysqli_fetch_row($q);
    $q = mysqli_query($conn, "SELECT COUNT(*) FROM Stage WHERE statut = 'en_cours'");      [$nb_en_cours] = mysqli_fetch_row($q);
    $q = mysqli_query($conn, "SELECT COUNT(*) FROM Dossier_Stage WHERE statut = 'valide'");[$nb_valides]  = mysqli_fetch_row($q);
    $q = mysqli_query($conn, "SELECT COUNT(DISTINCT filiere_ciblee) FROM Offre_Stage WHERE filiere_ciblee IS NOT NULL"); [$nb_filieres] = mysqli_fetch_row($q);
    mysqli_close($conn);
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Stages — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        
        .stat-card {
            background: #fff; border: 1px solid rgba(171,186,205,.4); border-radius: 16px;
            padding: 1.5rem; text-align: center; box-shadow: 0 4px 12px rgba(27,79,155,.05);
            height: 100%; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); border-color: var(--bleu-clair); }
        .stat-card .display-5 { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--bleu); }

        .action-card {
            display: flex; align-items: center; gap: 1rem;
            padding: 1.2rem; border: 1px solid rgba(171,186,205,.4);
            border-radius: 14px; background: #fff; text-decoration: none; color: inherit;
            transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,.03);
        }
        .action-card:hover { transform: translateX(5px); border-color: var(--bleu); box-shadow: 0 6px 15px rgba(27,79,155,.1); }
        .icon-box {
            width: 48px; height: 48px; border-radius: 12px; background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;
            transition: background-color 0.2s, color 0.2s;
        }
        .action-card:hover .icon-box { background: var(--bleu); color: #fff; }
        
        .card-cy { border: 1px solid rgba(171,186,205,.4); border-radius: 18px; box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem; }
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

<div class="container mb-5" style="max-width:1000px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_admin.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Gestion des Stages</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Vue d'ensemble sur les offres et les dossiers</p>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="display-5 mb-1"><?php echo $nb_offres; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.7rem; letter-spacing:1px;">Offres ouvertes</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="display-5 mb-1 text-warning"><?php echo $nb_en_cours; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.7rem; letter-spacing:1px;">Stages en cours</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="display-5 mb-1 text-success"><?php echo $nb_valides; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.7rem; letter-spacing:1px;">Dossiers validés</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="display-5 mb-1 text-primary"><?php echo $nb_filieres; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.7rem; letter-spacing:1px;">Filières actives</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Actions -->
        <div class="col-lg-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Actions rapides</h5>
            <div class="d-flex flex-column gap-3">
                <a href="ajouter_stage.php" class="action-card">
                    <div class="icon-box"><i class="bi bi-plus-square"></i></div>
                    <div class="flex-grow-1"><h6 class="fw-bold mb-0 text-dark">Ajouter une offre</h6></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="ajouter_domaine_stage.php" class="action-card">
                    <div class="icon-box"><i class="bi bi-mortarboard"></i></div>
                    <div class="flex-grow-1"><h6 class="fw-bold mb-0 text-dark">Gérer les domaines</h6></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="gestion_stages.php?voir=1" class="action-card <?php echo isset($_GET['voir']) ? 'border-primary' : ''; ?>">
                    <div class="icon-box"><i class="bi bi-list-ul"></i></div>
                    <div class="flex-grow-1"><h6 class="fw-bold mb-0 text-dark">Voir les offres publiées</h6></div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>

        <!-- Affichage des offres si demandé -->
        <div class="col-lg-8">
            <?php if (isset($_GET['voir'])) :
                $conn2 = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
                $offres = [];
                if ($conn2) {
                    mysqli_set_charset($conn2, 'utf8mb4');
                    $q = mysqli_query($conn2,
                        "SELECT o.num_offre, o.titre, o.filiere_ciblee, o.duree_semaines,
                                o.statut, o.date_publication, u.nom_entreprise
                         FROM Offre_Stage o
                         JOIN Utilisateur u ON u.id = o.id_entreprise
                         ORDER BY o.date_publication DESC LIMIT 50"
                    );
                    while ($row = mysqli_fetch_assoc($q)) $offres[] = $row;
                    mysqli_close($conn2);
                }
            ?>
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-briefcase-fill text-primary me-2"></i>Toutes les offres (<?php echo count($offres); ?>)</h5>
                <div class="card-cy p-0 overflow-hidden">
                    <?php if (empty($offres)) : ?>
                        <div class="text-center p-4">
                            <i class="bi bi-folder-x text-muted opacity-50 mb-3 d-block" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">Aucune offre référencée pour le moment.</p>
                        </div>
                    <?php else : ?>
                        <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                            <?php foreach ($offres as $o) :
                                $st = ['ouverte'=>['bg-success','Ouverte'],'pourvue'=>['bg-primary','Pourvue'],'archivee'=>['bg-secondary','Archivée']];
                                $badge = $st[$o['statut']] ?? ['bg-dark', $o['statut']];
                            ?>
                                <div class="list-group-item p-3 d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1" style="color:var(--bleu);"><?php echo h($o['titre']); ?></h6>
                                        <p class="text-muted mb-0" style="font-size:.8rem;">
                                            <i class="bi bi-building me-1"></i> <?php echo h($o['nom_entreprise']); ?>
                                            <?php if ($o['filiere_ciblee']) : ?> <span class="mx-1">•</span> <i class="bi bi-mortarboard me-1"></i> <?php echo h($o['filiere_ciblee']); ?><?php endif; ?>
                                            <span class="mx-1">•</span> <i class="bi bi-clock me-1"></i> <?php echo (int)$o['duree_semaines']; ?> sem.
                                        </p>
                                    </div>
                                    <span class="badge rounded-pill <?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
                    <i class="bi bi-cursor text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                    <p class="mb-0 text-center">Sélectionnez une action sur la gauche pour gérer le contenu.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>