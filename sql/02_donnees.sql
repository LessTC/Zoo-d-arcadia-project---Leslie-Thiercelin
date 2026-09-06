-- =====================================================================
--  Zoo d'Arcadia — Jeu de données initial
--
--  Reprend exactement le contenu affiché aujourd'hui en dur dans les
--  pages HTML, pour que la bascule vers PHP ne change rien à l'écran.
--
--  Aucun compte utilisateur ici : un mot de passe, même haché, n'a rien
--  à faire dans un fichier versionné sur Git. Les comptes sont créés
--  par le script sql/03_create_user.php.
--
--  Comme 01_schema.sql, ce script ne choisit pas la base : il agit sur
--  celle qu'on lui désigne à l'exécution. Il doit être lancé APRÈS
--  01_schema.sql, qui crée les tables.
--
--  Exécution :  mysql -u root arcadia < sql/02_donnees.sql
-- =====================================================================


-- ---------------------------------------------------------------------
--  Rôles
-- ---------------------------------------------------------------------
INSERT INTO roles (id, label) VALUES
  (1, 'Administrateur'),
  (2, 'Employe'),
  (3, 'Veterinaire');


-- ---------------------------------------------------------------------
--  Habitats
-- ---------------------------------------------------------------------
INSERT INTO habitats (id, name, description, image) VALUES
  (1, 'Savane',
   'Les habitants de la savane vous attendent dans leur parc de 5 hectares. La savane est un environnement où l’atmosphère est sèche. Les soigneurs vérifient l’état de la végétation tous les jours.',
   'images/jaliya-rasaputra-U_eZSoRUMQM-unsplash.jpg'),
  (2, 'Jungle',
   'Retrouvez les animaux majestueux de la jungle dans un environnement humide et luxuriant.',
   'images/joshua-j-cotten-dJTmBXaNdxY-unsplash.jpg'),
  (3, 'Marais',
   'Les marais sont des milieux essentiels pour la biodiversité. Leur niveau d’eau est suivi chaque jour par nos équipes afin d’assurer le bien-être des animaux qui y vivent.',
   'images/gary-yost-b2iauwRsxOM-unsplash.jpg');


-- ---------------------------------------------------------------------
--  Animaux — repris à l'identique de savane.html, jungle.html, marais.html
-- ---------------------------------------------------------------------
INSERT INTO animals (name, species, description, diet, health_state, habitat_id, image, image_alt) VALUES
  -- Savane
  ('Lionne', 'Lion',
   'Félin social vivant en groupe. Prédateur emblématique de la savane.',
   'carnivore', 'en bonne santé', 1,
   'images/jaliya-rasaputra-U_eZSoRUMQM-unsplash.jpg', 'Lionne dans la savane'),
  ('Girafe', 'Girafe',
   'Grand herbivore aux longues pattes et au long cou, très curieux.',
   'feuilles (acacias)', 'en bonne santé', 1,
   'images/anthony-melone-A-p2uJsN4i4-unsplash.jpg', 'Tête de girafe'),
  ('Rhinocéros', 'Rhinocéros',
   'Herbivore massif et calme, protégé par une peau très épaisse.',
   'herbes et feuilles', 'suivi vétérinaire régulier', 1,
   'images/colin-watts-7Ofg_Y-IJcA-unsplash.jpg', 'Rhinocéros de profil'),
  ('Gazelle', 'Gazelle',
   'Herbivore rapide et agile, vivant en troupeaux.',
   'herbes, jeunes pousses', 'en bonne santé', 1,
   'images/joel-herzog-ny_5l4QKBnE-unsplash.jpg', 'Gazelle courant dans l’herbe'),

  -- Jungle
  ('Tigre', 'Tigre',
   'Grand félin solitaire, agile et puissant.',
   'carnivore', 'en bonne santé', 2,
   'images/vinicius-gomes-SqFu-DwQPM4-unsplash.jpg', 'Tigre buvant de l’eau'),
  ('Koala', 'Koala',
   'Herbivore calme, adepte des feuilles d’eucalyptus.',
   'feuilles', 'suivi régulier', 2,
   'images/janosch-diggelmann-N2oEwyXxvos-unsplash.jpg', 'Koala sur un arbre'),
  ('Singes', 'Macaque',
   'Espèces sociales et joueuses, très actives.',
   'fruits et insectes', 'en bonne santé', 2,
   'images/dylan-mullins-Qmjq21UYtaE-unsplash.jpg', 'Famille de singes'),
  ('Bébé singe', 'Langur',
   'Jeune primate protégé par le groupe.',
   'lait / fruits', 'suivi attentif', 2,
   'images/joshua-j-cotten-dJTmBXaNdxY-unsplash.jpg', 'Bébé singe contre sa mère'),

  -- Marais
  ('Poule d’eau', 'Gallinule',
   'Oiseau aquatique vif et curieux, fréquente les berges et les roselières.',
   'insectes, graines', 'en bonne santé', 3,
   'images/joseph-corl-hQcs4wG7os8-unsplash.jpg', 'Gallinule d’eau colorée dans un marais'),
  ('Hippopotame', 'Hippopotame',
   'Grand herbivore semi-aquatique, passe de longues heures immergé.',
   'herbes', 'suivi quotidien', 3,
   'images/angel-luciano-0xC09hp4L04-unsplash.jpg', 'Hippopotame dans l’eau'),
  ('Crocodile', 'Crocodile',
   'Reptile patient et discret, parfaitement adapté à l’élément aquatique.',
   'carnivore', 'en bonne santé', 3,
   'images/gary-yost-b2iauwRsxOM-unsplash.jpg', 'Crocodile au repos dans l’eau'),
  ('Hérons exotiques', 'Héron',
   'Oiseaux élégants au long cou, observateurs des zones peu profondes.',
   'poissons, amphibiens', 'en bonne santé', 3,
   'images/joseph-corl-O_UXAEpsPvM-unsplash.jpg', 'Hérons exotiques dans les roseaux');


