<?php
/* on démarre la session */
session_start();

/* on vérifie que c'est bien un admin connecté */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';

/* on traite le changement de rôle quand l'admin soumet le formulaire */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $id_user      = (int)($_POST['id_user'] ?? 0);
    $nouveau_role = trim($_POST['nouveau_role'] ?? '');
    $roles_ok     = ['Etudiant', 'Tuteur', 'Jury', 'Entreprise', 'Admin'];

    if (isset($_POST['action']) && $_POST['action'] === 'changer_role' && $id_user > 0 && in_array($nouveau_role, $roles_ok)) {
        /* on met à jour le rôle principal de l'utilisateur */
        $upd = mysqli_prepare($conn, "UPDATE Utilisateur SET role_premier = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'si', $nouveau_role, $id_user);
        mysqli_stmt_execute($upd) ? $msg_ok = 'Rôle mis à jour.' : $msg_err = 'Erreur de mise à jour.';
        mysqli_stmt_close($upd);
    }

    /* on peut aussi activer ou désactiver un compte */
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_actif' && $id_user > 0) {
        $actif_actuel = (int)($_POST['actif_actuel'] ?? 1);
        $nouvel_actif = $actif_actuel === 1 ? 0 : 1;
        $upd2 = mysqli_prepare($conn, "UPDATE Utilisateur SET actif = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd2, 'ii', $nouvel_actif, $id_user);
        mysqli_stmt_execute($upd2);
        $msg_ok = $nouvel_actif ? 'Compte activé.' : 'Compte désactivé.';
        mysqli_stmt_close($upd2);
    }
}

/* on récupère l'onglet actif — par défaut on affiche les étudiants */
$onglet       = $_GET['role'] ?? 'Etudiant';
$roles_dispo  = ['Etudiant', 'Tuteur', 'Jury', 'Entreprise'];

$utilisateurs = [];
$stats        = [];

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* on compte les utilisateurs actifs par rôle pour les stats du haut */
    foreach ($roles_dispo as $r) {
        $sq = mysqli_prepare($conn, "SELECT COUNT(*) FROM Utilisateur WHERE role_premier = ? AND actif = 1");
        mysqli_stmt_bind_param($sq, 's', $r);
        mysqli_stmt_execute($sq);
        mysqli_stmt_bind_result($sq, $nb);
        mysqli_stmt_fetch($sq);
        $stats[$r] = (int)$nb;
        mysqli_stmt_close($sq);
    }

    /* on charge tous les utilisateurs de l'onglet sélectionné (actifs et inactifs) */
    $stmt = mysqli_prepare($conn,
        "SELECT id, nom, prenom, email, role_premier, role_second, role_troisieme,
                actif, filiere, niveau, annee_promo, specialite, nom_entreprise, secteur
         FROM Utilisateur
         WHERE role_premier = ?
         ORDER BY nom ASC"
    );
    mysqli_stmt_bind_param($stmt, 's', $onglet);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $utilisateurs[] = $row;
    mysqli_stmt_close($stmt);

    mysqli_close($conn);
}

