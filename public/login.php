<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <img class="logo_connexion" src="assets/img/logo.png">
    <h2>Stage</h2>
    <h1>Connexion</h1>
    <form action="../src/controllers/verifierConnexion.php" method="POST">
        <input type="text" id="login" name="login" value="Adresse mail @" required>
        <input type="password" id="mdp" name="mdp" value="Mot de passe" required>
        <a href="#">Mot de passe oublié ? </a><br><br>
        <input type="submit" value="Connexion">
    </form>
    <a href="../src/controllers/index.php" style="display:inline-block; margin-top:20px; text-decoration:none; color:#255FAA; font-weight:bold; text-alignement:center; "> Retour</a>
   
</body>
</html>
