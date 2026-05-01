<?php
session_start();

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'Entreprise') {
    header("Location: ../../public/login.php?erreur=4");
    exit;
}

$idEntreprise = (int)$_SESSION['id'];
$msgOk = '';
$msgErr = '';
$confirmData = null;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function formatHistoriqueStatut(string $statut): array {
    $map = [
        'acceptee_entreprise' => ['En attente étudiant', 'bg-warning text-dark'],
        'confirmee_etudiant'  => ['Confirmée', 'bg-success text-white'],
        'refusee_entreprise'  => ['Refusée par vous', 'bg-danger text-white'],
        'refusee_etudiant'    => ["Refusée par l'étudiant", 'bg-danger text-white'],
    ];
    return $map[$statut] ?? [$statut, 'bg-secondary text-white'];
}

try {
    $conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
    if (!$conn) throw new Exception("Connexion DB échouée.");
    mysqli_set_charset($conn, 'utf8mb4');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'demander_confirmation') {
        $numStage = (int)($_POST['num_stage'] ?? 0);
        $stmt = mysqli_prepare($conn, "
            SELECT s.num_stage, s.titre, s.convention_validee,
                CONCAT(u.prenom, ' ', u.nom) AS nom_etudiant,
                u.filiere, u.niveau, u.email AS email_etudiant,
                o.duree_semaines, o.date_debut, o.mission
            FROM Stage s JOIN Utilisateur u ON u.id = s.id_etudiant
            LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
            WHERE s.num_stage = ? AND s.id_entreprise = ? AND s.statut_candidature = 'en_attente'
        ");
        mysqli_stmt_bind_param($stmt, 'ii', $numStage, $idEntreprise);
        mysqli_stmt_execute($stmt);
        $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$data) { $msgErr = "Candidature introuvable ou déjà traitée."; }
        elseif ((int)($data['convention_validee'] ?? 0) !== 1) { $msgErr = "Vous n'avez pas encore validé la convention."; }
        else { $confirmData = $data; }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'valider_convention') {
        $numStage = (int)($_POST['num_stage'] ?? 0);
        $stmt = mysqli_prepare($conn, "SELECT s.num_stage, s.id_etudiant, s.titre FROM Stage s WHERE s.num_stage = ? AND s.id_entreprise = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $numStage, $idEntreprise);
        mysqli_stmt_execute($stmt);
        $cand = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$cand) { $msgErr = "Candidature introuvable."; }
        else {
            $sd = mysqli_prepare($conn, "SELECT id FROM DocumentCandidature WHERE num_stage = ? AND type_document = 'convention_stage' LIMIT 1");
            mysqli_stmt_bind_param($sd, 'i', $numStage);
            mysqli_stmt_execute($sd);
            $doc = mysqli_fetch_assoc(mysqli_stmt_get_result($sd));
            mysqli_stmt_close($sd);
            if (!$doc) { $msgErr = "Aucune convention n'a encore été envoyée par l'étudiant."; }
            else {
                $upd = mysqli_prepare($conn, "UPDATE Stage SET convention_validee = 1 WHERE num_stage = ? AND id_entreprise = ?");
                mysqli_stmt_bind_param($upd, 'ii', $numStage, $idEntreprise);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);
                $titreNotif = "Convention validée — " . $cand['titre'];
                $messageNotif = ($_SESSION['nom_entreprise'] ?? "L'entreprise") . " a validé votre convention pour \"" . $cand['titre'] . "\".";
                $lienNotif = "candidatures_etudiant.php";
                $insNotif = mysqli_prepare($conn, "INSERT INTO Notification (id_user, type, titre, message, lien) VALUES (?, 'autre', ?, ?, ?)");
                mysqli_stmt_bind_param($insNotif, 'isss', $cand['id_etudiant'], $titreNotif, $messageNotif, $lienNotif);
                mysqli_stmt_execute($insNotif);
                mysqli_stmt_close($insNotif);
                $msgOk = "La convention a bien été validée.";
            }
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'refuser') {
        $numStage = (int)($_POST['num_stage'] ?? 0);
        $motif = trim($_POST['motif'] ?? '');
        $stmt = mysqli_prepare($conn, "SELECT s.id_etudiant, s.titre FROM Stage s WHERE s.num_stage = ? AND s.id_entreprise = ? AND s.statut_candidature = 'en_attente'");
        mysqli_stmt_bind_param($stmt, 'ii', $numStage, $idEntreprise);
        mysqli_stmt_execute($stmt);
        $cand = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($cand) {
            $upd = mysqli_prepare($conn, "UPDATE Stage SET statut_candidature = 'refusee_entreprise', statut = 'annule' WHERE num_stage = ?");
            mysqli_stmt_bind_param($upd, 'i', $numStage);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $nomEnt = $_SESSION['nom_entreprise'] ?? "L'entreprise";
            $titreNotif = "Candidature refusée — " . $cand['titre'];
            $messageNotif = $nomEnt . " n'a pas retenu votre candidature pour \"" . $cand['titre'] . "\"." . ($motif ? " Motif : " . $motif : "");
            $insNotif = mysqli_prepare($conn, "INSERT INTO Notification (id_user, type, titre, message) VALUES (?, 'candidature_refusee', ?, ?)");
            mysqli_stmt_bind_param($insNotif, 'iss', $cand['id_etudiant'], $titreNotif, $messageNotif);
            mysqli_stmt_execute($insNotif);
            mysqli_stmt_close($insNotif);
            $msgOk = "Candidature refusée. L'étudiant a été notifié.";
        } else { $msgErr = "Candidature introuvable."; }
    }

    $candidatures = [];
    $stmt = mysqli_prepare($conn, "
        SELECT s.num_stage, s.titre, CONCAT(u.prenom, ' ', u.nom) AS nom_etudiant,
            u.filiere, u.niveau, u.email AS email_etudiant,
            o.date_debut, o.duree_semaines, s.statut_candidature, s.convention_validee
        FROM Stage s JOIN Utilisateur u ON u.id = s.id_etudiant
        LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
        WHERE s.id_entreprise = ? AND s.statut_candidature = 'en_attente'
        ORDER BY s.num_stage DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $idEntreprise);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $row['documents'] = [];
        $sd = mysqli_prepare($conn, "SELECT id, type_document, nom_fichier, chemin_fichier, date_envoi FROM DocumentCandidature WHERE num_stage = ? ORDER BY date_envoi DESC, id DESC");
        mysqli_stmt_bind_param($sd, 'i', $row['num_stage']);
        mysqli_stmt_execute($sd);
        $rd = mysqli_stmt_get_result($sd);
        while ($doc = mysqli_fetch_assoc($rd)) $row['documents'][] = $doc;
        mysqli_stmt_close($sd);
        $candidatures[] = $row;
    }
    mysqli_stmt_close($stmt);

    $historique = [];
    $sh = mysqli_prepare($conn, "
        SELECT s.titre, s.statut_candidature, CONCAT(u.prenom, ' ', u.nom) AS nom_etudiant
        FROM Stage s JOIN Utilisateur u ON u.id = s.id_etudiant
        WHERE s.id_entreprise = ? AND s.statut_candidature != 'en_attente'
        ORDER BY s.num_stage DESC LIMIT 12
    ");
    mysqli_stmt_bind_param($sh, 'i', $idEntreprise);
    mysqli_stmt_execute($sh);
    $rh = mysqli_stmt_get_result($sh);
    while ($row = mysqli_fetch_assoc($rh)) $historique[] = $row;
    mysqli_stmt_close($sh);
    mysqli_close($conn);

} catch (Exception $e) {
    $msgErr = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des candidatures — CY Stage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bleu: #1B4F9B;
            --bleu-clair: #2563c7;
            --bs-primary: #1B4F9B;
            --bs-primary-rgb: 27,79,155;
        }
        body { font-family: 'DM Sans', sans-serif; background: #f4f6fb; }

        /* Navbar */
        .navbar-cy { background: linear-gradient(135deg, #1B4F9B, #2563c7); }

        /* Cards */
        .card-cy {
            border: 1px solid rgba(171,186,205,.4);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(27,79,155,.08);
            background: #fff;
        }

        /* Avatar */
        .avatar-initiales {
            width: 46px; height: 46px; border-radius: 50%;
            background: linear-gradient(135deg, #1B4F9B, #2563c7);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif;
            font-weight: 800; font-size: .95rem; flex-shrink: 0;
        }

        /* Section label */
        .section-label {
            font-size: .72rem; font-weight: 800; letter-spacing: .14em;
            text-transform: uppercase; color: var(--bleu); margin-bottom: 12px;
        }

        /* Doc row */
        .doc-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 14px; border: 1px solid #e5e7eb;
            border-radius: 12px; background: #fbfdff;
        }
        .doc-row + .doc-row { margin-top: 8px; }

        /* Count badge */
        .count-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 22px; height: 22px; padding: 0 7px;
            border-radius: 999px; background: #d97706; color: #fff;
            font-size: .72rem; font-weight: 700; margin-left: 6px;
        }

        /* Convention badge inline */
        .badge-convention {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 10px; border-radius: 999px;
            background: #ecfdf3; color: #16a34a;
            border: 1px solid #b7ebc6; font-size: .76rem; font-weight: 700;
        }

        /* Hist row */
        .hist-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0; border-bottom: 1px solid #e5e7eb;
        }
        .hist-row:last-child { border-bottom: none; }

        /* Modal overlay */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(17,24,39,.48);
            display: none; align-items: center; justify-content: center;
            padding: 20px; z-index: 1050;
        }
        .modal-overlay.show { display: flex; }
        .modal-inner {
            width: 100%; max-width: 520px; background: #fff;
            border-radius: 22px; padding: 28px;
            box-shadow: 0 20px 60px rgba(17,24,39,.2);
        }
        .recap-ligne {
            display: flex; gap: 10px; padding: 8px 0;
            border-bottom: 1px solid #e5e7eb; font-size: .88rem;
        }
        .recap-ligne:last-child { border-bottom: none; }
        .recap-ligne strong { width: 110px; flex-shrink: 0; color: #374151; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-cy shadow-sm mb-4">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a class="navbar-brand" href="accueil_entreprise.php">
            <img src="../../public/assets/img/logo.png" alt="CY Stage" height="36">
        </a>
        <span class="fw-bold text-white">
            <i class="bi bi-building me-2"></i>
            <?php echo h($_SESSION['nom_entreprise'] ?? 'Entreprise'); ?>
        </span>
        <a href="deconnexion.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
        </a>
    </div>
</nav>

<div class="container" style="max-width:900px;">

    <!-- En-tête page -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="accueil_entreprise.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h1 class="h4 mb-0 fw-bold" style="color:var(--bleu); font-family:'Syne',sans-serif;">Gestion des candidatures</h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">Traitez et suivez les candidatures reçues</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($msgOk): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo h($msgOk); ?>
        </div>
    <?php endif; ?>
    <?php if ($msgErr): ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo h($msgErr); ?>
        </div>
    <?php endif; ?>

    <!-- Section candidatures en attente -->
    <div class="section-label">
        Candidatures en attente
        <?php if (!empty($candidatures)): ?>
            <span class="count-badge"><?php echo count($candidatures); ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($candidatures)): ?>
        <div class="card-cy p-5 text-center mb-4">
            <i class="bi bi-inbox fs-1 text-muted opacity-50 mb-3 d-block"></i>
            <h5 class="fw-bold mb-1" style="color:var(--bleu);">Aucune candidature en attente</h5>
            <p class="text-muted mb-0">Les nouvelles candidatures apparaîtront ici.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3 mb-4">
            <?php foreach ($candidatures as $c):
                $noms = explode(' ', trim($c['nom_etudiant'] ?? ''));
                $initiales = strtoupper(mb_substr($noms[0] ?? '?', 0, 1) . mb_substr($noms[1] ?? '', 0, 1));
                $dateFmt = !empty($c['date_debut']) ? date('d/m/Y', strtotime($c['date_debut'])) : 'Non précisé';
            ?>
                <div class="card-cy p-4">

                    <!-- Identité -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="avatar-initiales"><?php echo h($initiales); ?></div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1 fw-bold" style="font-family:'Syne',sans-serif;"><?php echo h($c['nom_etudiant']); ?></h5>
                            <p class="text-muted mb-1" style="font-size:.83rem;">
                                <?php echo h(implode(' · ', array_filter([$c['filiere'] ?? '', $c['niveau'] ?? '', $c['email_etudiant'] ?? '']))); ?>
                            </p>
                            <span style="color:var(--bleu-clair); font-size:.85rem; font-weight:700;"><?php echo h($c['titre']); ?></span>
                        </div>
                    </div>

                    <!-- Meta infos -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:.76rem;">
                            <i class="bi bi-calendar3 me-1"></i> Début : <?php echo h($dateFmt); ?>
                        </span>
                        <?php if (!empty($c['duree_semaines'])): ?>
                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:.76rem;">
                            <i class="bi bi-clock me-1"></i> <?php echo (int)$c['duree_semaines']; ?> semaines
                        </span>
                        <?php endif; ?>
                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:.76rem;">
                            <i class="bi bi-hash me-1"></i> N° <?php echo (int)$c['num_stage']; ?>
                        </span>
                        <?php if ((int)($c['convention_validee'] ?? 0) === 1): ?>
                        <span class="badge-convention">
                            <i class="bi bi-check-circle-fill"></i> Convention validée
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Documents -->
                    <div class="mb-3">
                        <p class="fw-semibold mb-2" style="font-size:.85rem; color:var(--bleu);">
                            <i class="bi bi-paperclip me-1"></i> Documents transmis
                        </p>
                        <?php if (empty($c['documents'])): ?>
                            <p class="text-muted mb-0" style="font-size:.84rem;">Aucun document déposé pour l'instant.</p>
                        <?php else: ?>
                            <?php foreach ($c['documents'] as $doc): ?>
                            <div class="doc-row">
                                <div>
                                    <span class="fw-semibold" style="font-size:.85rem;">
                                        <?php echo h(str_replace('_', ' ', $doc['type_document'])); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo h($doc['nom_fichier']); ?> · <?php echo h(date('d/m/Y H:i', strtotime($doc['date_envoi']))); ?></small>
                                </div>
                                <a href="<?php echo h($doc['chemin_fichier']); ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill">
                                    <i class="bi bi-eye me-1"></i> Ouvrir
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                        <form method="POST" class="flex-fill">
                            <input type="hidden" name="action" value="demander_confirmation">
                            <input type="hidden" name="num_stage" value="<?php echo (int)$c['num_stage']; ?>">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill" style="background:var(--bleu); border-color:var(--bleu);">
                                <i class="bi bi-check-lg me-1"></i> Valider la candidature
                            </button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="action" value="valider_convention">
                            <input type="hidden" name="num_stage" value="<?php echo (int)$c['num_stage']; ?>">
                            <button type="submit" class="btn btn-outline-success rounded-pill">
                                <i class="bi bi-file-earmark-check me-1"></i> Valider convention
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger rounded-pill"
                            onclick="ouvrirRefus(<?php echo (int)$c['num_stage']; ?>, '<?php echo h($c['nom_etudiant']); ?>')">
                            <i class="bi bi-x-lg me-1"></i> Refuser
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Historique -->
    <?php if (!empty($historique)): ?>
        <div class="section-label">Historique récent</div>
        <div class="card-cy p-3 mb-5">
            <?php foreach ($historique as $histo):
                [$lbl, $cls] = formatHistoriqueStatut($histo['statut_candidature'] ?? '');
            ?>
            <div class="hist-row">
                <div>
                    <span class="fw-bold" style="font-size:.9rem;"><?php echo h($histo['nom_etudiant']); ?></span>
                    <br>
                    <span class="text-muted" style="font-size:.82rem;"><?php echo h($histo['titre']); ?></span>
                </div>
                <span class="badge <?php echo h($cls); ?> rounded-pill" style="font-size:.74rem;"><?php echo h($lbl); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Modal confirmation candidature -->
