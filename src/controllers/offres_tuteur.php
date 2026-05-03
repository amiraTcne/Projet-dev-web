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
    while ($e = mysqli_fetch_assoc($re)) $etudiants[] = $e;

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

    $rs = mysqli_query($conn, "SELECT DISTINCT u.secteur FROM Offre_Stage o JOIN Utilisateur u ON u.id = o.id_entreprise WHERE o.statut = 'ouverte' AND u.secteur IS NOT NULL ORDER BY u.secteur");
    while ($s = mysqli_fetch_row($rs)) $secteurs[] = $s[0];

    $rv = mysqli_query($conn, "SELECT DISTINCT u.ville FROM Offre_Stage o JOIN Utilisateur u ON u.id = o.id_entreprise WHERE o.statut = 'ouverte' AND u.ville IS NOT NULL ORDER BY u.ville");
    while ($v = mysqli_fetch_row($rv)) $villes[] = $v[0];

    mysqli_close($conn);
}
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres de Stage — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bleu: #1B4F9B; --bleu-clair: #2563c7; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }
        .card-offre { border: 1px solid rgba(171,186,205,.4); border-radius: 18px; background: #fff; padding: 1.5rem; transition: transform 0.2s, box-shadow 0.2s; }
        .card-offre:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(27,79,155,.08); border-color: var(--bleu-clair); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="accueil_tuteur.php"><img src="../../public/assets/img/logo.png" alt="CY Stage" height="36"></a>
        <div class="ms-auto d-flex align-items-center">
            <span class="fw-bold text-white me-3 d-none d-sm-inline"><i class="bi bi-person-workspace me-2"></i> <?php echo h($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <a href="deconnexion.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right d-sm-none"></i><span class="d-none d-sm-inline">Déconnexion</span></a>
        </div>
    </div>
</nav>

<div class="container mb-5" style="max-width:1000px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_tuteur.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-chevron-left"></i></a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Offres de Stages</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Parcourez les offres et affectez vos étudiants</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msg_ok) : ?><div class="alert alert-success rounded-4"><i class="bi bi-check-circle-fill"></i> <strong><?php echo h($msg_ok); ?></strong></div><?php endif; ?>
    <?php if ($msg_err) : ?><div class="alert alert-danger rounded-4"><i class="bi bi-exclamation-triangle-fill"></i> <strong><?php echo h($msg_err); ?></strong></div><?php endif; ?>

    <!-- Recherche et Filtres -->
    <div class="bg-white p-4 rounded-4 border mb-4 shadow-sm">
        <form method="GET" id="form-recherche">
            <div class="input-group mb-3">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" id="champ-q" class="form-control bg-light border-start-0" placeholder="Rechercher un poste ou une entreprise…" value="<?php echo h($q); ?>" autocomplete="off">
            </div>

            <?php if (!empty($secteurs)) : ?>
            <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                <span class="text-muted fw-bold" style="font-size: .8rem;"><i class="bi bi-briefcase me-1"></i> Secteur :</span>
                <a href="offres_tuteur.php?<?php echo http_build_query(array_merge($_GET, ['secteur' => ''])); ?>" class="badge rounded-pill text-decoration-none <?php echo $secteur === '' ? 'bg-primary' : 'bg-light text-dark border'; ?>">Tous</a>
                <?php foreach ($secteurs as $s) : ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['secteur' => $s])); ?>" class="badge rounded-pill text-decoration-none <?php echo $secteur === $s ? 'bg-primary' : 'bg-light text-dark border'; ?>"><?php echo h($s); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($villes)) : ?>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted fw-bold" style="font-size: .8rem;"><i class="bi bi-geo-alt me-1"></i> Ville :</span>
                <a href="offres_tuteur.php?<?php echo http_build_query(array_merge($_GET, ['ville' => ''])); ?>" class="badge rounded-pill text-decoration-none <?php echo $ville === '' ? 'bg-primary' : 'bg-light text-dark border'; ?>">Toutes villes</a>
                <?php foreach ($villes as $v) : ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['ville' => $v])); ?>" class="badge rounded-pill text-decoration-none <?php echo $ville === $v ? 'bg-primary' : 'bg-light text-dark border'; ?>"><?php echo h($v); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- En-tête des résultats -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-ul me-2"></i> <?php echo count($offres); ?> offre(s) trouvée(s)</h5>
        <?php if ($q || $secteur || $ville) : ?>
            <a href="offres_tuteur.php" class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-x-circle me-1"></i> Réinitialiser les filtres</a>
        <?php endif; ?>
    </div>

    <!-- Liste des offres -->
    <?php if (empty($offres)) : ?>
        <div class="text-center p-5 bg-white rounded-4 border" style="border-style: dashed !important;">
            <i class="bi bi-folder-x text-muted opacity-50 mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">Aucune offre disponible</h5>
            <p class="text-muted mb-0">Essayez de modifier vos critères de recherche.</p>
        </div>
    <?php else : ?>
        <div class="row g-4">
            <?php foreach ($offres as $o) :
                $desc_courte = mb_strlen($o['mission'] ?? '') > 150 ? mb_substr($o['mission'], 0, 150) . '...' : ($o['mission'] ?? '');
                $duree = $o['duree_semaines'] ? round($o['duree_semaines'] / 4) . ' mois' : '';
                $techs = array_slice(array_filter(array_map('trim', explode(',', $o['competences'] ?? ''))), 0, 3);
            ?>
            <div class="col-md-6">
                <div class="card-offre h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color:var(--bleu); font-family:'Syne',sans-serif;"><?php echo h($o['titre']); ?></h5>
                            <p class="text-muted fw-semibold mb-2" style="font-size:.85rem;"><i class="bi bi-building me-1"></i> <?php echo h($o['nom_entreprise']); ?></p>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php if ($o['filiere_ciblee']) : ?><span class="badge bg-primary rounded-pill"><?php echo h($o['filiere_ciblee']); ?></span><?php endif; ?>
                        <?php if ($duree) : ?><span class="badge bg-success rounded-pill"><i class="bi bi-clock-history"></i> <?php echo $duree; ?></span><?php endif; ?>
                        <?php foreach ($techs as $t) : ?><span class="badge bg-light text-dark border rounded-pill"><?php echo h($t); ?></span><?php endforeach; ?>
                    </div>

                    <p class="text-secondary mb-4 flex-grow-1" style="font-size:.85rem; line-height: 1.5;"><?php echo h($desc_courte); ?></p>

                    <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-auto">
                        <div class="text-muted" style="font-size:.75rem; font-weight:600;">
                            <?php if ($o['ville']) : ?><span class="me-3"><i class="bi bi-geo-alt-fill"></i> <?php echo h($o['ville']); ?></span><?php endif; ?>
                            <?php if ($o['date_debut']) : ?><span><i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y', strtotime($o['date_debut'])); ?></span><?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" style="background:var(--bleu); border:none;" onclick="ouvrirModal(<?php echo (int)$o['num_offre']; ?>, '<?php echo h($o['titre'], ENT_QUOTES); ?>')">
                            <i class="bi bi-person-plus-fill me-1"></i> Affecter
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modale Bootstrap d'affectation -->
<div class="modal fade" id="affecterModal" tabindex="-1" aria-labelledby="modal-titre" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="modal-titre" style="color:var(--bleu); font-family:'Syne',sans-serif;"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3 pb-4">
        <form method="POST" action="offres_tuteur.php">
            <input type="hidden" name="num_offre" id="modal-offre">
            <div class="mb-4">
                <label class="form-label fw-bold text-dark">Sélectionner un étudiant de votre liste</label>
                <select name="id_etudiant" class="form-select bg-light" required>
                    <option value="">-- Choisir un étudiant --</option>
                    <?php foreach ($etudiants as $e) : ?>
                        <option value="<?php echo (int)$e['id']; ?>"><?php echo h($e['nom_complet']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light border fw-bold flex-grow-1 rounded-pill text-muted" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary fw-bold flex-grow-1 rounded-pill" style="background:var(--bleu); border:none;">Confirmer l'affectation</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Soumission automatique de la recherche avec délai
    let delai;
    document.getElementById('champ-q').addEventListener('input', function () {
        clearTimeout(delai);
        delai = setTimeout(function () { document.getElementById('form-recherche').submit(); }, 1000);
    });

    // Gestion de la modale Bootstrap 5
    const modalAffecter = new bootstrap.Modal(document.getElementById('affecterModal'));
    function ouvrirModal(id, titre) {
        document.getElementById('modal-offre').value = id;
        document.getElementById('modal-titre').textContent = 'Affecter à : ' + titre;
        modalAffecter.show();
    }
</script>
</body>
</html>