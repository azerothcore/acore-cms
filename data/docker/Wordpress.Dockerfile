FROM wordpress:5-fpm

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
     libgd-dev
RUN apt-get clean -y
RUN docker-php-ext-install soap
RUN docker-php-ext-install mbstring 
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN usermod -u 1000 www-data
RUN usermod -G staff www-data

RUN chown -R 1000:www-data /var/www/html
