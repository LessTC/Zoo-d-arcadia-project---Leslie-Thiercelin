<?php

declare(strict_types=1);

/**
 * Point d'entrée JSON : renvoie la fiche d'un animal sous forme de données
 * brutes, au lieu d'une page HTML complète.
 *
 * Il sert la modale ouverte depuis la page d'un habitat. Le visiteur reste
 * où il est, seule la portion d'écran concernée change — c'est le cas
 * d'usage d'une requête asynchrone.
 */

require __DIR__ . '/../includes/animaux.php';
require __DIR__ . '/../includes/mongo.php';

header('Content-Type: application/json; charset=utf-8');

$animalId = (int) ($_GET['id'] ?? 0);
$animal   = animal_detail($animalId);

if ($animal === null) {
    http_response_code(404);
    echo json_encode(['erreur' => 'Animal introuvable'], JSON_UNESCAPED_UNICODE);
    exit;
}

// US 11 : la consultation est comptée ici aussi. Sans cette ligne, une
// fiche consultée en modale ne serait jamais comptabilisée, puisque
// aucune page n'est rechargée à ce moment-là.
incrementer_consultation((int) $animal['id']);

$rapport = animal_dernier_rapport((int) $animal['id']);

// Les clés sont renommées en français : le JSON est une interface, elle
// n'a pas à exposer les noms de colonnes de la base.
echo json_encode([
    'id'          => (int) $animal['id'],
    'nom'         => $animal['name'],
    'espece'      => $animal['species'],
    'description' => $animal['description'],
    'nourriture'  => $animal['diet'],
    'etat'        => $animal['health_state'],
    'habitat'     => $animal['habitat'],
    'image'       => $animal['image'],
    'imageAlt'    => $animal['image_alt'],
    'rapport'     => $rapport ? [
        'date' => date('d/m/Y', strtotime($rapport['visit_date'])),
        'etat' => $rapport['animal_state'],
    ] : null,
], JSON_UNESCAPED_UNICODE);