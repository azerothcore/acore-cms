FROM wordpress:5-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

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
     libgmp-dev
RUN apt-get clean -y
RUN docker-php-ext-install soap
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install gmp

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN deluser www-data
RUN addgroup --gid $GROUP_ID www-data && \
    adduser --disabled-password --gecos '' --uid $USER_ID --gid $GROUP_ID www-data && \
    passwd -d www-data

# Correct permissions for non-root operations
RUN chown -R www-data:www-data \
    /run \
    /var/www/html


