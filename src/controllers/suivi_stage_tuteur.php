<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn     = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$stages   = [];
$stage    = null; /* le stage sélectionné */
$remarques = [];
$msg_ok   = '';
$msg_err  = '';

/* ID du stage sélectionné dans l'URL */
$id_stage_sel = (int)($_GET['stage'] ?? 0);

/* MAJ du statut d'avancement */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'avancement' && $conn) {
    $num_stage   = (int)($_POST['num_stage'] ?? 0);
    $avancement  = min(100, max(0, (int)($_POST['avancement'] ?? 0)));
    $statut      = $_POST['statut'] ?? '';
    $statuts_ok  = ['en_attente', 'en_cours', 'termine', 'annule'];

    if ($num_stage > 0 && in_array($statut, $statuts_ok)) {
        $upd = mysqli_prepare($conn,
            "UPDATE Stage SET avancement = ?, statut = ? WHERE num_stage = ? AND id_tuteur = ?"
        );
        mysqli_stmt_bind_param($upd, 'isii', $avancement, $statut, $num_stage, $_SESSION['id']);
        if (mysqli_stmt_execute($upd)) {
            $msg_ok = 'Avancement mis à jour !';
            $id_stage_sel = $num_stage;
        }
        mysqli_stmt_close($upd);
    }
}

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Tous les stages dont le tuteur est responsable */
    $stmt = mysqli_prepare($conn,
        "SELECT s.num_stage, s.titre, s.statut, s.avancement, s.date_debut, s.date_fin,
                ent.nom_entreprise, ent.ville,
                CONCAT(e.prenom, ' ', e.nom) AS nom_etudiant, e.filiere, e.niveau
         FROM Stage s
         JOIN Utilisateur e   ON e.id  = s.id_etudiant
         JOIN Utilisateur ent ON ent.id = s.id_entreprise
         WHERE s.id_tuteur = ?
         ORDER BY s.date_debut DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $stages[] = $row;
    mysqli_stmt_close($stmt);

    /* Charger le détail du stage sélectionné */
    if ($id_stage_sel > 0) {
        foreach ($stages as $s) {
            if ($s['num_stage'] === $id_stage_sel) { $stage = $s; break; }
        }

        if ($stage) {
            /* les remarques du dossier lié */
            $sr = mysqli_prepare($conn,
                "SELECT r.contenu, r.date_creation, u.nom, u.prenom
                 FROM Remarque r
                 JOIN Dossier_Stage d ON d.num_dossier = r.num_dossier
                 JOIN Utilisateur u   ON u.id = r.id_auteur
                 WHERE d.num_stage = ?
                 ORDER BY r.date_creation DESC LIMIT 10"
            );
            mysqli_stmt_bind_param($sr, 'i', $id_stage_sel);
            mysqli_stmt_execute($sr);
            $rr = mysqli_stmt_get_result($sr);
            while ($row = mysqli_fetch_assoc($rr)) $remarques[] = $row;
            mysqli_stmt_close($sr);
        }
    }

    mysqli_close($conn);
}

