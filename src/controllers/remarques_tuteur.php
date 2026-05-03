<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn      = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$etudiants = [];
$remarques = [];
$msg_ok    = '';
$msg_err   = '';

/* Étudiant sélectionné */
$id_etu_sel = (int)($_GET['etudiant'] ?? 0);

/* Envoi d'une remarque */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $contenu    = trim($_POST['contenu']    ?? '');
    $id_etudiant = (int)($_POST['id_etudiant'] ?? 0);

    if (!empty($contenu) && $id_etudiant > 0) {
        $sd = mysqli_prepare($conn,
            "SELECT d.num_dossier FROM Dossier_Stage d
             JOIN Stage s ON s.num_stage = d.num_stage
             WHERE s.id_etudiant = ? AND s.id_tuteur = ?
             ORDER BY d.date_creation DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($sd, 'ii', $id_etudiant, $_SESSION['id']);
        mysqli_stmt_execute($sd);
        $rdr = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
        mysqli_stmt_close($sd);

        if ($rdr) {
            $ins = mysqli_prepare($conn,
                "INSERT INTO Remarque (contenu, num_dossier, id_auteur) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'sii', $contenu, $rdr['num_dossier'], $_SESSION['id']);
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Remarque envoyée à l\'étudiant avec succès !';
                $id_etu_sel = $id_etudiant;
            }
            mysqli_stmt_close($ins);
        } else {
            $msg_err = 'Aucun dossier actif trouvé pour cet étudiant.';
        }
    } else {
        $msg_err = 'Veuillez remplir tous les champs.';
    }
}

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Liste des étudiants suivis par ce tuteur */
    $se = mysqli_prepare($conn,
        "SELECT e.id, e.nom, e.prenom, CONCAT(e.prenom, ' ', e.nom) AS nom_complet
         FROM Stage s
         JOIN Utilisateur e ON e.id = s.id_etudiant
         WHERE s.id_tuteur = ?
         GROUP BY e.id, e.nom, e.prenom
         ORDER BY e.nom, e.prenom"
    );
    mysqli_stmt_bind_param($se, 'i', $_SESSION['id']);
    mysqli_stmt_execute($se);
    $re = mysqli_stmt_get_result($se);
    while ($row = mysqli_fetch_assoc($re)) $etudiants[] = $row;
    mysqli_stmt_close($se);

    /* Historique des remarques pour l'étudiant sélectionné */
    if ($id_etu_sel > 0) {
        $sr = mysqli_prepare($conn,
            "SELECT r.contenu, r.date_creation, u.nom, u.prenom, u.role_premier
             FROM Remarque r
             JOIN Dossier_Stage d ON d.num_dossier = r.num_dossier
             JOIN Stage s         ON s.num_stage   = d.num_stage
             JOIN Utilisateur u   ON u.id = r.id_auteur
             WHERE s.id_etudiant = ? AND s.id_tuteur = ?
             ORDER BY r.date_creation DESC LIMIT 50"
        );
        mysqli_stmt_bind_param($sr, 'ii', $id_etu_sel, $_SESSION['id']);
        mysqli_stmt_execute($sr);
        $rr = mysqli_stmt_get_result($sr);
        while ($row = mysqli_fetch_assoc($rr)) $remarques[] = $row;
        mysqli_stmt_close($sr);
        
        // Inverser pour l'affichage du chat (les plus anciens en haut)
        $remarques = array_reverse($remarques);
    }
    mysqli_close($conn);
}

