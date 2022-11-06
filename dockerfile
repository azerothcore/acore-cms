captainVersion: 4
caproverOneClickApp:
    instructions:
        start: Just a plain Docker Compose.
        end: Docker Compose is deployed.
########
version: '3.7'

services:
  wp-db:
    restart: unless-stopped
    image: mysql:8
    command: 'mysqld --default-authentication-plugin=mysql_native_password'
    volumes:
      - mysql-data:/var/lib/mysql
      - ./data:/data/
      - ./apps:/apps/
      - ./conf:/conf/
    env_file:
      # environment variables are retrieved by this file
      # NOTE: you can add more variables to the .env file but
      # you cannot override the .env.docker ones (by design)
      - .env.docker
    environment:
      DOCKER_CONTAINER: 1
      MYSQL_ROOT_PASSWORD: flkdsjalfh
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
    networks:
      - local-private-net
    healthcheck:
        test: mysqladmin ping -h 127.0.0.1 -u $$MYSQL_USER --password=$$MYSQL_PASSWORD
    cap_add:
      - SYS_NICE  # CAP_SYS_NICE
  php:
    restart: unless-stopped
    build:
      context: .
      dockerfile: data/docker/Wordpress.Dockerfile
      args:
        USER_ID: ${DOCKER_USER_ID:-1000}
        GROUP_ID: ${DOCKER_GROUP_ID:-1000}
    depends_on:
      - wp-db
    working_dir: "/var/www/html"
    volumes:
      - type: bind
        source: ${DOCKER_CONF_PHP_PATH:-./conf/dist/php-conf/upload.ini}
        target: /usr/local/etc/php/conf.d/upload.ini
      - ./srv/wordpress:/var/www/html
    env_file:
      # environment variables are retrieved by this file
      # NOTE: you can add more variables to the .env file but
      # you cannot override the .env.docker ones (by design)
      - .env.docker
    environment:
      DOCKER_CONTAINER: 1
      WORDPRESS_DB_HOST: wp-db:3306
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      PHP_OPCACHE_VALIDATE_TIMESTAMPS: 1
      _OPCACHE_MAX_ACCELERATED_FILES: 100000
      PHP_OPCACHE_MEMORY_CONSUMPTION: 64
      PHP_OPCACHE_MAX_WASTED_PERCENTAGE: 5
    networks:
      - local-private-net
  web.local:
    restart: unless-stopped
    image: nginx
    env_file:
      # environment variables are retrieved by this file
      # NOTE: you can add more variables to the .env file but
      # you cannot override the .env.docker ones (by design)
      - .env.docker
    environment:
      DOCKER_CONTAINER: 1
    depends_on:
      - php
    volumes:
      - ${DOCKER_CONF_NGINX_PATH:-./conf/dist/nginx-conf}:/etc/nginx/conf.d/
      - ${DOCKER_CONF_CERTS_PATH:-./conf/dist/certs/}:/etc/nginx/certs/
      - ./srv/wordpress:/var/www/html
      - ./var/logs:/var/log/nginx
    extra_hosts:
      - "web.local:127.0.0.1"
    ports:
      - ${DOCKER_HTTP_PORTS:-80:80}
      - ${DOCKER_HTTPS_PORTS:-443:443}
    expose:
      - "80"
      - "443"
    networks:
      - local-private-net
      - local-shared-net
networks:
  local-shared-net:
    name: local-shared-net
    driver: bridge
  local-private-net:
    driver: bridge
volumes:
  mysql-data:
