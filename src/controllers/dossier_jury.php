<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Jury') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$num_dossier = (int)($_GET['id'] ?? 0);
if ($num_dossier <= 0) {
    header('Location: accueil_jury.php');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$dossier = null;
$eval    = null;
$msg_ok  = '';
$msg_err = '';

/* ── Traitement du formulaire d'évaluation ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $note        = isset($_POST['note'])        ? (float)$_POST['note']        : null;
    $appreciation = trim($_POST['appreciation'] ?? '');
    $valide      = isset($_POST['valide'])      ? 1 : 0;

    if ($note === null || $note < 0 || $note > 20) {
        $msg_err = 'La note doit être comprise entre 0 et 20.';
    } else {
        /* Vérification : ce jury a-t-il déjà évalué ce dossier ? */
        $chk = mysqli_prepare($conn,
            "SELECT id_eval FROM Evaluation_Jury WHERE num_dossier = ? AND id_jury = ?"
        );
        mysqli_stmt_bind_param($chk, 'ii', $num_dossier, $_SESSION['id']);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $existe = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);

        if ($existe) {
            /* Mise à jour de l'évaluation existante */
            $upd = mysqli_prepare($conn,
                "UPDATE Evaluation_Jury
                 SET note = ?, appreciation = ?, valide = ?, date_eval = NOW()
                 WHERE num_dossier = ? AND id_jury = ?"
            );
            mysqli_stmt_bind_param($upd, 'dsiii', $note, $appreciation, $valide, $num_dossier, $_SESSION['id']);
            if (mysqli_stmt_execute($upd)) {
                $msg_ok = 'Évaluation mise à jour avec succès.';
            } else {
                $msg_err = 'Erreur lors de la mise à jour.';
            }
            mysqli_stmt_close($upd);
        } else {
            /* Nouvelle évaluation */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Evaluation_Jury (note, appreciation, valide, num_dossier, id_jury)
                 VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'dsiii', $note, $appreciation, $valide, $num_dossier, $_SESSION['id']);
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Évaluation enregistrée avec succès.';
            } else {
                $msg_err = 'Erreur lors de l\'enregistrement.';
            }
            mysqli_stmt_close($ins);
        }

        /* Si validé, on met à jour le statut du dossier */
        if ($valide && !$msg_err) {
            $upd_d = mysqli_prepare($conn,
                "UPDATE Dossier_Stage SET statut = 'valide' WHERE num_dossier = ?"
            );
            mysqli_stmt_bind_param($upd_d, 'i', $num_dossier);
            mysqli_stmt_execute($upd_d);
            mysqli_stmt_close($upd_d);
        }
    }
}

/* ── Récupération du dossier complet ── */
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT
            d.num_dossier,
            d.statut            AS statut_dossier,
            d.rapport_url,
            d.resume_url,
            d.fiche_eval_url,
            d.convention_url,
            d.date_creation,
            d.date_modification,

            s.num_stage,
            s.titre             AS titre_stage,
            s.mission           AS mission_stage,
            s.date_debut,
            s.date_fin,
            s.avancement,
            s.statut            AS statut_stage,
            s.duree_semaines    AS duree_stage,

            e.nom               AS etudiant_nom,
            e.prenom            AS etudiant_prenom,
            e.email             AS etudiant_email,
            e.filiere,
            e.niveau,
            e.annee_promo,

            o.num_offre,
            o.titre             AS titre_offre,
            o.mission           AS mission_offre,
            o.competences,
            o.duree_semaines    AS duree_offre,
            o.date_debut        AS debut_offre,

            ent.nom_entreprise,
            ent.secteur,
            ent.ville,
            ent.site_web

         FROM Dossier_Stage d
         JOIN Stage s              ON s.num_stage  = d.num_stage
         JOIN Utilisateur e        ON e.id         = d.id_etudiant
         LEFT JOIN Offre_Stage o   ON o.num_offre  = s.num_offre
         LEFT JOIN Utilisateur ent ON ent.id       = s.id_entreprise
         WHERE d.num_dossier = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $num_dossier);
    mysqli_stmt_execute($stmt);
    $dossier = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    /* Évaluation éventuelle déjà saisie par CE jury */
    if ($dossier) {
        $se = mysqli_prepare($conn,
            "SELECT note, appreciation, valide, date_eval
             FROM Evaluation_Jury WHERE num_dossier = ? AND id_jury = ?"
        );
        mysqli_stmt_bind_param($se, 'ii', $num_dossier, $_SESSION['id']);
        mysqli_stmt_execute($se);
        $eval = mysqli_fetch_assoc(mysqli_stmt_get_result($se));
        mysqli_stmt_close($se);
    }

    mysqli_close($conn);
}

