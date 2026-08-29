<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';
require __DIR__ . '/includes/mongo.php';

$animalId = (int) ($_GET['id'] ?? 0);

$requete = db()->prepare(
    'SELECT a.id, a.name, a.species, a.description, a.diet, a.health_state,
            a.image, a.image_alt, h.name AS habitat
     FROM animals a
     JOIN habitats h ON h.id = a.habitat_id
     WHERE a.id = ?'
);
$requete->execute([$animalId]);
$animal = $requete->fetch();

if (!$animal) {
    http_response_code(404);
    $titrePage = 'Animal introuvable — Zoo d’Arcadia';
    require __DIR__ . '/includes/header.php';
    echo '<main class="container py-5"><h1 class="h3">Animal introuvable</h1>'
       . '<p><a href="index.php">Retour à l’accueil</a></p></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// US11 : une consultation de plus. Placé APRÈS la vérification, pour ne
// pas compter les visites sur un identifiant qui n'existe pas.
incrementer_consultation((int) $animal['id']);

// Le dernier passage du vétérinaire, s'il y en a eu un.
$requete = db()->prepare(
    'SELECT visit_date, animal_state, proposed_food, food_grams
     FROM veterinary_reports
     WHERE animal_id = ?
     ORDER BY visit_date DESC, id DESC
     LIMIT 1'
);
$requete->execute([$animal['id']]);
$dernierRapport = $requete->fetch() ?: null;

$titrePage       = $animal['name'] . ' — Zoo d’Arcadia';
$descriptionPage = 'Fiche de ' . $animal['name'] . ', ' . $animal['species'] . ' au Zoo d’Arcadia.';
// La fiche reprend le fond de son habitat : bg-savane, bg-jungle, bg-marais.
$classeBody      = 'bg-' . strtolower($animal['habitat']);

require __DIR__ . '/includes/header.php';
?>

  <main class="py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
          <article class="card p-4 p-md-5">

            <div class="row g-4 align-items-center">
              <?php if ($animal['image']): ?>
                <div class="col-12 col-md-5 text-center">
                  <img src="<?= e($animal['image']) ?>" alt="<?= e($animal['image_alt']) ?>"
                       class="img-fluid rounded">
                </div>
              <?php endif; ?>

              <div class="col-12 col-md-<?= $animal['image'] ? '7' : '12' ?>">
                <h1 class="h3 mb-1"><?= e($animal['name']) ?></h1>
                <p class="text-muted mb-3"><?= e($animal['species']) ?></p>

                <p><?= e($animal['description']) ?></p>

                <ul class="list-unstyled mb-0">
                  <li><strong>Habitat :</strong> <?= e($animal['habitat']) ?></li>
                  <li><strong>Nourriture :</strong> <?= e($animal['diet']) ?></li>
                  <li><strong>État :</strong> <?= e($animal['health_state']) ?></li>
                </ul>
              </div>
            </div>

            <?php if ($dernierRapport): ?>
              <hr class="my-4">
              <h2 class="h6">Dernier passage du vétérinaire</h2>
              <p class="small mb-0">
                Le <?= e(date('d/m/Y', strtotime($dernierRapport['visit_date']))) ?> —
                <?= e($dernierRapport['animal_state']) ?>
                <?php if ($dernierRapport['proposed_food']): ?>
                  · nourriture proposée : <?= e($dernierRapport['proposed_food']) ?>
                  <?php if ($dernierRapport['food_grams']): ?>
                    (<?= e((string) $dernierRapport['food_grams']) ?> g)
                  <?php endif; ?>
                <?php endif; ?>
              </p>
            <?php endif; ?>

            <hr class="my-4">
            <a href="<?= e(strtolower($animal['habitat'])) ?>.php" class="btn btn-brand">
              ← Retour à <?= e($animal['habitat']) ?>
            </a>

          </article>
        </div>
      </div>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>