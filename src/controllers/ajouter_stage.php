<?php
/* on démarre la session */
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}


$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';

/* on traite le formulaire d'ajout d'une offre */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $titre         = trim($_POST['titre'] ?? '');
    $mission       = trim($_POST['mission'] ?? '');
    $competences   = trim($_POST['competences'] ?? '');
    $filiere       = trim($_POST['filiere_ciblee'] ?? '');
    $duree         = (int)($_POST['duree_semaines'] ?? 0);
    $date_debut    = trim($_POST['date_debut'] ?? '');
    $id_entreprise = (int)($_POST['id_entreprise'] ?? 0);

    // 1. On vérifie les champs obligatoires
    if (empty($titre) || empty($mission) || $duree <= 0 || $id_entreprise <= 0) {
        $msg_err = 'Titre, mission, durée et entreprise sont obligatoires.';
    } else {
        // 2. On prépare la requête
        $ins = mysqli_prepare($conn,
            "INSERT INTO Offre_Stage (titre, mission, competences, filiere_ciblee,
             duree_semaines, date_debut, statut, id_entreprise)
             VALUES (?, ?, ?, ?, ?, ?, 'ouverte', ?)"
        );

        // 3. On sécurise : on vérifie que mysqli_prepare a fonctionné (sinon erreur SQL)
        if ($ins) {
            $date_val = empty($date_debut) ? null : $date_debut;
            
            // CORRECTION ICI : ssssisi au lieu de ssssiis
            mysqli_stmt_bind_param($ins, 'ssssisi',
                $titre, $mission, $competences, $filiere, $duree, $date_val, $id_entreprise
            );
            
            // On exécute
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Offre de stage ajoutée avec succès !';
            } else {
                $msg_err = "Erreur lors de l'exécution de la requête : " . mysqli_stmt_error($ins);
            }
            mysqli_stmt_close($ins);
        } else {
            // Si $ins est false (par exemple si la table n'existe pas ou erreur de syntaxe SQL)
            $msg_err = "Erreur SQL interne : " . mysqli_error($conn);
        }
    }
}

/* on charge la liste des entreprises pour la liste déroulante */
$entreprises = [];
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    $q = mysqli_query($conn,
        "SELECT id, nom_entreprise FROM Utilisateur
         WHERE role_premier = 'Entreprise' AND actif = 1
         ORDER BY nom_entreprise ASC"
    );
    while ($row = mysqli_fetch_assoc($q)) $entreprises[] = $row;
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une offre — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
<div class="page anim">

    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">Ajouter une offre de stage</div>

    <!-- messages de retour -->
    <?php if ($msg_ok) : ?><div class="msg-ok">✓ <?php echo htmlspecialchars($msg_ok); ?></div><?php endif; ?>
    <?php if ($msg_err) : ?><div class="msg-err"><?php echo htmlspecialchars($msg_err); ?></div><?php endif; ?>

    <!-- le formulaire de création d'offre -->
    <div class="carte">
        <form method="POST" action="ajouter_stage.php">

            <p class="label-section">Informations de l'offre</p>

            <label class="field-label" for="titre">Titre du poste *</label>
            <input class="field-input" type="text" name="titre" id="titre"
                   placeholder="Ex : Développeur web front-end" required>

            <label class="field-label" for="id_entreprise">Entreprise *</label>
            <select class="field-select" name="id_entreprise" id="id_entreprise" required>
                <option value="">— Sélectionner une entreprise —</option>
                <?php foreach ($entreprises as $e) : ?>
                <option value="<?php echo $e['id']; ?>">
                    <?php echo htmlspecialchars($e['nom_entreprise']); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- on affiche un message si aucune entreprise n'est enregistrée -->
            <?php if (empty($entreprises)) : ?>
            <p style="font-size:.78rem; color:var(--orange); margin-top:-10px; margin-bottom:12px;">
                ⚠ Aucune entreprise active. Ajoutez d'abord une entreprise via Gestion Utilisateurs.
            </p>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div>
                    <label class="field-label" for="duree_semaines">Durée (semaines) *</label>
                    <input class="field-input" type="number" name="duree_semaines" id="duree_semaines"
                           placeholder="Ex : 12" min="1" max="52" required>
                </div>
                <div>
                    <label class="field-label" for="date_debut">Date de début</label>
                    <input class="field-input" type="date" name="date_debut" id="date_debut">
                </div>
            </div>

            <label class="field-label" for="filiere_ciblee">Filière ciblée</label>
            <input class="field-input" type="text" name="filiere_ciblee" id="filiere_ciblee"
                   placeholder="Ex : Informatique, Réseaux, IA…">

            <p class="label-section" style="margin-top:4px;">Détails de la mission</p>

            <label class="field-label" for="mission">Description de la mission *</label>
            <textarea class="field-input field-textarea" name="mission" id="mission"
                      placeholder="Décris les missions et responsabilités du stage…" rows="5" required></textarea>

            <label class="field-label" for="competences">Compétences requises</label>
            <input class="field-input" type="text" name="competences" id="competences"
                   placeholder="Ex : PHP, JavaScript, MySQL (séparées par des virgules)">

            <button type="submit" class="btn-principal">
                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Publier l'offre
            </button>

        </form>
    </div>

    <div class="deconnexion">
        <a href="gestion_stages.php">← Retour</a>
    </div>

</div>
</body>
</html>
