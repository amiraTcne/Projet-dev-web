<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $id_user = (int)($_POST['id_user'] ?? 0);
    $roles_ok = ['Etudiant', 'Tuteur', 'Jury', 'Entreprise', 'Admin', ''];

    // Modification des 3 rôles[cite: 39]
    if (isset($_POST['action']) && $_POST['action'] === 'modifier_roles' && $id_user > 0) {
        $r1 = in_array($_POST['role_premier'], $roles_ok) ? $_POST['role_premier'] : null;
        $r2 = !empty($_POST['role_second']) && in_array($_POST['role_second'], $roles_ok) ? $_POST['role_second'] : null;
        $r3 = !empty($_POST['role_troisieme']) && in_array($_POST['role_troisieme'], $roles_ok) ? $_POST['role_troisieme'] : null;

        $upd = mysqli_prepare($conn, "UPDATE Utilisateur SET role_premier = ?, role_second = ?, role_troisieme = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'sssi', $r1, $r2, $r3, $id_user);
        mysqli_stmt_execute($upd) ? $msg_ok = 'Rôles mis à jour.' : $msg_err = 'Erreur de mise à jour.';
        mysqli_stmt_close($upd);
    }

    // Activation / Désactivation du compte[cite: 39]
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

$onglet       = $_GET['role'] ?? 'Etudiant';
$roles_dispo  = ['Etudiant', 'Tuteur', 'Jury', 'Entreprise'];
$utilisateurs = [];

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    $stmt = mysqli_prepare($conn, "SELECT * FROM Utilisateur WHERE role_premier = ? ORDER BY nom ASC");
    mysqli_stmt_bind_param($stmt, 's', $onglet);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $utilisateurs[] = $row;
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

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
        .card-cy {
            border: 1px solid rgba(171,186,205,.4); border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.06); background: #fff; padding: 1.5rem;
            transition: transform 0.2s;
        }
        .card-cy:hover { transform: translateY(-3px); border-color: var(--bleu-clair); }
        
        .avatar-sm {
            width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;
            font-family: 'Syne', sans-serif;
        }

        /* Nav Pills Custom */
        .nav-pills .nav-link { color: var(--gris-texte); font-weight: 600; border-radius: 20px; padding: 8px 20px; transition: all 0.2s; }
        .nav-pills .nav-link:hover { background-color: #eef2ff; color: var(--bleu); }
        .nav-pills .nav-link.active { background-color: var(--bleu); color: #fff; box-shadow: 0 4px 10px rgba(27,79,155,.2); }
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

<div class="container mb-5" style="max-width:1100px;">
    
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_admin.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Gestion des accès</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Gérez les rôles multiples et le statut des utilisateurs</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4 shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div>
    <?php endif; ?>

    <!-- Onglets de navigation[cite: 39] -->
    <ul class="nav nav-pills gap-2 mb-4 pb-3 border-bottom">
        <?php foreach ($roles_dispo as $r) : ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $onglet === $r ? 'active' : ''; ?>" href="?role=<?php echo $r; ?>">
                    <?php echo h($r); ?>s
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Grille des utilisateurs -->
    <div class="row g-4">
        <?php if (empty($utilisateurs)) : ?>
            <div class="col-12">
                <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
                    <i class="bi bi-person-x text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                    <p class="text-muted mb-0 fw-semibold">Aucun utilisateur trouvé dans cette catégorie.</p>
                </div>
            </div>
        <?php else : ?>
            <?php foreach ($utilisateurs as $u) : 
                $ini = strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1));
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-cy h-100 d-flex flex-column position-relative">
                        
                        <!-- Badge d'état -->
                        <span class="position-absolute top-0 end-0 mt-3 me-3 badge <?php echo $u['actif'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> rounded-pill border">
                            <?php echo $u['actif'] ? 'Actif' : 'Inactif'; ?>
                        </span>

                        <!-- Info utilisateur -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-sm"><?php echo h($ini); ?></div>
                            <div class="flex-grow-1 pe-4">
                                <h6 class="fw-bold mb-0 text-dark" style="font-family:'Syne',sans-serif; font-size:.95rem;"><?php echo h($u['prenom'] . ' ' . $u['nom']); ?></h6>
                                <small class="text-muted text-truncate d-block" style="max-width: 180px;"><i class="bi bi-envelope me-1"></i><?php echo h($u['email']); ?></small>
                            </div>
                        </div>
                        
                        <!-- Formulaire des rôles[cite: 39] -->
                        <form method="POST" class="flex-grow-1 d-flex flex-column">
                            <input type="hidden" name="action" value="modifier_roles">
                            <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                            
                            <div class="bg-light p-3 rounded-3 mb-3 flex-grow-1">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-dark mb-1">Rôle Principal</label>
                                    <select name="role_premier" class="form-select form-select-sm border-0 shadow-sm fw-semibold text-primary">
                                        <?php foreach (['Etudiant','Tuteur','Jury','Entreprise','Admin'] as $role) : ?>
                                            <option value="<?php echo $role; ?>" <?php echo $u['role_premier'] === $role ? 'selected' : ''; ?>><?php echo $role; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-dark mb-1">Rôle Secondaire</label>
                                    <select name="role_second" class="form-select form-select-sm border-0 shadow-sm">
                                        <option value="" class="text-muted">-- Aucun --</option>
                                        <?php foreach (['Tuteur','Jury','Entreprise','Admin'] as $role) : ?>
                                            <option value="<?php echo $role; ?>" <?php echo $u['role_second'] === $role ? 'selected' : ''; ?>><?php echo $role; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-1">
                                    <label class="form-label small fw-bold text-dark mb-1">Rôle Tertiaire</label>
                                    <select name="role_troisieme" class="form-select form-select-sm border-0 shadow-sm">
                                        <option value="" class="text-muted">-- Aucun --</option>
                                        <?php foreach (['Tuteur','Jury','Entreprise','Admin'] as $role) : ?>
                                            <option value="<?php echo $role; ?>" <?php echo $u['role_troisieme'] === $role ? 'selected' : ''; ?>><?php echo $role; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold shadow-sm mb-2" style="background:var(--bleu); border:none;">
                                <i class="bi bi-save me-1"></i> Sauvegarder les rôles
                            </button>
                        </form>

                        <!-- Formulaire d'activation / désactivation[cite: 39] -->
                        <form method="POST" class="mt-auto">
                            <input type="hidden" name="action" value="toggle_actif">
                            <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="actif_actuel" value="<?php echo $u['actif']; ?>">
                            <button type="submit" class="btn <?php echo $u['actif'] ? 'btn-outline-danger' : 'btn-outline-success'; ?> btn-sm w-100 rounded-pill fw-bold" onclick="return confirm('Confirmer cette action ?')">
                                <i class="bi <?php echo $u['actif'] ? 'bi-lock-fill' : 'bi-unlock-fill'; ?> me-1"></i> <?php echo $u['actif'] ? 'Désactiver le compte' : 'Activer le compte'; ?>
                            </button>
                        </form>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>