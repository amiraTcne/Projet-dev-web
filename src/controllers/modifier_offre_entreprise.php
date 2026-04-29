<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php');
    exit();
}

$idEntreprise = (int) $_SESSION['id'];
$numOffre = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);

if (!$connect) {
    die("Erreur : connexion impossible à la base de données.");
}

mysqli_set_charset($connect, "utf8mb4");

$erreur = '';
$message = '';
$offre = null;
$nomEntreprise = 'Entreprise';

// Nom entreprise connectée
$sqlEntreprise = "SELECT nom_entreprise
                  FROM Utilisateur
                  WHERE id = ? AND role_premier = 'Entreprise'";

$stmtEntreprise = mysqli_prepare($connect, $sqlEntreprise);

if ($stmtEntreprise) {
    mysqli_stmt_bind_param($stmtEntreprise, "i", $idEntreprise);
    mysqli_stmt_execute($stmtEntreprise);
    $resultEntreprise = mysqli_stmt_get_result($stmtEntreprise);
    $entreprise = mysqli_fetch_assoc($resultEntreprise);

    if ($entreprise && !empty($entreprise['nom_entreprise'])) {
        $nomEntreprise = $entreprise['nom_entreprise'];
    }

    mysqli_stmt_close($stmtEntreprise);
}

// Récupération de l'offre
$sqlOffre = "SELECT num_offre, titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut
             FROM Offre_Stage
             WHERE num_offre = ? AND id_entreprise = ?";

$stmtOffre = mysqli_prepare($connect, $sqlOffre);

if ($stmtOffre) {
    mysqli_stmt_bind_param($stmtOffre, "ii", $numOffre, $idEntreprise);
    mysqli_stmt_execute($stmtOffre);
    $resultOffre = mysqli_stmt_get_result($stmtOffre);
    $offre = mysqli_fetch_assoc($resultOffre);
    mysqli_stmt_close($stmtOffre);
}

if (!$offre) {
    die("Offre introuvable ou accès interdit.");
}

