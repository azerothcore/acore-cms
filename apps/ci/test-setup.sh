#!/bin/bash

CURPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# go to the root of the project
cd "$CURPATH/../../"

export DOCKER_PHP_RESTART=no

docker compose up --build -d --wait --exit-code-from php || {
    echo "Failed to start the Docker containers. Please check the logs for more details."
    exit 1
}

# Show the logs
docker compose logs php


