<?php
    $host = 'localhost';
    $user = 'use'; // remplacer par votre user
    $motdepass ='motDePasse'; // remplacer par votre mot de passe
    $db = 'cyStage';

    $connect = mysqli_connect($host,$user,$motdepass,$db);

    if(!$connect){

        die("connection mauvaise:" . mysqli_connect_error());
    }

    mysqli_close($connect);

    ?>
