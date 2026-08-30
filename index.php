<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/fonctions.php';

// Messages d'erreur à afficher, et valeurs déjà saisies pour ne pas
// obliger le visiteur à tout retaper si quelque chose cloche.
$erreurs = [];
$ancien  = ['name' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // trim() retire les espaces avant et après : « Marie  » devient « Marie ».
    $ancien['name']    = trim($_POST['name'] ?? '');
    $ancien['subject'] = trim($_POST['subject'] ?? '');
    $ancien['message'] = trim($_POST['message'] ?? '');

    if ($ancien['name'] === '') {
        $erreurs['name'] = 'Merci d’indiquer votre nom.';
    } elseif (mb_strlen($ancien['name']) > 100) {
        $erreurs['name'] = 'Le nom ne doit pas dépasser 100 caractères.';
    }

    if ($ancien['subject'] === '') {
        $erreurs['subject'] = 'Merci d’indiquer un titre.';
    } elseif (mb_strlen($ancien['subject']) > 150) {
        $erreurs['subject'] = 'Le titre ne doit pas dépasser 150 caractères.';
    }

    if ($ancien['message'] === '') {
        $erreurs['message'] = 'Merci d’écrire votre message.';
    }

    if (!$erreurs) {
        $requete = db()->prepare(
            'INSERT INTO reviews (nickname, title, comment) VALUES (?, ?, ?)'
        );
        $requete->execute([$ancien['name'], $ancien['subject'], $ancien['message']]);

        header('Location: index.php?avis=envoye#avis');
        exit;
    }
}

// US 1 : l'accueil doit mentionner les animaux du zoo, pas seulement les
// habitats. Une seule requête, rangée ensuite par habitat — plutôt qu'une
// requête par carte, qui multiplierait les allers-retours vers la base.
$animauxParHabitat = [];
$lignes = db()->query(
    'SELECT a.id, a.name, h.name AS habitat
     FROM animals a
     JOIN habitats h ON h.id = a.habitat_id
     ORDER BY h.name, a.id'
);
foreach ($lignes as $ligne) {
    $animauxParHabitat[$ligne['habitat']][] = $ligne;
}

// Nombre de noms affichés avant le repli. Au-delà, un bouton déplie le reste.
$animauxVisibles = 3;

// Les trois cartes de la section « Découvrir ». Le libellé et la photo sont
// propres à la page d'accueil ; la clé « habitat » fait le lien avec la base.
$cartesHabitats = [
    [
        'habitat' => 'Marais',
        'titre'   => 'Les marais exotiques',
        'page'    => 'marais.php',
        'image'   => 'images/gary-yost-b2iauwRsxOM-unsplash.jpg',
        'alt'     => 'Crocodile au repos dans l’eau',
    ],
    [
        'habitat' => 'Jungle',
        'titre'   => 'La jungle tropicale',
        'page'    => 'jungle.php',
        'image'   => 'images/joshua-j-cotten-dJTmBXaNdxY-unsplash.jpg',
        'alt'     => 'Bébé singe blotti contre sa mère',
    ],
    [
        'habitat' => 'Savane',
        'titre'   => 'La savane sauvage',
        'page'    => 'savane.php',
        'image'   => 'images/jaliya-rasaputra-U_eZSoRUMQM-unsplash.jpg',
        'alt'     => 'Lionne',
    ],
];

$avis = db()->query(
    'SELECT nickname, title, comment, created_at
     FROM reviews
     WHERE is_approved = 1
     ORDER BY created_at DESC'
)->fetchAll();

$titrePage       = 'Zoo d’Arcadia — Accueil';
$descriptionPage = 'Zoo d’Arcadia en Bretagne. Approche pédagogique et sensibilisation au bien-être animal.';
$classeBody      = '';

