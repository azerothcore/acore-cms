FROM wordpress:5-fpm

RUN apt-get update -y \
  && apt-get install -y \
     git \
     libxml2-dev \
     libpng-dev \
     zlib1g-dev \
  && apt-get clean -y \
  && docker-php-ext-install soap  
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN usermod -u 1000 www-data
RUN usermod -G staff www-data

ADD srv/wordpress /var/www/html

RUN chown -R 1000:www-data /var/www/html
