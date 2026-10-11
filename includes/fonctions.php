<?php

declare(strict_types=1);

/**
 * Prépare un texte pour l'affichage dans une page HTML.
 * Tout texte venant de la base ou d'un formulaire doit passer par ici (sécurité faille XSS). */
function e(?string $texte): string
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

function moisAnnee(string $dateSql): string
{
    $mois = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    $horodatage = strtotime($dateSql);

    return $mois[(int) date('n', $horodatage)] . ' ' . date('Y', $horodatage);
}
