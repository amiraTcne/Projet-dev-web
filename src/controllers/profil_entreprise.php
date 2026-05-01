<?php
session_start();

$host = "localhost";
$dbname = "cyStages";
$user = "userpro";
$pass = "projetStage26.";

// Vérifier qu'une entreprise est connectée[cite: 2]
if (!isset($_SESSION['id'])) {
    die("Utilisateur non connecté.");
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$idEntreprise = $_SESSION['id'];
$message = "";

// Mise à jour de la description[cite: 2]
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['description'])) {
    $description = trim($_POST['description']);

    $sqlUpdate = "UPDATE Utilisateur SET description = :description WHERE id = :id AND role_premier = 'Entreprise'";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':description' => $description,
        ':id' => $idEntreprise
    ]);

    $message = "Description mise à jour avec succès.";
}

// Récupération des infos de l'entreprise[cite: 2]
$sql = "SELECT nom_entreprise, email, description, secteur, ville, date_inscription 
        FROM Utilisateur 
        WHERE id = :id AND role_premier = 'Entreprise'";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $idEntreprise]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    die("Entreprise introuvable.");
}

// Comptage des offres publiées par l'entreprise[cite: 1, 3]
$stmtOffres = $pdo->prepare("SELECT COUNT(*) FROM Offre_Stage WHERE id_entreprise = :id");
$stmtOffres->execute([':id' => $idEntreprise]);
$nb_offres = $stmtOffres->fetchColumn();

// Comptage des stages liés à cette entreprise[cite: 1, 3]
$stmtStages = $pdo->prepare("SELECT COUNT(*) FROM Stage WHERE id_entreprise = :id");
$stmtStages->execute([':id' => $idEntreprise]);
$nb_stages = $stmtStages->fetchColumn();