/* Redirection si dossier introuvable */
if (!$dossier) {
    header('Location: accueil_jury.php');
    exit();
}

/* Helpers d'affichage */
$statuts_labels = [
    'incomplet' => ['Incomplet', '#dc2626', 'rgba(220,38,38,.10)'],
    'en_cours'  => ['En cours',  '#d97706', 'rgba(217,119,6,.12)'],
    'soumis'    => ['Soumis',    '#1B4F9B', 'rgba(27,79,155,.12)'],
    'valide'    => ['Validé ✓',  '#16a34a', 'rgba(22,163,74,.12)'],
    'rejete'    => ['Refusé',    '#dc2626', 'rgba(220,38,38,.10)'],
];
[$st_label, $st_color, $st_bg] = $statuts_labels[$dossier['statut_dossier']] ?? [$dossier['statut_dossier'], '#6b7280', 'rgba(107,114,128,.1)'];

$initiales = strtoupper(
    mb_substr($dossier['etudiant_prenom'] ?? '?', 0, 1) .
    mb_substr($dossier['etudiant_nom']    ?? '?', 0, 1)
);

$docs = [
    'Rapport de stage'   => $dossier['rapport_url'],
    'Résumé de stage'    => $dossier['resume_url'],
    "Fiche d'évaluation" => $dossier['fiche_eval_url'],
    'Convention'         => $dossier['convention_url'],
];

