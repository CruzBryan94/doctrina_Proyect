# syntax=docker/dockerfile:1
# Registry manifest digests resolved during preparation; update through a reviewed PR.
FROM composer:2@sha256:b6dcd72ca98fa19d0e5b8476de179859704891fad06dd9e0b96833d4c430a5fc AS composer-bin
FROM caddy:2@sha256:504a44d7d74bd2bc28a66afc4774912fc21223ca8a67734af1c54ab2f999b489 AS caddy-bin
FROM node:22-bookworm-slim@sha256:de170b39439eb0e5a3444ad7d60b015614a381e10fecae2e30ec9e735122f38d AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.3-fpm-bookworm@sha256:6b4641a875dd3e0f7551b2751e1f5909084856d18166af7fbb522ac230feb702 AS php-base
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
        libonig-dev libxml2-dev libzip-dev unzip tini gosu \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_mysql mbstring dom gd zip opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app

FROM php-base AS dependencies
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
# No Laravel scripts, DB connections, keys or environment file during image build.
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --optimize-autoloader \
    && composer check-platform-reqs --no-dev

FROM php-base AS runtime
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=warning \
    PORT=8080 XDG_CONFIG_HOME=/tmp/caddy-config XDG_DATA_HOME=/tmp/caddy-data
COPY --from=caddy-bin /usr/bin/caddy /usr/local/bin/caddy
COPY . /app
COPY --from=dependencies /app/vendor /app/vendor
COPY --from=frontend /build/public/build /app/public/build
COPY docker/php/ /usr/local/etc/php/conf.d/
# Replace the image's www pool and docker pool overrides, rather than adding a second pool.
RUN rm /usr/local/etc/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-artemis.conf
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/start.sh /usr/local/bin/artemis-start
RUN chmod 755 /usr/local/bin/artemis-start \
    && mkdir -p bootstrap/cache storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs storage/fonts \
    && chown -R www-data:www-data bootstrap/cache storage \
    && php-fpm -tt \
    && caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
EXPOSE 8080
ENTRYPOINT ["/usr/bin/tini", "--", "/usr/local/bin/artemis-start"]
