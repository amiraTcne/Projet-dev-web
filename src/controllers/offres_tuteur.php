<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Tuteur') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}

$conn    = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
$offres  = [];
$msg_ok  = '';
$msg_err = '';

/* Affecter un étudiant à une offre (créer un Stage) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $id_etudiant = (int)($_POST['id_etudiant'] ?? 0);
    $num_offre   = (int)($_POST['num_offre']   ?? 0);

    if ($id_etudiant > 0 && $num_offre > 0) {
        /* Vérifier que l'étudiant n'est pas déjà affecté à cette offre */
        $chk = mysqli_prepare($conn, "SELECT 1 FROM Stage WHERE id_etudiant = ? AND num_offre = ?");
        mysqli_stmt_bind_param($chk, 'ii', $id_etudiant, $num_offre);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);

        if (mysqli_stmt_num_rows($chk) > 0) {
            $msg_err = 'Cet étudiant est déjà affecté à cette offre.';
        } else {
            $ge = mysqli_prepare($conn, "SELECT id_entreprise, titre FROM Offre_Stage WHERE num_offre = ?");
            mysqli_stmt_bind_param($ge, 'i', $num_offre);
            mysqli_stmt_execute($ge);
            $ent = mysqli_fetch_assoc(mysqli_stmt_get_result($ge));
            mysqli_stmt_close($ge);

            if ($ent) {
                $ins = mysqli_prepare($conn,
                    "INSERT INTO Stage (titre, id_etudiant, id_entreprise, num_offre, id_tuteur, statut)
                     VALUES (?, ?, ?, ?, ?, 'en_attente')"
                );
                mysqli_stmt_bind_param($ins, 'siiii',
                    $ent['titre'], $id_etudiant, $ent['id_entreprise'], $num_offre, $_SESSION['id']
                );
                if (mysqli_stmt_execute($ins)) {
                    $msg_ok = 'Étudiant affecté avec succès !';
                } else {
                    $msg_err = 'Erreur lors de l\'affectation.';
                }
                mysqli_stmt_close($ins);
            }
        }
        mysqli_stmt_close($chk);
    }
}

/* Filtres */
$q       = trim($_GET['q']       ?? '');
$secteur = trim($_GET['secteur'] ?? '');
$ville   = trim($_GET['ville']   ?? '');

