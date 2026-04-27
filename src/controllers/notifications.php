<?php
/* on démarre la session */
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$demandes  = [];
$logs      = [];
$nb_dossiers_soumis = 0;

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* les demandes de filière en attente : ce sont les vraies notifications */
    $q1 = mysqli_query($conn,
        "SELECT df.id_demande, df.filiere_demandee, df.justification, df.date_demande,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant
         FROM Demande_Filiere df
         JOIN Utilisateur u ON u.id = df.id_etudiant
         WHERE df.statut = 'en_attente'
         ORDER BY df.date_demande ASC"
    );
    while ($row = mysqli_fetch_assoc($q1)) $demandes[] = $row;

    /* les dossiers soumis en attente de validation */
    $q2 = mysqli_query($conn, "SELECT COUNT(*) FROM Dossier_Stage WHERE statut = 'soumis'");
    [$nb_dossiers_soumis] = mysqli_fetch_row($q2);

    /* les 15 dernières entrées du journal de bord (Trace_Log) */
    $q3 = mysqli_query($conn,
        "SELECT tl.action, tl.entite, tl.description, tl.date_heure,
                CONCAT(u.prenom, ' ', u.nom) AS auteur
         FROM Trace_Log tl
         LEFT JOIN Utilisateur u ON u.id = tl.id_user
         ORDER BY tl.date_heure DESC LIMIT 15"
    );
    while ($row = mysqli_fetch_assoc($q3)) $logs[] = $row;

    mysqli_close($conn);
}

/* on calcule le nombre total de notifications pour le badge */
$nb_notifs = count($demandes) + (int)$nb_dossiers_soumis;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
<div class="page anim">

    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">Notifications</div>

    <!-- le résumé du nombre de notifications actives -->
    <div class="stats-grille-2">
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo count($demandes); ?></p>
            <p class="stat-label">Demande<?php echo count($demandes) > 1 ? 's' : ''; ?> de filière en attente</p>
        </div>
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $nb_dossiers_soumis; ?></p>
            <p class="stat-label">Dossier<?php echo $nb_dossiers_soumis > 1 ? 's' : ''; ?> à valider</p>
        </div>
    </div>

    <!-- les demandes de filières soumises par les étudiants -->
    <p class="label-section">
        Demandes de filières
        <?php if (!empty($demandes)) : ?>
        <span class="badge badge-orange" style="margin-left:8px;"><?php echo count($demandes); ?></span>
        <?php endif; ?>
    </p>
    <div class="carte">
        <?php if (empty($demandes)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <p>Aucune demande en attente.</p>
        </div>
        <?php else : ?>
        <?php foreach ($demandes as $d) : ?>
        <div class="liste-ligne" style="flex-wrap:wrap;">
            <div class="liste-info">
                <p class="liste-nom">
                    Demande : <strong><?php echo htmlspecialchars($d['filiere_demandee']); ?></strong>
                </p>
                <p class="liste-sous">
                    Par <?php echo htmlspecialchars($d['etudiant']); ?>
                    · <?php echo date('d/m/Y', strtotime($d['date_demande'])); ?>
                    <?php if ($d['justification']) : ?>
                    · "<?php echo htmlspecialchars(mb_substr($d['justification'], 0, 70)); ?>"
                    <?php endif; ?>
                </p>
            </div>
            <!-- on redirige vers la page de gestion des domaines pour traiter la demande -->
            <a href="ajouter_domaine_stage.php" class="btn-outline" style="flex-shrink:0; font-size:.78rem;">
                Traiter →
            </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- le journal de bord des dernières actions sur la plateforme -->
    <p class="label-section">Journal de bord (Trace_Log)</p>
    <div class="carte">
        <?php if (empty($logs)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>Aucune entrée dans le journal pour le moment.</p>
        </div>
        <?php else : ?>
        <?php foreach ($logs as $log) : ?>
        <div class="liste-ligne">
            <!-- icône selon le type d'action -->
            <div class="liste-avatar" style="background: linear-gradient(135deg, #374151, #6b7280); font-size:.65rem;">
                <?php echo mb_substr($log['action'], 0, 3); ?>
            </div>
            <div class="liste-info">
                <p class="liste-nom" style="font-size:.85rem;">
                    <span class="badge badge-gris" style="margin-right:6px; font-size:.68rem;">
                        <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                    <?php echo htmlspecialchars($log['description'] ?? $log['entite'] ?? '—'); ?>
                </p>
                <p class="liste-sous">
                    <?php echo htmlspecialchars($log['auteur'] ?? 'Inconnu'); ?>
                    · <?php echo date('d/m/Y H:i', strtotime($log['date_heure'])); ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="deconnexion">
        <a href="accueil_admin.php">← Retour au menu</a>
    </div>

</div>
</body>
</html>
