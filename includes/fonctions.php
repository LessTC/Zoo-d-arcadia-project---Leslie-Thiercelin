<?php

declare(strict_types=1);

/**
 * Prépare un texte pour l'affichage dans une page HTML.
 *
 * Tout texte venant de la base ou d'un formulaire DOIT passer par ici.
 * Sans cette précaution, un visiteur qui écrirait <script>…</script> dans
 * un champ verrait son code exécuté par le navigateur des autres visiteurs.
 * C'est la faille XSS.
 *
 * La fonction remplace les caractères qui ont un sens en HTML (< > & " ')
 * par leur équivalent inoffensif : le texte s'affiche tel quel au lieu
 * d'être interprété comme du code.
 *
 * Le nom est volontairement court — « e » pour « échapper » — parce qu'on
 * l'écrit des dizaines de fois par page.
 */
function e(?string $texte): string
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Transforme une date de la base (« 2024-06-15 10:00:00 ») en « juin 2024 ».
 *
 * PHP affiche les mois en anglais par défaut. Plutôt que d'installer une
 * extension pour si peu, on garde la liste des douze mois sous la main.
 */
function moisAnnee(string $dateSql): string
{
    $mois = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    $horodatage = strtotime($dateSql);

    return $mois[(int) date('n', $horodatage)] . ' ' . date('Y', $horodatage);
}
