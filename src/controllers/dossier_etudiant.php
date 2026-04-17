<?php
/* Démarrage de la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* Variables de la page */
$conn      = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$dossier   = null;/* les données du dossier de stage */
$remarques = [];/* l'historique des remarques */
$msg_ok    = '';/* le message de succès */
$msg_err   = '';/* le message d'erreur */

/* on traite l'upload d'un document */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type_doc']) && $conn) {

    /* Type de document attendu : rapport, résumé, fiche d'évaluation ou convention */
    $type_doc  = $_POST['type_doc'] ?? '';
    $champs_ok = ['rapport_url', 'resume_url', 'fiche_eval_url', 'convention_url'];

    if (!in_array($type_doc, $champs_ok)) {
        $msg_err = 'Type de document invalide.';
    } elseif (empty($_FILES['fichier']['name'])) {
        $msg_err = 'Choisis un fichier à déposer.';
    } else {
        /* Vérification de l'extension et de la taille du fichier */
        $ext    = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
        $taille = $_FILES['fichier']['size'];

        if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
            $msg_err = 'Format non autorisé. Seuls PDF, DOC et DOCX sont acceptés.';
        } elseif ($taille > 5 * 1024 * 1024) {
            $msg_err = 'Fichier trop lourd (5 Mo maximum).';
        } else {
            /* Création du dossier de stockage si nécessaire */
            $dir = __DIR__ . '/../../uploads/' . $_SESSION['id'] . '/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            /* Génération d'un nom unique pour le fichier */
            $nom  = $type_doc . '_' . time() . '.' . $ext;
            $url  = 'uploads/' . $_SESSION['id'] . '/' . $nom;

            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $dir . $nom)) {

                /* Récupération du num_dossier pour mettre à jour l'URL */
                $sd = mysqli_prepare($conn,
                    "SELECT d.num_dossier FROM Dossier_Stage d
                     JOIN Stage s ON s.num_stage = d.num_stage
                     WHERE d.id_etudiant = ? ORDER BY d.date_creation DESC LIMIT 1"
                );
                mysqli_stmt_bind_param($sd, 'i', $_SESSION['id']);
                mysqli_stmt_execute($sd);
                $rdr = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
                mysqli_stmt_close($sd);

                if ($rdr) {
                    /* Mise à jour de l'URL du document dans la table Dossier_Stage */
                    $upd = mysqli_prepare($conn,
                        "UPDATE Dossier_Stage SET $type_doc = ?, statut = 'en_cours',
                         date_modification = NOW() WHERE num_dossier = ?"
                    );
                    mysqli_stmt_bind_param($upd, 'si', $url, $rdr['num_dossier']);
                    if (mysqli_stmt_execute($upd)) {
                        $msg_ok = 'Document déposé avec succès !';
                    }
                    mysqli_stmt_close($upd);
                }
            } else {
                $msg_err = 'Erreur lors du dépôt du fichier.';
            }
        }
    }
}

/* on traite l'envoi d'une remarque */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu']) && $conn) {
    $contenu = trim($_POST['contenu'] ?? '');

    if (!empty($contenu)) {
        /* on récupère le num_dossier de l'étudiant */
        $sd = mysqli_prepare($conn,
            "SELECT d.num_dossier FROM Dossier_Stage d
             WHERE d.id_etudiant = ? ORDER BY d.date_creation DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($sd, 'i', $_SESSION['id']);
        mysqli_stmt_execute($sd);
        $rdr = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
        mysqli_stmt_close($sd);

        if ($rdr) {
            /* on insère la remarque en base de données */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Remarque (contenu, num_dossier, id_auteur) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'sii', $contenu, $rdr['num_dossier'], $_SESSION['id']);
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Remarque envoyée à votre tuteur !';
            }
            mysqli_stmt_close($ins);
        }
    }
}

