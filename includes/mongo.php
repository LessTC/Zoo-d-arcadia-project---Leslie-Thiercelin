<?php

declare(strict_types=1);

/*Compteur de consultations des fiches animaux (US11). */

/** Charge la configuration une seule fois. */
function mongo_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }

    return $config;
}

/** Une seule connexion pour toute la page, comme pour PDO. */
function mongo(): MongoDB\Driver\Manager
{
    static $manager = null;

    if ($manager === null) {
        $manager = new MongoDB\Driver\Manager(mongo_config()['mongo_uri']);
    }

    return $manager;
}

/*Ajoute une vue à la fiche d'un animal.*/
function incrementer_consultation(int $animalId): void
{
    try {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['animal_id' => $animalId],                 // qui
            ['$inc' => ['views' => 1]],                 // quoi : +1
            ['upsert' => true]                          // créer si absent
        );

        mongo()->executeBulkWrite(mongo_config()['mongo_namespace'], $bulk);
    } catch (Throwable $e) {
        error_log('Compteur MongoDB indisponible : ' . $e->getMessage());
    }
}

/*Renvoie [identifiant d'animal => nombre de vues], du plus vu au moins vu.*/
function consultations(): array
{
    try {
        $requete = new MongoDB\Driver\Query([], ['sort' => ['views' => -1]]);
        $curseur = mongo()->executeQuery(mongo_config()['mongo_namespace'], $requete);

        $resultat = [];
        foreach ($curseur as $document) {
            $resultat[(int) $document->animal_id] = (int) $document->views;
        }

        return $resultat;
    } catch (Throwable $e) {
        error_log('Compteur MongoDB indisponible : ' . $e->getMessage());
        return [];
    }
}