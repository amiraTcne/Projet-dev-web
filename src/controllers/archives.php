<?php
/* on démarre la session */
session_start();

/* on vérifie que c'est bien un admin connecté */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

/* on se connecte à la base de données */
$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';

/* on traite le formulaire quand l'admin soumet un dossier à archiver */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $num_dossier      = (int)($_POST['num_dossier'] ?? 0);
    $motif            = trim($_POST['motif'] ?? '');
    $annee_academique = trim($_POST['annee_academique'] ?? '');

    if ($num_dossier <= 0) {
        $msg_err = 'Sélectionne un dossier à archiver.';
    } elseif (empty($annee_academique)) {
        $msg_err = "L'année académique est obligatoire.";
    } else {
        /* on vérifie que ce dossier n'est pas déjà archivé pour éviter les doublons */
        $chk = mysqli_prepare($conn, "SELECT 1 FROM Archive WHERE num_dossier = ?");
        mysqli_stmt_bind_param($chk, 'i', $num_dossier);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $msg_err = 'Ce dossier est déjà archivé.';
        } else {
            /* on insère l'archive dans la table Archive */
            $ins = mysqli_prepare($conn,
                "INSERT INTO Archive (num_dossier, id_user_admin, motif, annee_academique)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($ins, 'iiss',
                $num_dossier,      /* le dossier qu'on archive */
                $_SESSION['id'],   /* l'admin qui effectue l'archivage */
                $motif,            /* le motif (optionnel) */
                $annee_academique  /* ex: 2025-2026 */
            );
            if (mysqli_stmt_execute($ins)) {
                $msg_ok = 'Dossier archivé avec succès !';
            } else {
                $msg_err = "Erreur lors de l'archivage.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($chk);
    }
}

