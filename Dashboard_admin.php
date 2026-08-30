<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';
require __DIR__ . '/includes/courriel.php';
require __DIR__ . '/includes/messages_contact.php';

// LA BARRIÈRE. Première instruction exécutable de la page, avant le
// moindre affichage : si la personne n'est pas un administrateur
// connecté, elle est redirigée ou refusée, et rien ne s'affiche.
$utilisateur = exiger_role('Administrateur');

require __DIR__ . '/includes/mongo.php';

// MongoDB ne stocke que des identifiants d'animaux et un nombre de vues.
// Les noms sont dans MySQL. Les deux bases ne se parlent pas : c'est PHP
// qui interroge l'une puis l'autre et assemble le résultat.
$vues = consultations();                     // [id => nombre de vues], déjà trié

$nomsAnimaux = [];
foreach (db()->query('SELECT id, name FROM animals') as $ligne) {
    $nomsAnimaux[(int) $ligne['id']] = $ligne['name'];
}

// La fiche la plus consultée sert de référence : sa barre fera 100 %,
// les autres seront proportionnelles.
$vueMax = $vues ? max($vues) : 0;

// Onglet à rouvrir après chaque action.
$ongletActif = $_GET['onglet'] ?? 'users';

// Les rôles proposables. L'administrateur ne crée que des employés et des
// vétérinaires : son propre profil s'obtient en ligne de commande, ce qui
// évite qu'un compte admin compromis en fabrique d'autres.
$requete = db()->prepare('SELECT id, label FROM roles WHERE label <> ? ORDER BY id');
$requete->execute(['Administrateur']);
$rolesProposables = $requete->fetchAll();

$recherche = trim($_GET['q'] ?? '');

$sql = 'SELECT u.id, u.email, u.first_name, u.last_name, u.is_active, u.created_at,
               r.label AS role
        FROM users u
        JOIN roles r ON r.id = u.role_id';
$parametres = [];

if ($recherche !== '') {
    $sql .= ' WHERE u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?';
    $motif = '%' . $recherche . '%';
    $parametres = [$motif, $motif, $motif];
}

$sql .= ' ORDER BY u.id';

