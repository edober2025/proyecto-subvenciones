FROM php:8.2-cli
RUN apt-get update && apt-get install -y git unzip libzip-dev libsqlite3-dev 
&& docker-php-ext-install pdo_sqlite zip 
&& rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN cp .env.example .env 
&& composer install --no-dev --optimize-autoloader 
&& touch database/database.sqlite 
&& php artisan key:generate 
&& php artisan migrate --force
EXPOSE 10000
CMD sh -c "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"
