<?php
/* Démarrage de la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on recupère les filtres depuis l'url */
$q = trim($_GET['q'] ?? '');
$secteur = trim($_GET['secteur'] ?? '');

/* la connexion à la base de données */
$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');

$offres      = []; 
$favoris_ids = []; 
$secteurs    = []; 

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /*on récupère les favoris de l'étudiant connecté pour colorier les cœurs sur les cartes */
    $sf = mysqli_prepare($conn, "SELECT num_offre FROM Favori WHERE id_user = ?");
    mysqli_stmt_bind_param($sf, 'i', $_SESSION['id']);
    mysqli_stmt_execute($sf);
    $rf = mysqli_stmt_get_result($sf);
    while ($f = mysqli_fetch_row($rf)) {
        $favoris_ids[] = (int)$f[0];
    }
    mysqli_stmt_close($sf);

    /* on construit la requête SQL avec les filtres actifs */
    $sql    = "SELECT o.num_offre, o.titre, o.mission, o.competences,
                      o.filiere_ciblee, o.duree_semaines, o.date_debut,
                      u.nom_entreprise, u.secteur, u.ville
               FROM Offre_Stage o
               JOIN Utilisateur u ON u.id = o.id_entreprise
               WHERE o.statut = 'ouverte'";

    $params = [];
    $types  = '';

    /* on filtre par mot-clé */
        $sql .= " AND (o.titre LIKE ? OR o.mission LIKE ? OR u.nom_entreprise LIKE ?)";
        $like    = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types  .= 'sss';
    }

    /* puis on filtre par secteur */
    if ($secteur !== '') {
        $sql .= " AND u.secteur = ?";
        $params[] = $secteur;
        $types  .= 's';
    }

    /* et on limite à 50 résultats, qu'on trie par date de publication */
    $sql .= " ORDER BY o.date_publication DESC LIMIT 50";

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);

    /* on ajoute le champ 'fav' pour savoir si l'offre est en favori */
    while ($row = mysqli_fetch_assoc($r)) {
        $row['fav'] = in_array((int)$row['num_offre'], $favoris_ids);
        $offres[]   = $row;
    }
    mysqli_stmt_close($stmt);

    /* on récupère les secteurs distincts */
    $rs = mysqli_query($conn,
        "SELECT DISTINCT u.secteur FROM Offre_Stage o
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE o.statut = 'ouverte' AND u.secteur IS NOT NULL
         ORDER BY u.secteur"
    );
    while ($s = mysqli_fetch_row($rs)) {
        $secteurs[] = $s[0];
    }

    mysqli_close($conn);

