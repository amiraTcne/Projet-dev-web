<?php
/* Démarrage de la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on recupère les filtres depuis l'url, c'est grâce à ça que l'utilisateur pourra filtrer par mot-clé et par secteur d'activité s*/

/* le mot-clé de recherche c'est à dire le titre, la mission ou nom d'entreprise*/
$q = trim($_GET['q'] ?? '');

/* le secteur d'activité sélectionné via les les chips (les sorte de bouton qu'il y a en dessous de la barre de recherche) de filtre */
$secteur = trim($_GET['secteur'] ?? '');

/* la connexion à la base de données */
$conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');

/* les listes qui qu'on va remplir grâce à la base de données*/
$offres      = []; /*les offres récupérées après le filtrage*/
$favoris_ids = []; /*les id des offres déjà en favoris (pour colorier les cœurs)*/
$secteurs    = []; /*les secteurs distincts pour les chips de filtre */

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /*on récupère les favoris de l'étudiant connecté pour colorier les cœurs sur les cartes */
    $sf = mysqli_prepare($conn, "SELECT num_offre FROM Favori WHERE id_user = ?");
    mysqli_stmt_bind_param($sf, 'i', $_SESSION['id']);
    mysqli_stmt_execute($sf);
    $rf = mysqli_stmt_get_result($sf);
    while ($f = mysqli_fetch_row($rf)) {
        $favoris_ids[] = (int)$f[0];
    }
    mysqli_stmt_close($sf);

    /* on construit la requête SQL avec les filtres actifs*/
    $sql    = "SELECT o.num_offre, o.titre, o.mission, o.competences,
                      o.filiere_ciblee, o.duree_semaines, o.date_debut,
                      u.nom_entreprise, u.secteur, u.ville
               FROM Offre_Stage o
               JOIN Utilisateur u ON u.id = o.id_entreprise
               WHERE o.statut = 'ouverte'";

    /* les paramètres de la requête préparée */
    $params = [];
    $types  = '';

    /* on filtre par mot-clé c'est à dire qu'on cherche le titre, la mission et l'entreprise */
    if ($q !== '') {
        $sql .= " AND (o.titre LIKE ? OR o.mission LIKE ? OR u.nom_entreprise LIKE ?)";
        $like    = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types  .= 'sss';
    }

    /* puis on filtre par secteur */
    if ($secteur !== '') {
        $sql .= " AND u.secteur = ?";
        $params[] = $secteur;
        $types  .= 's';
    }

    /* et on limite à 50 résultats, qu'on trie par date de publication */
    $sql .= " ORDER BY o.date_publication DESC LIMIT 50";

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);

    /* on ajoute le champ 'fav' pour savoir si l'offre est en favori */
    while ($row = mysqli_fetch_assoc($r)) {
        $row['fav'] = in_array((int)$row['num_offre'], $favoris_ids);
        $offres[]   = $row;
    }
    mysqli_stmt_close($stmt);

    /* on récupère les secteurs distincts (pour pas avoir de doublons) pour les chips de filtre*/
    $rs = mysqli_query($conn,
        "SELECT DISTINCT u.secteur FROM Offre_Stage o
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE o.statut = 'ouverte' AND u.secteur IS NOT NULL
         ORDER BY u.secteur"
    );
    while ($s = mysqli_fetch_row($rs)) {
        $secteurs[] = $s[0];
    }

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de stage — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
</head>
<body>
<div class="page anim">

    <!-- l'en-tête avec bouton retour vers l'accueil -->
    <header class="entete">
        <a href="accueil_etudiant.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Offres de Stages</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <!-- le formulaire de filtres-->
        <form method="GET" action="offres_etudiant.php" id="form-recherche">

            <!-- la barre de recherche -->
            <div class="barre-recherche" style="margin-bottom:10px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text"
                       name="q"
                       id="champ-q"
                       placeholder="Rechercher un poste ou une entreprise…"
                       value="<?php echo htmlspecialchars($q); ?>"
                       autocomplete="off">
            </div>

            <!-- les chips de filtres par secteur d'activité -->
            <?php if (!empty($secteurs)) : ?>
            <div class="filtres" style="margin-bottom:8px;">

                <!-- les chip "Tous" pour réinitialiser le filtre secteur -->
                <a href="offres_etudiant.php" class="chip <?php echo $secteur === '' ? 'actif' : ''; ?>">Tous</a>

                <?php foreach ($secteurs as $s) : ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['secteur' => $s])); ?>"
                   class="chip <?php echo $secteur === $s ? 'actif' : ''; ?>">
                    <?php echo htmlspecialchars($s); ?>
                </a>
                <?php endforeach; ?>

            </div>
            <?php endif; ?>

        </form>

        <!-- l'affichage du nombre de résultats trouvés -->
        <div style="display:flex; align-items:center; justify-content:space-between;">
            <p style="font-size:.79rem; color:var(--gris-texte); font-weight:600;">
                <?php echo count($offres); ?> offre<?php echo count($offres) > 1 ? 's' : ''; ?> trouvée<?php echo count($offres) > 1 ? 's' : ''; ?>
            </p>
            <!-- le lien pour réinitialiser tous les filtres -->
            <?php if ($q || $secteur) : ?>
            <a href="offres_etudiant.php" style="font-size:.77rem; color:var(--bleu-clair); font-weight:600; text-decoration:none;">
                ✕ Effacer
            </a>
            <?php endif; ?>
        </div>

        <!--on affihche les offres ou état vide -->
        <?php if (empty($offres)) : ?>

        <!-- quand il n'y a pas d'offre -->
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 0 0-4 0v2"/>
            </svg>
            <h3>Aucune offre disponible</h3>
            <p>Modifie tes critères de recherche ou reviens plus tard !</p>
        </div>

        <?php else : ?>

        <!-- on affiche toutes les cartes d'offres -->
        <?php foreach ($offres as $o) :

            /* on coupe la description à 115 caractères pour pas avoir tout le texte */
            $desc_courte = mb_strlen($o['mission'] ?? '') > 115
                           ? mb_substr($o['mission'], 0, 115) . '…'
                           : ($o['mission'] ?? '');

            /* on conserve la durée en semaines et on arrond le résultat pour ne pas l'avoir en avec décimal */
            $duree = $o['duree_semaines'] ? round($o['duree_semaines'] / 4) . ' mois' : '';

            /*on affiche au maximum 3 technologies pour pas surcharger la carte */
            $techs = array_slice(
                array_filter(array_map('trim', explode(',', $o['competences'] ?? ''))),
                0, 3
            );
        ?>

        <div class="carte-offre">

            <!-- l'en-tête de la carte : le titre, l'entreprise et bouton favori -->
            <div class="offre-entete">
                <div>
                    <p class="offre-titre"><?php echo htmlspecialchars($o['titre']); ?></p>
                    <p class="offre-sous">
                        <?php echo htmlspecialchars($o['nom_entreprise']); ?>
                        <?php if ($o['ville']) : ?> — <?php echo htmlspecialchars($o['ville']); ?><?php endif; ?>
                    </p>
                </div>

                <!-- le bouton cœur (qui est ou rouge (si favori) ou gris)-->
                <button class="btn-coeur <?php echo $o['fav'] ? 'actif' : ''; ?>"
                        data-id="<?php echo (int)$o['num_offre']; ?>"
                        aria-label="Favori">
                    <svg viewBox="0 0 24 24" fill="<?php echo $o['fav'] ? 'currentColor' : 'none'; ?>">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </button>
            </div>

            <!-- la filière cible, la durée et les technologies -->
            <div class="offre-tags">
                <?php if ($o['filiere_ciblee']) : ?>
                <span class="badge badge-bleu"><?php echo htmlspecialchars($o['filiere_ciblee']); ?></span>
                <?php endif; ?>
                <?php if ($duree) : ?>
                <span class="badge badge-vert"><?php echo $duree; ?></span>
                <?php endif; ?>
                <?php foreach ($techs as $t) : ?>
                <span class="tag"><?php echo htmlspecialchars($t); ?></span>
                <?php endforeach; ?>
            </div>

            <!-- la escription de la mission -->
            <?php if ($desc_courte) : ?>
            <p class="offre-desc"><?php echo htmlspecialchars($desc_courte); ?></p>
            <?php endif; ?>

            <!-- la localisation, la date et le bouton Voir -->
            <div class="offre-pied">
                <div class="offre-meta">
                    <?php if ($o['ville']) : ?>
                    <span style="display:flex; align-items:center; gap:3px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php echo htmlspecialchars($o['ville']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($o['date_debut']) : ?>
                    <span style="display:flex; align-items:center; gap:3px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                        <?php echo date('d/m/Y', strtotime($o['date_debut'])); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- le lien vers la fiche détaillée de l'offre -->
                <a href="detail_offre.php?id=<?php echo (int)$o['num_offre']; ?>" class="btn btn-petit">
                    Voir
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </div>

        </div>

        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<!-- le "toast" est la invisible (car -cache)
 mais par la site il va nous permettre d'afficher les messages de confirmation ou d'erreur,
 pour nous permettre de donner un retour direct à l'utilisateur -->
