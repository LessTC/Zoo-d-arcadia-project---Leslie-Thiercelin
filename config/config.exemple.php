<?php

/**
 * MODÈLE de configuration — cversionné sur Git.
 *
 * Pour installer le projet : copier ce fichier sous le nom config.php
 * dans le même dossier, puis adapter les valeurs à sa machine.
 *
 */

return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'arcadia',
    'user'     => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    // MongoDB — pour le compteur de consultations
    'mongo_uri'       => 'mongodb://127.0.0.1:27017',
    'mongo_namespace' => 'arcadia.consultations',
];
