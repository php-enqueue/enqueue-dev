#!/usr/bin/env bash

set -e
set -x

(cd docker && docker build --rm --force-rm --no-cache --pull --squash --tag "enqueue/dev:latest" -f Dockerfile .)
(cd docker && docker login --username="$DOCKER_USER" --password="$DOCKER_PASSWORD")
(cd docker && docker push "enqueue/dev:latest")