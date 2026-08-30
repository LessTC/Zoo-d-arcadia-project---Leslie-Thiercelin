-- =====================================================================
--  Zoo d'Arcadia — Migration : photos et textes alternatifs des animaux
--
--  Une « migration » est un script qui fait ÉVOLUER une base déjà en
--  service, au lieu de la reconstruire. Ici, on ajoute une colonne et on
--  complète les photos sans toucher aux comptes utilisateurs existants.
--
--  Les mêmes valeurs ont été reportées dans 01_schema.sql et
--  02_donnees.sql : une installation neuve produit donc le même résultat
--  sans avoir besoin de ce fichier.
--
--  Exécution : phpMyAdmin > base arcadia > onglet Importer
-- =====================================================================

USE arcadia;

-- Correction : la description du marais avait été rédigée de mémoire.
-- On remet le texte exact de marais.html.
UPDATE habitats SET
  description = 'Les marais sont des milieux essentiels pour la biodiversité. Leur niveau d’eau est suivi chaque jour par nos équipes afin d’assurer le bien-être des animaux qui y vivent.'
WHERE name = 'Marais';

-- Texte alternatif de l'image, lu à voix haute par les lecteurs d'écran
-- et affiché si la photo ne se charge pas. Le stocker en base plutôt que
-- de le générer automatiquement permet de décrire vraiment chaque photo.
ALTER TABLE animals
  ADD COLUMN image_alt VARCHAR(255) NULL AFTER image;


-- --------------------------- Savane ---------------------------------
UPDATE animals SET
  image     = 'images/jaliya-rasaputra-U_eZSoRUMQM-unsplash.jpg',
  image_alt = 'Lionne dans la savane'
WHERE name = 'Lionne';

UPDATE animals SET
  image     = 'images/anthony-melone-A-p2uJsN4i4-unsplash.jpg',
  image_alt = 'Tête de girafe'
WHERE name = 'Girafe';

UPDATE animals SET
  image     = 'images/colin-watts-7Ofg_Y-IJcA-unsplash.jpg',
  image_alt = 'Rhinocéros de profil'
WHERE name = 'Rhinocéros';

UPDATE animals SET
  image     = 'images/joel-herzog-ny_5l4QKBnE-unsplash.jpg',
  image_alt = 'Gazelle courant dans l’herbe'
WHERE name = 'Gazelle';


-- --------------------------- Jungle ---------------------------------
UPDATE animals SET
  image     = 'images/vinicius-gomes-SqFu-DwQPM4-unsplash.jpg',
  image_alt = 'Tigre buvant de l’eau'
WHERE name = 'Tigre';

UPDATE animals SET
  image     = 'images/janosch-diggelmann-N2oEwyXxvos-unsplash.jpg',
  image_alt = 'Koala sur un arbre'
WHERE name = 'Koala';

UPDATE animals SET
  image     = 'images/dylan-mullins-Qmjq21UYtaE-unsplash.jpg',
  image_alt = 'Famille de singes'
WHERE name = 'Singes';

UPDATE animals SET
  image     = 'images/joshua-j-cotten-dJTmBXaNdxY-unsplash.jpg',
  image_alt = 'Bébé singe contre sa mère'
WHERE name = 'Bébé singe';


-- --------------------------- Marais ---------------------------------
UPDATE animals SET
  image     = 'images/joseph-corl-hQcs4wG7os8-unsplash.jpg',
  image_alt = 'Gallinule d’eau colorée dans un marais'
WHERE name = 'Poule d’eau';

UPDATE animals SET
  image     = 'images/angel-luciano-0xC09hp4L04-unsplash.jpg',
  image_alt = 'Hippopotame dans l’eau'
WHERE name = 'Hippopotame';

UPDATE animals SET
  image     = 'images/gary-yost-b2iauwRsxOM-unsplash.jpg',
  image_alt = 'Crocodile au repos dans l’eau'
WHERE name = 'Crocodile';

UPDATE animals SET
  image     = 'images/joseph-corl-O_UXAEpsPvM-unsplash.jpg',
  image_alt = 'Hérons exotiques dans les roseaux'
WHERE name = 'Hérons exotiques';