<div class="toast cache" id="toast"></div>

<script>
    /*on cherche en temps réel avec délai et on attend 1000ms après la dernière frappe avant de soumettre le formulaire,
     pour que ça évite d'envoyer une requête à chaque lettre tapée.
     */
    var delai;
    document.getElementById('champ-q').addEventListener('input', function () {
        clearTimeout(delai);
        delai = setTimeout(function () {
            document.getElementById('form-recherche').submit();
        }, 1000);
    });

    /*lorsqu'on clique sur le cœur, on envoie une requête POST vers api_favori.php, sans avoir à recharger la page, 
     puis on met à jour l'apparence du cœur */
    document.querySelectorAll('.btn-coeur').forEach(function (btn) {
        btn.addEventListener('click', async function (e) {
            e.stopPropagation(); /* on empêche la propagation du clic */

            var id     = this.dataset.id;
            var actif  = this.classList.contains('actif');
            var action = actif ? 'remove' : 'add';

            try {
                var reponse = await fetch('api_favori.php', {
                    method  : 'POST',
                    headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body    : 'num_offre=' + id + '&action=' + action
                });
                var data = await reponse.json();

                if (data.success) {
                    /* on met à jour l'apparence du cœur */
                    this.classList.toggle('actif');
                    this.querySelector('svg').setAttribute('fill', action === 'add' ? 'currentColor' : 'none');
                    afficherToast(action === 'add' ? '💙 Ajouté aux favoris' : 'Retiré des favoris', action === 'add');
                }
            } catch (err) {
                afficherToast('Erreur réseau…');
            }
        });
    });

    /*on affiche un message temporaire en bas de l'écran et toast disparaît directement après 2.4 secondes*/
    function afficherToast(msg, ok) {
        var t    = document.getElementById('toast');
        t.textContent = msg;
        t.className   = 'toast' + (ok ? ' ok' : '');
        setTimeout(function () { t.className = 'toast cache'; }, 2400);
    }
</script>

</body>
</html>
