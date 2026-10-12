<?php

declare(strict_types=1);

/*Connexion à la base de données du Zoo d'Arcadia.*/
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    require_once __DIR__ . '/configuration.php';
    $config = configuration();
    
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Vraies requêtes préparées, envoyées au serveur MySQL séparément
        // de leurs paramètres. Rend l'injection SQL impossible.
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
