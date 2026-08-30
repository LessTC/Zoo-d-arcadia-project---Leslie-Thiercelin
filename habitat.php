<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/fonctions.php';

// (int) ramène à 0 tout ce qui n'est pas un nombre : « ?id=abc » donne 0,
// donc aucun habitat trouvé, donc la page « introuvable » ci-dessous.
$habitatId = (int) ($_GET['id'] ?? 0);

// La valeur vient de l'URL, donc du visiteur : prepare() + execute() est
// obligatoire. La valeur voyage à part et ne peut pas devenir du SQL.
$requete = db()->prepare('SELECT id, name, description FROM habitats WHERE id = ?');
$requete->execute([$habitatId]);
$habitat = $requete->fetch();

if (!$habitat) {
    http_response_code(404);
    $titrePage = 'Habitat introuvable — Zoo d’Arcadia';
    require __DIR__ . '/includes/header.php';
    echo '<main class="container py-5"><h1 class="h3">Habitat introuvable</h1>'
       . '<p><a href="habitats.php">Voir tous les habitats</a></p></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$requete = db()->prepare(
    'SELECT id, name, description, diet, health_state, image, image_alt
     FROM animals
     WHERE habitat_id = ?
     ORDER BY id'
);
$requete->execute([$habitat['id']]);
$animaux = $requete->fetchAll();

$titrePage       = $habitat['name'] . ' — Zoo d’Arcadia';
$descriptionPage = 'L’habitat « ' . $habitat['name'] . ' » du Zoo d’Arcadia et les animaux qui y vivent.';
// Deux classes : bg-habitat sert de repli, et bg-savane / bg-jungle /
// bg-marais prend le dessus quand la charte prévoit un dégradé dédié.
$classeBody      = 'bg-habitat bg-' . strtolower($habitat['name']);

require __DIR__ . '/includes/header.php';
?>

  <!-- Intro : nom et description de l'habitat -->
  <section class="py-4">
    <div class="container">
      <h1 class="text-white mb-3"><?= e($habitat['name']) ?></h1>
      <div class="intro-habitat p-3 p-md-4">
        <p class="mb-0 text-white"><?= e($habitat['description']) ?></p>
      </div>
    </div>
  </section>

  <!-- Grille des animaux -->
  <main class="pb-5">
    <div class="container">

      <?php if (!$animaux): ?>
        <div class="card p-4">
          <p class="mb-0">Aucun animal n’est présenté dans cet habitat pour le moment.</p>
        </div>
      <?php endif; ?>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
        <?php foreach ($animaux as $animal): ?>
          <div class="col">
            <article class="card h-100 p-3 text-center">
              <?php if ($animal['image']): ?>
                <img src="<?= e($animal['image']) ?>"
                     alt="<?= e($animal['image_alt']) ?>"
                     class="avatar-128 mx-auto mb-3" loading="lazy" decoding="async">
              <?php endif; ?>

              <h2 class="h5 mb-2">
                <a href="animal.php?id=<?= (int) $animal['id'] ?>"
                   class="text-decoration-none btn-text-green"><?= e($animal['name']) ?></a>
              </h2>
              <p class="small mb-3"><?= e($animal['description']) ?></p>

              <ul class="list-unstyled text-start small mb-0">
                <li><strong>Nourriture :</strong> <?= e($animal['diet']) ?></li>
                <li><strong>État :</strong> <?= e($animal['health_state']) ?></li>
              </ul>
            </article>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-4">
        <a href="habitats.php" class="btn btn-brand">← Tous les habitats</a>
      </div>

    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>