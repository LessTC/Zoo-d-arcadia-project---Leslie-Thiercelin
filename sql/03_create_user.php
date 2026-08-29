<?php

declare(strict_types=1);

/**
 * Création d'un compte du back-office (US6 / US9).
 *
 * À lancer en ligne de commande depuis la racine du projet :
 *     php sql/03_create_user.php
 *
 * Le mot de passe est demandé à la saisie, haché immédiatement, et seule
 * l'empreinte part en base. Il n'est écrit dans aucun fichier, ne figure
 * dans aucun script versionné, et n'est jamais envoyé par courriel —
 * c'est l'exigence de l'énoncé.
 */

if (PHP_SAPI !== 'cli') {
    exit("Ce script doit être lancé en ligne de commande, pas depuis un navigateur.\n");
}

require __DIR__ . '/../includes/db.php';

/** Pose une question et renvoie la réponse saisie, sans espaces superflus. */
function demander(string $question): string
{
    echo $question;
    return trim((string) fgets(STDIN));
}

try {
    $pdo = db();

    echo "=== Création d'un compte Zoo d'Arcadia ===\n\n";

    // On lit les rôles en base plutôt que de les écrire en dur :
    // si un rôle est ajouté un jour, ce script suit automatiquement.
    $roles = $pdo->query('SELECT id, label FROM roles ORDER BY id')->fetchAll();

    if (!$roles) {
        exit("Aucun rôle en base. Exécute d'abord sql/02_donnees.sql.\n");
    }

    echo "Rôles disponibles :\n";
    foreach ($roles as $role) {
        echo "  {$role['id']} — {$role['label']}\n";
    }

    $roleId = (int) demander("\nNuméro du rôle : ");

    $idsValides = array_column($roles, 'id');
    if (!in_array($roleId, array_map('intval', $idsValides), true)) {
        exit("Numéro de rôle inconnu.\n");
    }

    $email = demander('Adresse e-mail : ');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Adresse e-mail invalide.\n");
    }

    $prenom = demander('Prénom : ');
    $nom    = demander('Nom : ');

    if ($prenom === '' || $nom === '') {
        exit("Le prénom et le nom sont obligatoires.\n");
    }

    $motDePasse = demander('Mot de passe (8 caractères minimum) : ');
    if (strlen($motDePasse) < 8) {
        exit("Mot de passe trop court.\n");
    }

    if ($motDePasse !== demander('Confirmation du mot de passe : ')) {
        exit("Les deux saisies ne correspondent pas.\n");
    }

    // password_hash() choisit l'algorithme recommandé du moment (bcrypt
    // aujourd'hui) et génère lui-même un sel aléatoire unique. Deux comptes
    // ayant le même mot de passe produisent donc deux empreintes différentes.
    $empreinte = password_hash($motDePasse, PASSWORD_DEFAULT);

    // Le mot de passe en clair ne doit pas traîner en mémoire plus longtemps.
    unset($motDePasse);

    $requete = $pdo->prepare(
        'INSERT INTO users (email, password_hash, last_name, first_name, role_id)
         VALUES (:email, :hash, :nom, :prenom, :role)'
    );

    $requete->execute([
        ':email'  => $email,
        ':hash'   => $empreinte,
        ':nom'    => $nom,
        ':prenom' => $prenom,
        ':role'   => $roleId,
    ]);

    echo "\nCompte créé (identifiant " . $pdo->lastInsertId() . ") pour {$email}.\n";
} catch (PDOException $e) {
    // 23000 = violation de contrainte ; ici, l'unicité de l'adresse e-mail.
    if ($e->getCode() === '23000') {
        exit("\nCette adresse e-mail est déjà utilisée par un compte.\n");
    }
    exit("\nErreur base de données : " . $e->getMessage() . "\n");
}
