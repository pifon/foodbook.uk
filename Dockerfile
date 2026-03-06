ARG NODE_VERSION=20
FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    certbot \
    cron \
    curl \
    nginx \
    openssl \
    supervisor \
    unzip \
    && docker-php-ext-install opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /run/nginx

WORKDIR /var/www/html

# --- PHP dependencies ---
FROM base AS deps

RUN apt-get update && apt-get install -y --no-install-recommends git \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && update-ca-certificates

COPY composer.json composer.lock* ./
ENV COMPOSER_PROCESS_TIMEOUT=600
# Relax SSL for Composer so dist downloads work behind strict proxies / corporate TLS inspection
RUN composer config -g secure-http false \
    && composer install --no-dev --no-scripts --no-interaction --prefer-dist

# --- Frontend (build assets): install Yarn via npm (with relaxed TLS), then use Yarn to avoid npm "Exit handler never called" bug.
FROM node:${NODE_VERSION}-bookworm AS frontend

WORKDIR /var/www/html

ENV NODE_OPTIONS="--max-old-space-size=4096"

RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && update-ca-certificates

# Install Yarn from tarball via curl (-k fallback for strict proxies). Overwrite Corepack shim so we use real Yarn.
RUN curl -fsSL -o /tmp/yarn.tgz "https://registry.npmjs.org/yarn/-/yarn-1.22.22.tgz" \
    || curl -fk -o /tmp/yarn.tgz "https://registry.npmjs.org/yarn/-/yarn-1.22.22.tgz" \
    && (corepack disable yarn 2>/dev/null || true) \
    && rm -f /usr/local/bin/yarn /usr/local/bin/yarnpkg 2>/dev/null || true \
    && npm install -g --force /tmp/yarn.tgz \
    && rm /tmp/yarn.tgz
COPY . .
# Relax TLS for yarn install only (strict proxies); Yarn has its own strict-ssl, Node has NODE_TLS_REJECT_UNAUTHORIZED.
ENV NODE_TLS_REJECT_UNAUTHORIZED=0
RUN yarn config set strict-ssl false \
    && yarn import 2>/dev/null || true \
    && yarn install \
    && yarn run build

# --- Final image ---
FROM base

COPY docker/nginx/default.conf.template /etc/nginx/conf.d/default.conf.template
COPY docker/certbot-renew.sh /usr/local/bin/certbot-renew.sh
COPY docker/certbot-cron /etc/cron.d/certbot-cron
COPY docker/supervisord.conf /etc/supervisord.conf

RUN chmod +x /usr/local/bin/certbot-renew.sh \
    && chmod 0644 /etc/cron.d/certbot-cron \
    && crontab /etc/cron.d/certbot-cron \
    && mkdir -p /var/www/certbot

COPY --from=deps /var/www/html/vendor ./vendor
COPY . .
COPY --from=frontend /var/www/html/public/build ./public/build
# Keep a copy for when host mount overrides public/build and has no build (entrypoint restores it)
COPY --from=frontend /var/www/html/public/build /opt/foodbook-build

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]