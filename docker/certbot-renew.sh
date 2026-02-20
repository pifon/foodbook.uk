#!/bin/sh
set -e

certbot renew --webroot -w /var/www/certbot --quiet --no-self-upgrade

if nginx -t 2>/dev/null && [ -f /run/nginx.pid ] && kill -0 "$(cat /run/nginx.pid)" 2>/dev/null; then
    nginx -s reload
fi
