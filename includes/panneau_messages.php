<?php

/**
 * Panneau « Messages reçus », partagé par l'espace employé et l'espace
 * administrateur. Il ne contient que de l'affichage : les données et le
 * traitement sont dans includes/messages_contact.php.
 *
 * La page qui l'inclut doit définir avant l'inclusion :
 *   $messagesContact — la liste renvoyée par messages_contact()
 *   $nonLus          — le décompte renvoyé par messages_non_lus()
 *   $pageCourante    — le nom du fichier appelant, pour l'action des
 *                      formulaires : « Espace_soigneur.php » par exemple
 */
?>
<div class="card p-4">
  <h2 class="h5 mb-3">
    Messages reçus
    <?php if ($nonLus > 0): ?>
      <span class="badge bg-danger ms-2"><?= (int) $nonLus ?> non lu<?= $nonLus > 1 ? 's' : '' ?></span>
    <?php endif; ?>
  </h2>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-secondary" role="alert">Message supprimé.</div>
  <?php endif; ?>

  <?php if (!$messagesContact): ?>
    <p class="mb-0 text-muted">Aucun message pour le moment.</p>
  <?php endif; ?>

  <?php foreach ($messagesContact as $index => $unMessage): ?>
    <?php if ($index > 0): ?><hr><?php endif; ?>

    <div class="<?= $unMessage['is_read'] ? 'opacity-75' : '' ?>">
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
          <strong><?= e($unMessage['subject']) ?></strong>
          <?php if (!$unMessage['is_read']): ?>
            <span class="badge bg-danger ms-1">nouveau</span>
          <?php endif; ?>
          <div class="small text-muted">
            <a href="mailto:<?= e($unMessage['email']) ?>"><?= e($unMessage['email']) ?></a>
            <?php if ($unMessage['phone']): ?> · <?= e($unMessage['phone']) ?><?php endif; ?>
            · <?= e(date('d/m/Y H:i', strtotime($unMessage['created_at']))) ?>
          </div>
        </div>

        <div class="text-nowrap">
          <form action="<?= e($pageCourante) ?>" method="post" class="d-inline">
            <input type="hidden" name="message_id" value="<?= (int) $unMessage['id'] ?>">
            <button class="btn btn-outline-secondary btn-sm" type="submit"
                    name="action" value="message_lu"
                    title="<?= $unMessage['is_read'] ? 'Marquer comme non lu' : 'Marquer comme lu' ?>">
              <i class="bi bi-<?= $unMessage['is_read'] ? 'envelope' : 'envelope-open' ?>"></i>
            </button>
          </form>
          <form action="<?= e($pageCourante) ?>" method="post" class="d-inline"
                onsubmit="return confirm('Supprimer ce message ?');">
            <input type="hidden" name="message_id" value="<?= (int) $unMessage['id'] ?>">
            <button class="btn btn-outline-danger btn-sm" type="submit"
                    name="action" value="message_suppr" title="Supprimer">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        </div>
      </div>

      <p class="small mb-0 mt-2"><?= nl2br(e($unMessage['message'])) ?></p>
    </div>
  <?php endforeach; ?>
</div>
