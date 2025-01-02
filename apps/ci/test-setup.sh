#!/bin/bash

CURPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# go to the root of the project
cd "$CURPATH/../../"

export DOCKER_PHP_RESTART=no

docker compose up --build -d --wait

# Show the logs
docker compose logs php


