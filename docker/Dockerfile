FROM formapro/nginx-php-fpm:latest-all-exts

## libs
RUN set -x && \
    apt-get update && \
    apt-get install -y --no-install-recommends wget curl openssl ca-certificates nano netcat php-redis

## confis

# RUN rm -f /etc/php/7.0/cli/conf.d/*xdebug.ini

COPY ./php/cli.ini /etc/php/7.1/cli/conf.d/1-dev_cli.ini
COPY ./bin/dev_entrypoiny.sh /usr/local/bin/entrypoint.sh
RUN chmod u+x /usr/local/bin/entrypoint.sh

RUN mkdir -p /mqdev
WORKDIR /mqdev

CMD /usr/local/bin/entrypoint.sh