<?php if ($confirmData): ?>
<div class="modal-overlay show" id="overlay-confirm">
    <div class="modal-inner">
        <h5 class="fw-bold mb-2" style="color:var(--bleu); font-family:'Syne',sans-serif;">
            <i class="bi bi-check-circle me-2"></i> Valider la candidature
        </h5>
        <p class="text-muted mb-3" style="font-size:.88rem;">
            Vous allez accepter la candidature de <strong><?php echo h($confirmData['nom_etudiant']); ?></strong>.
            L'étudiant devra ensuite confirmer ou refuser.
        </p>
        <div class="bg-light rounded-3 p-3 mb-3">
            <div class="recap-ligne"><strong>Candidat</strong><span><?php echo h($confirmData['nom_etudiant']); ?></span></div>
            <div class="recap-ligne"><strong>Filière</strong><span><?php echo h(implode(' · ', array_filter([$confirmData['filiere'] ?? '', $confirmData['niveau'] ?? '']))); ?></span></div>
            <div class="recap-ligne"><strong>Poste</strong><span><?php echo h($confirmData['titre']); ?></span></div>
            <div class="recap-ligne"><strong>Début</strong><span><?php echo !empty($confirmData['date_debut']) ? h(date('d/m/Y', strtotime($confirmData['date_debut']))) : 'Non précisé'; ?></span></div>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" class="flex-fill">
                <input type="hidden" name="action" value="confirmer_validation">
                <input type="hidden" name="num_stage" value="<?php echo (int)$confirmData['num_stage']; ?>">
                <button type="submit" class="btn btn-primary w-100 rounded-pill" style="background:var(--bleu); border-color:var(--bleu);">Confirmer</button>
            </form>
            <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="document.getElementById('overlay-confirm').remove()">Annuler</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal refus -->
