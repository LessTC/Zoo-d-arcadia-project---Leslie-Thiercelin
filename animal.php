<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';
require __DIR__ . '/includes/mongo.php';
require __DIR__ . '/includes/animaux.php';

$animalId = (int) ($_GET['id'] ?? 0);

$animal = animal_detail($animalId);

if (!$animal) {
    http_response_code(404);
    $titrePage = 'Animal introuvable — Zoo d’Arcadia';
    require __DIR__ . '/includes/header.php';
    echo '<main class="container py-5"><h1 class="h3">Animal introuvable</h1>'
       . '<p><a href="index.php">Retour à l’accueil</a></p></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Compteur de consultation placé après  la vérification, pour ne
// pas compter les visites sur un animal qui n'existe pas.
incrementer_consultation((int) $animal['id']);

//dernier passage vétérinaire si applicable
$dernierRapport = 
animal_dernier_rapport((int) 
$animal['id']);

$titrePage       = $animal['name'] . ' — Zoo d’Arcadia';
$descriptionPage = 'Fiche de ' . $animal['name'] . ', ' . $animal['species'] . ' au Zoo d’Arcadia.';
$classeBody      = 'bg-habitat bg-' . strtolower($animal['habitat']);

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
                  · Recommandation nourriture : <?= e($dernierRapport['proposed_food']) ?>
                  <?php if ($dernierRapport['food_grams']): ?>
                    (<?= e((string) $dernierRapport['food_grams']) ?> g)
                  <?php endif; ?>
                <?php endif; ?>
              </p>
            <?php endif; ?>

            <hr class="my-4">
            <a href="habitat.php?id=<?= (int) $animal['habitat_id'] ?>" class="btn btn-brand">
              ← Retour à <?= e($animal['habitat']) ?>
            </a>

          </article>
        </div>
      </div>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>