// Initiales pour l'avatar (2 premières lettres du nom de l'entreprise)[cite: 3]
$nomEnt = $entreprise['nom_entreprise'] ?? 'EN';
$initiales = strtoupper(mb_substr($nomEnt, 0, 2));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Entreprise — CY Stage</title>
    
    <!-- On réutilise la feuille de style du profil étudiant[cite: 3] -->
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    
    <style>
        /* Styles extraits et adaptés du profil étudiant[cite: 3] */
        .avatar {
            width: 76px; height: 76px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800;
            margin: 0 auto 10px; box-shadow: 0 4px 16px rgba(27, 79, 155, 0.25);
        }
        
        .info-ligne { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--gris-border); }
        .info-ligne:first-child { padding-top: 0; }
        .info-ligne:last-child  { border-bottom: none; padding-bottom: 0; }
        
        .info-icone { width: 34px; height: 34px; border-radius: 8px; background: var(--gris-fond); display: flex; align-items: center; justify-content: center; color: var(--bleu); flex-shrink: 0; }
        .info-icone svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        
        .info-label  { font-size: .70rem; color: var(--gris-texte); font-weight: 500; margin-bottom: 1px; }
        .info-valeur { font-size: .88rem; font-weight: 700; }
        
        .stats-grille { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;}
        .stat-carte { background: var(--blanc); border: 1px solid var(--gris-border); border-radius: var(--radius); padding: 16px 12px; text-align: center; box-shadow: var(--shadow); }
        .stat-nombre { font-family: 'Syne', sans-serif; font-size: 1.9rem; font-weight: 800; color: var(--bleu); line-height: 1; }
        .stat-label  { font-size: .72rem; color: var(--gris-texte); margin-top: 5px; font-weight: 500; line-height: 1.35; }

        /* Styles spécifiques pour l'édition de description en conservant le visuel global[cite: 2] */
        .btn-action { background: var(--bleu); color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: .8rem; font-weight: 600; margin-top: 10px; }
        .btn-action:hover { background: var(--bleu-clair); }
        .btn-annuler { background: var(--gris-fond); color: var(--gris-texte); text-decoration: none; padding: 8px 14px; border-radius: 6px; font-size: .8rem; font-weight: 600; display: inline-block; margin-top: 10px; border: 1px solid var(--gris-border); }
        .textarea-edit { width: 100%; min-height: 120px; padding: 10px; border: 1px solid var(--gris-border); border-radius: 8px; font-family: inherit; font-size: .88rem; box-sizing: border-box; resize: vertical; margin-top:10px; }
        .msg-succes { background: #d4edda; color: #155724; padding: 10px; border-radius: 8px; font-size: .85rem; margin-bottom: 15px; text-align: center; font-weight: 500; }
        .texte-description { font-size: .88rem; color: #333; line-height: 1.5; white-space: pre-line; margin-bottom: 5px; }
    </style>

    <script>
        function activerEdition() {
            document.getElementById('mode-affichage').style.display = 'none';
            document.getElementById('mode-edition').style.display = 'block';
        }
    </script>
</head>
<body>
<div class="page anim">

    <!-- En-tête avec bouton retour[cite: 3] -->
    <header class="entete">
        <a href="accueil_entreprise.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Mon Profil Entreprise</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <!-- Message de confirmation lors de l'enregistrement[cite: 2] -->
        <?php if (!empty($message)): ?>
            <div class="msg-succes"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Bloc Identité[cite: 3] -->
        <div class="carte" style="text-align:center; padding:22px 16px; margin-bottom: 15px;">
            <div class="avatar"><?php echo htmlspecialchars($initiales); ?></div>
            <h2 style="font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:800; margin-bottom:6px;">
                <?php echo htmlspecialchars($entreprise['nom_entreprise'] ?? 'Non renseigné'); ?>
            </h2>
            <!-- Badge correspondant à l'utilisateur -->
            <span class="badge" style="background:var(--bleu); color:#fff; padding:4px 8px; border-radius:12px; font-size:.7rem; font-weight:bold;">Entreprise</span>
            <p style="font-size:.76rem; color:var(--gris-texte); margin-top:9px;">
                Inscrite le <?php echo date('d/m/Y', strtotime($entreprise['date_inscription'])); ?>
            </p>
        </div>

        <!-- Grille de statistiques personnalisée à l'entreprise[cite: 3] -->
        <div class="stats-grille">
            <div class="stat-carte">
                <p class="stat-nombre"><?php echo (int)$nb_offres; ?></p>
                <p class="stat-label">Offre<?php echo $nb_offres > 1 ? 's' : ''; ?> publiée<?php echo $nb_offres > 1 ? 's' : ''; ?></p>
            </div>
            <div class="stat-carte">
                <p class="stat-nombre"><?php echo (int)$nb_stages; ?></p>
                <p class="stat-label">Stage<?php echo $nb_stages > 1 ? 's' : ''; ?> suivi<?php echo $nb_stages > 1 ? 's' : ''; ?></p>
            </div>
        </div>

        <!-- Bloc Description modifiable[cite: 2] -->
        <p class="label-section" style="font-size: .8rem; font-weight: 700; color: var(--gris-texte); text-transform: uppercase; margin: 15px 0 8px;">À propos</p>
        <div class="carte" style="padding: 16px;">
            <div id="mode-affichage">
                <p class="texte-description">
                    <?php echo !empty($entreprise['description']) ? nl2br(htmlspecialchars($entreprise['description'])) : "Aucune description renseignée pour le moment."; ?>
                </p>
                <button type="button" class="btn-action" onclick="activerEdition()">Modifier la description</button>
            </div>

            <!-- Formulaire d'édition masqué par défaut[cite: 2] -->
            <div id="mode-edition" style="display:none;">
                <form method="POST" action="">
                    <textarea name="description" class="textarea-edit" placeholder="Présentez votre entreprise..." required><?php echo htmlspecialchars($entreprise['description'] ?? ''); ?></textarea>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn-action">Enregistrer</button>
                        <a href="profil_entreprise.php" class="btn-annuler">Annuler</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Informations Générales[cite: 3] -->
        <p class="label-section" style="font-size: .8rem; font-weight: 700; color: var(--gris-texte); text-transform: uppercase; margin: 15px 0 8px;">Informations générales</p>
        <div class="carte" style="padding: 10 16px;">
            
            <div class="info-ligne">
                <div class="info-icone">
                    <!-- Icône Bâtiment / Secteur -->
                    <svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4M9 7h6M9 11h6"/></svg>
                </div>
                <div>
                    <p class="info-label">Secteur d'activité</p>
                    <p class="info-valeur"><?php echo htmlspecialchars($entreprise['secteur'] ?? '—'); ?></p>
                </div>
            </div>

            <div class="info-ligne">
                <div class="info-icone">
                    <!-- Icône Localisation / Ville -->
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div>
                    <p class="info-label">Ville de rattachement</p>
                    <p class="info-valeur"><?php echo htmlspecialchars($entreprise['ville'] ?? '—'); ?></p>
                </div>
            </div>

            <div class="info-ligne">
                <div class="info-icone">
                    <!-- Icône Email[cite: 3] -->
                    <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <div>
                    <p class="info-label">Adresse email de contact</p>
                    <p class="info-valeur"><?php echo htmlspecialchars($entreprise['email'] ?? '—'); ?></p>
                </div>
            </div>

        </div>

        <!-- Bouton de déconnexion[cite: 3] -->
        <div class="deconnexion" style="text-align: center; margin-top: 25px; margin-bottom: 25px;">
            <a href="deconnexion.php" style="color: #e63946; font-weight: 600; text-decoration: none; font-size: .9rem;">Se déconnecter</a>
        </div>

    </div>
</div>
</body>
</html>