$nom_etu_sel = '';
foreach ($etudiants as $e) {
    if ($e['id'] === $id_etu_sel) { $nom_etu_sel = $e['nom_complet']; break; }
}
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remarques — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy {
            border: 1px solid rgba(171,186,205,.4); border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem;
        }
        
        /* Chat bubbles */
        .chat-bubble { max-width: 75%; padding: 12px 18px; border-radius: 18px; font-size: .9rem; line-height: 1.5; margin-bottom: 5px; }
        .bubble-tuteur { background: var(--bleu); color: #fff; border-bottom-right-radius: 4px; }
        .bubble-autre { background: #f1f5f9; color: #1e293b; border-bottom-left-radius: 4px; }
        
        .chat-container { max-height: 500px; overflow-y: auto; padding-right: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_tuteur.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-workspace me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:900px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_tuteur.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Remarques & Échanges</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Communiquez avec vos étudiants à propos de leurs dossiers</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <?php if (empty($etudiants)) : ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-chat-slash text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucun étudiant suivi</h5>
            <p class="text-muted mb-0">Vous devez d'abord affecter un étudiant à une offre de stage.</p>
        </div>
    <?php else : ?>
        <div class="row g-4">
            
            <!-- Colonne Sélection Étudiant -->
            <div class="col-md-4">
                <div class="card-cy h-100">
                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:.8rem; letter-spacing:1px;">Sélectionner un étudiant</h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($etudiants as $e) : ?>
                            <a href="remarques_tuteur.php?etudiant=<?php echo (int)$e['id']; ?>" class="btn text-start rounded-pill <?php echo $id_etu_sel === $e['id'] ? 'btn-primary shadow-sm' : 'btn-outline-secondary border-0 bg-light'; ?> fw-semibold" style="<?php echo $id_etu_sel === $e['id'] ? 'background-color: var(--bleu); border-color: var(--bleu);' : ''; ?>">
                                <i class="bi bi-person-circle me-2"></i> <?php echo h($e['nom_complet']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne Chat -->
            <div class="col-md-8">
                <div class="card-cy h-100 d-flex flex-column">
                    <?php if (!$id_etu_sel) : ?>
                        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-muted p-4">
                            <i class="bi bi-chat-dots fs-1 mb-3 opacity-50"></i>
                            <p class="mb-0 text-center">Sélectionnez un étudiant dans la liste de gauche pour visualiser l'historique et lui envoyer un message.</p>
                        </div>
                    <?php else : ?>
                        <h5 class="fw-bold mb-4 pb-2 border-bottom" style="font-family:'Syne',sans-serif; color:#111827;">
                            Discussion avec <?php echo h($nom_etu_sel); ?>
                        </h5>
                        
                        <!-- Historique des messages -->
                        <div class="chat-container flex-grow-1 mb-4 d-flex flex-column">
                            <?php if (empty($remarques)) : ?>
                                <div class="text-center text-muted my-auto opacity-75">Aucun échange pour le moment. Lancez la discussion !</div>
                            <?php else : ?>
                                <?php foreach ($remarques as $rem) :
                                    $est_tuteur = $rem['role_premier'] === 'Tuteur';
                                ?>
                                    <div class="d-flex flex-column mb-3 <?php echo $est_tuteur ? 'align-items-end' : 'align-items-start'; ?>">
                                        <span class="text-muted mb-1" style="font-size: .7rem; font-weight: 600;">
                                            <?php echo h($rem['prenom'] . ' ' . $rem['nom']); ?> • <?php echo date('d/m/Y H:i', strtotime($rem['date_creation'])); ?>
                                        </span>
                                        <div class="chat-bubble <?php echo $est_tuteur ? 'bubble-tuteur' : 'bubble-autre'; ?>">
                                            <?php echo nl2br(h($rem['contenu'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Formulaire d'envoi -->
                        <form method="POST" action="remarques_tuteur.php?etudiant=<?php echo $id_etu_sel; ?>" class="mt-auto">
                            <input type="hidden" name="id_etudiant" value="<?php echo $id_etu_sel; ?>">
                            <div class="input-group">
                                <textarea class="form-control bg-light rounded-start-4" name="contenu" rows="2" placeholder="Écrivez votre message..." style="resize:none;" required></textarea>
                                <button type="submit" class="btn btn-primary rounded-end-4 px-4" style="background:var(--bleu); border-color:var(--bleu);">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Auto-scroll chat to bottom -->
<script>
    const chatContainer = document.querySelector('.chat-container');
    if(chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
</script>
</body>
</html>