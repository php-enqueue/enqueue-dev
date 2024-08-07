#!/bin/bash

set -x
set -e

docker compose run --workdir="/mqdev" --rm dev ./docker/bin/test.sh $@
