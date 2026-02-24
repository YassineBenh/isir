############################################
# Runtime Base Image
############################################

FROM serversideup/php:8.5-fpm-nginx-alpine AS runtime

############################################
# PHP Dependencies Build Stage
############################################
FROM runtime AS php-build

WORKDIR /var/www/html

# Install PHP dependencies first (cached layer)
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Copy source code
COPY --chown=www-data:www-data . .

############################################
# Frontend Build Stage
############################################
FROM php-build AS frontend-build

USER root

RUN apk add --no-cache nodejs npm && \
    mkdir -p /var/www/html/storage/logs && \
    chown -R www-data:www-data /var/www/html/storage

USER www-data

WORKDIR /var/www/html

# Install Node dependencies first (cached layer)
COPY --chown=www-data:www-data package.json package-lock.json ./
RUN npm ci

RUN npm run build

############################################
# Production Deploy Image
############################################
FROM runtime AS deploy

ENV PHP_OPCACHE_ENABLE="1"
ENV AUTORUN_ENABLED="true"

WORKDIR /var/www/html

COPY --from=php-build --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=php-build --chown=www-data:www-data /var/www/html/public ./public
COPY --from=php-build --chown=www-data:www-data /var/www/html/bootstrap ./bootstrap
COPY --from=php-build --chown=www-data:www-data /var/www/html/app ./app
COPY --from=php-build --chown=www-data:www-data /var/www/html/config ./config
COPY --from=php-build --chown=www-data:www-data /var/www/html/database ./database
COPY --from=php-build --chown=www-data:www-data /var/www/html/resources ./resources
COPY --from=php-build --chown=www-data:www-data /var/www/html/routes ./routes
COPY --from=php-build --chown=www-data:www-data /var/www/html/storage ./storage
COPY --from=php-build --chown=www-data:www-data /var/www/html/artisan ./artisan
COPY --from=php-build --chown=www-data:www-data /var/www/html/composer.json ./composer.json
COPY --from=php-build --chown=www-data:www-data /var/www/html/composer.lock ./composer.lock
COPY --from=php-build --chown=www-data:www-data /var/www/html/.env.example.production ./.env.example.production
COPY --from=frontend-build --chown=www-data:www-data /var/www/html/public/build ./public/build

COPY --chmod=755 ./docker/entrypoint.d/ /etc/entrypoint.d/
COPY --chmod=755 ./docker/s6-rc.d/ /etc/s6-overlay/s6-rc.d/

USER www-data
