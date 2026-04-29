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

$offre = null;
$nomEntreprise = 'Entreprise';

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

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_offre'])) {
    $delete = mysqli_prepare($connect, "DELETE FROM Offre_Stage WHERE num_offre = ? AND id_entreprise = ?");
    mysqli_stmt_bind_param($delete, "ii", $numOffre, $idEntreprise);
    mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);

    header('Location: offres_entreprisefram.php?message=offre_supprimee');
    exit();
}

$sql = "SELECT num_offre, titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, date_publication, statut
        FROM Offre_Stage
        WHERE num_offre = ? AND id_entreprise = ?";

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $numOffre, $idEntreprise);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $offre = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$offre) {
    die("Offre introuvable ou accès interdit.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail de l'offre - CY Tech</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Montserrat+Alternates:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --gris-ardoise: #676D6B;
            --bleu-royal: #255FAA;
            --bleu-horizon: #5686D9;
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

        .back-link {
            text-decoration: none;
            color: var(--bleu-royal);
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card-cy {
            border: 1px solid rgba(171, 186, 205, 0.45);
            border-radius: 1.3rem;
            box-shadow: 0 12px 32px rgba(37, 95, 170, 0.10);
            background-color: #ffffff;
        }

        .label-cy {
            color: var(--bleu-royal);
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .value-box {
            background: #f6f9fe;
            border: 1px solid rgba(86, 134, 217, 0.18);
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
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
            padding: 0.75rem 1.3rem;
            text-decoration: none;
        }

        .btn-cy:hover {
            color: white;
            opacity: 0.95;
        }

        .btn-danger-cy {
            background: #dc3545;
            color: white;
            border: none;
            font-weight: 700;
            border-radius: 0.8rem;
            padding: 0.75rem 1.3rem;
        }

        .btn-danger-cy:hover {
            background: #bb2d3b;
            color: white;
        }

        .modal-delete-overlay {
            position: fixed;
            inset: 0;
            background: rgba(35, 45, 65, 0.28);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .modal-delete-overlay.show {
            display: flex;
        }

        .modal-delete-box {
            width: 100%;
            max-width: 320px;
            background: #ffffff;
            border: 1px solid rgba(37, 95, 170, 0.15);
            border-radius: 0.35rem;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
            padding: 1.35rem 1.2rem 1rem;
            text-align: center;
            animation: popupFade 0.18s ease-out;
        }

        .modal-delete-icon {
            width: 42px;
            height: 42px;
            border: 1.5px solid #2d3436;
            border-radius: 0.45rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.85rem;
            font-size: 1.35rem;
            font-weight: 700;
            color: #2d3436;
            line-height: 1;
        }

        .modal-delete-text {
            font-size: 0.98rem;
            color: #2d3436;
            margin-bottom: 1rem;
            line-height: 1.45;
        }

        .modal-delete-actions {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            align-items: center;
        }

        .btn-cancel-modal,
        .btn-confirm-modal {
            border: none;
            border-radius: 0.35rem;
            padding: 0.45rem 1rem;
            min-width: 110px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-cancel-modal {
            background: #2f6db5;
            color: #ffffff;
        }

        .btn-cancel-modal:hover {
            background: #255a96;
        }

        .btn-confirm-modal {
            background: #1f4f8f;
            color: #ffffff;
        }

        .btn-confirm-modal:hover {
            background: #183d6d;
        }

        @keyframes popupFade {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>
<body>

<div class="container py-4 py-md-5">
    <div class="mb-4">
        <a href="offres_entreprisefram.php" class="back-link d-inline-block mb-2">← Retour aux offres</a>
        <h1 class="page-title mb-1"><?php echo htmlspecialchars($offre['titre']); ?></h1>
        <p class="text-muted mb-0">
            Entreprise connectée : <strong><?php echo htmlspecialchars($nomEntreprise); ?></strong>
        </p>
    </div>

    <div class="card card-cy p-4 p-md-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h2 class="h4 mb-0" style="color:#255FAA;">Détail de l'offre</h2>
            <span class="badge-cy"><?php echo htmlspecialchars($offre['statut']); ?></span>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="label-cy">Description</div>
                <div class="value-box"><?php echo nl2br(htmlspecialchars($offre['mission'])); ?></div>
            </div>

            <div class="col-md-6">
                <div class="label-cy">Compétences recherchées</div>
                <div class="value-box"><?php echo !empty($offre['competences']) ? htmlspecialchars($offre['competences']) : 'Non renseigné'; ?></div>
            </div>

            <div class="col-md-6">
                <div class="label-cy">Profil recherché</div>
                <div class="value-box"><?php echo !empty($offre['filiere_ciblee']) ? htmlspecialchars($offre['filiere_ciblee']) : 'Non renseigné'; ?></div>
            </div>

            <div class="col-md-4">
                <div class="label-cy">Durée</div>
                <div class="value-box"><?php echo htmlspecialchars($offre['duree_semaines']); ?> semaine(s)</div>
            </div>

            <div class="col-md-4">
                <div class="label-cy">Date de début</div>
                <div class="value-box"><?php echo !empty($offre['date_debut']) ? htmlspecialchars($offre['date_debut']) : 'Non renseignée'; ?></div>
            </div>

            <div class="col-md-4">
                <div class="label-cy">Date de publication</div>
                <div class="value-box"><?php echo htmlspecialchars($offre['date_publication']); ?></div>
            </div>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-3 mt-4">
            <a class="btn btn-cy" href="modifier_offre_entreprise.php?id=<?php echo (int) $offre['num_offre']; ?>">
                Modifier
            </a>
                <button type="button" class="btn btn-danger-cy" id="openDeleteModal">
                    Supprimer
                </button>
            </div>

            <div class="modal-delete-overlay" id="deleteModal">
                <div class="modal-delete-box">
                    <div class="modal-delete-icon">
                        !
                    </div>

                    <p class="modal-delete-text">
                        Êtes-vous sûr de vouloir supprimer cette offre ?
                    </p>

                    <form method="POST" class="modal-delete-actions">
                        <button type="button" class="btn-cancel-modal" id="closeDeleteModal">Annuler</button>
                        <button type="submit" name="supprimer_offre" class="btn-confirm-modal">Confirmer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const deleteModal = document.getElementById('deleteModal');
    const openDeleteModal = document.getElementById('openDeleteModal');
    const closeDeleteModal = document.getElementById('closeDeleteModal');

    openDeleteModal.addEventListener('click', function () {
        deleteModal.classList.add('show');
    });

    closeDeleteModal.addEventListener('click', function () {
        deleteModal.classList.remove('show');
    });

    deleteModal.addEventListener('click', function (e) {
        if (e.target === deleteModal) {
            deleteModal.classList.remove('show');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            deleteModal.classList.remove('show');
        }
    });
</script>
</body>
</html>