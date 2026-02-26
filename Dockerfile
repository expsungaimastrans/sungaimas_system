FROM php:8.4-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl nginx \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --optimize-autoloader --no-interaction --no-dev

# Permission storage & cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Nginx config
RUN echo 'server { \
    listen 8080; \
    root /app/public; \
    index index.php; \
    location / { try_files $uri $uri/ /index.php?$query_string; } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}' > /etc/nginx/sites-available/default

EXPOSE 8080

CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan migrate --force && \
    php-fpm -D && \
    nginx -g "daemon off;"