<?php
session_start();

$host = "localhost";
$dbname = "cyStages";
$user = "userpro";
$pass = "projetStage26.";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier qu'une entreprise est connectée
if (!isset($_SESSION['id'])) {
    die("Utilisateur non connecté.");
}

$idEntreprise = $_SESSION['id'];
$message = "";

// Mise à jour de la description
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['description'])) {
    $description = trim($_POST['description']);

    $sqlUpdate = "UPDATE Utilisateur SET description = :description WHERE id = :id AND role_premier = 'Entreprise'";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':description' => $description,
        ':id' => $idEntreprise
    ]);

    $message = "Description mise à jour avec succès.";
}

// Récupération des infos de l'entreprise
$sql = "SELECT nom_entreprise, description 
        FROM Utilisateur 
        WHERE id = :id AND role_premier = 'Entreprise'";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $idEntreprise]);
$entreprise = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entreprise) {
    die("Entreprise introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil entreprise</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6fb;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            color: #1f4fa3;
            margin-bottom: 10px;
        }

        h2 {
            color: #1f4fa3;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .description-box {
            background-color: #eef3fa;
            border: 1px solid #cfd8e6;
            border-radius: 10px;
            padding: 15px;
            min-height: 180px;
            white-space: pre-line;
            margin-bottom: 20px;
            color: #444;
        }

        textarea {
            width: 100%;
            min-height: 220px;
            padding: 15px;
            border: 1px solid #cfd8e6;
            border-radius: 10px;
            resize: vertical;
            font-size: 16px;
            box-sizing: border-box;
        }

        .buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        button, .btn-retour {
            background-color: #2f63b8;
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        button:hover, .btn-retour:hover {
            background-color: #244e91;
        }
    </style>

    <script>
        function activerEdition() {
            document.getElementById("mode-affichage").style.display = "none";
            document.getElementById("mode-edition").style.display = "block";
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Profil</h1>
        <h2>Description de l'entreprise</h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div id="mode-affichage">
            <div class="description-box">
                <?php
                echo !empty($entreprise['description'])
                    ? nl2br(htmlspecialchars($entreprise['description']))
                    : "Aucune description renseignée.";
                ?>
            </div>

            <div class="buttons">
                <button type="button" onclick="activerEdition()">Modifier</button>
                <a href="accueil_entreprise.php" class="btn-retour">Retour</a>
            </div>
        </div>

        <div id="mode-edition" style="display:none;">
            <form method="POST" action="">
                <textarea name="description" required><?php echo htmlspecialchars($entreprise['description'] ?? ''); ?></textarea>

                <div class="buttons">
                    <button type="submit">Enregistrer</button>
                    <a href="profil_entreprise.php" class="btn-retour">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>