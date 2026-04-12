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
        <li><a href="#">Accueil</a></li>
        <li><a href="#">Offres de stage</a></li>
        <li><a href="#">À propos</a></li>
    </ul>

    <div class="nav-actions">
        <a href="../../public/login.php" class="btn-outline" >Se connecter</a>
        <a href="#" class="btn-primary">S'inscrire</a>
    </div>
</nav>

<!-- ══════════════════ HERO ══════════════════ -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-tag">Plateforme de stages — CY Tech ING1 2025-2026</div>
        <h1>Trouvez votre stage,<br>construisez votre avenir.</h1>
        <p>Consultez les offres de stage disponibles déposées par nos entreprises partenaires. Connectez-vous pour postuler, suivre votre dossier et interagir avec votre tuteur.</p>
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
<div class="section">

    <div class="banner-login">
        <p>👁️ Vous consultez les offres en <strong>mode visiteur</strong>. Connectez-vous pour postuler et accéder à votre espace personnel.</p>
        <a href="#" class="btn-primary" onclick="openModal('connexion'); return false;">Se connecter →</a>
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
                <button class="btn-voir" onclick="openModal('connexion')">
                    Voir →
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════════ MODAL AUTH ══════════════════ -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
    <div class="modal">
        <button class="modal-close" onclick="closeModal()">✕</button>

        <div class="modal-tabs">
            <button class="modal-tab active" id="tab-connexion" onclick="switchTab('connexion')">Se connecter</button>
            <button class="modal-tab" id="tab-inscription" onclick="switchTab('inscription')">S'inscrire</button>
        </div>

        <div class="modal-body">

            <!-- CONNEXION -->
            <form class="modal-form active" id="form-connexion" method="POST" action="login.php">
                <div class="form-group">
                    <label>Adresse e-mail</label>
                    <input type="email" name="email" placeholder="prenom.nom@cy-tech.fr" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="mot_de_passe" placeholder="••••••••" required>
                </div>
                <button type="submit" class="form-submit">Se connecter</button>
            </form>

            <!-- INSCRIPTION -->
            <form class="modal-form" id="form-inscription" method="POST" action="register.php">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" placeholder="Dupont" required>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" placeholder="Jean" required>
                </div>
                <div class="form-group">
                    <label>Adresse e-mail</label>
                    <input type="email" name="email" placeholder="prenom.nom@cy-tech.fr" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="mot_de_passe" placeholder="••••••••" required>
                </div>
                <button type="submit" class="form-submit">Créer mon compte</button>
            </form>

        </div>
    </div>
</div>

<!-- ══════════════════ FOOTER ══════════════════ -->
<footer>
    <strong>CY Tech</strong> — Plateforme de suivi des stages · Projet Dev Web ING1 · 2025-2026
</footer>

<script>
function openModal(tab) {
    document.getElementById('modalOverlay').classList.add('active');
    switchTab(tab);
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
}

function closeModalOutside(e) {
    if (e.target === document.getElementById('modalOverlay')) closeModal();
}

function switchTab(tab) {
    document.querySelectorAll('.modal-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.modal-form').forEach(f => f.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('form-' + tab).classList.add('active');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});
</script>

</body>
</html>