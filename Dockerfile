FROM php:8.1.1-fpm

WORKDIR /var/www

COPY composer.lock composer.json ./

RUN apt-get update && apt-get install -y \
    build-essential \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip


RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

COPY . /var/www/

COPY --chown=www:www . /var/www/
RUN chown -R www-data:www-data /var/www

#change a current user to www
USER www

EXPOSE 9000

CMD ["php-fpm"]
