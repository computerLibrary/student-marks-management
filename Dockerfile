FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true
RUN a2enmod mpm_prefork
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 8080
CMD ["apache2-foreground"]
