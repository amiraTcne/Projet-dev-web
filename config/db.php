<?php 
$host = '   ';
$user = '   ';
$Motpasse = '......';
$db = 'basededonne'
// Connexion à la base de données
$connect = mysqli_connect($host, $user, $Motpasse, $db);

if (!$connect) {
    die("Connexion impossible : " . mysqli_connect_error());
}
mysqli_close($connect);
?>
