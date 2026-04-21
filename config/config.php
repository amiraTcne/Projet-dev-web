<?php
// src/config.php

$host = 'localhost';
$db   = 'cyStages'; // On utilise le nom trouvé dans le fichier SQL
$user = 'root';
$pass = ''; // Vide par défaut sur Windows, 'root' si tu es sur Mac

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

/** * Fonction pour le Fichier Trace (Obligatoire point 4 du projet) [cite: 79]
 */
function loggerAction($message) {
    $fichier = __DIR__ . '/trace.log';
    $journal = date('Y-m-d H:i:s') . " : " . $message . PHP_EOL;
    file_put_contents($fichier, $journal, FILE_APPEND);
}
?>