<?php
/**
 * notifications_etudiant.php
 * ─────────────────────────────────────────────────────────────
 * Espace notifications de l'étudiant.
 * Affiche toutes ses notifications (lues et non lues).
 * - Clic sur une notif → marque comme lue + redirige vers lien
 * - Bouton "Tout marquer comme lu"
 * ─────────────────────────────────────────────────────────────
 */
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$id_etudiant = (int)$_SESSION['id'];
$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
if (!$conn) die("Connexion DB échouée.");
mysqli_set_charset($conn, 'utf8mb4');

/* ── Marquer une notif comme lue (AJAX ou redirect) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marquer_lu'])) {
    $id_notif = (int)$_POST['id_notif'];
    $stmt = mysqli_prepare($conn,
        "UPDATE Notification SET lu = 1 WHERE id_notif = ? AND id_user = ?"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $id_notif, $id_etudiant);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit();
    }

    $lien = trim($_POST['lien'] ?? '');
    if ($lien) {
        header('Location: ' . $lien);
    } else {
        header('Location: notifications_etudiant.php');
    }
    exit();
}

/* ── Tout marquer comme lu ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tout_lire'])) {
    $stmt = mysqli_prepare($conn,
        "UPDATE Notification SET lu = 1 WHERE id_user = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_etudiant);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: notifications_etudiant.php');
    exit();
}

/* ── Chargement des notifications ── */
$notifications = [];
$stmt = mysqli_prepare($conn,
    "SELECT id_notif, type, titre, message, lien, lu, date_creation
     FROM Notification
     WHERE id_user = ?
     ORDER BY lu ASC, date_creation DESC
     LIMIT 50"
);
mysqli_stmt_bind_param($stmt, 'i', $id_etudiant);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($r)) $notifications[] = $row;
mysqli_stmt_close($stmt);

$nb_non_lues = count(array_filter($notifications, fn($n) => !$n['lu']));

mysqli_close($conn);

/* ── Config Bootstrap Icons et Couleurs par type ── */
$type_cfg = [
    'candidature_validee' => [
        'icon'  => 'bi-check-circle',
        'color' => 'text-success',
        'bg'    => 'bg-success-subtle',
        'badge' => 'Acceptée',
        'badge_class' => 'bg-success text-white',
    ],
    'candidature_refusee' => [
        'icon'  => 'bi-x-circle',
        'color' => 'text-danger',
        'bg'    => 'bg-danger-subtle',
        'badge' => 'Refusée',
        'badge_class' => 'bg-danger text-white',
    ],
    'stage_cree' => [
        'icon'  => 'bi-briefcase',
        'color' => 'text-primary',
        'bg'    => 'bg-primary-subtle',
        'badge' => 'Stage créé',
        'badge_class' => 'bg-primary text-white',
    ],
    'remarque' => [
        'icon'  => 'bi-chat-left-dots',
        'color' => 'text-warning',
        'bg'    => 'bg-warning-subtle',
        'badge' => 'Remarque',
        'badge_class' => 'bg-warning text-dark',
    ],
    'autre' => [
        'icon'  => 'bi-info-circle',
        'color' => 'text-secondary',
        'bg'    => 'bg-secondary-subtle',
        'badge' => 'Info',
        'badge_class' => 'bg-secondary text-white',
    ],
];

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
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        .notif-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1.2rem;
            margin-bottom: 0.8rem;
            display: flex; gap: 15px; align-items: flex-start;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        .notif-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(27,79,155,.12);
            border-color: var(--bleu-clair);
        }
        .notif-card.non-lue {
            border-left: 4px solid var(--bleu);
            background: rgba(27,79,155,.02);
        }
        .notif-card.lue { opacity: 0.8; }

        .icon-wrap {
            width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 1.4rem;
        }

        .dot-non-lu {
            position: absolute; top: 15px; right: 15px;
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--bleu);
            box-shadow: 0 0 0 3px rgba(27,79,155,.2);
        }
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

