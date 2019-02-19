FROM formapro/nginx-php-fpm:latest-all-exts

## libs
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests wget curl openssl ca-certificates nano netcat php-dev php-redis php-pgsql git python

RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests php-dev librabbitmq-dev make  && \
    mkdir -p $HOME/php-amqp && \
    cd $HOME/php-amqp && \
    git clone https://github.com/pdezwart/php-amqp.git . && git checkout v1.9.3 && \
    phpize --clean && phpize && ./configure && make install

## librdkafka
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests g++ php-pear php-dev && \
    mkdir -p $HOME/librdkafka && \
    cd $HOME/librdkafka && \
    git clone https://github.com/edenhill/librdkafka.git . && \
    git checkout v0.11.1 && \
    ./configure && make && make install && \
    pecl install rdkafka && \
    echo "extension=rdkafka.so" > /etc/php/7.2/cli/conf.d/10-rdkafka.ini && \
    echo "extension=rdkafka.so" > /etc/php/7.2/fpm/conf.d/10-rdkafka.ini

COPY ./php/cli.ini /etc/php/7.2/cli/conf.d/1-dev_cli.ini
COPY ./bin/dev_entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /mqdev
WORKDIR /mqdev

CMD /usr/local/bin/entrypoint.sh
