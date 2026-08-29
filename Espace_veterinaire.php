<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';

// LA BARRIÈRE. Première instruction exécutable de la page, avant le
// moindre affichage : si la personne n'est pas un vétérinaire
// connecté, elle est redirigée ou refusée, et rien ne s'affiche.
$utilisateur = exiger_role('Veterinaire');

// Onglet à rouvrir après chaque action.
$ongletActif = $_GET['onglet'] ?? 'reports';

// Les animaux, pour les menus déroulants.
$animaux = db()->query(
    'SELECT a.id, a.name, h.name AS habitat
     FROM animals a
     JOIN habitats h ON h.id = a.habitat_id
     ORDER BY h.name, a.name'
)->fetchAll();

// L'historique des comptes rendus, tous vétérinaires confondus.
$rapports = db()->query(
    'SELECT r.visit_date, r.animal_state, r.proposed_food, r.food_grams,
            r.state_details, a.name AS animal, u.first_name AS veterinaire
     FROM veterinary_reports r
     JOIN animals a ON a.id = r.animal_id
     JOIN users u   ON u.id = r.veterinarian_id
     ORDER BY r.visit_date DESC, r.id DESC
     LIMIT 20'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'rapport_ajout') {
        $animalId   = (int) ($_POST['animal_id'] ?? 0);
        $date       = $_POST['visit_date'] ?? '';
        $etat       = trim($_POST['animal_state'] ?? '');
        $nourriture = trim($_POST['proposed_food'] ?? '');
        $grammage   = (int) ($_POST['food_grams'] ?? 0);
        $detail     = trim($_POST['state_details'] ?? '');

        if ($animalId > 0 && $date !== '' && $etat !== '') {

            $requete = db()->prepare(
                'INSERT INTO veterinary_reports
                   (animal_id, veterinarian_id, visit_date, animal_state,
                    proposed_food, food_grams, state_details)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $requete->execute([
                $animalId,
                $utilisateur['id'],
                $date,
                $etat,
                $nourriture !== '' ? $nourriture : null,
                $grammage > 0 ? $grammage : null,
                $detail !== '' ? $detail : null,
            ]);

            // LA BOUCLE : l'état courant de l'animal, celui qu'affichent les
            // pages publiques, reflète le dernier compte rendu du vétérinaire.
            $requete = db()->prepare('UPDATE animals SET health_state = ? WHERE id = ?');
            $requete->execute([$etat, $animalId]);

            header('Location: Espace_veterinaire.php?onglet=reports&rapport=ok');
            exit;
        }

        header('Location: Espace_veterinaire.php?onglet=reports&rapport=erreur');
        exit;
    }

    if ($action === 'commentaire_ajout') {
            $habitatId   = (int) ($_POST['habitat_id'] ?? 0);
            $commentaire = trim($_POST['comment'] ?? '');
        
            if ($habitatId > 0 && $commentaire !== '') {
                $requete = db()->prepare(
                    'INSERT INTO habitat_comments (habitat_id, veterinarian_id, comment)
                     VALUES (?, ?, ?)'
                );
                $requete->execute([$habitatId, $utilisateur['id'], $commentaire]);
        
                header('Location: Espace_veterinaire.php?onglet=habitats&commentaire=ok');
                exit;
            }
        
            header('Location: Espace_veterinaire.php?onglet=habitats&commentaire=erreur');
            exit;
    }
}

// Les habitats, pour le menu déroulant.
$habitats = db()->query('SELECT id, name FROM habitats ORDER BY name')->fetchAll();

// Les derniers commentaires laissés sur les habitats.
$commentaires = db()->query(
    'SELECT c.comment, c.created_at, h.name AS habitat, u.first_name AS veterinaire
     FROM habitat_comments c
     JOIN habitats h ON h.id = c.habitat_id
     JOIN users u    ON u.id = c.veterinarian_id
     ORDER BY c.created_at DESC
     LIMIT 15'
)->fetchAll();

// Les repas saisis par les soigneurs — le vétérinaire les consulte, il ne
// les modifie pas : chaque rôle a son périmètre.
$repas = db()->query(
    'SELECT f.feed_date, f.feed_time, f.food, f.quantity, f.unit,
            a.name AS animal, u.first_name AS soigneur
     FROM feedings f
     JOIN animals a ON a.id = f.animal_id
     JOIN users u   ON u.id = f.employee_id
     ORDER BY f.feed_date DESC, f.feed_time DESC
     LIMIT 30'
)->fetchAll();

