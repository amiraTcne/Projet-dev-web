<?php
/* Démarrage de la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn  = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$stage = null;

/* Variable nous permettant de savoir quelle vue afficher */
$vue = 'formulaire'; 

/* on traite l'envoi de l'avancement de la semaine */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'avancement') {
    $texte = trim($_POST['avancement_semaine'] ?? '');

    if (!empty($texte) && $conn) {
        $contenu = '[AVANCEMENT SEMAINE] ' . $texte;

        $sd = mysqli_prepare($conn,
            "SELECT d.num_dossier FROM Dossier_Stage d
             JOIN Stage s ON s.num_stage = d.num_stage
             WHERE d.id_etudiant = ? AND s.statut = 'en_cours'
             ORDER BY s.date_debut DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($sd, 'i', $_SESSION['id']);
        mysqli_stmt_execute($sd);
        $rdr = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
        mysqli_stmt_close($sd);

        if ($rdr) {
            $ins = mysqli_prepare($conn,
                "INSERT INTO Remarque (contenu, num_dossier, id_auteur) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'sii', $contenu, $rdr['num_dossier'], $_SESSION['id']);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
        $vue = 'succes-semaine';
    }
}

/* on traite l'envoi d'une remarque simple */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remarque') {
    $texte = trim($_POST['remarques'] ?? '');

    if (!empty($texte) && $conn) {
        $sd = mysqli_prepare($conn,
            "SELECT d.num_dossier FROM Dossier_Stage d
             WHERE d.id_etudiant = ? ORDER BY d.date_creation DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($sd, 'i', $_SESSION['id']);
        mysqli_stmt_execute($sd);
        $rdr = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
        mysqli_stmt_close($sd);

        if ($rdr) {
            $ins = mysqli_prepare($conn,
                "INSERT INTO Remarque (contenu, num_dossier, id_auteur) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'sii', $texte, $rdr['num_dossier'], $_SESSION['id']);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
        $vue = 'succes-remarque';
    }
}

/* on charge le stage en cours*/
if ($vue === 'formulaire' && $conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT s.titre, s.mission, s.date_fin, ent.nom_entreprise
         FROM Stage s
         JOIN Utilisateur ent ON ent.id = s.id_entreprise
         WHERE s.id_etudiant = ? AND s.statut IN ('en_cours', 'en_attente')
         ORDER BY s.date_debut DESC LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $stage = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

if ($conn) mysqli_close($conn);

/* on prépare les missions au'on va afficher */
$missions = [];
if ($stage && !empty($stage['mission'])) {
    $missions = array_filter(array_map('trim', explode("\n", $stage['mission'])));
}
if (empty($missions) && $stage) {
    $missions = [
        'Prise en main du projet et des outils',
        'Développement des fonctionnalités',
        'Rédaction de la documentation',
    ];
}

$prochain = $stage && !empty($stage['date_fin'])
            ? date('d/m/Y', strtotime($stage['date_fin']))
            : 'Non planifié';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avancement Stage — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06);
            background: #fff;
            padding: 2rem;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--bleu);
            margin-bottom: 1rem;
            display: flex; align-items: center; gap: 8px;
        }

        .form-control:focus {
            border-color: var(--bleu-clair);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 199, 0.15);
        }

        .success-icon {
            width: 80px; height: 80px; border-radius: 50%;
            background: #d1fae5; color: #059669;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; margin: 0 auto 20px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_etudiant.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline">
                <i class="bi bi-mortarboard-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-right d-sm-none"></i> <span class="d-none d-sm-inline">Déconnexion</span>
            </a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width: 800px;">

    <!-- En-tête -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?php echo $vue !== 'formulaire' ? 'avancement_etudiant.php' : 'accueil_etudiant.php'; ?>" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Avancement du Stage</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Communiquez avec votre tuteur pédagogique</p>
        </div>
    </div>

    <?php if ($vue === 'formulaire') : ?>
        <div class="card-cy">
            <?php if (!$stage) : ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x text-muted opacity-50 mb-3 d-block" style="font-size: 3.5rem;"></i>
                    <h3 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucun stage en cours</h3>
                    <p class="text-muted mb-4">L'avancement apparaîtra ici quand ton stage sera validé.</p>
                    <a href="offres_etudiant.php" class="btn btn-primary rounded-pill fw-bold px-4" style="background:var(--bleu); border:none;">
                        Chercher un stage
                    </a>
                </div>
            <?php else : ?>
                
                <!-- Détails du stage en cours-->
                <div class="border-bottom pb-4 mb-4">
                    <h4 class="fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#111827;"><?php echo h($stage['titre']); ?></h4>
                    <p class="text-muted fw-semibold mb-0"><i class="bi bi-building me-1"></i> <?php echo h($stage['nom_entreprise']); ?></p>
                </div>

                <div class="section-title"><i class="bi bi-list-task"></i> Missions du stage</div>
                <div class="bg-light rounded-4 p-3 mb-4 border">
                    <ul class="mb-0 text-secondary" style="font-size:.9rem; line-height: 1.6;">
                        <?php foreach ($missions as $m) : ?>
                            <li><?php echo h($m); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="d-flex align-items-center justify-content-between p-3 rounded-4 mb-4" style="background: #eef2ff; border: 1px solid #c7d2fe;">
                    <span class="fw-bold text-dark" style="font-size:.9rem;"><i class="bi bi-calendar-event me-2 text-primary"></i> Prochain entretien pédagogique :</span>
                    <span class="badge bg-primary text-white fs-6 rounded-pill px-3"><?php echo $prochain; ?></span>
                </div>

                <!-- Formulaires-->
                <div class="row g-4 mt-2 border-top pt-2">
                    <div class="col-12">
                        <form method="POST" action="avancement_etudiant.php">
                            <input type="hidden" name="action" value="avancement">
                            <label class="section-title"><i class="bi bi-journal-text"></i> Avancement de la semaine</label>
                            <textarea class="form-control rounded-4 mb-3" name="avancement_semaine" rows="4" placeholder="Décris ce que tu as accompli cette semaine…"></textarea>
                            <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4" style="background:var(--bleu); border:none;">
                                <i class="bi bi-send me-1"></i> Envoyer l'avancement
                            </button>
                        </form>
                    </div>

                    <div class="col-12 mt-4 pt-4 border-top">
                        <form method="POST" action="avancement_etudiant.php">
                            <input type="hidden" name="action" value="remarque">
                            <label class="section-title"><i class="bi bi-chat-left-dots"></i> Remarques / Questions</label>
                            <textarea class="form-control rounded-4 mb-3" name="remarques" rows="3" placeholder="Une question ou une remarque pour ton tuteur ?"></textarea>
                            <button type="submit" class="btn btn-outline-primary rounded-pill fw-bold px-4">
                                <i class="bi bi-chat me-1"></i> Envoyer la remarque
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($vue === 'succes-semaine' || $vue === 'succes-remarque') : ?>
        <div class="card-cy text-center py-5">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h3 class="fw-bold mb-3" style="font-family:'Syne',sans-serif; color:#111827;">Message envoyé !</h3>
            <p class="text-muted mb-4 fs-5">
                <?php echo $vue === 'succes-semaine' ? "L'avancement de votre semaine" : "Votre remarque"; ?> a bien été envoyé à votre professeur.
            </p>
            <a href="avancement_etudiant.php" class="btn btn-primary rounded-pill fw-bold px-4 py-2" style="background:var(--bleu); border:none;">
                Retour à l'avancement
            </a>
        </div>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>