/* on prépare les listes et les stats qu'on va afficher */
$dossiers_disponibles = []; /* les dossiers validés qu'on peut encore archiver */
$archives             = []; /* la liste de toutes les archives existantes */
$nb_total             = 0;  /* nombre total d'archives */
$nb_annee             = 0;  /* nombre d'archives pour l'année en cours */
$annee_courante       = date('Y') . '-' . (date('Y') + 1); /* ex: 2025-2026 */

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* on récupère les dossiers validés qui ne sont pas encore dans la table Archive
       ce sont les seuls qu'on peut proposer à l'archivage */
    $q1 = mysqli_query($conn,
        "SELECT d.num_dossier,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant,
                s.titre AS titre_stage,
                ent.nom_entreprise,
                u.filiere,
                u.annee_promo
         FROM Dossier_Stage d
         JOIN Stage s         ON s.num_stage   = d.num_stage
         JOIN Utilisateur u   ON u.id           = d.id_etudiant
         JOIN Utilisateur ent ON ent.id          = s.id_entreprise
         WHERE d.statut = 'valide'
           AND d.num_dossier NOT IN (SELECT num_dossier FROM Archive)
         ORDER BY d.date_modification DESC"
    );
    while ($row = mysqli_fetch_assoc($q1)) {
        $dossiers_disponibles[] = $row;
    }

    /* on récupère toutes les archives avec les infos de l'étudiant, du stage et de l'admin */
    $q2 = mysqli_query($conn,
        "SELECT a.id_archive,
                a.date_archivage,
                a.motif,
                a.annee_academique,
                CONCAT(u.prenom, ' ', u.nom)    AS etudiant,
                u.filiere,
                s.titre                          AS titre_stage,
                ent.nom_entreprise,
                CONCAT(adm.prenom, ' ', adm.nom) AS admin_nom
         FROM Archive a
         JOIN Dossier_Stage d  ON d.num_dossier = a.num_dossier
         JOIN Stage s          ON s.num_stage   = d.num_stage
         JOIN Utilisateur u    ON u.id           = d.id_etudiant
         JOIN Utilisateur ent  ON ent.id          = s.id_entreprise
         JOIN Utilisateur adm  ON adm.id          = a.id_user_admin
         ORDER BY a.date_archivage DESC"
    );
    while ($row = mysqli_fetch_assoc($q2)) {
        $archives[] = $row;
    }

    /* on calcule les deux stats */
    $nb_total = count($archives);
    foreach ($archives as $a) {
        if ($a['annee_academique'] === $annee_courante) {
            $nb_annee++;
        }
    }

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives — CY Stage</title>
    <!-- on réutilise le même CSS que les autres pages admin -->
    <link rel="stylesheet" href="../../public/assets/css/style_acceuil.css">
    <style>
        /* le bandeau bleu en haut de la page */
        .bandeau-page {
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            border-radius: var(--radius);
            padding: 20px;
            color: #fff;
            margin-bottom: 24px;
        }
        .bandeau-page h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .bandeau-page p { font-size: .82rem; opacity: .85; }

        /* les deux boîtes de stats côte à côte */
        .stats-grille {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        .stat-carte {
            background: var(--blanc);
            border: 1px solid var(--gris-border);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
            box-shadow: var(--shadow);
        }
        .stat-nombre {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--bleu);
            line-height: 1;
        }
        .stat-label { font-size: .73rem; color: var(--gris-texte); margin-top: 5px; line-height: 1.3; }

        /* le petit titre de section en majuscules */
        .section-titre {
            font-family: 'Syne', sans-serif;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--bleu);
            margin-bottom: 12px;
        }

        /* la carte blanche qui contient le formulaire d'archivage */
        .form-card {
            background: var(--blanc);
            border: 1px solid var(--gris-border);
            border-radius: var(--radius);
            padding: 18px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }

        /* les labels des champs du formulaire */
        .field-label {
            font-size: .75rem;
            font-weight: 600;
            color: var(--gris-texte);
            margin-bottom: 5px;
            display: block;
        }

        /* les champs de saisie et la liste déroulante */
        .field-select,
        .field-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--gris-border);
            border-radius: 8px;
            background: var(--gris-fond);
            font-family: 'DM Sans', sans-serif;
            font-size: .87rem;
            color: var(--noir);
            outline: none;
            margin-bottom: 14px;
            transition: border-color .2s;
        }
        .field-select:focus,
        .field-input:focus { border-color: var(--bleu); background: var(--blanc); }
        .field-input::placeholder { color: var(--gris-texte); opacity: .6; }

        /* le bouton pour valider l'archivage */
        .btn-archiver {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 8px;
            background: var(--bleu);
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: .88rem;
            cursor: pointer;
            transition: opacity .2s, transform .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }
        .btn-archiver:hover { opacity: .88; transform: translateY(-1px); }
        .btn-archiver svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        /* chaque ligne d'archive dans la liste */
        .archive-ligne {
            background: var(--blanc);
            border: 1px solid var(--gris-border);
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: var(--shadow);
            transition: transform .15s;
        }
        .archive-ligne:hover { transform: translateY(-1px); }

        .archive-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        /* le nom de l'étudiant et le titre du stage */
        .archive-nom   { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .92rem; }
        .archive-stage { font-size: .78rem; color: var(--bleu-clair); font-weight: 600; margin-top: 2px; }

        /* le badge avec l'année académique */
        .badge-annee {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(27, 79, 155, .10);
            color: var(--bleu);
            font-size: .70rem;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* les petites infos en bas de chaque ligne (filière, date, admin) */
        .archive-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: .74rem;
            color: var(--gris-texte);
        }
        .archive-meta span { display: flex; align-items: center; gap: 4px; }
        .archive-meta svg  { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

        /* le motif affiché en italique si l'admin en a renseigné un */
        .archive-motif {
            font-size: .76rem;
            color: var(--gris-texte);
            font-style: italic;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid var(--gris-border);
        }

        /* quand il n'y a rien à afficher */
        .etat-vide {
            text-align: center;
            padding: 36px 20px;
            color: var(--gris-texte);
        }
        .etat-vide svg { width: 44px; height: 44px; opacity: .3; margin-bottom: 10px; }
        .etat-vide p   { font-size: .84rem; }

        /* les messages de retour après soumission du formulaire */
        .msg-ok  { background: rgba(22,163,74,.09); border: 1px solid #16a34a; border-radius: 8px; padding: 9px 13px; font-size: .83rem; color: #16a34a; font-weight: 600; margin-bottom: 16px; }
        .msg-err { background: #fff0f0; border: 1px solid #fca5a5; border-radius: 8px; padding: 9px 13px; font-size: .83rem; color: #dc2626; margin-bottom: 16px; }

        /* le bouton retour en bas de page */
        .btn-retour {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--gris-texte);
            text-decoration: none;
            font-size: .88rem;
            font-weight: 500;
            padding: 9px 20px;
            border: 1px solid var(--gris-border);
            border-radius: 8px;
            transition: background .15s, color .15s;
        }
        .btn-retour:hover { background: var(--gris-fond); color: var(--noir); }
    </style>
</head>
<body>

<div class="page">

    <!-- le logo en haut à droite comme sur les autres pages admin -->
    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <!-- le bandeau bleu avec le titre et l'année courante -->
    <div class="bandeau-page">
        <h2>📦 Archives</h2>
        <p>Archivage des dossiers de stage validés · Année <?php echo htmlspecialchars($annee_courante); ?></p>
    </div>

    <!-- les messages de retour après soumission du formulaire -->
    <?php if ($msg_ok) : ?>
    <div class="msg-ok">✓ <?php echo htmlspecialchars($msg_ok); ?></div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
    <div class="msg-err"><?php echo htmlspecialchars($msg_err); ?></div>
    <?php endif; ?>

    <!-- les deux stats : total archivé et archivé cette année -->
    <div class="stats-grille">
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $nb_total; ?></p>
            <p class="stat-label">Dossier<?php echo $nb_total > 1 ? 's' : ''; ?> archivé<?php echo $nb_total > 1 ? 's' : ''; ?> au total</p>
        </div>
        <div class="stat-carte">
            <p class="stat-nombre"><?php echo $nb_annee; ?></p>
            <p class="stat-label">Cette année académique</p>
        </div>
    </div>

    <!-- le formulaire pour archiver un nouveau dossier
         seuls les dossiers validés et pas encore archivés apparaissent dans la liste -->
    <p class="section-titre">Archiver un dossier</p>

    <div class="form-card">
        <?php if (empty($dossiers_disponibles)) : ?>
        <!-- quand tous les dossiers validés sont déjà archivés -->
        <p style="font-size:.84rem; color:var(--gris-texte); text-align:center; padding:10px 0;">
            Aucun dossier validé en attente d'archivage.
        </p>
        <?php else : ?>
        <form method="POST" action="archives.php">

            <!-- on choisit le dossier dans une liste déroulante -->
            <label class="field-label" for="num_dossier">Dossier à archiver</label>
            <select class="field-select" name="num_dossier" id="num_dossier" required>
                <option value="">— Sélectionner un dossier —</option>
                <?php foreach ($dossiers_disponibles as $d) : ?>
                <option value="<?php echo (int)$d['num_dossier']; ?>">
                    <?php echo htmlspecialchars(
                        $d['etudiant'] . ' · ' .
                        $d['titre_stage'] . ' @ ' .
                        $d['nom_entreprise']
                    ); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- l'année académique est pré-remplie avec l'année en cours -->
            <label class="field-label" for="annee_academique">Année académique</label>
            <input class="field-input" type="text" name="annee_academique" id="annee_academique"
                   placeholder="ex : 2025-2026"
                   value="<?php echo htmlspecialchars($annee_courante); ?>"
                   pattern="\d{4}-\d{4}"
                   title="Format attendu : 2025-2026"
                   required>

            <!-- le motif est optionnel, l'admin peut laisser vide -->
            <label class="field-label" for="motif">Motif (optionnel)</label>
            <input class="field-input" type="text" name="motif" id="motif"
                   placeholder="Ex : Fin de stage validé par le jury">

            <button type="submit" class="btn-archiver">
                <svg viewBox="0 0 24 24"><path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><path d="M10 12h4"/></svg>
                Archiver ce dossier
            </button>

        </form>
        <?php endif; ?>
    </div>

    <!-- la liste de toutes les archives déjà créées -->
    <p class="section-titre">Archives existantes (<?php echo $nb_total; ?>)</p>

    <?php if (empty($archives)) : ?>
    <!-- quand il n'y a encore aucune archive -->
    <div class="etat-vide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5" rx="1"/><path d="M10 12h4"/>
        </svg>
        <p>Aucune archive pour le moment.</p>
    </div>

    <?php else : ?>

    <?php foreach ($archives as $a) : ?>
    <div class="archive-ligne">

        <!-- le nom de l'étudiant, le titre du stage et le badge de l'année -->
        <div class="archive-top">
            <div>
                <p class="archive-nom"><?php echo htmlspecialchars($a['etudiant']); ?></p>
                <p class="archive-stage">
                    <?php echo htmlspecialchars($a['titre_stage']); ?> · <?php echo htmlspecialchars($a['nom_entreprise']); ?>
                </p>
            </div>
            <span class="badge-annee"><?php echo htmlspecialchars($a['annee_academique']); ?></span>
        </div>

        <!-- la filière, la date d'archivage et l'admin qui a archivé -->
        <div class="archive-meta">

            <?php if ($a['filiere']) : ?>
            <span>
                <svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                <?php echo htmlspecialchars($a['filiere']); ?>
            </span>
            <?php endif; ?>

            <span>
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Archivé le <?php echo date('d/m/Y', strtotime($a['date_archivage'])); ?>
            </span>

            <span>
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Par <?php echo htmlspecialchars($a['admin_nom']); ?>
            </span>

        </div>

        <!-- on affiche le motif seulement si l'admin en a renseigné un -->
        <?php if (!empty($a['motif'])) : ?>
        <p class="archive-motif">"<?php echo htmlspecialchars($a['motif']); ?>"</p>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

    <?php endif; ?>

    <!-- le lien pour revenir au menu admin -->
    <div class="deconnexion">
        <a href="accueil_admin.php" class="btn-retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Retour au menu
        </a>
    </div>

</div>

</body>
</html>
