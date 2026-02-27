FROM php:8.3-fpm

# Install dependencies yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Copy composer dari official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Install dependencies PHP
RUN composer install --optimize-autoloader --no-dev

# Setting permission (penting buat Laravel)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Jalankan server
CMD php artisan serve --host=0.0.0.0 --port=8080
