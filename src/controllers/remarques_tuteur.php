<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn      = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$etudiants = [];
$remarques = [];
$msg_ok    = '';
$msg_err   = '';

/* Étudiant sélectionné */
$id_etu_sel = (int)($_GET['etudiant'] ?? 0);

/* Envoi d'une remarque */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $contenu    = trim($_POST['contenu']    ?? '');
    $id_etudiant = (int)($_POST['id_etudiant'] ?? 0);

    if (!empty($contenu) && $id_etudiant > 0) {
        /* Récupérer le dossier lié à cet étudiant */
        $sd = mysqli_prepare($conn,
            "SELECT d.num_dossier FROM Dossier_Stage d
             JOIN Stage s ON s.num_stage = d.num_stage
             WHERE s.id_etudiant = ? AND s.id_tuteur = ?
             ORDER BY d.date_creation DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($sd, 'ii', $id_etudiant, $_SESSION['id']);
        mysqli_stmt_execute($sd);
        $rdr = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
        mysqli_stmt_close($sd);

        if ($rdr) {
            $ins = mysqli_prepare($conn,
                "INSERT INTO Remarque (contenu, num_dossier, id_auteur) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'sii', $contenu, $rdr['num_dossier'], $_SESSION['id']);
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Remarque envoyée à l\'étudiant !';
                $id_etu_sel = $id_etudiant;
            }
            mysqli_stmt_close($ins);
        } else {
            $msg_err = 'Aucun dossier trouvé pour cet étudiant.';
        }
    } else {
        $msg_err = 'Veuillez remplir tous les champs.';
    }
}

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Liste des étudiants suivis par ce tuteur */
    $se = mysqli_prepare($conn,
    "SELECT e.id, e.nom, e.prenom, CONCAT(e.prenom, ' ', e.nom) AS nom_complet
     FROM Stage s
     JOIN Utilisateur e ON e.id = s.id_etudiant
     WHERE s.id_tuteur = ?
     GROUP BY e.id, e.nom, e.prenom
     ORDER BY e.nom, e.prenom"
);
    mysqli_stmt_bind_param($se, 'i', $_SESSION['id']);
    mysqli_stmt_execute($se);
    $re = mysqli_stmt_get_result($se);
    while ($row = mysqli_fetch_assoc($re)) $etudiants[] = $row;
    mysqli_stmt_close($se);

    /* Historique des remarques pour l'étudiant sélectionné */
    if ($id_etu_sel > 0) {
        $sr = mysqli_prepare($conn,
            "SELECT r.contenu, r.date_creation, u.nom, u.prenom, u.role_premier
             FROM Remarque r
             JOIN Dossier_Stage d ON d.num_dossier = r.num_dossier
             JOIN Stage s         ON s.num_stage   = d.num_stage
             JOIN Utilisateur u   ON u.id = r.id_auteur
             WHERE s.id_etudiant = ? AND s.id_tuteur = ?
             ORDER BY r.date_creation DESC LIMIT 20"
        );
        mysqli_stmt_bind_param($sr, 'ii', $id_etu_sel, $_SESSION['id']);
        mysqli_stmt_execute($sr);
        $rr = mysqli_stmt_get_result($sr);
        while ($row = mysqli_fetch_assoc($rr)) $remarques[] = $row;
        mysqli_stmt_close($sr);
    }

    mysqli_close($conn);
}