/* les labels affichés sur les onglets */
$labels = ['Etudiant'=>'Étudiants', 'Tuteur'=>'Tuteurs', 'Jury'=>'Jurys', 'Entreprise'=>'Entreprises'];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-cy { border: 1px solid rgba(171,186,205,.4); border-radius: 18px; box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem; }
        
        .stat-card {
            background: #fff; border: 1px solid rgba(171,186,205,.4); border-radius: 14px;
            padding: 1.2rem; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,.03);
        }
        .stat-card .display-6 { font-family: 'Syne', sans-serif; font-weight: 800; color: var(--bleu); }

        .avatar-sm {
            width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;
        }

        /* Nav Pills Custom */
        .nav-pills .nav-link { color: var(--gris-texte); font-weight: 600; border-radius: 20px; padding: 8px 20px; transition: all 0.2s; }
        .nav-pills .nav-link:hover { background-color: #eef2ff; color: var(--bleu); }
        .nav-pills .nav-link.active { background-color: var(--bleu); color: #fff; box-shadow: 0 4px 10px rgba(27,79,155,.2); }
        
        /* Select inline */
        .form-select-sm { width: auto; display: inline-block; border-radius: 8px; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_admin.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-shield-lock-fill me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:1000px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_admin.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Gestion Utilisateurs</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Gérez les comptes, les rôles et les accès</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <!-- Statistiques (Comptes Actifs) -->
    <div class="row g-3 mb-4">
        <?php foreach ($labels as $r => $label) : ?>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="display-6 mb-1"><?php echo $stats[$r] ?? 0; ?></div>
                <div class="text-muted fw-bold text-uppercase" style="font-size:.7rem; letter-spacing:1px;"><?php echo h($label); ?> actifs</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Navigation par Onglets (Bootstrap Nav Pills) -->
    <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3">
        <?php foreach ($labels as $r => $label) : ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $onglet === $r ? 'active' : ''; ?>" href="gestion_espaces.php?role=<?php echo $r; ?>">
                    <?php echo h($label); ?> <span class="badge bg-light text-dark rounded-pill ms-1"><?php echo $stats[$r] ?? 0; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Liste des utilisateurs -->
    <div class="card-cy p-0 overflow-hidden">
        <h6 class="fw-bold text-dark p-3 m-0 border-bottom bg-light"><i class="bi bi-people-fill text-primary me-2"></i>Liste des <?php echo h($labels[$onglet] ?? $onglet); ?></h6>
        
        <?php if (empty($utilisateurs)) : ?>
            <div class="text-center p-5">
                <i class="bi bi-person-x text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                <p class="text-muted mb-0 fw-semibold">Aucun utilisateur dans cette catégorie.</p>
            </div>
        <?php else : ?>
            <div class="list-group list-group-flush">
                <?php foreach ($utilisateurs as $u) :
                    $ini = strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1));
                    $sous = match ($u['role_premier']) {
                        'Etudiant'   => trim(($u['filiere'] ?? '') . ($u['niveau'] ? ' · ' . $u['niveau'] : '')),
                        'Tuteur'     => $u['specialite'] ?? '',
                        'Entreprise' => $u['nom_entreprise'] ?? '',
                        'Jury'       => $u['specialite'] ?? '',
                        default      => ''
                    };
                ?>
                <div class="list-group-item p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    
                    <!-- Info Utilisateur -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm"><?php echo h($ini); ?></div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                                <?php echo h($u['prenom'] . ' ' . $u['nom']); ?>
                                <span class="badge <?php echo $u['actif'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> rounded-pill" style="font-size:.65rem;"><?php echo $u['actif'] ? 'Actif' : 'Inactif'; ?></span>
                            </h6>
                            <p class="text-muted mb-0" style="font-size:.8rem;">
                                <i class="bi bi-envelope me-1"></i> <?php echo h($u['email']); ?>
                                <?php if ($sous) : ?> <span class="mx-1">•</span> <?php echo h($sous); ?><?php endif; ?>
                            </p>
                            <?php if ($u['role_second'] || $u['role_troisieme']) : ?>
                                <div class="mt-1 d-flex gap-1">
                                    <?php if ($u['role_second']) : ?><span class="badge border text-secondary" style="font-size:.65rem;"><i class="bi bi-plus"></i> <?php echo h($u['role_second']); ?></span><?php endif; ?>
                                    <?php if ($u['role_troisieme']) : ?><span class="badge border text-secondary" style="font-size:.65rem;"><i class="bi bi-plus"></i> <?php echo h($u['role_troisieme']); ?></span><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions Administratives -->
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
                        
                        <!-- Changement de Rôle -->
                        <form method="POST" action="gestion_espaces.php?role=<?php echo $onglet; ?>" class="m-0">
                            <input type="hidden" name="action" value="changer_role">
                            <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                            <select name="nouveau_role" class="form-select form-select-sm bg-light text-primary" onchange="this.form.submit()">
                                <?php foreach (['Etudiant','Tuteur','Jury','Entreprise','Admin'] as $r) : ?>
                                    <option value="<?php echo $r; ?>" <?php echo $u['role_premier'] === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>

                        <!-- Activation/Désactivation -->
                        <form method="POST" action="gestion_espaces.php?role=<?php echo $onglet; ?>" class="m-0">
                            <input type="hidden" name="action" value="toggle_actif">
                            <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="actif_actuel" value="<?php echo $u['actif']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $u['actif'] ? 'btn-outline-danger' : 'btn-outline-success'; ?> fw-bold rounded-pill" onclick="return confirm('Confirmer cette action ?')">
                                <i class="bi <?php echo $u['actif'] ? 'bi-lock-fill' : 'bi-unlock-fill'; ?> me-1"></i> <?php echo $u['actif'] ? 'Désactiver' : 'Activer'; ?>
                            </button>
                        </form>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>