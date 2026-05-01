<?php
session_start();

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'Etudiant') {
    header("Location: ../../public/login.php?erreur=4");
    exit;
}

$idEtudiant = (int)$_SESSION['id'];
$msgOk = '';
$msgErr = '';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function formatStatutCandidature(string $statut): array
{
    $map = [
        'en_attente' => ['En attente', 'badge-orange'],
        'acceptee_entreprise' => ['Acceptée par l’entreprise', 'badge-vert'],
        'confirmee_etudiant' => ['Stage confirmé', 'badge-vert'],
        'refusee_entreprise' => ['Refusée par l’entreprise', 'badge-rouge'],
        'refusee_etudiant' => ['Annulée par l’étudiant', 'badge-rouge'],
    ];

    return $map[$statut] ?? [$statut, 'badge-gris'];
}

function enregistrerDocument(mysqli $conn, int $numStage, array $fichier, string $typeDocument, string $prefixe): void
{
    if (!isset($fichier['tmp_name']) || ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return;
    }

    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception("Erreur lors de l'envoi du fichier : " . $typeDocument);
    }

    $extensionsAutorisees = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
    $tailleMax = 5 * 1024 * 1024;

    $nomOriginal = $fichier['name'] ?? 'document';
    $taille = (int)($fichier['size'] ?? 0);
    $ext = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

    if (!in_array($ext, $extensionsAutorisees, true)) {
        throw new Exception("Format non autorisé pour : " . $nomOriginal);
    }

    if ($taille > $tailleMax) {
        throw new Exception("Fichier trop volumineux : " . $nomOriginal);
    }

    $dossier = __DIR__ . '/uploads/candidatures/' . $numStage;
    if (!is_dir($dossier) && !mkdir($dossier, 0777, true)) {
        throw new Exception("Impossible de créer le dossier d'upload.");
    }

    $nomStocke = $prefixe . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $cheminAbsolu = $dossier . '/' . $nomStocke;
    $cheminBDD = 'uploads/candidatures/' . $numStage . '/' . $nomStocke;

    if (!move_uploaded_file($fichier['tmp_name'], $cheminAbsolu)) {
        throw new Exception("Impossible d'enregistrer le fichier : " . $nomOriginal);
    }

    $stmt = mysqli_prepare($conn, "
        INSERT INTO DocumentCandidature (num_stage, type_document, nom_fichier, chemin_fichier)
        VALUES (?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "isss", $numStage, $typeDocument, $nomOriginal, $cheminBDD);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}


