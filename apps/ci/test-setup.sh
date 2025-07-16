#!/bin/bash

CURPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# go to the root of the project
cd "$CURPATH/../../"

export DOCKER_PHP_RESTART=no

docker compose up --build -d --wait
EXIT_CODE=$?

# Check if any container exited with non-zero status
FAILED_CONTAINERS=$(docker compose ps -q | xargs docker inspect --format '{{ .Name }} {{ .State.ExitCode }}' | grep -v ' 0$')

if [[ $EXIT_CODE -ne 0 || -n "$FAILED_CONTAINERS" ]]; then
  echo "Some containers exited with error:"
  echo "$FAILED_CONTAINERS"
  docker compose logs
  exit 1
fi

docker compose logs php
