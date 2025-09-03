# Dockerfile para Laravel API
FROM php:8.2-fpm-alpine

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

# Instalar dependências do sistema
RUN apk add --no-cache \
    git curl libpng-dev oniguruma-dev libxml2-dev zip unzip mysql-client \
    supervisor bash vim $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

# Instalar extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && pecl install mongodb-1.21.1 \
    && docker-php-ext-enable mongodb \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Diretório de trabalho
WORKDIR /var/www/html

# Copiar dependências primeiro (cache eficiente)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress --prefer-dist

# Copiar aplicação completa
COPY . .

# Criar diretórios e definir permissões
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copiar e liberar script de init
COPY init.sh /var/www/html/init.sh
RUN chmod +x /var/www/html/init.sh

# Copiar supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar .env de exemplo se não existir
RUN [ ! -f .env ] && cp .env.example .env || echo ".env já existe"

# Expor porta
EXPOSE 8000

# Comando inicial
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