<div class="container mb-5" style="max-width:800px;">

    <!-- En-tête -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_etudiant.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div class="flex-grow-1">
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Notifications</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Suivez l'état de vos candidatures et de votre stage</p>
        </div>
    </div>

    <!-- Contrôles et Compteur[cite: 14] -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div class="fw-bold text-dark d-flex align-items-center gap-2">
            Vos notifications
            <?php if ($nb_non_lues > 0): ?>
                <span class="badge rounded-pill bg-primary"><?php echo $nb_non_lues; ?> nouvelle(s)</span>
            <?php endif; ?>
        </div>

        <?php if ($nb_non_lues > 0): ?>
            <form method="POST" class="m-0">
                <input type="hidden" name="tout_lire" value="1">
                <button type="submit" class="btn btn-light border btn-sm rounded-pill fw-semibold text-muted">
                    <i class="bi bi-check2-all me-1"></i> Tout marquer comme lu
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Liste des notifications[cite: 14] -->
    <?php if (empty($notifications)): ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-bell-slash text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucune notification</h5>
            <p class="text-muted mb-0">Vous serez informé ici des mises à jour importantes.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column">
            <?php foreach ($notifications as $n):
                $cfg = $type_cfg[$n['type']] ?? $type_cfg['autre'];
                $est_lue = (bool)$n['lu'];
                
                // Formattage date relative[cite: 14]
                $ts = strtotime($n['date_creation']);
                $diff = time() - $ts;
                if ($diff < 3600)       $date_str = round($diff / 60) . ' min';
                elseif ($diff < 86400)  $date_str = round($diff / 3600) . ' h';
                elseif ($diff < 604800) $date_str = round($diff / 86400) . ' j';
                else                    $date_str = date('d/m/Y', $ts);
            ?>
            <div class="notif-card <?php echo $est_lue ? 'lue' : 'non-lue'; ?>"
                 id="notif-<?php echo (int)$n['id_notif']; ?>"
                 data-id="<?php echo (int)$n['id_notif']; ?>"
                 data-lien="<?php echo h($n['lien'] ?? ''); ?>"
                 onclick="lireNotif(this)">

                <div class="icon-wrap <?php echo $cfg['bg']; ?> <?php echo $cfg['color']; ?>">
                    <i class="bi <?php echo $cfg['icon']; ?>"></i>
                </div>

                <div class="flex-grow-1 pe-4">
                    <h6 class="fw-bold mb-1" style="color:#111827;"><?php echo h($n['titre']); ?></h6>
                    <p class="text-secondary mb-2" style="font-size:.85rem; line-height: 1.5;"><?php echo h($n['message']); ?></p>
                    
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge rounded-pill <?php echo $cfg['badge_class']; ?> px-2 py-1" style="font-size:.7rem;">
                            <?php echo $cfg['badge']; ?>
                        </span>
                        <span class="text-muted fw-semibold" style="font-size:.75rem;"><i class="bi bi-clock me-1"></i> Il y a <?php echo $date_str; ?></span>
                        
                        <?php if ($n['type'] === 'candidature_validee' && !$est_lue && $n['lien']): ?>
                            <a href="<?php echo h($n['lien']); ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-0 ms-auto fw-bold" style="font-size:.75rem;" onclick="event.stopPropagation()">
                                Voir l'offre <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$est_lue): ?>
                    <div class="dot-non-lu"></div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
/**
 * Clic sur une notif : AJAX marque lue + redirect[cite: 14]
 */
async function lireNotif(el) {
    const id = el.dataset.id;
    const lien = el.dataset.lien;

    // Mise à jour visuelle instantanée
    if(el.classList.contains('non-lue')) {
        el.classList.remove('non-lue');
        el.classList.add('lue');
        const dot = el.querySelector('.dot-non-lu');
        if (dot) dot.remove();

        // Réduire le compteur global (facultatif visuellement)
        let badgeHeader = document.querySelector('.badge.bg-primary');
        if(badgeHeader) {
            let count = parseInt(badgeHeader.textContent);
            if(count > 1) {
                badgeHeader.textContent = (count - 1) + " nouvelle(s)";
            } else {
                badgeHeader.remove();
                document.querySelector('form.m-0').remove(); // retire le bouton "Tout marquer lu"
            }
        }
    }

    const fd = new FormData();
    fd.append('marquer_lu', '1');
    fd.append('id_notif', id);
    if (lien) fd.append('lien', lien);

    try {
        await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
    } catch(e) {}

    if (lien) {
        setTimeout(() => { window.location.href = lien; }, 150);
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>