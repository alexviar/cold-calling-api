# Imagen base con PHP y Apache
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libzip-dev zip unzip curl git \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Copiar configuración personalizada de Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Instalar dependencias de Laravel
RUN composer install --optimize-autoloader --no-dev

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer puertos necesarios
EXPOSE 80 6001

# Iniciar Apache
CMD ["apache2-foreground"]
