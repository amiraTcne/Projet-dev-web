<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('UTC');

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['tmp_2fa_user_id'], $_SESSION['tmp_2fa_email'], $_SESSION['tmp_2fa_role'])) {
    header('Location: login.php');
    exit();
}

$host    = 'localhost';
$dbname  = 'cyStages';
$db_user = 'userpro';
$db_pass = 'projetStage26.';

$connect = mysqli_connect($host, $db_user, $db_pass, $dbname);
if (!$connect) {
    die("Connexion impossible à la base de données.");
}

mysqli_set_charset($connect, "utf8mb4");
mysqli_query($connect, "SET time_zone = '+00:00'");

$idUser = (int) $_SESSION['tmp_2fa_user_id'];
$email  = $_SESSION['tmp_2fa_email'];
$role   = $_SESSION['tmp_2fa_role'];

$erreur = '';
$info   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    unset($_SESSION['tmp_2fa_code_sent']);
    header('Location: double_auth.php');
    exit();
}

if (!isset($_SESSION['tmp_2fa_code_sent'])) {
    $sqlUser = "SELECT id FROM Utilisateur WHERE id = ? LIMIT 1";
    $stmtUser = mysqli_prepare($connect, $sqlUser);

    if (!$stmtUser) {
        die("Erreur lors de la vérification de l'utilisateur.");
    }

    mysqli_stmt_bind_param($stmtUser, "i", $idUser);
    mysqli_stmt_execute($stmtUser);
    $resultUser = mysqli_stmt_get_result($stmtUser);
    $userExists = mysqli_fetch_assoc($resultUser);
    mysqli_stmt_close($stmtUser);

    if (!$userExists) {
        die("Erreur : l'utilisateur connecté n'existe pas en base. ID reçu = " . (int) $idUser);
    }

    $sqlOldCodes = "UPDATE Double_Authentification
                    SET utilise = 1
                    WHERE id_user = ? AND utilise = 0";
    $stmtOldCodes = mysqli_prepare($connect, $sqlOldCodes);

    if ($stmtOldCodes) {
        mysqli_stmt_bind_param($stmtOldCodes, "i", $idUser);
        mysqli_stmt_execute($stmtOldCodes);
        mysqli_stmt_close($stmtOldCodes);
    }

    $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

    $sqlInsert = "INSERT INTO Double_Authentification (id_user, code_verification, date_expiration, utilise)
                  VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE), 0)";
    $stmtInsert = mysqli_prepare($connect, $sqlInsert);

    if ($stmtInsert) {
        mysqli_stmt_bind_param($stmtInsert, "is", $idUser, $code);
        $okInsert = mysqli_stmt_execute($stmtInsert);
        mysqli_stmt_close($stmtInsert);

        if (!$okInsert) {
            $erreur = "Impossible d'enregistrer le code de vérification.";
        }
    } else {
        $erreur = "Erreur dans la préparation du code de vérification.";
    }

    if ($erreur === '') {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cystage.projet@gmail.com';
            $mail->Password   = 'nsdncpgmixslfdrw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('cystage.projet@gmail.com', 'CY Stage');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Votre code de vérification CY Stage';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #1f2d3d;'>
                    <h2 style='color: #255FAA; margin-bottom: 12px;'>Connexion sécurisée</h2>
                    <p>Bonjour,</p>
                    <p>Voici votre code de vérification :</p>
                    <div style='font-size: 32px; font-weight: 700; letter-spacing: 10px; color: #255FAA; margin: 20px 0;'>
                        {$code}
                    </div>
                    <p>Ce code expire dans <strong>10 minutes</strong>.</p>
                    <p>Si vous n'êtes pas à l'origine de cette tentative, ignorez simplement cet e-mail.</p>
                </div>
            ";
            $mail->AltBody = "Bonjour,\n\nVotre code de vérification est : {$code}\n\nCe code expire dans 10 minutes.\n\nCY Stage";

            $mail->send();

            $_SESSION['tmp_2fa_code_sent'] = true;
            $info = "Un code de vérification a été envoyé à votre adresse e-mail.";
        } catch (Exception $e) {
            $erreur = "Le mail de vérification n'a pas pu être envoyé : " . $mail->ErrorInfo;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $codeSaisi = trim(
        ($_POST['code1'] ?? '') .
        ($_POST['code2'] ?? '') .
        ($_POST['code3'] ?? '') .
        ($_POST['code4'] ?? '')
    );

    if (!preg_match('/^\d{4}$/', $codeSaisi)) {
        $erreur = "Veuillez saisir un code à 4 chiffres.";
    } else {
        $sqlCheck = "SELECT id_2fa
                     FROM Double_Authentification
                     WHERE id_user = ?
                       AND code_verification = ?
                       AND utilise = 0
                       AND date_expiration >= UTC_TIMESTAMP()
                     ORDER BY id_2fa DESC
                     LIMIT 1";

        $stmtCheck = mysqli_prepare($connect, $sqlCheck);

        if ($stmtCheck) {
            mysqli_stmt_bind_param($stmtCheck, "is", $idUser, $codeSaisi);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);
            $ligne = mysqli_fetch_assoc($resultCheck);
            mysqli_stmt_close($stmtCheck);

            if ($ligne) {
                $id2fa = (int) $ligne['id_2fa'];

                $sqlUse = "UPDATE Double_Authentification SET utilise = 1 WHERE id_2fa = ?";
                $stmtUse = mysqli_prepare($connect, $sqlUse);

                if ($stmtUse) {
                    mysqli_stmt_bind_param($stmtUse, "i", $id2fa);
                    mysqli_stmt_execute($stmtUse);
                    mysqli_stmt_close($stmtUse);
                }

                $_SESSION['id']             = $_SESSION['tmp_2fa_user_id'];
                $_SESSION['email']          = $_SESSION['tmp_2fa_email'];
                $_SESSION['nom']            = $_SESSION['tmp_2fa_nom'] ?? '';
                $_SESSION['prenom']         = $_SESSION['tmp_2fa_prenom'] ?? '';
                $_SESSION['role']           = $_SESSION['tmp_2fa_role'];
                $_SESSION['role_second']    = $_SESSION['tmp_2fa_role_second'] ?? null;
                $_SESSION['role_troisieme'] = $_SESSION['tmp_2fa_role_troisieme'] ?? null;
                $_SESSION['auth_2fa_ok']    = true;

                if ($_SESSION['role'] === 'Etudiant') {
                    $_SESSION['filiere']     = $_SESSION['tmp_2fa_filiere'] ?? null;
                    $_SESSION['niveau']      = $_SESSION['tmp_2fa_niveau'] ?? null;
                    $_SESSION['annee_promo'] = $_SESSION['tmp_2fa_annee_promo'] ?? null;
                }

                if ($_SESSION['role'] === 'Tuteur') {
                    $_SESSION['specialite']  = $_SESSION['tmp_2fa_specialite'] ?? null;
                    $_SESSION['departement'] = $_SESSION['tmp_2fa_departement'] ?? null;
                }

                if ($_SESSION['role'] === 'Jury') {
                    $_SESSION['specialite'] = $_SESSION['tmp_2fa_specialite'] ?? null;
                    $_SESSION['commission'] = $_SESSION['tmp_2fa_commission'] ?? null;
                    $_SESSION['annee_jury'] = $_SESSION['tmp_2fa_annee_jury'] ?? null;
                }

                if ($_SESSION['role'] === 'Entreprise') {
                    $_SESSION['num_siret']      = $_SESSION['tmp_2fa_num_siret'] ?? null;
                    $_SESSION['nom_entreprise'] = $_SESSION['tmp_2fa_nom_entreprise'] ?? null;
                    $_SESSION['secteur']        = $_SESSION['tmp_2fa_secteur'] ?? null;
                    $_SESSION['ville']          = $_SESSION['tmp_2fa_ville'] ?? null;
                    $_SESSION['nb_stagiere']    = $_SESSION['tmp_2fa_nb_stagiere'] ?? null;
                }

                unset($_SESSION['tmp_2fa_user_id']);
                unset($_SESSION['tmp_2fa_email']);
                unset($_SESSION['tmp_2fa_nom']);
                unset($_SESSION['tmp_2fa_prenom']);
                unset($_SESSION['tmp_2fa_role']);
                unset($_SESSION['tmp_2fa_role_second']);
                unset($_SESSION['tmp_2fa_role_troisieme']);
                unset($_SESSION['tmp_2fa_filiere']);
                unset($_SESSION['tmp_2fa_niveau']);
                unset($_SESSION['tmp_2fa_annee_promo']);
                unset($_SESSION['tmp_2fa_specialite']);
                unset($_SESSION['tmp_2fa_departement']);
                unset($_SESSION['tmp_2fa_commission']);
                unset($_SESSION['tmp_2fa_annee_jury']);
                unset($_SESSION['tmp_2fa_num_siret']);
                unset($_SESSION['tmp_2fa_nom_entreprise']);
                unset($_SESSION['tmp_2fa_secteur']);
                unset($_SESSION['tmp_2fa_ville']);
                unset($_SESSION['tmp_2fa_nb_stagiere']);
                unset($_SESSION['tmp_2fa_code_sent']);

                if ($_SESSION['role'] === 'Entreprise') {
                    header('Location: ../src/controllers/accueil_entreprise.php');
                    exit();
                } elseif ($_SESSION['role'] === 'Admin') {
                    header('Location: ../src/controllers/accueil_admin.php');
                    exit();
                } elseif ($_SESSION['role'] === 'Etudiant') {
                    header('Location: ../src/controllers/accueil_etudiant.php');
                    exit();
                } elseif ($_SESSION['role'] === 'Tuteur') {
                    header('Location: ../src/controllers/accueil_tuteur.php');
                    exit();
                } elseif ($_SESSION['role'] === 'Jury') {
                    header('Location: ../src/controllers/accueil_jury.php');
                    exit();
                } else {
                    header('Location: login.php');
                    exit();
                }
            } else {
                $erreur = "Code invalide ou expiré.";
            }
        } else {
            $erreur = "Erreur lors de la vérification du code.";
        }
    }
}