$etudiants = [];
$secteurs  = [];
$villes    = [];

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');

    /* Récupérer les étudiants pour le dropdown d'affectation */
    $re = mysqli_query($conn,
        "SELECT id, CONCAT(prenom, ' ', nom) AS nom_complet
         FROM Utilisateur WHERE role_premier = 'Etudiant' AND actif = 1
         ORDER BY nom, prenom"
    );
    while ($e = mysqli_fetch_assoc($re)) {
        $etudiants[] = $e;
    }

    /* Requête offres */
    $sql = "SELECT o.num_offre, o.titre, o.mission, o.competences,
                   o.filiere_ciblee, o.duree_semaines, o.date_debut,
                   u.nom_entreprise, u.secteur, u.ville
            FROM Offre_Stage o
            JOIN Utilisateur u ON u.id = o.id_entreprise
            WHERE o.statut = 'ouverte'";

    $params = []; $types = '';

    if ($q !== '') {
        $sql .= " AND (o.titre LIKE ? OR o.mission LIKE ? OR u.nom_entreprise LIKE ?)";
        $like = '%' . $q . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types .= 'sss';
    }
    if ($secteur !== '') {
        $sql .= " AND u.secteur = ?";
        $params[] = $secteur; $types .= 's';
    }
    if ($ville !== '') {
        $sql .= " AND u.ville = ?";
        $params[] = $ville; $types .= 's';
    }

    $sql .= " ORDER BY o.date_publication DESC LIMIT 50";
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $offres[] = $row;
    mysqli_stmt_close($stmt);

    /* Secteurs et villes pour filtres */
    $rs = mysqli_query($conn,
        "SELECT DISTINCT u.secteur FROM Offre_Stage o
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE o.statut = 'ouverte' AND u.secteur IS NOT NULL ORDER BY u.secteur"
    );
    while ($s = mysqli_fetch_row($rs)) $secteurs[] = $s[0];

    $rv = mysqli_query($conn,
        "SELECT DISTINCT u.ville FROM Offre_Stage o
         JOIN Utilisateur u ON u.id = o.id_entreprise
         WHERE o.statut = 'ouverte' AND u.ville IS NOT NULL ORDER BY u.ville"
    );
    while ($v = mysqli_fetch_row($rv)) $villes[] = $v[0];

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de Stage — CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/style_etudiant.css">
    <style>
        .modal-affecter { display:none; position:fixed; inset:0; background:rgba(17,24,39,.45); z-index:100; align-items:flex-end; justify-content:center; }
        .modal-inner    { background:#fff; border-radius:20px 20px 0 0; width:100%; max-width:520px; padding:20px 18px 32px; }
        .modal-handle   { width:32px; height:4px; border-radius:2px; background:var(--gris-border); margin:0 auto 17px; }
        select.select-field { width:100%; padding:10px 12px; border:1px solid var(--gris-border); border-radius:8px; background:var(--gris-fond); font-family:'DM Sans',sans-serif; font-size:.87rem; color:var(--noir); outline:none; appearance:none; margin-top:8px; cursor:pointer; }
        select.select-field:focus { border-color:var(--bleu); }
        .btn-affecter { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border:1px solid var(--bleu); background:var(--bleu); color:#fff; border-radius:20px; font-size:.74rem; font-weight:700; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; }
        .btn-affecter:hover { opacity:.85; }
    </style>
</head>
<body>
<div class="page anim">

    <header class="entete">
        <a href="accueil_tuteur.php" class="btn-retour" aria-label="Retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="entete-titre">Offres de Stages</span>
        <div style="width:36px;"></div>
    </header>

    <div class="contenu">

        <?php if ($msg_ok) : ?>
        <div style="background:rgba(22,163,74,.09); border:1px solid var(--vert); border-radius:8px; padding:9px 13px; font-size:.83rem; color:var(--vert); font-weight:600;">
            ✓ <?php echo htmlspecialchars($msg_ok); ?>
        </div>
        <?php endif; ?>
        <?php if ($msg_err) : ?>
        <div style="background:#fff0f0; border:1px solid #fca5a5; border-radius:8px; padding:9px 13px; font-size:.83rem; color:var(--rouge);">
            <?php echo htmlspecialchars($msg_err); ?>
        </div>
        <?php endif; ?>

        <!-- Barre de recherche -->
        <form method="GET" id="form-recherche">
            <div class="barre-recherche" style="margin-bottom:10px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="q" id="champ-q" placeholder="Rechercher un poste ou une entreprise…"
                       value="<?php echo htmlspecialchars($q); ?>" autocomplete="off">
            </div>

            <!-- Chips secteurs -->
            <?php if (!empty($secteurs)) : ?>
            <div class="filtres" style="margin-bottom:6px;">
                <a href="offres_tuteur.php" class="chip <?php echo $secteur === '' ? 'actif' : ''; ?>">Tous</a>
                <?php foreach ($secteurs as $s) : ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['secteur' => $s])); ?>"
                   class="chip <?php echo $secteur === $s ? 'actif' : ''; ?>">
                    <?php echo htmlspecialchars($s); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Chips villes -->
            <?php if (!empty($villes)) : ?>
            <div class="filtres" style="margin-bottom:8px;">
                <a href="offres_tuteur.php" class="chip <?php echo $ville === '' ? 'actif' : ''; ?>">Toutes villes</a>
                <?php foreach ($villes as $v) : ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['ville' => $v])); ?>"
                   class="chip <?php echo $ville === $v ? 'actif' : ''; ?>">
                    <?php echo htmlspecialchars($v); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </form>

        <div style="display:flex; align-items:center; justify-content:space-between;">
            <p style="font-size:.79rem; color:var(--gris-texte); font-weight:600;">
                <?php echo count($offres); ?> offre<?php echo count($offres) > 1 ? 's' : ''; ?> trouvée<?php echo count($offres) > 1 ? 's' : ''; ?>
            </p>
            <?php if ($q || $secteur || $ville) : ?>
            <a href="offres_tuteur.php" style="font-size:.77rem; color:var(--bleu-clair); font-weight:600; text-decoration:none;">✕ Effacer</a>
            <?php endif; ?>
        </div>

        <?php if (empty($offres)) : ?>
        <div class="etat-vide">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 0 0-4 0v2"/>
            </svg>
            <h3>Aucune offre disponible</h3>
            <p>Modifie les critères de recherche ou reviens plus tard.</p>
        </div>

        <?php else : ?>
        <?php foreach ($offres as $o) :
            $desc_courte = mb_strlen($o['mission'] ?? '') > 115 ? mb_substr($o['mission'], 0, 115) . '…' : ($o['mission'] ?? '');
            $duree = $o['duree_semaines'] ? round($o['duree_semaines'] / 4) . ' mois' : '';
            $techs = array_slice(array_filter(array_map('trim', explode(',', $o['competences'] ?? ''))), 0, 3);
        ?>

        <div class="carte-offre">
            <div class="offre-entete">
                <div>
                    <p class="offre-titre"><?php echo htmlspecialchars($o['titre']); ?></p>
                    <p class="offre-sous">
                        <?php echo htmlspecialchars($o['nom_entreprise']); ?>
                        <?php if ($o['ville']) : ?> — <?php echo htmlspecialchars($o['ville']); ?><?php endif; ?>
                    </p>
                </div>
                <!-- Bouton affecter -->
                <button class="btn-affecter"
                        onclick="ouvrirModal(<?php echo (int)$o['num_offre']; ?>, '<?php echo htmlspecialchars($o['titre'], ENT_QUOTES); ?>')">
                    Affecter
                </button>
            </div>

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

            <?php if ($desc_courte) : ?>
            <p class="offre-desc"><?php echo htmlspecialchars($desc_courte); ?></p>
            <?php endif; ?>

            <div class="offre-pied">
                <div class="offre-meta">
                    <?php if ($o['ville']) : ?>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php echo htmlspecialchars($o['ville']); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($o['date_debut']) : ?>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                        <?php echo date('d/m/Y', strtotime($o['date_debut'])); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<!-- Modal d'affectation -->
