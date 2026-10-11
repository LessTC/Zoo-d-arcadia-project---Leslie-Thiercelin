<?php

declare(strict_types=1);

/**
 * Administration des comptes du back-office (US6 / US9).
 *
 * À lancer en ligne de commande depuis la racine du projet :
 *     php sql/03_create_user.php
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

/**
 * Demande un mot de passe, le fait confirmer, et renvoie son empreinte.
 */
function demander_empreinte(): string
{
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

    return $empreinte;
}

try {
    $pdo = db();

    echo "=== Comptes du back-office — Zoo d'Arcadia ===\n\n";

    $email = demander('Adresse e-mail : ');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Adresse e-mail invalide.\n");
    }
    $requete = $pdo->prepare(
        'SELECT u.id, u.first_name, u.last_name, r.label AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.email = ?'
    );
    $requete->execute([$email]);
    $existant = $requete->fetch();

    // ------------------------------------------------------------------
    //  Compte existant — redéfiniton de mot de passe.
    // ------------------------------------------------------------------
    if ($existant) {
        echo "\nCompte existant : {$existant['first_name']} {$existant['last_name']}"
           . " — {$existant['role']}\n";

        if (strtolower(demander('Redéfinir son mot de passe ? (o/n) : ')) !== 'o') {
            exit("Opération annulée.\n");
        }

        $requete = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $requete->execute([demander_empreinte(), $existant['id']]);

        echo "\nMot de passe redéfini pour {$email}.\n";
        echo "Il doit être communiqué de vive voix : jamais par courriel.\n";
        exit(0);
    }

    // ------------------------------------------------------------------
    //  Adresse inconnue — création de compte.
    // ------------------------------------------------------------------
    echo "\nAucun compte avec cette adresse : création d'un nouveau compte.\n\n";


    $roles = $pdo->query('SELECT id, label FROM roles ORDER BY id')->fetchAll();

    if (!$roles) {
        exit("Aucun rôle en base. Exécute d'abord sql/02_donnees.sql.\n");
    }

    echo "Rôles disponibles :\n";
    foreach ($roles as $role) {
        echo "  {$role['id']} — {$role['label']}\n";
    }

    $roleId = (int) demander("\nNuméro du rôle : ");

    if (!in_array($roleId, array_map('intval', array_column($roles, 'id')), true)) {
        exit("Numéro de rôle inconnu.\n");
    }

    $prenom = demander('Prénom : ');
    $nom    = demander('Nom : ');

    if ($prenom === '' || $nom === '') {
        exit("Le prénom et le nom sont obligatoires.\n");
    }

    $requete = $pdo->prepare(
        'INSERT INTO users (email, password_hash, last_name, first_name, role_id)
         VALUES (:email, :hash, :nom, :prenom, :role)'
    );

    $requete->execute([
        ':email'  => $email,
        ':hash'   => demander_empreinte(),
        ':nom'    => $nom,
        ':prenom' => $prenom,
        ':role'   => $roleId,
    ]);

    echo "\nCompte créé (identifiant " . $pdo->lastInsertId() . ") pour {$email}.\n";
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        exit("\nCette adresse e-mail est déjà utilisée par un compte.\n");
    }

    exit("\nErreur base de données : " . $e->getMessage() . "\n");
}
