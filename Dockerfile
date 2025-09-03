# =============================================================================
# Dockerfile Otimizado para Laravel 10 F2B API
# =============================================================================
# Build unificado para evitar problemas de dependências
# Configurado com MySQL + Redis (MongoDB comentado temporariamente)
# =============================================================================

FROM php:8.2-fpm-alpine

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Instalar todas as dependências necessárias
RUN apk add --no-cache \
    # Ferramentas básicas
    bash curl git supervisor mysql-client zip unzip \
    # Ferramentas de rede para detecção de hosts
    bind-tools iproute2 net-tools \
    # Bibliotecas runtime
    libpng oniguruma libxml2 openssl libssl3 \
    pcre zlib freetype libjpeg-turbo \
    && rm -rf /var/cache/apk/*

# Instalar dependências de compilação temporárias
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS autoconf g++ gcc make pkgconfig \
    libpng-dev oniguruma-dev libxml2-dev openssl-dev \
    pcre-dev zlib-dev freetype-dev libjpeg-turbo-dev
    # cyrus-sasl-dev (removido - específico para MongoDB)

# Configurar e instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql mbstring exif pcntl bcmath gd

# Instalar extensões PECL (silencioso)
RUN pecl channel-update pecl.php.net \
    && pecl install -o -f redis 2>/dev/null \
    && docker-php-ext-enable redis
    # && pecl install -o -f mongodb 2>/dev/null \
    # && docker-php-ext-enable mongodb

# Limpar dependências de compilação
RUN apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# Instalar Composer (versão estável)
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do Composer
COPY composer.json composer.lock ./

# Verificar se os arquivos existem e instalar dependências
RUN ls -la composer.* \
    && composer --version \
    && composer validate --no-check-publish \
    && composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-req=ext-mongodb \
    && composer clear-cache

# Copiar aplicação
COPY . .

# Copiar configuração PHP personalizada
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

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
