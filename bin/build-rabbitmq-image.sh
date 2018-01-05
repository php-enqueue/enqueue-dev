#!/usr/bin/env bash

set -e
set -x

(cd docker && docker build --rm --force-rm --no-cache --pull --squash --tag "enqueue/rabbitmq:latest" -f Dockerfile.rabbitmq .)
(cd docker && docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD")
(cd docker && docker push "enqueue/rabbitmq:latest")