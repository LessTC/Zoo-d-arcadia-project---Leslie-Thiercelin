<?php

/**
 * En-tête commun à toutes les pages du site.
 *
 * Même principe que style.css : le header n'est écrit qu'une fois. Le jour
 * où tu ajoutes une entrée au menu, tu modifies ce seul fichier et les
 * pages du site suivent.
 *
 * La page qui l'inclut peut définir AVANT l'inclusion :
 *   $titrePage       — le titre affiché dans l'onglet du navigateur
 *   $descriptionPage — la phrase de description pour les moteurs de recherche
 *   $classeBody      — la classe CSS du <body>, ex. « bg-savane »
 *
 * L'opérateur ?? fournit une valeur de repli si la variable n'existe pas,
 * ce qui évite une erreur sur une page qui aurait oublié de la définir.
 *
 * Le bloc de droite s'adapte tout seul : icône de connexion pour un
 * visiteur, prénom et bouton Déconnexion pour une personne identifiée.
 * Un seul en-tête sert donc au site public et au back-office.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/fonctions.php';

$titrePage       = $titrePage       ?? 'Zoo d’Arcadia';
$descriptionPage = $descriptionPage ?? 'Zoo d’Arcadia en Bretagne. Approche pédagogique et sensibilisation au bien-être animal.';
$classeBody      = $classeBody      ?? '';

$utilisateurConnecte = utilisateur_connecte();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titrePage) ?></title>
  <meta name="description" content="<?= e($descriptionPage) ?>">

  <link href="https://fonts.googleapis.com/css2?family=Inknut+Antiqua:wght@400;600;700&family=Averia+Sans+Libre:wght@300;400;700&display=swap" rel="stylesheet">

  <!-- Bootstrap + Icônes -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Styles -->
  <link href="style.css" rel="stylesheet">
</head>

<body class="<?= e($classeBody) ?>">

  <!-- Header -->
  <header>
    <nav class="navbar" data-bs-theme="dark">
      <div class="container d-flex align-items-center justify-content-between">
        <!-- Titre -->
        <a class="navbar-brand m-0 fw-semibold" href="index.php">ZOO D’ARCADIA</a>

        <!-- Droite : Connexion + burger -->
        <div class="d-flex align-items-center gap-2 nav-actions">
          <?php if ($utilisateurConnecte): ?>
            <a href="<?= e(page_accueil_role($utilisateurConnecte['role'])) ?>"
               class="nav-link p-0 me-2 d-flex align-items-center gap-1"
               title="Retour à mon espace">
              <i class="bi bi-speedometer2 fs-5"></i>
              <span class="small d-none d-sm-inline">
                <?= e($utilisateurConnecte['prenom']) ?> — <?= e($utilisateurConnecte['role']) ?>
              </span>
            </a>
            <a href="deconnexion.php" class="btn btn-sm btn-light">Déconnexion</a>
          <?php else: ?>
            <a href="Connexion.php" class="nav-link p-0" aria-label="Connexion utilisateur">
              <i class="bi bi-person-circle fs-3"></i>
            </a>
          <?php endif; ?>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                  aria-controls="nav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
          </button>

          <!-- Menu replié -->
          <div id="nav" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
              <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
              <li class="nav-item"><a class="nav-link" href="index.php#decouvrir">Habitats</a></li>
              <li class="nav-item"><a class="nav-link" href="Services.php">Services</a></li>
              <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>
  </header>
