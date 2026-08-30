<?php

declare(strict_types=1);

/**
 * Page publique de l'habitat Jungle.
 * Même structure que savane.php : données en haut, affichage en bas.
 */

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/fonctions.php';

$requete = db()->prepare('SELECT id, name, description FROM habitats WHERE name = ?');
$requete->execute(['Jungle']);
$habitat = $requete->fetch();

if (!$habitat) {
    http_response_code(404);
    exit('Habitat introuvable.');
}

$requete = db()->prepare(
    'SELECT id, name, description, diet, health_state, image, image_alt
     FROM animals
     WHERE habitat_id = ?
     ORDER BY id'
);
$requete->execute([$habitat['id']]);
$animaux = $requete->fetchAll();

$titrePage       = 'Jungle — Zoo d’Arcadia';
$descriptionPage = 'La jungle tropicale du Zoo d’Arcadia : présentation des animaux.';
$classeBody      = 'bg-jungle';

require __DIR__ . '/includes/header.php';
?>

  <!-- Intro jungle -->
  <section class="py-4">
    <div class="container">
      <h1 class="text-white mb-3">La jungle tropicale</h1>
      <div class="intro-jungle p-3 p-md-4">
        <p class="mb-0 text-white"><?= e($habitat['description']) ?></p>
      </div>
    </div>
  </section>

  <!-- Grille des animaux -->
  <main class="pb-5">
    <div class="container">
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">

        <?php foreach ($animaux as $animal): ?>
          <div class="col">
            <article class="card h-100 p-3 text-center">
              <?php if ($animal['image']): ?>
                <img src="<?= e($animal['image']) ?>"
                     alt="<?= e($animal['image_alt']) ?>"
                     class="avatar-128 mx-auto mb-3" loading="lazy" decoding="async">
              <?php endif; ?>

              <h3 class="h5 mb-2">
                <a href="animal.php?id=<?= (int) $animal['id'] ?>"
                   class="text-decoration-none btn-text-green"><?= e($animal['name']) ?></a>
              </h3>
              <p class="small mb-3"><?= e($animal['description']) ?></p>

              <ul class="list-unstyled text-start small mb-0">
                <li><strong>Nourriture :</strong> <?= e($animal['diet']) ?></li>
                <li><strong>État :</strong> <?= e($animal['health_state']) ?></li>
              </ul>
            </article>
          </div>
        <?php endforeach; ?>

      </div>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
