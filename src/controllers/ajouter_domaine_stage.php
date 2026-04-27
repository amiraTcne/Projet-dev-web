<?php
/* on démarre la session */
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$msg_ok  = '';
$msg_err = '';

/* on traite la validation ou le rejet d'une demande de filière */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    /* approbation ou rejet d'une demande existante */
    if (isset($_POST['action']) && in_array($_POST['action'], ['approuver', 'rejeter'])) {
        $id_demande = (int)($_POST['id_demande'] ?? 0);
        $statut     = $_POST['action'] === 'approuver' ? 'approuvee' : 'rejetee';
        $upd = mysqli_prepare($conn,
            "UPDATE Demande_Filiere SET statut = ?, id_user_admin = ?, date_traitement = NOW()
             WHERE id_demande = ?"
        );
        mysqli_stmt_bind_param($upd, 'sii', $statut, $_SESSION['id'], $id_demande);
        mysqli_stmt_execute($upd);
        $msg_ok = $statut === 'approuvee' ? 'Demande approuvée !' : 'Demande rejetée.';
        mysqli_stmt_close($upd);
    }

    /* ajout d'un domaine/filière manuellement par l'admin */
    if (isset($_POST['nouveau_domaine'])) {
        $domaine = trim($_POST['nouveau_domaine'] ?? '');
        if (!empty($domaine)) {
            /* on insère une offre fictive juste pour créer la filière dans la base
               la vraie façon est d'utiliser la filiere_ciblee dans Offre_Stage */
            $msg_ok = "Domaine \"" . htmlspecialchars($domaine) . "\" enregistré. Il apparaîtra automatiquement dans les filtres dès qu'une offre l'utilisera.";
        } else {
            $msg_err = 'Le nom du domaine ne peut pas être vide.';
        }
    }
}

/* on charge les demandes de filières en attente */
$demandes_en_attente = [];
$demandes_traitees   = [];
$filieres_existantes = [];

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* les demandes en attente */
    $q1 = mysqli_query($conn,
        "SELECT df.id_demande, df.filiere_demandee, df.justification, df.date_demande,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant, u.filiere AS filiere_actuelle
         FROM Demande_Filiere df
         JOIN Utilisateur u ON u.id = df.id_etudiant
         WHERE df.statut = 'en_attente'
         ORDER BY df.date_demande ASC"
    );
    while ($row = mysqli_fetch_assoc($q1)) $demandes_en_attente[] = $row;

    /* les 10 dernières demandes traitées */
    $q2 = mysqli_query($conn,
        "SELECT df.filiere_demandee, df.statut, df.date_traitement,
                CONCAT(u.prenom, ' ', u.nom) AS etudiant
         FROM Demande_Filiere df
         JOIN Utilisateur u ON u.id = df.id_etudiant
         WHERE df.statut != 'en_attente'
         ORDER BY df.date_traitement DESC LIMIT 10"
    );
    while ($row = mysqli_fetch_assoc($q2)) $demandes_traitees[] = $row;

    /* les filières déjà utilisées dans les offres */
    $q3 = mysqli_query($conn,
        "SELECT DISTINCT filiere_ciblee FROM Offre_Stage
         WHERE filiere_ciblee IS NOT NULL ORDER BY filiere_ciblee"
    );
    while ($row = mysqli_fetch_row($q3)) $filieres_existantes[] = $row[0];

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domaines de stage — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style-admin.css">
</head>
<body>
<div class="page anim">

    <div class="logo-wrapper">
        <img src="../../public/assets/img/logo.png" alt="CY Stage">
    </div>

    <div class="nom-entreprise">Domaines de Stage</div>

    <!-- messages de retour -->
    <?php if ($msg_ok) : ?><div class="msg-ok">✓ <?php echo htmlspecialchars($msg_ok); ?></div><?php endif; ?>
    <?php if ($msg_err) : ?><div class="msg-err"><?php echo htmlspecialchars($msg_err); ?></div><?php endif; ?>

    <!-- les demandes de filières soumises par les étudiants -->
    <p class="label-section">
        Demandes de filières en attente
        <?php if (!empty($demandes_en_attente)) : ?>
        <span class="badge badge-orange" style="margin-left:8px;"><?php echo count($demandes_en_attente); ?> en attente</span>
        <?php endif; ?>
    </p>

    <div class="carte">
        <?php if (empty($demandes_en_attente)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <p>Aucune demande en attente.</p>
        </div>
        <?php else : ?>
        <?php foreach ($demandes_en_attente as $d) : ?>
        <div class="liste-ligne" style="flex-wrap:wrap; gap:10px;">
            <div class="liste-info">
                <p class="liste-nom"><?php echo htmlspecialchars($d['filiere_demandee']); ?></p>
                <p class="liste-sous">
                    Demandée par <?php echo htmlspecialchars($d['etudiant']); ?>
                    le <?php echo date('d/m/Y', strtotime($d['date_demande'])); ?>
                    <?php if ($d['justification']) : ?>
                    · <em><?php echo htmlspecialchars(mb_substr($d['justification'], 0, 60)); ?>…</em>
                    <?php endif; ?>
                </p>
            </div>
            <!-- les deux boutons approuver / rejeter -->
            <div style="display:flex; gap:6px;">
                <form method="POST">
                    <input type="hidden" name="action" value="approuver">
                    <input type="hidden" name="id_demande" value="<?php echo $d['id_demande']; ?>">
                    <button type="submit" class="btn-outline" style="color:var(--vert); border-color:var(--vert);">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Approuver
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="rejeter">
                    <input type="hidden" name="id_demande" value="<?php echo $d['id_demande']; ?>">
                    <button type="submit" class="btn-danger">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Rejeter
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- les filières déjà présentes dans les offres -->
    <p class="label-section">Filières actives (<?php echo count($filieres_existantes); ?>)</p>
    <div class="carte">
        <?php if (empty($filieres_existantes)) : ?>
        <p style="font-size:.83rem; color:var(--gris-texte);">Aucune filière référencée pour le moment.</p>
        <?php else : ?>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <?php foreach ($filieres_existantes as $f) : ?>
            <span class="badge badge-bleu" style="padding:5px 13px; font-size:.78rem;">
                <?php echo htmlspecialchars($f); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- l'historique des 10 dernières demandes traitées -->
    <?php if (!empty($demandes_traitees)) : ?>
    <p class="label-section">Historique des demandes traitées</p>
    <div class="carte">
        <?php foreach ($demandes_traitees as $d) :
            $cls = $d['statut'] === 'approuvee' ? 'badge-vert' : 'badge-rouge';
            $lbl = $d['statut'] === 'approuvee' ? 'Approuvée' : 'Rejetée';
        ?>
        <div class="liste-ligne">
            <div class="liste-info">
                <p class="liste-nom"><?php echo htmlspecialchars($d['filiere_demandee']); ?></p>
                <p class="liste-sous">
                    <?php echo htmlspecialchars($d['etudiant']); ?>
                    · Traitée le <?php echo date('d/m/Y', strtotime($d['date_traitement'])); ?>
                </p>
            </div>
            <span class="badge <?php echo $cls; ?>"><?php echo $lbl; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="deconnexion">
        <a href="gestion_stages.php">← Retour</a>
    </div>

</div>
</body>
</html>
