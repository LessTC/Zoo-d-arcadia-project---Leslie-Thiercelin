<?php

declare(strict_types=1);

/**
 * Connexion à la base de données du Zoo d'Arcadia.
 *
 * Toutes les pages du site passent par cette fonction : il n'existe qu'un
 * seul endroit où la connexion est configurée. Elle renvoie toujours la
 * même instance PDO, créée à la première demande seulement.
 */
function db(): PDO
{
    // La variable static conserve sa valeur d'un appel à l'autre : la
    // connexion n'est ouverte qu'une fois, même si dix requêtes suivent.
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/../config/config.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        // Une erreur SQL lève une exception au lieu de passer inaperçue.
        // Sans cette option, une requête ratée renvoie false en silence.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // Les résultats arrivent en tableaux associatifs ($ligne['name']),
        // et non dupliqués en version indexée et associative.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Vraies requêtes préparées, envoyées au serveur MySQL séparément
        // de leurs paramètres. C'est ce qui rend l'injection SQL impossible :
        // la protection ne repose pas sur un échappement fait côté PHP.
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
