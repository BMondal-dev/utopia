FROM docker.io/library/composer:2 AS composer

WORKDIR /usr/local/src/

COPY composer.lock /usr/local/src/
COPY composer.json /usr/local/src/

RUN composer install \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-dev

FROM docker.io/phpswoole/swoole:php8.4-alpine AS base

WORKDIR /usr/src/code

RUN apk add --no-cache postgresql-dev \
    && (php -m | grep -q '^redis$' || (pecl install redis && docker-php-ext-enable redis)) \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer /usr/local/src/vendor /usr/src/code/vendor
COPY ./src /usr/src/code/src
COPY ./app /usr/src/code/app

EXPOSE 8080

CMD ["php", "app/http.php"]

FROM base AS development

RUN apk add --no-cache inotify-tools

COPY ./dev/watch.sh /usr/local/bin/watch
RUN chmod +x /usr/local/bin/watch

EXPOSE 8080

CMD ["watch"]

FROM base AS production

EXPOSE 8080

CMD ["php", "app/http.php"]
