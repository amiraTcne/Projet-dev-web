<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$dossiers = [];
$msg_ok  = '';
$msg_err = '';

/* Valider ou refuser une convention */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $num_dossier = (int)($_POST['num_dossier'] ?? 0);
    $action      = $_POST['action_val'] ?? '';

    if ($num_dossier > 0 && in_array($action, ['valider', 'refuser'])) {
        $commentaire = trim($_POST['commentaire'] ?? '');

        /* Vérifier que le dossier appartient bien à un des étudiants de ce tuteur */
        $chk = mysqli_prepare($conn,
            "SELECT d.num_dossier FROM Dossier_Stage d
             JOIN Stage s ON s.num_stage = d.num_stage
             WHERE d.num_dossier = ? AND s.id_tuteur = ?"
        );
        mysqli_stmt_bind_param($chk, 'ii', $num_dossier, $_SESSION['id']);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $ok = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);

        if ($ok) {
            /* Insérer la validation */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Validation_Convention (num_dossier, validee_par, id_validateur, commentaire)
                 VALUES (?, 'tuteur', ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'iis', $num_dossier, $_SESSION['id'], $commentaire);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            /* Mettre à jour le statut du dossier */
            $nouveau_statut = $action === 'valider' ? 'valide' : 'rejete';
            $upd = mysqli_prepare($conn,
                "UPDATE Dossier_Stage SET statut = ?, date_modification = NOW() WHERE num_dossier = ?"
            );
            mysqli_stmt_bind_param($upd, 'si', $nouveau_statut, $num_dossier);
            if (mysqli_stmt_execute($upd)) {
                $msg_ok = $action === 'valider' ? 'Convention validée ✓' : 'Convention refusée.';
            }
            mysqli_stmt_close($upd);
        } else {
            $msg_err = 'Action non autorisée.';
        }
    }
}

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Tous les dossiers des étudiants suivis par ce tuteur */
    $stmt = mysqli_prepare($conn,
        "SELECT d.num_dossier, d.statut, d.convention_url, d.date_modification,
                s.titre AS titre_stage, s.date_debut, s.date_fin,
                CONCAT(e.prenom, ' ', e.nom) AS nom_etudiant,
                ent.nom_entreprise,
                (SELECT COUNT(*) FROM Validation_Convention vc
                 WHERE vc.num_dossier = d.num_dossier AND vc.validee_par = 'tuteur') AS deja_valide
         FROM Dossier_Stage d
         JOIN Stage s ON s.num_stage = d.num_stage
         JOIN Utilisateur e   ON e.id  = s.id_etudiant
         JOIN Utilisateur ent ON ent.id = s.id_entreprise
         WHERE s.id_tuteur = ?
         ORDER BY d.date_creation DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $dossiers[] = $row;
    mysqli_stmt_close($stmt);

    mysqli_close($conn);
}

