<?php
/* Démarrage de la session */
session_start();

/* on regarde si c'est un étudiant qui est connecté uniquement */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Etudiant') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$favoris = []; /* la liste des offres sauvegardées */

/* on change les favoris depuis la base de données */
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* on effectue une jointure entre Favori, Offre_Stage et Utilisateur 
    pour qu'on puisse avoir toutes les informations nécessaires à l'affichage des cartes */
    $stmt = mysqli_prepare($conn,
        "SELECT o.num_offre, o.titre, o.mission, o.competences,
                o.filiere_ciblee, o.duree_semaines, o.date_debut,
                u.nom_entreprise, u.secteur, u.ville,
                f.date_ajout
         FROM Favori f
         JOIN Offre_Stage o ON o.num_offre = f.num_offre
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE f.id_user = ?
         ORDER BY f.date_ajout DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) {
        $favoris[] = $row;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
</head>
<body>
<div class="page anim">

    <!-- l'en-tête avec bouton retour -->
    <header class="entete">
        <a href="accueil_etudiant.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Offres en favoris</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <!-- Rle resumé du nombre d'offres sauvegardées -->
        <div style="display:flex; align-items:center; justify-content:space-between;">
            <p style="font-size:.79rem; color:var(--gris-texte); font-weight:600;">
                <?php echo count($favoris); ?> offre<?php echo count($favoris) > 1 ? 's' : ''; ?> sauvegardée<?php echo count($favoris) > 1 ? 's' : ''; ?>
            </p>
            <?php if (!empty($favoris)) : ?>
            <a href="offres_etudiant.php" style="font-size:.77rem; color:var(--bleu-clair); font-weight:600; text-decoration:none;">
                + Explorer
            </a>
            <?php endif; ?>
        </div>

        <!-- quand on a aucun favori enregistré -->
        <?php if (empty($favoris)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            <h3>Aucun favori pour l'instant</h3>
            <p>Parcours les offres et clique sur le 💙 pour les sauvegarder ici.</p>
            <a href="offres_etudiant.php" class="btn" style="width:auto; padding:9px 18px; display:inline-flex; margin-top:6px;">
                Parcourir les offres
            </a>
        </div>

        <?php else : ?>

        <!-- on affiche les cartes de favoris -->
        <?php foreach ($favoris as $o) :
            /* on conserve la durée en semaines et on arrond le résultat pour ne pas l'avoir en avec décimal */
            $duree = $o['duree_semaines'] ? round($o['duree_semaines'] / 4) . ' mois' : '';

            /* On affiche au maximum 3 technologies */
            $techs = array_slice(
                array_filter(array_map('trim', explode(',', $o['competences'] ?? ''))),
                0, 3
            );
        ?>

        <!-- ici chaque carte a un id unique pour que JavaScript puisse l'animer à la suppression -->
        <div class="carte-offre" id="fav-<?php echo (int)$o['num_offre']; ?>">

            <div class="offre-entete">
                <div>
                    <p class="offre-titre"><?php echo htmlspecialchars($o['titre']); ?></p>
                    <p class="offre-sous">
                        <?php echo htmlspecialchars($o['nom_entreprise']); ?>
                        <?php if ($o['ville']) : ?> — <?php echo htmlspecialchars($o['ville']); ?><?php endif; ?>
                    </p>
                </div>

                <!-- le cœur est toujours rouge ici et si il y a un clic il est retiré des favoris avec une animation -->
                <button class="btn-coeur actif"
                        data-id="<?php echo (int)$o['num_offre']; ?>"
                        aria-label="Retirer des favoris">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </button>
            </div>

            <!-- la filière cible, la durée et technologies -->
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

            <!-- la date de sauvegarde et lien vers le détail -->
            <div class="offre-pied">
                <span style="font-size:.74rem; color:var(--gris-texte);">
                    Sauvegardé le <?php echo date('d/m/Y', strtotime($o['date_ajout'])); ?>
                </span>
                <a href="detail_offre.php?id=<?php echo (int)$o['num_offre']; ?>" class="btn btn-petit">
                    Voir
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </div>
        </div>

        <?php endforeach; ?>
        <?php endif;?>

    </div>
</div>

<!-- le "toast" est la invisible (car -cache)
 mais par la site il va nous permettre d'afficher les messages de confirmation ou d'erreur,
 pour nous permettre de donner un retour direct à l'utilisateur -->
<div class="toast cache" id="toast"></div>

<script>
    /* quand on enlève un favoris avec animation
     quand on clic sur le cœur, on appelle api_favori.php en AJAX,
     puis on fait disparaître la carte vers la droite avant de la supprimer du DOM.
     */
    document.querySelectorAll('.btn-coeur').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var id   = this.dataset.id;
            var card = document.getElementById('fav-' + id);

            try {
                var r = await fetch('api_favori.php', {
                    method  : 'POST',
                    headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body    : 'num_offre=' + id + '&action=remove'
                });
                var d = await r.json();

                if (d.success) {
                    /* l'animation de disparition*/
                    card.style.transition = 'opacity .3s, transform .3s';
                    card.style.opacity    = '0';
                    card.style.transform  = 'translateX(18px)';

                    /* on supprime la carte du DOM après la fin de l'animation */
                    setTimeout(function () { card.remove(); }, 320);

                    /* la notification temporaire en bas de l'écran */
                    var t = document.getElementById('toast');
                    t.textContent = 'Retiré des favoris';
                    t.className   = 'toast';
                    setTimeout(function () { t.className = 'toast cache'; }, 2200);
                }
            } catch (e) {
                console.error('Erreur lors du retrait du favori :', e);
            }
        });
    });
</script>

</body>
</html>
