ARG PHP_VERSION=7.3
FROM formapro/nginx-php-fpm:${PHP_VERSION}-latest-all-exts

ARG PHP_VERSION

## libs
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests wget curl openssl ca-certificates nano netcat php${PHP_VERSION}-dev php${PHP_VERSION}-redis php${PHP_VERSION}-pgsql git python php${PHP_VERSION}-amqp

## librdkafka
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests g++ php${PHP_VERSION}-pear php${PHP_VERSION}-dev make && \
    mkdir -p $HOME/librdkafka && \
    cd $HOME/librdkafka && \
    git clone https://github.com/edenhill/librdkafka.git . && \
    git checkout v1.0.0 && \
    ./configure && make && make install && \
    pecl install rdkafka && \
    echo "extension=rdkafka.so" > /etc/php/${PHP_VERSION}/cli/conf.d/10-rdkafka.ini && \
    echo "extension=rdkafka.so" > /etc/php/${PHP_VERSION}/fpm/conf.d/10-rdkafka.ini

COPY ./php/cli.ini /etc/php/${PHP_VERSION}/cli/conf.d/1-dev_cli.ini
COPY ./bin/dev_entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /mqdev
WORKDIR /mqdev

CMD /usr/local/bin/entrypoint.sh
