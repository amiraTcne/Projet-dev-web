<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$tuteur  = null;
$nb_etudiants = 0;
$nb_stages    = 0;

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT nom, prenom, email, specialite, departement, date_inscription
         FROM Utilisateur WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $tuteur = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    /* nombre d'étudiants suivis */
    $se = mysqli_prepare($conn, "SELECT COUNT(DISTINCT id_etudiant) FROM Stage WHERE id_tuteur = ?");
    mysqli_stmt_bind_param($se, 'i', $_SESSION['id']);
    mysqli_stmt_execute($se);
    mysqli_stmt_bind_result($se, $nb_etudiants);
    mysqli_stmt_fetch($se);
    mysqli_stmt_close($se);

    /* nombre de stages en cours */
    $ss = mysqli_prepare($conn, "SELECT COUNT(*) FROM Stage WHERE id_tuteur = ? AND statut = 'en_cours'");
    mysqli_stmt_bind_param($ss, 'i', $_SESSION['id']);
    mysqli_stmt_execute($ss);
    mysqli_stmt_bind_result($ss, $nb_stages);
    mysqli_stmt_fetch($ss);
    mysqli_stmt_close($ss);

    mysqli_close($conn);
}

$initiales = strtoupper(
    mb_substr($tuteur['prenom'] ?? '?', 0, 1) .
    mb_substr($tuteur['nom']    ?? '?', 0, 1)
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        .avatar {
            width: 76px; height: 76px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif;
            font-size: 1.5rem; font-weight: 800; margin: 0 auto 10px;
            box-shadow: 0 4px 16px rgba(27, 79, 155, 0.25);
        }
        .info-ligne { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--gris-border); }
        .info-ligne:first-child { padding-top: 0; }
        .info-ligne:last-child  { border-bottom: none; padding-bottom: 0; }
        .info-icone { width: 34px; height: 34px; border-radius: 8px; background: var(--gris-fond); display: flex; align-items: center; justify-content: center; color: var(--bleu); flex-shrink: 0; }
        .info-icone svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .info-label  { font-size: .70rem; color: var(--gris-texte); font-weight: 500; margin-bottom: 1px; }
        .info-valeur { font-size: .88rem; font-weight: 700; }
        .stats-grille { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .stat-carte { background: var(--blanc); border: 1px solid var(--gris-border); border-radius: var(--radius); padding: 16px 12px; text-align: center; box-shadow: var(--shadow); }
        .stat-nombre { font-family: 'Syne', sans-serif; font-size: 1.9rem; font-weight: 800; color: var(--bleu); line-height: 1; }
        .stat-label  { font-size: .72rem; color: var(--gris-texte); margin-top: 5px; font-weight: 500; line-height: 1.35; }
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
        <span class="entete-titre">Mon Profil</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <div class="carte" style="text-align:center; padding:22px 16px;">
            <div class="avatar"><?php echo htmlspecialchars($initiales); ?></div>
            <h2 style="font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:800; margin-bottom:6px;">
                <?php echo htmlspecialchars($tuteur['prenom'] . ' ' . $tuteur['nom']); ?>
            </h2>
            <span class="badge badge-bleu">Tuteur</span>
            <p style="font-size:.76rem; color:var(--gris-texte); margin-top:9px;">
                Inscrit le <?php echo date('d/m/Y', strtotime($tuteur['date_inscription'])); ?>
            </p>
        </div>

        <div class="stats-grille">
            <div class="stat-carte">
                <p class="stat-nombre"><?php echo (int)$nb_etudiants; ?></p>
                <p class="stat-label">Étudiant<?php echo $nb_etudiants > 1 ? 's' : ''; ?> suivi<?php echo $nb_etudiants > 1 ? 's' : ''; ?></p>
            </div>
            <div class="stat-carte">
                <p class="stat-nombre"><?php echo (int)$nb_stages; ?></p>
                <p class="stat-label">Stage<?php echo $nb_stages > 1 ? 's' : ''; ?> en cours</p>
            </div>
        </div>

        <p class="label-section">Informations professionnelles</p>
        <div class="carte">

            <div class="info-ligne">
                <div class="info-icone">
                    <svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <div>
                    <p class="info-label">Spécialité</p>
                    <p class="info-valeur"><?php echo htmlspecialchars($tuteur['specialite'] ?? '—'); ?></p>
                </div>
            </div>

            <div class="info-ligne">
                <div class="info-icone">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
                <div>
                    <p class="info-label">Département</p>
                    <p class="info-valeur"><?php echo htmlspecialchars($tuteur['departement'] ?? '—'); ?></p>
                </div>
            </div>

        </div>

        <p class="label-section">Contact</p>
        <div class="carte">
            <div class="info-ligne">
                <div class="info-icone">
                    <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <div>
                    <p class="info-label">Adresse email</p>
                    <p class="info-valeur"><?php echo htmlspecialchars($tuteur['email'] ?? '—'); ?></p>
                </div>
            </div>
        </div>

        <div class="deconnexion">
            <a href="deconnexion.php">Se déconnecter</a>
        </div>

    </div>
</div>
</body>
</html>