$statuts_labels = [
    'incomplet' => ['Incomplet', 'badge-rouge'],
    'en_cours'  => ['En cours',  'badge-orange'],
    'soumis'    => ['Soumis',    'badge-bleu'],
    'valide'    => ['Validé ✓',  'badge-vert'],
    'rejete'    => ['Refusé',    'badge-rouge'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valider Conventions — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        .convention-card {
            background: var(--blanc); border: 1px solid var(--gris-border);
            border-radius: var(--radius); padding: 15px;
            box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 11px;
        }
        .row-info { display: flex; gap: 8px; align-items: center; font-size: .84rem; }
        .row-label { color: var(--gris-texte); font-weight: 500; width: 88px; flex-shrink: 0; }
        .btn-valider {
            flex: 1; padding: 10px; border: none; border-radius: 8px;
            background: var(--vert); color: #fff; font-family: 'DM Sans', sans-serif;
            font-weight: 700; font-size: .87rem; cursor: pointer; display: flex;
            align-items: center; justify-content: center; gap: 6px; transition: opacity .2s;
        }
        .btn-valider:hover { opacity: .85; }
        .btn-refuser {
            flex: 1; padding: 10px; border: 1px solid var(--gris-border); border-radius: 8px;
            background: var(--blanc); color: var(--rouge); font-family: 'DM Sans', sans-serif;
            font-weight: 700; font-size: .87rem; cursor: pointer; display: flex;
            align-items: center; justify-content: center; gap: 6px; transition: all .2s;
        }
        .btn-refuser:hover { border-color: var(--rouge); background: rgba(220,38,38,.04); }
        .btn-dl-small {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 11px; border: 1px solid var(--bleu);
            background: var(--blanc); color: var(--bleu);
            border-radius: 20px; font-size: .72rem; font-weight: 700;
            cursor: pointer; text-decoration: none; font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }
        .btn-dl-small:hover { background: var(--bleu); color: #fff; }
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
        <span class="entete-titre">Valider conventions</span>
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

        <?php if (empty($dossiers)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            <h3>Aucune convention à valider</h3>
            <p>Vous n'avez pas encore d'étudiants avec un dossier actif.</p>
        </div>

        <?php else : ?>

        <p style="font-size:.79rem; color:var(--gris-texte); font-weight:600;">
            <?php echo count($dossiers); ?> dossier<?php echo count($dossiers) > 1 ? 's' : ''; ?>
        </p>

        <?php foreach ($dossiers as $d) :
            [$st_label, $st_class] = $statuts_labels[$d['statut']] ?? [$d['statut'], 'badge-gris'];
        ?>

        <div class="convention-card">

            <!-- Nom de l'étudiant + statut -->
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <p style="font-family:'Syne',sans-serif; font-weight:700; font-size:.94rem;">
                    <?php echo htmlspecialchars($d['nom_etudiant']); ?>
                </p>
                <span class="badge <?php echo $st_class; ?>"><?php echo $st_label; ?></span>
            </div>

            <!-- Infos du stage -->
            <div style="display:flex; flex-direction:column; gap:5px;">
                <div class="row-info">
                    <span class="row-label">Nom</span>
                    <span style="font-weight:700;"><?php echo htmlspecialchars($d['titre_stage']); ?></span>
                </div>
                <div class="row-info">
                    <span class="row-label">Date début</span>
                    <span style="font-weight:700;"><?php echo $d['date_debut'] ? date('d/m/Y', strtotime($d['date_debut'])) : '—'; ?></span>
                </div>
                <div class="row-info">
                    <span class="row-label">Date fin</span>
                    <span style="font-weight:700;"><?php echo $d['date_fin'] ? date('d/m/Y', strtotime($d['date_fin'])) : '—'; ?></span>
                </div>
                <div class="row-info">
                    <span class="row-label">Entreprise</span>
                    <span style="font-weight:700;"><?php echo htmlspecialchars($d['nom_entreprise']); ?></span>
                </div>
                <div class="row-info">
                    <span class="row-label">Convention</span>
                    <?php if (!empty($d['convention_url'])) : ?>
                    <a href="/<?php echo htmlspecialchars($d['convention_url']); ?>" download class="btn-dl-small">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Télécharger
                    </a>
                    <?php else : ?>
                    <span style="font-size:.78rem; color:var(--rouge); font-weight:600;">Non déposée</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Commentaire -->
            <?php if ($d['deja_valide'] == 0) : ?>
            <form method="POST" action="valider_conventions_tuteur.php">
                <input type="hidden" name="num_dossier" value="<?php echo (int)$d['num_dossier']; ?>">
                <textarea class="textarea" name="commentaire" rows="2" placeholder="Commentaire optionnel…" style="margin-bottom:9px;"></textarea>

                <!-- Boutons Valider / Refuser -->
                <div style="display:flex; gap:9px;">
                    <button type="submit" name="action_val" value="valider" class="btn-valider">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </button>
                    <button type="submit" name="action_val" value="refuser" class="btn-refuser">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </form>

            <?php else : ?>
            <div style="font-size:.80rem; color:var(--vert); font-weight:600;">
                ✓ Vous avez déjà validé ce dossier
            </div>
            <?php endif; ?>

        </div>

        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>
</body>
</html>