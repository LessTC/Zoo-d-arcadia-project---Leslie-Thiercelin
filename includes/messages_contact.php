<?php

declare(strict_types=1);

/*Boîte de réception des messages du formulaire de contact (US 10). */

require_once __DIR__ . '/db.php';

/** Les messages reçus, non lus en tête, puis du plus récent au plus ancien. */
function messages_contact(): array
{
    return db()->query(
        'SELECT id, email, phone, subject, message, is_read, created_at
         FROM contact_messages
         ORDER BY is_read ASC, created_at DESC'
    )->fetchAll();
}

/** Nombre de messages non lus, pour la pastille de l'onglet. */
function messages_non_lus(array $messages): int
{
    return count(array_filter($messages, static fn(array $m): bool => !$m['is_read']));
}

function traiter_action_message(string $page): void
{
    $action = $_POST['action'] ?? '';

    if ($action !== 'message_lu' && $action !== 'message_suppr') {
        return;
    }

    $id = (int) ($_POST['message_id'] ?? 0);

    if ($id > 0 && $action === 'message_lu') {
        $requete = db()->prepare(
            'UPDATE contact_messages SET is_read = NOT is_read WHERE id = ?'
        );
        $requete->execute([$id]);
    }

    if ($id > 0 && $action === 'message_suppr') {
        $requete = db()->prepare('DELETE FROM contact_messages WHERE id = ?');
        $requete->execute([$id]);
    }

    $suffixe = $action === 'message_suppr' ? '&message=supprime' : '';

    header('Location: ' . $page . '?onglet=messages' . $suffixe);
    exit;
}
