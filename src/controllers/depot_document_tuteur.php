<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sécurité Tuteur
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
        $msg_err = 'Veuillez choisir un fichier à déposer.';
    } else {
        $ext    = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
        $taille = $_FILES['fichier']['size'];

        if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
            $msg_err = 'Format non autorisé. Seuls PDF, DOC et DOCX sont acceptés.';
        } elseif ($taille > 5 * 1024 * 1024) {
            $msg_err = 'Fichier trop lourd (5 Mo maximum).';
        } else {
            // Création du répertoire et de l'url
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

    /* Chargement de la liste des étudiants */
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
    'convention_url' => 'Convention de stage',
    'rapport_url'    => 'Rapport de stage',
    'resume_url'     => 'Résumé de la mission',
    'fiche_eval_url' => "Fiche d'évaluation",
];

$nom_etu_sel = '';
foreach ($etudiants as $e) {
    if ($e['id'] === $id_etu_sel) { $nom_etu_sel = $e['nom_complet']; break; }
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dépôt Documents — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy {
            border: 1px solid rgba(171,186,205,.4); border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem;
        }

        .doc-btn {
            display: flex; align-items: center; gap: 15px; padding: 15px;
            border: 1px solid #e5e7eb; border-radius: 14px; background: #fff;
            cursor: pointer; transition: all 0.2s ease; text-align: left;
            width: 100%; margin-bottom: 12px;
        }
        .doc-btn:hover { border-color: var(--bleu-clair); box-shadow: 0 4px 12px rgba(27,79,155,.08); transform: translateY(-2px); }
        .doc-icon { width: 45px; height: 45px; border-radius: 12px; background: #eef2ff; color: var(--bleu); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        
        .upload-zone { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: background 0.2s; }
        .upload-zone:hover { background: #f8fafc; border-color: var(--bleu); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_tuteur.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-workspace me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_tuteur.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Dépôt de documents</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Importez les fichiers relatifs au stage de vos étudiants</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <?php if (empty($etudiants)) : ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-people text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucun étudiant suivi</h5>
            <p class="text-muted mb-0">Vous devez d'abord être assigné à un étudiant pour déposer des documents.</p>
        </div>
    <?php else : ?>
        <div class="row g-4">
            
            <!-- Sélection Étudiant -->
            <div class="col-md-4">
                <div class="card-cy h-100">
                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:.8rem; letter-spacing:1px;">Sélectionner un étudiant</h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($etudiants as $e) : ?>
                            <a href="depot_document_tuteur.php?etudiant=<?php echo (int)$e['id']; ?>" class="btn text-start rounded-pill <?php echo $id_etu_sel === $e['id'] ? 'btn-primary shadow-sm' : 'btn-outline-secondary border-0 bg-light'; ?> fw-semibold" style="<?php echo $id_etu_sel === $e['id'] ? 'background-color: var(--bleu); border-color: var(--bleu);' : ''; ?>">
                                <i class="bi bi-person-fill me-2"></i> <?php echo h($e['nom_complet']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Dépôt des documents -->
            <div class="col-md-8">
                <div class="card-cy h-100 d-flex flex-column">
                    <?php if (!$id_etu_sel) : ?>
                        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-muted p-4">
                            <i class="bi bi-cloud-arrow-up fs-1 mb-3 opacity-50"></i>
                            <p class="mb-0 text-center">Sélectionnez un étudiant dans la liste de gauche pour importer un document.</p>
                        </div>
                    <?php else : ?>
                        <h5 class="fw-bold mb-4 pb-2 border-bottom" style="font-family:'Syne',sans-serif; color:#111827;">
                            Importer pour <?php echo h($nom_etu_sel); ?>
                        </h5>

                        <div class="d-flex flex-column">
                            <?php foreach ($docs_labels as $champ => $label) : ?>
                                <button type="button" class="doc-btn" onclick="ouvrirModal('<?php echo $champ; ?>', '<?php echo h($label, ENT_QUOTES); ?>')">
                                    <div class="doc-icon"><i class="bi bi-file-earmark-arrow-up"></i></div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo $label; ?></h6>
                                        <small class="text-muted">Cliquez pour importer ce fichier</small>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<!-- Modal Upload Bootstrap 5 -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="modal-titre" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="modal-titre" style="color:var(--bleu); font-family:'Syne',sans-serif;"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3 pb-4">
        <form method="POST" enctype="multipart/form-data" action="depot_document_tuteur.php?etudiant=<?php echo $id_etu_sel; ?>">
            <input type="hidden" name="type_doc" id="modal-champ">
            <input type="hidden" name="id_etudiant" value="<?php echo $id_etu_sel; ?>">

            <label class="upload-zone d-block w-100 mb-4" for="modal-fichier">
                <i class="bi bi-cloud-upload text-muted mb-2 d-block" style="font-size: 2.5rem;"></i>
                <span id="nom-fic" class="d-block fw-bold text-dark mb-1">Cliquer pour choisir un fichier</span>
                <span class="text-muted" style="font-size: .8rem;">Formats acceptés : PDF, DOC, DOCX (Max 5 Mo)</span>
                <input type="file" id="modal-fichier" name="fichier" accept=".pdf,.doc,.docx" class="d-none" onchange="document.getElementById('nom-fic').textContent = this.files[0] ? this.files[0].name : 'Cliquer pour choisir un fichier'">
            </label>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light border fw-bold flex-grow-1 rounded-pill text-muted" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary fw-bold flex-grow-1 rounded-pill" style="background:var(--bleu); border:none;">
                    <i class="bi bi-upload me-1"></i> Importer
                </button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialisation de la modale Bootstrap 5
    const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));

    function ouvrirModal(champ, label) {
        document.getElementById('modal-champ').value = champ;
        document.getElementById('modal-titre').textContent = 'Déposer : ' + label;
        
        // Reset du champ fichier
        document.getElementById('modal-fichier').value = '';
        document.getElementById('nom-fic').textContent = 'Cliquer pour choisir un fichier';
        
        uploadModal.show();
    }
</script>
</body>
</html>