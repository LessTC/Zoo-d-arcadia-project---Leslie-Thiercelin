<?php

declare(strict_types=1);

/**
 * Lecture d'une fiche animal, partagée par deux points d'affichage.
 *
 * La page animal.php et le point d'entrée JSON api/animal.php lisent
 * exactement la même donnée.
 */

require_once __DIR__ . '/db.php';

/** La fiche d'un animal, ou null si l'identifiant n'existe pas. */
function animal_detail(int $animalId): ?array
{
    $requete = db()->prepare(
        'SELECT a.id, a.name, a.species, a.description, a.diet, a.health_state,
                a.image, a.image_alt, a.habitat_id, h.name AS habitat
         FROM animals a
         JOIN habitats h ON h.id = a.habitat_id
         WHERE a.id = ?'
    );
    $requete->execute([$animalId]);

    return $requete->fetch() ?: null;
}

/** Le dernier passage du vétérinaire sur cet animal, s'il y en a eu un. */
function animal_dernier_rapport(int $animalId): ?array
{
    $requete = db()->prepare(
        'SELECT visit_date, animal_state, proposed_food, food_grams
         FROM veterinary_reports
         WHERE animal_id = ?
         ORDER BY visit_date DESC, id DESC
         LIMIT 1'
    );
    $requete->execute([$animalId]);

    return $requete->fetch() ?: null;
}