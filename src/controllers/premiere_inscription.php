<?php
// =============================================================
//  REGISTER.PHP — Inscription avec formulaire dynamique
// =============================================================
session_start();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $db_host = 'localhost';
    $db_user = 'userpro';
    $db_pass = 'projetStage26.';
    $db_name = 'cyStages';

    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) die("Erreur de connexion : " . mysqli_connect_error());
    mysqli_set_charset($conn, 'utf8mb4');

    // -- Champs communs
    $nom      = trim(mysqli_real_escape_string($conn, $_POST['nom']      ?? ''));
    $prenom   = trim(mysqli_real_escape_string($conn, $_POST['prenom']   ?? ''));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']    ?? ''));
    $mdp_raw  = $_POST['mot_de_passe'] ?? '';
    $role     = $_POST['role_premier'] ?? '';

    $roles_valides = ['Etudiant', 'Tuteur', 'Jury', 'Entreprise'];
    if (!in_array($role, $roles_valides)) {
        $error = 'Rôle invalide.';
    } elseif (empty($nom) || empty($prenom) || empty($email) || empty($mdp_raw)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($mdp_raw) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $mot_de_passe = password_hash($mdp_raw, PASSWORD_BCRYPT);

        // -- Champs selon le rôle
        $filiere     = $annee_promo = $niveau      = null;
        $specialite  = $departement = $commission  = $annee_jury  = null;
        $num_siret   = $nom_ent     = $secteur     = $adresse     = null;
        $ville       = $code_postal = $site_web    = null;
        $nb_stagiere = 0;

        if ($role === 'Etudiant') {
            $filiere     = mysqli_real_escape_string($conn, $_POST['filiere']     ?? '');
            $niveau      = mysqli_real_escape_string($conn, $_POST['niveau']      ?? '');
            $annee_promo = (int)($_POST['annee_promo'] ?? 0);
        } elseif ($role === 'Tuteur') {
            $specialite  = mysqli_real_escape_string($conn, $_POST['specialite']  ?? '');
            $departement = mysqli_real_escape_string($conn, $_POST['departement'] ?? '');
        } elseif ($role === 'Jury') {
            $specialite  = mysqli_real_escape_string($conn, $_POST['specialite']  ?? '');
            $commission  = mysqli_real_escape_string($conn, $_POST['commission']  ?? '');
            $annee_jury  = (int)($_POST['annee_jury'] ?? 0);
        } elseif ($role === 'Entreprise') {
            $num_siret   = mysqli_real_escape_string($conn, $_POST['num_siret']   ?? '');
            $nom_ent     = mysqli_real_escape_string($conn, $_POST['nom_entreprise'] ?? '');
            $secteur     = mysqli_real_escape_string($conn, $_POST['secteur']     ?? '');
            $adresse     = mysqli_real_escape_string($conn, $_POST['adresse']     ?? '');
            $ville       = mysqli_real_escape_string($conn, $_POST['ville']       ?? '');
            $code_postal = mysqli_real_escape_string($conn, $_POST['code_postal'] ?? '');
            $site_web    = mysqli_real_escape_string($conn, $_POST['site_web']    ?? '');
            $nb_stagiere = (int)($_POST['nb_stagiere'] ?? 0);
        }

        // -- Vérifier doublon email
        $check = mysqli_query($conn, "SELECT id FROM Utilisateur WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            $sql = "INSERT INTO Utilisateur (
                        nom, prenom, email, mot_de_passe, actif,
                        role_premier,
                        filiere, niveau, annee_promo,
                        specialite, departement, commission, annee_jury,
                        num_siret, nom_entreprise, secteur, adresse, ville, code_postal, site_web, nb_stagiere
                    ) VALUES (
                        '$nom', '$prenom', '$email', '$mot_de_passe', 1,
                        '$role',
                        " . ($filiere     ? "'$filiere'"     : 'NULL') . ",
                        " . ($niveau      ? "'$niveau'"      : 'NULL') . ",
                        " . ($annee_promo ? "$annee_promo"   : 'NULL') . ",
                        " . ($specialite  ? "'$specialite'"  : 'NULL') . ",
                        " . ($departement ? "'$departement'" : 'NULL') . ",
                        " . ($commission  ? "'$commission'"  : 'NULL') . ",
                        " . ($annee_jury  ? "$annee_jury"    : 'NULL') . ",
                        " . ($num_siret   ? "'$num_siret'"   : 'NULL') . ",
                        " . ($nom_ent     ? "'$nom_ent'"     : 'NULL') . ",
                        " . ($secteur     ? "'$secteur'"     : 'NULL') . ",
                        " . ($adresse     ? "'$adresse'"     : 'NULL') . ",
                        " . ($ville       ? "'$ville'"       : 'NULL') . ",
                        " . ($code_postal ? "'$code_postal'" : 'NULL') . ",
                        " . ($site_web    ? "'$site_web'"    : 'NULL') . ",
                        $nb_stagiere
                    )";

            if (mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                $_SESSION['user_id']   = $new_id;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_nom']  = $prenom . ' ' . $nom;

                // Redirection selon le rôle
                $redirects = [
                    'Etudiant'   => 'accueil_etudiant.php',
                    'Tuteur'     => 'accueil_tuteur.php',
                    'Jury'       => 'accueil_jury.php',
                    'Entreprise' => 'accueil_entreprise.php',
                    'Admin'      => 'accueil_admin.php',
                ];
                mysqli_close($conn);
                header('Location: ' . ($redirects[$role] ?? 'index.php'));
                exit;
            } else {
                $error = 'Erreur lors de l\'inscription : ' . mysqli_error($conn);
            }
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
    <title>CyTech — Inscription</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Montserrat+Alternates:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/style_premiereco.css">
    <link rel="stylesheet" href="../../public/assets/css/style_index.css">
    <style>
        
    </style>
</head>
<body>

<div class="register-page">

    <!-- ══ GAUCHE ══ -->
    <div class="register-left">
        <a href="index.php" class="nav-logo">
       <img class="logo" src="../../public/assets/img/logo.png">
        </a>


        <div class="left-content">
            <h2>Rejoignez la plateforme de stages CY Tech</h2>
            <p>Créez votre compte en quelques étapes et accédez à votre espace personnalisé selon votre rôle.</p>
            <div class="left-roles">
                <div class="left-role-item">
                    <span class="left-role-icon">🎓</span>
                    <div>
                        <div class="left-role-label">Étudiant</div>
                        <div class="left-role-desc">Postulez aux offres et suivez votre dossier</div>
                    </div>
                </div>
                <div class="left-role-item">
                    <span class="left-role-icon">🏢</span>
                    <div>
                        <div class="left-role-label">Entreprise</div>
                        <div class="left-role-desc">Déposez des offres et accueillez des stagiaires</div>
                    </div>
                </div>
                <div class="left-role-item">
                    <span class="left-role-icon">🧑‍🏫</span>
                    <div>
                        <div class="left-role-label">Tuteur</div>
                        <div class="left-role-desc">Suivez et accompagnez vos étudiants</div>
                    </div>
                </div>
                <div class="left-role-item">
                    <span class="left-role-icon">⚖️</span>
                    <div>
                        <div class="left-role-label">Jury</div>
                        <div class="left-role-desc">Évaluez les dossiers et soutenances</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="left-bottom">CY Tech · Projet Dev Web ING1 · 2025-2026</div>
    </div>

    <!-- ══ DROITE ══ -->
    <div class="register-right">
        <div class="register-right-inner">

            <h1>Créer un compte</h1>
            <p class="subtitle">Déjà inscrit ? <a href="../../public/login.php" onclick="window.location='index.php'">Se connecter</a></p>

            <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- BARRE DE PROGRESSION -->
            <div class="steps-bar" id="stepsBar">
                <div class="step-item active" id="step-ind-1">
                    <div class="step-circle">1</div>
                    <span>Mon rôle</span>
                </div>
                <div class="step-line" id="line-1"></div>
                <div class="step-item" id="step-ind-2">
                    <div class="step-circle">2</div>
                    <span>Mon profil</span>
                </div>
                <div class="step-line" id="line-2"></div>
                <div class="step-item" id="step-ind-3">
                    <div class="step-circle">3</div>
                    <span>Confirmation</span>
                </div>
            </div>

            <form method="POST" action="premiere_inscription.php" id="registerForm">

                <!-- ══ ÉTAPE 1 : CHOIX DU RÔLE ══ -->
                <div id="step1">
                    <p style="font-size:13px;color:var(--texte-muted);margin-bottom:1rem;">Quel est votre rôle sur la plateforme ?</p>
                    <div class="role-grid">
                        <div class="role-card" onclick="selectRole('Etudiant', this)">
                            <div class="role-card-icon">🎓</div>
                            <div class="role-card-label">Étudiant</div>
                            <div class="role-card-desc">Je recherche un stage</div>
                        </div>
                        <div class="role-card" onclick="selectRole('Entreprise', this)">
                            <div class="role-card-icon">🏢</div>
                            <div class="role-card-label">Entreprise</div>
                            <div class="role-card-desc">Je propose des stages</div>
                        </div>
                        <div class="role-card" onclick="selectRole('Tuteur', this)">
                            <div class="role-card-icon">🧑‍🏫</div>
                            <div class="role-card-label">Tuteur</div>
                            <div class="role-card-desc">Je suis des étudiants</div>
                        </div>
                        <div class="role-card" onclick="selectRole('Jury', this)">
                            <div class="role-card-icon">⚖️</div>
                            <div class="role-card-label">Jury</div>
                            <div class="role-card-desc">J'évalue les dossiers</div>
                        </div>
                    </div>
                    <input type="hidden" name="role_premier" id="role_premier" value="">
                    <div class="form-actions">
                        <button type="button" class="btn-next" onclick="goStep2()" style="width:100%">Continuer →</button>
                    </div>
                </div>

                <!-- ══ ÉTAPE 2 : FORMULAIRE ══ -->
                <div id="step2" style="display:none">

                    <!-- Champs communs -->
                    <div class="form-section-title">Informations personnelles</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" placeholder="Dupont" required>
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" placeholder="Jean" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Adresse e-mail *</label>
                        <input type="email" name="email" placeholder="prenom.nom@cy-tech.fr" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe *</label>
                        <input type="password" name="mot_de_passe" placeholder="Min. 6 caractères" required>
                    </div>

                    <!-- Champs ÉTUDIANT -->
                    <div class="field-block" id="fields-Etudiant">
                        <div class="form-section-title">🎓 Profil étudiant</div>
                        <div class="form-group">
                            <label>Filière *</label>
                            <input type="text" name="filiere" placeholder="Ex: Informatique, Mathématiques...">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Niveau *</label>
                                <input type="text" name="niveau" placeholder="Ex: ING1, L3, M1...">
                            </div>
                            <div class="form-group">
                                <label>Année de promo *</label>
                                <input type="number" name="annee_promo" placeholder="2026" min="2020" max="2035">
                            </div>
                        </div>
                    </div>

                    <!-- Champs TUTEUR -->
                    <div class="field-block" id="fields-Tuteur">
                        <div class="form-section-title">🧑‍🏫 Profil tuteur</div>
                        <div class="form-group">
                            <label>Spécialité *</label>
                            <input type="text" name="specialite" placeholder="Ex: Informatique, Mathématiques...">
                        </div>
                        <div class="form-group">
                            <label>Département *</label>
                            <input type="text" name="departement" placeholder="Ex: Département Informatique">
                        </div>
                    </div>

                    <!-- Champs JURY -->
                    <div class="field-block" id="fields-Jury">
                        <div class="form-section-title">⚖️ Profil jury</div>
                        <div class="form-group">
                            <label>Spécialité *</label>
                            <input type="text" name="specialite" placeholder="Ex: Informatique & IA">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Commission *</label>
                                <input type="text" name="commission" placeholder="Ex: Commission Ingénierie">
                            </div>
                            <div class="form-group">
                                <label>Année jury *</label>
                                <input type="number" name="annee_jury" placeholder="2026" min="2020" max="2035">
                            </div>
                        </div>
                    </div>

                    <!-- Champs ENTREPRISE -->
                    <div class="field-block" id="fields-Entreprise">
                        <div class="form-section-title">🏢 Profil entreprise</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom de l'entreprise *</label>
                                <input type="text" name="nom_entreprise" placeholder="TechCorp SAS">
                            </div>
                            <div class="form-group">
                                <label>N° SIRET *</label>
                                <input type="text" name="num_siret" placeholder="14 chiffres" maxlength="14">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Secteur *</label>
                                <input type="text" name="secteur" placeholder="Ex: Informatique">
                            </div>
                            <div class="form-group">
                                <label>Ville *</label>
                                <input type="text" name="ville" placeholder="Paris">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Adresse</label>
                            <input type="text" name="adresse" placeholder="12 rue de la Paix">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Code postal</label>
                                <input type="text" name="code_postal" placeholder="75001" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label>Site web</label>
                                <input type="url" name="site_web" placeholder="https://...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Capacité stagiaires</label>
                            <input type="number" name="nb_stagiere" placeholder="0" min="0" max="255" value="0">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-back" onclick="goStep1()">← Retour</button>
                        <button type="button" class="btn-next" onclick="goStep3()">Vérifier →</button>
                    </div>
                </div>

                <!-- ══ ÉTAPE 3 : CONFIRMATION ══ -->
                <div id="step3" style="display:none">
                    <div class="form-section-title">Récapitulatif</div>
                    <div id="recap" style="background:var(--blanc);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;font-size:13px;line-height:2;color:var(--texte-muted);margin-bottom:1.5rem;"></div>
                    <div class="form-actions">
                        <button type="button" class="btn-back" onclick="goStep2()">← Modifier</button>
                        <button type="submit" class="btn-next">Créer mon compte ✓</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
let selectedRole = '';

function selectRole(role, el) {
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedRole = role;
    document.getElementById('role_premier').value = role;
}

function goStep2() {
    if (!selectedRole) {
        alert('Veuillez sélectionner un rôle.');
        return;
    }
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';

    // Afficher les champs du rôle sélectionné
    document.querySelectorAll('.field-block').forEach(f => f.classList.remove('visible'));
    const block = document.getElementById('fields-' + selectedRole);
    if (block) block.classList.add('visible');

    updateSteps(2);
}

function goStep1() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    updateSteps(1);
}

function goStep3() {
    const form = document.getElementById('registerForm');
    const nom    = form.querySelector('[name="nom"]').value.trim();
    const prenom = form.querySelector('[name="prenom"]').value.trim();
    const email  = form.querySelector('[name="email"]').value.trim();
    const mdp    = form.querySelector('[name="mot_de_passe"]').value;

    if (!nom || !prenom || !email || !mdp) {
        alert('Veuillez remplir tous les champs obligatoires.');
        return;
    }
    if (mdp.length < 6) {
        alert('Le mot de passe doit contenir au moins 6 caractères.');
        return;
    }

    // Construire le récap
    const roleLabels = {
        Etudiant: '🎓 Étudiant', Entreprise: '🏢 Entreprise',
        Tuteur: '🧑‍🏫 Tuteur', Jury: '⚖️ Jury'
    };

    let html = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 20px">
            <span style="color:var(--texte-muted)">Rôle</span>
            <strong style="color:var(--texte)">${roleLabels[selectedRole] || selectedRole}</strong>
            <span style="color:var(--texte-muted)">Nom</span>
            <strong style="color:var(--texte)">${prenom} ${nom}</strong>
            <span style="color:var(--texte-muted)">Email</span>
            <strong style="color:var(--texte)">${email}</strong>
            <span style="color:var(--texte-muted)">Mot de passe</span>
            <strong style="color:var(--texte)">••••••••</strong>
        </div>`;

    document.getElementById('recap').innerHTML = html;
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'block';
    updateSteps(3);
}

function updateSteps(active) {
    for (let i = 1; i <= 3; i++) {
        const el   = document.getElementById('step-ind-' + i);
        const line = document.getElementById('line-' + i);
        el.classList.remove('active', 'done');
        if (i < active)       el.classList.add('done');
        else if (i === active) el.classList.add('active');
        if (line) {
            line.classList.toggle('done', i < active);
        }
    }
}
</script>
</body>
</html>