<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';
require __DIR__ . '/includes/courriel.php';

$erreurs = [];
$ancien  = ['email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ancien['email']   = trim($_POST['email'] ?? '');
    $ancien['phone']   = trim($_POST['phone'] ?? '');
    $ancien['subject'] = trim($_POST['subject'] ?? '');
    $ancien['message'] = trim($_POST['message'] ?? '');

    if (!filter_var($ancien['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = 'Merci d’indiquer une adresse e-mail valide.';
    }

    if ($ancien['subject'] === '') {
        $erreurs['subject'] = 'Merci d’indiquer un sujet.';
    } elseif (mb_strlen($ancien['subject']) > 150) {
        $erreurs['subject'] = 'Le sujet ne doit pas dépasser 150 caractères.';
    }

    if ($ancien['message'] === '') {
        $erreurs['message'] = 'Merci d’écrire votre message.';
    } elseif (mb_strlen($ancien['message']) > 600) {
        $erreurs['message'] = 'Le message ne doit pas dépasser 600 caractères.';
    }

    if (!$erreurs) {
        $requete = db()->prepare(
            'INSERT INTO contact_messages (email, phone, subject, message)
             VALUES (?, ?, ?, ?)'
        );
        $requete->execute([
            $ancien['email'],
            $ancien['phone'] !== '' ? $ancien['phone'] : null,
            $ancien['subject'],
            $ancien['message'],
        ]);

        // US 10 : la demande est aussi transmise par courriel au zoo, pour
        // que l'employé puisse répondre directement au visiteur.
        envoyer_courriel(
            'contact@arcadia.fr',
            'Nouveau message : ' . $ancien['subject'],
            "Un visiteur a écrit depuis le formulaire de contact.\n\n"
            . "Répondre à : {$ancien['email']}\n"
            . ($ancien['phone'] !== '' ? "Téléphone   : {$ancien['phone']}\n" : '')
            . "\n{$ancien['message']}"
        );

        header('Location: contact.php?envoye=1');
        exit;
    }
}

$titrePage       = 'Contact — Zoo d’Arcadia';
$descriptionPage = 'Contactez l’équipe du Zoo d’Arcadia.';
$classeBody      = 'bg-contact';

require __DIR__ . '/includes/header.php';
?>

  <!-- Formulaire -->
  <main class="d-flex align-items-center" id="contact">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
          <article class="card card-contact shadow-lg p-4 p-md-5">
            <h1 class="h3 text-center mb-4">Contact</h1>

            <?php if (isset($_GET['envoye'])): ?>
              <div class="alert alert-success" role="alert">
                Merci ! Votre message a bien été transmis à notre équipe.
              </div>
            <?php endif; ?>

            <form action="contact.php" method="post" novalidate>
              <div class="mb-3">
                <label for="email" class="form-label">Adresse mail</label>
                <input id="email" name="email" type="email"
                       class="form-control<?= isset($erreurs['email']) ? ' is-invalid' : '' ?>"
                       value="<?= e($ancien['email']) ?>"
                       placeholder="nom@exemple.com" autocomplete="email" required>
                <?php if (isset($erreurs['email'])): ?>
                  <div class="invalid-feedback"><?= e($erreurs['email']) ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label for="phone" class="form-label">Numéro de téléphone (facultatif)</label>
                <input id="phone" name="phone" type="tel" class="form-control"
                       value="<?= e($ancien['phone']) ?>"
                       placeholder="06 12 34 56 78" autocomplete="tel">
              </div>

              <div class="mb-3">
                <label for="subject" class="form-label">Sujet</label>
                <input id="subject" name="subject" type="text"
                       class="form-control<?= isset($erreurs['subject']) ? ' is-invalid' : '' ?>"
                       value="<?= e($ancien['subject']) ?>"
                       placeholder="Votre sujet" required>
                <?php if (isset($erreurs['subject'])): ?>
                  <div class="invalid-feedback"><?= e($erreurs['subject']) ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label for="message" class="form-label">Votre message</label>
                <textarea id="message" name="message" rows="5"
                          class="form-control<?= isset($erreurs['message']) ? ' is-invalid' : '' ?>"
                          placeholder="Écrivez votre message…" maxlength="600" required><?= e($ancien['message']) ?></textarea>
                <?php if (isset($erreurs['message'])): ?>
                  <div class="invalid-feedback"><?= e($erreurs['message']) ?></div>
                <?php endif; ?>
              </div>

              <button type="submit" class="btn btn-brand w-100">Envoyer</button>
            </form>
          </article>
        </div>
      </div>
    </div>
  </main>
  

  <?php require __DIR__ . '/includes/footer.php'; ?>
