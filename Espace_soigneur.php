<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';

// LA BARRIÈRE. Première instruction exécutable de la page, avant le
// moindre affichage : si la personne n'est pas un employé
// connecté, elle est redirigée ou refusée, et rien ne s'affiche.
$utilisateur = exiger_role('Employe');

// --- Actions de modération ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action']  ?? '';
    // Conversion en entier : quoi qu'on nous envoie, on obtient un nombre.
    $avisId = (int) ($_POST['avis_id'] ?? 0);

    if ($avisId > 0 && $action === 'valider') {
        // approved_by garde la trace de QUI a validé, et quand.
        $requete = db()->prepare(
            'UPDATE reviews SET is_approved = 1, approved_by = ? WHERE id = ?'
        );
        $requete->execute([$utilisateur['id'], $avisId]);

        header('Location: Espace_soigneur.php?modere=valide');
        exit;
    }

    if ($avisId > 0 && $action === 'rejeter') {
        $requete = db()->prepare('DELETE FROM reviews WHERE id = ?');
        $requete->execute([$avisId]);

        header('Location: Espace_soigneur.php?modere=rejete');
        exit;
    }

    if ($action === 'service_maj') {
        $serviceId  = (int) ($_POST['service_id'] ?? 0);
        $nom        = trim($_POST['name'] ?? '');
        $horaires   = trim($_POST['schedule'] ?? '');
        $descriptif = trim($_POST['description'] ?? '');

        if ($serviceId > 0 && $nom !== '') {
            $requete = db()->prepare(
                'UPDATE services SET name = ?, schedule = ?, description = ? WHERE id = ?'
            );
            $requete->execute([$nom, $horaires, $descriptif, $serviceId]);

            header('Location: Espace_soigneur.php?onglet=services&service=' . $serviceId . '&maj=ok');
            exit;
        }

        header('Location: Espace_soigneur.php?onglet=services&maj=erreur');
        exit;
    }

    if ($action === 'service_suppr') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);

        if ($serviceId > 0) {
            // Les images liées partent avec, grâce au ON DELETE CASCADE.
            $requete = db()->prepare('DELETE FROM services WHERE id = ?');
            $requete->execute([$serviceId]);
        }

        header('Location: Espace_soigneur.php?onglet=services&suppr=ok');
        exit;
    }
    
    if ($action === 'repas_ajout') {
        $animalId   = (int) ($_POST['animal_id'] ?? 0);
        $date       = $_POST['feed_date'] ?? '';
        $heure      = $_POST['feed_time'] ?? '';
        $nourriture = trim($_POST['food'] ?? '');
        $unite      = $_POST['unit'] ?? 'kg';
    
        // On accepte « 2,5 » comme « 2.5 » : en France on tape une virgule,
        // mais MySQL attend un point.
        $quantite = (float) str_replace(',', '.', (string) ($_POST['quantity'] ?? '0'));
    
        // Liste blanche : on n'enregistre que des unités qu'on a prévues.
        if (!in_array($unite, ['kg', 'g', 'L'], true)) {
            $unite = 'kg';
        }
    
        if ($animalId > 0 && $date !== '' && $heure !== '' && $nourriture !== '' && $quantite > 0) {
            $requete = db()->prepare(
                'INSERT INTO feedings
                   (animal_id, employee_id, feed_date, feed_time, food, quantity, unit)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $requete->execute([
                $animalId, $utilisateur['id'], $date, $heure, $nourriture, $quantite, $unite,
            ]);
    
            header('Location: Espace_soigneur.php?onglet=food&repas=ok');
            exit;
        }
    
        header('Location: Espace_soigneur.php?onglet=food&repas=erreur');
        exit;
    }
    }

// Les avis en attente : ceux que personne n'a encore tranchés.
$avisEnAttente = db()->query(
    'SELECT id, nickname, title, comment, created_at
     FROM reviews
     WHERE is_approved = 0
     ORDER BY created_at DESC'
)->fetchAll();

