#!/usr/bin/env bash


set -e
set -x

mkdir -p /tmp/roboconf
rm -rf /tmp/roboconf/*

(cd /tmp/roboconf && git clone git@github.com:roboconf/rabbitmq-with-ssl-in-docker.git)

(cd /tmp/roboconf/rabbitmq-with-ssl-in-docker && docker build --rm --force-rm --no-cache --pull --squash --tag "enqueue/rabbitmq-ssl:latest" .)

(cd /tmp/roboconf/rabbitmq-with-ssl-in-docker && docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD")
(cd /tmp/roboconf/rabbitmq-with-ssl-in-docker && docker push "enqueue/rabbitmq-ssl:latest")

docker run --rm -v "`pwd`/var/rabbitmq_certificates:/enqueue" "enqueue/rabbitmq-ssl:latest" cp /home/testca/cacert.pem /enqueue/cacert.pem



