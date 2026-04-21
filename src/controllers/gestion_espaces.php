<?php
session_start();
$connect      = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$query        = "SELECT * FROM v_utilisateurs ORDER BY date_inscription DESC";
$result       = mysqli_query($connect, $query);
$utilisateurs = mysqli_fetch_all($result, MYSQLI_ASSOC);

/* Badges couleur par rôle */
$role_badges = [
    'Etudiant'   => 'bg-primary',
    'Admin'      => 'bg-danger',
    'Entreprise' => 'bg-success',
    'Tuteur'     => 'bg-warning text-dark',
    'Jury'       => 'bg-info text-dark',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
    <?php include '../../public/frameworks.php'; ?>
</head>
<body>

<!-- Navbar -->
<nav class="navbar shadow-sm mb-4" style="background: linear-gradient(135deg,#1B4F9B,#2563c7);">
    <div class="container-fluid px-4 d-flex align-items-center gap-3">
        <img src="../../public/assets/img/logo.png" height="36" alt="CY Stage">
        <span class="fw-bold text-white ms-2">Gestion des Utilisateurs</span>
        <a href="accueil_admin.php" class="btn btn-outline-light btn-sm ms-auto">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>
</nav>

<div class="container" style="max-width:960px;">

    <!-- Barre de recherche Alpine.js (filtre en temps réel côté client) -->
    <div class="d-flex align-items-center gap-3 mb-4" x-data="{ search: '' }">

    

        <span class="badge rounded-pill" style="background:#1B4F9B; font-size:.82rem; padding:6px 14px;">
            <?php echo count($utilisateurs); ?> inscrits
        </span>

        <!-- Tableau Bootstrap -->
        <div class="table-responsive mt-3 w-100" x-data="{ search: '' }">

            <!-- Barre de recherche au-dessus du tableau -->
            <div class="mb-3" style="max-width:360px;">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input type="text"
                           class="form-control border-start-0 ps-0"
                           placeholder="Filtrer par nom…"
                           x-model="search">
                </div>
            </div>

            <table class="table table-hover align-middle rounded-3 overflow-hidden shadow-sm">
                <thead style="background:#1B4F9B; color:#fff;">
                    <tr>
                        <th class="py-3 ps-3">#</th>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th class="text-center">Statut</th>
                        <th>Inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $i => $user) : ?>
                    <tr
                        x-show="search === '' || '<?php echo strtolower(addslashes($user['nom_complet'])); ?>'.includes(search.toLowerCase())"
                        x-transition
                    >
                        <td class="ps-3 text-secondary" style="font-size:.82rem;"><?php echo $i + 1; ?></td>
                        <td class="fw-semibold"><?php echo htmlspecialchars($user['nom_complet']); ?></td>
                        <td class="text-muted" style="font-size:.85rem;"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php
                            $role  = $user['role_premier'];
                            $badge = $role_badges[$role] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?php echo $badge; ?> rounded-pill">
                                <?php echo htmlspecialchars($role); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($user['actif']) : ?>
                                <span class="badge bg-success rounded-pill">
                                    <i class="bi bi-check-circle-fill me-1"></i>Actif
                                </span>
                            <?php else : ?>
                                <span class="badge bg-secondary rounded-pill">
                                    <i class="bi bi-x-circle-fill me-1"></i>Inactif
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted" style="font-size:.82rem;">
                            <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>

</div><!-- /container -->

</body>
</html>