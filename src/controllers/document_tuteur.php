<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn      = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$etudiants = [];
$dossier   = null;

$id_etu_sel = (int)($_GET['etudiant'] ?? 0);

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Étudiants suivis */
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

    /* Dossier de l'étudiant sélectionné */
    if ($id_etu_sel > 0) {
        $sd = mysqli_prepare($conn,
            "SELECT d.*, s.titre AS titre_stage, ent.nom_entreprise
             FROM Dossier_Stage d
             JOIN Stage s ON s.num_stage = d.num_stage
             JOIN Utilisateur ent ON ent.id = s.id_entreprise
             WHERE d.id_etudiant = ? AND s.id_tuteur = ?
             ORDER BY d.date_creation DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($sd, 'ii', $id_etu_sel, $_SESSION['id']);
        mysqli_stmt_execute($sd);
        $dossier = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
        mysqli_stmt_close($sd);
    }
    mysqli_close($conn);
}

$docs = [
    'convention_url' => 'Convention de Stage',
    'rapport_url'    => 'Rapport de Stage',
    'resume_url'     => 'Résumé Synthétique',
    'fiche_eval_url' => "Fiche d'Évaluation",
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
    <title>Documents Envoyés — CY Stage</title>
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
        .doc-box {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 1.2rem; display: flex; align-items: center; gap: 15px;
            transition: all 0.2s ease-in-out; margin-bottom: 1rem;
        }
        .doc-box:hover { border-color: var(--bleu-clair); box-shadow: 0 6px 12px rgba(27,79,155,.05); transform: translateY(-2px); }
        .doc-icon {
            width: 50px; height: 50px; border-radius: 12px; background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;
        }
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
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Documents des étudiants</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Consultez et téléchargez les documents liés aux dossiers</p>
        </div>
    </div>

    <?php if (empty($etudiants)) : ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-people text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucun étudiant suivi</h5>
            <p class="text-muted mb-0">Vous n'avez pas encore d'étudiants affectés sous votre responsabilité.</p>
        </div>
    <?php else : ?>
        <div class="row g-4">
            
            <!-- Colonne Sélection Étudiant[cite: 21] -->
            <div class="col-md-4">
                <div class="card-cy h-100">
                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:.8rem; letter-spacing:1px;">Sélectionner un étudiant</h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($etudiants as $e) : ?>
                            <a href="document_tuteur.php?etudiant=<?php echo (int)$e['id']; ?>" class="btn text-start rounded-pill <?php echo $id_etu_sel === $e['id'] ? 'btn-primary shadow-sm' : 'btn-outline-secondary border-0 bg-light'; ?> fw-semibold" style="<?php echo $id_etu_sel === $e['id'] ? 'background-color: var(--bleu); border-color: var(--bleu);' : ''; ?>">
                                <i class="bi bi-person-fill me-2"></i> <?php echo h($e['nom_complet']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne Liste des Documents[cite: 21] -->
            <div class="col-md-8">
                <div class="card-cy h-100 d-flex flex-column">
                    <?php if (!$id_etu_sel) : ?>
                        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-muted p-4">
                            <i class="bi bi-folder2-open fs-1 mb-3 opacity-50"></i>
                            <p class="mb-0 text-center">Sélectionnez un étudiant dans la liste de gauche pour visualiser ses documents.</p>
                        </div>
                    <?php elseif (!$dossier) : ?>
                        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-muted p-4 border rounded-4 bg-light" style="border-style: dashed !important;">
                            <i class="bi bi-file-earmark-x fs-1 mb-3 opacity-50 text-danger"></i>
                            <h6 class="fw-bold text-dark">Aucun dossier trouvé</h6>
                            <p class="mb-0 text-center small">L'étudiant <?php echo h($nom_etu_sel); ?> n'a pas encore de dossier de stage actif.</p>
                        </div>
                    <?php else : ?>
                        <h5 class="fw-bold mb-3 pb-3 border-bottom d-flex align-items-center gap-2" style="font-family:'Syne',sans-serif; color:#111827;">
                            <i class="bi bi-archive text-muted"></i> Documents de <?php echo h($nom_etu_sel); ?>
                        </h5>
                        
                        <!-- Informations du stage[cite: 21] -->
                        <div class="bg-light p-3 rounded-4 border mb-4">
                            <span class="badge bg-primary rounded-pill mb-2">Stage en cours</span>
                            <div class="fw-bold text-dark" style="font-size: .9rem;"><?php echo h($dossier['titre_stage']); ?></div>
                            <div class="text-muted" style="font-size: .85rem;"><i class="bi bi-building me-1"></i> <?php echo h($dossier['nom_entreprise']); ?></div>
                        </div>

                        <!-- Liste des fichiers[cite: 21] -->
                        <div class="d-flex flex-column">
                            <?php foreach ($docs as $champ => $label) :
                                $url    = $dossier[$champ] ?? null;
                                $depose = !empty($url);
                            ?>
                            <div class="doc-box">
                                <div class="doc-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1" style="color:var(--bleu);"><?php echo $label; ?></h6>
                                    <?php if ($depose) : ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="bi bi-check-circle me-1"></i> Transmis</span>
                                    <?php else : ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1"><i class="bi bi-x-circle me-1"></i> Document manquant</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($depose) : ?>
                                        <a href="/<?php echo h($url); ?>" download class="btn btn-primary btn-sm rounded-pill fw-bold px-3 shadow-sm" style="background:var(--bleu); border:none;">
                                            <i class="bi bi-download"></i> <span class="d-none d-sm-inline ms-1">Télécharger</span>
                                        </a>
                                    <?php else : ?>
                                        <button class="btn btn-light btn-sm rounded-pill px-3 border text-muted" disabled><i class="bi bi-dash"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>