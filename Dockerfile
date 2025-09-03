# =============================================================================
# Dockerfile Otimizado para Laravel 10 F2B API com MongoDB
# =============================================================================
# Este Dockerfile usa multi-stage build para otimizar o processo de compilação
# =============================================================================

# Stage 1: Build das extensões PHP
FROM php:8.2-fpm-alpine AS php-extensions

# Instalar dependências de compilação
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    autoconf \
    g++ \
    gcc \
    make \
    pkgconfig \
    openssl-dev \
    libssl3 \
    cyrus-sasl-dev \
    pcre-dev \
    zlib-dev

# Compilar extensões (com output silencioso e configurações otimizadas)
RUN pecl channel-update pecl.php.net \
    && pecl install -o -f redis mongodb 2>/dev/null \
    && docker-php-ext-enable redis mongodb

# Stage 2: Imagem final otimizada
FROM php:8.2-fpm-alpine

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Copiar extensões compiladas do stage anterior
COPY --from=php-extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Instalar dependências do sistema (sem compiladores)
RUN apk add --no-cache \
    bash \
    curl \
    git \
    supervisor \
    mysql-client \
    zip \
    unzip \
    libpng \
    oniguruma \
    libxml2 \
    openssl \
    libssl3 \
    cyrus-sasl \
    pcre \
    zlib \
    && rm -rf /var/cache/apk/*

# Instalar extensões PHP básicas
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Instalar Composer (versão mais estável)
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar e instalar dependências PHP (cache layer)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-suggest \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    && composer clear-cache

# Copiar aplicação
COPY . .

# Configurar permissões e diretórios
RUN mkdir -p \
    storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copiar configurações
COPY init.sh /var/www/html/init.sh
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod +x /var/www/html/init.sh

# Criar .env se não existir
RUN [ ! -f .env ] && cp .env.example .env || echo ".env already exists"

# Configurações PHP para produção
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Expor porta
EXPOSE 8007

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8007/api/health || exit 1

# Comando inicial
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
