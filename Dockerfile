# syntax=docker/dockerfile:1.4
FROM php:8.2-fpm-alpine AS app_php

COPY --from=composer/composer:2-bin --link /composer /usr/local/bin/composer
COPY --from=mlocati/php-extension-installer:latest --link /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache shadow fcgi
RUN set -eux; \
    install-php-extensions \
		apcu \
		intl \
		opcache \
		zip \
    pdo pdo_pgsql \
    ;

COPY --link docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/

COPY --link docker/php/fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
RUN mkdir -p /var/run/php

COPY --link docker/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck
HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

ARG UID=10000
ARG GID=10000
RUN usermod -u $UID www-data && groupmod -g $GID www-data && chown www-data:www-data /var/run/php

WORKDIR /srv/app

FROM app_php AS app_php_dev

RUN set -eux; \
	install-php-extensions \
    	xdebug \
    ;

RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini
COPY --link docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

RUN chown www-data:www-data /opt

USER www-data

FROM caddy:2.6-alpine AS app_caddy

COPY --link docker/caddy/Caddyfile /etc/caddy/Caddyfile

ARG UID=10000
ARG GID=10000
RUN addgroup -g $GID caddy && adduser -S -H -u $UID -G caddy -D caddy && chown -R caddy:caddy /data /config

USER caddy
