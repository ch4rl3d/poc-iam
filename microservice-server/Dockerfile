#syntax=docker/dockerfile:1.4

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# Builder images
FROM composer/composer:2-bin AS composer

FROM mlocati/php-extension-installer:latest AS php_extension_installer

# Prod image
FROM php:8.2-fpm-alpine AS app_php

ARG HOST_GID=1000
ARG HOST_UID=1000

ENV APP_ENV=prod

WORKDIR /srv/app

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=php_extension_installer --link /usr/bin/install-php-extensions /usr/local/bin/

# persistent / runtime deps
RUN apk add --no-cache \
		acl \
		fcgi \
		file \
		gettext \
		git \
        shadow \
	; \
    usermod -u "${HOST_UID}" www-data; \
    existing_group=$(getent group "${HOST_GID}" | cut -d: -f1); \
    if [[ -n "${existing_group}" ]]; then delgroup "${existing_group}"; fi; \
    groupmod -g "${HOST_GID}" www-data;

RUN set -eux; \
    install-php-extensions \
		apcu \
		intl \
		opcache \
        gmp \
        bcmath \
        pdo \
        pdo_pgsql \
		zip \
    ;

###> recipes ###
###< recipes ###

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link microservice-server/docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link microservice-server/docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

RUN mkdir -p /var/run/php

COPY --link microservice-server/docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer --link /composer /usr/bin/composer

# prevent the reinstallation of vendors at every changes in the source code
COPY --link --chown=www-data:www-data microservice-server/composer.* microservice-server/symfony.* ./

RUN set -eux; \
    composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
    composer clear-cache;

# copy sources
COPY --link --chown=www-data:www-data microservice-server/bin ./bin
COPY --link --chown=www-data:www-data microservice-server/.env ./.env
COPY --link --chown=www-data:www-data microservice-server/public ./public
COPY --link --chown=www-data:www-data microservice-server/config ./config
COPY --link --chown=www-data:www-data microservice-server/migrations ./migrations
COPY --link --chown=www-data:www-data microservice-server/src ./src

RUN set -eux; \
	mkdir -p var/cache var/log; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    # composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    chmod +x bin/console; sync;

# Dev image
FROM app_php AS app_php_dev

ENV APP_ENV=dev XDEBUG_MODE=off
# VOLUME /srv/app/var/

RUN rm "$PHP_INI_DIR/conf.d/app.prod.ini"; \
	mv "$PHP_INI_DIR/php.ini" "$PHP_INI_DIR/php.ini-production"; \
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --link microservice-server/docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

RUN set -eux; \
	install-php-extensions \
    	xdebug \
    ;

RUN set -eux; \
    composer install --prefer-dist --no-progress; \
    composer clear-cache;


FROM nginx:stable-alpine3.17 AS app_nginx

WORKDIR /srv/app

COPY --from=app_php --link /srv/app/public public/
COPY --link microservice-server/docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf
