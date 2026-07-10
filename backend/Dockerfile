# Laravel API — simple single-stage image using PHP's built-in server.
FROM php:8.3-cli

# System deps + PHP extensions Laravel needs.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libsqlite3-dev libicu-dev libpq-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql pdo_pgsql zip intl bcmath \
    && rm -rf /var/lib/apt/lists/*

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first (better layer caching).
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist

# App source.
COPY . .
RUN composer dump-autoload --optimize

# Entrypoint prepares the SQLite DB, runs migrations + seeds, then serves.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p database \
    && chmod -R 777 storage bootstrap/cache database

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]