// Traitement modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_offre'])) {
    $titre = trim($_POST['titre'] ?? '');
    $duree = trim($_POST['duree'] ?? '');
    $date_debut = trim($_POST['date_debut'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $profil = trim($_POST['profil'] ?? '');
    $competences = trim($_POST['competences'] ?? '');
    $statut = trim($_POST['statut'] ?? '');

    $statutsAutorises = ['ouverte', 'pourvue', 'archivee'];

    if ($titre === '' || $duree === '' || $description === '') {
        $erreur = "Veuillez remplir les champs obligatoires : titre, durée et description.";
    } elseif (!ctype_digit($duree) || (int)$duree <= 0) {
        $erreur = "La durée doit être un nombre entier positif.";
    } elseif (!in_array($statut, $statutsAutorises, true)) {
        $erreur = "Le statut sélectionné est invalide.";
    } else {
        $sqlUpdate = "UPDATE Offre_Stage
                      SET titre = ?, mission = ?, competences = ?, filiere_ciblee = ?, duree_semaines = ?, date_debut = ?, statut = ?
                      WHERE num_offre = ? AND id_entreprise = ?";

        $stmtUpdate = mysqli_prepare($connect, $sqlUpdate);

        if ($stmtUpdate) {
            $dureeInt = (int) $duree;
            $dateSql = ($date_debut !== '') ? $date_debut : null;

            mysqli_stmt_bind_param(
                $stmtUpdate,
                "ssssissii",
                $titre,
                $description,
                $competences,
                $profil,
                $dureeInt,
                $dateSql,
                $statut,
                $numOffre,
                $idEntreprise
            );

            if (mysqli_stmt_execute($stmtUpdate)) {
                header("Location: detail_offre_entreprise.php?id=" . $numOffre . "&message=offre_modifiee");
                exit();
            } else {
                $erreur = "Erreur lors de la mise à jour de l'offre.";
            }

            mysqli_stmt_close($stmtUpdate);
        } else {
            $erreur = "Erreur dans la préparation de la requête.";
        }
    }

    // Réinjecter les valeurs saisies si erreur
    $offre['titre'] = $titre;
    $offre['mission'] = $description;
    $offre['competences'] = $competences;
    $offre['filiere_ciblee'] = $profil;
    $offre['duree_semaines'] = $duree;
    $offre['date_debut'] = $date_debut;
    $offre['statut'] = $statut;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'offre - CY Tech</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Montserrat+Alternates:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --gris-ardoise: #676D6B;
            --bleu-royal: #255FAA;
            --bleu-horizon: #5686D9;
            --brume-acier: #ABBACD;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(180deg, #f8fbff 0%, #eef3f9 100%);
            color: #2d3436;
        }

        .page-title {
            font-family: 'Montserrat Alternates', sans-serif;
            color: var(--bleu-royal);
            font-size: 2rem;
            font-weight: 700;
        }

        .card-cy {
            border: 1px solid rgba(171, 186, 205, 0.45);
            border-radius: 1.3rem;
            box-shadow: 0 12px 32px rgba(37, 95, 170, 0.10);
            background-color: #ffffff;
        }

        .form-label {
            color: var(--bleu-royal);
            font-weight: 700;
        }

        .form-control,
        .form-select {
            border-radius: 0.8rem;
            border: 1px solid rgba(171, 186, 205, 0.8);
            padding: 0.75rem 0.9rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--bleu-horizon);
            box-shadow: 0 0 0 0.2rem rgba(86, 134, 217, 0.15);
        }

        .btn-cy {
            background: linear-gradient(135deg, var(--bleu-royal), var(--bleu-horizon));
            color: white;
            border: none;
            font-weight: 700;
            border-radius: 0.8rem;
            padding: 0.75rem 1.35rem;
        }

        .btn-cy:hover {
            color: white;
            opacity: 0.95;
        }

        .btn-outline-cy {
            border: 1px solid var(--bleu-royal);
            color: var(--bleu-royal);
            font-weight: 700;
            border-radius: 0.8rem;
            padding: 0.75rem 1.35rem;
            text-decoration: none;
            background: white;
        }

        .btn-outline-cy:hover {
            background: #eef4ff;
            color: var(--bleu-royal);
        }

        .back-link {
            text-decoration: none;
            color: var(--bleu-royal);
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container py-4 py-md-5">
    <div class="mb-4">
        <a href="detail_offre_entreprise.php?id=<?php echo (int) $numOffre; ?>" class="back-link d-inline-block mb-2">← Retour au détail de l'offre</a>
        <h1 class="page-title mb-1">Modification offre de stage</h1>
        <p class="text-muted mb-0">
            Entreprise connectée : <strong><?php echo htmlspecialchars($nomEntreprise); ?></strong>
        </p>
    </div>

    <?php if (!empty($erreur)) : ?>
        <div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($erreur); ?></div>
    <?php endif; ?>

    <div class="card card-cy p-4 p-md-5">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="titre" class="form-label">Titre</label>
                <input
                    type="text"
                    id="titre"
                    name="titre"
                    class="form-control"
                    value="<?php echo htmlspecialchars($offre['titre']); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="duree" class="form-label">Durée (en semaines)</label>
                <input
                    type="number"
                    id="duree"
                    name="duree"
                    class="form-control"
                    min="1"
                    value="<?php echo htmlspecialchars($offre['duree_semaines']); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="date_debut" class="form-label">Date</label>
                <input
                    type="date"
                    id="date_debut"
                    name="date_debut"
                    class="form-control"
                    value="<?php echo htmlspecialchars($offre['date_debut'] ?? ''); ?>"
                >
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    rows="5"
                    required
                ><?php echo htmlspecialchars($offre['mission']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="profil" class="form-label">Profil recherché</label>
                <textarea
                    id="profil"
                    name="profil"
                    class="form-control"
                    rows="3"
                ><?php echo htmlspecialchars($offre['filiere_ciblee'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="competences" class="form-label">Compétences recherchées</label>
                <input
                    type="text"
                    id="competences"
                    name="competences"
                    class="form-control"
                    value="<?php echo htmlspecialchars($offre['competences'] ?? ''); ?>"
                >
            </div>

            <div class="mb-4">
                <label for="statut" class="form-label">Statut</label>
                <select id="statut" name="statut" class="form-select" required>
                    <option value="ouverte" <?php echo ($offre['statut'] === 'ouverte') ? 'selected' : ''; ?>>Ouverte</option>
                    <option value="pourvue" <?php echo ($offre['statut'] === 'pourvue') ? 'selected' : ''; ?>>Pourvue</option>
                    <option value="archivee" <?php echo ($offre['statut'] === 'archivee') ? 'selected' : ''; ?>>Archivée</option>
                </select>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3">
                <a href="detail_offre_entreprise.php?id=<?php echo (int) $numOffre; ?>" class="btn btn-outline-cy">
                    Annuler
                </a>
                <button type="submit" name="modifier_offre" class="btn btn-cy">
                    Confirmer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>