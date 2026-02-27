#!/bin/sh
set -e

chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# When host mount overrides public/build and has no manifest, restore from image or create minimal one
if [ ! -f /var/www/html/public/build/manifest.json ]; then
    mkdir -p /var/www/html/public/build/assets
    if [ -d /opt/foodbook-build ] && [ -f /opt/foodbook-build/manifest.json ]; then
        cp -a /opt/foodbook-build/. /var/www/html/public/build/
    else
        # Minimal manifest so Vite/layouts do not throw (page may load without assets)
        echo '{"resources/css/app.css":{"file":"assets/app.css","isEntry":true},"resources/js/app.ts":{"file":"assets/app.js","isEntry":true}}' > /var/www/html/public/build/manifest.json
        touch /var/www/html/public/build/assets/app.css /var/www/html/public/build/assets/app.js
    fi
fi

# Clear all Laravel caches so current code is used (avoids 404/500 from stale cache/views)
php /var/www/html/artisan optimize:clear 2>/dev/null || true

# Derive domain from APP_URL (from .env or env). Used for nginx server_name and cert paths.
if [ -n "$APP_URL" ]; then
    _url="$APP_URL"
else
    _url=$(grep -E '^APP_URL=' /var/www/html/.env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'" || true)
fi
APP_DOMAIN=$(echo "$_url" | sed -e 's|https\?://||' -e 's|/.*||' -e 's|:.*||')
[ -z "$APP_DOMAIN" ] && APP_DOMAIN=foodbook

# Generate self-signed certificate if it doesn't exist
CERT_DIR="/etc/letsencrypt/live/$APP_DOMAIN"
if [ ! -f "$CERT_DIR/fullchain.pem" ] || [ ! -f "$CERT_DIR/privkey.pem" ]; then
    mkdir -p "$CERT_DIR"
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$CERT_DIR/privkey.pem" -out "$CERT_DIR/fullchain.pem" \
        -subj "/CN=$APP_DOMAIN" -addext "subjectAltName=DNS:$APP_DOMAIN,DNS:localhost,DNS:127.0.0.1" 2>/dev/null || true
fi

# Generate nginx config from template. Prefer mounted project template so edits apply without rebuild.
_tmpl=/var/www/html/docker/nginx/default.conf.template
if [ ! -f "$_tmpl" ]; then
    _tmpl=/etc/nginx/conf.d/default.conf.template
fi
sed "s/__APP_DOMAIN__/$APP_DOMAIN/g" "$_tmpl" > /etc/nginx/conf.d/default.conf

/usr/local/bin/certbot-renew.sh || true

exec "$@"