/* Trouver le nom de l'étudiant sélectionné */
$nom_etu_sel = '';
foreach ($etudiants as $e) {
    if ($e['id'] === $id_etu_sel) { $nom_etu_sel = $e['nom_complet']; break; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remarques — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        .etu-pill {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 6px 14px; border-radius: 20px;
            border: 1px solid var(--gris-border); background: var(--blanc);
            font-size: .80rem; font-weight: 600; cursor: pointer;
            text-decoration: none; color: var(--noir);
            transition: all .2s;
        }
        .etu-pill:hover, .etu-pill.actif {
            border-color: var(--bleu); background: var(--bleu); color: #fff;
        }
        .bubble {
            padding: 10px 13px; border-radius: 12px;
            font-size: .82rem; line-height: 1.5; max-width: 88%;
        }
        .bubble-tuteur {
            background: var(--bleu); color: #fff; border-bottom-right-radius: 4px;
            align-self: flex-end;
        }
        .bubble-autre {
            background: var(--gris-fond); color: var(--noir); border-bottom-left-radius: 4px;
            align-self: flex-start;
        }
        .chat-wrapper { display: flex; flex-direction: column; gap: 10px; }
    </style>
</head>
<body>
<div class="page anim">

    <header class="entete">
        <a href="accueil_tuteur.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Remarques</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <?php if ($msg_ok) : ?>
        <div style="background:rgba(22,163,74,.09); border:1px solid var(--vert); border-radius:8px; padding:9px 13px; font-size:.83rem; color:var(--vert); font-weight:600;">
            ✓ <?php echo htmlspecialchars($msg_ok); ?>
        </div>
        <?php endif; ?>
        <?php if ($msg_err) : ?>
        <div style="background:#fff0f0; border:1px solid #fca5a5; border-radius:8px; padding:9px 13px; font-size:.83rem; color:var(--rouge);">
            <?php echo htmlspecialchars($msg_err); ?>
        </div>
        <?php endif; ?>

        <?php if (empty($etudiants)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <h3>Aucun étudiant suivi</h3>
            <p>Affectez d'abord un étudiant à une offre de stage.</p>
        </div>

        <?php else : ?>

        <!-- Sélection de l'étudiant -->
        <p class="label-section">Sélectionner un étudiant</p>
        <div style="display:flex; flex-wrap:wrap; gap:7px;">
            <?php foreach ($etudiants as $e) : ?>
            <a href="remarques_tuteur.php?etudiant=<?php echo (int)$e['id']; ?>"
               class="etu-pill <?php echo $id_etu_sel === $e['id'] ? 'actif' : ''; ?>">
                <?php echo htmlspecialchars($e['nom_complet']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Formulaire d'envoi -->
        <p class="label-section">Remarques :</p>
        <div class="carte">
            <form method="POST" action="remarques_tuteur.php<?php echo $id_etu_sel ? '?etudiant=' . $id_etu_sel : ''; ?>">
                <?php if (!$id_etu_sel) : ?>
                <label style="font-size:.80rem; font-weight:600; color:var(--gris-texte); display:block; margin-bottom:4px;">Étudiant</label>
                <select name="id_etudiant" style="width:100%; padding:10px 12px; border:1px solid var(--gris-border); border-radius:8px; background:var(--gris-fond); font-family:'DM Sans',sans-serif; font-size:.87rem; color:var(--noir); outline:none; margin-bottom:10px; appearance:none;" required>
                    <option value="">-- Choisir un étudiant --</option>
                    <?php foreach ($etudiants as $e) : ?>
                    <option value="<?php echo (int)$e['id']; ?>"><?php echo htmlspecialchars($e['nom_complet']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php else : ?>
                <input type="hidden" name="id_etudiant" value="<?php echo $id_etu_sel; ?>">
                <?php endif; ?>

                <textarea class="textarea" name="contenu" rows="4"
                          placeholder="Écrivez votre remarque pour <?php echo htmlspecialchars($nom_etu_sel ?: 'l\'étudiant'); ?>…"></textarea>
                <button type="submit" class="btn" style="margin-top:10px;">Valider</button>
            </form>
        </div>

        <!-- Historique des échanges -->
        <?php if ($id_etu_sel && !empty($remarques)) : ?>
        <p class="label-section">Échanges avec <?php echo htmlspecialchars($nom_etu_sel); ?></p>
        <div class="carte">
            <div class="chat-wrapper">
                <?php foreach ($remarques as $rem) :
                    $est_tuteur = $rem['role_premier'] === 'Tuteur';
                ?>
                <div style="display:flex; flex-direction:column; align-items:<?php echo $est_tuteur ? 'flex-end' : 'flex-start'; ?>">
                    <p style="font-size:.70rem; color:var(--gris-texte); margin-bottom:3px; font-weight:600;">
                        <?php echo htmlspecialchars($rem['prenom'] . ' ' . $rem['nom']); ?>
                        · <?php echo date('d/m/Y', strtotime($rem['date_creation'])); ?>
                    </p>
                    <div class="bubble <?php echo $est_tuteur ? 'bubble-tuteur' : 'bubble-autre'; ?>">
                        <?php echo nl2br(htmlspecialchars($rem['contenu'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($id_etu_sel && empty($remarques)) : ?>
        <div class="etat-vide" style="padding:24px;">
            <p style="font-size:.83rem; color:var(--gris-texte);">Aucun échange pour cet étudiant pour l'instant.</p>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</div>
</body>
</html>