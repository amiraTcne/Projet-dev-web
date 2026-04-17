<?php
session_start();
session_destroy();
/* nous avons utilisé un chemin relatif car nous avions pu constater une erreur lorsque l'une d'entre nous s'est connecter avec un localhost:8080 au lieu de localhost:8000 */
header('Location: ../../public/login.php');
exit();
?>