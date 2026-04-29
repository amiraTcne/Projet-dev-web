<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'cystage.projet@gmail.com';
    $mail->Password = 'nsdncpgmixslfdrw';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('cystage.projet@gmail.com', 'CY Stage');
    $mail->addAddress('mira.tcne@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test SMTP CY Stage';
    $mail->Body = '<p>Test réussi : PHPMailer fonctionne.</p>';

    $mail->send();
    echo "Mail envoyé avec succès.";
} catch (Exception $e) {
    echo "Erreur : " . $mail->ErrorInfo;
}