/* on fait des changement sur le dossier de stage depuis notre bdd */
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT d.*, s.titre AS titre_stage, ent.nom_entreprise
         FROM Dossier_Stage d
         JOIN Stage s ON s.num_stage = d.num_stage
         JOIN Utilisateur ent ON ent.id = s.id_entreprise
         WHERE d.id_etudiant = ? ORDER BY d.date_creation DESC LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $dossier = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    /* on change les 8 dernières remarques si un dossier existe */
    if ($dossier) {
        $sr = mysqli_prepare($conn,
            "SELECT r.contenu, r.date_creation, u.nom, u.prenom
             FROM Remarque r
             JOIN Utilisateur u ON u.id = r.id_auteur
             WHERE r.num_dossier = ?
             ORDER BY r.date_creation DESC LIMIT 8"
        );
        mysqli_stmt_bind_param($sr, 'i', $dossier['num_dossier']);
        mysqli_stmt_execute($sr);
        $rr = mysqli_stmt_get_result($sr);
        while ($row = mysqli_fetch_assoc($rr)) {
            $remarques[] = $row;
        }
        mysqli_stmt_close($sr);
    }
    mysqli_close($conn);
}

/* on configure les 4 documents qu'on veux, ici chaque entrée correspond au champ de la bdd à son label d'affichage */
$docs = [
    'rapport_url'    => 'Rapport de stage',
    'resume_url'     => 'Résumé de stage',
    'fiche_eval_url' => "Fiche d'évaluation",
    'convention_url' => 'Convention de stage',
];

/* le statut de la bdd avec le label et la classe CSS */
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
    <title>Mon Dossier — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
</head>
<body>
<div class="page anim">

    <!-- l'en-tête avec bouton retour -->
    <header class="entete">
        <a href="accueil_etudiant.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Dossier Etudiant</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <!-- le messages de retour utilisateur -->
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

        <!-- si l'étudiant n'a pas encore de dossier -->
        <?php if (!$dossier) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                <path d="M14 2v6h6"/>
            </svg>
            <h3>Aucun dossier de stage</h3>
            <p>Ton dossier sera créé automatiquement quand ta candidature sera acceptée.</p>
            <a href="offres_etudiant.php" class="btn" style="width:auto; padding:9px 18px; display:inline-flex; margin-top:6px;">
                Chercher une offre
            </a>
        </div>

        <?php else : ?>

        <!-- le bandeau de statut du dossier -->
        <?php [$st_label, $st_class] = $statuts_labels[$dossier['statut']] ?? [$dossier['statut'], 'badge-gris']; ?>
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--gris-fond); border-radius:var(--radius); border:1px solid var(--gris-border);">
            <span style="font-weight:600; font-size:.87rem;">Statut du dossier :</span>
            <span class="badge <?php echo $st_class; ?>"><?php echo $st_label; ?></span>
        </div>

        <!-- les informations sur le stage lié au dossier -->
        <p class="label-section">Stage lié</p>
        <div class="carte">
            <div style="display:flex; gap:8px; align-items:center; margin-bottom:7px; font-size:.85rem;">
                <span style="color:var(--gris-texte); width:72px; flex-shrink:0;">Stage</span>
                <span style="font-weight:700;"><?php echo htmlspecialchars($dossier['titre_stage']); ?></span>
            </div>
            <div style="display:flex; gap:8px; align-items:center; font-size:.85rem;">
                <span style="color:var(--gris-texte); width:72px; flex-shrink:0;">Entreprise</span>
                <span style="font-weight:700;"><?php echo htmlspecialchars($dossier['nom_entreprise']); ?></span>
            </div>
        </div>

        <!-- la liste des 4 documents à déposer -->
        <p class="label-section">Documents requis</p>
        <div class="carte">
            <?php foreach ($docs as $champ => $label) :
                /* on récupère l'url du document depuis le dossier */
                $url    = $dossier[$champ] ?? null;
                $depose = !empty($url); /* c'est true si le document a été déposé */
            ?>
            <div class="doc-ligne">
                <div class="doc-icone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                        <path d="M14 2v6h6"/>
                    </svg>
                </div>
                <div style="flex:1; min-width:0;">
                    <p class="doc-nom"><?php echo $label; ?></p>
                    <p class="doc-statut <?php echo $depose ? 'ok' : 'non'; ?>">
                        <?php echo $depose ? '✓ Déposé' : '— Non déposé'; ?>
                    </p>
                </div>
                <!-- le buton qui ouvre le modal d'upload -->
                <button class="btn-upload"
                        onclick="ouvrirModal('<?php echo $champ; ?>', '<?php echo htmlspecialchars($label, ENT_QUOTES); ?>')"
                        aria-label="Déposer le document">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- le formulaire d'envoi de remarques au tuteur -->
        <p class="label-section">Remarques</p>
        <div class="carte">
            <form method="POST" action="dossier_etudiant.php">
                <textarea class="textarea" name="contenu" rows="4"
                          placeholder="Pose une question à ton tuteur ou signale un problème…"></textarea>
                <button type="submit" class="btn" style="margin-top:10px;">Envoyer la remarque</button>
            </form>
        </div>

        <!-- l'historique des 8 derniers échanges -->
        <?php if (!empty($remarques)) : ?>
        <p class="label-section">Échanges récents</p>
        <div class="carte">
            <?php foreach ($remarques as $rem) : ?>
            <div style="padding:9px 0; border-bottom:1px solid var(--gris-border);">
                <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                    <span style="font-weight:700; font-size:.83rem;">
                        <?php echo htmlspecialchars($rem['prenom'] . ' ' . $rem['nom']); ?>
                    </span>
                    <span style="font-size:.72rem; color:var(--gris-texte);">
                        <?php echo date('d/m/Y', strtotime($rem['date_creation'])); ?>
                    </span>
                </div>
                <p style="font-size:.82rem; color:var(--gris-texte); line-height:1.5;">
                    <?php echo nl2br(htmlspecialchars($rem['contenu'])); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<!-- le modal d'upload de document -->
