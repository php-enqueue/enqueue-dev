ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-alpine

ARG PHP_VERSION

RUN --mount=type=cache,target=/var/cache/apk apk add --no-cache $PHPIZE_DEPS \
    libpq-dev \
    librdkafka-dev \
    rabbitmq-c-dev \
    linux-headers && \
    apk add --no-cache --repository=https://dl-cdn.alpinelinux.org/alpine/edge/testing \
    gearman-dev

# Install First Party Modules
RUN docker-php-ext-install -j$(nproc) \
    pcntl \
    pdo_mysql \
    pdo_pgsql

#  Install Third Party Modules
RUN --mount=type=cache,target=/tmp/pear pecl install redis \
    mongodb-1.21.0 \
    gearman \
    rdkafka \
    xdebug && \
    pecl install --configureoptions 'with-librabbitmq-dir="autodetect"' amqp
RUN docker-php-ext-enable redis mongodb gearman rdkafka xdebug amqp

COPY ./php/cli.ini /usr/local/etc/php/conf.d/1-dev_cli
COPY ./bin/dev_entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /mqdev
WORKDIR /mqdev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["/usr/local/bin/entrypoint.sh"]
