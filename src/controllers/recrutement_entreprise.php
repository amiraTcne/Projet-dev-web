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

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function formatHistoriqueStatut(string $statut): array
{
    $map = [
        'acceptee_entreprise' => ['En attente de confirmation étudiant', 'badge-orange'],       
        'confirmee_etudiant' => ['Confirmée', 'badge-vert'],
        'refusee_entreprise' => ['Refusée par vous', 'badge-rouge'],
        'refusee_etudiant' => ['Refusée par l’étudiant', 'badge-rouge'],
    ];

    return $map[$statut] ?? [$statut, 'badge-bleu'];
}

try {
    $conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
    if (!$conn) {
        throw new Exception("Connexion DB échouée.");
    }
    mysqli_set_charset($conn, 'utf8mb4');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'demander_confirmation') {
        $numStage = (int)($_POST['num_stage'] ?? 0);

        $stmt = mysqli_prepare($conn, "
            SELECT
                s.num_stage,
                s.titre,
                CONCAT(u.prenom, ' ', u.nom) AS nom_etudiant,
                u.filiere,
                u.niveau,
                u.email AS email_etudiant,
                o.duree_semaines,
                o.date_debut,
                o.mission
            FROM Stage s
            JOIN Utilisateur u ON u.id = s.id_etudiant
            LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
            WHERE s.num_stage = ?
              AND s.id_entreprise = ?
              AND s.statut_candidature = 'en_attente'
        ");
        mysqli_stmt_bind_param($stmt, 'ii', $numStage, $idEntreprise);
        mysqli_stmt_execute($stmt);
        $confirmData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$confirmData) {
            $msgErr = "Candidature introuvable ou déjà traitée.";
        }
    }

    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirmer_validation') {
        $numStage = (int)($_POST['num_stage'] ?? 0);

        mysqli_begin_transaction($conn);

        $stmt = mysqli_prepare($conn, "
            SELECT
                s.num_stage,
                s.id_etudiant,
                s.num_offre,
                s.titre,
                o.date_debut,
                o.duree_semaines
            FROM Stage s
            LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
            WHERE s.num_stage = ?
              AND s.id_entreprise = ?
              AND s.statut_candidature = 'en_attente'
        ");
        mysqli_stmt_bind_param($stmt, 'ii', $numStage, $idEntreprise);
        mysqli_stmt_execute($stmt);
        $cand = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$cand) {
            mysqli_rollback($conn);
            $msgErr = "Candidature introuvable ou déjà traitée.";
        } else {
            $idEtudiant = (int)$cand['id_etudiant'];
            $dateDebutOffre = $cand['date_debut'] ?? null;
            $duree = (int)($cand['duree_semaines'] ?? 0);
            $dateFinOffre = $dateDebutOffre ? date('Y-m-d', strtotime($dateDebutOffre . ' +' . $duree . ' weeks')) : null;

            $conflit = false;
            if ($dateDebutOffre) {
                $sc = mysqli_prepare($conn, "
                    SELECT COUNT(*)
                    FROM Stage
                    WHERE id_etudiant = ?
                      AND num_stage != ?
                      AND statut IN ('en_cours','en_attente')
                      AND statut_candidature IN ('confirmee_etudiant','acceptee_entreprise')
                      AND date_debut IS NOT NULL
                      AND date_debut <= ?
                      AND (date_fin IS NULL OR date_fin >= ?)
                ");
                mysqli_stmt_bind_param($sc, 'iiss', $idEtudiant, $numStage, $dateFinOffre, $dateDebutOffre);
                mysqli_stmt_execute($sc);
                mysqli_stmt_bind_result($sc, $nbConflits);
                mysqli_stmt_fetch($sc);
                mysqli_stmt_close($sc);
                $conflit = ((int)$nbConflits > 0);
            }

            if ($conflit) {
                mysqli_rollback($conn);
                $msgErr = "Impossible de valider : cet étudiant a déjà un stage prévu sur cette période.";
            } else {
                $upd = mysqli_prepare($conn, "
                    UPDATE Stage
                    SET statut_candidature = 'acceptee_entreprise'
                    WHERE num_stage = ? AND id_entreprise = ?
                ");
                mysqli_stmt_bind_param($upd, 'ii', $numStage, $idEntreprise);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);

                $nomEnt = $_SESSION['nom_entreprise'] ?? 'L’entreprise';
                $titreNotif = "Candidature acceptée — " . $cand['titre'];
                $messageNotif = $nomEnt . " a accepté votre candidature pour le poste \"" . $cand['titre'] . "\". Rendez-vous dans votre espace pour confirmer ou refuser cette offre.";
                $lienNotif = "candidatures_etudiant.php";

                $insNotif = mysqli_prepare($conn, "
                    INSERT INTO Notification (id_user, type, titre, message, lien)
                    VALUES (?, 'candidature_validee', ?, ?, ?)
                ");
                mysqli_stmt_bind_param($insNotif, 'isss', $idEtudiant, $titreNotif, $messageNotif, $lienNotif);
                mysqli_stmt_execute($insNotif);
                mysqli_stmt_close($insNotif);

                mysqli_commit($conn);
                $msgOk = "Candidature validée. L’étudiant a été notifié.";
            }
        }
    }

    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'refuser') {
        $numStage = (int)($_POST['num_stage'] ?? 0);
        $motif = trim($_POST['motif'] ?? '');

        $stmt = mysqli_prepare($conn, "
            SELECT s.id_etudiant, s.titre
            FROM Stage s
            WHERE s.num_stage = ?
              AND s.id_entreprise = ?
              AND s.statut_candidature = 'en_attente'
        ");
        mysqli_stmt_bind_param($stmt, 'ii', $numStage, $idEntreprise);
        mysqli_stmt_execute($stmt);
        $cand = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($cand) {
            $upd = mysqli_prepare($conn, "
                UPDATE Stage
                SET statut_candidature = 'refusee_entreprise',
                    statut = 'annule'
                WHERE num_stage = ?
            ");
            mysqli_stmt_bind_param($upd, 'i', $numStage);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $nomEnt = $_SESSION['nom_entreprise'] ?? 'L’entreprise';
            $titreNotif = "Candidature refusée — " . $cand['titre'];
            $messageNotif = $nomEnt . " n'a pas retenu votre candidature pour le poste \"" . $cand['titre'] . "\"." . ($motif ? " Motif : " . $motif : " Aucun motif précisé.");

            $insNotif = mysqli_prepare($conn, "
                INSERT INTO Notification (id_user, type, titre, message)
                VALUES (?, 'candidature_refusee', ?, ?)
            ");
            mysqli_stmt_bind_param($insNotif, 'iss', $cand['id_etudiant'], $titreNotif, $messageNotif);
            mysqli_stmt_execute($insNotif);
            mysqli_stmt_close($insNotif);

            $msgOk = "Candidature refusée. L’étudiant a été notifié.";
        } else {
            $msgErr = "Candidature introuvable.";
        }
    }

    $candidatures = [];
    $stmt = mysqli_prepare($conn, "
        SELECT
            s.num_stage,
            s.titre,
            CONCAT(u.prenom, ' ', u.nom) AS nom_etudiant,
            u.filiere,
            u.niveau,
            u.email AS email_etudiant,
            o.date_debut,
            o.duree_semaines,
            s.statut_candidature
        FROM Stage s
        JOIN Utilisateur u ON u.id = s.id_etudiant
        LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
        WHERE s.id_entreprise = ?
          AND s.statut_candidature = 'en_attente'
        ORDER BY s.num_stage DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $idEntreprise);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $row['documents'] = [];
        $sd = mysqli_prepare($conn, "
            SELECT id, type_document, nom_fichier, chemin_fichier, date_envoi
            FROM DocumentCandidature
            WHERE numstage = ?
            ORDER BY date_envoi DESC, id DESC
        ");
        mysqli_stmt_bind_param($sd, 'i', $row['num_stage']);
        mysqli_stmt_execute($sd);
        $rd = mysqli_stmt_get_result($sd);
        while ($doc = mysqli_fetch_assoc($rd)) {
            $row['documents'][] = $doc;
        }
        mysqli_stmt_close($sd);

        $candidatures[] = $row;
    }
    mysqli_stmt_close($stmt);

    $historique = [];
    $sh = mysqli_prepare($conn, "
        SELECT
            s.titre,
            s.statut_candidature,
            CONCAT(u.prenom, ' ', u.nom) AS nom_etudiant
        FROM Stage s
        JOIN Utilisateur u ON u.id = s.id_etudiant
        WHERE s.id_entreprise = ?
          AND s.statut_candidature != 'en_attente'
        ORDER BY s.num_stage DESC
        LIMIT 12
    ");
    mysqli_stmt_bind_param($sh, 'i', $idEntreprise);
    mysqli_stmt_execute($sh);
    $rh = mysqli_stmt_get_result($sh);
    while ($row = mysqli_fetch_assoc($rh)) {
        $historique[] = $row;
    }
    mysqli_stmt_close($sh);

    mysqli_close($conn);

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @mysqli_rollback($conn);
    }
    $msgErr = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des candidatures - CY Stage</title>
    <link rel="stylesheet" href="../../public/assets/css/styleetudiant.css">
    <style>
        :root{
            --bleu:#1B4F9B;
            --bleu-clair:#2563c7;
            --fond:#f5f7fb;
            --blanc:#ffffff;
            --texte:#111827;
            --muted:#6b7280;
            --bord:#e5e7eb;
            --vert:#16a34a;
            --vert-fond:#ecfdf3;
            --rouge:#dc2626;
            --rouge-fond:#fff1f2;
            --orange:#d97706;
            --shadow:0 10px 30px rgba(27,79,155,.08);
            --radius:18px;
        }
        *{box-sizing:border-box}
        body{margin:0;background:var(--fond);font-family:'DM Sans',sans-serif;color:var(--texte)}
        .page{max-width:1120px;margin:0 auto;padding:24px 18px 40px}
        .entete{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px}
        .retour{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid var(--bord);color:var(--bleu);text-decoration:none;box-shadow:0 2px 10px rgba(27,79,155,.06)}
        .retour svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round}
        .titre-zone h1{margin:0;font-size:1.45rem;color:var(--bleu)}
        .titre-zone p{margin:5px 0 0;color:var(--muted);font-size:.92rem}
        .alert{border-radius:14px;padding:13px 15px;margin-bottom:16px;font-size:.92rem;font-weight:600;border:1px solid transparent;background:#fff}
        .alert-ok{background:var(--vert-fond);color:var(--vert);border-color:#b7ebc6}
        .alert-err{background:var(--rouge-fond);color:var(--rouge);border-color:#fecaca}
        .section-label{margin:26px 0 12px;font-size:.8rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--bleu)}
        .count{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:24px;padding:0 8px;border-radius:999px;background:var(--orange);color:#fff;font-size:.74rem;margin-left:6px}
        .liste{display:grid;gap:16px}
        .carte{background:#fff;border:1px solid var(--bord);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px}
        .top{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
        .avatar{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--bleu),var(--bleu-clair));color:#fff;font-weight:800;flex-shrink:0}
        .identite{display:flex;gap:12px;align-items:flex-start}
        .nom{margin:0;font-size:1rem}
        .sub{margin:5px 0 0;color:var(--muted);font-size:.85rem}
        .poste{margin:4px 0 0;color:var(--bleu-clair);font-size:.86rem;font-weight:700}
        .meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
        .meta span{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;color:var(--muted);background:#f8fafc;border:1px solid #edf2f7;padding:7px 10px;border-radius:999px}
        .bloc{margin-top:16px;padding-top:16px;border-top:1px solid var(--bord)}
        .bloc h3{margin:0 0 10px;font-size:.92rem;color:var(--bleu)}
        .docs{display:grid;gap:8px}
        .doc{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--bord);border-radius:12px;background:#fbfdff}
        .doc small{display:block;color:var(--muted);margin-top:2px}
        .doc a{text-decoration:none;color:var(--bleu);font-weight:700;font-size:.84rem}
        .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
        .btn{border:none;border-radius:12px;padding:11px 15px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px}
        .btn-ok{background:var(--bleu);color:#fff}
        .btn-ko{background:#fff;color:var(--rouge);border:1px solid #fecaca}
        .btn-ko:hover{background:var(--rouge);color:#fff}
        .badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:.75rem;font-weight:700}
        .badge-vert{background:#ecfdf3;color:var(--vert)}
        .badge-orange{background:#fff7ed;color:var(--orange)}
        .badge-rouge{background:#fff1f2;color:var(--rouge)}
        .badge-bleu{background:#eff6ff;color:var(--bleu)}
        .etat-vide{background:#fff;border:1px dashed #cbd5e1;border-radius:20px;padding:40px 18px;text-align:center;color:var(--muted)}
        .etat-vide h3{margin:0 0 8px;color:var(--bleu)}
        .historique{background:#fff;border:1px solid var(--bord);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px 16px}
        .hist-ligne{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--bord)}
        .hist-ligne:last-child{border-bottom:none}
        .overlay{position:fixed;inset:0;background:rgba(17,24,39,.48);display:flex;align-items:center;justify-content:center;padding:20px;z-index:1000}
        .modal{width:100%;max-width:520px;background:#fff;border-radius:22px;padding:24px;box-shadow:0 20px 60px rgba(17,24,39,.25)}
        .modal h3{margin:0 0 8px;color:var(--bleu)}
        .modal p{margin:0 0 14px;color:var(--muted);line-height:1.55}
        .recap{background:#f8fafc;border:1px solid var(--bord);border-radius:14px;padding:14px;margin:16px 0}
        .recap-ligne{display:flex;gap:10px;padding:7px 0;border-bottom:1px solid var(--bord);font-size:.88rem}
        .recap-ligne:last-child{border-bottom:none}
        .recap-ligne strong{width:110px;flex-shrink:0;color:#374151}
        textarea{width:100%;min-height:100px;border-radius:14px;border:1px solid var(--bord);padding:12px;font:inherit;resize:vertical;background:#fff}
        @media (max-width:760px){
            .top,.hist-ligne{flex-direction:column;align-items:flex-start}
            .actions{width:100%}
            .actions form{width:100%}
            .actions .btn{width:100%}
        }
    </style>
</head>
<body>
<div class="page">
    <div class="entete">
        <a href="accueil_entreprise.php" class="retour" aria-label="Retour">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </a>
        <div class="titre-zone" style="flex:1">
            <h1>Gestion des candidatures</h1>
        </div>
        <div style="width:42px"></div>
    </div>

    <?php if ($msgOk): ?>
        <div class="alert alert-ok"><?php echo h($msgOk); ?></div>
    <?php endif; ?>

    <?php if ($msgErr): ?>
        <div class="alert alert-err"><?php echo h($msgErr); ?></div>
    <?php endif; ?>

    <div class="section-label">
        Candidatures en attente
        <?php if (!empty($candidatures)): ?>
            <span class="count"><?php echo count($candidatures); ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($candidatures)): ?>
        <div class="etat-vide">
            <h3>Aucune candidature en attente</h3>
            <p>Les nouvelles candidatures apparaîtront ici.</p>
        </div>
    <?php else: ?>
        <div class="liste">
            <?php foreach ($candidatures as $c): ?>
                <?php
                    $noms = explode(' ', trim($c['nom_etudiant'] ?? ''));
                    $initiales = strtoupper(mb_substr($noms[0] ?? '?', 0, 1) . mb_substr($noms[1] ?? '', 0, 1));
                    $dateFmt = !empty($c['date_debut']) ? date('d/m/Y', strtotime($c['date_debut'])) : 'Non précisé';
                ?>
                <div class="carte">
                    <div class="top">
                        <div class="identite">
                            <div class="avatar"><?php echo h($initiales); ?></div>
                            <div>
                                <h2 class="nom"><?php echo h($c['nom_etudiant']); ?></h2>
                                <p class="sub">
                                    <?php echo h(implode(' · ', array_filter([$c['filiere'] ?? '', $c['niveau'] ?? '', $c['email_etudiant'] ?? '']))); ?>
                                </p>
                                <p class="poste"><?php echo h($c['titre']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="meta">
                        <span>Début : <?php echo h($dateFmt); ?></span>
                        <?php if (!empty($c['duree_semaines'])): ?>
                            <span>Durée : <?php echo (int)$c['duree_semaines']; ?> semaines</span>
                        <?php endif; ?>
                        <span>N° candidature : <?php echo (int)$c['num_stage']; ?></span>
                    </div>

                    <div class="bloc">
                        <h3>Documents transmis</h3>

                        <?php if (empty($c['documents'])): ?>
                            <p style="margin:0;color:var(--muted);font-size:.88rem;">Aucun document n’a encore été déposé par l’étudiant.</p>
                        <?php else: ?>
                            <div class="docs">
                                <?php foreach ($c['documents'] as $doc): ?>
                                    <div class="doc">
                                        <div>
                                            <strong><?php echo h(str_replace('_', ' ', $doc['type_document'])); ?></strong>
                                            <small><?php echo h($doc['nom_fichier']); ?> · envoyé le <?php echo h(date('d/m/Y H:i', strtotime($doc['date_envoi']))); ?></small>
                                        </div>
                                        <a href="<?php echo h($doc['chemin_fichier']); ?>" target="_blank">Ouvrir</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="actions">
                        <form method="POST">
                            <input type="hidden" name="action" value="demander_confirmation">
                            <input type="hidden" name="num_stage" value="<?php echo (int)$c['num_stage']; ?>">
                            <button type="submit" class="btn btn-ok">Valider la candidature</button>
                        </form>

                        <button type="button" class="btn btn-ko" onclick="ouvrirRefus(<?php echo (int)$c['num_stage']; ?>, '<?php echo h($c['nom_etudiant']); ?>')">
                            Refuser
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($historique)): ?>
        <div class="section-label">Historique récent</div>
        <div class="historique">
            <?php foreach ($historique as $histo): ?>
                <?php [$lbl, $cls] = formatHistoriqueStatut($histo['statut_candidature'] ?? ''); ?>
                <div class="hist-ligne">
                    <div>
                        <div style="font-weight:700"><?php echo h($histo['nom_etudiant']); ?></div>
                        <div style="font-size:.84rem;color:var(--muted);margin-top:3px"><?php echo h($histo['titre']); ?></div>
                    </div>
                    <span class="badge <?php echo h($cls); ?>"><?php echo h($lbl); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($confirmData): ?>
    <div class="overlay" id="overlay-confirm">
        <div class="modal">
            <h3>Valider la candidature</h3>
            <p>Vous êtes sur le point d’accepter la candidature de <strong><?php echo h($confirmData['nom_etudiant']); ?></strong>. L’étudiant devra ensuite confirmer ou refuser le stage depuis son espace.</p>

            <div class="recap">
                <div class="recap-ligne"><strong>Candidat</strong><span><?php echo h($confirmData['nom_etudiant']); ?></span></div>
                <div class="recap-ligne"><strong>Filière</strong><span><?php echo h(implode(' · ', array_filter([$confirmData['filiere'] ?? '', $confirmData['niveau'] ?? '']))); ?></span></div>
                <div class="recap-ligne"><strong>Poste</strong><span><?php echo h($confirmData['titre']); ?></span></div>
                <div class="recap-ligne"><strong>Début</strong><span><?php echo !empty($confirmData['date_debut']) ? h(date('d/m/Y', strtotime($confirmData['date_debut']))) : 'Non précisé'; ?></span></div>
                <div class="recap-ligne"><strong>Durée</strong><span><?php echo !empty($confirmData['duree_semaines']) ? (int)$confirmData['duree_semaines'] . ' semaines' : 'Non précisée'; ?></span></div>
            </div>

            <div class="actions">
                <form method="POST" style="flex:1">
                    <input type="hidden" name="action" value="confirmer_validation">
                    <input type="hidden" name="num_stage" value="<?php echo (int)$confirmData['num_stage']; ?>">
                    <button type="submit" class="btn btn-ok" style="width:100%">Confirmer la validation</button>
                </form>
                <button type="button" class="btn btn-ko" onclick="document.getElementById('overlay-confirm').remove()">Annuler</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="overlay" id="overlay-refus" style="display:none;">
    <div class="modal">
        <h3 id="titre-refus">Refuser la candidature</h3>
        <p>L’étudiant sera notifié du refus. Vous pouvez ajouter un motif facultatif.</p>

        <form method="POST">
            <input type="hidden" name="action" value="refuser">
            <input type="hidden" name="num_stage" id="refus-num-stage">

            <textarea name="motif" placeholder="Motif du refus (optionnel)"></textarea>

            <div class="actions">
                <button type="submit" class="btn btn-ko">Confirmer le refus</button>
                <button type="button" class="btn btn-ok" onclick="fermerRefus()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function ouvrirRefus(numStage, nomEtudiant) {
    document.getElementById('refus-num-stage').value = numStage;
    document.getElementById('titre-refus').textContent = 'Refuser ' + nomEtudiant;
    document.getElementById('overlay-refus').style.display = 'flex';
}
function fermerRefus() {
    document.getElementById('overlay-refus').style.display = 'none';
}
document.getElementById('overlay-refus').addEventListener('click', function(e){
    if (e.target === this) fermerRefus();
});
</script>
</body>
</html>