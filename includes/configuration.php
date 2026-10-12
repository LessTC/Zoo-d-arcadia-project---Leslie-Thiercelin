<?php

declare(strict_types=1);

/**
 * Configuration de l'application, par ordre de priorité :
 *   1. les variables d'environnement, posées par Docker ;
 *   2. le fichier config/config.php, s'il existe (XAMPP, alwaysdata) ;
 *   3. des valeurs par défaut.
 *
 * L'ordre compte : le dossier du projet étant monté dans le conteneur,
 * config/config.php y est visible avec les valeurs de la machine locale.
 * Sans cette priorité, le conteneur chercherait la base sur 127.0.0.1.
 */
function configuration(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $fichier = __DIR__ . '/../config/config.php';
    $local   = is_file($fichier) ? require $fichier : [];

    // getenv() renvoie false si la variable n'existe pas — à distinguer
    // d'un mot de passe vide, qui est la configuration de XAMPP par défaut.
    $env = static fn(string $nom, $defaut) => getenv($nom) !== false ? getenv($nom) : $defaut;

    $config = [
        'host'            => $env('DB_HOST',    $local['host']     ?? '127.0.0.1'),
        'port'            => (int) $env('DB_PORT', $local['port']  ?? 3306),
        'database'        => $env('DB_NAME',    $local['database'] ?? 'arcadia'),
        'user'            => $env('DB_USER',    $local['user']     ?? 'root'),
        'password'        => $env('DB_PASS',    $local['password'] ?? ''),
        'charset'         => $env('DB_CHARSET', $local['charset']  ?? 'utf8mb4'),
        'mongo_uri'       => $env('MONGO_URI',  $local['mongo_uri'] ?? 'mongodb://127.0.0.1:27017'),
        'mongo_namespace' => $env('MONGO_NS',   $local['mongo_namespace'] ?? 'arcadia.consultations'),
    ];

    return $config;
}