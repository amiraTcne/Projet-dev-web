<?php
session_start();

// 1. Sécurité : Uniquement pour les étudiants
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$id_etudiant = (int)$_SESSION['id'];
$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
if (!$conn) { die("Erreur de connexion"); }
mysqli_set_charset($conn, 'utf8mb4');

$msg_ok = '';
$msg_err = '';

/**
 * LOGIQUE D'UPLOAD DES DOCUMENTS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type_doc'])) {
    $type_doc = $_POST['type_doc']; // ex: rapport_url, convention_url...
    $champs_valides = ['rapport_url', 'resume_url', 'fiche_eval_url', 'convention_url'];

    if (in_array($type_doc, $champs_valides) && !empty($_FILES['fichier']['name'])) {
        $repertoire = "../../uploads/dossiers/";
        if (!is_dir($repertoire)) mkdir($repertoire, 0777, true);

        $nom_fichier = time() . "_" . basename($_FILES['fichier']['name']);
        $chemin_final = $repertoire . $nom_fichier;

        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_final)) {
            // Mise à jour du dossier dans la base
            $sql_upd = "UPDATE Dossier_Stage SET $type_doc = ? WHERE id_etudiant = ? AND statut != 'valide'";
            $stmt_upd = mysqli_prepare($conn, $sql_upd);
            mysqli_stmt_bind_param($stmt_upd, "si", $chemin_final, $id_etudiant);
            mysqli_stmt_execute($stmt_upd);
            $msg_ok = "Document mis à jour avec succès !";
        } else {
            $msg_err = "Erreur lors du transfert du fichier.";
        }
    }
}

/**
 * RÉCUPÉRATION DU DOSSIER
 * On récupère le dossier lié au stage 'en_cours' de l'étudiant
 */
$sql = "SELECT d.*, s.titre AS titre_stage, ent.nom_entreprise 
        FROM Dossier_Stage d
        JOIN Stage s ON d.num_stage = s.num_stage
        JOIN Utilisateur ent ON s.id_entreprise = ent.id
        WHERE d.id_etudiant = ? 
        ORDER BY d.date_creation DESC LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_etudiant);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$dossier = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Dossier de Stage</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f8fbff; padding: 30px; }
        .card { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        h1 { color: #255FAA; margin-bottom: 5px; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .status-incomplet { background: #fff3cd; color: #856404; }
        .status-valide { background: #d4edda; color: #155724; }
        
        .doc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
        .doc-item { border: 1px solid #eee; padding: 20px; border-radius: 12px; background: #fafafa; }
        .btn-upload { background: #255FAA; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        .link-view { color: #255FAA; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div class="card">
    <a href="accueil_etudiant.php" style="text-decoration:none;">← Retour</a>
    
    <?php if (!$dossier): ?>
        <div style="text-align:center; padding: 50px;">
            <h2>Aucun dossier actif</h2>
            <p>Vous devez d'abord confirmer un stage pour générer votre dossier.</p>
            <a href="confirmer_stage_etudiant.php" style="color:#255FAA;">Voir mes candidatures acceptées</a>
        </div>
    <?php else: ?>
        <h1>Mon Dossier de Stage</h1>
        <p>Stage : <strong><?php echo htmlspecialchars($dossier['titre_stage']); ?></strong> chez <strong><?php echo htmlspecialchars($dossier['nom_entreprise']); ?></strong></p>
        <span class="status-badge status-<?php echo $dossier['statut']; ?>">État : <?php echo $dossier['statut']; ?></span>

        <?php if ($msg_ok): ?> <div class="alert alert-success"><?php echo $msg_ok; ?></div> <?php endif; ?>

        <div class="doc-grid">
            <div class="doc-item">
                <strong>Convention de stage</strong><br>
                <?php if ($dossier['convention_url']): ?>
                    <a href="<?php echo $dossier['convention_url']; ?>" class="link-view" target="_blank">📄 Voir le document</a>
                <?php else: ?>
                    <small style="color:red;">Manquant</small>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="type_doc" value="convention_url">
                    <input type="file" name="fichier" required style="font-size: 0.7rem; margin-top:10px;">
                    <button type="submit" class="btn-upload">Mettre à jour</button>
                </form>
            </div>

            <div class="doc-item">
                <strong>Rapport de stage</strong><br>
                <?php if ($dossier['rapport_url']): ?>
                    <a href="<?php echo $dossier['rapport_url']; ?>" class="link-view" target="_blank">📄 Voir le document</a>
                <?php else: ?>
                    <small style="color:red;">Non déposé</small>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="type_doc" value="rapport_url">
                    <input type="file" name="fichier" required style="font-size: 0.7rem; margin-top:10px;">
                    <button type="submit" class="btn-upload">Déposer</button>
                </form>
            </div>
            
            </div>
    <?php endif; ?>
</div>

</body>
</html>