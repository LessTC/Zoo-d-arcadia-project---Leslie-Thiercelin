<?php

declare(strict_types=1);


require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/fonctions.php';

// Requête fixe, aucune valeur ne vient du visiteur : query() suffit.
// prepare() n'aurait rien à protéger ici.
$habitats = db()->query('SELECT id, name, image FROM habitats ORDER BY name')->fetchAll();

$titrePage       = 'Nos habitats — Zoo d’Arcadia';
$descriptionPage = 'Découvrez tous les habitats du Zoo d’Arcadia : savane, jungle, marais et les animaux qui y vivent.';
$classeBody      = 'bg-habitat';

require __DIR__ . '/includes/header.php';
?>

  <main class="py-5">
    <div class="container">
      <h1 class="text-white mb-4">Nos habitats</h1>

      <?php if (!$habitats): ?>
        <p class="text-white">Aucun habitat n’est présenté pour le moment.</p>
      <?php endif; ?>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
        <?php foreach ($habitats as $unHabitat): ?>
          <div class="col">
            <a href="habitat.php?id=<?= (int) $unHabitat['id'] ?>"
               class="card h-100 p-3 text-center text-decoration-none btn-text-green">
              <?php if ($unHabitat['image']): ?>
                <img src="<?= e($unHabitat['image']) ?>"
                     alt="Habitat <?= e($unHabitat['name']) ?>"
                     class="photo-service mb-3" loading="lazy" decoding="async">
              <?php endif; ?>
              <h2 class="h5 mb-0"><?= e($unHabitat['name']) ?></h2>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>