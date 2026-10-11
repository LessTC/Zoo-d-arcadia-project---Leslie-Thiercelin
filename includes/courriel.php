<?php

declare(strict_types=1);

/*Envoi de courriels -> écrit dans un fichier du dossier courriels/. */

function envoyer_courriel(string $destinataire, string $sujet, string $message): bool
{
    $dossier = __DIR__ . '/../courriels';

    if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
        error_log('Impossible de créer le dossier courriels/');
        return false;
    }

    $adresseSure = preg_replace('/[^a-z0-9]+/i', '-', $destinataire);
    $chemin      = $dossier . '/' . date('Y-m-d_His') . '_' . $adresseSure . '.txt';

    $contenu = "À        : {$destinataire}\n"
             . "Sujet    : {$sujet}\n"
             . "Envoyé le: " . date('d/m/Y à H:i:s') . "\n"
             . str_repeat('-', 60) . "\n\n"
             . $message . "\n";

    if (file_put_contents($chemin, $contenu) === false) {
        error_log('Écriture du courriel impossible : ' . $chemin);
        return false;
    }

    return true;
}