// Tous les services, pour le menu déroulant.
$services = db()->query('SELECT id, name FROM services ORDER BY id')->fetchAll();

// Celui qu'on est en train de modifier, s'il y en a un.
$serviceEdite = null;
$idDemande = (int) ($_GET['service'] ?? 0);

if ($idDemande > 0) {
    $requete = db()->prepare(
        'SELECT id, name, schedule, description FROM services WHERE id = ?'
    );
    $requete->execute([$idDemande]);
    $serviceEdite = $requete->fetch() ?: null;
}

// Les animaux, pour le menu déroulant.
$animaux = db()->query(
    'SELECT a.id, a.name, h.name AS habitat
     FROM animals a
     JOIN habitats h ON h.id = a.habitat_id
     ORDER BY h.name, a.name'
)->fetchAll();

// Les 20 derniers repas enregistrés, tous soigneurs confondus.
$historique = db()->query(
    'SELECT f.feed_date, f.feed_time, f.food, f.quantity, f.unit, a.name AS animal
     FROM feedings f
     JOIN animals a ON a.id = f.animal_id
     ORDER BY f.feed_date DESC, f.feed_time DESC
     LIMIT 20'
)->fetchAll();

// Onglet à ouvrir au chargement, transmis dans l'URL après chaque action.
$ongletActif = $_GET['onglet'] ?? 'avis';

$titrePage  = 'Espace Employé — Zoo d’Arcadia';
$classeBody = '';

