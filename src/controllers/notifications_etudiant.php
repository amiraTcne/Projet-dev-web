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

    // Si requête AJAX renvoyer JSON, sinon redirect
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

/* ── Config icônes et couleurs par type ── */
$type_cfg = [
    'candidature_validee' => [
        'icon'  => '<polyline points="20 6 9 17 4 12"/>',
        'color' => '#16a34a',
        'bg'    => 'rgba(22,163,74,.12)',
        'badge' => 'Acceptée',
        'badge_class' => 'badge-vert',
    ],
    'candidature_refusee' => [
        'icon'  => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'color' => '#dc2626',
        'bg'    => 'rgba(220,38,38,.10)',
        'badge' => 'Refusée',
        'badge_class' => 'badge-rouge',
    ],
    'stage_cree' => [
        'icon'  => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>',
        'color' => '#1B4F9B',
        'bg'    => 'rgba(27,79,155,.12)',
        'badge' => 'Stage créé',
        'badge_class' => 'badge-bleu',
    ],
    'remarque' => [
        'icon'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'color' => '#d97706',
        'bg'    => 'rgba(217,119,6,.12)',
        'badge' => 'Remarque',
        'badge_class' => 'badge-orange',
    ],
    'autre' => [
        'icon'  => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'color' => '#6b7280',
        'bg'    => 'rgba(107,114,128,.10)',
        'badge' => 'Info',
        'badge_class' => 'badge-gris',
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — CY Stage</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        /* ── Notif card ── */
        .notif-card {
            background: var(--blanc);
            border: 1px solid var(--gris-border);
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-bottom: 10px;
            display: flex; gap: 13px; align-items: flex-start;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, opacity .15s;
            position: relative;
        }
        .notif-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(27,79,155,.12); }
        .notif-card.non-lue {
            border-left: 3px solid var(--bleu);
            background: rgba(27,79,155,.025);
        }
        .notif-card.lue { opacity: .75; }

        .notif-icon-wrap {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .notif-icon-wrap svg {
            width: 16px; height: 16px; fill: none; stroke: currentColor;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }

        .notif-body { flex: 1; min-width: 0; }
        .notif-titre {
            font-weight: 700; font-size: .89rem; line-height: 1.3;
            margin-bottom: 4px;
        }
        .notif-msg {
            font-size: .80rem; color: var(--gris-texte); line-height: 1.5;
            margin-bottom: 6px;
        }
        .notif-footer {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        }
        .notif-date { font-size: .71rem; color: var(--gris-texte); }

        /* Badge point non lu */
        .dot-non-lu {
            position: absolute; top: 14px; right: 14px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--bleu);
        }

        /* Bouton action dans notif */
        .notif-action-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 11px; border-radius: 20px;
            background: var(--bleu); color: #fff;
            font-size: .72rem; font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            transition: opacity .2s;
        }
        .notif-action-btn:hover { opacity: .85; }

        /* Badges */
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            font-size: .69rem; font-weight: 700;
        }
        .badge-vert   { background: rgba(22,163,74,.12);  color: #16a34a; }
        .badge-rouge  { background: rgba(220,38,38,.10);  color: #dc2626; }
        .badge-bleu   { background: rgba(27,79,155,.10);  color: #1B4F9B; }
        .badge-orange { background: rgba(217,119,6,.12);  color: #d97706; }
        .badge-gris   { background: rgba(107,114,128,.10); color: #6b7280; }

        /* Header compteur */
        .notif-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px; flex-wrap: wrap; gap: 8px;
        }
        .nb-badge {
            background: var(--bleu); color: #fff;
            padding: 3px 10px; border-radius: 20px;
            font-size: .78rem; font-weight: 700; margin-left: 8px;
        }
        .btn-tout-lire {
            padding: 6px 14px; border: 1px solid var(--gris-border);
            border-radius: 20px; background: var(--blanc);
            font-family: 'DM Sans', sans-serif; font-weight: 600;
            font-size: .78rem; color: var(--gris-texte); cursor: pointer;
            transition: background .15s, color .15s;
        }
        .btn-tout-lire:hover { background: var(--gris-fond); color: var(--noir); }
    </style>
</head>
<body>
<div class="page anim">

    <header class="entete">
        <a href="accueil_etudiant.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Notifications</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <!-- Header -->
        <div class="notif-header">
            <p style="font-size:.82rem; font-weight:700;">
                Vos notifications
                <?php if ($nb_non_lues > 0) : ?>
                <span class="nb-badge"><?php echo $nb_non_lues; ?></span>
                <?php endif; ?>
            </p>
            <?php if ($nb_non_lues > 0) : ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="tout_lire" value="1">
                <button type="submit" class="btn-tout-lire">Tout marquer comme lu</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <h3>Aucune notification</h3>
            <p>Vous serez informé ici des mises à jour de vos candidatures.</p>
        </div>

        <?php else : ?>
        <?php foreach ($notifications as $n) :
            $cfg      = $type_cfg[$n['type']] ?? $type_cfg['autre'];
            $est_lue  = (bool)$n['lu'];
            $date_str = '';
            $ts       = strtotime($n['date_creation']);
            $now      = time();
            $diff     = $now - $ts;
            if ($diff < 3600)       $date_str = round($diff / 60) . ' min';
            elseif ($diff < 86400)  $date_str = round($diff / 3600) . ' h';
            elseif ($diff < 604800) $date_str = round($diff / 86400) . ' j';
            else                    $date_str = date('d/m/Y', $ts);
        ?>
        <div class="notif-card <?php echo $est_lue ? 'lue' : 'non-lue'; ?>"
             id="notif-<?php echo (int)$n['id_notif']; ?>"
             data-id="<?php echo (int)$n['id_notif']; ?>"
             data-lien="<?php echo htmlspecialchars($n['lien'] ?? '', ENT_QUOTES); ?>"
             onclick="lireNotif(this)">

            <!-- Icône -->
            <div class="notif-icon-wrap"
                 style="background:<?php echo $cfg['bg']; ?>; color:<?php echo $cfg['color']; ?>">
                <svg viewBox="0 0 24 24">
                    <?php echo $cfg['icon']; ?>
                </svg>
            </div>

            <!-- Corps -->
            <div class="notif-body">
                <p class="notif-titre"><?php echo htmlspecialchars($n['titre']); ?></p>
                <p class="notif-msg"><?php echo htmlspecialchars($n['message']); ?></p>
                <div class="notif-footer">
                    <span class="badge <?php echo $cfg['badge_class']; ?>">
                        <?php echo $cfg['badge']; ?>
                    </span>
                    <span class="notif-date"><?php echo $date_str; ?></span>

                    <!-- Bouton d'action si offre validée et non encore confirmée -->
                    <?php if ($n['type'] === 'candidature_validee' && !$est_lue && $n['lien']) : ?>
                    <a href="<?php echo htmlspecialchars($n['lien']); ?>"
                       class="notif-action-btn"
                       onclick="event.stopPropagation()">
                        Voir l'offre →
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Point non lu -->
            <?php if (!$est_lue) : ?>
            <div class="dot-non-lu"></div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<script>
/**
 * Clic sur une notif :
 * 1. Appel AJAX pour marquer comme lue
 * 2. Retire le style "non-lue"
 * 3. Redirige si lien présent
 */
async function lireNotif(el) {
    const id   = el.dataset.id;
    const lien = el.dataset.lien;

    // Marquer visuellement
    el.classList.remove('non-lue');
    el.classList.add('lue');
    const dot = el.querySelector('.dot-non-lu');
    if (dot) dot.remove();

    // AJAX
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

    // Rediriger si lien
    if (lien) {
        setTimeout(() => { window.location.href = lien; }, 150);
    }
}
</script>

</body>
</html>