// Fonction utilitaire pour sécuriser l'affichage HTML
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
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        /* Navbar */
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        /* Custom Elements */
        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06);
            background: #fff;
            transition: all 0.2s ease-in-out;
        }
        .card-cy:hover {
            box-shadow: 0 12px 24px rgba(27,79,155,.12);
            border-color: var(--bleu-clair);
            transform: translateY(-2px);
        }

        .chip-cy {
            display: inline-block; padding: 6px 16px; border-radius: 999px;
            background: #fff; border: 1px solid #e5e7eb; color: #4b5563;
            font-size: .85rem; font-weight: 600; text-decoration: none;
            transition: all 0.2s; white-space: nowrap;
        }
        .chip-cy:hover { background: #f0f4fa; color: var(--bleu); border-color: var(--bleu-clair); }
        .chip-cy.actif { background: var(--bleu); color: #fff; border-color: var(--bleu); }

        /* Toast stylisé */
        .toast-cy {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: #111827; color: #fff; padding: 12px 24px; border-radius: 999px;
            font-size: .9rem; font-weight: 600; opacity: 0; transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 1050; display: flex; align-items: center; gap: 8px;
        }
        .toast-cy.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast-cy.ok { background: #1B4F9B; }

        .btn-coeur {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #e5e7eb; background: #fff; color: #9ca3af;
            transition: all 0.2s;
        }
        .btn-coeur:hover { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
        .btn-coeur.actif { background: #fee2e2; border-color: #fca5a5; color: #ef4444; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_etudiant.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline">
                <i class="bi bi-mortarboard-fill me-2"></i>
                <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i>
                <span class="d-none d-sm-inline">Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">

    <!-- En-tête -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_etudiant.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Offres de stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Trouvez le stage qui correspond à vos ambitions</p>
        </div>
    </div>

    <!-- Formulaire de filtres -->
    <form method="GET" action="offres_etudiant.php" id="form-recherche" class="mb-4">
        
        <!-- Barre de recherche -->
        <div class="input-group input-group-lg mb-3 shadow-sm rounded-4 overflow-hidden">
            <span class="input-group-text bg-white border-end-0 text-muted px-4">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" name="q" id="champ-q" class="form-control border-start-0 ps-0" 
                   placeholder="Rechercher un poste, une mission ou une entreprise…" 
                   value="<?php echo h($q); ?>" autocomplete="off" style="font-size:.95rem;">
        </div>

        <!-- Chips Secteurs -->
        <?php if (!empty($secteurs)): ?>
        <div class="d-flex flex-wrap gap-2 mb-2">
            <a href="offres_etudiant.php" class="chip-cy <?php echo $secteur === '' ? 'actif' : ''; ?>">Tous les secteurs</a>
            <?php foreach ($secteurs as $s): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['secteur' => $s])); ?>"
                   class="chip-cy <?php echo $secteur === $s ? 'actif' : ''; ?>">
                    <?php echo h($s); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </form>

    <!-- Résultats trouvés -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <span class="text-muted fw-bold" style="font-size:.85rem;">
            <?php echo count($offres); ?> offre<?php echo count($offres) > 1 ? 's' : ''; ?> trouvée<?php echo count($offres) > 1 ? 's' : ''; ?>
        </span>
        <?php if ($q || $secteur): ?>
            <a href="offres_etudiant.php" class="text-decoration-none fw-bold" style="color:var(--bleu); font-size:.85rem;">
                <i class="bi bi-x-circle me-1"></i> Réinitialiser
            </a>
        <?php endif; ?>
    </div>

    <!-- Liste des offres -->
    <?php if (empty($offres)): ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-search text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucune offre correspondante</h5>
            <p class="text-muted mb-0">Essayez de modifier vos critères de recherche ou de retirer des filtres.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($offres as $o):
                $desc_courte = mb_strlen($o['mission'] ?? '') > 115
                               ? mb_substr($o['mission'], 0, 115) . '…'
                               : ($o['mission'] ?? '');
                $duree = $o['duree_semaines'] ? round($o['duree_semaines'] / 4) . ' mois' : '';
                $techs = array_slice(array_filter(array_map('trim', explode(',', $o['competences'] ?? ''))), 0, 3);
            ?>
                <div class="card-cy p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:var(--bleu);">
                                <a href="detail_offre.php?id=<?php echo (int)$o['num_offre']; ?>" class="text-decoration-none text-reset">
                                    <?php echo h($o['titre']); ?>
                                </a>
                            </h5>
                            <p class="text-muted fw-semibold mb-0" style="font-size:.85rem;">
                                <i class="bi bi-building me-1"></i> <?php echo h($o['nom_entreprise']); ?>
                            </p>
                        </div>
                        <button class="btn btn-coeur <?php echo $o['fav'] ? 'actif' : ''; ?>" data-id="<?php echo (int)$o['num_offre']; ?>" aria-label="Favori">
                            <i class="bi <?php echo $o['fav'] ? 'bi-heart-fill' : 'bi-heart'; ?> fs-5"></i>
                        </button>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3 mt-2">
                        <?php if ($o['filiere_ciblee']): ?>
                            <span class="badge rounded-pill text-white" style="background-color: var(--bleu); font-weight: 500; font-size:.72rem;">
                                <?php echo h($o['filiere_ciblee']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($duree): ?>
                            <span class="badge rounded-pill bg-success text-white" style="font-weight: 500; font-size:.72rem;">
                                <?php echo $duree; ?>
                            </span>
                        <?php endif; ?>
                        <?php foreach ($techs as $t): ?>
                            <span class="badge rounded-pill bg-light text-dark border" style="font-weight: 500; font-size:.72rem;">
                                <?php echo h($t); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($desc_courte): ?>
                        <p class="text-secondary mb-3" style="font-size: .88rem; line-height: 1.5;">
                            <?php echo h($desc_courte); ?>
                        </p>
                    <?php endif; ?>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: .8rem; font-weight: 600;">
                            <?php if ($o['ville']): ?>
                                <span><i class="bi bi-geo-alt me-1"></i> <?php echo h($o['ville']); ?></span>
                            <?php endif; ?>
                            <?php if ($o['date_debut']): ?>
                                <span><i class="bi bi-calendar-event me-1"></i> Dès le <?php echo date('d/m/Y', strtotime($o['date_debut'])); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="detail_offre.php?id=<?php echo (int)$o['num_offre']; ?>" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" style="background:var(--bleu); border:none;">
                            Voir l'offre
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Toast Notifications -->
<div class="toast-cy" id="toast">
    <i class="bi" id="toast-icon"></i>
    <span id="toast-text"></span>
</div>

<script>
    /* Recherche en temps réel avec délai */
    var delai;
    document.getElementById('champ-q').addEventListener('input', function () {
        clearTimeout(delai);
        delai = setTimeout(function () {
            document.getElementById('form-recherche').submit();
        }, 1000);
    });

    /* Gestion des favoris via AJAX */
    document.querySelectorAll('.btn-coeur').forEach(function (btn) {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            var id     = this.dataset.id;
            var actif  = this.classList.contains('actif');
            var action = actif ? 'remove' : 'add';
            var icon   = this.querySelector('i');

            try {
                var reponse = await fetch('api_favori.php', {
                    method  : 'POST',
                    headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body    : 'num_offre=' + id + '&action=' + action
                });
                var data = await reponse.json();

                if (data.success) {
                    this.classList.toggle('actif');
                    
                    if (action === 'add') {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill');
                    } else {
                        icon.classList.remove('bi-heart-fill');
                        icon.classList.add('bi-heart');
                    }

                    afficherToast(action === 'add' ? 'Ajouté à vos favoris' : 'Retiré de vos favoris', action === 'add');
                }
            } catch (err) {
                afficherToast('Erreur réseau…', false);
            }
        });
    });

    /* Affichage du toast */
    function afficherToast(msg, isSuccess) {
        var t = document.getElementById('toast');
        var tText = document.getElementById('toast-text');
        var tIcon = document.getElementById('toast-icon');

        tText.textContent = msg;
        t.className = 'toast-cy show ' + (isSuccess ? 'ok' : '');
        tIcon.className = isSuccess ? 'bi bi-check-circle-fill' : 'bi bi-info-circle-fill';

        setTimeout(function () { t.classList.remove('show'); }, 2500);
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>