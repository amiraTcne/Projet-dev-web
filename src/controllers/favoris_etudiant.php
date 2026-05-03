<?php
/* Démarrage de la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$favoris = [];

/* on charge les favoris depuis la base de données */
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT o.num_offre, o.titre, o.mission, o.competences,
                o.filiere_ciblee, o.duree_semaines, o.date_debut,
                u.nom_entreprise, u.secteur, u.ville,
                f.date_ajout
         FROM Favori f
         JOIN Offre_Stage o ON o.num_offre = f.num_offre
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE f.id_user = ?
         ORDER BY f.date_ajout DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) {
        $favoris[] = $row;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris — CY Stage</title>
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
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06);
            background: #fff;
            transition: all 0.3s ease-in-out;
        }
        .card-cy:hover {
            box-shadow: 0 12px 24px rgba(27,79,155,.12);
            border-color: var(--bleu-clair);
            transform: translateY(-2px);
        }

        .btn-coeur {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #fca5a5; background: #fee2e2; color: #ef4444;
            transition: all 0.2s;
        }
        .btn-coeur:hover { background: #fecaca; color: #dc2626; transform: scale(1.05); }

        /* Animation pour retirer un favori */
        .fade-out {
            opacity: 0;
            transform: translateX(30px) !important;
        }

        /* Toast stylisé */
        .toast-cy {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: #111827; color: #fff; padding: 12px 24px; border-radius: 999px;
            font-size: .9rem; font-weight: 600; opacity: 0; transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 1050; display: flex; align-items: center; gap: 8px;
        }
        .toast-cy.show { transform: translateX(-50%) translateY(0); opacity: 1; }
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
                <i class="bi bi-mortarboard-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i> <span class="d-none d-sm-inline">Déconnexion</span>
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
        <div class="flex-grow-1">
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Offres en favoris</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Vos annonces sauvegardées</p>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <span class="text-muted fw-bold" style="font-size:.85rem;">
            <?php echo count($favoris); ?> offre<?php echo count($favoris) > 1 ? 's' : ''; ?> sauvegardée<?php echo count($favoris) > 1 ? 's' : ''; ?>
        </span>
        <?php if (!empty($favoris)) : ?>
            <a href="offres_etudiant.php" class="text-decoration-none fw-bold" style="color:var(--bleu); font-size:.85rem;">
                <i class="bi bi-search me-1"></i> Explorer d'autres offres
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($favoris)) : ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-heart text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucun favori pour l'instant</h5>
            <p class="text-muted mb-4">Parcours les offres et clique sur l'icône cœur pour les sauvegarder ici.</p>
            <a href="offres_etudiant.php" class="btn btn-primary rounded-pill fw-bold px-4" style="background:var(--bleu); border:none;">
                Parcourir les offres
            </a>
        </div>
    <?php else : ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($favoris as $o) :
                $duree = $o['duree_semaines'] ? round($o['duree_semaines'] / 4) . ' mois' : '';
                $techs = array_slice(array_filter(array_map('trim', explode(',', $o['competences'] ?? ''))), 0, 3);
            ?>
            <div class="card-cy p-4" id="fav-<?php echo (int)$o['num_offre']; ?>">
                
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:var(--bleu);">
                            <a href="detail_offre.php?id=<?php echo (int)$o['num_offre']; ?>" class="text-decoration-none text-reset">
                                <?php echo h($o['titre']); ?>
                            </a>
                        </h5>
                        <p class="text-muted fw-semibold mb-0" style="font-size:.85rem;">
                            <i class="bi bi-building me-1"></i> <?php echo h($o['nom_entreprise']); ?>
                            <?php if ($o['ville']) : ?> — <?php echo h($o['ville']); ?><?php endif; ?>
                        </p>
                    </div>
                    <!-- Bouton pour retirer des favoris -->
                    <button class="btn-coeur" data-id="<?php echo (int)$o['num_offre']; ?>" aria-label="Retirer des favoris">
                        <i class="bi bi-heart-fill fs-5"></i>
                    </button>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3 mt-2">
                    <?php if ($o['filiere_ciblee']) : ?>
                        <span class="badge rounded-pill text-white" style="background-color: var(--bleu); font-weight: 500; font-size:.72rem;">
                            <?php echo h($o['filiere_ciblee']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($duree) : ?>
                        <span class="badge rounded-pill bg-success text-white" style="font-weight: 500; font-size:.72rem;">
                            <?php echo $duree; ?>
                        </span>
                    <?php endif; ?>
                    <?php foreach ($techs as $t) : ?>
                        <span class="badge rounded-pill bg-light text-dark border" style="font-weight: 500; font-size:.72rem;">
                            <?php echo h($t); ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <span class="text-muted" style="font-size:.75rem; font-weight:600;">
                        Sauvegardé le <?php echo date('d/m/Y', strtotime($o['date_ajout'])); ?>
                    </span>
                    <a href="detail_offre.php?id=<?php echo (int)$o['num_offre']; ?>" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" style="background:var(--bleu); border:none;">
                        Voir l'offre
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Toast Notification -->
<div class="toast-cy" id="toast">
    <i class="bi bi-info-circle-fill"></i>
    <span id="toast-text"></span>
</div>

<script>
    /* Retrait asynchrone des favoris avec animation */
    document.querySelectorAll('.btn-coeur').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var id   = this.dataset.id;
            var card = document.getElementById('fav-' + id);

            try {
                var r = await fetch('api_favori.php', {
                    method  : 'POST',
                    headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body    : 'num_offre=' + id + '&action=remove'
                });
                var d = await r.json();

                if (d.success) {
                    /* Animation CSS de disparition */
                    card.classList.add('fade-out');

                    /* Suppression du DOM */
                    setTimeout(function () { 
                        card.remove(); 
                        // S'il n'y a plus de favoris, on recharge pour afficher l'état vide
                        if(document.querySelectorAll('.card-cy').length === 0) {
                            window.location.reload();
                        }
                    }, 300);

                    /* Affichage du toast */
                    var t = document.getElementById('toast');
                    document.getElementById('toast-text').textContent = 'Retiré des favoris';
                    t.classList.add('show');
                    setTimeout(function () { t.classList.remove('show'); }, 2500);
                }
            } catch (e) {
                console.error('Erreur lors du retrait du favori :', e);
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>