-- ---------------------------------------------------------------------
--  Services — repris de Services.html
-- ---------------------------------------------------------------------
INSERT INTO services (id, name, category, slug, schedule, description) VALUES
  (1, 'Visite guidée (gratuit)', 'visite', 'visite-guidee', '10h – 17h',
   'Profitez de l’expérience de nos guides pour découvrir les secrets de nos animaux.'),
  (2, 'Visite en petit train', 'visite', 'visite-petit-train', '9h – 19h',
   'Le petit train permet une visite en toute tranquillité de l’ensemble du parc.'),
  (3, 'Restauration', 'restauration', 'restauration', '11h30 – 15h',
   'Le parc propose un service de restauration rapide (sucré/salé) toute la journée, ainsi qu’un restaurant entre 11h et 14h.');


-- ---------------------------------------------------------------------
--  Avis — repris d'index.html.
--  Les deux premiers sont validés (donc visibles publiquement), le
--  troisième est en attente : il sert à tester l'écran de modération
--  de l'espace employé.
--  approved_by reste NULL : aucun compte n'existe encore à ce stade.
-- ---------------------------------------------------------------------
INSERT INTO reviews (nickname, title, comment, is_approved, created_at) VALUES
  ('A.', 'Fantastique',
   'Le zoo est fantastique et l’équipe à l’écoute !', TRUE, '2024-06-15 10:00:00'),
  ('M.', 'Très belle découverte',
   'Très belle découverte !', TRUE, '2024-05-02 14:30:00'),
  ('L.', 'Un peu d’attente',
   'Un peu d’attente à l’entrée, mais la visite vaut le détour.', FALSE, '2025-05-10 09:15:00');



-- ---------------------------------------------------------------------
--  Images des services. La restauration en a deux, les visites une seule.
-- ---------------------------------------------------------------------
INSERT INTO service_images (service_id, path, alt, position) VALUES
  (1, 'images/steve-payne-ygYxOk1PKcU-unsplash.jpg',      'Panda roux nourri par un guide', 1),
  (2, 'images/dusan-veverkolog-of8koAjYI7c-unsplash.jpg', 'Petit train du zoo',             1),
  (3, 'images/joseph-gonzalez-zcUgjyqEwe8-unsplash.jpg',  'Pancakes aux fruits',            1),
  (3, 'images/eaters-collective-12eHC6FxPyg-unsplash.jpg','Pâtes aux légumes',              2);