#!/bin/bash

set -x
set -e

docker-compose run --workdir="/mqdev" --rm dev ./bin/test "$@"
