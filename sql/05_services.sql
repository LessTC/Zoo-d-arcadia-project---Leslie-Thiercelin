-- =====================================================================
--  Zoo d'Arcadia — Migration : services dynamiques
--
--  Ajoute la catégorie et l'ancre aux services, et crée la table des
--  images liées (un service peut en avoir plusieurs).
--
--  Exécution : phpMyAdmin > base arcadia > onglet Importer
-- =====================================================================

USE arcadia;

-- Catégorie : distingue les visites de la restauration à l'affichage,
-- sans dupliquer la structure dans une seconde table.
ALTER TABLE services
  ADD COLUMN category VARCHAR(30) NOT NULL DEFAULT 'visite' AFTER name,
  ADD COLUMN slug     VARCHAR(100) NULL AFTER category,
  ADD UNIQUE KEY uq_services_slug (slug);

-- Les textes du site avaient été rédigés de mémoire : on remet ceux de
-- Services.html.
UPDATE services SET
  category    = 'visite',
  slug        = 'visite-guidee',
  description = 'Profitez de l’expérience de nos guides pour découvrir les secrets de nos animaux.'
WHERE name = 'Visite guidée (gratuit)';

UPDATE services SET
  category    = 'visite',
  slug        = 'visite-petit-train',
  description = 'Le petit train permet une visite en toute tranquillité de l’ensemble du parc.'
WHERE name = 'Visite en petit train';

UPDATE services SET
  category    = 'restauration',
  slug        = 'restauration',
  description = 'Le parc propose un service de restauration rapide (sucré/salé) toute la journée, ainsi qu’un restaurant entre 11h et 14h.'
WHERE name = 'Restauration';


-- ---------------------------------------------------------------------
--  service_images : un service possède zéro, une ou plusieurs photos.
--  position sert à les ordonner sans dépendre de l'ordre d'insertion.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS service_images;

CREATE TABLE service_images (
  id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  service_id INT UNSIGNED     NOT NULL,
  path       VARCHAR(255)     NOT NULL,
  alt        VARCHAR(255)     NOT NULL,
  position   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_service_images_service (service_id),
  CONSTRAINT fk_service_images_service
    FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE = InnoDB;


INSERT INTO service_images (service_id, path, alt, position)
SELECT id, 'images/steve-payne-ygYxOk1PKcU-unsplash.jpg', 'Panda roux nourri par un guide', 1
FROM services WHERE slug = 'visite-guidee';

INSERT INTO service_images (service_id, path, alt, position)
SELECT id, 'images/dusan-veverkolog-of8koAjYI7c-unsplash.jpg', 'Petit train du zoo', 1
FROM services WHERE slug = 'visite-petit-train';

INSERT INTO service_images (service_id, path, alt, position)
SELECT id, 'images/joseph-gonzalez-zcUgjyqEwe8-unsplash.jpg', 'Pancakes aux fruits', 1
FROM services WHERE slug = 'restauration';

INSERT INTO service_images (service_id, path, alt, position)
SELECT id, 'images/eaters-collective-12eHC6FxPyg-unsplash.jpg', 'Pâtes aux légumes', 2
FROM services WHERE slug = 'restauration';