require __DIR__ . '/includes/header.php';
?>

    <!--Image et premiere partie-->
    <section class="hero">
      <div class="container content py-5">
        <div class="row g-4">
          <div class="col-lg-8">
            <h1 class="mb-3">Arcadia, un zoo à l’écoute de la nature.</h1>
            <div class="panel panel-green mb-3">
              <p class="mb-2">
                Arcadia est un zoo situé en Bretagne, à proximité de la forêt de
                Brocéliande. Créé en 1960, le zoo présente une grande diversité
                d’animaux, répartis par habitat (savane, jungle, marais). Venez
                à leur rencontre !
              </p>
              <p class="mb-0">
                Le soin porté à chaque animal est primordial: chaque jour,
                plusieurs vétérinaires effectuent des contrôles et
                l’alimentation est préparée par nos soigneurs avant l’ouverture.
              </p>
            </div>
          </div>

          <!-- Bloc “infos pratiques”-->
          <div class="col-lg-4">
            <div class="panel panel-red-soft rounded-4 p-3 shadow">
              <h2 class="h5 mb-3 text-white">Infos pratiques</h2>
              <div class="text-white">
                <div class="mb-2">
                  <strong>Adresse</strong>
                  <br />
                  Rue de Brocéliance, 56000 Bretagne
                </div>
                <div class="mb-2">
                  <strong>Ouverture</strong>
                  <br />
                  Lundi – Samedi • 9h–19h
                </div>
                <a href="contact.php" class="btn btn-light btn-sm mt-1" id="contact"
                  >Contact</a
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Titre Découvrir -->
    <main id="content">
      <section id="decouvrir" class="py-5">
        <div class="container">
          <h2 class="mb-4">Venez découvrir nos incroyables pensionnaires !</h2>

          <div class="row row-cols-1 row-cols-md-3 g-4 text-center">
            <?php foreach ($cartesHabitats as $carte): ?>
              <?php
                $animaux  = $animauxParHabitat[$carte['habitat']] ?? [];
                $visibles = array_slice($animaux, 0, $animauxVisibles);
                $restants = array_slice($animaux, $animauxVisibles);
                // Identifiant unique par carte, sinon un clic déplierait les trois.
                $idRepli  = 'autres-' . strtolower($carte['habitat']);
              ?>
              <div class="col">
                <img
                  src="<?= e($carte['image']) ?>"
                  class="avatar-128 img-fluid"
                  alt="<?= e($carte['alt']) ?>"
                />
                <h3 class="h6 mt-3">
                  <a class="text-decoration-none btn-text-green" href="<?= e($carte['page']) ?>">
                    <?= e($carte['titre']) ?>
                  </a>
                </h3>

                <?php if ($visibles): ?>
                  <p class="small text-muted mb-1">
                    <?php foreach ($visibles as $i => $unAnimal): ?>
                      <?= $i > 0 ? ' · ' : '' ?>
                      <a class="text-decoration-none btn-text-green"
                         href="animal.php?id=<?= (int) $unAnimal['id'] ?>"><?= e($unAnimal['name']) ?></a>
                    <?php endforeach; ?>
                  </p>

                  <?php if ($restants): ?>
                    <button class="btn btn-link btn-sm p-0 text-decoration-none" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= e($idRepli) ?>"
                            aria-expanded="false"
                            aria-controls="<?= e($idRepli) ?>">
                      et <?= count($restants) ?> autre<?= count($restants) > 1 ? 's' : '' ?>
                    </button>

                    <div class="collapse" id="<?= e($idRepli) ?>">
                      <p class="small text-muted mb-0">
                        <?php foreach ($restants as $i => $unAnimal): ?>
                          <?= $i > 0 ? ' · ' : '' ?>
                          <a class="text-decoration-none btn-text-green"
                             href="animal.php?id=<?= (int) $unAnimal['id'] ?>"><?= e($unAnimal['name']) ?></a>
                        <?php endforeach; ?>
                      </p>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Lien “expériences” -->
          <div class="mt-5 panel panel-green p-3 rounded-4">
            <p class="mb-2 text-white">
              Et profitez des expériences proposées par notre équipe !
            </p>
            <ul class="mb-0">
              <li>
                <a class="text-white" href="Services.php#visite-guidee"
                  >Visites guidées</a
                >
              </li>
              <li>
                <a class="text-white" href="Services.php#restauration"
                  >Restauration</a
                >
              </li>
            </ul>
          </div>
        </div>
      </section>

      <!-- Avis + Infos -->
      <section id="infos" class="py-5">
        <div class="container">
          <div class="row g-4">
            <!-- Bloc Avis -->
            <div class="col-12 col-lg-6">
              <div class="card card-avis shadow">
                <h2 class="h5 mb-3">Avis</h2>
      
                <?php if (!$avis): ?>
                  <p class="small text-muted mb-0">Aucun avis pour le moment.</p>
                <?php endif; ?>
      
                <?php foreach ($avis as $index => $unAvis): ?>
                  <?php if ($index > 0): ?><hr><?php endif; ?>
                  <div class="small text-muted">
                    “<?= e($unAvis['comment']) ?>” — <?= e($unAvis['nickname']) ?>, <?= moisAnnee($unAvis['created_at']) ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
      
            <!-- Commentaire -->
            <div class="col-12 col-lg-6" id="avis">
              <div class="card h-100">
                <h2 class="h5 mb-3">Laissez nous votre commentaire</h2>
                 <?php if (isset($_GET['avis']) && $_GET['avis'] === 'envoye'): ?>
                    <div class="alert alert-success" role="alert">
                     Merci ! Votre avis a bien été envoyé. Il sera publié après validation par notre équipe.
                   </div>
                 <?php endif; ?>
                 <form action="index.php#avis" method="post" novalidate>
                  <div class="row g-3">
                    <div class="col-12">
                      <label for="name" class="form-label">Nom</label>
                      <input
                        id="name"
                        name="name"
                        type="text"
                        class="form-control<?= isset($erreurs['name']) ? ' is-invalid' : '' ?>"
                        value="<?= e($ancien['name']) ?>"
                        autocomplete="name"
                        required
                      />
                      <?php if (isset($erreurs['name'])): ?>
                        <div class="invalid-feedback"><?= e($erreurs['name']) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="col-12">
                      <label for="subject" class="form-label">Titre</label>
                      <input
                        id="subject"
                        name="subject"
                        type="text"
                        class="form-control<?= isset($erreurs['subject']) ? ' is-invalid' : '' ?>"
                        value="<?= e($ancien['subject']) ?>"
                        required
                      />
                      <?php if (isset($erreurs['subject'])): ?>
                        <div class="invalid-feedback"><?= e($erreurs['subject']) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="col-12">
                      <label for="message" class="form-label"
                        >Votre message</label
                      >
                      <textarea
                        id="message"
                        name="message"
                        rows="5"
                        class="form-control<?= isset($erreurs['message']) ? ' is-invalid' : '' ?>"
                        placeholder="Écrivez votre message…"
                        required
                      ><?= e($ancien['message']) ?></textarea>
                        <?php if (isset($erreurs['message'])): ?>
                          <div class="invalid-feedback"><?= e($erreurs['message']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-brand">
                        Envoyer
                      </button>
                      <!-- Ajouter le backend pour envoyer les messages sur la messagerie Dashboard admin-->
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <?php require __DIR__ . '/includes/footer.php'; ?>
