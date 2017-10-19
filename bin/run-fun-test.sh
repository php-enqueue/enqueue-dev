#!/bin/bash

set -x
set -e

COMPOSE_PROJECT_NAME=mqdev docker-compose run --workdir="/mqdev" --rm dev ./bin/test "$@"