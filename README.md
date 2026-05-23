# Assoflow

Assoflow est une application Laravel conçue pour gérer les projets, les clients et les temps d'activité. Elle utilise Filament pour l'interface d'administration et Vue.js pour le frontend.

## Fonctionnalités

- Gestion des utilisateurs et des organisations
- Gestion des clients et des projets
- Suivi des temps d'activité
- Partage de projets et de clients
- Interface d'administration avec Filament

## Prérequis

- PHP 8.3+
- Composer
- Node.js
- Base de données SQLite (ou autre base de données compatible avec Laravel)

## Installation

1. Cloner le dépôt :

   ```bash
   git clone https://github.com/votre-depot/assoflow.git
   cd assoflow
   ```

2. Installer les dépendances PHP :

   ```bash
   composer install
   ```

3. Copier le fichier d'environnement :

   ```bash
   cp .env.example .env
   ```

4. Générer la clé d'application :

   ```bash
   php artisan key:generate
   ```

5. Installer les dépendances Node.js :

   ```bash
   npm install
   ```

6. Compiler les assets :

   ```bash
   npm run build
   ```

7. Lancer la base de données :

   ```bash
   docker run -p 3306:3306 -e MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1 -d mariadb
   ```

8. Exécuter les migrations :
   ```bash
   php artisan migrate
   ```

## Structure du projet

- `app/Models/` : Modèles Eloquent
- `app/Enums/` : Énumérations
- `app/Http/Controllers/` : Contrôleurs
- `app/Http/Requests/` : Requêtes HTTP
- `app/Providers/` : Fournisseurs de services
- `database/migrations/` : Migrations de base de données
- `resources/js/` : Composants Vue.js
- `routes/` : Définitions des routes

## Modèles principaux

- **Account** : Compte utilisateur ou organisation
- **User** : Utilisateur
- **Organization** : Organisation
- **Client** : Client
- **Project** : Projet
- **ActivityTime** : Temps d'activité
- **View** : Vue
- **Share** : Partage
- **SharedProject** : Projet partagé
- **SharedClient** : Client partagé

## Routes

Les routes sont définies dans `routes/web.php`.

## Développement

Pour démarrer l'application en mode développement :

```bash
composer dev
```

## Tests

Pour exécuter les tests :

```bash
php artisan test
```
