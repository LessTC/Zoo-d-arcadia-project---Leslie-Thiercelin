# Zoo d'Arcadia

Application web de gestion d'un zoo : consultation des habitats et des animaux
par les visiteurs, espaces réservés pour l'administrateur, les employés et les
vétérinaires.

**Site en ligne** — <https://lesstc.alwaysdata.net/>

**Stack** — PHP 8.2+ (PDO) · MariaDB · MongoDB · Bootstrap 5 · JavaScript sans
bibliothèque

Le développement se fait en local sous XAMPP (PHP 8.2, MariaDB 10.4) ; la
production tourne chez alwaysdata (PHP 8.4, MariaDB 11.4) avec la base NoSQL
sur un cluster MongoDB Atlas. Les instructions ci-dessous concernent
l'installation locale ; la démarche de déploiement est décrite dans la
documentation technique.

## Fonctionnalités

- Consultation publique des habitats, des animaux et des services
- Fiche animal ouverte en fenêtre, sans rechargement de page (requête `fetch`)
- Dépôt d'avis par les visiteurs, soumis à validation
- Trois espaces protégés : administrateur, employé, vétérinaire
- Comptes rendus vétérinaires et suivi de l'alimentation
- Compteur de consultations par animal, stocké en base NoSQL

## Prérequis

- **XAMPP** avec PHP 8.2 ou supérieur (Apache et MySQL / MariaDB)
- **MongoDB Community Server** 6.0 ou supérieur
- **L'extension PHP `mongodb`** : déposer le fichier `php_mongodb.dll`
  correspondant **exactement à la version de PHP installée** dans
  `C:\xampp\php\ext\`, ajouter la ligne `extension=php_mongodb.dll` dans
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
Les comptes se gèrent en ligne de commande, depuis la racine du projet :

```bash
php sql/03_create_user.php
```

Le script demande d'abord l'adresse e-mail, puis s'adapte :

- **adresse inconnue** — il demande le rôle, le prénom, le nom et le mot de
  passe, et crée le compte. Choisir le rôle **1** pour un administrateur,
  **2** pour un employé, **3** pour un vétérinaire.
- **adresse existante** — il affiche le compte concerné et propose de
  **redéfinir son mot de passe**.

Ce second usage répond à l'oubli d'un mot de passe. L'énoncé prévoit que
l'utilisateur se rapproche de l'administrateur pour l'obtenir, et interdit
qu'il circule par courriel : une réinitialisation en libre-service par mail
était donc exclue par principe.

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

**Sans JavaScript, le site reste fonctionnel.** Depuis la page d'un habitat,
le nom d'un animal ouvre normalement sa fiche dans une fenêtre, sans
rechargement. Si le JavaScript est désactivé ou si la requête échoue, le lien
mène à la page complète `animal.php`, qui sert la même information.

## Structure du projet

| Dossier     | Contenu                                                                   |
| ----------- | ------------------------------------------------------------------------- |
| `api/`      | Points d'entrée JSON appelés par le JavaScript                            |
| `config/`   | Paramètres de connexion aux bases                                         |
| `docs/`     | Livrables du projet en PDF : manuel, charte, documentations               |
| `images/`   | Photographies du site                                                     |
| `includes/` | Connexion PDO, authentification, accès aux données, en-tête et pied de page |
| `sql/`      | Scripts de création de la base et d'intégration des données               |

## Licence

CC0 1.0 Universal