$statuts_labels = [
    'en_attente' => ['En attente', 'badge-orange'],
    'en_cours'   => ['En cours',   'badge-bleu'],
    'termine'    => ['Terminé',    'badge-vert'],
    'annule'     => ['Annulé',     'badge-rouge'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Stage — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        .stage-item {
            background: var(--blanc); border: 1px solid var(--gris-border);
            border-radius: var(--radius); padding: 13px 14px;
            display: flex; align-items: center; gap: 12px;
            cursor: pointer; text-decoration: none; color: var(--noir);
            transition: transform .15s, box-shadow .15s;
        }
        .stage-item:hover, .stage-item.actif {
            border-color: var(--bleu); box-shadow: var(--shadow);
            transform: translateY(-1px);
        }
        .stage-item.actif { background: rgba(27,79,155,.04); }
        .stage-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif;
            font-size: .87rem; font-weight: 800; flex-shrink: 0;
        }
        .barre-prog-fond {
            height: 7px; border-radius: 4px; background: var(--gris-border); overflow: hidden; margin-top: 6px;
        }
        .barre-prog-valeur { height: 100%; border-radius: 4px; background: linear-gradient(90deg, var(--bleu), var(--bleu-clair)); transition: width .5s ease; }
        .select-field { width:100%; padding:10px 12px; border:1px solid var(--gris-border); border-radius:8px; background:var(--gris-fond); font-family:'DM Sans',sans-serif; font-size:.87rem; color:var(--noir); outline:none; appearance:none; cursor:pointer; }
        .select-field:focus { border-color:var(--bleu); }
        input[type=range] { width:100%; accent-color: var(--bleu); cursor:pointer; }
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
        <span class="entete-titre">Suivi de Stage</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <?php if ($msg_ok) : ?>
        <div style="background:rgba(22,163,74,.09); border:1px solid var(--vert); border-radius:8px; padding:9px 13px; font-size:.83rem; color:var(--vert); font-weight:600;">
            ✓ <?php echo htmlspecialchars($msg_ok); ?>
        </div>
        <?php endif; ?>

        <?php if (empty($stages)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            <h3>Aucun stage à suivre</h3>
            <p>Vous n'avez pas encore d'étudiant affecté.</p>
        </div>

        <?php else : ?>

        <!-- Liste des étudiants suivis -->
        <p class="label-section">Étudiants suivis (<?php echo count($stages); ?>)</p>
        <?php foreach ($stages as $s) :
            $initiales_etu = strtoupper(mb_substr(explode(' ', $s['nom_etudiant'])[0], 0, 1) . mb_substr(explode(' ', $s['nom_etudiant'])[1] ?? '?', 0, 1));
            [$st_label, $st_class] = $statuts_labels[$s['statut']] ?? [$s['statut'], 'badge-gris'];
        ?>
        <a href="suivi_stage_tuteur.php?stage=<?php echo (int)$s['num_stage']; ?>"
           class="stage-item <?php echo $id_stage_sel === $s['num_stage'] ? 'actif' : ''; ?>">
            <div class="stage-avatar"><?php echo $initiales_etu; ?></div>
            <div style="flex:1; min-width:0;">
                <p style="font-weight:700; font-size:.87rem; margin-bottom:2px;">
                    <?php echo htmlspecialchars($s['nom_etudiant']); ?>
                </p>
                <p style="font-size:.74rem; color:var(--gris-texte); margin-bottom:4px;">
                    <?php echo htmlspecialchars($s['titre']); ?>
                </p>
                <div class="barre-prog-fond">
                    <div class="barre-prog-valeur" style="width:<?php echo (int)$s['avancement']; ?>%"></div>
                </div>
            </div>
            <span class="badge <?php echo $st_class; ?>"><?php echo $st_label; ?></span>
        </a>
        <?php endforeach; ?>

        <?php if ($stage) : ?>

        <!-- Détail du stage sélectionné -->
        <p class="label-section" style="margin-top:6px;">Détail — <?php echo htmlspecialchars($stage['nom_etudiant']); ?></p>

        <div class="carte">
            <div style="display:flex; flex-direction:column; gap:7px; font-size:.84rem;">
                <div style="display:flex; gap:8px;">
                    <span style="color:var(--gris-texte); width:80px; flex-shrink:0;">Stage</span>
                    <span style="font-weight:700;"><?php echo htmlspecialchars($stage['titre']); ?></span>
                </div>
                <div style="display:flex; gap:8px;">
                    <span style="color:var(--gris-texte); width:80px; flex-shrink:0;">Entreprise</span>
                    <span style="font-weight:700;"><?php echo htmlspecialchars($stage['nom_entreprise']); ?><?php if ($stage['ville']) echo ' · ' . htmlspecialchars($stage['ville']); ?></span>
                </div>
                <?php if ($stage['date_debut']) : ?>
                <div style="display:flex; gap:8px;">
                    <span style="color:var(--gris-texte); width:80px; flex-shrink:0;">Début</span>
                    <span style="font-weight:700;"><?php echo date('d/m/Y', strtotime($stage['date_debut'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($stage['date_fin']) : ?>
                <div style="display:flex; gap:8px;">
                    <span style="color:var(--gris-texte); width:80px; flex-shrink:0;">Fin</span>
                    <span style="font-weight:700;"><?php echo date('d/m/Y', strtotime($stage['date_fin'])); ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex; gap:8px;">
                    <span style="color:var(--gris-texte); width:80px; flex-shrink:0;">Filière</span>
                    <span style="font-weight:700;"><?php echo htmlspecialchars($stage['filiere'] . ' ' . $stage['niveau']); ?></span>
                </div>
            </div>
        </div>

        <!-- Formulaire de mise à jour de l'avancement -->
        <p class="label-section">Mettre à jour l'avancement</p>
        <div class="carte">
            <form method="POST" action="suivi_stage_tuteur.php?stage=<?php echo $stage['num_stage']; ?>">
                <input type="hidden" name="action" value="avancement">
                <input type="hidden" name="num_stage" value="<?php echo $stage['num_stage']; ?>">

                <label style="font-size:.80rem; font-weight:600; color:var(--gris-texte); display:block; margin-bottom:4px;">
                    Avancement : <span id="val-av"><?php echo (int)$stage['avancement']; ?></span>%
                </label>
                <input type="range" name="avancement" min="0" max="100" step="5"
                       value="<?php echo (int)$stage['avancement']; ?>"
                       oninput="document.getElementById('val-av').textContent = this.value">

                <label style="font-size:.80rem; font-weight:600; color:var(--gris-texte); display:block; margin:12px 0 4px;">Statut</label>
                <select name="statut" class="select-field">
                    <?php foreach ($statuts_labels as $key => [$label, $class]) : ?>
                    <option value="<?php echo $key; ?>" <?php echo $stage['statut'] === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn" style="margin-top:12px;">Enregistrer</button>
            </form>
        </div>

        <!-- Échanges récents -->
        <?php if (!empty($remarques)) : ?>
        <p class="label-section">Échanges récents</p>
        <div class="carte">
            <?php foreach ($remarques as $rem) : ?>
            <div style="padding:9px 0; border-bottom:1px solid var(--gris-border);">
                <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                    <span style="font-weight:700; font-size:.83rem;">
                        <?php echo htmlspecialchars($rem['prenom'] . ' ' . $rem['nom']); ?>
                    </span>
                    <span style="font-size:.72rem; color:var(--gris-texte);">
                        <?php echo date('d/m/Y', strtotime($rem['date_creation'])); ?>
                    </span>
                </div>
                <p style="font-size:.82rem; color:var(--gris-texte); line-height:1.5;">
                    <?php echo nl2br(htmlspecialchars($rem['contenu'])); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
        <?php endif; ?>

    </div>
</div>
</body>
</html>