<div class="modal-overlay" id="overlay-refus">
    <div class="modal-inner">
        <h5 class="fw-bold mb-2" style="color:#dc2626; font-family:'Syne',sans-serif;">
            <i class="bi bi-x-circle me-2"></i> <span id="titre-refus">Refuser la candidature</span>
        </h5>
        <p class="text-muted mb-3" style="font-size:.88rem;">L'étudiant sera notifié du refus. Vous pouvez ajouter un motif facultatif.</p>
        <form method="POST">
            <input type="hidden" name="action" value="refuser">
            <input type="hidden" name="num_stage" id="refus-num-stage">
            <textarea name="motif" class="form-control rounded-3 mb-3" rows="3" placeholder="Motif du refus (optionnel)"></textarea>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger rounded-pill flex-fill">Confirmer le refus</button>
                <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="fermerRefus()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function ouvrirRefus(numStage, nomEtudiant) {
    document.getElementById('refus-num-stage').value = numStage;
    document.getElementById('titre-refus').textContent = 'Refuser ' + nomEtudiant;
    document.getElementById('overlay-refus').classList.add('show');
}
function fermerRefus() {
    document.getElementById('overlay-refus').classList.remove('show');
}
document.getElementById('overlay-refus').addEventListener('click', function(e) {
    if (e.target === this) fermerRefus();
});
</script>
</body>
</html>