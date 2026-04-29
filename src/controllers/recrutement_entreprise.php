<?php
/**
 * recrutement_entreprise.php
 * ─────────────────────────────────────────────────────────────
 * Gestion des candidatures reçues par l'entreprise.
 *
 * FLUX :
 *  1. Liste des candidatures (statut_candidature = 'en_attente')
 *  2. Clic "Valider" → formulaire de confirmation
 *  3. Confirmation → Stage passe en 'acceptee_entreprise'
 *                  → Notification créée pour l'étudiant
 *                  → Si conflit de stage, message d'erreur
 *  4. Clic "Refuser" → Stage passe en 'refusee_entreprise'
 *                    → Notification créée pour l'étudiant
 * ─────────────────────────────────────────────────────────────
 */
session_start();
 
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Entreprise') {
    header('Location: ../../public/login.php?erreur=4');
    exit();
}
 
$id_entreprise = (int)$_SESSION['id'];
$msg_ok  = '';
$msg_err = '';
$confirm_data = null; // données pour l'étape de confirmation
 
try {
    $conn = mysqli_connect('localhost', 'userpro', 'projetStage26.', 'cyStages');
    if (!$conn) throw new Exception("Connexion DB échouée.");
    mysqli_set_charset($conn, 'utf8mb4');
 
    /* ═══════════════════════════════════════════════════════════
       ÉTAPE 2 : Affichage du formulaire de confirmation
       (POST avec action='demander_confirmation')
    ═══════════════════════════════════════════════════════════ */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'demander_confirmation') {
        $num_stage = (int)($_POST['num_stage'] ?? 0);
 
        // Charger les infos du stage/candidature pour l'afficher dans le formulaire
        $stmt = mysqli_prepare($conn,
            "SELECT s.num_stage, s.titre,
                    CONCAT(u.prenom,' ',u.nom) AS nom_etudiant,
                    u.filiere, u.niveau,
                    o.duree_semaines, o.date_debut, o.mission
             FROM Stage s
             JOIN Utilisateur u ON u.id = s.id_etudiant
             LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
             WHERE s.num_stage = ? AND s.id_entreprise = ?
               AND s.statut_candidature = 'en_attente'"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $num_stage, $id_entreprise);
        mysqli_stmt_execute($stmt);
        $confirm_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
 
        if (!$confirm_data) {
            $msg_err = "Candidature introuvable ou déjà traitée.";
        }
        // On ne fait pas de redirect : on va afficher le formulaire de confirmation ci-dessous
    }
 
    /* ═══════════════════════════════════════════════════════════
       ÉTAPE 3 : Confirmation définitive de validation
       (POST avec action='confirmer_validation')
    ═══════════════════════════════════════════════════════════ */
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirmer_validation') {
        $num_stage = (int)($_POST['num_stage'] ?? 0);
 
        mysqli_begin_transaction($conn);
 
        // 1. Récupérer les infos de la candidature
        $stmt = mysqli_prepare($conn,
            "SELECT s.num_stage, s.id_etudiant, s.num_offre, s.titre,
                    o.date_debut, o.duree_semaines
             FROM Stage s
             LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
             WHERE s.num_stage = ? AND s.id_entreprise = ?
               AND s.statut_candidature = 'en_attente'"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $num_stage, $id_entreprise);
        mysqli_stmt_execute($stmt);
        $cand = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
 
        if (!$cand) {
            mysqli_rollback($conn);
            $msg_err = "Candidature introuvable ou déjà traitée.";
        } else {
            $id_etudiant = $cand['id_etudiant'];
 
            // 2. Vérifier que l'étudiant n'a pas déjà un stage actif EN MÊME TEMPS
            // (statut en_cours ou confirmee_etudiant avec dates qui se chevauchent)
            $date_debut_offre = $cand['date_debut'];
            $duree            = (int)$cand['duree_semaines'];
            $date_fin_offre   = $date_debut_offre
                                ? date('Y-m-d', strtotime($date_debut_offre . ' +' . $duree . ' weeks'))
                                : null;
 
            $conflit = false;
            if ($date_debut_offre) {
                $sc = mysqli_prepare($conn,
                    "SELECT COUNT(*) FROM Stage
                     WHERE id_etudiant = ?
                       AND num_stage != ?
                       AND statut IN ('en_cours','en_attente')
                       AND statut_candidature IN ('confirmee_etudiant','acceptee_entreprise')
                       AND date_debut IS NOT NULL
                       AND date_debut <= ?
                       AND (date_fin IS NULL OR date_fin >= ?)"
                );
                mysqli_stmt_bind_param($sc, 'iiss',
                    $id_etudiant, $num_stage, $date_fin_offre, $date_debut_offre
                );
                mysqli_stmt_execute($sc);
                mysqli_stmt_bind_result($sc, $nb_conflits);
                mysqli_stmt_fetch($sc);
                mysqli_stmt_close($sc);
                $conflit = $nb_conflits > 0;
            }
 
            if ($conflit) {
                mysqli_rollback($conn);
                $msg_err = "Impossible de valider : cet étudiant a déjà un stage prévu sur cette période.";
            } else {
                // 3. Mettre à jour le statut de la candidature
                $upd = mysqli_prepare($conn,
                    "UPDATE Stage SET statut_candidature = 'acceptee_entreprise'
                     WHERE num_stage = ? AND id_entreprise = ?"
                );
                mysqli_stmt_bind_param($upd, 'ii', $num_stage, $id_entreprise);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);
 
                // 4. Créer une notification pour l'étudiant
                $nom_ent = htmlspecialchars($_SESSION['nom_entreprise'] ?? 'L\'entreprise');
                $titre_notif   = "Candidature acceptée — " . $cand['titre'];
                $message_notif = "$nom_ent a accepté votre candidature pour le poste \"{$cand['titre']}\". "
                               . "Rendez-vous dans votre espace pour confirmer ou refuser cette offre.";
                $lien_notif    = "confirmer_stage_etudiant.php?stage=" . $num_stage;
 
                $ins_notif = mysqli_prepare($conn,
                    "INSERT INTO Notification (id_user, type, titre, message, lien)
                     VALUES (?, 'candidature_validee', ?, ?, ?)"
                );
                mysqli_stmt_bind_param($ins_notif, 'isss',
                    $id_etudiant, $titre_notif, $message_notif, $lien_notif
                );
                mysqli_stmt_execute($ins_notif);
                mysqli_stmt_close($ins_notif);
 
                mysqli_commit($conn);
                $msg_ok = "Candidature validée ! L'étudiant a été notifié et doit maintenant confirmer de son côté.";
            }
        }
    }
 
    /* ═══════════════════════════════════════════════════════════
       REFUS D'UNE CANDIDATURE
    ═══════════════════════════════════════════════════════════ */
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'refuser') {
        $num_stage = (int)($_POST['num_stage'] ?? 0);
        $motif     = trim($_POST['motif'] ?? '');
 
        $stmt = mysqli_prepare($conn,
            "SELECT s.id_etudiant, s.titre FROM Stage s
             WHERE s.num_stage = ? AND s.id_entreprise = ?
               AND s.statut_candidature = 'en_attente'"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $num_stage, $id_entreprise);
        mysqli_stmt_execute($stmt);
        $cand = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
 
        if ($cand) {
            // Mettre à jour le statut
            $upd = mysqli_prepare($conn,
                "UPDATE Stage SET statut_candidature = 'refusee_entreprise', statut = 'annule'
                 WHERE num_stage = ?"
            );
            mysqli_stmt_bind_param($upd, 'i', $num_stage);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
 
            // Notification refus
            $nom_ent = $_SESSION['nom_entreprise'] ?? 'L\'entreprise';
            $titre_notif   = "Candidature refusée — " . $cand['titre'];
            $message_notif = "$nom_ent n'a pas retenu votre candidature pour le poste \"{$cand['titre']}\"."
                           . ($motif ? " Motif : $motif" : " Aucun motif précisé.");
 
            $ins_notif = mysqli_prepare($conn,
                "INSERT INTO Notification (id_user, type, titre, message)
                 VALUES (?, 'candidature_refusee', ?, ?)"
            );
            mysqli_stmt_bind_param($ins_notif, 'iss',
                $cand['id_etudiant'], $titre_notif, $message_notif
            );
            mysqli_stmt_execute($ins_notif);
            mysqli_stmt_close($ins_notif);
 
            $msg_ok = "Candidature refusée. L'étudiant a été notifié.";
        } else {
            $msg_err = "Candidature introuvable.";
        }
    }
 
    /* ═══════════════════════════════════════════════════════════
       CHARGEMENT DE LA LISTE DES CANDIDATURES EN ATTENTE
    ═══════════════════════════════════════════════════════════ */
    $candidatures = [];
    $stmt = mysqli_prepare($conn,
        "SELECT s.num_stage, s.titre,
                CONCAT(u.prenom,' ',u.nom) AS nom_etudiant,
                u.filiere, u.niveau, u.email AS email_etudiant,
                o.date_debut, o.duree_semaines,
                s.statut_candidature
         FROM Stage s
         JOIN Utilisateur u ON u.id = s.id_etudiant
         LEFT JOIN Offre_Stage o ON o.num_offre = s.num_offre
         WHERE s.id_entreprise = ?
           AND s.statut_candidature = 'en_attente'
         ORDER BY s.num_stage DESC"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_entreprise);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) $candidatures[] = $row;
    mysqli_stmt_close($stmt);
 
    // Historique récent (acceptées / refusées)
    $historique = [];
    $sh = mysqli_prepare($conn,
        "SELECT s.titre, s.statut_candidature,
                CONCAT(u.prenom,' ',u.nom) AS nom_etudiant
         FROM Stage s
         JOIN Utilisateur u ON u.id = s.id_etudiant
         WHERE s.id_entreprise = ?
           AND s.statut_candidature != 'en_attente'
         ORDER BY s.num_stage DESC LIMIT 10"
    );
    mysqli_stmt_bind_param($sh, 'i', $id_entreprise);
    mysqli_stmt_execute($sh);
    $rh = mysqli_stmt_get_result($sh);
    while ($row = mysqli_fetch_assoc($rh)) $historique[] = $row;
    mysqli_stmt_close($sh);
 
    mysqli_close($conn);
 
} catch (Exception $e) {
    $msg_err = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des candidatures — CY Stage</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Variables (reprises du projet) ── */
        :root {
            --bleu:        #1B4F9B;
            --bleu-clair:  #2563c7;
            --blanc:       #ffffff;
            --gris-fond:   #f4f6fb;
            --gris-texte:  #6b7280;
            --gris-border: #e5e7eb;
            --noir:        #111827;
            --vert:        #16a34a;
            --rouge:       #dc2626;
            --orange:      #d97706;
            --radius:      14px;
            --shadow:      0 2px 12px rgba(27,79,155,.10);
        }
 
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
 
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--gris-fond);
            color: var(--noir);
            min-height: 100vh;
        }
 
        /* ── Layout ── */
        .page {
            max-width: 860px;
            margin: 0 auto;
            background: var(--blanc);
            min-height: 100vh;
            padding: 32px 40px 48px;
            box-shadow: 0 0 40px rgba(27,79,155,.08);
        }
 
        /* ── Header ── */
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 36px;
        }
        .header a {
            display: flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; border-radius: 50%;
            border: 1px solid var(--gris-border);
            background: var(--gris-fond);
            color: var(--bleu); text-decoration: none; flex-shrink: 0;
            transition: background .2s;
        }
        .header a:hover { background: var(--gris-border); }
        .header a svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; }
 
        /* ── Alertes ── */
        .alert {
            padding: 11px 15px; border-radius: 10px;
            font-size: .85rem; font-weight: 600;
            margin-bottom: 22px; display: flex; align-items: center; gap: 9px;
        }
        .alert-ok  { background: rgba(22,163,74,.09); border: 1px solid var(--vert); color: var(--vert); }
        .alert-err { background: #fff0f0; border: 1px solid #fca5a5; color: var(--rouge); }
 
        /* ── Section titre ── */
        .section-label {
            font-size: .68rem; font-weight: 700; letter-spacing: .15em;
            text-transform: uppercase; color: var(--bleu);
            margin-bottom: 14px; margin-top: 28px;
        }
 
        /* ── Carte candidature ── */
        .cand-card {
            background: var(--blanc);
            border: 1px solid var(--gris-border);
            border-radius: var(--radius);
            padding: 18px 20px;
            margin-bottom: 14px;
            box-shadow: var(--shadow);
            transition: transform .15s, box-shadow .15s;
        }
        .cand-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(27,79,155,.12); }
 
        .cand-top {
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 12px;
            margin-bottom: 12px;
        }
 
        .cand-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--bleu), var(--bleu-clair));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-family: 'Syne', sans-serif;
            font-size: .90rem; font-weight: 800; flex-shrink: 0;
        }
 
        .cand-info { flex: 1; min-width: 0; }
        .cand-nom  { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem; }
        .cand-sub  { font-size: .78rem; color: var(--gris-texte); margin-top: 2px; }
        .cand-titre { font-size: .82rem; color: var(--bleu-clair); font-weight: 600; margin-top: 3px; }
 
        .cand-meta {
            display: flex; gap: 14px; flex-wrap: wrap;
            font-size: .76rem; color: var(--gris-texte);
            padding-top: 10px; border-top: 1px solid var(--gris-border);
            margin-bottom: 14px;
        }
        .cand-meta span { display: flex; align-items: center; gap: 4px; }
        .cand-meta svg  { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
 
        /* ── Boutons d'action ── */
        .cand-actions { display: flex; gap: 9px; flex-wrap: wrap; }
 
        .btn-valider {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border: none; border-radius: 8px;
            background: var(--vert); color: #fff;
            font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: .84rem;
            cursor: pointer; transition: opacity .2s, transform .2s;
        }
        .btn-valider:hover { opacity: .88; transform: translateY(-1px); }
        .btn-valider svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
 
        .btn-refuser {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border: 1px solid var(--rouge);
            background: transparent; color: var(--rouge);
            border-radius: 8px; font-family: 'DM Sans', sans-serif;
            font-weight: 700; font-size: .84rem; cursor: pointer;
            transition: background .2s, color .2s;
        }
        .btn-refuser:hover { background: var(--rouge); color: #fff; }
        .btn-refuser svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
 
        /* ── Formulaire de confirmation ── */
        .confirm-overlay {
            position: fixed; inset: 0;
            background: rgba(17,24,39,.5);
            z-index: 200;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            animation: fadeIn .2s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
 
        .confirm-box {
            background: var(--blanc);
            border-radius: 20px;
            padding: 30px 28px 28px;
            width: 100%; max-width: 480px;
            box-shadow: 0 20px 60px rgba(17,24,39,.25);
            animation: slideUp .25s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
 
        .confirm-icon {
            width: 56px; height: 56px; border-radius: 50%;
            background: rgba(22,163,74,.12);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            color: var(--vert);
        }
        .confirm-icon svg { width: 26px; height: 26px; fill: none; stroke: currentColor; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
 
        .confirm-titre {
            font-family: 'Syne', sans-serif; font-size: 1.05rem; font-weight: 800;
            text-align: center; margin-bottom: 6px;
        }
        .confirm-sous {
            font-size: .82rem; color: var(--gris-texte); text-align: center;
            margin-bottom: 20px; line-height: 1.55;
        }
 
        .confirm-recap {
            background: var(--gris-fond);
            border: 1px solid var(--gris-border);
            border-radius: 10px; padding: 14px; margin-bottom: 18px;
        }
        .recap-ligne {
            display: flex; gap: 8px; font-size: .82rem;
            padding: 5px 0; border-bottom: 1px solid var(--gris-border);
        }
        .recap-ligne:last-child { border-bottom: none; padding-bottom: 0; }
        .recap-label { color: var(--gris-texte); width: 90px; flex-shrink: 0; }
        .recap-val   { font-weight: 700; }
 
        .confirm-btns { display: flex; gap: 10px; }
 
        .btn-confirm-ok {
            flex: 1; padding: 11px; border: none; border-radius: 9px;
            background: var(--vert); color: #fff;
            font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: .88rem;
            cursor: pointer; transition: opacity .2s;
        }
        .btn-confirm-ok:hover { opacity: .88; }
 
        .btn-confirm-cancel {
            flex: 1; padding: 11px; border: 1px solid var(--gris-border);
            border-radius: 9px; background: transparent;
            font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: .88rem;
            color: var(--gris-texte); cursor: pointer; transition: background .2s;
        }
        .btn-confirm-cancel:hover { background: var(--gris-fond); color: var(--noir); }
 
        /* ── Modal refus ── */
        .refus-overlay {
            position: fixed; inset: 0;
            background: rgba(17,24,39,.5);
            z-index: 200;
            display: flex; align-items: flex-end; justify-content: center;
            padding: 20px;
            animation: fadeIn .2s ease;
        }
        .refus-box {
            background: var(--blanc); border-radius: 20px 20px 0 0;
            padding: 24px 24px 32px; width: 100%; max-width: 480px;
            box-shadow: 0 -10px 40px rgba(17,24,39,.15);
            animation: slideUp .25s ease;
        }
        .refus-handle {
            width: 32px; height: 4px; border-radius: 2px;
            background: var(--gris-border); margin: 0 auto 18px;
        }
        .refus-titre {
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: .97rem;
            margin-bottom: 14px;
        }
        .textarea-motif {
            width: 100%; padding: 10px 12px;
            border: 1px solid var(--gris-border); border-radius: 8px;
            background: var(--gris-fond); font-family: 'DM Sans', sans-serif;
            font-size: .87rem; color: var(--noir); resize: vertical;
            min-height: 80px; outline: none; margin-bottom: 14px;
            transition: border-color .2s;
        }
        .textarea-motif:focus { border-color: var(--rouge); background: var(--blanc); }
 
        /* ── Historique ── */
        .hist-ligne {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 0; border-bottom: 1px solid var(--gris-border);
        }
        .hist-ligne:last-child { border-bottom: none; }
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: .71rem; font-weight: 700; white-space: nowrap;
        }
        .badge-vert   { background: rgba(22,163,74,.12);  color: var(--vert); }
        .badge-rouge  { background: rgba(220,38,38,.10);  color: var(--rouge); }
        .badge-orange { background: rgba(217,119,6,.12);  color: var(--orange); }
        .badge-bleu   { background: rgba(27,79,155,.10);  color: var(--bleu); }
 
        /* ── État vide ── */
        .etat-vide {
            text-align: center; padding: 44px 20px;
            color: var(--gris-texte);
        }
        .etat-vide svg { width: 48px; height: 48px; opacity: .3; margin-bottom: 12px; }
        .etat-vide h3  { font-family: 'Syne', sans-serif; font-size: .97rem; margin-bottom: 6px; }
        .etat-vide p   { font-size: .82rem; }
 
        /* ── Responsive ── */
        @media (max-width: 600px) {
            .page { padding: 20px 18px; }
            .cand-top { flex-direction: column; }
        }
    </style>
</head>
<body>
 
<div class="page">
 
    <!-- En-tête -->
    <div class="header">
        <a href="accueil_entreprise.php" aria-label="Retour">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h1>Gestion des candidatures</h1>
    </div>
 
    <!-- Messages -->
    <?php if ($msg_ok) : ?>
    <div class="alert alert-ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
        <?php echo htmlspecialchars($msg_ok); ?>
    </div>
    <?php endif; ?>
    <?php if ($msg_err) : ?>
    <div class="alert alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php echo htmlspecialchars($msg_err); ?>
    </div>
    <?php endif; ?>
 
    <!-- Liste des candidatures en attente -->
    <p class="section-label">
        Candidatures en attente
        <?php if (!empty($candidatures)) : ?>
        <span style="background:var(--orange); color:#fff; padding:2px 8px; border-radius:20px; font-size:.68rem; margin-left:6px;">
            <?php echo count($candidatures); ?>
        </span>
        <?php endif; ?>
    </p>
 
    <?php if (empty($candidatures)) : ?>
    <div class="etat-vide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <h3>Aucune candidature en attente</h3>
        <p>Les nouvelles candidatures apparaîtront ici.</p>
    </div>
 
    <?php else : ?>
 
    <?php foreach ($candidatures as $c) :
        $initiales = strtoupper(
            mb_substr(explode(' ', $c['nom_etudiant'])[0], 0, 1) .
            mb_substr(explode(' ', $c['nom_etudiant'])[1] ?? '?', 0, 1)
        );
        $date_fmt = $c['date_debut'] ? date('d/m/Y', strtotime($c['date_debut'])) : 'À définir';
    ?>
    <div class="cand-card">
        <div class="cand-top">
            <div class="cand-avatar"><?php echo $initiales; ?></div>
            <div class="cand-info">
                <p class="cand-nom"><?php echo htmlspecialchars($c['nom_etudiant']); ?></p>
                <p class="cand-sub">
                    <?php echo htmlspecialchars(implode(' · ', array_filter([$c['filiere'], $c['niveau']]))); ?>
                </p>
                <p class="cand-titre"><?php echo htmlspecialchars($c['titre']); ?></p>
            </div>
        </div>
 
        <div class="cand-meta">
            <span>
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Début : <?php echo $date_fmt; ?>
            </span>
            <?php if ($c['duree_semaines']) : ?>
            <span>
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?php echo (int)$c['duree_semaines']; ?> semaines
            </span>
            <?php endif; ?>
        </div>
 
        <div class="cand-actions">
            <!-- Bouton Valider → ouvre la modale de confirmation -->
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action"    value="demander_confirmation">
                <input type="hidden" name="num_stage" value="<?php echo (int)$c['num_stage']; ?>">
                <button type="submit" class="btn-valider">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Valider la candidature
                </button>
            </form>
 
            <!-- Bouton Refuser → ouvre la modale de refus -->
            <button class="btn-refuser"
                    onclick="ouvrirRefus(<?php echo (int)$c['num_stage']; ?>, '<?php echo htmlspecialchars($c['nom_etudiant'], ENT_QUOTES); ?>')">
                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Refuser
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
 
    <!-- Historique -->
    <?php if (!empty($historique)) : ?>
    <p class="section-label" style="margin-top:36px;">Historique récent</p>
    <div style="background:var(--blanc); border:1px solid var(--gris-border); border-radius:var(--radius); padding:16px; box-shadow:var(--shadow);">
        <?php foreach ($historique as $h) :
            $statut_cfg = [
                'acceptee_entreprise' => ['En attente étudiant', 'badge-orange'],
                'confirmee_etudiant'  => ['Confirmée',           'badge-vert'],
                'refusee_entreprise'  => ['Refusée par vous',    'badge-rouge'],
                'refusee_etudiant'    => ['Refusée par étudiant','badge-rouge'],
            ];
            [$lbl, $cls] = $statut_cfg[$h['statut_candidature']] ?? [$h['statut_candidature'], 'badge-bleu'];
        ?>
        <div class="hist-ligne">
            <div style="flex:1; min-width:0;">
                <p style="font-weight:700; font-size:.86rem;"><?php echo htmlspecialchars($h['nom_etudiant']); ?></p>
                <p style="font-size:.75rem; color:var(--gris-texte); margin-top:1px;"><?php echo htmlspecialchars($h['titre']); ?></p>
            </div>
            <span class="badge <?php echo $cls; ?>"><?php echo $lbl; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
 
</div><!-- /.page -->
 
 
<!-- ═══════════════════════════════════════════════════
     MODALE DE CONFIRMATION DE VALIDATION
═══════════════════════════════════════════════════ -->
<?php if ($confirm_data) : ?>
<div class="confirm-overlay" id="confirm-overlay">
    <div class="confirm-box">
        <div class="confirm-icon">
            <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <p class="confirm-titre">Confirmer la validation</p>
        <p class="confirm-sous">
            Vous êtes sur le point d'accepter la candidature de
            <strong><?php echo htmlspecialchars($confirm_data['nom_etudiant']); ?></strong>.
            L'étudiant devra confirmer de son côté avant que le stage soit officiellement créé.
        </p>
 
        <!-- Récap -->
        <div class="confirm-recap">
            <div class="recap-ligne">
                <span class="recap-label">Candidat</span>
                <span class="recap-val"><?php echo htmlspecialchars($confirm_data['nom_etudiant']); ?></span>
            </div>
            <div class="recap-ligne">
                <span class="recap-label">Filière</span>
                <span class="recap-val"><?php echo htmlspecialchars(implode(' · ', array_filter([$confirm_data['filiere'], $confirm_data['niveau']]))); ?></span>
            </div>
            <div class="recap-ligne">
                <span class="recap-label">Poste</span>
                <span class="recap-val"><?php echo htmlspecialchars($confirm_data['titre']); ?></span>
            </div>
            <div class="recap-ligne">
                <span class="recap-label">Durée</span>
                <span class="recap-val"><?php echo $confirm_data['duree_semaines'] ? (int)$confirm_data['duree_semaines'] . ' semaines' : 'À définir'; ?></span>
            </div>
            <div class="recap-ligne">
                <span class="recap-label">Début</span>
                <span class="recap-val"><?php echo $confirm_data['date_debut'] ? date('d/m/Y', strtotime($confirm_data['date_debut'])) : 'À définir'; ?></span>
            </div>
        </div>
 
        <div class="confirm-btns">
            <form method="POST" style="flex:1;">
                <input type="hidden" name="action"    value="confirmer_validation">
                <input type="hidden" name="num_stage" value="<?php echo (int)$confirm_data['num_stage']; ?>">
                <button type="submit" class="btn-confirm-ok" style="width:100%;">
                    ✓ Confirmer la validation
                </button>
            </form>
            <button type="button" class="btn-confirm-cancel"
                    onclick="document.getElementById('confirm-overlay').remove()">
                Annuler
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
 
 
<!-- ═══════════════════════════════════════════════════
     MODALE DE REFUS (bottom sheet)
═══════════════════════════════════════════════════ -->
<div id="refus-overlay" class="refus-overlay" style="display:none;">
    <div class="refus-box">
        <div class="refus-handle"></div>
        <p class="refus-titre" id="refus-titre">Refuser la candidature</p>
        <p style="font-size:.82rem; color:var(--gris-texte); margin-bottom:14px; line-height:1.5;">
            L'étudiant sera notifié du refus. Vous pouvez indiquer un motif (optionnel).
        </p>
        <form method="POST" id="refus-form">
            <input type="hidden" name="action"    value="refuser">
            <input type="hidden" name="num_stage" id="refus-stage-id">
            <textarea class="textarea-motif" name="motif"
                      placeholder="Motif du refus (optionnel)…"></textarea>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn-valider" style="background:var(--rouge); flex:1;">
                    Confirmer le refus
                </button>
                <button type="button" onclick="fermerRefus()"
                        style="flex:1; padding:10px; border:1px solid var(--gris-border); border-radius:8px; background:transparent; font-family:'DM Sans',sans-serif; font-weight:600; font-size:.87rem; cursor:pointer; color:var(--gris-texte);">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>
 
<script>
function ouvrirRefus(numStage, nomEtudiant) {
    document.getElementById('refus-stage-id').value = numStage;
    document.getElementById('refus-titre').textContent = 'Refuser — ' + nomEtudiant;
    document.getElementById('refus-overlay').style.display = 'flex';
}
function fermerRefus() {
    document.getElementById('refus-overlay').style.display = 'none';
}
document.getElementById('refus-overlay').addEventListener('click', function(e) {
    if (e.target === this) fermerRefus();
});
</script>
 
</body>
</html>
