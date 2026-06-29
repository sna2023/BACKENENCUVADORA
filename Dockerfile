FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip libpq-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan migrate --force --seed || php artisan db:seed --force; php artisan config:clear && php artisan serve --host=0.0.0.0 --port=$PORT
