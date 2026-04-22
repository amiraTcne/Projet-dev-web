<?php
/* on démarre la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on récupère l'identifiant de l'offre depuis l'URL et on force le type entier
   pour éviter les injections SQL */
$id_offre = (int)($_GET['id'] ?? 0);

/* si l'ID est invalide, on retourne à la liste des offres */
if ($id_offre <= 0) {
    header('Location: offres_etudiant.php');
    exit();
}

/* les variables qui nous permettent de gérer l'état de la page */
$conn         = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$offre        = null;  /* les données de l'offre */
$est_favori   = false; /* est-ce que l'offre est en favori ? */
$deja_postule = false; /* est-ce que l'étudiant a déjà postulé ? */
$msg_ok       = '';    /* message de succès affiché si la candidature est envoyée */
$msg_err      = '';    /* message d'erreur affiché si quelque chose se passe mal */

/* on traite la candidature quand l'étudiant soumet le formulaire */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    /* on vérifie d'abord que l'étudiant n'a pas déjà postulé */
    $chk = mysqli_prepare($conn, "SELECT 1 FROM Stage WHERE id_etudiant = ? AND num_offre = ?");
    mysqli_stmt_bind_param($chk, 'ii', $_SESSION['id'], $id_offre);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);

    if (mysqli_stmt_num_rows($chk) > 0) {
        $msg_err = 'Tu as déjà postulé à cette offre.';
    } else {
        /* on récupère l'id de l'entreprise pour pouvoir créer le Stage correctement */
        $ge = mysqli_prepare($conn, "SELECT id_entreprise, titre FROM Offre_Stage WHERE num_offre = ?");
        mysqli_stmt_bind_param($ge, 'i', $id_offre);
        mysqli_stmt_execute($ge);
        $ent = mysqli_fetch_assoc(mysqli_stmt_get_result($ge));
        mysqli_stmt_close($ge);

        if ($ent) {
            /* on prépare la requête SQL avec des '?' pour la sécurité */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Stage (titre, id_etudiant, id_entreprise, num_offre, statut)
                 VALUES (?, ?, ?, ?, 'en_attente')"
            );

            /* on lie les variables aux '?', le 'siii' veut dire (String, Integer, Integer, Integer) */
            mysqli_stmt_bind_param($ins, 'siii',
                $ent['titre'],        /* le titre du stage */
                $_SESSION['id'],      /* l'ID de l'étudiant récupéré depuis la session */
                $ent['id_entreprise'],/* l'ID de l'entreprise */
                $id_offre             /* le numéro de l'offre concernée */
            );

            /* on exécute la requête — le trigger SQL va créer le Dossier_Stage automatiquement */
            if (mysqli_stmt_execute($ins)) {
                $msg_ok       = 'Candidature envoyée !';
                $deja_postule = true;
            } else {
                $msg_err = "Erreur lors de l'envoi.";
            }
            mysqli_stmt_close($ins);
        }
    }
    mysqli_stmt_close($chk);
}

/* on charge les données de l'offre depuis la base */
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    $stmt = mysqli_prepare($conn,
        "SELECT o.num_offre, o.titre, o.mission, o.competences,
                o.filiere_ciblee, o.duree_semaines, o.date_debut,
                u.nom_entreprise, u.secteur, u.ville
         FROM Offre_Stage o
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE o.num_offre = ? AND o.statut = 'ouverte'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_offre);
    mysqli_stmt_execute($stmt);
    $offre = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($offre) {
        /* on vérifie si l'offre est déjà en favori pour colorier le cœur */
        $cf = mysqli_prepare($conn, "SELECT 1 FROM Favori WHERE id_user = ? AND num_offre = ?");
        mysqli_stmt_bind_param($cf, 'ii', $_SESSION['id'], $id_offre);
        mysqli_stmt_execute($cf);
        mysqli_stmt_store_result($cf);
        $est_favori = mysqli_stmt_num_rows($cf) > 0;
        mysqli_stmt_close($cf);

        /* on vérifie aussi si l'étudiant a déjà postulé (utile si la page est chargée sans POST) */
        if (!$deja_postule) {
            $cp = mysqli_prepare($conn, "SELECT 1 FROM Stage WHERE id_etudiant = ? AND num_offre = ?");
            mysqli_stmt_bind_param($cp, 'ii', $_SESSION['id'], $id_offre);
            mysqli_stmt_execute($cp);
            mysqli_stmt_store_result($cp);
            $deja_postule = mysqli_stmt_num_rows($cp) > 0;
            mysqli_stmt_close($cp);
        }
    }
    mysqli_close($conn);
}

