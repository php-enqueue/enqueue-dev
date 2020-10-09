#!/bin/bash

set -x
set -e

docker pull enqueue/dev
docker-compose run --workdir="/mqdev" --rm dev ./docker/bin/test.sh $@
