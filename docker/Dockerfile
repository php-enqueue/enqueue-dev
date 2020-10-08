FROM formapro/nginx-php-fpm:7.3-latest-all-exts

## libs
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests wget curl openssl ca-certificates nano netcat php7.3-dev php7.3-redis php7.3-pgsql git python

## librdkafka
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests g++ php-pear php7.3-dev make && \
    mkdir -p $HOME/librdkafka && \
    cd $HOME/librdkafka && \
    git clone https://github.com/edenhill/librdkafka.git . && \
    git checkout v1.0.0 && \
    ./configure && make && make install && \
    pecl install rdkafka && \
    echo "extension=rdkafka.so" > /etc/php/7.3/cli/conf.d/10-rdkafka.ini && \
    echo "extension=rdkafka.so" > /etc/php/7.3/fpm/conf.d/10-rdkafka.ini

COPY ./php/cli.ini /etc/php/7.3/cli/conf.d/1-dev_cli.ini
COPY ./bin/dev_entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /mqdev
WORKDIR /mqdev

CMD /usr/local/bin/entrypoint.sh
