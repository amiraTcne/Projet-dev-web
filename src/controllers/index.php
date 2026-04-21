<?php
// =============================================================
//  CONFIG BASE DE DONNÉES
// =============================================================
$db_host = 'localhost';
$db_user = 'userpro';
$db_pass = 'projetStage26.';
$db_name = 'cyStages';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    header('Location: src/controllers/index.php?erreur=2');
    exit();
}
mysqli_set_charset($conn, 'utf8mb4');

// =============================================================
//  RÉCUPÉRATION DES OFFRES DISPONIBLES (vue publique)
// =============================================================
$query = "
    SELECT
        o.num_offre,
        o.titre,
        o.mission,
        o.competences,
        o.filiere_ciblee,
        o.duree_semaines,
        o.date_debut,
        o.date_publication,
        ent.nom_entreprise,
        ent.secteur,
        ent.ville
    FROM Offre_Stage o
    JOIN Utilisateur ent ON ent.id = o.id_entreprise
    WHERE o.statut = 'ouverte'
    ORDER BY o.date_publication DESC
";
$result = mysqli_query($conn, $query);
$offres = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $offres[] = $row;
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyTech — Stages</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Montserrat+Alternates:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/style_index.css">
</head>
<body>

<!-- ══════════════════ NAVBAR ══════════════════ -->
<nav>
    <a href="index.php" class="nav-logo">
       <img class="logo" src="../../public/assets/img/logo.png">
    </a>

    <ul class="nav-links">
        <li><a href="#accueil">Accueil</a></li>
        <li><a href="#offres">Offres de stage</a></li>
        <li><a href="#apropos">À propos</a></li>
    </ul>

    <div class="nav-actions">
        <a href="../../public/login.php" class="btn-outline" >Se connecter</a>
        <a href="premiere_inscription.php" class="btn-primary">S'inscrire</a>
    </div>
</nav>

<!-- ══════════════════ HERO ══════════════════ -->
<section class="hero" id="accueil" >
    <div class="hero-inner">
        <div class="hero-tag">Plateforme de stages — CY Tech ING1 2025-2026</div>
        <h1>Trouvez votre stage,<br>construisez votre avenir.</h1>
        <p>Consultez les offres de stage disponibles déposées par nos entreprises partenaires. Connectez-vous pour postuler, suivre son dossier et interagir avec son tuteur ou encore déposer une offre de stage.</p>
        <div class="hero-stats">
            <div class="hero-stat">
                <strong><?= count($offres) ?></strong>
                <span>Offres ouvertes</span>
            </div>
            <div class="hero-stat">
                <strong>100%</strong>
                <span>En ligne</span>
            </div>
            <div class="hero-stat">
                <strong>ING1</strong>
                <span>Promo 2026</span>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════ OFFRES ══════════════════ -->
<div class="section" id="offres">

    <div class="banner-login">
        <p>👁️ Vous consultez les offres en <strong>mode visiteur</strong>. Connectez-vous pour postuler et accéder à votre espace personnel.</p>
        <a href="../../public/login.php" class="btn-primary" >Se connecter →</a>
    </div>

    <div class="section-header">
        <div class="section-title">
            Offres disponibles
            <span class="count-badge"><?= count($offres) ?></span>
        </div>
    </div>

    <?php if (empty($offres)): ?>
    <div class="empty-state">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#5686D9" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4"/>
        </svg>
        <p>Aucune offre de stage disponible pour le moment.</p>
    </div>
    <?php else: ?>
    <div class="offres-grid">
        <?php foreach ($offres as $offre):
            $initiale = strtoupper(substr($offre['nom_entreprise'] ?? 'E', 0, 2));
            $date_debut = $offre['date_debut'] ? date('d/m/Y', strtotime($offre['date_debut'])) : 'À définir';
        ?>
        <div class="card-offre">
            <div class="card-top">
                <div class="card-entreprise-logo"><?= htmlspecialchars($initiale) ?></div>
                <div class="card-meta">
                    <div class="card-entreprise"><?= htmlspecialchars($offre['nom_entreprise'] ?? '—') ?></div>
                    <div class="card-titre"><?= htmlspecialchars($offre['titre']) ?></div>
                </div>
            </div>

            <p class="card-mission"><?= htmlspecialchars($offre['mission']) ?></p>

            <div class="card-tags">
                <?php if ($offre['filiere_ciblee']): ?>
                    <span class="tag"><?= htmlspecialchars($offre['filiere_ciblee']) ?></span>
                <?php endif; ?>
                <?php if ($offre['secteur']): ?>
                    <span class="tag tag-grey"><?= htmlspecialchars($offre['secteur']) ?></span>
                <?php endif; ?>
                <?php if ($offre['ville']): ?>
                    <span class="tag tag-grey">📍 <?= htmlspecialchars($offre['ville']) ?></span>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <div class="card-infos">
                    <span>⏱ <?= (int)$offre['duree_semaines'] ?> sem.</span>
                    <span>📅 <?= $date_debut ?></span>
                </div>
                <a href="../../public/login.php" class="btn-voir" > Voir → </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<!-- ══════════════════ Ecole ══════════════════  -->
