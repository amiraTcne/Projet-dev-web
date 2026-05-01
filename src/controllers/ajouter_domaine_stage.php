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

/* on traite la validation ou le rejet d'une demande de filière */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    /* approbation ou rejet d'une demande existante */
    if (isset($_POST['action']) && in_array($_POST['action'], ['approuver', 'rejeter'])) {
        $id_demande = (int)($_POST['id_demande'] ?? 0);
        $statut     = $_POST['action'] === 'approuver' ? 'approuvee' : 'rejetee';
        $upd = mysqli_prepare($conn,
            "UPDATE Demande_Filiere SET statut = ?, id_user_admin = ?, date_traitement = NOW()
             WHERE id_demande = ?"
        );
        mysqli_stmt_bind_param($upd, 'sii', $statut, $_SESSION['id'], $id_demande);
        mysqli_stmt_execute($upd);
        $msg_ok = $statut === 'approuvee' ? 'Demande de filière approuvée !' : 'Demande rejetée.';
        mysqli_stmt_close($upd);
    }

    /* ajout d'un domaine/filière manuellement par l'admin */
    if (isset($_POST['nouveau_domaine'])) {
        $domaine = trim($_POST['nouveau_domaine'] ?? '');
        if (!empty($domaine)) {
            $msg_ok = "Domaine \"" . htmlspecialchars($domaine) . "\" enregistré. Il apparaîtra automatiquement dans les filtres dès qu'une offre l'utilisera.";
        } else {
            $msg_err = 'Le nom du domaine ne peut pas être vide.';
        }
    }
}

/* on charge les demandes de filières en attente */
$demandes_en_attente = [];
$demandes_traitees   = [];
$filieres_existantes = [];

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* les demandes en attente */
    $q1 = mysqli_query($conn,
        "SELECT df.id_demande, df.filiere_demandee, df.justification, df.date_demande,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant, u.filiere AS filiere_actuelle
         FROM Demande_Filiere df
         JOIN Utilisateur u ON u.id = df.id_etudiant
         WHERE df.statut = 'en_attente'
         ORDER BY df.date_demande ASC"
    );
    while ($row = mysqli_fetch_assoc($q1)) $demandes_en_attente[] = $row;

    /* les 10 dernières demandes traitées */
    $q2 = mysqli_query($conn,
        "SELECT df.filiere_demandee, df.statut, df.date_traitement,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant
         FROM Demande_Filiere df
         JOIN Utilisateur u ON u.id = df.id_etudiant
         WHERE df.statut != 'en_attente'
         ORDER BY df.date_traitement DESC LIMIT 10"
    );
    while ($row = mysqli_fetch_assoc($q2)) $demandes_traitees[] = $row;

    /* les filières déjà utilisées dans les offres */
    $q3 = mysqli_query($conn,
        "SELECT DISTINCT filiere_ciblee FROM Offre_Stage
         WHERE filiere_ciblee IS NOT NULL ORDER BY filiere_ciblee"
    );
    while ($row = mysqli_fetch_row($q3)) $filieres_existantes[] = $row[0];

    mysqli_close($conn);
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domaines de stage — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy {
            border: 1px solid rgba(171,186,205,.4); border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem;
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
        <a href="gestion_stages.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Domaines de Stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Gérez les filières ciblées par les offres de stage</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- Demandes en attente -->
        <div class="col-12">
            <h2 class="section-title">Demandes en attente <?php if (!empty($demandes_en_attente)) : ?><span class="badge bg-warning text-dark rounded-pill ms-2"><?php echo count($demandes_en_attente); ?></span><?php endif; ?></h2>
            
            <div class="card-cy mt-2">
                <?php if (empty($demandes_en_attente)) : ?>
                    <div class="text-center p-4">
                        <i class="bi bi-inbox text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mb-0 fw-semibold">Aucune demande de filière en attente de validation.</p>
                    </div>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($demandes_en_attente as $d) : ?>
                            <div class="list-group-item px-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h6 class="fw-bold mb-1" style="color:var(--bleu);"><?php echo h($d['filiere_demandee']); ?></h6>
                                    <p class="text-muted mb-0" style="font-size:.8rem;">
                                        Demandée par <strong class="text-dark"><?php echo h($d['etudiant']); ?></strong> le <?php echo date('d/m/Y', strtotime($d['date_demande'])); ?>
                                    </p>
                                    <?php if ($d['justification']) : ?>
                                        <p class="text-secondary fst-italic mt-1 mb-0" style="font-size:.8rem; border-left: 2px solid #e5e7eb; padding-left: 10px;">"<?php echo h($d['justification']); ?>"</p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Boutons d'action -->
                                <div class="d-flex gap-2">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="approuver">
                                        <input type="hidden" name="id_demande" value="<?php echo (int)$d['id_demande']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-3">
                                            <i class="bi bi-check-lg me-1"></i> Approuver
                                        </button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="rejeter">
                                        <input type="hidden" name="id_demande" value="<?php echo (int)$d['id_demande']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3">
                                            <i class="bi bi-x-lg me-1"></i> Rejeter
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filières actives -->
        <div class="col-md-6">
            <h2 class="section-title">Filières référencées</h2>
            <div class="card-cy mt-2 h-100">
                <?php if (empty($filieres_existantes)) : ?>
                    <p class="text-muted fst-italic mb-0">Aucune filière référencée dans les offres pour le moment.</p>
                <?php else : ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($filieres_existantes as $f) : ?>
                            <span class="badge rounded-pill fw-normal" style="background-color: var(--bleu); font-size: .85rem; padding: 8px 15px;">
                                <?php echo h($f); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique -->
        <div class="col-md-6">
            <h2 class="section-title">Dernières demandes traitées</h2>
            <div class="card-cy mt-2 h-100">
                <?php if (empty($demandes_traitees)) : ?>
                    <p class="text-muted fst-italic mb-0">Aucun historique disponible.</p>
                <?php else : ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($demandes_traitees as $d) :
                            $cls = $d['statut'] === 'approuvee' ? 'text-success' : 'text-danger';
                            $icon = $d['statut'] === 'approuvee' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                        ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start border-0 pb-2">
                                <div>
                                    <div class="fw-bold" style="font-size: .9rem;"><i class="bi <?php echo $icon; ?> <?php echo $cls; ?> me-2"></i><?php echo h($d['filiere_demandee']); ?></div>
                                    <div class="text-muted ms-4" style="font-size: .75rem;"><?php echo h($d['etudiant']); ?> · le <?php echo date('d/m/Y', strtotime($d['date_traitement'])); ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>