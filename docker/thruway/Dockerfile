FROM makasim/nginx-php-fpm:latest-all-exts

RUN mkdir -p /thruway
WORKDIR /thruway

# Thruway router
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN COMPOSER_HOME=/thruway composer global require --prefer-dist --no-scripts voryx/thruway

COPY WsRouter.php .

CMD ["/usr/bin/php", "WsRouter.php"]

