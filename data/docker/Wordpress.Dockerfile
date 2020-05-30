FROM wordpress:5-fpm

RUN apt-get update -y \
  && apt-get install -y \
     libxml2-dev \
     libpng-dev \
     zlib1g-dev \
  && apt-get clean -y \
  && docker-php-ext-install soap  
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install gd
