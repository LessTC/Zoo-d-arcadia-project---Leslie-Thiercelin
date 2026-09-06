<?php

declare(strict_types=1);

/**
 * Gestion de la connexion et des rôles.
 *
 * Le principe : HTTP ne se souvient de rien. Chaque page demandée est une
 * requête indépendante, le serveur ne sait pas que c'est « la même personne
 * qu'il y a dix secondes ». La SESSION corrige ça : le serveur range les
 * informations de la personne connectée dans un casier, et remet au
 * navigateur un simple numéro de casier, stocké dans un cookie.
 *
 * Conséquence importante : le contenu du casier reste sur le serveur. Le
 * navigateur ne détient que le numéro. Personne ne peut donc se déclarer
 * administrateur en modifiant quelque chose côté client.
 */

require_once __DIR__ . '/db.php';

/**
 * Ouvre la session, sauf si elle l'est déjà.
 *
 * Les trois attributs du cookie sont déclarés AVANT session_start(), car
 * c'est cet appel qui envoie le cookie au navigateur : après, il est trop
 * tard pour en changer les règles.
 *
 *   samesite = Lax  protège du CSRF (falsification de requête entre sites).
 *                   Le navigateur joint normalement le cookie de session en
 *                   fonction de la DESTINATION de la requête, sans regarder
 *                   d'où elle part : une page malveillante pourrait donc
 *                   déclencher une suppression sur le dashboard au nom d'un
 *                   administrateur connecté. « Lax » interdit d'envoyer le
 *                   cookie sur une requête POST venant d'un autre domaine,
 *                   tout en le conservant quand la personne arrive sur le
 *                   site par un lien ordinaire.
 *
 *   httponly = true rend le cookie invisible à JavaScript. Si une faille XSS
 *                   passait malgré l'échappement, le numéro de session ne
 *                   pourrait pas être lu et recopié ailleurs.
 *
 *   secure          n'impose HTTPS qu'en production. En local, XAMPP sert le
 *                   site en HTTP : exiger HTTPS empêcherait toute connexion.
 *                   On se cale donc sur le protocole réellement utilisé.
 *
 * Ces trois réglages ne remplacent pas un jeton anti-CSRF par formulaire.
 * Ils constituent une défense en profondeur, à moindre coût.
 */
function demarrer_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);

        session_start();
    }
}

/**
 * Enregistre l'utilisateur comme connecté.
 *
 * On ne range JAMAIS l'empreinte du mot de passe en session : elle n'y
 * servirait à rien et n'a aucune raison de circuler.
 */
function connecter_utilisateur(array $utilisateur): void
{
    demarrer_session();

    // Change le numéro de casier au moment précis de la connexion. Sans ça,
    // quelqu'un qui aurait deviné le numéro AVANT la connexion en
    // profiterait après. C'est la « fixation de session ».
    session_regenerate_id(true);

    $_SESSION['utilisateur'] = [
        'id'     => (int) $utilisateur['id'],
        'email'  => $utilisateur['email'],
        'prenom' => $utilisateur['first_name'],
        'nom'    => $utilisateur['last_name'],
        'role'   => $utilisateur['role'],
    ];
}

/** Renvoie l'utilisateur connecté, ou null si personne ne l'est. */
function utilisateur_connecte(): ?array
{
    demarrer_session();

    return $_SESSION['utilisateur'] ?? null;
}

function est_connecte(): bool
{
    return utilisateur_connecte() !== null;
}

/** Vide le casier et le détruit. */
function deconnecter(): void
{
    demarrer_session();
    $_SESSION = [];
    session_destroy();
}

/** Page vers laquelle envoyer chaque rôle après sa connexion. */
function page_accueil_role(string $role): string
{
    return match ($role) {
        'Administrateur' => 'Dashboard_admin.php',
        'Employe'        => 'Espace_soigneur.php',
        'Veterinaire'    => 'Espace_veterinaire.php',
        default          => 'index.php',
    };
}

/**
 * Barrière à placer en TOUT DÉBUT des pages du back-office.
 *
 * Elle vérifie deux choses : que la personne est connectée, et que son rôle
 * fait partie de ceux autorisés. La vérification est faite AVANT le moindre
 * affichage : masquer un bouton ne protège rien, seul le serveur décide.
 *
 * Renvoie l'utilisateur, pour éviter d'avoir à le redemander ensuite.
 */
function exiger_role(string ...$rolesAutorises): array
{
    $utilisateur = utilisateur_connecte();

    if ($utilisateur === null) {
        header('Location: Connexion.php?erreur=connexion_requise');
        exit;
    }

    if (!in_array($utilisateur['role'], $rolesAutorises, true)) {
        http_response_code(403);
        exit('Accès refusé : cet espace ne vous est pas ouvert.');
    }

    return $utilisateur;
}
