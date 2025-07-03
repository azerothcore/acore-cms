#!/usr/bin/env bash
set -euo pipefail

service="php"                    # service name in docker-compose.yml

# Find the container ID for this project, even if it is stopped
cid=$(docker compose ps -a -q "$service")

if [ -z "$cid" ]; then
  echo "No '$service' container found for this project (running or stopped)."
  exit 1
fi

# Ask Docker for the project name via the container label
project=$(docker inspect -f '{{ index .Config.Labels "com.docker.compose.project" }}' "$cid")

# Compose the volume name: <project>_wordpress-src
volume="${project}_wordpress-src"

echo "Using container ID: $cid"
echo "Using volume:      $volume"

# Copy everything from ./srv into /var/www/html inside the volume
docker run --rm \
  -v "${volume}:/var/www/html" \
  -v "$(pwd)/srv:/srv" \
  alpine sh -c 'cp -r /srv/* /var/www/html/ && ls -la /var/www/html/'
