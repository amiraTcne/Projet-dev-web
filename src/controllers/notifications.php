<?php
/* on démarre la session */
session_start();

/* Sécurité : on vérifie que c'est bien un admin connecté */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$demandes  = [];
$logs      = [];
$nb_dossiers_soumis = 0;

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* les demandes de filière en attente : ce sont les vraies notifications */
    $q1 = mysqli_query($conn,
        "SELECT df.id_demande, df.filiere_demandee, df.justification, df.date_demande,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant
         FROM Demande_Filiere df
         JOIN Utilisateur u ON u.id = df.id_etudiant
         WHERE df.statut = 'en_attente'
         ORDER BY df.date_demande ASC"
    );
    while ($row = mysqli_fetch_assoc($q1)) $demandes[] = $row;

    /* les dossiers soumis en attente de validation */
    $q2 = mysqli_query($conn, "SELECT COUNT(*) FROM Dossier_Stage WHERE statut = 'soumis'");
    [$nb_dossiers_soumis] = mysqli_fetch_row($q2);

    /* les 15 dernières entrées du journal de bord (Trace_Log) */
    $q3 = mysqli_query($conn,
        "SELECT tl.action, tl.entite, tl.description, tl.date_heure,
                CONCAT(u.prenom, ' ', u.nom) AS auteur
         FROM Trace_Log tl
         LEFT JOIN Utilisateur u ON u.id = tl.id_user
         ORDER BY tl.date_heure DESC LIMIT 15"
    );
    while ($row = mysqli_fetch_assoc($q3)) $logs[] = $row;

    mysqli_close($conn);
}

/* on calcule le nombre total de notifications pour le badge */
$nb_notifs = count($demandes) + (int)$nb_dossiers_soumis;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy { border: 1px solid rgba(171,186,205,.4); border-radius: 18px; box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem; }
        
        .stat-card {
            background: #fff; border: 1px solid rgba(171,186,205,.4); border-radius: 14px;
            padding: 1.5rem; text-align: center; box-shadow: 0 4px 12px rgba(27,79,155,.05);
        }
        .stat-card .display-4 { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--bleu); }
        
        .log-icon {
            width: 45px; height: 45px; border-radius: 12px;
            background: linear-gradient(135deg, #374151, #6b7280); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; font-weight: 700; flex-shrink: 0; text-transform: uppercase;
        }

        .section-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; color: var(--bleu); font-weight: 700; margin-bottom: 1rem; border-bottom: 2px solid var(--bleu); display: inline-block; padding-bottom: 0.3rem;}
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

<div class="container mb-5" style="max-width:900px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_admin.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div class="flex-grow-1">
            <h1 class="h4 mb-0 fw-bold d-flex align-items-center gap-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">
                Notifications
                <?php if ($nb_notifs > 0) : ?><span class="badge bg-danger rounded-pill fs-6"><?php echo $nb_notifs; ?></span><?php endif; ?>
            </h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Suivi des alertes et du journal d'activité de la plateforme</p>
        </div>
    </div>

    <!-- Statistiques des alertes -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="display-4 mb-1 text-warning"><?php echo count($demandes); ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.8rem; letter-spacing:1px;">Demande<?php echo count($demandes) > 1 ? 's' : ''; ?> de filière en attente</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="display-4 mb-1 text-primary"><?php echo $nb_dossiers_soumis; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.8rem; letter-spacing:1px;">Dossier<?php echo $nb_dossiers_soumis > 1 ? 's' : ''; ?> à valider</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- Demandes de filières -->
        <div class="col-lg-12 mb-4">
            <h2 class="section-title">Demandes de filières</h2>
            <div class="card-cy mt-2 p-0 overflow-hidden">
                <?php if (empty($demandes)) : ?>
                    <div class="text-center p-5">
                        <i class="bi bi-bell-slash text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mb-0 fw-semibold">Aucune demande en attente.</p>
                    </div>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($demandes as $d) : ?>
                            <div class="list-group-item p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <p class="mb-1 text-dark">Demande : <strong class="text-primary"><?php echo h($d['filiere_demandee']); ?></strong></p>
                                    <p class="text-muted mb-0" style="font-size:.8rem;">
                                        Par <strong class="text-dark"><?php echo h($d['etudiant']); ?></strong> · <?php echo date('d/m/Y', strtotime($d['date_demande'])); ?>
                                    </p>
                                    <?php if ($d['justification']) : ?>
                                        <p class="text-secondary fst-italic mt-1 mb-0" style="font-size:.8rem; border-left: 2px solid #e5e7eb; padding-left: 10px;">"<?php echo h($d['justification']); ?>"</p>
                                    <?php endif; ?>
                                </div>
                                <a href="gestion_filieres.php" class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-4 flex-shrink-0">
                                    Traiter <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Journal de bord (Trace_Log) -->
        <div class="col-lg-12">
            <h2 class="section-title">Journal de bord (Activité récente)</h2>
            <div class="card-cy mt-2 p-0 overflow-hidden">
                <?php if (empty($logs)) : ?>
                    <div class="text-center p-5">
                        <i class="bi bi-journal-x text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mb-0 fw-semibold">Aucune entrée dans le journal pour le moment.</p>
                    </div>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($logs as $log) : ?>
                            <div class="list-group-item p-3 d-flex align-items-center gap-3">
                                <div class="log-icon" title="<?php echo h($log['action']); ?>">
                                    <?php echo h(mb_substr($log['action'], 0, 3)); ?>
                                </div>
                                <div>
                                    <p class="mb-1 text-dark d-flex align-items-center gap-2" style="font-size:.9rem;">
                                        <span class="badge bg-secondary text-white fw-normal" style="font-size:.65rem;"><?php echo h($log['action']); ?></span>
                                        <span class="fw-semibold"><?php echo h($log['description'] ?? $log['entite'] ?? '—'); ?></span>
                                    </p>
                                    <p class="text-muted mb-0" style="font-size:.75rem;">
                                        <i class="bi bi-person me-1"></i> <?php echo h($log['auteur'] ?? 'Inconnu'); ?>
                                        <span class="mx-1">•</span> <i class="bi bi-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($log['date_heure'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>