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
$dossier   = null;
$msg_ok    = '';

$id_etu_sel = (int)($_GET['etudiant'] ?? 0);

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Étudiants suivis */
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

    /* Dossier de l'étudiant sélectionné */
    if ($id_etu_sel > 0) {
        $sd = mysqli_prepare($conn,
            "SELECT d.*, s.titre AS titre_stage, ent.nom_entreprise
             FROM Dossier_Stage d
             JOIN Stage s ON s.num_stage = d.num_stage
             JOIN Utilisateur ent ON ent.id = s.id_entreprise
             WHERE d.id_etudiant = ? AND s.id_tuteur = ?
             ORDER BY d.date_creation DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($sd, 'ii', $id_etu_sel, $_SESSION['id']);
        mysqli_stmt_execute($sd);
        $dossier = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
        mysqli_stmt_close($sd);
    }

    mysqli_close($conn);
}

$docs = [
    'convention_url' => 'Convention',
    'rapport_url'    => 'Rapport',
    'resume_url'     => 'Résumé',
    'fiche_eval_url' => "Fiche d'évaluation",
];

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
    <title>Documents Envoyés — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        .etu-pill {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 6px 14px; border-radius: 20px;
            border: 1px solid var(--gris-border); background: var(--blanc);
            font-size: .80rem; font-weight: 600; cursor: pointer;
            text-decoration: none; color: var(--noir); transition: all .2s;
        }
        .etu-pill:hover, .etu-pill.actif { border-color: var(--bleu); background: var(--bleu); color: #fff; }

        .doc-card {
            background: var(--blanc); border: 1px solid var(--gris-border);
            border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow);
            display: flex; align-items: center; gap: 13px;
        }
        .doc-icon-big {
            width: 50px; height: 50px; border-radius: 12px;
            background: var(--gris-fond); display: flex; align-items: center; justify-content: center;
            color: var(--bleu); flex-shrink: 0;
        }
        .doc-icon-big svg { width: 24px; height: 24px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .btn-dl {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 13px; border: 1px solid var(--bleu);
            background: var(--blanc); color: var(--bleu);
            border-radius: 20px; font-size: .74rem; font-weight: 700;
            cursor: pointer; text-decoration: none; font-family: 'DM Sans', sans-serif;
            transition: all .2s; white-space: nowrap; flex-shrink: 0;
        }
        .btn-dl:hover { background: var(--bleu); color: #fff; }
        .btn-dl svg { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
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
        <span class="entete-titre">Documents envoyés</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <?php if (empty($etudiants)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                <path d="M14 2v6h6"/>
            </svg>
            <h3>Aucun étudiant suivi</h3>
            <p>Vous n'avez pas encore d'étudiants affectés.</p>
        </div>

        <?php else : ?>

        <!-- Sélection de l'étudiant -->
        <p class="label-section">Élèves</p>
        <div style="display:flex; flex-wrap:wrap; gap:7px; margin-bottom:4px;">
            <?php foreach ($etudiants as $e) : ?>
            <a href="document_tuteur.php?etudiant=<?php echo (int)$e['id']; ?>"
               class="etu-pill <?php echo $id_etu_sel === $e['id'] ? 'actif' : ''; ?>">
                <?php echo htmlspecialchars($e['nom_complet']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (!$id_etu_sel) : ?>
        <div class="etat-vide" style="padding:24px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                <path d="M14 2v6h6"/>
            </svg>
            <p>Sélectionnez un étudiant pour voir ses documents.</p>
        </div>

        <?php elseif (!$dossier) : ?>
        <div class="etat-vide" style="padding:24px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                <path d="M14 2v6h6"/>
            </svg>
            <h3>Aucun dossier trouvé</h3>
            <p>L'étudiant n'a pas encore de dossier actif.</p>
        </div>

        <?php else : ?>

        <!-- Infos du stage -->
        <div style="padding:10px 13px; background:var(--gris-fond); border-radius:var(--radius); border:1px solid var(--gris-border); font-size:.83rem;">
            <strong><?php echo htmlspecialchars($nom_etu_sel); ?></strong>
            <span style="color:var(--gris-texte);"> · <?php echo htmlspecialchars($dossier['titre_stage']); ?> @ <?php echo htmlspecialchars($dossier['nom_entreprise']); ?></span>
        </div>

        <!-- Cartes documents -->
        <?php foreach ($docs as $champ => $label) :
            $url    = $dossier[$champ] ?? null;
            $depose = !empty($url);
        ?>
        <div class="doc-card">
            <div class="doc-icon-big">
                <svg viewBox="0 0 24 24">
                    <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                    <path d="M14 2v6h6"/>
                </svg>
            </div>
            <div style="flex:1; min-width:0;">
                <p style="font-weight:700; font-size:.90rem;"><?php echo $label; ?></p>
                <p style="font-size:.74rem; font-weight:600; margin-top:2px; <?php echo $depose ? 'color:var(--vert)' : 'color:var(--rouge)'; ?>">
                    <?php echo $depose ? '✓ Déposé' : '— Non déposé'; ?>
                </p>
            </div>
            <?php if ($depose) : ?>
            <a href="/<?php echo htmlspecialchars($url); ?>" download class="btn-dl">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Télécharger
            </a>
            <?php endif; ?>
        </div>

        <?php endforeach; ?>

        <?php endif; ?>
        <?php endif; ?>

    </div>
</div>
</body>
</html>