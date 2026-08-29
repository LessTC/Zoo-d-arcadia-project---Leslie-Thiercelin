<?php

/**
 * MODÈLE de configuration — celui-ci EST versionné sur Git.
 *
 * Pour installer le projet : copier ce fichier sous le nom config.php
 * dans le même dossier, puis adapter les valeurs à sa machine.
 *
 * config.php, lui, n'est jamais versionné (voir .gitignore) : il contient
 * les identifiants de connexion, qui n'ont rien à faire dans un dépôt.
 */

return [
    // 127.0.0.1 plutôt que « localhost » : sous Windows, « localhost »
    // déclenche une résolution IPv6 puis IPv4 qui ralentit chaque connexion.
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'arcadia',
    'user'     => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    // MongoDB — uniquement pour le compteur de consultations (US11).
    'mongo_uri'       => 'mongodb://127.0.0.1:27017',
    'mongo_namespace' => 'arcadia.consultations',
];
