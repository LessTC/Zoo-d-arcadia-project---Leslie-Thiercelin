<?php

declare(strict_types=1);

/**Gestion de la connexion et des rôles.**/

require_once __DIR__ . '/db.php';

/** Sécurité - trois réglages */
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

/* Enregistre l'utilisateur comme connecté.*/
function connecter_utilisateur(array $utilisateur): void
{
    demarrer_session();

    /*sécurité par régeneration de session*/
    session_regenerate_id(true);

    $_SESSION['utilisateur'] = [
        'id'     => (int) $utilisateur['id'],
        'email'  => $utilisateur['email'],
        'prenom' => $utilisateur['first_name'],
        'nom'    => $utilisateur['last_name'],
        'role'   => $utilisateur['role'],
    ];
}

/*Renvoie l'utilisateur connecté ou null. */
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

function page_accueil_role(string $role): string
{
    return match ($role) {
        'Administrateur' => 'Dashboard_admin.php',
        'Employe'        => 'Espace_soigneur.php',
        'Veterinaire'    => 'Espace_veterinaire.php',
        default          => 'index.php',
    };
}

/*Barrière sécurité au début des pages du back-office.*/
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
