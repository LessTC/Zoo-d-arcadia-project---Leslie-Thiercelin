-- =====================================================================
--  Zoo d'Arcadia — Structure de la base relationnelle
--  SGBD : MariaDB / MySQL (XAMPP)
--
--  Convention : identifiants de base en anglais (usage courant en
--  développement), libellés affichés en français côté interface.
--
--  ATTENTION : ce script est ré-exécutable, mais il SUPPRIME les tables
--  existantes avant de les recréer. Toute donnée saisie est perdue.
--
--  Exécution :  mysql -u root < sql/01_schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS arcadia
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE arcadia;

-- Suppression dans l'ordre inverse des dépendances : une table
-- référencée par une clé étrangère ne peut pas être supprimée en premier.
DROP TABLE IF EXISTS feedings;
DROP TABLE IF EXISTS habitat_comments;
DROP TABLE IF EXISTS veterinary_reports;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS animals;
DROP TABLE IF EXISTS habitats;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- Vestiges de l'ébauche francophone, retirés définitivement.
DROP TABLE IF EXISTS alimentation;
DROP TABLE IF EXISTS commentaire_habitat;
DROP TABLE IF EXISTS rapport_veterinaire;
DROP TABLE IF EXISTS avis;
DROP TABLE IF EXISTS animal;
DROP TABLE IF EXISTS habitat;
DROP TABLE IF EXISTS service;
DROP TABLE IF EXISTS utilisateur;
DROP TABLE IF EXISTS role;


-- ---------------------------------------------------------------------
--  roles : les 3 profils du back-office
--  Table dédiée plutôt qu'une colonne ENUM : ajouter un profil ne
--  demande alors aucune modification de structure.
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  label VARCHAR(50)      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_label (label)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  users : comptes du back-office, créés par l'administrateur (US6/US9)
--
--  La colonne s'appelle password_hash et non password : elle ne contient
--  QUE l'empreinte produite par la fonction PHP password_hash().
--  Jamais le mot de passe en clair, jamais un chiffrement réversible.
--  255 caractères : bcrypt en occupe 60, mais l'algorithme par défaut
--  de PHP peut évoluer vers des empreintes plus longues.
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  email         VARCHAR(180)     NOT NULL,
  password_hash VARCHAR(255)     NOT NULL,
  last_name     VARCHAR(100)     NOT NULL,
  first_name    VARCHAR(100)     NOT NULL,
  role_id       TINYINT UNSIGNED NOT NULL,
  is_active     BOOLEAN          NOT NULL DEFAULT TRUE,
  created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  habitats : savane, jungle, marais
