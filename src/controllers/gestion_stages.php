<?php
/* on démarre la session */
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on charge les stats pour la page */
$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$nb_offres = $nb_en_cours = $nb_valides = $nb_filieres = 0;

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    $q = mysqli_query($conn, "SELECT COUNT(*) FROM Offre_Stage WHERE statut = 'ouverte'"); [$nb_offres]   = mysqli_fetch_row($q);
    $q = mysqli_query($conn, "SELECT COUNT(*) FROM Stage WHERE statut = 'en_cours'");      [$nb_en_cours] = mysqli_fetch_row($q);
    $q = mysqli_query($conn, "SELECT COUNT(*) FROM Dossier_Stage WHERE statut = 'valide'");[$nb_valides]  = mysqli_fetch_row($q);
    $q = mysqli_query($conn, "SELECT COUNT(DISTINCT filiere_ciblee) FROM Offre_Stage WHERE filiere_ciblee IS NOT NULL"); [$nb_filieres] = mysqli_fetch_row($q);
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Stages — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
<div class="page anim">

    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">Gestion des Stages</div>

    <!-- les 4 stats : offres ouvertes, stages en cours, dossiers validés, filières -->
    <div class="stats-grille-4">
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $nb_offres; ?></p>
            <p class="stat-label">Offres ouvertes</p>
        </div>
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $nb_en_cours; ?></p>
            <p class="stat-label">Stages en cours</p>
        </div>
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $nb_valides; ?></p>
            <p class="stat-label">Dossiers validés</p>
        </div>
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $nb_filieres; ?></p>
            <p class="stat-label">Filières actives</p>
        </div>
    </div>

    <h3 class="options-title">Actions</h3>

    <!-- les 3 cartes d'action de la page -->
    <div class="nav-grid">

        <!-- ajouter une offre de stage manuellement -->
        <a href="ajouter_stage.php" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="13" x2="12" y2="17"/><line x1="10" y1="15" x2="14" y2="15"/></svg>
            </div>
            <h4>Ajouter une offre de stage</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <!-- ajouter un nouveau domaine / filière -->
        <a href="ajouter_domaine_stage.php" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <h4>Ajouter un domaine de stage</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

        <!-- voir la liste des offres publiées -->
        <a href="gestion_stages.php?voir=1" class="nav">
            <div class="icon">
                <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <h4>Voir toutes les offres</h4>
            <span class="arrow"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></span>
        </a>

    </div>

    <?php if (isset($_GET['voir'])) :
        /* si on clique sur voir les offres, on les charge et on les affiche ici */
        $conn2 = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
        $offres = [];
        if ($conn2) {
            mysqli_set_charset($conn2, 'utf8mb4');
            $q = mysqli_query($conn2,
                "SELECT o.num_offre, o.titre, o.filiere_ciblee, o.duree_semaines,
                        o.statut, o.date_publication, u.nom_entreprise
                 FROM Offre_Stage o
                 JOIN Utilisateur u ON u.id = o.id_entreprise
                 ORDER BY o.date_publication DESC LIMIT 50"
            );
            while ($row = mysqli_fetch_assoc($q)) $offres[] = $row;
            mysqli_close($conn2);
        }
    ?>
    <div style="margin-top:24px;">
        <p class="label-section">Toutes les offres (<?php echo count($offres); ?>)</p>
        <div class="carte">
            <?php if (empty($offres)) : ?>
            <div class="etat-vide"><p>Aucune offre pour le moment.</p></div>
            <?php else : ?>
            <?php foreach ($offres as $o) :
                $statuts = ['ouverte'=>['badge-vert','Ouverte'],'pourvue'=>['badge-bleu','Pourvue'],'archivee'=>['badge-gris','Archivée']];
                [$cls, $lbl] = $statuts[$o['statut']] ?? ['badge-gris', $o['statut']];
            ?>
            <div class="liste-ligne">
                <div class="liste-info">
                    <p class="liste-nom"><?php echo htmlspecialchars($o['titre']); ?></p>
                    <p class="liste-sous">
                        <?php echo htmlspecialchars($o['nom_entreprise']); ?>
                        <?php if ($o['filiere_ciblee']) : ?> · <?php echo htmlspecialchars($o['filiere_ciblee']); ?><?php endif; ?>
                        · <?php echo (int)$o['duree_semaines']; ?> sem.
                    </p>
                </div>
                <span class="badge <?php echo $cls; ?>"><?php echo $lbl; ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="deconnexion">
        <a href="accueil_admin.php">← Retour au menu</a>
    </div>

</div>
</body>
</html>
