<?php

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/fonctions.php';

// Déjà connecté ? Inutile de redemander : on l'envoie chez lui.
if (est_connecte()) {
    header('Location: ' . page_accueil_role(utilisateur_connecte()['role']));
    exit;
}

$erreur     = '';
$emailSaisi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $emailSaisi = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['password'] ?? '';

    $requete = db()->prepare(
        'SELECT u.id, u.email, u.password_hash, u.first_name, u.last_name,
                u.is_active, r.label AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.email = ?'
    );
    $requete->execute([$emailSaisi]);
    $utilisateur = $requete->fetch();

    if ($utilisateur
        && $utilisateur['is_active']
        && password_verify($motDePasse, $utilisateur['password_hash'])) {

        connecter_utilisateur($utilisateur);
        header('Location: ' . page_accueil_role($utilisateur['role']));
        exit;
    }

    $erreur = 'Adresse e-mail ou mot de passe incorrect.';
}

$titrePage       = 'Connexion — Zoo d’Arcadia';
$descriptionPage = 'Espace de connexion du Zoo d’Arcadia.';
$classeBody      = 'bg-connexion';

require __DIR__ . '/includes/header.php';
?>

  <!--Formulaire-->
  <main class="d-flex align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6">
          <article class="card card-login shadow-lg p-4 p-md-5">
            <h1 class="h3 text-center mb-4">
              Connexion
            </h1>

            <?php if ($erreur !== ''): ?>
              <div class="alert alert-danger" role="alert"><?= e($erreur) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'connexion_requise'): ?>
              <div class="alert alert-warning" role="alert">
                Merci de vous connecter pour accéder à cet espace.
              </div>
            <?php endif; ?>

            <form action="Connexion.php" method="post" novalidate>              
              <div class="mb-3">
                <label for="email" class="form-label">Adresse mail</label>
                <input id="email" name="email" type="email" class="form-control"
                       value="<?= e($emailSaisi) ?>" autocomplete="email" required>
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
              </div>

              <button type="submit" class="btn btn-brand w-100">
                Connexion
              </button>
            </form>
          </article>
        </div>
      </div>
    </div>
  </main>

<?php require __DIR__ . '/includes/footer.php'; ?>