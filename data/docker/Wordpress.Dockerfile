FROM wordpress:6.7.1-php8.3-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

# Update package list and install required packages
RUN apt-get update -y \
  && apt-get install -y \
     libfreetype6-dev \
     libmcrypt-dev \
     git \
     libxml2-dev \
     libpng-dev \
     zlib1g-dev \
     libgd3 \
     libonig-dev \
     libgd-dev \
     libicu-dev \
     libgmp-dev \
     redis-tools \
     procps \
     wget \
     netcat-openbsd \
     sendmail \
     socat \
     acl \
     && apt-get clean -y

# Install PHP extensions
RUN docker-php-ext-install soap \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install opcache \
    && docker-php-ext-install mysqli \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install gmp

# Install Redis PHP extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN wget --no-check-certificate -O /usr/local/bin/wp \
       https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp

# Add init script for runtime configuration
COPY apps/init /usr/local/bin/acore-init
RUN chmod +x /usr/local/bin/acore-init/init.sh

# Remove and re-add www-data user with specified UID and GID
RUN deluser www-data \
    && addgroup --gid $GROUP_ID www-data \
    && adduser --disabled-password --gecos '' --uid $USER_ID --gid $GROUP_ID www-data \
    && passwd -d www-data

ENTRYPOINT ["bash", "/usr/local/bin/acore-init/init.sh"]