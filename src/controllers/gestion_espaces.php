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

    if ($_POST['action'] === 'changer_role' && $id_user > 0 && in_array($nouveau_role, $roles_ok)) {
        /* on met à jour le rôle principal de l'utilisateur */
        $upd = mysqli_prepare($conn, "UPDATE Utilisateur SET role_premier = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'si', $nouveau_role, $id_user);
        mysqli_stmt_execute($upd) ? $msg_ok = 'Rôle mis à jour.' : $msg_err = 'Erreur de mise à jour.';
        mysqli_stmt_close($upd);
    }

    /* on peut aussi activer ou désactiver un compte */
    if ($_POST['action'] === 'toggle_actif' && $id_user > 0) {
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
    <style>
        /* les onglets pour naviguer entre les rôles */
        .onglets { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
        .onglet {
            padding: 7px 18px; border-radius: 20px;
            border: 1px solid var(--gris-border); background: var(--blanc);
            font-family: 'DM Sans', sans-serif; font-size: .83rem; font-weight: 600;
            color: var(--gris-texte); text-decoration: none; transition: all .2s;
        }
        .onglet:hover, .onglet.actif { border-color: var(--bleu); background: var(--bleu); color: #fff; }

        /* le select inline pour changer le rôle directement dans la liste */
        .select-role {
            padding: 5px 8px; border: 1px solid var(--gris-border); border-radius: 6px;
            background: var(--gris-fond); font-family: 'DM Sans', sans-serif;
            font-size: .78rem; color: var(--noir); cursor: pointer; outline: none;
        }
        .select-role:focus { border-color: var(--bleu); }

        /* les boutons d'action alignés à droite */
        .user-actions { display: flex; gap: 6px; align-items: center; flex-shrink: 0; }
    </style>
</head>
<body>
<div class="page anim">

    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">Gestion des Utilisateurs</div>

    <!-- les messages après une action -->
    <?php if ($msg_ok) : ?><div class="msg-ok">✓ <?php echo htmlspecialchars($msg_ok); ?></div><?php endif; ?>
    <?php if ($msg_err) : ?><div class="msg-err"><?php echo htmlspecialchars($msg_err); ?></div><?php endif; ?>

    <!-- les 4 stats : nombre d'actifs par rôle -->
    <div class="stats-grille-4">
        <?php foreach ($labels as $r => $label) : ?>
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $stats[$r] ?? 0; ?></p>
            <p class="stat-label"><?php echo $label; ?> actifs</p>
        </div>
        <?php endforeach; ?>
    </div>

    <h3 class="options-title">Utilisateurs par rôle</h3>

    <!-- les onglets pour naviguer entre les 4 rôles -->
    <div class="onglets">
        <?php foreach ($labels as $r => $label) : ?>
        <a href="gestion_espaces.php?role=<?php echo $r; ?>"
           class="onglet <?php echo $onglet === $r ? 'actif' : ''; ?>">
            <?php echo $label; ?> (<?php echo $stats[$r] ?? 0; ?>)
        </a>
        <?php endforeach; ?>
    </div>

    <!-- la liste des utilisateurs du rôle sélectionné -->
    <p class="label-section"><?php echo $labels[$onglet] ?? $onglet; ?></p>
    <div class="carte">

        <?php if (empty($utilisateurs)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <p>Aucun utilisateur dans cette catégorie.</p>
        </div>

        <?php else : ?>
        <?php foreach ($utilisateurs as $u) :
            /* on construit les initiales pour l'avatar */
            $ini = strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1));
            /* on prépare la sous-ligne selon le rôle */
            $sous = match ($u['role_premier']) {
                'Etudiant'   => trim(($u['filiere'] ?? '') . ($u['niveau'] ? ' · ' . $u['niveau'] : '')),
                'Tuteur'     => $u['specialite'] ?? '',
                'Entreprise' => $u['nom_entreprise'] ?? '',
                'Jury'       => $u['specialite'] ?? '',
                default      => ''
            };
        ?>
        <div class="liste-ligne">

            <!-- avatar avec les initiales -->
            <div class="liste-avatar"><?php echo htmlspecialchars($ini); ?></div>

            <!-- infos nom, email, sous-titre et rôles secondaires -->
            <div class="liste-info">
                <p class="liste-nom"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></p>
                <p class="liste-sous">
                    <?php echo htmlspecialchars($u['email']); ?>
                    <?php if ($sous) : ?> · <?php echo htmlspecialchars($sous); ?><?php endif; ?>
                    <?php if ($u['role_second']) : ?>
                    &nbsp;<span style="color:var(--orange); font-size:.68rem; font-weight:700;"><?php echo $u['role_second']; ?></span>
                    <?php endif; ?>
                    <?php if ($u['role_troisieme']) : ?>
                    &nbsp;<span style="color:var(--orange); font-size:.68rem; font-weight:700;"><?php echo $u['role_troisieme']; ?></span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- badge actif / inactif -->
            <span class="badge <?php echo $u['actif'] ? 'badge-vert' : 'badge-rouge'; ?>" style="flex-shrink:0;">
                <?php echo $u['actif'] ? 'Actif' : 'Inactif'; ?>
            </span>

            <!-- actions : changer le rôle et activer/désactiver -->
            <div class="user-actions">

                <!-- formulaire de changement de rôle : le select soumet automatiquement -->
                <form method="POST" action="gestion_espaces.php?role=<?php echo $onglet; ?>">
                    <input type="hidden" name="action" value="changer_role">
                    <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                    <select name="nouveau_role" class="select-role" onchange="this.form.submit()">
                        <?php foreach (['Etudiant','Tuteur','Jury','Entreprise','Admin'] as $r) : ?>
                        <option value="<?php echo $r; ?>" <?php echo $u['role_premier'] === $r ? 'selected' : ''; ?>>
                            <?php echo $r; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <!-- bouton pour activer ou désactiver le compte -->
                <form method="POST" action="gestion_espaces.php?role=<?php echo $onglet; ?>">
                    <input type="hidden" name="action" value="toggle_actif">
                    <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                    <input type="hidden" name="actif_actuel" value="<?php echo $u['actif']; ?>">
                    <button type="submit"
                            class="<?php echo $u['actif'] ? 'btn-danger' : 'btn-outline'; ?>"
                            onclick="return confirm('Confirmer cette action ?')">
                        <svg viewBox="0 0 24 24"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                        <?php echo $u['actif'] ? 'Désactiver' : 'Activer'; ?>
                    </button>
                </form>

            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <div class="deconnexion">
        <a href="accueil_admin.php">← Retour au menu</a>
    </div>

</div>
</body>
</html>
