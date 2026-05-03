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
            // Mise à jour du dossier dans la base (uniquement si non validé)
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

// Fonction utilitaire pour sécuriser l'affichage HTML
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Dossier de Stage — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        /* Navbar */
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        /* Cards */
        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06);
            background: #fff;
            padding: 2rem;
        }

        .doc-box {
            background: #fbfdff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.2s ease-in-out;
        }
        .doc-box:hover {
            border-color: var(--bleu-clair);
            box-shadow: 0 8px 16px rgba(27,79,155,.05);
        }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 12px;
            border-radius: 999px;
            letter-spacing: 0.5px;
        }
        .status-incomplet { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-valide { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-en_cours { background: #eef2ff; color: var(--bleu); border: 1px solid #c7d2fe; }

        .icon-circle {
            width: 45px; height: 45px; border-radius: 12px;
            background: #eef2ff; color: var(--bleu);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 15px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_etudiant.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline">
                <i class="bi bi-mortarboard-fill me-2"></i>
                <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i>
                <span class="d-none d-sm-inline">Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">

    <!-- En-tête -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_etudiant.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Mon Dossier de Stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Gérez vos documents officiels (convention, rapport...)</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i> <strong><?php echo h($msg_ok); ?></strong>
        </div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i> <strong><?php echo h($msg_err); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Contenu Principal -->
    <div class="card-cy">
        <?php if (!$dossier): ?>
            <!-- État vide si aucun dossier actif -->
            <div class="text-center py-5">
                <i class="bi bi-folder-x text-muted opacity-50 mb-3 d-block" style="font-size: 3.5rem;"></i>
                <h3 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucun dossier actif</h3>
                <p class="text-muted mb-4">Vous devez d'abord confirmer un stage pour générer votre dossier.</p>
                <a href="candidatures_etudiant.php" class="btn btn-primary rounded-pill fw-bold px-4" style="background:var(--bleu); border:none;">
                    Voir mes candidatures
                </a>
            </div>
        <?php else: ?>
            
            <!-- Informations du stage -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start border-bottom pb-4 mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-2" style="font-family:'Syne',sans-serif; color:#111827;">
                        <?php echo h($dossier['titre_stage']); ?>
                    </h4>
                    <p class="text-muted fw-semibold mb-0" style="font-size:.9rem;">
                        <i class="bi bi-building me-1"></i> Entreprise : <strong class="text-dark"><?php echo h($dossier['nom_entreprise']); ?></strong>
                    </p>
                </div>
                <div>
                    <?php 
                        // Style dynamique pour le statut
                        $statut_class = 'incomplet';
                        if ($dossier['statut'] === 'valide') $statut_class = 'valide';
                        if ($dossier['statut'] === 'en_cours') $statut_class = 'en_cours';
                    ?>
                    <span class="status-badge status-<?php echo $statut_class; ?>">
                        <i class="bi bi-info-circle me-1"></i> <?php echo h($dossier['statut']); ?>
                    </span>
                </div>
            </div>

            <!-- Grille des documents -->
            <div class="row g-4">
                
                <!-- Convention de Stage -->
                <div class="col-md-6">
                    <div class="doc-box">
                        <div class="icon-circle"><i class="bi bi-file-earmark-text"></i></div>
                        <h5 class="fw-bold mb-3" style="font-size:1.05rem; color:var(--bleu);">Convention de stage</h5>
                        
                        <div class="mb-3">
                            <?php if ($dossier['convention_url']): ?>
                                <a href="<?php echo h($dossier['convention_url']); ?>" class="btn btn-outline-success btn-sm rounded-pill fw-bold" target="_blank">
                                    <i class="bi bi-eye me-1"></i> Document transmis
                                </a>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                                    <i class="bi bi-x-circle me-1"></i> Document manquant
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Formulaire d'upload (masqué si le dossier est totalement validé) -->
                        <?php if ($dossier['statut'] !== 'valide'): ?>
                            <form method="POST" enctype="multipart/form-data" class="mt-4">
                                <input type="hidden" name="type_doc" value="convention_url">
                                <label class="form-label text-muted" style="font-size:0.8rem; font-weight:600;">Déposer ou mettre à jour (.pdf)</label>
                                <div class="input-group input-group-sm">
                                    <input type="file" class="form-control rounded-start-pill" name="fichier" required>
                                    <button class="btn btn-primary rounded-end-pill px-3" type="submit" style="background:var(--bleu); border-color:var(--bleu);">
                                        <i class="bi bi-upload"></i>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Rapport de Stage -->
                <div class="col-md-6">
                    <div class="doc-box">
                        <div class="icon-circle"><i class="bi bi-journal-bookmark"></i></div>
                        <h5 class="fw-bold mb-3" style="font-size:1.05rem; color:var(--bleu);">Rapport de stage</h5>
                        
                        <div class="mb-3">
                            <?php if ($dossier['rapport_url']): ?>
                                <a href="<?php echo h($dossier['rapport_url']); ?>" class="btn btn-outline-success btn-sm rounded-pill fw-bold" target="_blank">
                                    <i class="bi bi-eye me-1"></i> Rapport déposé
                                </a>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2">
                                    <i class="bi bi-clock-history me-1"></i> À fournir en fin de stage
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($dossier['statut'] !== 'valide'): ?>
                            <form method="POST" enctype="multipart/form-data" class="mt-4">
                                <input type="hidden" name="type_doc" value="rapport_url">
                                <label class="form-label text-muted" style="font-size:0.8rem; font-weight:600;">Déposer le rapport final (.pdf)</label>
                                <div class="input-group input-group-sm">
                                    <input type="file" class="form-control rounded-start-pill" name="fichier" required>
                                    <button class="btn btn-primary rounded-end-pill px-3" type="submit" style="background:var(--bleu); border-color:var(--bleu);">
                                        <i class="bi bi-upload"></i>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>