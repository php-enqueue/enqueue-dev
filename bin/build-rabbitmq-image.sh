#!/usr/bin/env bash

set -e
set -x

(cd docker && docker build --rm --force-rm --no-cache --pull --squash --tag "enqueue/rabbitmq-local-build" -f Dockerfile."$1"-rabbitmq .)
(cd docker && docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD")
(cd docker && docker tag enqueue/rabbitmq-local-build enqueue/rabbitmq:"$1")
(cd docker && docker push "enqueue/rabbitmq:$1")
