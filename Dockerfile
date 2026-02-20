FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    certbot \
    cron \
    curl \
    nginx \
    supervisor \
    unzip \
    && docker-php-ext-install opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /run/nginx

WORKDIR /var/www/html

# --- Dependencies ---
FROM base AS deps

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json* ./
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm ci

# --- Build frontend ---
FROM deps AS frontend

COPY . .
RUN npm run build

# --- Final image ---
FROM base

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
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

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