$emailMasque = preg_replace('/(^.).*(@.*$)/', '$1****$2', $email);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Double authentification - CY Stage</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #eef4fb 0%, #f8fbff 45%, #e8eef8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            color: #1f2d3d;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(37, 95, 170, 0.12);
            border: 1px solid rgba(171, 186, 205, 0.35);
        }

        .auth-left {
            background: linear-gradient(180deg, #255FAA 0%, #5686D9 100%);
            color: #ffffff;
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-left h1 {
            margin: 0 0 18px;
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .auth-left p {
            margin: 0 0 18px;
            font-size: 1rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.92);
            max-width: 470px;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.15);
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 22px;
        }

        .auth-right {
            padding: 48px 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
        }

        .logo-box {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 28px;
        }

        .logo-box img {
            max-width: 180px;
            width: 100%;
            height: 70px;
            object-fit: contain;
            display: block;
        }

        .title {
            text-align: center;
            color: #255FAA;
            font-size: 1.9rem;
            font-weight: 800;
            margin: 0 0 12px;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 0.98rem;
            line-height: 1.6;
            margin: 0 0 10px;
        }

        .email {
            text-align: center;
            color: #5686D9;
            font-weight: 700;
            margin-bottom: 28px;
            word-break: break-word;
        }

        .label {
            color: #255FAA;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 16px;
            text-align: center;
        }

        .code-boxes {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .code-boxes input {
            width: 56px;
            height: 58px;
            border: 1px solid #d4dbe6;
            border-radius: 14px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2d3d;
            background: #f9fbfe;
            outline: none;
            transition: all 0.2s ease;
        }

        .code-boxes input:focus {
            border-color: #255FAA;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 95, 170, 0.12);
        }

        .btn-main {
            width: 100%;
            background: linear-gradient(135deg, #255FAA 0%, #5686D9 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 15px 18px;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            box-shadow: 0 10px 22px rgba(37, 95, 170, 0.18);
        }

        .btn-main:hover {
            transform: translateY(-1px);
            opacity: 0.98;
        }

        .btn-link {
            display: block;
            margin: 16px auto 0;
            background: none;
            border: none;
            color: #5686D9;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
        }

        .error,
        .info {
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.93rem;
            margin-bottom: 18px;
            text-align: center;
        }

        .error {
            background: #fff1f2;
            color: #b42318;
            border: 1px solid #fecdd3;
        }

        .info {
            background: #eff6ff;
            color: #255FAA;
            border: 1px solid #bfdbfe;
        }

        @media (max-width: 900px) {
            .auth-wrapper {
                grid-template-columns: 1fr;
            }

            .auth-left {
                padding: 38px 28px;
            }

            .auth-right {
                padding: 34px 24px 40px;
            }

            .auth-left h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 520px) {
            body {
                padding: 16px;
            }

            .auth-right,
            .auth-left {
                padding-left: 20px;
                padding-right: 20px;
            }

            .code-boxes {
                gap: 10px;
            }

            .code-boxes input {
                width: 48px;
                height: 52px;
                font-size: 1.3rem;
            }

            .title {
                font-size: 1.6rem;
            }

            .logo-box img {
                max-width: 150px;
                height: 58px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-left">
            <div class="auth-badge">CY Stage • Connexion sécurisée</div>
            <h1>Double authentification</h1>
        </div>

        <div class="auth-right">
            <div class="auth-card">
                <div class="logo-box">
                    <img src="assets/img/logo.png" alt="Logo CY Stage">
                </div>

                <h2 class="title">Vérification e-mail</h2>
                <p class="subtitle">Un code de sécurité a été envoyé à l’adresse suivante :</p>
                <div class="email"><?php echo htmlspecialchars($emailMasque); ?></div>

                <div class="label">Saisir le code de vérification</div>

                <?php if (!empty($erreur)) : ?>
                    <div class="error"><?php echo htmlspecialchars($erreur); ?></div>
                <?php endif; ?>

                <?php if (!empty($info)) : ?>
                    <div class="info"><?php echo htmlspecialchars($info); ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="code-boxes">
                        <input type="text" name="code1" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input type="text" name="code2" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input type="text" name="code3" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input type="text" name="code4" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                    </div>

                    <button type="submit" name="verify_code" class="btn-main">Valider</button>
                </form>

                <form method="POST">
                    <button type="submit" name="resend_code" class="btn-link">Renvoyer un code</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.code-boxes input');

        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/[^0-9]/g, '');
                if (input.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>