<div id="modal-bg" style="display:none; position:fixed; inset:0; background:rgba(17,24,39,.45); z-index:100; align-items:flex-end; justify-content:center;">
    <div style="background:#fff; border-radius:20px 20px 0 0; width:100%; max-width:520px; padding:20px 18px 32px;">
        
        <div style="width:32px; height:4px; border-radius:2px; background:var(--gris-border); margin:0 auto 17px;"></div>

        <p id="modal-titre" style="font-family:'Syne',sans-serif; font-weight:700; font-size:.97rem; margin-bottom:13px;"></p>

        <!-- le formulaire d'upload -->
        <form method="POST" enctype="multipart/form-data" action="dossier_etudiant.php">
            <input type="hidden" name="type_doc" id="modal-champ">

            <!-- la zone de sélection de fichier -->
            <label style="display:flex; flex-direction:column; align-items:center; gap:8px; border:2px dashed var(--gris-border); border-radius:var(--radius); padding:20px; cursor:pointer;" for="modal-fichier">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--gris-texte)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <p id="nom-fic" style="font-size:.81rem; color:var(--gris-texte);">Clique pour choisir un fichier</p>
                <p style="font-size:.72rem; color:var(--gris-texte);">PDF, DOC, DOCX · max 5 Mo</p>
                <input type="file" id="modal-fichier" name="fichier" accept=".pdf,.doc,.docx"
                       onchange="document.getElementById('nom-fic').textContent = this.files[0]?.name || 'Fichier choisi'"
                       style="display:none;">
            </label>

            <div style="display:flex; gap:9px; margin-top:12px;">
                <button type="submit" class="btn" style="flex:1;">Déposer</button>
                <button type="button" onclick="fermerModal()"
                        style="flex:1; padding:10px; border:1px solid var(--gris-border); border-radius:8px; background:transparent; font-family:'DM Sans',sans-serif; font-weight:600; font-size:.87rem; cursor:pointer; color:var(--gris-texte);">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    /*on ouvre le modal d'upload pour le document sélectionné
    avec comme paramètre le nom du champ correspondant (ex: 'rapport_url') et le nom affiché dans le modal (ex: 'Rapport de stage')*/
    function ouvrirModal(champ, label) {
        document.getElementById('modal-champ').value    = champ;
        document.getElementById('modal-titre').textContent = 'Déposer : ' + label;
        document.getElementById('nom-fic').textContent  = 'Clique pour choisir un fichier';
        document.getElementById('modal-bg').style.display = 'flex';
    }

    /* on ferme le modal d'upload */
    function fermerModal() {
        document.getElementById('modal-bg').style.display = 'none';
    }

    /* on ferme le modal en cliquant sur le fond obscurci */
    document.getElementById('modal-bg').addEventListener('click', function (e) {
        if (e.target === this) fermerModal();
    });
</script>

</body>
</html>
