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

$idAuteur = $_SESSION['id'] ?? 3;
$numStage = isset($_GET['num_stage']) ? (int) $_GET['num_stage'] : 0;

$message = '';
$erreur = '';

if ($numStage <= 0) {
    die("Stage introuvable.");
}

$sqlStage = "SELECT 
                s.num_stage,
                s.titre,
                s.mission,
                s.duree_semaines,
                s.date_debut,
                s.date_fin,
                s.avancement,
                s.statut,
                s.id_etudiant,
                u.nom,
                u.prenom,
                d.num_dossier
             FROM Stage s
             INNER JOIN Utilisateur u ON s.id_etudiant = u.id
             LEFT JOIN Dossier_Stage d ON d.num_stage = s.num_stage
             WHERE s.num_stage = ? AND s.id_entreprise = ?";

$stmtStage = mysqli_prepare($connect, $sqlStage);
mysqli_stmt_bind_param($stmtStage, "ii", $numStage, $idEntreprise);
mysqli_stmt_execute($stmtStage);
$resultStage = mysqli_stmt_get_result($stmtStage);
$stage = mysqli_fetch_assoc($resultStage);
mysqli_stmt_close($stmtStage);

if (!$stage) {
    die("Ce stage n'existe pas ou n'appartient pas à votre entreprise.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_remarque'])) {
    $contenu = trim($_POST['contenu_remarque'] ?? '');

    if ($contenu === '') {
        $erreur = "La remarque ne peut pas être vide.";
    } elseif (empty($stage['num_dossier'])) {
        $erreur = "Aucun dossier de stage associé pour enregistrer une remarque.";
    } else {
        $sqlInsert = "INSERT INTO Remarque (contenu, num_dossier, id_auteur) VALUES (?, ?, ?)";
        $stmtInsert = mysqli_prepare($connect, $sqlInsert);

        if ($stmtInsert) {
            mysqli_stmt_bind_param($stmtInsert, "sii", $contenu, $stage['num_dossier'], $idAuteur);

            if (mysqli_stmt_execute($stmtInsert)) {
                $message = "Remarque ajoutée avec succès.";
            } else {
                $erreur = "Erreur lors de l'ajout de la remarque.";
            }

            mysqli_stmt_close($stmtInsert);
        }
    }
}

$remarquesEntreprise = [];
$remarquesStagiaire = [];

if (!empty($stage['num_dossier'])) {
    $sqlRem = "SELECT 
                    r.contenu,
                    r.date_creation,
                    r.id_auteur,
                    u.nom,
                    u.prenom,
                    u.role_premier
               FROM Remarque r
               INNER JOIN Utilisateur u ON r.id_auteur = u.id
               WHERE r.num_dossier = ?
               ORDER BY r.date_creation ASC";

    $stmtRem = mysqli_prepare($connect, $sqlRem);
    mysqli_stmt_bind_param($stmtRem, "i", $stage['num_dossier']);
    mysqli_stmt_execute($stmtRem);
    $resultRem = mysqli_stmt_get_result($stmtRem);

    while ($row = mysqli_fetch_assoc($resultRem)) {
        if ($row['role_premier'] === 'Entreprise') {
            $remarquesEntreprise[] = $row;
        } elseif ($row['role_premier'] === 'Etudiant') {
            $remarquesStagiaire[] = $row;
        }
    }

    mysqli_stmt_close($stmtRem);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de stage</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Montserrat+Alternates:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --gris-ardoise: #676D6B;
            --bleu-royal: #255FAA;
            --bleu-horizon: #5686D9;
            --brume-acier: #ABBACD;
            --fond: #f5f7fb;
            --blanc: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(180deg, #f8fbff 0%, #eef3f9 100%);
            color: #2d3436;
            padding: 32px 18px;
        }

        .wrapper {
            max-width: 980px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 8px;
            color: var(--bleu-royal);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        h1 {
            font-family: 'Montserrat Alternates', sans-serif;
            color: var(--bleu-royal);
            font-size: 2rem;
            margin-bottom: 22px;
        }

        .card-page {
            background: var(--blanc);
            border-radius: 24px;
            box-shadow: 0 14px 35px rgba(37, 95, 170, 0.10);
            padding: 28px 24px;
            border: 1px solid rgba(171, 186, 205, 0.35);
        }

        .stage-header {
            margin-bottom: 22px;
        }

        .stage-title {
            font-size: 1.4rem;
            color: #2d3436;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stagiaire {
            color: var(--gris-ardoise);
            font-weight: 600;
        }

        .section {
            margin-bottom: 24px;
        }

        .section h2 {
            color: var(--bleu-royal);
            font-size: 1.25rem;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .bubble {
            background: #edf3fb;
            border: 1px solid rgba(171, 186, 205, 0.7);
            border-radius: 10px;
            padding: 10px 12px;
            color: var(--gris-ardoise);
            margin-bottom: 8px;
        }

        .bubble strong {
            color: #2d3436;
        }

        textarea, input[type="text"] {
            width: 100%;
            border: 1px solid rgba(171, 186, 205, 0.85);
            border-radius: 10px;
            padding: 12px;
            font-family: 'Montserrat', sans-serif;
            margin-top: 6px;
            margin-bottom: 10px;
            outline: none;
        }

        textarea:focus, input[type="text"]:focus {
            border-color: var(--bleu-horizon);
            box-shadow: 0 0 0 3px rgba(86, 134, 217, 0.14);
        }

        .btn-row {
            text-align: right;
        }

        button {
            background: var(--bleu-royal);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 7px 14px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: var(--bleu-horizon);
        }

        .message {
            background: #e8f6ee;
            color: #1f7a39;
            border: 1px solid #b7e4c7;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .erreur {
            background: #fdeaea;
            color: #b42318;
            border: 1px solid #f4b4b4;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .mission-box {
            background: #edf3fb;
            border: 1px solid rgba(171, 186, 205, 0.7);
            border-radius: 10px;
            padding: 12px 14px;
            color: var(--gris-ardoise);
            line-height: 1.6;
        }

        .meta {
            margin-top: 10px;
            color: var(--bleu-horizon);
            font-weight: 600;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <a href="stages_en_cours_entreprise.php" class="back-link">← Retour aux stages en cours</a>
        <h1>Gestion de stage</h1>

        <div class="card-page">
            <?php if (!empty($message)) : ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($erreur)) : ?>
                <div class="erreur"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <div class="stage-header">
                <div class="stage-title"><?php echo htmlspecialchars($stage['titre']); ?></div>
                <div class="stagiaire"><?php echo htmlspecialchars($stage['prenom'] . ' ' . $stage['nom']); ?></div>
                <div class="meta">
                    Statut : <?php echo htmlspecialchars($stage['statut']); ?>
                    | Avancement : <?php echo htmlspecialchars($stage['avancement']); ?>%
                </div>
            </div>

            <div class="section">
                <h2>Remarques Stagiaires :</h2>
                <?php if (empty($remarquesStagiaire)) : ?>
                    <div class="bubble">Aucune remarque stagiaire pour le moment.</div>
                <?php else : ?>
                    <?php foreach ($remarquesStagiaire as $rem) : ?>
                        <div class="bubble">
                            <?php echo nl2br(htmlspecialchars($rem['contenu'])); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2>Remarques Entreprise :</h2>
                <?php if (empty($remarquesEntreprise)) : ?>
                    <div class="bubble">Aucune remarque entreprise pour le moment.</div>
                <?php else : ?>
                    <?php foreach ($remarquesEntreprise as $rem) : ?>
                        <div class="bubble">
                            <?php echo nl2br(htmlspecialchars($rem['contenu'])); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2>Ajouter une remarque</h2>
                <form method="POST" action="">
                    <textarea name="contenu_remarque" rows="4" placeholder="Ajouter une remarque"></textarea>
                    <div class="btn-row">
                        <button type="submit" name="ajouter_remarque">Envoyer</button>
                    </div>
                </form>
            </div>

            <div class="section">
                <h2>Missions attribuées :</h2>
                <div class="mission-box">
                    <?php if (!empty($stage['mission'])) : ?>
                        <?php echo nl2br(htmlspecialchars($stage['mission'])); ?>
                    <?php else : ?>
                        Aucune mission renseignée.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>