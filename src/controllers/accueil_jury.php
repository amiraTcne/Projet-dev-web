<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Jury') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn     = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$dossiers = [];

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* On récupère uniquement le nécessaire pour les cartes :
       num_dossier (pour le lien), nom/prénom/filière/niveau de l'étudiant, statut du dossier */
    $stmt = mysqli_prepare($conn,
        "SELECT DISTINCT
            d.num_dossier,
            d.statut        AS statut_dossier,
            e.nom           AS etudiant_nom,
            e.prenom        AS etudiant_prenom,
            e.filiere,
            e.niveau
         FROM Dossier_Stage d
         JOIN Stage s           ON s.num_stage    = d.num_stage
         JOIN Utilisateur e     ON e.id           = d.id_etudiant
         LEFT JOIN Evaluation_Jury ev
                                ON ev.num_dossier = d.num_dossier
                               AND ev.id_jury     = ?
         WHERE
             ev.id_jury = ?
             OR (d.statut = 'soumis' AND ev.id_jury IS NULL)
         ORDER BY e.nom, e.prenom"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['id'], $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $dossiers[] = $row;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

$statuts = [
    'incomplet' => ['Incomplet', '#dc2626', 'rgba(220,38,38,.10)'],
    'en_cours'  => ['En cours',  '#d97706', 'rgba(217,119,6,.12)'],
    'soumis'    => ['Soumis',    '#1B4F9B', 'rgba(27,79,155,.12)'],
    'valide'    => ['Validé ✓',  '#16a34a', 'rgba(22,163,74,.12)'],
    'rejete'    => ['Refusé',    '#dc2626', 'rgba(220,38,38,.10)'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Jury — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_acceuil.css">
    <?php include '../../public/frameworks.php'; ?>
    <style>
        .nav-etudiant {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            border: 1px solid var(--gris-border);
            border-radius: var(--radius);
            background: var(--blanc);
            text-decoration: none;
            color: var(--noir);
            transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        }
        .nav-etudiant:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(27,79,155,.12);
            border-color: var(--bleu);
        }
        .avatar {
            width: 46px; height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1B4F9B, #2563c7);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1rem;
            flex-shrink: 0;
        }
        .nav-etudiant-info   { flex: 1; min-width: 0; }
        .nav-etudiant-nom    { font-weight: 700; font-size: 0.95rem; }
        .nav-etudiant-sub    { font-size: 0.76rem; color: var(--gris-texte); margin-top: 2px; }
        .nav-etudiant-action { font-size: 0.74rem; color: var(--bleu); font-weight: 600; margin-top: 4px; }
        .badge-statut {
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 0.69rem;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .arrow-icon { flex-shrink: 0; color: var(--gris-texte); }
        .arrow-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
        .etat-vide { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 50px 20px; gap: 12px; color: var(--gris-texte); }
        .etat-vide svg { opacity: .35; }
        .nav-grid { grid-template-columns: 1fr; }
    </style>
</head>
<body>
<div class="page">

    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">
        <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
    </div>

    <h3 class="options-title">
        Dossiers à évaluer
        <span style="font-size:0.78rem; font-weight:500; color:var(--gris-texte); margin-left:6px;">
            (<?php echo count($dossiers); ?>)
        </span>
    </h3>

    <?php if (empty($dossiers)) : ?>
    <div class="etat-vide">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
            <path d="M14 2v6h6"/>
        </svg>
        <strong>Aucun dossier à évaluer</strong>
        <p>Les dossiers soumis par les étudiants apparaîtront ici.</p>
    </div>

    <?php else : ?>
    <div class="nav-grid">
        <?php foreach ($dossiers as $d) :
            $initiales = strtoupper(
                mb_substr($d['etudiant_prenom'] ?? '?', 0, 1) .
                mb_substr($d['etudiant_nom']    ?? '?', 0, 1)
            );
            [$st_label, $st_color, $st_bg] = $statuts[$d['statut_dossier']] ?? [$d['statut_dossier'], '#6b7280', 'rgba(107,114,128,.1)'];
        ?>
        <a href="dossier_jury.php?id=<?php echo (int)$d['num_dossier']; ?>" class="nav-etudiant">

            <div class="avatar"><?php echo htmlspecialchars($initiales); ?></div>

            <div class="nav-etudiant-info">
                <p class="nav-etudiant-nom">
                    <?php echo htmlspecialchars($d['etudiant_prenom'] . ' ' . $d['etudiant_nom']); ?>
                </p>
                <?php if ($d['filiere'] || $d['niveau']) : ?>
                <p class="nav-etudiant-sub">
                    <?php echo htmlspecialchars(implode(' · ', array_filter([$d['filiere'], $d['niveau']]))); ?>
                </p>
                <?php endif; ?>
                <p class="nav-etudiant-action">Consulter le dossier et évaluer →</p>
            </div>

            <span class="badge-statut" style="color:<?php echo $st_color; ?>; background:<?php echo $st_bg; ?>;">
                <?php echo $st_label; ?>
            </span>

            <div class="arrow-icon">
                <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
            </div>

        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="deconnexion">
        <a href="deconnexion.php">Se déconnecter</a>
    </div>

</div>
</body>
</html>