$techs = array_filter(array_map('trim', explode(',', $dossier['competences'] ?? '')));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier étudiant — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        /* Réutilise style_etudiant.css (même charte mobile-first) */
        .info-ligne {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--gris-border);
        }
        .info-ligne:last-child { border-bottom: none; padding-bottom: 0; }
        .info-ligne:first-child { padding-top: 0; }
        .info-icone {
            width: 34px; height: 34px; border-radius: 8px;
            background: var(--gris-fond);
            display: flex; align-items: center; justify-content: center;
            color: var(--bleu); flex-shrink: 0;
        }
        .info-icone svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .info-label  { font-size: .70rem; color: var(--gris-texte); font-weight: 500; margin-bottom: 1px; }
        .info-valeur { font-size: .87rem; font-weight: 700; }

        /* Avatar étudiant */
        .avatar-lg {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            color: #fff; font-family: 'Syne', sans-serif;
            font-size: 1.4rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 10px;
            box-shadow: 0 4px 16px rgba(27,79,155,.22);
        }

        /* Documents */
        .doc-ligne { display: flex; align-items: center; gap: 11px; padding: 11px 0; border-bottom: 1px solid var(--gris-border); }
        .doc-ligne:last-child { border-bottom: none; padding-bottom: 0; }
        .doc-ligne:first-child { padding-top: 0; }
        .doc-icone { width: 36px; height: 36px; border-radius: 8px; background: var(--gris-fond); display: flex; align-items: center; justify-content: center; color: var(--bleu); flex-shrink: 0; }
        .doc-icone svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .doc-nom    { font-weight: 600; font-size: .85rem; }
        .doc-statut { font-size: .73rem; font-weight: 600; margin-top: 1px; }
        .doc-statut.ok  { color: var(--vert); }
        .doc-statut.non { color: var(--rouge); }
        .doc-lien { margin-left: auto; font-size: .74rem; color: var(--bleu-clair); font-weight: 600; text-decoration: none; }
        .doc-lien:hover { text-decoration: underline; }

        /* Formulaire note */
        .note-input {
            width: 100%; padding: 10px 13px;
            border: 1px solid var(--gris-border); border-radius: 8px;
            background: var(--gris-fond); font-family: 'DM Sans', sans-serif;
            font-size: .90rem; color: var(--noir); outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .note-input:focus { border-color: var(--bleu); box-shadow: 0 0 0 3px rgba(27,79,155,.08); background: var(--blanc); }

        .form-label { font-size: .78rem; font-weight: 600; color: var(--gris-texte); margin-bottom: 5px; display: block; }

        .checkbox-row { display: flex; align-items: center; gap: 10px; }
        .checkbox-row input[type="checkbox"] { width: 17px; height: 17px; accent-color: var(--vert); cursor: pointer; }
        .checkbox-row label { font-size: .87rem; font-weight: 600; cursor: pointer; }

        /* Pastille statut */
        .pastille {
            display: inline-block; padding: 3px 10px;
            border-radius: 20px; font-size: .72rem; font-weight: 700;
        }
    </style>
</head>
<body>
<div class="page anim">

    <!-- En-tête avec retour -->
    <header class="entete">
        <a href="accueil_jury.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Dossier étudiant</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <!-- Messages de retour -->
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

        <!-- ── Identité étudiant ── -->
        <div class="carte" style="text-align:center; padding:20px 16px;">
            <div class="avatar-lg"><?php echo htmlspecialchars($initiales); ?></div>
            <h2 style="font-family:'Syne',sans-serif; font-size:1.05rem; font-weight:800; margin-bottom:5px;">
                <?php echo htmlspecialchars($dossier['etudiant_prenom'] . ' ' . $dossier['etudiant_nom']); ?>
            </h2>
            <p style="font-size:.78rem; color:var(--gris-texte); margin-bottom:8px;">
                <?php echo htmlspecialchars($dossier['etudiant_email']); ?>
            </p>
            <span class="pastille" style="color:<?php echo $st_color; ?>; background:<?php echo $st_bg; ?>;">
                Dossier : <?php echo $st_label; ?>
            </span>
        </div>

        <!-- ── Infos académiques ── -->
        <p class="label-section">Profil académique</p>
        <div class="carte">
            <div class="info-ligne">
                <div class="info-icone"><svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
                <div><p class="info-label">Filière</p><p class="info-valeur"><?php echo htmlspecialchars($dossier['filiere'] ?? '—'); ?></p></div>
            </div>
            <div class="info-ligne">
                <div class="info-icone"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
                <div><p class="info-label">Niveau · Promotion</p><p class="info-valeur"><?php echo htmlspecialchars(($dossier['niveau'] ?? '—') . ' · ' . ($dossier['annee_promo'] ?? '—')); ?></p></div>
            </div>
        </div>

        <!-- ── Offre de stage ── -->
        <?php if ($dossier['titre_offre'] || $dossier['titre_stage']) : ?>
        <p class="label-section">Stage</p>
        <div class="carte">
            <div class="info-ligne">
                <div class="info-icone"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></div>
                <div>
                    <p class="info-label">Poste</p>
                    <p class="info-valeur"><?php echo htmlspecialchars($dossier['titre_offre'] ?? $dossier['titre_stage']); ?></p>
                </div>
            </div>
            <?php if ($dossier['nom_entreprise']) : ?>
            <div class="info-ligne">
                <div class="info-icone"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <div>
                    <p class="info-label">Entreprise<?php echo $dossier['ville'] ? ' · Ville' : ''; ?></p>
                    <p class="info-valeur">
                        <?php echo htmlspecialchars($dossier['nom_entreprise']); ?>
                        <?php if ($dossier['ville']) : ?> — <?php echo htmlspecialchars($dossier['ville']); ?><?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($dossier['date_debut'] || $dossier['date_fin']) : ?>
            <div class="info-ligne">
                <div class="info-icone"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div>
                    <p class="info-label">Période</p>
                    <p class="info-valeur">
                        <?php
                        $debut = $dossier['date_debut'] ? date('d/m/Y', strtotime($dossier['date_debut'])) : '—';
                        $fin   = $dossier['date_fin']   ? date('d/m/Y', strtotime($dossier['date_fin']))   : '—';
                        echo htmlspecialchars($debut . ' → ' . $fin);
                        ?>
                        <?php if ($dossier['duree_stage'] ?? $dossier['duree_offre']) : ?>
                        <span style="font-weight:400; color:var(--gris-texte); font-size:.78rem;">
                            (<?php echo (int)($dossier['duree_stage'] ?? $dossier['duree_offre']); ?> sem.)
                        </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($dossier['mission_offre']) : ?>
            <div class="info-ligne" style="flex-direction:column; align-items:flex-start;">
                <p class="info-label" style="margin-bottom:4px;">Mission</p>
                <p style="font-size:.83rem; color:var(--gris-texte); line-height:1.6;">
                    <?php echo nl2br(htmlspecialchars($dossier['mission_offre'])); ?>
                </p>
            </div>
            <?php endif; ?>
            <?php if (!empty($techs)) : ?>
            <div class="info-ligne" style="flex-direction:column; align-items:flex-start;">
                <p class="info-label" style="margin-bottom:6px;">Compétences</p>
                <div style="display:flex; flex-wrap:wrap; gap:5px;">
                    <?php foreach ($techs as $t) : ?>
                    <span class="tag"><?php echo htmlspecialchars($t); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Documents déposés ── -->
        <p class="label-section">Documents</p>
        <div class="carte">
            <?php foreach ($docs as $label => $url) :
                $depose = !empty($url);
            ?>
            <div class="doc-ligne">
                <div class="doc-icone">
                    <svg viewBox="0 0 24 24"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/><path d="M14 2v6h6"/></svg>
                </div>
                <div style="flex:1; min-width:0;">
                    <p class="doc-nom"><?php echo $label; ?></p>
                    <p class="doc-statut <?php echo $depose ? 'ok' : 'non'; ?>">
                        <?php echo $depose ? '✓ Déposé' : '— Non déposé'; ?>
                    </p>
                </div>
                <?php if ($depose) : ?>
                <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="doc-lien">
                    Voir ↗
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Formulaire d'évaluation ── -->
        <p class="label-section">
            <?php echo $eval ? 'Mon évaluation (modifier)' : 'Évaluer ce dossier'; ?>
        </p>
        <div class="carte">
            <form method="POST" action="dossier_jury.php?id=<?php echo $num_dossier; ?>">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label class="form-label" for="note">Note /20 *</label>
                        <input class="note-input" type="number" id="note" name="note"
                               min="0" max="20" step="0.5" required
                               value="<?php echo $eval ? htmlspecialchars($eval['note']) : ''; ?>"
                               placeholder="Ex : 14.5">
                    </div>
                    <?php if ($eval) : ?>
                    <div style="display:flex; flex-direction:column; justify-content:flex-end;">
                        <p class="form-label">Dernière évaluation</p>
                        <p style="font-size:.80rem; color:var(--gris-texte);">
                            <?php echo date('d/m/Y', strtotime($eval['date_eval'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom:12px;">
                    <label class="form-label" for="appreciation">Appréciation</label>
                    <textarea class="textarea" id="appreciation" name="appreciation" rows="4"
                              placeholder="Commentaires sur le dossier, le rapport, la soutenance…"><?php echo htmlspecialchars($eval['appreciation'] ?? ''); ?></textarea>
                </div>

                <div class="checkbox-row" style="margin-bottom:16px;">
                    <input type="checkbox" id="valide" name="valide" value="1"
                           <?php echo ($eval && $eval['valide']) ? 'checked' : ''; ?>>
                    <label for="valide">Valider ce stage</label>
                </div>

                <button type="submit" class="btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <?php echo $eval ? 'Mettre à jour l\'évaluation' : 'Enregistrer l\'évaluation'; ?>
                </button>

            </form>
        </div>

    </div>
</div>
</body>
</html>