require __DIR__ . '/includes/header.php';
?>

  <!-- LAYOUT -->
  <main class="employe-layout py-4">
    <div class="container">
      <div class="row g-3">
        
        <!-- Sidebar -->
        <aside class="col-12 col-lg-3">
          <div class="card p-3">
            <h1 class="h5 mb-3">Espace Employé</h1>
            <div class="nav flex-lg-column nav-pills gap-2" id="empTabs" role="tablist">
              <button class="nav-link<?= $ongletActif === 'avis' ? ' active' : '' ?>" id="tab-avis" data-bs-toggle="pill" data-bs-target="#pane-avis" type="button">
                <i class="bi bi-chat-square-text me-1"></i> Avis
              </button>
              <button class="nav-link<?= $ongletActif === 'services' ? ' active' : '' ?>" id="tab-services" data-bs-toggle="pill" data-bs-target="#pane-services" type="button">
                <i class="bi bi-gear me-1"></i> Services
              </button>
              <button class="nav-link<?= $ongletActif === 'food' ? ' active' : '' ?>" id="tab-food" data-bs-toggle="pill" data-bs-target="#pane-food" type="button">
                <i class="bi bi-basket me-1"></i> Alimentation
              </button>
            </div>
          </div>
        </aside>

        <!-- Contenu -->
        <section class="col-12 col-lg-9">
          <div class="tab-content">

            <!-- ========== AVIS ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'avis' ? ' show active' : '' ?>" id="pane-avis" role="tabpanel">
              <div class="card p-4">
                <h2 class="h5 mb-3">
                Gestion des avis visiteurs
                <span class="badge bg-secondary ms-2"><?= count($avisEnAttente) ?> en attente</span>
                </h2>
              
                <?php if (isset($_GET['modere'])): ?>
                <div class="alert alert-<?= $_GET['modere'] === 'valide' ? 'success' : 'secondary' ?>" role="alert">
                  <?= $_GET['modere'] === 'valide'
                      ? 'Avis publié : il est désormais visible sur la page d’accueil.'
                      : 'Avis rejeté et supprimé.' ?>
                </div>
                <?php endif; ?>
              
                <?php if (!$avisEnAttente): ?>
                <p class="mb-0 text-muted">Aucun avis en attente de validation.</p>
                <?php else: ?>
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Date</th><th>Auteur</th><th>Titre</th><th>Avis</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($avisEnAttente as $unAvis): ?>
                      <tr>
                        <td><?= e(date('d/m/Y', strtotime($unAvis['created_at']))) ?></td>
                        <td><?= e($unAvis['nickname']) ?></td>
                        <td><?= e($unAvis['title']) ?></td>
                        <td><?= e($unAvis['comment']) ?></td>
                        <td class="text-end text-nowrap">
                          <form action="Espace_soigneur.php" method="post" class="d-inline">
                            <input type="hidden" name="avis_id" value="<?= (int) $unAvis['id'] ?>">
                            <button type="submit" name="action" value="valider"
                                    class="btn btn-success btn-sm" title="Publier cet avis">
                              <i class="bi bi-check-lg"></i>
                            </button>
                          </form>
                          <form action="Espace_soigneur.php" method="post" class="d-inline">
                            <input type="hidden" name="avis_id" value="<?= (int) $unAvis['id'] ?>">
                            <button type="submit" name="action" value="rejeter"
                                    class="btn btn-danger btn-sm" title="Supprimer définitivement">
                              <i class="bi bi-x-lg"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              </div>
            </div>

            <!-- ========== SERVICES ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'services' ? ' show active' : '' ?>" id="pane-services" role="tabpanel">
              <div class="card p-4">
                <h2 class="h5 mb-3">Modifier un service</h2>
                <?php if (isset($_GET['maj']) && $_GET['maj'] === 'ok'): ?>
                  <div class="alert alert-success" role="alert">Service mis à jour.</div>
                <?php elseif (isset($_GET['maj'])): ?>
                  <div class="alert alert-danger" role="alert">Le nom du service est obligatoire.</div>
                <?php endif; ?>

                <?php if (isset($_GET['suppr'])): ?>
                  <div class="alert alert-secondary" role="alert">Service supprimé.</div>
                <?php endif; ?>

                <!-- Étape 1 : choisir le service -->
                <form action="Espace_soigneur.php" method="get" class="row g-2 align-items-end mb-4">
                  <input type="hidden" name="onglet" value="services">
                  <div class="col-12 col-md-8">
                    <label for="serviceChoix" class="form-label">Service à modifier</label>
                    <select id="serviceChoix" name="service" class="form-select">
                      <?php foreach ($services as $unService): ?>
                        <option value="<?= (int) $unService['id'] ?>"
                          <?= $serviceEdite && (int) $serviceEdite['id'] === (int) $unService['id'] ? 'selected' : '' ?>>
                          <?= e($unService['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12 col-md-4">
                    <button class="btn btn-outline-secondary w-100" type="submit">Charger</button>
                  </div>
                </form>

                <!-- Étape 2 : modifier -->
                <?php if ($serviceEdite): ?>
                  <form action="Espace_soigneur.php" method="post" class="row g-3">
                    <input type="hidden" name="service_id" value="<?= (int) $serviceEdite['id'] ?>">

                    <div class="col-12 col-md-6">
                      <label for="serviceName" class="form-label">Nom du service</label>
                      <input id="serviceName" name="name" type="text" class="form-control"
                             value="<?= e($serviceEdite['name']) ?>" required>
                    </div>

                    <div class="col-12 col-md-6">
                      <label for="serviceHours" class="form-label">Horaires</label>
                      <input id="serviceHours" name="schedule" type="text" class="form-control"
                             value="<?= e($serviceEdite['schedule']) ?>" placeholder="9h – 19h">
                    </div>

                    <div class="col-12">
                      <label for="serviceDesc" class="form-label">Description</label>
                      <textarea id="serviceDesc" name="description" rows="3"
                                class="form-control"><?= e($serviceEdite['description']) ?></textarea>
                    </div>

                    <div class="col-12">
                      <button class="btn btn-brand" type="submit" name="action" value="service_maj">
                        Mettre à jour
                      </button>
                    </div>
                  </form>

                  <form action="Espace_soigneur.php" method="post" class="mt-2"
                        onsubmit="return confirm('Supprimer définitivement ce service et ses photos ?');">
                    <input type="hidden" name="service_id" value="<?= (int) $serviceEdite['id'] ?>">
                    <button class="btn btn-outline-danger" type="submit" name="action" value="service_suppr">
                      Supprimer
                    </button>
                  </form>
                <?php else: ?>
                  <p class="text-muted mb-0">Choisis un service ci-dessus, puis clique sur « Charger ».</p>
                <?php endif; ?>
              </div>
            </div>

            <!-- ========== ALIMENTATION ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'food' ? ' show active' : '' ?>" id="pane-food" role="tabpanel">
              <div class="card p-4 mb-3">
                <h2 class="h5 mb-3">Nouvelle alimentation</h2>
                <?php if (isset($_GET['repas']) && $_GET['repas'] === 'ok'): ?>
                  <div class="alert alert-success" role="alert">Repas enregistré.</div>
                <?php elseif (isset($_GET['repas'])): ?>
                  <div class="alert alert-danger" role="alert">
                    Tous les champs sont obligatoires, et la quantité doit être supérieure à zéro.
                  </div>
                <?php endif; ?>
                
                <form action="Espace_soigneur.php" method="post" class="row g-3">
                  <div class="col-12 col-md-4">
                    <label for="animal" class="form-label">Animal</label>
                    <select id="animal" name="animal_id" class="form-select" required>
                      <option value="" disabled selected>Choisir…</option>
                      <?php foreach ($animaux as $unAnimal): ?>
                        <option value="<?= (int) $unAnimal['id'] ?>">
                          <?= e($unAnimal['name']) ?> — <?= e($unAnimal['habitat']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                
                  <div class="col-6 col-md-4">
                    <label for="date" class="form-label">Date</label>
                    <input id="date" name="feed_date" type="date" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                  </div>
                
                  <div class="col-6 col-md-4">
                    <label for="heure" class="form-label">Heure</label>
                    <input id="heure" name="feed_time" type="time" class="form-control" required>
                  </div>
                
                  <div class="col-12 col-md-6">
                    <label for="food" class="form-label">Nourriture</label>
                    <input id="food" name="food" type="text" class="form-control" required>
                  </div>
                
                  <div class="col-8 col-md-4">
                    <label for="quantity" class="form-label">Quantité</label>
                    <input id="quantity" name="quantity" type="number" step="0.01" min="0.01"
                           class="form-control" placeholder="5" required>
                  </div>
                
                  <div class="col-4 col-md-2">
                    <label for="unit" class="form-label">Unité</label>
                    <select id="unit" name="unit" class="form-select">
                      <option value="kg">kg</option>
                      <option value="g">g</option>
                      <option value="L">L</option>
                    </select>
                  </div>
                
                  <div class="col-12 d-grid">
                    <button class="btn btn-brand" type="submit" name="action" value="repas_ajout">
                      Enregistrer
                    </button>
                  </div>
                </form>
              </div>

              <div class="card p-4">
                <h3 class="h6 mb-3">Historique alimentation</h3>
                <table class="table table-sm">
                  <thead><tr><th>Date</th><th>Heure</th><th>Animal</th><th>Nourriture</th><th>Quantité</th></tr></thead>
                  <tbody>
                    <?php if (!$historique): ?>
                      <tr><td colspan="5" class="text-muted">Aucun repas enregistré.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($historique as $repas): ?>
                      <tr>
                        <td><?= e(date('d/m/Y', strtotime($repas['feed_date']))) ?></td>
                        <td><?= e(substr($repas['feed_time'], 0, 5)) ?></td>
                        <td><?= e($repas['animal']) ?></td>
                        <td><?= e($repas['food']) ?></td>
                        <td><?= e(rtrim(rtrim(number_format((float) $repas['quantity'], 2, ',', ' '), '0'), ',')) ?> <?= e($repas['unit']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </section>
      </div>
    </div>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>