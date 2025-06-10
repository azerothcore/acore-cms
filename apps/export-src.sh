#!/bin/bash

docker compose stop

docker_container_name="php"

# Get the container ID for the 'php' service (running or stopped)
php_container_id=$(docker ps -a -q --filter "name=-$docker_container_name")

# Check if the container exists (even if stopped)
if [ -z "$php_container_id" ]; then
    echo "No container found for the '$docker_container_name' service (running or stopped)."
    exit 1
fi

# Retrieve the full container name
php_container_name=$(docker inspect -f '{{.Name}}' "$php_container_id" | sed 's/^\/\+//')

# Extract the prefix from the container name (everything before '_php')
php_container_prefix=$(echo "$php_container_name" | sed "s/-$docker_container_name.*//")

echo "Container Prefix: $php_container_prefix"

# Use the prefix for volume operations
docker run --rm -v "${php_container_prefix}_wordpress-src:/var/www/html" -v "$(pwd)/srv:/srv" alpine sh -c "cp -r /srv/* /var/www/html/ && ls -l /var/www/html/"