$titrePage  = 'Espace Vétérinaire — Zoo d’Arcadia';
$classeBody = '';

require __DIR__ . '/includes/header.php';
?>

  <!-- LAYOUT -->
  <main class="vet-layout py-4">
    <div class="container">
      <div class="row g-3">
        
        <!-- Sidebar -->
        <aside class="col-12 col-lg-3">
          <div class="card p-3">
            <h1 class="h5 mb-3">Espace Vétérinaire</h1>
            <div class="nav flex-lg-column nav-pills gap-2" id="vetTabs" role="tablist">
              <button class="nav-link<?= $ongletActif === 'reports' ? ' active' : '' ?>" id="tab-reports" data-bs-toggle="pill" data-bs-target="#pane-reports" type="button">
                <i class="bi bi-clipboard-heart me-1"></i> Comptes rendus
              </button>
              <button class="nav-link<?= $ongletActif === 'habitats' ? ' active' : '' ?>" id="tab-habitats" data-bs-toggle="pill" data-bs-target="#pane-habitats" type="button">
                <i class="bi bi-tree me-1"></i> Habitats
              </button>
              <button class="nav-link<?= $ongletActif === 'food' ? ' active' : '' ?>" id="tab-food" data-bs-toggle="pill" data-bs-target="#pane-food" type="button">
                <i class="bi bi-basket me-1"></i> Suivi alimentation
              </button>
            </div>
          </div>
        </aside>

        <!-- Contenu -->
        <section class="col-12 col-lg-9">
          <div class="tab-content">

            <!-- ========== COMPTES RENDUS ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'reports' ? ' show active' : '' ?>" id="pane-reports" role="tabpanel">
              <div class="card p-4 mb-3">
                <h2 class="h5 mb-3">Nouveau compte rendu</h2>
                <?php if (isset($_GET['rapport']) && $_GET['rapport'] === 'ok'): ?>
                  <div class="alert alert-success" role="alert">
                    Compte rendu enregistré. L’état de l’animal a été mis à jour sur le site.
                  </div>
                <?php elseif (isset($_GET['rapport'])): ?>
                  <div class="alert alert-danger" role="alert">
                    L’animal, la date et l’état sont obligatoires.
                  </div>
                <?php endif; ?>
                
                <form action="Espace_veterinaire.php" method="post" class="row g-3">
                
                  <div class="col-12 col-md-6">
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
                
                  <div class="col-12 col-md-6">
                    <label for="date" class="form-label">Date de passage</label>
                    <input id="date" name="visit_date" type="date" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                  </div>
                
                  <div class="col-12 col-md-6">
                    <label for="etat" class="form-label">État de l’animal</label>
                    <select id="etat" name="animal_state" class="form-select" required>
                      <option value="" disabled selected>Choisir…</option>
                      <option value="en bonne santé">en bonne santé</option>
                      <option value="suivi régulier">suivi régulier</option>
                      <option value="suivi attentif">suivi attentif</option>
                      <option value="en soins">en soins</option>
                    </select>
                  </div>
                
                  <div class="col-8 col-md-4">
                    <label for="nourriture" class="form-label">Nourriture proposée</label>
                    <input id="nourriture" name="proposed_food" type="text" class="form-control"
                           placeholder="viande, feuilles…">
                  </div>
                
                  <div class="col-4 col-md-2">
                    <label for="grammage" class="form-label">Grammage (g)</label>
                    <input id="grammage" name="food_grams" type="number" min="0" step="10"
                           class="form-control" placeholder="500">
                  </div>
                
                  <div class="col-12">
                    <label for="detail" class="form-label">Détail de l’état</label>
                    <textarea id="detail" name="state_details" rows="3" class="form-control"
                              placeholder="Observations, traitement en cours…"></textarea>
                  </div>
                
                  <div class="col-12 d-grid">
                    <button class="btn btn-brand" type="submit" name="action" value="rapport_ajout">
                      Enregistrer
                    </button>
                  </div>
                </form>
              </div>

              <div class="card p-4">
                <h3 class="h6 mb-3">Historique des comptes rendus</h3>
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Date</th><th>Animal</th><th>État</th>
                      <th>Nourriture</th><th>Grammage</th><th>Vétérinaire</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$rapports): ?>
                      <tr><td colspan="6" class="text-muted">Aucun compte rendu enregistré.</td></tr>
                    <?php endif; ?>
                  
                    <?php foreach ($rapports as $rapport): ?>
                      <tr>
                        <td><?= e(date('d/m/Y', strtotime($rapport['visit_date']))) ?></td>
                        <td><?= e($rapport['animal']) ?></td>
                        <td><?= e($rapport['animal_state']) ?></td>
                        <td><?= e($rapport['proposed_food'] ?? '—') ?></td>
                        <td><?= $rapport['food_grams'] ? e((string) $rapport['food_grams']) . ' g' : '—' ?></td>
                        <td><?= e($rapport['veterinaire']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- ========== HABITATS ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'habitats' ? ' show active' : '' ?>" id="pane-habitats" role="tabpanel">
              <div class="card p-4">
                <h2 class="h5 mb-3">Commentaires sur habitats</h2>
                <?php if (isset($_GET['commentaire']) && $_GET['commentaire'] === 'ok'): ?>
                  <div class="alert alert-success" role="alert">Commentaire enregistré.</div>
                <?php elseif (isset($_GET['commentaire'])): ?>
                  <div class="alert alert-danger" role="alert">
                    L’habitat et le commentaire sont obligatoires.
                  </div>
                <?php endif; ?>
                
                <form action="Espace_veterinaire.php" method="post" class="row g-3">
                  <div class="col-12 col-md-6">
                    <label for="habitat" class="form-label">Habitat</label>
                    <select id="habitat" name="habitat_id" class="form-select" required>
                      <option value="" disabled selected>Choisir…</option>
                      <?php foreach ($habitats as $unHabitat): ?>
                        <option value="<?= (int) $unHabitat['id'] ?>"><?= e($unHabitat['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                
                  <div class="col-12">
                    <label for="commentaire" class="form-label">Commentaire</label>
                    <textarea id="commentaire" name="comment" rows="3" class="form-control"
                              placeholder="État de la végétation, propreté, équipements…" required></textarea>
                  </div>
                
                  <div class="col-12 d-grid">
                    <button class="btn btn-brand" type="submit" name="action" value="commentaire_ajout">
                      Enregistrer
                    </button>
                  </div>
                </form>
              </div>
              <div class="card p-4 mt-3">
                <h3 class="h6 mb-3">Derniers commentaires</h3>
              
                <?php if (!$commentaires): ?>
                  <p class="mb-0 text-muted">Aucun commentaire pour le moment.</p>
                <?php else: ?>
                  <?php foreach ($commentaires as $index => $unCommentaire): ?>
                    <?php if ($index > 0): ?><hr><?php endif; ?>
                    <div class="small">
                      <strong><?= e($unCommentaire['habitat']) ?></strong>
                      — <?= e($unCommentaire['veterinaire']) ?>,
                      <?= e(date('d/m/Y', strtotime($unCommentaire['created_at']))) ?>
                      <div class="text-muted"><?= e($unCommentaire['comment']) ?></div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- ========== SUIVI ALIMENTATION ========== -->
            <div class="tab-pane fade<?= $ongletActif === 'food' ? ' show active' : '' ?>" id="pane-food" role="tabpanel">
              <div class="card p-4">
                <h2 class="h5 mb-3">Alimentation des animaux</h2>
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Date</th><th>Heure</th><th>Animal</th>
                      <th>Nourriture</th><th>Quantité</th><th>Soigneur</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$repas): ?>
                      <tr><td colspan="6" class="text-muted">Aucun repas enregistré.</td></tr>
                    <?php endif; ?>
                  
                    <?php foreach ($repas as $unRepas): ?>
                      <tr>
                        <td><?= e(date('d/m/Y', strtotime($unRepas['feed_date']))) ?></td>
                        <td><?= e(substr($unRepas['feed_time'], 0, 5)) ?></td>
                        <td><?= e($unRepas['animal']) ?></td>
                        <td><?= e($unRepas['food']) ?></td>
                        <td><?= e(rtrim(rtrim(number_format((float) $unRepas['quantity'], 2, ',', ' '), '0'), ',')) ?> <?= e($unRepas['unit']) ?></td>
                        <td><?= e($unRepas['soigneur']) ?></td>
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