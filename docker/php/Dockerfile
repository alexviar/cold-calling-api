# Stage 1: Compilar dependencias y optimizar la aplicación
FROM composer:2 AS builder
WORKDIR /app

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --prefer-dist

# Stage 2: Imagen final optimizada para producción
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql bcmath \
    && rm -rf /var/lib/apt/lists/*  # Limpiar caché de APT

WORKDIR /var/www

COPY . .

# Copiar la aplicación ya compilada desde el builder
COPY --from=builder /app/vendor /var/www/vendor

# Ajustar permisos en directorios críticos
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