$requete = db()->prepare($sql);
$requete->execute($parametres);
$utilisateurs = $requete->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'user_creer') {
        $email      = trim($_POST['email'] ?? '');
        $motDePasse = $_POST['password'] ?? '';
        $prenom     = trim($_POST['first_name'] ?? '');
        $nom        = trim($_POST['last_name'] ?? '');
        $roleId     = (int) ($_POST['role_id'] ?? 0);

        $idsAutorises = array_map('intval', array_column($rolesProposables, 'id'));

        $valide = filter_var($email, FILTER_VALIDATE_EMAIL)
            && strlen($motDePasse) >= 8
            && $prenom !== ''
            && $nom !== ''
            && in_array($roleId, $idsAutorises, true);

        if ($valide) {
            try {
                $requete = db()->prepare(
                    'INSERT INTO users (email, password_hash, first_name, last_name, role_id)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $requete->execute([
                    $email,
                    password_hash($motDePasse, PASSWORD_DEFAULT),
                    $prenom,
                    $nom,
                    $roleId,
                ]);

                // US 6 : l'utilisateur reçoit son identifiant par courriel.
                // Le mot de passe n'y figure JAMAIS : il lui sera remis de
                // vive voix par l'administrateur.
                envoyer_courriel(
                    $email,
                    'Votre accès au Zoo d’Arcadia',
                    "Bonjour {$prenom},\n\n"
                    . "Un compte vient de vous être créé sur l'application du Zoo d'Arcadia.\n\n"
                    . "Votre identifiant de connexion : {$email}\n\n"
                    . "Pour des raisons de sécurité, votre mot de passe ne vous est pas\n"
                    . "communiqué par courriel. Rapprochez-vous de l'administrateur pour\n"
                    . "l'obtenir.\n\n"
                    . "— Zoo d'Arcadia"
                );

                header('Location: Dashboard_admin.php?onglet=users&user=cree');
                exit;
            } catch (PDOException $e) {
                // 23000 = contrainte violée. Ici, l'unicité du courriel.
                $motif = $e->getCode() === '23000' ? 'doublon' : 'erreur';
                header('Location: Dashboard_admin.php?onglet=users&user=' . $motif);
                exit;
            }
        }

        header('Location: Dashboard_admin.php?onglet=users&user=invalide');
        exit;
    }

    if ($action === 'user_actif') {
        $id = (int) ($_POST['user_id'] ?? 0);

        // On ne se désactive pas soi-même : l'administrateur connecté se
        // couperait l'accès à son propre espace.
        if ($id > 0 && $id !== (int) $utilisateur['id']) {
            $requete = db()->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?');
            $requete->execute([$id]);
        }

        header('Location: Dashboard_admin.php?onglet=users&user=bascule');
        exit;
    }

    if ($action === 'user_suppr') {
        $id = (int) ($_POST['user_id'] ?? 0);

        if ($id > 0 && $id !== (int) $utilisateur['id']) {
            try {
                $requete = db()->prepare('DELETE FROM users WHERE id = ?');
                $requete->execute([$id]);
            } catch (PDOException $e) {
                // Ce compte a signé des comptes rendus ou des repas : la clé
                // étrangère refuse la suppression pour ne pas perdre
                // l'historique. On propose la désactivation à la place.
                header('Location: Dashboard_admin.php?onglet=users&user=lie');
                exit;
            }
        }

        header('Location: Dashboard_admin.php?onglet=users&user=supprime');
        exit;
    }
    
    // Boîte de réception partagée avec l'espace employé : le traitement
    // vit dans includes/messages_contact.php.
    traiter_action_message('Dashboard_admin.php');

    if ($action === 'animal_enregistrer') {
        $id          = (int) ($_POST['animal_id'] ?? 0);
        $nom         = trim($_POST['name'] ?? '');
        $espece      = trim($_POST['species'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nourriture  = trim($_POST['diet'] ?? '');
        $etat        = trim($_POST['health_state'] ?? '');
        $image       = trim($_POST['image'] ?? '');
        $imageAlt    = trim($_POST['image_alt'] ?? '');
        $habitatId   = (int) ($_POST['habitat_id'] ?? 0);
    
        if ($nom === '' || $espece === '' || $habitatId <= 0) {
            header('Location: Dashboard_admin.php?onglet=content&animal=invalide');
            exit;
        }
    
        if ($id > 0) {
            // Modification d'un animal existant.
            $requete = db()->prepare(
                'UPDATE animals
                 SET name = ?, species = ?, description = ?, diet = ?,
                     health_state = ?, image = ?, image_alt = ?, habitat_id = ?
                 WHERE id = ?'
            );
            $requete->execute([
                $nom, $espece, $description, $nourriture, $etat,
                $image !== '' ? $image : null,
                $imageAlt !== '' ? $imageAlt : null,
                $habitatId, $id,
            ]);
    
            header('Location: Dashboard_admin.php?onglet=content&animal_id=' . $id . '&animal=modifie');
            exit;
        }
    
        // Création.
        $requete = db()->prepare(
            'INSERT INTO animals
               (name, species, description, diet, health_state, image, image_alt, habitat_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $requete->execute([
            $nom, $espece, $description, $nourriture, $etat,
            $image !== '' ? $image : null,
            $imageAlt !== '' ? $imageAlt : null,
            $habitatId,
        ]);
    
        header('Location: Dashboard_admin.php?onglet=content&animal_id=' . db()->lastInsertId() . '&animal=cree');
        exit;
    }
    
    if ($action === 'animal_supprimer') {
        $id = (int) ($_POST['animal_id'] ?? 0);
    
        if ($id > 0) {
            // Les comptes rendus et les repas de cet animal partent avec,
            // grâce au ON DELETE CASCADE de ces deux tables. Son compteur
            // MongoDB, lui, subsiste : les deux bases sont indépendantes.
            $requete = db()->prepare('DELETE FROM animals WHERE id = ?');
            $requete->execute([$id]);
        }
    
        header('Location: Dashboard_admin.php?onglet=content&animal=supprime');
        exit;
    }
    
    if ($action === 'service_enregistrer') {
        $id          = (int) ($_POST['service_id'] ?? 0);
        $nom         = trim($_POST['name'] ?? '');
        $categorie   = $_POST['category'] ?? 'visite';
        $horaires    = trim($_POST['schedule'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Liste blanche : seules ces deux catégories existent à l'affichage.
        if (!in_array($categorie, ['visite', 'restauration'], true)) {
            $categorie = 'visite';
        }

        if ($nom === '') {
            header('Location: Dashboard_admin.php?onglet=content&service=invalide');
            exit;
        }

        // Le slug sert d'ancre dans l'URL de Services.php : on le fabrique
        // à partir du nom, sans accents ni caractères spéciaux.
        $slug = strtolower(trim(preg_replace(
            '/[^a-z0-9]+/i',
            '-',
            iconv('UTF-8', 'ASCII//TRANSLIT', $nom) ?: $nom
        ), '-'));

        if ($id > 0) {
            $requete = db()->prepare(
                'UPDATE services SET name = ?, category = ?, slug = ?, schedule = ?, description = ?
                 WHERE id = ?'
            );
            $requete->execute([$nom, $categorie, $slug, $horaires, $description, $id]);

            header('Location: Dashboard_admin.php?onglet=content&service_id=' . $id . '&service=modifie');
            exit;
        }

        $requete = db()->prepare(
            'INSERT INTO services (name, category, slug, schedule, description)
             VALUES (?, ?, ?, ?, ?)'
        );
        $requete->execute([$nom, $categorie, $slug, $horaires, $description]);

        header('Location: Dashboard_admin.php?onglet=content&service_id=' . db()->lastInsertId() . '&service=cree');
        exit;
    }

    if ($action === 'service_supprimer') {
        $id = (int) ($_POST['service_id'] ?? 0);

        if ($id > 0) {
            // Les images liées partent avec, grâce au ON DELETE CASCADE.
            $requete = db()->prepare('DELETE FROM services WHERE id = ?');
            $requete->execute([$id]);
        }

        header('Location: Dashboard_admin.php?onglet=content&service=supprime');
        exit;
    }

    if ($action === 'habitat_maj') {
        $id          = (int) ($_POST['habitat_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image       = trim($_POST['image'] ?? '');
    
        if ($id > 0) {
            $requete = db()->prepare('UPDATE habitats SET description = ?, image = ? WHERE id = ?');
            $requete->execute([$description, $image !== '' ? $image : null, $id]);
    
            header('Location: Dashboard_admin.php?onglet=content&habitat_id=' . $id . '&habitat=ok');
            exit;
        }
    
        header('Location: Dashboard_admin.php?onglet=content&habitat=erreur');
        exit;
    }
}

// --- Comptes rendus vétérinaires, avec filtres -----------------------
$filtreAnimal = trim($_GET['animal'] ?? '');
$filtreDu     = $_GET['du'] ?? '';
$filtreAu     = $_GET['au'] ?? '';

$sqlRapports = 'SELECT r.visit_date, r.animal_state, r.proposed_food, r.food_grams,
                       r.state_details, a.name AS animal, u.first_name AS veterinaire
                FROM veterinary_reports r
                JOIN animals a ON a.id = r.animal_id
                JOIN users u   ON u.id = r.veterinarian_id';

$conditions         = [];
$parametresRapports = [];

if ($filtreAnimal !== '') {
    $conditions[]         = 'a.name LIKE ?';
    $parametresRapports[] = '%' . $filtreAnimal . '%';
}

// Un champ <input type="date"> renvoie toujours AAAA-MM-JJ. On refuse
// tout ce qui ne ressemble pas à ça, plutôt que de le transmettre à MySQL.
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtreDu)) {
    $conditions[]         = 'r.visit_date >= ?';
    $parametresRapports[] = $filtreDu;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtreAu)) {
    $conditions[]         = 'r.visit_date <= ?';
    $parametresRapports[] = $filtreAu;
}

if ($conditions) {
    $sqlRapports .= ' WHERE ' . implode(' AND ', $conditions);
}

$sqlRapports .= ' ORDER BY r.visit_date DESC, r.id DESC LIMIT 100';

$requete = db()->prepare($sqlRapports);
$requete->execute($parametresRapports);
$rapports = $requete->fetchAll();

// --- Messages du formulaire de contact -------------------------------
// Les non-lus remontent en premier, puis les plus récents.
$messagesContact = messages_contact();
$nonLus          = messages_non_lus($messagesContact);
$pageCourante    = 'Dashboard_admin.php';

// --- Contenu du zoo : animaux ----------------------------------------
$tousLesAnimaux = db()->query(
    'SELECT a.id, a.name, h.name AS habitat
     FROM animals a
     JOIN habitats h ON h.id = a.habitat_id
     ORDER BY h.name, a.name'
)->fetchAll();

$tousLesHabitats = db()->query('SELECT id, name FROM habitats ORDER BY name')->fetchAll();

// L'animal en cours d'édition. 0 ou absent = on est en création.
$animalEdite = null;
$idAnimal    = (int) ($_GET['animal_id'] ?? 0);

if ($idAnimal > 0) {
    $requete = db()->prepare(
        'SELECT id, name, species, description, diet, health_state, image, image_alt, habitat_id
         FROM animals WHERE id = ?'
    );
    $requete->execute([$idAnimal]);
    $animalEdite = $requete->fetch() ?: null;
}

// --- Contenu du zoo : services (US 3 et US 6) ------------------------
$tousLesServices = db()->query(
    'SELECT id, name, category, schedule FROM services ORDER BY category, id'
)->fetchAll();

// Le service en cours d'édition. 0 ou absent = on est en création.
$serviceEdite = null;
$idService    = (int) ($_GET['service_id'] ?? 0);

if ($idService > 0) {
    $requete = db()->prepare(
        'SELECT id, name, category, slug, schedule, description FROM services WHERE id = ?'
    );
    $requete->execute([$idService]);
    $serviceEdite = $requete->fetch() ?: null;
}

// --- Contenu du zoo : habitats ---------------------------------------
$habitatEdite = null;
$idHabitat    = (int) ($_GET['habitat_id'] ?? 0);

if ($idHabitat > 0) {
    $requete = db()->prepare('SELECT id, name, description, image FROM habitats WHERE id = ?');
    $requete->execute([$idHabitat]);
    $habitatEdite = $requete->fetch() ?: null;
}

$titrePage  = 'Espace Administrateur — Zoo d’Arcadia';
$classeBody = '';

require __DIR__ . '/includes/header.php';
?>

  <!-- LAYOUT -->
  <main class="admin-layout py-4">
    <div class="container">
      <div class="row g-3">
        
        <!-- Sidebar -->
        <aside class="col-12 col-lg-3">
          <div class="card p-3">
            <h1 class="h5 mb-3">Espace Administrateur</h1>
            <div class="nav flex-lg-column nav-pills gap-2" id="adminTabs" role="tablist" aria-orientation="vertical">
              <button class="nav-link<?= $ongletActif === 'users' ? ' active' : '' ?>" id="tab-users" data-bs-toggle="pill" data-bs-target="#pane-users" type="button" role="tab">
                <i class="bi bi-people me-1"></i> Utilisateurs
              </button>
              <button class="nav-link<?= $ongletActif === 'content' ? ' active' : '' ?>" id="tab-content" data-bs-toggle="pill" data-bs-target="#pane-content" type="button" role="tab">
                <i class="bi bi-pen me-1"></i> Contenu du zoo
              </button>
              <button class="nav-link<?= $ongletActif === 'reports' ? ' active' : '' ?>" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button" role="tab">
                <i class="bi bi-clipboard2-pulse me-1"></i> Comptes rendus & Stats
              </button>
              <button class="nav-link<?= $ongletActif === 'messages' ? ' active' : '' ?>" id="tab-messages" data-bs-toggle="pill" data-bs-target="#pane-messages" type="button" role="tab">
                <i class="bi bi-envelope me-1"></i> Messages
                <?php if ($nonLus > 0): ?>
                  <span class="badge bg-danger ms-1"><?= (int) $nonLus ?></span>
                <?php endif; ?>
              </button>
            </div>
          </div>
        </aside>

        <!-- Contenu : début du tab-content-->
        <section class="col-12 col-lg-9">
          <div class="tab-content">

            <!-- ==== UTILISATEURS ==== -->
            <div class="tab-pane fade<?= $ongletActif === 'users' ? ' show active' : '' ?>" id="pane-users" role="tabpanel" aria-labelledby="tab-users">
              <div class="card p-4 mb-3">
                <h2 class="h5 mb-3">Créer un utilisateur (Employé / Vétérinaire)</h2>
                <?php if (isset($_GET['user'])): ?>
                  <?php
                    $messages = [
                      'cree'     => ['success',   'Compte créé. Communique le mot de passe de vive voix, jamais par courriel.'],
                      'doublon'  => ['danger',    'Cette adresse est déjà utilisée par un compte.'],
                      'invalide' => ['danger',    'Vérifie les champs : adresse valide, mot de passe de 8 caractères minimum, prénom, nom et rôle.'],
                      'bascule'  => ['secondary', 'Statut du compte modifié.'],
                      'supprime' => ['secondary', 'Compte supprimé.'],
                      'lie'      => ['warning',   'Ce compte a produit des comptes rendus ou des repas : il ne peut pas être supprimé. Désactive-le pour lui retirer l’accès.'],
                      'erreur'   => ['danger',    'Une erreur est survenue.'],
                    ];
                    [$couleur, $texte] = $messages[$_GET['user']] ?? ['secondary', ''];
                  ?>
                  <?php if ($texte !== ''): ?>
                    <div class="alert alert-<?= e($couleur) ?>" role="alert"><?= e($texte) ?></div>
                  <?php endif; ?>
                <?php endif; ?>
                
                <form class="row g-3" action="Dashboard_admin.php" method="post" novalidate>
                  <div class="col-12 col-md-6">
                    <label for="userFirstName" class="form-label">Prénom</label>
                    <input id="userFirstName" name="first_name" type="text" class="form-control" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label for="userLastName" class="form-label">Nom</label>
                    <input id="userLastName" name="last_name" type="text" class="form-control" required>
                  </div>
                
                  <div class="col-12 col-md-6">
                    <label for="userEmail" class="form-label">Courriel (identifiant)</label>
                    <input id="userEmail" name="email" type="email" class="form-control"
                           placeholder="nom@exemple.com" required autocomplete="email">
                  </div>
                
                  <div class="col-12 col-md-6">
                    <label for="userPassword" class="form-label">Mot de passe (non envoyé par mail)</label>
                    <input id="userPassword" name="password" type="password" class="form-control"
                           placeholder="8 caractères minimum" required autocomplete="new-password" minlength="8">
                  </div>
                
                  <div class="col-12 col-md-6">
                    <label for="userRole" class="form-label">Rôle</label>
                    <select id="userRole" name="role_id" class="form-select" required>
                      <option value="" selected disabled>Choisir…</option>
                      <?php foreach ($rolesProposables as $unRole): ?>
                        <option value="<?= (int) $unRole['id'] ?>"><?= e($unRole['label']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                
                  <div class="col-12">
                    <button type="submit" class="btn btn-brand" name="action" value="user_creer">
                      Créer l’utilisateur
                    </button>
                  </div>
                </form>
              </div>

              <div class="card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <h3 class="h6 m-0">Utilisateurs existants</h3>
                  <form action="Dashboard_admin.php" method="get"
                        class="input-group input-group-sm" style="max-width: 280px;">
                    <input type="hidden" name="onglet" value="users">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" value="<?= e($recherche) ?>"
                           class="form-control" placeholder="Rechercher…">
                    <button class="btn btn-outline-secondary" type="submit">OK</button>
                  </form>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead>
                      <tr>
                        <th>Courriel</th>
                        <th>Nom</th>
                        <th>Rôle</th>
                        <th>Créé le</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!$utilisateurs): ?>
                        <tr><td colspan="5" class="text-muted">Aucun utilisateur trouvé.</td></tr>
                      <?php endif; ?>
                    
                      <?php foreach ($utilisateurs as $unUtilisateur): ?>
                        <tr class="<?= $unUtilisateur['is_active'] ? '' : 'table-secondary' ?>">
                          <td>
                            <?= e($unUtilisateur['email']) ?>
                            <?php if (!$unUtilisateur['is_active']): ?>
                              <span class="badge bg-secondary ms-1">désactivé</span>
                            <?php endif; ?>
                          </td>
                          <td><?= e($unUtilisateur['first_name']) ?> <?= e($unUtilisateur['last_name']) ?></td>
                          <td><?= e($unUtilisateur['role']) ?></td>
                          <td><?= e(date('d/m/Y', strtotime($unUtilisateur['created_at']))) ?></td>
                          <td class="text-end text-nowrap">
                            <?php if ((int) $unUtilisateur['id'] === (int) $utilisateur['id']): ?>
                              <span class="small text-muted">vous</span>
                            <?php else: ?>
                              <form action="Dashboard_admin.php" method="post" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= (int) $unUtilisateur['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit"
                                        name="action" value="user_actif"
                                        title="<?= $unUtilisateur['is_active'] ? 'Désactiver' : 'Réactiver' ?>">
                                  <i class="bi bi-<?= $unUtilisateur['is_active'] ? 'pause' : 'play' ?>"></i>
                                </button>
                              </form>
                              <form action="Dashboard_admin.php" method="post" class="d-inline"
                                    onsubmit="return confirm('Supprimer définitivement ce compte ?');">
                                <input type="hidden" name="user_id" value="<?= (int) $unUtilisateur['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit"
                                        name="action" value="user_suppr" title="Supprimer">
                                  <i class="bi bi-trash"></i>
                                </button>
                              </form>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- ========== CONTENU DU ZOO ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'content' ? ' show active' : '' ?>" id="pane-content" role="tabpanel" aria-labelledby="tab-content">
              <div class="row g-3">
                <div class="col-12">
                  <article class="card p-4">
                    <h2 class="h5 mb-3">Services & Horaires</h2>

                    <?php if (isset($_GET['service'])): ?>
                      <?php
                        $msgService = [
                          'cree'     => ['success',   'Service créé.'],
                          'modifie'  => ['success',   'Service mis à jour.'],
                          'supprime' => ['secondary', 'Service supprimé.'],
                          'invalide' => ['danger',    'Le nom du service est obligatoire.'],
                        ];
                        [$couleurS, $texteS] = $msgService[$_GET['service']] ?? ['secondary', ''];
                      ?>
                      <?php if ($texteS !== ''): ?>
                        <div class="alert alert-<?= e($couleurS) ?>" role="alert"><?= e($texteS) ?></div>
                      <?php endif; ?>
                    <?php endif; ?>

                    <h3 class="h6 mb-3">
                      <?= $serviceEdite ? 'Modifier « ' . e($serviceEdite['name']) . ' »' : 'Ajouter un service' ?>
                      <?php if ($serviceEdite): ?>
                        <a href="Dashboard_admin.php?onglet=content" class="small ms-2">Ajouter un autre service</a>
                      <?php endif; ?>
                    </h3>

                    <form class="row g-3" action="Dashboard_admin.php" method="post">
                      <input type="hidden" name="service_id" value="<?= (int) ($serviceEdite['id'] ?? 0) ?>">

                      <div class="col-12 col-md-6">
                        <label for="serviceName" class="form-label">Nom du service</label>
                        <input id="serviceName" name="name" type="text" class="form-control"
                               value="<?= e($serviceEdite['name'] ?? '') ?>"
                               placeholder="Visite en petit train" required>
                      </div>

                      <div class="col-12 col-md-3">
                        <label for="serviceCategory" class="form-label">Catégorie</label>
                        <select id="serviceCategory" name="category" class="form-select">
                          <option value="visite" <?= ($serviceEdite['category'] ?? '') === 'visite' ? 'selected' : '' ?>>Visite</option>
                          <option value="restauration" <?= ($serviceEdite['category'] ?? '') === 'restauration' ? 'selected' : '' ?>>Restauration</option>
                        </select>
                      </div>

                      <div class="col-12 col-md-3">
                        <label for="serviceHours" class="form-label">Horaires</label>
                        <input id="serviceHours" name="schedule" type="text" class="form-control"
                               value="<?= e($serviceEdite['schedule'] ?? '') ?>" placeholder="9h – 19h">
                      </div>

                      <div class="col-12">
                        <label for="serviceDesc" class="form-label">Description</label>
                        <textarea id="serviceDesc" name="description" rows="3" class="form-control"
                                  placeholder="Texte de présentation…"><?= e($serviceEdite['description'] ?? '') ?></textarea>
                      </div>

                      <div class="col-12">
                        <button class="btn btn-brand" type="submit" name="action" value="service_enregistrer">
                          <?= $serviceEdite ? 'Mettre à jour' : 'Créer le service' ?>
                        </button>
                      </div>
                    </form>

                    <?php if ($serviceEdite): ?>
                      <form action="Dashboard_admin.php" method="post" class="mt-2"
                            onsubmit="return confirm('Supprimer ce service et ses photos ?');">
                        <input type="hidden" name="service_id" value="<?= (int) $serviceEdite['id'] ?>">
                        <button class="btn btn-outline-danger" type="submit" name="action" value="service_supprimer">
                          Supprimer ce service
                        </button>
                      </form>
                    <?php endif; ?>

                    <hr class="my-4">

                    <h3 class="h6 mb-3">Tous les services (<?= count($tousLesServices) ?>)</h3>
                    <div class="table-responsive">
                      <table class="table table-sm align-middle">
                        <thead><tr><th>Nom</th><th>Catégorie</th><th>Horaires</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                          <?php foreach ($tousLesServices as $unService): ?>
                            <tr<?= $serviceEdite && (int) $serviceEdite['id'] === (int) $unService['id'] ? ' class="table-active"' : '' ?>>
                              <td><?= e($unService['name']) ?></td>
                              <td><?= e($unService['category']) ?></td>
                              <td><?= e($unService['schedule'] ?? '—') ?></td>
                              <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="Dashboard_admin.php?onglet=content&service_id=<?= (int) $unService['id'] ?>">
                                  <i class="bi bi-pencil"></i>
                                </a>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </article>
                </div>

                <div class="col-12">
                  <article class="card p-4">
                    <h2 class="h5 mb-3">Habitats</h2>

                    <?php if (isset($_GET['habitat'])): ?>
                      <div class="alert alert-<?= $_GET['habitat'] === 'ok' ? 'success' : 'danger' ?>" role="alert">
                        <?= $_GET['habitat'] === 'ok' ? 'Habitat mis à jour.' : 'Habitat introuvable.' ?>
                      </div>
                    <?php endif; ?>

                    <form action="Dashboard_admin.php" method="get" class="row g-2 align-items-end mb-3">
                      <input type="hidden" name="onglet" value="content">
                      <div class="col-12 col-md-8">
                        <label for="habitatChoix" class="form-label">Habitat à modifier</label>
                        <select id="habitatChoix" name="habitat_id" class="form-select">
                          <?php foreach ($tousLesHabitats as $unHabitat): ?>
                            <option value="<?= (int) $unHabitat['id'] ?>"
                              <?= $habitatEdite && (int) $habitatEdite['id'] === (int) $unHabitat['id'] ? 'selected' : '' ?>>
                              <?= e($unHabitat['name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-12 col-md-4">
                        <button class="btn btn-outline-secondary w-100" type="submit">Charger</button>
                      </div>
                    </form>

                    <?php if ($habitatEdite): ?>
                      <form action="Dashboard_admin.php" method="post" class="row g-3">
                        <input type="hidden" name="habitat_id" value="<?= (int) $habitatEdite['id'] ?>">

                        <div class="col-12">
                          <label class="form-label">Nom</label>
                          <input type="text" class="form-control" value="<?= e($habitatEdite['name']) ?>" disabled>
                          <div class="form-text">
                            Le nom n’est pas modifiable : il détermine le fond de page et les liens du site.
                          </div>
                        </div>

                        <div class="col-12">
                          <label for="habitatDesc" class="form-label">Description</label>
                          <textarea id="habitatDesc" name="description" rows="3"
                                    class="form-control"><?= e($habitatEdite['description']) ?></textarea>
                        </div>

                        <div class="col-12">
                          <label for="habitatImage" class="form-label">Image</label>
                          <input id="habitatImage" name="image" type="text" class="form-control"
                                 value="<?= e($habitatEdite['image']) ?>"
                                 placeholder="images/nom-du-fichier.jpg">
                          <div class="form-text">Le fichier doit déjà se trouver dans le dossier <code>images/</code>.</div>
                        </div>

                        <div class="col-12">
                          <button class="btn btn-brand" type="submit" name="action" value="habitat_maj">
                            Mettre à jour
                          </button>
                        </div>
                      </form>
                    <?php else: ?>
                      <p class="text-muted mb-0">Choisis un habitat ci-dessus, puis clique sur « Charger ».</p>
                    <?php endif; ?>
                  </article>
                </div>

                <div class="col-12">
                  <article class="card p-4">
                    <h2 class="h5 mb-3">Animaux</h2>
                    <?php if (isset($_GET['animal'])): ?>
                      <?php
                        $msgAnimal = [
                          'cree'     => ['success',   'Animal créé.'],
                          'modifie'  => ['success',   'Animal mis à jour.'],
                          'supprime' => ['secondary', 'Animal supprimé.'],
                          'invalide' => ['danger',    'Le nom, l’espèce et l’habitat sont obligatoires.'],
                        ];
                        [$couleurA, $texteA] = $msgAnimal[$_GET['animal']] ?? ['secondary', ''];
                      ?>
                      <?php if ($texteA !== ''): ?>
                        <div class="alert alert-<?= e($couleurA) ?>" role="alert"><?= e($texteA) ?></div>
                      <?php endif; ?>
                    <?php endif; ?>
                    
                    <h3 class="h6 mb-3">
                      <?= $animalEdite ? 'Modifier « ' . e($animalEdite['name']) . ' »' : 'Ajouter un animal' ?>
                      <?php if ($animalEdite): ?>
                        <a href="Dashboard_admin.php?onglet=content" class="small ms-2">Ajouter un autre animal</a>
                      <?php endif; ?>
                    </h3>
                    
                    <form class="row g-3" action="Dashboard_admin.php" method="post">
                      <input type="hidden" name="animal_id" value="<?= (int) ($animalEdite['id'] ?? 0) ?>">
                    
                      <div class="col-12 col-md-4">
                        <label for="animalName" class="form-label">Nom</label>
                        <input id="animalName" name="name" type="text" class="form-control"
                               value="<?= e($animalEdite['name'] ?? '') ?>" placeholder="Lionne" required>
                      </div>
                    
                      <div class="col-12 col-md-4">
                        <label for="animalSpecies" class="form-label">Espèce</label>
                        <input id="animalSpecies" name="species" type="text" class="form-control"
                               value="<?= e($animalEdite['species'] ?? '') ?>" placeholder="Lion" required>
                      </div>
                    
                      <div class="col-12 col-md-4">
                        <label for="habitat" class="form-label">Habitat</label>
                        <select id="habitat" name="habitat_id" class="form-select" required>
                          <option value="" disabled <?= $animalEdite ? '' : 'selected' ?>>Choisir…</option>
                          <?php foreach ($tousLesHabitats as $unHabitat): ?>
                            <option value="<?= (int) $unHabitat['id'] ?>"
                              <?= $animalEdite && (int) $animalEdite['habitat_id'] === (int) $unHabitat['id'] ? 'selected' : '' ?>>
                              <?= e($unHabitat['name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    
                      <div class="col-12 col-md-6">
                        <label for="animalDiet" class="form-label">Nourriture</label>
                        <input id="animalDiet" name="diet" type="text" class="form-control"
                               value="<?= e($animalEdite['diet'] ?? '') ?>" placeholder="carnivore">
                      </div>
                    
                      <div class="col-12 col-md-6">
                        <label for="animalState" class="form-label">État</label>
                        <select id="animalState" name="health_state" class="form-select">
                          <?php foreach (['en bonne santé', 'suivi régulier', 'suivi attentif', 'en soins'] as $etatPossible): ?>
                            <option value="<?= e($etatPossible) ?>"
                              <?= ($animalEdite['health_state'] ?? '') === $etatPossible ? 'selected' : '' ?>>
                              <?= e($etatPossible) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    
                      <div class="col-12">
                        <label for="animalDesc" class="form-label">Description</label>
                        <textarea id="animalDesc" name="description" rows="3" class="form-control"
                                  placeholder="Texte descriptif…"><?= e($animalEdite['description'] ?? '') ?></textarea>
                      </div>
                    
                      <div class="col-12 col-md-7">
                        <label for="animalImage" class="form-label">Image</label>
                        <input id="animalImage" name="image" type="text" class="form-control"
                               value="<?= e($animalEdite['image'] ?? '') ?>"
                               placeholder="images/nom-du-fichier.jpg">
                        <div class="form-text">Le fichier doit déjà se trouver dans le dossier <code>images/</code>.</div>
                      </div>
                    
                      <div class="col-12 col-md-5">
                        <label for="animalImageAlt" class="form-label">Description de l’image</label>
                        <input id="animalImageAlt" name="image_alt" type="text" class="form-control"
                               value="<?= e($animalEdite['image_alt'] ?? '') ?>"
                               placeholder="Lionne dans la savane">
                      </div>
                    
                      <div class="col-12">
                        <button class="btn btn-brand" type="submit" name="action" value="animal_enregistrer">
                          <?= $animalEdite ? 'Mettre à jour' : 'Créer l’animal' ?>
                        </button>
                      </div>
                    </form>
                    
                    <?php if ($animalEdite): ?>
                      <form action="Dashboard_admin.php" method="post" class="mt-2"
                            onsubmit="return confirm('Supprimer cet animal ? Ses comptes rendus et ses repas seront supprimés aussi.');">
                        <input type="hidden" name="animal_id" value="<?= (int) $animalEdite['id'] ?>">
                        <button class="btn btn-outline-danger" type="submit" name="action" value="animal_supprimer">
                          Supprimer cet animal
                        </button>
                      </form>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <h3 class="h6 mb-3">Tous les animaux (<?= count($tousLesAnimaux) ?>)</h3>
                    <div class="table-responsive">
                      <table class="table table-sm align-middle">
                        <thead><tr><th>Nom</th><th>Habitat</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                          <?php foreach ($tousLesAnimaux as $unAnimal): ?>
                            <tr<?= $animalEdite && (int) $animalEdite['id'] === (int) $unAnimal['id'] ? ' class="table-active"' : '' ?>>
                              <td><?= e($unAnimal['name']) ?></td>
                              <td><?= e($unAnimal['habitat']) ?></td>
                              <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="Dashboard_admin.php?onglet=content&animal_id=<?= (int) $unAnimal['id'] ?>">
                                  <i class="bi bi-pencil"></i>
                                </a>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </article>
                </div>
              </div>
            </div>

            <!-- ========== COMPTES RENDUS & STATS ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'reports' ? ' show active' : '' ?>" id="pane-reports" role="tabpanel" aria-labelledby="tab-reports">
              
              <!-- Stats-->
              <div class="card p-4 mb-3">
                <h2 class="h5 mb-3">Nombre de consultations par animal</h2>
                <?php if (!$vues): ?>
                  <p class="mb-0 text-muted">
                    Aucune fiche animal n’a encore été consultée.
                  </p>

                <?php else: ?>
                  <?php foreach ($vues as $idAnimal => $nombre): ?>
                    <div class="mb-2">
                      <div class="d-flex justify-content-between">
                        <span><?= e($nomsAnimaux[$idAnimal] ?? 'Animal supprimé') ?></span>
                        <span><?= (int) $nombre ?></span>
                      </div>
                      <div class="progress">
                        <div class="progress-bar"
                             role="progressbar"
                             style="width: <?= $vueMax > 0 ? round($nombre / $vueMax * 100) : 0 ?>%"
                             aria-valuenow="<?= (int) $nombre ?>"
                             aria-valuemin="0"
                             aria-valuemax="<?= (int) $vueMax ?>">
                        </div>                      
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>

              </div>

              <!-- Comptes rendus vétérinaires -->
              <div class="card p-4">
                <h2 class="h5 mb-3">Comptes rendus vétérinaires</h2>
                <form action="Dashboard_admin.php" method="get" class="row g-3 mb-3">
                  <input type="hidden" name="onglet" value="reports">
                
                  <div class="col-12 col-md-4">
                    <label for="filterAnimal" class="form-label">Animal</label>
                    <input id="filterAnimal" name="animal" type="text" class="form-control"
                           value="<?= e($filtreAnimal) ?>" placeholder="ex : Girafe">
                  </div>
                
                  <div class="col-6 col-md-4">
                    <label for="filterFrom" class="form-label">Du</label>
                    <input id="filterFrom" name="du" type="date" class="form-control"
                           value="<?= e($filtreDu) ?>">
                  </div>
                
                  <div class="col-6 col-md-4">
                    <label for="filterTo" class="form-label">Au</label>
                    <input id="filterTo" name="au" type="date" class="form-control"
                           value="<?= e($filtreAu) ?>">
                  </div>
                
                  <div class="col-12">
                    <button class="btn btn-brand" type="submit">Filtrer</button>
                    <a class="btn btn-outline-secondary ms-2"
                       href="Dashboard_admin.php?onglet=reports">Réinitialiser</a>
                  </div>
                </form>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead>
                      <tr>
                        <th>Date</th><th>Animal</th><th>Vétérinaire</th>
                        <th>État</th><th>Nourriture</th><th>Détail</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!$rapports): ?>
                        <tr><td colspan="6" class="text-muted">Aucun compte rendu ne correspond.</td></tr>
                      <?php endif; ?>
                    
                      <?php foreach ($rapports as $rapport): ?>
                        <tr>
                          <td><?= e(date('d/m/Y', strtotime($rapport['visit_date']))) ?></td>
                          <td><?= e($rapport['animal']) ?></td>
                          <td><?= e($rapport['veterinaire']) ?></td>
                          <td><?= e($rapport['animal_state']) ?></td>
                          <td>
                            <?= e($rapport['proposed_food'] ?? '—') ?>
                            <?= $rapport['food_grams'] ? '(' . e((string) $rapport['food_grams']) . ' g)' : '' ?>
                          </td>
                          <td class="small"><?= e($rapport['state_details'] ?? '—') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- ==== MESSAGES DE CONTACT ==== -->
            <div class="tab-pane fade<?= $ongletActif === 'messages' ? ' show active' : '' ?>" id="pane-messages" role="tabpanel" aria-labelledby="tab-messages">
              <?php require __DIR__ . '/includes/panneau_messages.php'; ?>
            </div>

          </div><!-- div qui ferme le /tab-content -->
        </section>
      </div>
    </div>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>
  