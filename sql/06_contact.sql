-- =====================================================================
--  Zoo d'Arcadia — Migration : messages du formulaire de contact
--
--  Exécution : phpMyAdmin > base arcadia > onglet Importer
-- =====================================================================

USE arcadia;

DROP TABLE IF EXISTS contact_messages;

CREATE TABLE contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email      VARCHAR(180) NOT NULL,
  phone      VARCHAR(30)  NULL,
  subject    VARCHAR(150) NOT NULL,
  message    TEXT         NOT NULL,
  -- Permet à l'administrateur de distinguer les messages déjà traités,
  -- et d'afficher un compteur de non-lus. Même principe que la
  -- modération des avis.
  is_read    BOOLEAN      NOT NULL DEFAULT FALSE,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_contact_read (is_read)
) ENGINE = InnoDB;