-- ---------------------------------------------------------------------
CREATE TABLE habitats (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  description TEXT         NULL,
  image       VARCHAR(255) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_habitats_name (name)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  animals : un animal appartient à un et un seul habitat.
--
--  diet est une caractéristique stable de l'animal (carnivore, feuilles…),
--  elle vit donc ici. health_state est en revanche l'état COURANT : il
--  reflète le dernier rapport vétérinaire et sera mis à jour à chaque
--  nouveau compte rendu. C'est un choix assumé de valeur recalculée et
--  stockée, pour éviter une sous-requête sur chaque fiche publique.
--
--  ON DELETE CASCADE : supprimer un habitat supprime ses animaux.
-- ---------------------------------------------------------------------
CREATE TABLE animals (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL,
  species      VARCHAR(100) NOT NULL,
  description  TEXT         NULL,
  diet         VARCHAR(150) NULL,
  health_state VARCHAR(100) NULL,
  image        VARCHAR(255) NULL,
  -- Texte alternatif de la photo : lu par les lecteurs d'écran et affiché
  -- si l'image ne se charge pas. Stocké en base pour décrire réellement
  -- chaque cliché plutôt que de générer un libellé automatique.
  image_alt    VARCHAR(255) NULL,
  habitat_id   INT UNSIGNED NOT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_animals_habitat (habitat_id),
  CONSTRAINT fk_animals_habitat
    FOREIGN KEY (habitat_id) REFERENCES habitats (id) ON DELETE CASCADE
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  veterinary_reports : compte rendu de passage du vétérinaire.
--  Les colonnes suivent l'énoncé : état, nourriture proposée, grammage,
--  date de passage, détail de l'état.
--
--  veterinarian_id n'a PAS de ON DELETE CASCADE : on ne veut pas perdre
--  l'historique médical si un compte est supprimé.
-- ---------------------------------------------------------------------
CREATE TABLE veterinary_reports (
  id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  animal_id      INT UNSIGNED      NOT NULL,
  veterinarian_id INT UNSIGNED     NOT NULL,
  visit_date     DATE              NOT NULL,
  animal_state   VARCHAR(255)      NOT NULL,
  proposed_food  VARCHAR(150)      NULL,
  food_grams     SMALLINT UNSIGNED NULL COMMENT 'grammage en grammes',
  state_details  TEXT              NULL,
  created_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reports_animal (animal_id),
  KEY idx_reports_date (visit_date),
  CONSTRAINT fk_reports_animal
    FOREIGN KEY (animal_id) REFERENCES animals (id) ON DELETE CASCADE,
  CONSTRAINT fk_reports_veterinarian
    FOREIGN KEY (veterinarian_id) REFERENCES users (id)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  habitat_comments : avis du vétérinaire sur l'état d'un habitat
-- ---------------------------------------------------------------------
CREATE TABLE habitat_comments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  habitat_id      INT UNSIGNED NOT NULL,
  veterinarian_id INT UNSIGNED NOT NULL,
  comment         TEXT         NOT NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_habitat_comments_habitat (habitat_id),
  CONSTRAINT fk_habitat_comments_habitat
    FOREIGN KEY (habitat_id) REFERENCES habitats (id) ON DELETE CASCADE,
  CONSTRAINT fk_habitat_comments_veterinarian
    FOREIGN KEY (veterinarian_id) REFERENCES users (id)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  feedings : saisie quotidienne de l'employé.
--
--  quantity est un DECIMAL et non du texte : cela permet des sommes et
--  des moyennes pour les statistiques du dashboard admin. Stocker
--  « 5 kg » dans une seule colonne texte l'interdirait.
-- ---------------------------------------------------------------------
CREATE TABLE feedings (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  animal_id   INT UNSIGNED  NOT NULL,
  employee_id INT UNSIGNED  NOT NULL,
  feed_date   DATE          NOT NULL,
  feed_time   TIME          NOT NULL,
  food        VARCHAR(150)  NOT NULL,
  quantity    DECIMAL(6, 2) NOT NULL,
  unit        VARCHAR(10)   NOT NULL DEFAULT 'kg',
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_feedings_animal (animal_id),
  KEY idx_feedings_date (feed_date),
  CONSTRAINT fk_feedings_animal
    FOREIGN KEY (animal_id) REFERENCES animals (id) ON DELETE CASCADE,
  CONSTRAINT fk_feedings_employee
    FOREIGN KEY (employee_id) REFERENCES users (id)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  reviews : avis déposé par un visiteur, invisible tant qu'un employé
--  ne l'a pas validé. approved_by reste NULL tant que personne n'a
--  tranché, et y revient si le compte du valideur est supprimé.
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nickname    VARCHAR(100) NOT NULL,
  title       VARCHAR(150) NOT NULL,
  comment     TEXT         NOT NULL,
  is_approved BOOLEAN      NOT NULL DEFAULT FALSE,
  approved_by INT UNSIGNED NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reviews_approved (is_approved),
  CONSTRAINT fk_reviews_approver
    FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  services : prestations proposées aux visiteurs
-- ---------------------------------------------------------------------
CREATE TABLE services (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(150) NOT NULL,
  schedule    VARCHAR(100) NULL,
  description TEXT         NULL,
  image       VARCHAR(255) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE = InnoDB;
