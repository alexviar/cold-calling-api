# Dockerfile
FROM php:8.2-cli

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    ffmpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Crear directorio de trabajo
WORKDIR /app

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de Laravel
RUN composer install --optimize-autoloader --no-dev

RUN php artisan migrate --force

# Exponer el puerto del WebSocket (configurado en Laravel Reverb)
EXPOSE 8080

# Comando para ejecutar el servidor WebSocket
CMD ["php", "artisan", "reverb:start"]