/* si l'offre n'existe pas ou n'est plus disponible, on retourne à la liste */
if (!$offre) {
    header('Location: offres_etudiant.php');
    exit();
}

/* on prépare les données d'affichage */
$techs = array_filter(array_map('trim', explode(',', $offre['competences'] ?? '')));
$duree = $offre['duree_semaines'] ? round($offre['duree_semaines'] / 4) . ' mois' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($offre['titre']); ?> — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
</head>
<body>
<div class="page anim">

    <!-- l'en-tête avec le bouton retour et le bouton favori -->
    <header class="entete">
        <a href="offres_etudiant.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Détail de l'offre</span>

        <!-- le bouton cœur : rouge si l'offre est en favori, gris sinon -->
        <button class="btn-coeur <?php echo $est_favori ? 'actif' : ''; ?>"
                id="btn-fav"
                data-id="<?php echo $id_offre; ?>"
                style="border-radius:50%; border:1px solid var(--gris-border);"
                aria-label="Favori">
            <svg viewBox="0 0 24 24" fill="<?php echo $est_favori ? 'currentColor' : 'none'; ?>">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </button>
    </header>

    <div class="contenu">

        <!-- le bandeau bleu avec le titre et les informations principales -->
        <div class="bandeau">
            <h2><?php echo htmlspecialchars($offre['titre']); ?></h2>
            <p>
                <?php echo htmlspecialchars($offre['nom_entreprise']); ?>
                <?php if ($offre['ville']) : ?> · <?php echo htmlspecialchars($offre['ville']); ?><?php endif; ?>
            </p>
            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                <?php if ($offre['filiere_ciblee']) : ?>
                <span style="background:rgba(255,255,255,.20); color:#fff; padding:2px 9px; border-radius:20px; font-size:.70rem; font-weight:700;">
                    <?php echo htmlspecialchars($offre['filiere_ciblee']); ?>
                </span>
                <?php endif; ?>
                <?php if ($duree) : ?>
                <span style="background:rgba(255,255,255,.20); color:#fff; padding:2px 9px; border-radius:20px; font-size:.70rem; font-weight:700;">
                    <?php echo $duree; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- le message de succès après que la candidature a été envoyée -->
        <?php if ($msg_ok) : ?>
        <div style="background:rgba(22,163,74,.09); border:1px solid var(--vert); border-radius:10px; padding:11px 14px; display:flex; align-items:center; gap:10px;">
            <span style="width:32px; height:32px; border-radius:50%; background:var(--vert); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#fff;">✓</span>
            <p style="font-weight:700; font-size:.87rem; color:var(--vert);"><?php echo htmlspecialchars($msg_ok); ?></p>
        </div>
        <?php endif; ?>

        <!-- le message d'erreur si quelque chose s'est mal passé -->
        <?php if ($msg_err) : ?>
        <div style="background:#fff0f0; border:1px solid #fca5a5; border-radius:8px; padding:10px 13px; font-size:.83rem; color:var(--rouge);">
            <?php echo htmlspecialchars($msg_err); ?>
        </div>
        <?php endif; ?>

        <!-- la localisation, la durée et la date de début -->
        <p class="label-section">Informations</p>
        <div class="carte">
            <?php if ($offre['ville']) : ?>
            <div style="display:flex; gap:10px; align-items:center; padding:9px 0; border-bottom:1px solid var(--gris-border);">
                <div style="width:30px; height:30px; border-radius:7px; background:var(--gris-fond); display:flex; align-items:center; justify-content:center; color:var(--bleu); flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size:.69rem; color:var(--gris-texte); font-weight:500;">Localisation</p>
                    <p style="font-size:.87rem; font-weight:700;"><?php echo htmlspecialchars($offre['ville']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($offre['duree_semaines']) : ?>
            <div style="display:flex; gap:10px; align-items:center; padding:9px 0; border-bottom:1px solid var(--gris-border);">
                <div style="width:30px; height:30px; border-radius:7px; background:var(--gris-fond); display:flex; align-items:center; justify-content:center; color:var(--bleu); flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size:.69rem; color:var(--gris-texte); font-weight:500;">Durée</p>
                    <p style="font-size:.87rem; font-weight:700;">
                        <?php echo $offre['duree_semaines']; ?> semaines (~<?php echo $duree; ?>)
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($offre['date_debut']) : ?>
            <div style="display:flex; gap:10px; align-items:center; padding:9px 0;">
                <div style="width:30px; height:30px; border-radius:7px; background:var(--gris-fond); display:flex; align-items:center; justify-content:center; color:var(--bleu); flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size:.69rem; color:var(--gris-texte); font-weight:500;">Début du stage</p>
                    <p style="font-size:.87rem; font-weight:700;">
                        <?php echo date('d/m/Y', strtotime($offre['date_debut'])); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- la description complète de la mission -->
        <?php if ($offre['mission']) : ?>
        <p class="label-section">Mission</p>
        <div class="carte">
            <p style="font-size:.86rem; color:var(--gris-texte); line-height:1.65;">
                <?php echo nl2br(htmlspecialchars($offre['mission'])); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- les technologies et compétences requises pour le stage -->
        <?php if (!empty($techs)) : ?>
        <p class="label-section">Compétences recherchées</p>
        <div class="carte" style="display:flex; flex-wrap:wrap; gap:7px;">
            <?php foreach ($techs as $t) : ?>
            <span class="tag" style="padding:4px 11px; font-size:.79rem;">
                <?php echo htmlspecialchars($t); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- si l'étudiant a déjà postulé on lui affiche un message, sinon on affiche le bouton -->
        <?php if ($deja_postule && !$msg_err) : ?>
        <div class="btn" style="background:var(--gris-fond); color:var(--gris-texte); cursor:default;">
            ✓ Candidature déjà envoyée
        </div>
        <?php else : ?>
        <form method="POST" action="detail_offre.php?id=<?php echo $id_offre; ?>">
            <button type="submit" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Postuler à cette offre
            </button>
        </form>
        <?php endif; ?>

    </div>
</div>

<!-- le toast est invisible au départ mais il va s'afficher pour
     confirmer ou signaler une erreur quand on clique sur le cœur -->
<div class="toast cache" id="toast"></div>

<script>
    /* le toggle favori depuis la page de détail :
       on envoie une requête vers api_favori.php sans recharger la page,
       puis on met à jour l'apparence du cœur */
    document.getElementById('btn-fav').addEventListener('click', async function () {
        var action = this.classList.contains('actif') ? 'remove' : 'add';
        try {
            var r = await fetch('api_favori.php', {
                method  : 'POST',
                headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
                body    : 'num_offre=' + this.dataset.id + '&action=' + action
            });
            var d = await r.json();
            if (d.success) {
                this.classList.toggle('actif');
                this.querySelector('svg').setAttribute('fill', action === 'add' ? 'currentColor' : 'none');
                /* on affiche le toast de confirmation en bas de l'écran */
                var t = document.getElementById('toast');
                t.textContent = action === 'add' ? '💙 Ajouté aux favoris' : 'Retiré des favoris';
                t.className   = 'toast' + (action === 'add' ? ' ok' : '');
                setTimeout(function () { t.className = 'toast cache'; }, 2300);
            }
        } catch (e) {}
    });
</script>

</body>
</html>