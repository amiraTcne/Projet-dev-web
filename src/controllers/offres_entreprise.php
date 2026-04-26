<?php
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

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
    <title>Offres de stage</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Montserrat+Alternates:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --gris-ardoise: #676D6B;
            --bleu-royal: #255FAA;
            --bleu-horizon: #5686D9;
            --brume-acier: #ABBACD;
            --fond: #F5F7FB;
            --blanc: #FFFFFF;
            --texte: #2E3438;
            --ombre: rgba(37, 95, 170, 0.12);
            --succes-bg: #e8f6ee;
            --succes-text: #1f7a39;
            --erreur-bg: #fdeaea;
            --erreur-text: #b42318;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(180deg, #f8fafe 0%, #eef3f9 100%);
            color: var(--texte);
            padding: 32px 20px;
        }

        .container {
            max-width: 1220px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .top-left a {
            display: inline-block;
            margin-bottom: 8px;
            color: var(--bleu-royal);
            text-decoration: none;
            font-weight: 600;
        }

        .top-left a:hover {
            text-decoration: underline;
        }

        .top-left h1 {
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 34px;
            color: var(--bleu-royal);
            letter-spacing: 0.3px;
        }

        .top-right {
            color: var(--gris-ardoise);
            font-weight: 600;
            font-size: 15px;
        }

        .feedback {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 12px;
            font-weight: 500;
        }

        .message {
            background: var(--succes-bg);
            color: var(--succes-text);
            border: 1px solid #b7e4c7;
        }

        .erreur {
            background: var(--erreur-bg);
            color: var(--erreur-text);
            border: 1px solid #f4b4b4;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            align-items: start;
        }

        .bloc {
            background: var(--blanc);
            border-radius: 24px;
            padding: 26px;
            box-shadow: 0 14px 35px var(--ombre);
            border: 1px solid rgba(171, 186, 205, 0.35);
        }

        .bloc h2 {
            font-family: 'Montserrat Alternates', sans-serif;
            font-size: 25px;
            color: var(--bleu-royal);
            margin-bottom: 20px;
        }

        .offres-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .offre-card {
            background: linear-gradient(180deg, #f6f9fe 0%, #edf3fb 100%);
            border: 1px solid rgba(86, 134, 217, 0.25);
            border-radius: 18px;
            padding: 18px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .offre-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(86, 134, 217, 0.12);
        }

        .offre-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .offre-header h3 {
            color: var(--bleu-royal);
            font-size: 20px;
            font-weight: 700;
        }

        .badge {
            background: var(--bleu-royal);
            color: white;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .offre-card p {
            color: var(--gris-ardoise);
            margin-bottom: 8px;
            line-height: 1.6;
            font-size: 14.8px;
        }

        .offre-card strong {
            color: var(--texte);
        }

        .meta {
            color: var(--bleu-horizon) !important;
            font-weight: 600;
            margin-top: 10px;
        }

        .aucune-offre {
            background: #f8fbff;
            color: var(--gris-ardoise);
            border: 1px dashed var(--brume-acier);
            border-radius: 16px;
            padding: 18px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .champ {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .champ label {
            color: var(--bleu-royal);
            font-weight: 700;
            font-size: 14px;
        }

        .champ input,
        .champ textarea {
            width: 100%;
            border: 1px solid rgba(171, 186, 205, 0.7);
            border-radius: 12px;
            padding: 12px 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            background: #fbfdff;
            color: var(--texte);
            outline: none;
            transition: all 0.2s ease;
        }

        .champ input::placeholder,
        .champ textarea::placeholder {
            color: #9aa7b8;
        }

        .champ input:focus,
        .champ textarea:focus {
            border-color: var(--bleu-horizon);
            box-shadow: 0 0 0 4px rgba(86, 134, 217, 0.14);
            background: #ffffff;
        }

        .champ textarea {
            resize: vertical;
            min-height: 96px;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 6px;
        }

        .btn {
            border: none;
            background: linear-gradient(135deg, var(--bleu-royal), var(--bleu-horizon));
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 15px;
            padding: 13px 24px;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(37, 95, 170, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(37, 95, 170, 0.22);
        }

        .infos-entreprise {
            margin-top: 8px;
            color: var(--gris-ardoise);
            font-size: 14px;
        }

        @media (max-width: 960px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .top-left h1 {
                font-size: 28px;
            }

            .bloc {
                padding: 20px;
            }
        }

        @media (max-width: 560px) {
            body {
                padding: 20px 14px;
            }

            .top-left h1 {
                font-size: 24px;
            }

            .bloc h2 {
                font-size: 21px;
            }

            .offre-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .actions {
                justify-content: stretch;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <div class="top-left">
            <a href="accueil_entreprise.php">← Retour au tableau de bord</a>
            <h1>Offres de stage</h1>
            <div class="infos-entreprise">
                Entreprise connectée :
                <strong><?php echo htmlspecialchars($_SESSION['nom_entreprise'] ?? 'Entreprise'); ?></strong>
            </div>
        </div>
        <div class="top-right">CY Tech • Espace entreprise</div>
    </div>

    <?php if (!empty($message)) : ?>
        <div class="feedback message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($erreur)) : ?>
        <div class="feedback erreur"><?php echo htmlspecialchars($erreur); ?></div>
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