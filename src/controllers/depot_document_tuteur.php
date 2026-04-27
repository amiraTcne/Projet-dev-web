<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';
$etudiants = [];
$id_etu_sel = (int)($_GET['etudiant'] ?? 0);

/* Traitement de l'upload */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type_doc']) && $conn) {
    $type_doc    = $_POST['type_doc'] ?? '';
    $id_etudiant = (int)($_POST['id_etudiant'] ?? 0);
    $champs_ok   = ['rapport_url', 'resume_url', 'fiche_eval_url', 'convention_url'];

    if (!in_array($type_doc, $champs_ok) || $id_etudiant <= 0) {
        $msg_err = 'Paramètres invalides.';
    } elseif (empty($_FILES['fichier']['name'])) {
        $msg_err = 'Choisis un fichier à déposer.';
    } else {
        $ext    = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
        $taille = $_FILES['fichier']['size'];

        if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
            $msg_err = 'Format non autorisé. Seuls PDF, DOC et DOCX sont acceptés.';
        } elseif ($taille > 5 * 1024 * 1024) {
            $msg_err = 'Fichier trop lourd (5 Mo maximum).';
        } else {
            $dir = __DIR__ . '/../../uploads/tuteur_' . $_SESSION['id'] . '_etu_' . $id_etudiant . '/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $nom = $type_doc . '_' . time() . '.' . $ext;
            $url = 'uploads/tuteur_' . $_SESSION['id'] . '_etu_' . $id_etudiant . '/' . $nom;

            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $dir . $nom)) {
                $sd = mysqli_prepare($conn,
                    "SELECT d.num_dossier FROM Dossier_Stage d
                     JOIN Stage s ON s.num_stage = d.num_stage
                     WHERE d.id_etudiant = ? AND s.id_tuteur = ?
                     ORDER BY d.date_creation DESC LIMIT 1"
                );
                mysqli_stmt_bind_param($sd, 'ii', $id_etudiant, $_SESSION['id']);
                mysqli_stmt_execute($sd);
                $rdr = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
                mysqli_stmt_close($sd);

                if ($rdr) {
                    $upd = mysqli_prepare($conn,
                        "UPDATE Dossier_Stage SET $type_doc = ?, date_modification = NOW() WHERE num_dossier = ?"
                    );
                    mysqli_stmt_bind_param($upd, 'si', $url, $rdr['num_dossier']);
                    if (mysqli_stmt_execute($upd)) $msg_ok = 'Document déposé avec succès !';
                    mysqli_stmt_close($upd);
                }
                $id_etu_sel = $id_etudiant;
            } else {
                $msg_err = 'Erreur lors du dépôt du fichier.';
            }
        }
    }
}

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

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

    mysqli_close($conn);
}

