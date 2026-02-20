#!/bin/sh
set -e

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

/usr/local/bin/certbot-renew.sh || true

exec "$@"
