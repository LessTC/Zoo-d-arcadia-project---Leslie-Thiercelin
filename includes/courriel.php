<?php

declare(strict_types=1);

/**
 * Envoi de courriels.
 *
 * XAMPP n'expédie aucun message sous Windows : la fonction mail() de PHP
 * échoue en silence faute de serveur SMTP. Plutôt que de laisser croire
 * qu'un courriel est parti, on l'écrit dans un fichier du dossier
 * courriels/ — daté, lisible, vérifiable.
 *
 * Le code appelant est ainsi identique à ce qu'il serait en production :
 * seule cette fonction changera au déploiement, en remplaçant l'écriture
 * du fichier par un envoi SMTP réel. Le reste de l'application n'aura pas
 * une ligne à modifier.
 */

function envoyer_courriel(string $destinataire, string $sujet, string $message): bool
{
    $dossier = __DIR__ . '/../courriels';

    if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
        error_log('Impossible de créer le dossier courriels/');
        return false;
    }

    // Le nom du fichier ne doit contenir que des caractères sûrs : on
    // remplace tout le reste, sinon une adresse malformée pourrait
    // fabriquer un chemin vers un autre dossier.
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
