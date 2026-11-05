#!/bin/sh
set -e

# Avec une commande (ex. le service queue) : on l'exécute telle quelle.
# Les migrations et les caches restent la responsabilité du seul service app,
# pour éviter que deux conteneurs migrent en même temps.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

cd /var/www/html

# Le volume storage_app peut arriver vide ou appartenir à root au premier lancement.
mkdir -p storage/app/public storage/app/private \
    storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/logs bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

php artisan migrate --force
php artisan optimize

# Après artisan (lancé en root) : sinon les caches et laravel.log créés ici
# appartiendraient à root et php-fpm (www-data) ne pourrait plus y écrire.
chown -R www-data:www-data storage bootstrap/cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
