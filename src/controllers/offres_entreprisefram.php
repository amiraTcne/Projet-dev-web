<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php');
    exit();
}

$idEntreprise = (int) $_SESSION['id'];

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);

if (!$connect) {
    die("Erreur : connexion impossible à la base de données.");
}

mysqli_set_charset($connect, "utf8mb4");

$message = '';
$erreur = '';
$offres = [];
$nomEntreprise = 'Entreprise';

// Récupération du nom de l'entreprise connectée
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


if (isset($_GET['message']) && $_GET['message'] === 'offre_supprimee') {
    $message = "L'offre a bien été supprimée.";
}

// Ajout d'une offre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_offre'])) {
    $titre = trim($_POST['titre'] ?? '');
    $duree = trim($_POST['duree'] ?? '');
    $date_debut = trim($_POST['date_debut'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $profil = trim($_POST['profil'] ?? '');
    $competences = trim($_POST['competences'] ?? '');

    if ($titre === '' || $duree === '' || $description === '') {
        $erreur = "Veuillez remplir les champs obligatoires : titre, durée et description.";
    } elseif (!ctype_digit($duree) || (int)$duree <= 0) {
        $erreur = "La durée doit être un nombre entier positif.";
    } else {
        $sqlInsert = "INSERT INTO Offre_Stage
                      (titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut, id_entreprise)
                      VALUES (?, ?, ?, ?, ?, ?, 'ouverte', ?)";

        $stmtInsert = mysqli_prepare($connect, $sqlInsert);

        if ($stmtInsert) {
            $dateSql = ($date_debut !== '') ? $date_debut : null;
            $dureeInt = (int) $duree;

            mysqli_stmt_bind_param(
                $stmtInsert,
                "ssssisi",
                $titre,
                $description,
                $competences,
                $profil,
                $dureeInt,
                $dateSql,
                $idEntreprise
            );

            if (mysqli_stmt_execute($stmtInsert)) {
                $message = "L'offre de stage a bien été publiée.";
            } else {
                $erreur = "Erreur lors de l'ajout de l'offre.";
            }

            mysqli_stmt_close($stmtInsert);
        } else {
            $erreur = "Erreur dans la préparation de la requête.";
        }
    }
}

$sqlOffres = "SELECT num_offre, titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, date_publication, statut
              FROM Offre_Stage
              WHERE id_entreprise = ?
              ORDER BY date_publication DESC, num_offre DESC";

$stmtOffres = mysqli_prepare($connect, $sqlOffres);

if ($stmtOffres) {
    mysqli_stmt_bind_param($stmtOffres, "i", $idEntreprise);
    mysqli_stmt_execute($stmtOffres);
    $resultOffres = mysqli_stmt_get_result($stmtOffres);

    while ($row = mysqli_fetch_assoc($resultOffres)) {
        $offres[] = $row;
    }

    mysqli_stmt_close($stmtOffres);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de stage - CY Tech</title>

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
            --fond-page: #F4F7FB;
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

        .text-cy {
            color: var(--bleu-royal);
        }

        .card-cy {
            border: 1px solid rgba(171, 186, 205, 0.45);
            border-radius: 1.3rem;
            box-shadow: 0 12px 32px rgba(37, 95, 170, 0.10);
            background-color: #ffffff;
        }

        .offer-card {
            display: block;
            text-decoration: none;
            color: inherit;
            background: linear-gradient(180deg, #f6f9fe 0%, #edf3fb 100%);
            border: 1px solid rgba(86, 134, 217, 0.22);
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .offer-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(37, 95, 170, 0.12);
            color: inherit;
        }

        .badge-cy {
            background-color: var(--bleu-royal);
            color: white;
            font-weight: 600;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            text-transform: capitalize;
        }

        .btn-cy {
            background: linear-gradient(135deg, var(--bleu-royal), var(--bleu-horizon));
            color: white;
            border: none;
            font-weight: 700;
            border-radius: 0.8rem;
            padding: 0.7rem 1.3rem;
        }

        .btn-cy:hover {
            color: white;
            opacity: 0.95;
        }

        .form-label {
            color: var(--bleu-royal);
            font-weight: 700;
        }

        .form-control {
            border-radius: 0.8rem;
            border: 1px solid rgba(171, 186, 205, 0.8);
            padding: 0.75rem 0.9rem;
        }

        .form-control:focus {
            border-color: var(--bleu-horizon);
            box-shadow: 0 0 0 0.2rem rgba(86, 134, 217, 0.15);
        }

        .muted-cy {
            color: var(--gris-ardoise);
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
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <a href="accueil_entreprise.php" class="back-link d-inline-block mb-2">← Retour au tableau de bord</a>
            <h1 class="page-title mb-1">Offres de stage</h1>
            <p class="muted-cy mb-0">
                Entreprise connectée :
                <strong><?php echo htmlspecialchars($nomEntreprise); ?></strong>
            </p>
        </div>
        <div class="muted-cy fw-semibold">CY Tech • Espace entreprise</div>
    </div>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success rounded-4"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($erreur)) : ?>
        <div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($erreur); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-cy p-4 h-100">
                <h2 class="h3 text-cy fw-bold mb-4">Offres déposées</h2>

                <?php if (empty($offres)) : ?>
                    <div class="border rounded-4 p-4 bg-light muted-cy">
                        Vous n'avez encore publié aucune offre de stage.
                    </div>
                <?php else : ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($offres as $offre) : ?>
                            <a href="detail_offre_entreprise.php?id=<?php echo (int) $offre['num_offre']; ?>" class="offer-card p-3 p-md-4">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-2 mb-2">
                                    <h3 class="h5 fw-bold text-cy mb-0">
                                        <?php echo htmlspecialchars($offre['titre']); ?>
                                    </h3>
                                    <span class="badge-cy">
                                        <?php echo htmlspecialchars($offre['statut']); ?>
                                    </span>
                                </div>

                                <p class="muted-cy mb-2">
                                    <?php echo nl2br(htmlspecialchars($offre['mission'])); ?>
                                </p>

                                <?php if (!empty($offre['competences'])) : ?>
                                    <p class="mb-1">
                                        <strong>Compétences :</strong>
                                        <?php echo htmlspecialchars($offre['competences']); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($offre['filiere_ciblee'])) : ?>
                                    <p class="mb-1">
                                        <strong>Profil recherché :</strong>
                                        <?php echo htmlspecialchars($offre['filiere_ciblee']); ?>
                                    </p>
                                <?php endif; ?>

                                <p class="mb-0 fw-semibold" style="color:#5686D9;">
                                    Durée : <?php echo htmlspecialchars($offre['duree_semaines']); ?> semaine(s)
                                    <?php if (!empty($offre['date_debut'])) : ?>
                                        | Début : <?php echo htmlspecialchars($offre['date_debut']); ?>
                                    <?php endif; ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-cy p-4">
                <h2 class="h3 text-cy fw-bold mb-4">Ajouter une offre de stage</h2>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre</label>
                        <input type="text" id="titre" name="titre" class="form-control" placeholder="Ajouter titre" required>
                    </div>

                    <div class="mb-3">
                        <label for="duree" class="form-label">Durée (en semaines)</label>
                        <input type="number" id="duree" name="duree" class="form-control" placeholder="Ajouter durée" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="date_debut" class="form-label">Date</label>
                        <input type="date" id="date_debut" name="date_debut" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Ajouter description" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="profil" class="form-label">Profil recherché</label>
                        <textarea id="profil" name="profil" class="form-control" rows="3" placeholder="Ajouter profil"></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="competences" class="form-label">Compétences recherchées</label>
                        <input type="text" id="competences" name="competences" class="form-control" placeholder="Ajouter compétence">
                    </div>

                    <div class="text-end">
                        <button type="submit" name="ajouter_offre" class="btn btn-cy">
                            Publier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>