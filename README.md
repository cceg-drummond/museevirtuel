# Muse

Application web Laravel/Vue pour la gestion de cours, projets de recherche et musées virtuels.

---

## Versions des technologies

### Backend

| Technologie | Version |
|-------------|---------|
| PHP | 8.5 |
| Laravel | 13.x |
| Laravel Fortify | 1.30+ |
| Inertia.js (serveur) | 3.x |
| Laravel Wayfinder | 0.1.x |
| Laravel Sail (dev Docker) | 1.x |
| Pest | 4.4+ |
| Laravel Pint | 1.24+ |

### Frontend

| Technologie | Version |
|-------------|---------|
| Node.js | 22 |
| Vue | 3.5+ |
| Vite | 7.x |
| TypeScript | 5.2+ |
| Tailwind CSS | 4.x |
| Inertia.js (client) | 3.x |
| reka-ui | 2.6+ |
| Tiptap | 3.x |

### Outils système (vidéo et transcription)

| Outil | Rôle | Fourni par |
|-------|------|------------|
| FFmpeg | Découpe et fusion des vidéos de groupe | Images Docker (dev et prod) |
| Whisper (CLI Python, CPU) | Transcription audio | Images Docker (dev et prod) |

### Base de données

| Environnement | Moteur | Version |
|---------------|--------|---------|
| Dev Docker (Sail) | MySQL | 8.4 |
| Dev local sans Docker (Herd) | SQLite | — |
| Production (Docker) | MySQL | 8.4 |

---

## Développement — Laravel Sail (recommandé)

### Prérequis

- Docker Desktop (avec WSL 2 sous Windows)
- PHP 8.5 et Composer 2 sur la machine hôte, seulement pour le premier `composer install`

### Étapes

```bash
# 1. Cloner le dépôt
git clone <url-du-repo> muse
cd muse

# 2. Installer les dépendances PHP (fournit ./vendor/bin/sail)
composer install

# 3. Copier l'environnement — .env.example est déjà configuré pour Sail
cp .env.example .env

# 4. Construire l'image (la première fois est longue : Whisper + PyTorch) et démarrer
./vendor/bin/sail build
./vendor/bin/sail up -d

# 5. Clé d'application et base de données
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed

# 6. Assets front-end
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

L'application est accessible sur `http://localhost`.

Les jobs vidéo et de transcription passent par la file d'attente. Pour les traiter, dans un autre terminal :

```bash
./vendor/bin/sail artisan queue:work --timeout=900
```

Vérifier que FFmpeg et Whisper sont disponibles :

```bash
./vendor/bin/sail exec laravel.test ffmpeg -version
./vendor/bin/sail artisan whisper:check
```

> Le modèle Whisper (`WHISPER_MODEL`, `small` par défaut) est téléchargé à la première transcription, puis conservé dans le volume `whisper-models`.

---

## Développement local sans Docker (Laravel Herd)

### Prérequis

- [Laravel Herd](https://herd.laravel.com/) (fournit PHP 8.5 et un serveur local)
- Node.js 22+ et npm, Composer 2
- Optionnel : FFmpeg et Whisper (`pip install openai-whisper`) dans le PATH pour les fonctions vidéo

### Étapes

```bash
composer install
cp .env.example .env
```

Dans `.env`, passer à SQLite :

```dotenv
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD ne sont pas nécessaires
```

```bash
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run dev
```

L'application est accessible sur `http://muse.test` via Herd.

---

## Production — Docker

### Prérequis

- Docker et Docker Compose sur le serveur

### Étapes

```bash
# 1. Cloner le dépôt sur le serveur
git clone <url-du-repo> muse
cd muse

# 2. Créer le fichier d'environnement de production (ignoré par git)
cp .env.production.example .env.production
#    Remplir au minimum :
#    APP_KEY=      → php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
#    APP_URL=      → ex. https://muse.exemple.com
#    DB_PASSWORD=  → mot de passe fort

# 3. Construire et démarrer
docker compose -f docker-compose.prod.yml --env-file .env.production up -d --build
```

Au démarrage, le conteneur `app` exécute les migrations, crée le lien `storage:link` et met en cache la config, les routes et les vues. L'application est servie sur le port `APP_PORT` (80 par défaut).

| Service | Rôle |
|---------|------|
| `app` | nginx + php-fpm |
| `queue` | `queue:work` : jobs FFmpeg et Whisper (timeout 900 s) |
| `mysql` | MySQL 8.4 |

| Volume | Contenu |
|--------|---------|
| `mysql_data` | Base de données |
| `storage_app` | `storage/app` : vidéos des groupes (privé) et images des musées (public) |
| `whisper_models` | Modèles Whisper téléchargés |

Commandes courantes :

```bash
# Logs
docker compose -f docker-compose.prod.yml --env-file .env.production logs -f app queue

# Mise à jour après un git pull
docker compose -f docker-compose.prod.yml --env-file .env.production up -d --build

# Commande artisan ponctuelle
docker compose -f docker-compose.prod.yml --env-file .env.production exec app php artisan <commande>
```

---

## Commandes utiles

Préfixer par `./vendor/bin/sail` sous Sail.

```bash
# Lancer les tests
php artisan test --compact

# Vérifier et corriger le style PHP
./vendor/bin/pint

# Générer les routes Wayfinder (TypeScript)
php artisan wayfinder:generate --no-interaction

# Lancer le worker de queue
php artisan queue:work --timeout=900

# Voir toutes les routes
php artisan route:list
```

---

## Structure des environnements

| Fichier | Usage |
|---------|-------|
| `.env.example` | Modèle pour le développement avec Sail (MySQL) |
| `.env.production.example` | Modèle pour la production Docker |
| `compose.yaml` | Dev : Sail (app + MySQL 8.4) |
| `docker/8.5/` | Image Sail personnalisée (ajout de Whisper) |
| `Dockerfile` | Image de production multi-stage : build PHP + Node → runtime nginx + php-fpm |
| `docker-compose.prod.yml` | Production : app + queue + MySQL |
| `docker/prod/` | Config nginx, PHP, supervisord et entrypoint de production |
