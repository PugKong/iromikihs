#syntax=docker/dockerfile:1.4

FROM dunglas/frankenphp:1.1.2-php8.3-alpine AS frankenphp_upstream
FROM composer/composer:2-bin AS composer_upstream
FROM node:21-alpine AS node_upstream


FROM node_upstream AS node_base

WORKDIR /app


FROM node_base AS node_builder

COPY --link ./assets ./assets
COPY --link ./templates ./templates
COPY --link ./node_modules ./node_modules
COPY --link ./vendor ./vendor
COPY --link ./package.json ./package-lock.json ./postcss.config.js ./tailwind.config.js ./webpack.config.js ./

RUN npm run build


FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app

RUN set -eux; install-php-extensions apcu intl opcache zip pcntl pdo pdo_pgsql

COPY --link docker/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link docker/Caddyfile /etc/caddy/Caddyfile
COPY --from=composer_upstream --link /composer /usr/bin/composer

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]


FROM frankenphp_base AS frankenphp_dev

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; install-php-extensions xdebug

COPY --link docker/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

ARG UID=1000
ARG GID=1000
RUN addgroup -g $GID franken && adduser -S -u $UID -G franken -D franken && chown -R franken:franken /data /config
VOLUME /home/franken
USER franken


FROM frankenphp_base AS frankenphp_prod

ENV FRANKENPHP_CONFIG="worker /app/public/index.php 1"
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"

ARG UID=10000
ARG GID=10000
RUN addgroup -g $GID franken && adduser -S -H -u $UID -G franken -D franken && chown -R franken:franken /data /config

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link docker/conf.d/app.prod.ini "$PHP_INI_DIR/conf.d/"

COPY --link ./bin ./bin
COPY --link ./config ./config
COPY --link ./migrations ./migrations
COPY --link ./public/index.php ./public/index.php
COPY --link ./src ./src
COPY --link ./templates ./templates
COPY --link ./translations ./translations
COPY --link ./vendor ./vendor
COPY --link ./.env ./composer.* ./symfony.lock ./
COPY --from=node_builder --link /app/public/build /app/public/build

RUN set -eux; \
  mkdir -p var/cache var/log; \
  composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
  composer dump-autoload --classmap-authoritative --no-dev; \
  composer dump-env prod; \
  composer run-script --no-dev post-install-cmd; \
  chmod +x bin/console; \
  chown -R franken:franken var; \
  sync;

USER franken
