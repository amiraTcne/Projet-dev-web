<?php
session_start();

/* Vérification de la connexion et du rôle */
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: login.php');
    exit();
}

/* Connexion à la base */
$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);

if (!$connect) {
    die("Erreur : connexion impossible à la base de données.");
}

mysqli_set_charset($connect, "utf8mb4");

$idEntreprise = (int) $_SESSION['id'];
$message = '';
$erreur = '';

/* Traitement du formulaire */
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
        $sql = "INSERT INTO Offre_Stage
                (titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, statut, id_entreprise)
                VALUES (?, ?, ?, ?, ?, ?, 'ouverte', ?)";

        $stmt = mysqli_prepare($connect, $sql);

        if ($stmt) {
            $dateSql = ($date_debut !== '') ? $date_debut : null;
            $dureeInt = (int)$duree;

            mysqli_stmt_bind_param(
                $stmt,
                "ssssisi",
                $titre,
                $description,
                $competences,
                $profil,
                $dureeInt,
                $dateSql,
                $idEntreprise
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "L'offre de stage a bien été publiée.";
            } else {
                $erreur = "Erreur lors de l'ajout de l'offre.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $erreur = "Erreur dans la préparation de la requête.";
        }
    }
}

/* Récupération des offres de l'entreprise connectée */
$offres = [];

$sqlOffres = "SELECT num_offre, titre, mission, competences, filiere_ciblee, duree_semaines, date_debut, date_publication, statut
              FROM Offre_Stage
              WHERE id_entreprise = ?
              ORDER BY date_publication DESC, num_offre DESC";

$stmtOffres = mysqli_prepare($connect, $sqlOffres);

if ($stmtOffres) {
    mysqli_stmt_bind_param($stmtOffres, "i", $idEntreprise);
    mysqli_stmt_execute($stmtOffres);
    $result = mysqli_stmt_get_result($stmtOffres);

    while ($row = mysqli_fetch_assoc($result)) {
        $offres[] = $row;
    }

    mysqli_stmt_close($stmtOffres);
} else {
    $erreur = "Impossible de charger les offres.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de stage - Entreprise</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f4f6fb;
            color: #1f2a44;
            padding: 30px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .top-bar a {
            text-decoration: none;
            color: #1f5fbf;
            font-weight: bold;
        }

        .top-bar h1 {
            color: #1f5fbf;
            font-size: 32px;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
        }

        .bloc {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 24px;
        }

        .bloc h2 {
            color: #1f5fbf;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .message {
            background-color: #e7f8ec;
            color: #1f7a39;
            border: 1px solid #b7e4c7;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .erreur {
            background-color: #fdeaea;
            color: #b42318;
            border: 1px solid #f4b4b4;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .offres-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .offre-card {
            background: #eef3f9;
            border: 1px solid #d7e0ed;
            border-radius: 14px;
            padding: 16px;
        }

        .offre-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .offre-header h3 {
            color: #1f5fbf;
            font-size: 20px;
        }

        .badge {
            background: #dce9ff;
            color: #1f5fbf;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .offre-card p {
            margin-bottom: 8px;
            line-height: 1.5;
            color: #4b5563;
        }

        .meta {
            font-size: 14px;
            color: #6b7280;
        }

        .aucune-offre {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 18px;
            color: #64748b;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .champ {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .champ label {
            color: #1f5fbf;
            font-weight: bold;
        }

        .champ input,
        .champ textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cfd8e3;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
        }

        .champ input:focus,
        .champ textarea:focus {
            border-color: #1f5fbf;
            box-shadow: 0 0 0 3px rgba(31, 95, 191, 0.12);
        }

        .champ textarea {
            resize: vertical;
            min-height: 90px;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
        }

        .btn {
            background-color: #1f5fbf;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 22px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #184c98;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .top-bar h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <div>
            <a href="accueil_entreprise.php">← Retour au tableau de bord</a>
            <h1>Offres de Stages</h1>
        </div>
    </div>

    <?php if (!empty($message)) : ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($erreur)) : ?>
        <div class="erreur"><?php echo htmlspecialchars($erreur); ?></div>
    <?php endif; ?>

    <div class="layout">
        <section class="bloc">
            <h2>Offres déposées</h2>

            <?php if (empty($offres)) : ?>
                <div class="aucune-offre">
                    Vous n'avez encore publié aucune offre de stage.
                </div>
            <?php else : ?>
                <div class="offres-list">
                    <?php foreach ($offres as $offre) : ?>
                        <article class="offre-card">
                            <div class="offre-header">
                                <h3><?php echo htmlspecialchars($offre['titre']); ?></h3>
                                <span class="badge"><?php echo htmlspecialchars($offre['statut']); ?></span>
                            </div>

                            <p><?php echo nl2br(htmlspecialchars($offre['mission'])); ?></p>

                            <?php if (!empty($offre['competences'])) : ?>
                                <p><strong>Compétences :</strong> <?php echo htmlspecialchars($offre['competences']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($offre['filiere_ciblee'])) : ?>
                                <p><strong>Profil recherché :</strong> <?php echo htmlspecialchars($offre['filiere_ciblee']); ?></p>
                            <?php endif; ?>

                            <p class="meta">
                                Durée : <?php echo htmlspecialchars($offre['duree_semaines']); ?> semaine(s)
                                <?php if (!empty($offre['date_debut'])) : ?>
                                    | Début : <?php echo htmlspecialchars($offre['date_debut']); ?>
                                <?php endif; ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="bloc">
            <h2>Ajouter une offre de stage</h2>

            <form method="POST" action="">
                <div class="champ">
                    <label for="titre">Titre</label>
                    <input type="text" id="titre" name="titre" placeholder="Ajouter titre" required>
                </div>

                <div class="champ">
                    <label for="duree">Durée (en semaines)</label>
                    <input type="number" id="duree" name="duree" placeholder="Ajouter durée" min="1" required>
                </div>

                <div class="champ">
                    <label for="date_debut">Date</label>
                    <input type="date" id="date_debut" name="date_debut">
                </div>

                <div class="champ">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Ajouter description" required></textarea>
                </div>

                <div class="champ">
                    <label for="profil">Profil recherché</label>
                    <textarea id="profil" name="profil" placeholder="Ajouter profil"></textarea>
                </div>

                <div class="champ">
                    <label for="competences">Compétences recherchées</label>
                    <input type="text" id="competences" name="competences" placeholder="Ajouter compétence">
                </div>

                <div class="actions">
                    <button type="submit" name="ajouter_offre" class="btn">Publier</button>
                </div>
            </form>
        </section>
    </div>
</div>

</body>
</html>