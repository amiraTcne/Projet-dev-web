<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <img class="logo_connexion" src="/assets/img/logo.png">
    <h2>Stage</h2>
    <h1>Connexion</h1>
    <form action="verifierConnexion.php" method="POST">
        <label>Login :</label><br>
        <input type="text" id="login" name="login" required><br>

        <label>Mot de passe :</label><br>
        <input type="password" id="mdp" name="mdp" required><br><br>

        <input type="submit" value="Connexion">
    </form>
    <a href="#">Mot de passe oublié ? </a>
   
</body>
</html>
