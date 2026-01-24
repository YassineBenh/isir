############################################
# Base Image
############################################

FROM serversideup/php:8.5-fpm-nginx AS base

USER root

RUN curl -fsSL https://deb.nodesource.com/setup_22.x -o /tmp/nodesource_setup.sh && \
    bash /tmp/nodesource_setup.sh && \
    apt-get install -y nodejs=22.* && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

USER www-data

############################################
# Build Stage with Dependency Caching
############################################
FROM base AS build

WORKDIR /var/www/html

# Install PHP dependencies first (cached layer)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

# Install Node dependencies including dev deps for Vite (cached layer)
COPY package.json package-lock.json ./
RUN npm ci

# Copy source code and build (only runs when code changes)
COPY --chown=www-data:www-data . .
RUN npm run build

############################################
# Production Deploy Image
############################################
FROM base AS deploy

ENV PHP_OPCACHE_ENABLE="1"
ENV AUTORUN_ENABLED="true"

WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=build --chown=www-data:www-data /var/www/html/public ./public
COPY --from=build --chown=www-data:www-data /var/www/html/bootstrap ./bootstrap
COPY --from=build --chown=www-data:www-data /var/www/html/app ./app
COPY --from=build --chown=www-data:www-data /var/www/html/config ./config
COPY --from=build --chown=www-data:www-data /var/www/html/database ./database
COPY --from=build --chown=www-data:www-data /var/www/html/resources ./resources
COPY --from=build --chown=www-data:www-data /var/www/html/routes ./routes
COPY --from=build --chown=www-data:www-data /var/www/html/storage ./storage
COPY --from=build --chown=www-data:www-data /var/www/html/artisan ./artisan
COPY --from=build --chown=www-data:www-data /var/www/html/composer.json ./composer.json
COPY --from=build --chown=www-data:www-data /var/www/html/composer.lock ./composer.lock
COPY --from=build --chown=www-data:www-data /var/www/html/.env.example.production ./.env.example.production

COPY --chmod=755 ./docker/entrypoint.d/ /etc/entrypoint.d/

USER www-data