<div id="modal-bg" class="modal-affecter">
    <div class="modal-inner">
        <div class="modal-handle"></div>
        <p id="modal-titre" style="font-family:'Syne',sans-serif; font-weight:700; font-size:.97rem; margin-bottom:13px;"></p>
        <form method="POST" action="offres_tuteur.php">
            <input type="hidden" name="num_offre" id="modal-offre">
            <label style="font-size:.80rem; font-weight:600; color:var(--gris-texte);">Sélectionner un étudiant</label>
            <select name="id_etudiant" class="select-field" required>
                <option value="">-- Choisir un étudiant --</option>
                <?php foreach ($etudiants as $e) : ?>
                <option value="<?php echo (int)$e['id']; ?>"><?php echo htmlspecialchars($e['nom_complet']); ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex; gap:9px; margin-top:14px;">
                <button type="submit" class="btn" style="flex:1;">Affecter l'étudiant</button>
                <button type="button" onclick="fermerModal()"
                        style="flex:1; padding:10px; border:1px solid var(--gris-border); border-radius:8px; background:transparent; font-family:'DM Sans',sans-serif; font-weight:600; font-size:.87rem; cursor:pointer; color:var(--gris-texte);">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    var delai;
    document.getElementById('champ-q').addEventListener('input', function () {
        clearTimeout(delai);
        delai = setTimeout(function () {
            document.getElementById('form-recherche').submit();
        }, 1000);
    });

    function ouvrirModal(id, titre) {
        document.getElementById('modal-offre').value = id;
        document.getElementById('modal-titre').textContent = 'Affecter à : ' + titre;
        document.getElementById('modal-bg').style.display = 'flex';
    }

    function fermerModal() {
        document.getElementById('modal-bg').style.display = 'none';
    }

    document.getElementById('modal-bg').addEventListener('click', function (e) {
        if (e.target === this) fermerModal();
    });
</script>
</body>
</html>