<section id="ecole">
    <div class="section">
        <div class="section-header">
            <div class="section-title">Notre <span>École</span></div>
        </div>
        <div class="ecole-grid">
            <div class="ecole-texte">
                <p>CY Tech s'affirme comme une grande école d'ingénieurs publique de référence, intégrée à CY Cergy Paris Université et solidement implantée sur ses campus de Cergy et de Pau. Véritable pôle d'excellence en sciences exactes, l'école propose une offre de formation diversifiée couvrant des secteurs stratégiques tels que les mathématiques appliquées, l'informatique, le génie civil, la chimie et les biotechnologies. Sa pédagogie se distingue par une forte culture de l'innovation et de l'interdisciplinarité, illustrée notamment par des doubles cursus prestigieux en management ou en design.</p>
                <br>
                <p>Tournée vers l'avenir, l'institution place l'international et la recherche au cœur de son parcours, imposant une mobilité à l'étranger pour forger des profils ouverts et adaptables. Grâce à des liens étroits avec le monde industriel et une immersion professionnelle constante (stages, alternance, projets), CY Tech garantit à ses diplômés une insertion rapide sur un marché du travail en quête d'experts capables de relever les défis de la transition numérique et écologique.</p>
            </div>
            <div class="ecole-image">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQSBHcEmQ0Uow3JX1Sm0BI0liGamyX9c5_UkA&s" alt="Campus CY Tech">
            </div>
        </div>
    </div>
</section>
<!-- ══════════════════ A propos ══════════════════  -->
<section id="apropos">
    <div class="section">
        <div class="section-header">
            <div class="section-title">À <span>propos</span></div>
        </div>
        <div class="apropos-grid">
            <div class="apropos-card">
                <div class="apropos-icon">🎓</div>
                <h3>Le projet</h3>
                <p>CyStages est une plateforme développée dans le cadre du projet Dev Web ING1 2025-2026 à CY Tech. Elle centralise la gestion des stages pour tous les acteurs : étudiants, tuteurs, jurys et entreprises. Les developpeuses de ce projet sont : Ambre, Amina, Amira et Sirine</p>
            </div>
            <div class="apropos-card">
                <div class="apropos-icon">👥</div>
                <h3>Les acteurs</h3>
                <p>Cinq profils coexistent sur la plateforme : les <strong>étudiants</strong> qui postulent, les <strong>entreprises</strong> qui déposent des offres, les <strong>tuteurs</strong> qui suivent les stages, les <strong>jurys</strong> qui évaluent les dossiers et les <strong> administrateurs </strong> qui gèrent l'ensemble des profils.</p>
            </div>
            <div class="apropos-card">
                <div class="apropos-icon">📋</div>
                <h3>Fonctionnalités</h3>
                <p>Consultation des offres en accès libre, gestion de dossiers de stage, suivi de l'avancement, validation des conventions, évaluation jury et archivage — tout en un seul endroit.</p>
            </div>

        </div>
    </div>
</section>


<!-- ══════════════════ FOOTER ══════════════════ -->
<footer>
    <strong>CY Tech</strong> — Plateforme de suivi des stages · Projet Dev Web ING1 · 2025-2026
</footer>

</body>
</html>