$docs_labels = [
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
    <title>Dépôt Documents — CY Stage</title>
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
        .doc-btn {
            display: flex; align-items: center; gap: 12px;
            padding: 13px; border: 1px solid var(--gris-border);
            border-radius: var(--radius); background: var(--blanc);
            cursor: pointer; transition: all .2s; width: 100%;
            text-align: left; font-family: 'DM Sans', sans-serif;
        }
        .doc-btn:hover { border-color: var(--bleu); box-shadow: var(--shadow); transform: translateY(-1px); }
        .doc-btn-icon { width: 42px; height: 42px; border-radius: 10px; background: var(--gris-fond); display: flex; align-items: center; justify-content: center; color: var(--bleu); flex-shrink: 0; }
        .doc-btn-icon svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
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
        <span class="entete-titre">Dépôt documents</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

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

        <?php if (empty($etudiants)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8l6 6v12a2 2 0 0 1-2 2z"/>
                <path d="M14 2v6h6"/>
            </svg>
            <h3>Aucun étudiant suivi</h3>
        </div>

        <?php else : ?>

        <!-- Sélection étudiant -->
        <p class="label-section">Élèves</p>
        <div style="display:flex; flex-wrap:wrap; gap:7px;">
            <?php foreach ($etudiants as $e) : ?>
            <a href="depot_document_tuteur.php?etudiant=<?php echo (int)$e['id']; ?>"
               class="etu-pill <?php echo $id_etu_sel === $e['id'] ? 'actif' : ''; ?>">
                <?php echo htmlspecialchars($e['nom_complet']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (!$id_etu_sel) : ?>
        <div class="etat-vide" style="padding:24px;">
            <p>Sélectionnez un étudiant pour déposer un document.</p>
        </div>

        <?php else : ?>

        <!-- Boutons de type de document -->
        <p class="label-section">Importer document pour <?php echo htmlspecialchars($nom_etu_sel); ?></p>
        <?php foreach ($docs_labels as $champ => $label) : ?>
        <button class="doc-btn" onclick="ouvrirModal('<?php echo $champ; ?>', '<?php echo htmlspecialchars($label, ENT_QUOTES); ?>')">
            <div class="doc-btn-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
            </div>
            <span style="font-weight:700; font-size:.87rem;"><?php echo $label; ?></span>
            <span style="margin-left:auto; font-size:.74rem; color:var(--gris-texte);">Importer document</span>
        </button>
        <?php endforeach; ?>

        <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<!-- Modal d'upload -->
<div id="modal-bg" style="display:none; position:fixed; inset:0; background:rgba(17,24,39,.45); z-index:100; align-items:flex-end; justify-content:center;">
    <div style="background:#fff; border-radius:20px 20px 0 0; width:100%; max-width:520px; padding:20px 18px 32px;">
        <div style="width:32px; height:4px; border-radius:2px; background:var(--gris-border); margin:0 auto 17px;"></div>
        <p id="modal-titre" style="font-family:'Syne',sans-serif; font-weight:700; font-size:.97rem; margin-bottom:13px;"></p>

        <form method="POST" enctype="multipart/form-data" action="depot_document_tuteur.php?etudiant=<?php echo $id_etu_sel; ?>">
            <input type="hidden" name="type_doc" id="modal-champ">
            <input type="hidden" name="id_etudiant" value="<?php echo $id_etu_sel; ?>">

            <label style="display:flex; flex-direction:column; align-items:center; gap:8px; border:2px dashed var(--gris-border); border-radius:var(--radius); padding:20px; cursor:pointer;" for="modal-fichier">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--gris-texte)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <p id="nom-fic" style="font-size:.81rem; color:var(--gris-texte);">fichier choisie</p>
                <p style="font-size:.72rem; color:var(--gris-texte);">PDF, DOC, DOCX · max 5 Mo</p>
                <input type="file" id="modal-fichier" name="fichier" accept=".pdf,.doc,.docx"
                       onchange="document.getElementById('nom-fic').textContent = this.files[0]?.name || 'Fichier choisi'"
                       style="display:none;">
            </label>

            <div style="display:flex; gap:9px; margin-top:12px; align-items:center;">
                <span style="font-size:.80rem; color:var(--gris-texte);">fichier déposé</span>
                <button type="submit" class="btn" style="flex:1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Importer document
                </button>
                <button type="button" onclick="fermerModal()"
                        style="padding:10px 14px; border:1px solid var(--gris-border); border-radius:8px; background:transparent; font-family:'DM Sans',sans-serif; font-weight:600; font-size:.87rem; cursor:pointer; color:var(--rouge);">
                    ✕
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function ouvrirModal(champ, label) {
        document.getElementById('modal-champ').value = champ;
        document.getElementById('modal-titre').textContent = 'Déposer : ' + label;
        document.getElementById('nom-fic').textContent = 'fichier choisie';
        document.getElementById('modal-bg').style.display = 'flex';
    }
    function fermerModal() {
        document.getElementById('modal-bg').style.display = 'none';
    }
    document.getElementById('modal-bg').addEventListener('click', function (e) {
        if (e.target === this) fermerModal();
    });
</script>
</body>
</html>