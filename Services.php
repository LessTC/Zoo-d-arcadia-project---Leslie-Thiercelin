<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';

// Les visites, dans l'ordre de création
$visites = db()->query(
    "SELECT id, name, slug, description
     FROM services
     WHERE category = 'visite'
     ORDER BY id"
)->fetchAll();

// La restauration : fetch() et non fetchAll(), on n'en attend qu'une.
$restauration = db()->query(
    "SELECT id, name, slug, description
     FROM services
     WHERE category = 'restauration'
     ORDER BY id
     LIMIT 1"
)->fetch();

// TOUTES les images en UNE seule requête, qu'on range ensuite par service.
$imagesParService = [];
$lignes = db()->query(
    'SELECT service_id, path, alt
     FROM service_images
     ORDER BY service_id, position'
);
foreach ($lignes as $image) {
    $imagesParService[$image['service_id']][] = $image;
}

// Deux colonnes minimum pour qu'un service seul ne s'étale pas sur toute
// la largeur, trois maximum pour que les cartes restent lisibles.
$colonnes = max(2, min(count($visites), 3));

// Onglet à ouvrir au chargement, transmis dans l'URL après chaque action.
$ongletActif = $_GET['onglet'] ?? 'avis';

// titre

$titrePage       = 'Nos services — Zoo d’Arcadia';
$descriptionPage = 'Les services proposés aux visiteurs du Zoo d’Arcadia.';
$classeBody      = '';

require __DIR__ . '/includes/header.php';
?>

  <!-- Hero -->
  <section class="bg-services d-flex align-items-center">
    <div class="container hero-content py-5">
      <h1 class="text-white mb-0">Nos services</h1>
    </div>
  </section>

  <main class="py-5">
    <div class="container">

      <!-- Visites -->
      <section class="mb-5">
        <div class="row row-cols-1 row-cols-lg-<?= $colonnes ?> g-4">
          <?php foreach ($visites as $visite): ?>
            <div class="col">
              <article class="card h-100 p-4">
                <h2 class="h4 mb-2" id="<?= e($visite['slug']) ?>"><?= e($visite['name']) ?></h2>
                <p class="mb-3"><?= e($visite['description']) ?></p>
      
                <?php foreach ($imagesParService[$visite['id']] ?? [] as $image): ?>
                  <img src="<?= e($image['path']) ?>"
                       class="photo-service rounded" alt="<?= e($image['alt']) ?>">
                <?php endforeach; ?>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <?php if ($restauration): ?>
        <!-- Restauration -->
        <section>
          <article class="card p-4">
            <h2 class="h4 mb-2" id="<?= e($restauration['slug']) ?>"><?= e($restauration['name']) ?></h2>
            <p class="mb-4"><?= e($restauration['description']) ?></p>
      
            <div class="row row-cols-1 row-cols-md-2 g-3">
              <?php foreach ($imagesParService[$restauration['id']] ?? [] as $image): ?>
                <div class="col">
                  <img src="<?= e($image['path']) ?>"
                       class="photo-service rounded" alt="<?= e($image['alt']) ?>">   
                </div>
              <?php endforeach; ?>
            </div>
          </article>
        </section>
      <?php endif; ?>

    </div>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>  