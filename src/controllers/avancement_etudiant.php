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
/* on peut avoir 'formulaire', 'succes-semaine' ou 'succes-remarque' */
$vue = 'formulaire'; 

/* on traite l'envoi de l'avancement de la semaine */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'avancement') {
    $texte = trim($_POST['avancement_semaine'] ?? '');

    if (!empty($texte) && $conn) {
        /* On préfixe le contenu pour qu'on puisse différencier les avancements des remarques simples */
        $contenu = '[AVANCEMENT SEMAINE] ' . $texte;

        /* on récupère le dossier du stage en cours */
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
            /* Insertion de l'avancement comme une remarque */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Remarque (contenu, num_dossier, id_auteur) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'sii', $contenu, $rdr['num_dossier'], $_SESSION['id']);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }

        /* Passage à la vue de confirmation */
        $vue = 'succes-semaine';
    }
}

/* on traite l'envoi d'une remarque simple */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remarque') {
    $texte = trim($_POST['remarques'] ?? '');

    if (!empty($texte) && $conn) {
        /* on récupère le dernier dossier de l'étudiant */
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

        /* on passe à la vue de confirmation */
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
/* Si aucune mission en base, on affiche des exemples par défaut */
if (empty($missions) && $stage) {
    $missions = [
        'Prise en main du projet et des outils',
        'Développement des fonctionnalités',
        'Rédaction de la documentation',
    ];
}

/* la date du prochain entretien formatée en français */
$prochain = $stage && !empty($stage['date_fin'])
            ? date('d/m/Y', strtotime($stage['date_fin']))
            : 'Non planifié';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avancement Stage — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
</head>
<body>
<div class="page anim">

    <!-- le bouton retour qui permet de revenir au formulaire si on est sur un écran de succès -->
    <header class="entete">
        <a href="<?php echo $vue !== 'formulaire' ? 'avancement_etudiant.php' : 'accueil_etudiant.php'; ?>"
           class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Avancement Stages</span>
        <div style="width:36px;"></div>
    </header>

    <!-- ici on liste les missions, les prochain entretien, les zones de texte (textarea) avancement semaine, les zones de texte (textarea) remarques -->
    <?php if ($vue === 'formulaire') : ?>
    <div class="contenu">

        <?php if (!$stage) : ?>
        <!-- Aucun stage en cours : on invite l'étudiant à chercher une offre -->
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            <h3>Aucun stage en cours</h3>
            <p>L'avancement apparaîtra ici quand ton stage sera validé.</p>
        </div>

        <?php else : ?>

        <!-- Liste des missions du stage -->
        <p class="label-section">Missions du stage :</p>
        <div class="carte">
            <ul style="list-style:none; display:flex; flex-direction:column; gap:7px;">
                <?php foreach ($missions as $m) : ?>
                <li style="display:flex; align-items:flex-start; gap:8px; font-size:.83rem; color:var(--gris-texte); line-height:1.45;">
                    <span style="color:var(--bleu); font-weight:700; margin-top:1px;">–</span>
                    <?php echo htmlspecialchars($m); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Date du prochain entretien avec le tuteur pédagogique -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:var(--gris-fond); border-radius:var(--radius); border:1px solid var(--gris-border);">
            <span style="font-size:.82rem; font-weight:600; line-height:1.3;">
                Prochain entretien avec le professeur :
            </span>
            <span style="font-size:.87rem; font-weight:700; color:var(--bleu); white-space:nowrap; margin-left:8px;">
                <?php echo $prochain; ?>
            </span>
        </div>

        <!-- L'avancement de la semaine -->
        <p class="label-section">Avancement de la semaine :</p>
        <div class="carte">
            <form method="POST" action="avancement_etudiant.php">
                <input type="hidden" name="action" value="avancement">
                <textarea class="textarea" name="avancement_semaine" rows="4"
                          placeholder="Décris ce que tu as accompli cette semaine…"></textarea>
                <button type="submit" class="btn" style="margin-top:10px;">Valider</button>
            </form>
        </div>

        <!-- Les remarques pour le tuteur -->
        <p class="label-section">Remarques :</p>
        <div class="carte">
            <form method="POST" action="avancement_etudiant.php">
                <input type="hidden" name="action" value="remarque">
                <textarea class="textarea" name="remarques" rows="4"
                          placeholder="Une question pour ton tuteur ?"></textarea>
                <button type="submit" class="btn" style="margin-top:10px;">Valider</button>
            </form>
        </div>

        <?php endif; ?>

    </div>

    <!-- pour l'écran de confirmation après envoi de l'avancement semaine-->
    <?php elseif ($vue === 'succes-semaine') : ?>
    <div class="contenu">
        <div class="ecran-succes">
            <div class="cercle-check">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <p class="texte-succes">
                L'avancement de votre semaine a bien été envoyé à votre professeur !
            </p>
        </div>
    </div>

    <!-- pour l'écran de confirmation après envoi d'une remarque -->
    <?php elseif ($vue === 'succes-remarque') : ?>
    <div class="contenu">
        <div class="ecran-succes">
            <div class="cercle-check">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <p class="texte-succes">
                Votre remarque a bien été envoyée à votre professeur !
            </p>
        </div>
    </div>

    <?php endif; ?>

</div>
</body>
</html>
