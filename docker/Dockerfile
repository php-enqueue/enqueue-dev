FROM formapro/nginx-php-fpm:latest-all-exts

## libs
RUN set -x && \
    apt-get update && \
    apt-get install -y wget curl openssl ca-certificates nano netcat php-dev php-redis git python

## confis

# RUN rm -f /etc/php/7.0/cli/conf.d/*xdebug.ini

## librdkafka
RUN git clone https://github.com/edenhill/librdkafka.git /root/librdkafka
RUN cd /root/librdkafka && git checkout v0.11.0-RC2 && ./configure && make && make install
RUN pecl install rdkafka
RUN echo "extension=rdkafka.so" > /etc/php/7.1/cli/conf.d/10-rdkafka.ini
RUN echo "extension=rdkafka.so" > /etc/php/7.1/fpm/conf.d/10-rdkafka.ini

COPY ./php/cli.ini /etc/php/7.1/cli/conf.d/1-dev_cli.ini
COPY ./bin/dev_entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /mqdev
WORKDIR /mqdev

CMD /usr/local/bin/entrypoint.sh
