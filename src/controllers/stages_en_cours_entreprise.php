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

$stages = [];


$idEntreprise = $_SESSION['id']; 

$sql = "SELECT 
            s.num_stage,
            s.titre,
            s.statut,
            s.id_etudiant,
            u.nom,
            u.prenom
        FROM Stage s
        INNER JOIN Utilisateur u ON s.id_etudiant = u.id
        WHERE s.id_entreprise = ?
          AND s.statut = 'en_cours'
        ORDER BY s.titre ASC, u.nom ASC";

$stmt = mysqli_prepare($connect, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $idEntreprise);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $titre = $row['titre'];

        if (!isset($stages[$titre])) {
            $stages[$titre] = [
                'num_stage' => $row['num_stage'],
                'titre' => $row['titre'],
                'stagiaires' => []
            ];
        }

        $stages[$titre]['stagiaires'][] = [
            'id_etudiant' => $row['id_etudiant'],
            'nom_complet' => $row['prenom'] . ' ' . $row['nom'],
            'num_stage' => $row['num_stage']
        ];
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stages en cours</title>

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
            max-width: 950px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 24px;
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
        }

        .card-page {
            background: var(--blanc);
            border-radius: 24px;
            box-shadow: 0 14px 35px rgba(37, 95, 170, 0.10);
            padding: 28px 24px;
            border: 1px solid rgba(171, 186, 205, 0.35);
        }

        .stage-block {
            margin-bottom: 28px;
        }

        .stage-title {
            color: var(--bleu-royal);
            font-weight: 800;
            font-size: 1.35rem;
            margin-bottom: 10px;
        }

        .stage-title a {
            text-decoration: none;
            color: inherit;
        }

        .stage-title a:hover {
            text-decoration: underline;
        }

        .stagiaires-label {
            color: var(--bleu-horizon);
            font-weight: 700;
            margin-bottom: 6px;
        }

        .stagiaire-link {
            display: block;
            margin-left: 14px;
            margin-bottom: 4px;
            color: var(--gris-ardoise);
            text-decoration: none;
            font-weight: 500;
        }

        .stagiaire-link:hover {
            color: var(--bleu-royal);
            text-decoration: underline;
        }

        .empty {
            color: var(--gris-ardoise);
            background: #f8fbff;
            border: 1px dashed var(--brume-acier);
            border-radius: 16px;
            padding: 18px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <a href="accueil_entreprise.php" class="back-link">← Retour au tableau de bord</a>
            <h1>Stages en cours</h1>
        </div>

        <div class="card-page">
            <?php if (empty($stages)) : ?>
                <div class="empty">Aucun stage en cours pour le moment.</div>
            <?php else : ?>
                <?php foreach ($stages as $stage) : ?>
                    <div class="stage-block">
                        <div class="stage-title">
                            <a href="gestion_stages_en_cours_entreprise.php?num_stage=<?php echo urlencode($stage['num_stage']); ?>">
                                <?php echo htmlspecialchars($stage['titre']); ?>
                            </a>
                        </div>
                        <div class="stagiaires-label">Etudiant stagiaire<?php echo count($stage['stagiaires']) > 1 ? 's' : ''; ?> :</div>

                        <?php foreach ($stage['stagiaires'] as $stagiaire) : ?>
                            <a class="stagiaire-link" href="gestion_stages_en_cours_entreprise.php?num_stage=<?php echo urlencode($stagiaire['num_stage']); ?>">
                                <?php echo htmlspecialchars($stagiaire['nom_complet']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>