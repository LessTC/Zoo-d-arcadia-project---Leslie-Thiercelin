<?php

declare(strict_types=1);

/**
 * Administration des comptes du back-office (US6 / US9).
 *
 * À lancer en ligne de commande depuis la racine du projet :
 *     php sql/03_create_user.php
 *
 * Le script commence par demander l'adresse e-mail, puis se comporte
 * différemment selon qu'elle existe déjà ou non :
 *   - adresse inconnue  → création d'un compte ;
 *   - adresse existante → redéfinition de son mot de passe.
 *
 * Cette seconde possibilité répond à un besoin concret : l'US 6 prévoit que
 * l'utilisateur « se rapproche de l'administrateur » pour obtenir son mot de
 * passe, et interdit que celui-ci circule par courriel. Un mécanisme de
 * réinitialisation en libre-service par mail était donc exclu d'emblée ; la
 * redéfinition passe par l'administrateur, comme le sujet le décrit.
 *
 * Dans les deux cas, le mot de passe est demandé à la saisie, haché
 * immédiatement, et seule l'empreinte part en base. Il n'est écrit dans
 * aucun fichier et ne figure dans aucun script versionné.
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
 *
 * Factorisé parce que création et redéfinition ont exactement les mêmes
 * exigences : une seule règle de longueur, un seul appel à password_hash(),
 * donc aucun risque que les deux chemins divergent un jour.
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

    // L'adresse vient d'une saisie : requête préparée, comme partout ailleurs.
    $requete = $pdo->prepare(
        'SELECT u.id, u.first_name, u.last_name, r.label AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.email = ?'
    );
    $requete->execute([$email]);
    $existant = $requete->fetch();

    // ------------------------------------------------------------------
    //  Cas 1 : le compte existe — on redéfinit son mot de passe.
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
    //  Cas 2 : adresse inconnue — on crée le compte.
    // ------------------------------------------------------------------
    echo "\nAucun compte avec cette adresse : création d'un nouveau compte.\n\n";

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
    // 23000 = violation de contrainte ; ici, l'unicité de l'adresse e-mail.
    // Le cas ne devrait plus se produire puisqu'on vérifie l'existence en
    // amont, mais deux exécutions simultanées le rendraient encore possible.
    if ($e->getCode() === '23000') {
        exit("\nCette adresse e-mail est déjà utilisée par un compte.\n");
    }

    exit("\nErreur base de données : " . $e->getMessage() . "\n");
}
