# =============================================================================
# Image de PRODUCTION de Muse — le développement passe par Laravel Sail (compose.yaml).
# Usage : docker compose -f docker-compose.prod.yml --env-file .env.production up -d --build
# =============================================================================

# -----------------------------------------------------------------------------
# base — PHP-FPM + extensions, partagé par le build et le runtime
# pour que les deux tournent exactement sur la même version de PHP.
# Debian plutôt qu'Alpine : les wheels PyTorch (Whisper) exigent glibc.
# -----------------------------------------------------------------------------
FROM php:8.5-fpm-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev libicu-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl bcmath opcache pcntl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# -----------------------------------------------------------------------------
# build — dépendances Composer + assets Vite.
# PHP et Node dans la même étape : le plugin Vite de Wayfinder exécute
# `php artisan wayfinder:generate` pendant `npm run build`.
# -----------------------------------------------------------------------------
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# Couches séparées : les dépendances ne sont réinstallées que si les manifestes changent.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Environnement factice : artisan doit démarrer pour Wayfinder, sans base de données réelle.
ENV APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory:

RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && rm -rf node_modules

# -----------------------------------------------------------------------------
# runtime — image finale : nginx + php-fpm (supervisord), FFmpeg, Whisper.
# -----------------------------------------------------------------------------
FROM base AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor ffmpeg python3-venv curl \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

# Whisper CLI pour les jobs TranscrireVideo / TranscrireGroupeMedia.
# torch CPU : évite ~2 Go de bibliothèques CUDA inutiles sur un serveur sans GPU.
RUN python3 -m venv /opt/whisper \
    && /opt/whisper/bin/pip install --no-cache-dir --index-url https://download.pytorch.org/whl/cpu torch \
    && /opt/whisper/bin/pip install --no-cache-dir openai-whisper

# PATH : config('services.whisper.binary', 'whisper') le trouve sans configuration.
# XDG_CACHE_HOME : Whisper y télécharge ses modèles ; ce dossier est monté en volume.
ENV PATH="/opt/whisper/bin:$PATH" \
    XDG_CACHE_HOME=/opt/cache
RUN mkdir -p /opt/cache/whisper && chown -R www-data:www-data /opt/cache

COPY docker/prod/php.ini /usr/local/etc/php/conf.d/zz-muse.ini
COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/prod/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["entrypoint"]
