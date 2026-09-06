# Zoo d'Arcadia

Application web de gestion d'un zoo : consultation des habitats et des animaux
par les visiteurs, espaces réservés pour l'administrateur, les employés et les
vétérinaires.

**Stack** — PHP 8.2 · MySQL / MariaDB · MongoDB · Bootstrap 5

## Fonctionnalités

- Consultation publique des habitats, des animaux et des services
- Dépôt d'avis par les visiteurs, soumis à validation
- Trois espaces protégés : administrateur, employé, vétérinaire
- Comptes rendus vétérinaires et suivi de l'alimentation
- Compteur de consultations par animal, stocké en base NoSQL

## Prérequis

- **XAMPP** avec PHP 8.2 ou supérieur (Apache et MySQL / MariaDB)
- **MongoDB Community Server** 6.0 ou supérieur
- **L'extension PHP `mongodb`** : ajouter la ligne `extension=mongodb` dans
  `C:\xampp\php\php.ini`, puis **redémarrer Apache** depuis le panneau XAMPP
- **Git**

## Installation locale

### 1. Récupérer le projet

Placer dans le dossier `htdocs` de l'installation XAMPP :

```bash
cd C:\xampp\htdocs
git clone https://github.com/LessTC/Zoo-d-arcadia-project---Leslie-Thiercelin.git arcadia
```

### 2. Configurer la connexion aux bases

Copier `config/config.exemple.php` sous le nom `config/config.php`, dans le
même dossier, puis adapter les valeurs si l'installation diffère de la
configuration XAMPP par défaut.

Ce fichier n'est pas versionné : il contient les identifiants propres à
chaque machine.

### 3. Créer la base de données

Dans phpMyAdmin, créer d'abord une base nommée `arcadia`, avec
l'interclassement **utf8mb4_unicode_ci**.

Puis, cette base étant sélectionnée, aller dans l'onglet **Importer** et
lancer les deux scripts dans cet ordre :

1. `sql/01_schema.sql` — crée les 11 tables
2. `sql/02_donnees.sql` — insère les habitats, animaux, services et avis

Les scripts ne créent pas la base et ne la nomment nulle part : ils
agissent sur celle qui est sélectionnée. C'est ce qui permet de les
rejouer tels quels chez un hébergeur, où le nom de la base est souvent
imposé.

En ligne de commande, l'équivalent est :

```bash
mysql -u root arcadia < sql/01_schema.sql
mysql -u root arcadia < sql/02_donnees.sql
```

### 4. Créer les comptes du back-office

Par sécurité, l'application ne permet pas de créer un compte administrateur.
Le premier se crée en ligne de commande, depuis la racine du projet :

```bash
php sql/03_create_user.php
```

Le script demande le rôle, l'adresse, le nom et le mot de passe. Choisir le
rôle **1** pour un administrateur. Répéter l'opération pour créer un compte
employé (rôle **2**) et un compte vétérinaire (rôle **3**).

Le mot de passe n'est jamais stocké en clair : seule son empreinte, produite
par `password_hash()`, est enregistrée.

### 5. Démarrer

Lancer **Apache** et **MySQL** depuis le panneau XAMPP. MongoDB s'exécute en
service Windows et démarre automatiquement.

Le site est accessible sur [http://localhost/arcadia/](http://localhost/arcadia/)

## Bon à savoir

**Les courriels ne sont pas envoyés en local.** XAMPP ne dispose pas de
serveur d'envoi. L'application écrit chaque message dans un fichier daté du
dossier `courriels/`, créé automatiquement au premier envoi. Le code
correspondant est isolé dans `includes/courriel.php` : le passage à un envoi
réel ne concernera que ce fichier.

**Sans MongoDB, le site reste fonctionnel.** Seul le compteur de consultations
est inactif : l'erreur est interceptée et consignée dans le journal de PHP,
les fiches animaux restent consultables.

## Structure du projet

| Dossier     | Contenu                                                          |
| ----------- | ---------------------------------------------------------------- |
| `config/`   | Paramètres de connexion aux bases                                |
| `includes/` | Connexion PDO, authentification, en-tête et pied de page communs |
| `sql/`      | Scripts de création de la base et d'intégration des données      |
| `images/`   | Photographies du site                                            |

## Licence

CC0 1.0 Universal