try {
    $conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
    if (!$conn) {
        throw new Exception("Connexion à la base de données échouée.");
    }
    mysqli_set_charset($conn, 'utf8mb4');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $numStage = (int)($_POST['num_stage'] ?? 0);

        if ($action === 'upload_documents') {
            $check = mysqli_prepare($conn, "
                SELECT num_stage, titre
                FROM Stage
                WHERE num_stage = ? AND id_etudiant = ?
            ");
            mysqli_stmt_bind_param($check, "ii", $numStage, $idEtudiant);
            mysqli_stmt_execute($check);
            $cand = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
            mysqli_stmt_close($check);

            if (!$cand) {
                throw new Exception("Candidature introuvable.");
            }

            mysqli_begin_transaction($conn);

            enregistrerDocument($conn, $numStage, $_FILES['cv'] ?? [], 'cv', 'cv');
            enregistrerDocument($conn, $numStage, $_FILES['lettre_motivation'] ?? [], 'lettre_motivation', 'lm');
            enregistrerDocument($conn, $numStage, $_FILES['convention_stage'] ?? [], 'convention_stage', 'convention');

            if (!empty($_FILES['documents_supplementaires']['name']) && is_array($_FILES['documents_supplementaires']['name'])) {
                $count = count($_FILES['documents_supplementaires']['name']);
                for ($i = 0; $i < $count; $i++) {
                    $file = [
                        'name' => $_FILES['documents_supplementaires']['name'][$i] ?? '',
                        'type' => $_FILES['documents_supplementaires']['type'][$i] ?? '',
                        'tmp_name' => $_FILES['documents_supplementaires']['tmp_name'][$i] ?? '',
                        'error' => $_FILES['documents_supplementaires']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $_FILES['documents_supplementaires']['size'][$i] ?? 0,
                    ];
                    enregistrerDocument($conn, $numStage, $file, 'supplementaire', 'suppl');
                }
            }

            mysqli_commit($conn);
            $msgOk = "Les documents ont bien été envoyés.";
        }

        if ($action === 'annuler_candidature') {
            $stmt = mysqli_prepare($conn, "
                UPDATE Stage
                SET statut = 'annule',
                    statut_candidature = 'refusee_etudiant'
                WHERE num_stage = ?
                  AND id_etudiant = ?
                  AND statut_candidature IN ('en_attente', 'acceptee_entreprise')
            ");
            mysqli_stmt_bind_param($stmt, "ii", $numStage, $idEtudiant);
            mysqli_stmt_execute($stmt);
            $ok = mysqli_stmt_affected_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if (!$ok) {
                throw new Exception("Cette candidature ne peut plus être annulée.");
            }

            $st = mysqli_prepare($conn, "
                SELECT titre, id_entreprise
                FROM Stage
                WHERE num_stage = ?
            ");
            mysqli_stmt_bind_param($st, "i", $numStage);
            mysqli_stmt_execute($st);
            $stage = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
            mysqli_stmt_close($st);

            if ($stage) {
                $titreCand = $stage['titre'] ?? 'une offre';
                $idEntreprise = (int)($stage['id_entreprise'] ?? 0);

                if ($idEntreprise > 0) {
                    $titreNotif = "Candidature annulée — " . $titreCand;
                    $messageNotif = ($_SESSION['prenom'] ?? 'L’étudiant') . " " . ($_SESSION['nom'] ?? '') . " a annulé sa candidature pour le poste \"" . $titreCand . "\".";
                    $ins = mysqli_prepare($conn, "
                        INSERT INTO Notification (id_user, type, titre, message)
                        VALUES (?, 'autre', ?, ?)
                    ");
                    mysqli_stmt_bind_param($ins, "iss", $idEntreprise, $titreNotif, $messageNotif);
                    mysqli_stmt_execute($ins);
                    mysqli_stmt_close($ins);
                }
            }

            $msgOk = "La candidature a bien été annulée.";
        }
        if ($action === 'confirmer_stage') {
            // 1. Récupérer l'état réel actuel
            $stmt = mysqli_prepare($conn, "
                SELECT statut_candidature, convention_validee
                FROM Stage
                WHERE num_stage = ? AND id_etudiant = ?
            ");
            mysqli_stmt_bind_param($stmt, "ii", $numStage, $idEtudiant);
            mysqli_stmt_execute($stmt);
            $stage = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$stage) {
                throw new Exception("Candidature introuvable.");
            }

            // 2. Vérifier si l'entreprise a validé la candidature
            if (($stage['statut_candidature'] ?? '') !== 'acceptee_entreprise') {
                throw new Exception("Ce stage ne peut pas être confirmé car l'entreprise n'a pas encore validé votre candidature.");
            }

            // 3. Vérifier si la convention a été validée (règle métier)
            if ((int)($stage['convention_validee'] ?? 0) !== 1) {
                throw new Exception("Ce stage ne peut pas être confirmé car la convention de stage n'a pas été validée par l'entreprise.");
            }

            // 4. Si tout est bon, on confirme
            $upd = mysqli_prepare($conn, "
                UPDATE Stage
                SET statut_candidature = 'confirmee_etudiant',
                    statut = 'en_cours'
                WHERE num_stage = ?
                AND id_etudiant = ?
                AND statut_candidature = 'acceptee_entreprise'
                AND convention_validee = 1
            ");
            mysqli_stmt_bind_param($upd, "ii", $numStage, $idEtudiant);
            mysqli_stmt_execute($upd);
            
            if (mysqli_stmt_affected_rows($upd) <= 0) {
                throw new Exception("Une erreur est survenue lors de la confirmation.");
            }
            mysqli_stmt_close($upd);

            $msgOk = "Le stage a bien été confirmé.";
        }
    }

    $candidatures = [];
    $stmt = mysqli_prepare($conn, "
        SELECT
            s.num_stage,
            s.num_offre,
            s.titre,
            s.mission,
            s.statut,
            s.statut_candidature,
            s.id_entreprise,
            o.date_debut,
            o.duree_semaines,
            u.nom_entreprise,
            u.ville
        FROM Stage s
        JOIN Utilisateur u ON u.id = s.id_entreprise
        LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
        WHERE s.id_etudiant = ?
        ORDER BY s.num_stage DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $idEtudiant);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $row['documents'] = [];
        $sd = mysqli_prepare($conn, "
            SELECT id, type_document, nom_fichier, chemin_fichier, date_envoi
            FROM DocumentCandidature
            WHERE num_stage = ?
            ORDER BY date_envoi DESC, id DESC
        ");
        mysqli_stmt_bind_param($sd, "i", $row['num_stage']);
        mysqli_stmt_execute($sd);
        $rd = mysqli_stmt_get_result($sd);
        while ($doc = mysqli_fetch_assoc($rd)) {
            $row['documents'][] = $doc;
        }
        mysqli_stmt_close($sd);

        $candidatures[] = $row;
    }
    mysqli_stmt_close($stmt);
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
    <title>Candidatures déposées - CY Stage</title>
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
        .page{max-width:1100px;margin:0 auto;padding:24px 18px 40px}
        .entete{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px}
        .retour{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid var(--bord);color:var(--bleu);text-decoration:none;box-shadow:0 2px 10px rgba(27,79,155,.06)}
        .retour svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round}
        .titre-zone h1{margin:0;font-size:1.45rem;color:var(--bleu)}
        .titre-zone p{margin:5px 0 0;color:var(--muted);font-size:.92rem}
        .alert{border-radius:14px;padding:13px 15px;margin-bottom:16px;font-size:.92rem;font-weight:600;border:1px solid transparent;background:#fff}
        .alert-ok{background:var(--vert-fond);color:var(--vert);border-color:#b7ebc6}
        .alert-err{background:var(--rouge-fond);color:var(--rouge);border-color:#fecaca}
        .liste{display:grid;gap:16px}
        .carte{background:var(--blanc);border:1px solid var(--bord);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px}
        .carte-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}
        .carte-titre{margin:0;font-size:1.05rem;color:var(--texte)}
        .carte-sous{margin:5px 0 0;color:var(--muted);font-size:.88rem}
        .badge{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:.75rem;font-weight:700;white-space:nowrap}
        .badge-vert{background:#ecfdf3;color:var(--vert)}
        .badge-orange{background:#fff7ed;color:var(--orange)}
        .badge-rouge{background:#fff1f2;color:var(--rouge)}
        .badge-bleu{background:#eff6ff;color:var(--bleu)}
        .badge-gris{background:#f3f4f6;color:#4b5563}
        .meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
        .meta span{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;color:var(--muted);background:#f8fafc;border:1px solid #edf2f7;padding:7px 10px;border-radius:999px}
        .bloc{margin-top:16px;padding-top:16px;border-top:1px solid var(--bord)}
        .bloc h3{margin:0 0 10px;font-size:.92rem;color:var(--bleu)}
        .docs{display:grid;gap:8px;margin-bottom:12px}
        .doc-ligne{display:flex;justify-content:space-between;align-items:center;gap:10px;border:1px solid var(--bord);border-radius:12px;padding:10px 12px;background:#fbfdff}
        .doc-ligne small{display:block;color:var(--muted);margin-top:2px}
        .doc-ligne a{color:var(--bleu);font-weight:700;text-decoration:none;font-size:.84rem}
        .form-upload{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
        .champ{display:flex;flex-direction:column;gap:6px}
        .champ label{font-size:.82rem;font-weight:700;color:#374151}
        .champ input[type="file"]{border:1px solid var(--bord);background:#fff;border-radius:12px;padding:10px;font-size:.84rem}
        .champ.full{grid-column:1 / -1}
        .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
        .btn{border:none;border-radius:12px;padding:11px 15px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px}
        .btn-primary{background:var(--bleu);color:#fff}
        .btn-danger{background:#fff;color:var(--rouge);border:1px solid #fecaca}
        .btn-danger:hover{background:var(--rouge);color:#fff}
        .etat-vide{background:#fff;border:1px dashed #cbd5e1;border-radius:20px;padding:40px 18px;text-align:center;color:var(--muted)}
        .etat-vide h3{margin:0 0 8px;color:var(--bleu)}
        @media (max-width:760px){
            .carte-head{flex-direction:column}
            .form-upload{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
<div class="page">
    <div class="entete">
        <a href="accueil_etudiant.php" class="retour" aria-label="Retour">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </a>
        <div class="titre-zone" style="flex:1">
            <h1>Candidatures déposées</h1>
            <p>Retrouve ici toutes les offres auxquelles tu as postulé, les documents envoyés et l’état de chaque candidature.</p>
        </div>
        <div style="width:42px"></div>
    </div>

    <?php if ($msgOk): ?>
        <div class="alert alert-ok"><?php echo h($msgOk); ?></div>
    <?php endif; ?>

    <?php if ($msgErr): ?>
        <div class="alert alert-err"><?php echo h($msgErr); ?></div>
    <?php endif; ?>

    <?php if (empty($candidatures)): ?>
        <div class="etat-vide">
            <h3>Aucune candidature pour le moment</h3>
            <p>Les offres auxquelles tu postules apparaîtront ici.</p>
        </div>
    <?php else: ?>
        <div class="liste">
            <?php foreach ($candidatures as $c): ?>
                <?php
                    [$libelleStatut, $classeStatut] = formatStatutCandidature($c['statut_candidature'] ?? '');
                    $dateDebut = !empty($c['date_debut']) ? date('d/m/Y', strtotime($c['date_debut'])) : 'Non précisé';
                    $duree = !empty($c['duree_semaines']) ? ((int)$c['duree_semaines']) . ' semaines' : 'Durée non précisée';
                ?>
                <div class="carte">
                    <div class="carte-head">
                        <div>
                            <h2 class="carte-titre"><?php echo h($c['titre']); ?></h2>
                            <p class="carte-sous">
                                <?php echo h($c['nom_entreprise'] ?? 'Entreprise'); ?>
                                <?php if (!empty($c['ville'])): ?> · <?php echo h($c['ville']); ?><?php endif; ?>
                            </p>
                        </div>
                        <span class="badge <?php echo h($classeStatut); ?>"><?php echo h($libelleStatut); ?></span>
                    </div>

                    <div class="meta">
                        <span>Début : <?php echo h($dateDebut); ?></span>
                        <span>Durée : <?php echo h($duree); ?></span>
                        <span>N° candidature : <?php echo (int)$c['num_stage']; ?></span>
                    </div>

                    <div class="bloc">
                        <h3>Documents déjà envoyés</h3>

                        <?php if (empty($c['documents'])): ?>
                            <p style="margin:0;color:var(--muted);font-size:.88rem;">Aucun document transmis pour l’instant.</p>
                        <?php else: ?>
                            <div class="docs">
                                <?php foreach ($c['documents'] as $doc): ?>
                                    <div class="doc-ligne">
                                        <div>
                                            <strong><?php echo h(str_replace('_', ' ', $doc['type_document'])); ?></strong>
                                            <small><?php echo h($doc['nom_fichier']); ?> · envoyé le <?php echo h(date('d/m/Y H:i', strtotime($doc['date_envoi']))); ?></small>
                                        </div>
                                        <a href="<?php echo h($doc['chemin_fichier']); ?>" target="_blank">Consulter</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (in_array($c['statut_candidature'], ['en_attente', 'acceptee_entreprise'], true)): ?>
                        <div class="bloc">
                            <h3>Ajouter des documents</h3>
                            <form method="POST" enctype="multipart/form-data" class="form-upload">
                                <input type="hidden" name="action" value="upload_documents">
                                <input type="hidden" name="num_stage" value="<?php echo (int)$c['num_stage']; ?>">

                                <div class="champ">
                                    <label for="cv-<?php echo (int)$c['num_stage']; ?>">CV</label>
                                    <input type="file" name="cv" id="cv-<?php echo (int)$c['num_stage']; ?>" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                </div>

                                <div class="champ">
                                    <label for="lm-<?php echo (int)$c['num_stage']; ?>">Lettre de motivation</label>
                                    <input type="file" name="lettre_motivation" id="lm-<?php echo (int)$c['num_stage']; ?>" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                </div>

                                <div class="champ">
                                    <label for="conv-<?php echo (int)$c['num_stage']; ?>">Convention de stage</label>
                                    <input type="file" name="convention_stage" id="conv-<?php echo (int)$c['num_stage']; ?>" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                </div>

                                <div class="champ">
                                    <label for="suppl-<?php echo (int)$c['num_stage']; ?>">Documents supplémentaires</label>
                                    <input type="file" name="documents_supplementaires[]" id="suppl-<?php echo (int)$c['num_stage']; ?>" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                </div>

                                <div class="champ full">
                                    <button type="submit" class="btn btn-primary">Envoyer les documents</button>
                                </div>
                            </form>

                            <div class="actions">
                                <form method="POST" onsubmit="return confirm('Confirmer ce stage ?');">
                                    <input type="hidden" name="action" value="confirmer_stage">
                                    <input type="hidden" name="num_stage" value="<?php echo (int)$c['num_stage']; ?>">
                                    <button type="submit" class="btn btn-success">
                                        Confirmer le stage
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Confirmer l’annulation de cette candidature ?');">
                                    <input type="hidden" name="action" value="annuler_candidature">
                                    <input type="hidden" name="num_stage" value="<?php echo (int)$c['num_stage']; ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <?php echo ($c['statut_candidature'] === 'acceptee_entreprise') ? 'Refuser ce stage' : 'Annuler ma candidature'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>