ARG PHP_VERSION=7.4
FROM makasim/nginx-php-fpm:${PHP_VERSION}-all-exts

ARG PHP_VERSION

## libs
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests \
        wget \
        curl \
        openssl \
        ca-certificates \
        nano \
        netcat \
        php${PHP_VERSION}-dev \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-pgsql \
        git \
        python \
        php${PHP_VERSION}-amqp \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-curl \
        make \
        g++ \
        unzip \
    && \
    update-alternatives --install /usr/bin/php php /usr/bin/php${PHP_VERSION} 100

## gearman
RUN set -x && \
    apt-get install -y --no-install-recommends --no-install-suggests \
        libgearman-dev \
    && \
    mkdir -p $HOME/gearman && \
    cd $HOME/gearman && \
    git clone https://github.com/php/pecl-networking-gearman.git . && \
    git checkout gearman-2.1.0 && \
    phpize && ./configure && make && make install && \
    if [ ! -f /etc/php/${PHP_VERSION}/cli/conf.d/20-gearman.ini ]; then \
        echo "extension=gearman.so" > /etc/php/${PHP_VERSION}/cli/conf.d/20-gearman.ini && \
        echo "extension=gearman.so" > /etc/php/${PHP_VERSION}/fpm/conf.d/20-gearman.ini \
        ; \
    fi;

## librdkafka
RUN set -x && \
    mkdir -p $HOME/librdkafka && \
    cd $HOME/librdkafka && \
    git clone https://github.com/edenhill/librdkafka.git . && \
    git checkout v1.0.0 && \
    ./configure && make && make install

## php-rdkafka
RUN set -x && \
    mkdir -p $HOME/php-rdkafka && \
    cd $HOME/php-rdkafka && \
    git clone https://github.com/arnaud-lb/php-rdkafka.git . && \
    git checkout 5.0.1 && \
    phpize && ./configure && make all && make install && \
    echo "extension=rdkafka.so" > /etc/php/${PHP_VERSION}/cli/conf.d/10-rdkafka.ini && \
    echo "extension=rdkafka.so" > /etc/php/${PHP_VERSION}/fpm/conf.d/10-rdkafka.ini

COPY ./php/cli.ini /etc/php/${PHP_VERSION}/cli/conf.d/1-dev_cli.ini
COPY ./bin/dev_entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /mqdev
WORKDIR /mqdev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD /usr/local/bin/entrypoint.sh
