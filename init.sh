#!/bin/bash
cd /var/www/html || exit 1

echo "=== Iniciando Laravel F2B API ==="

# Auto-detectar hosts dos bancos de dados para containers Docker
echo "Detectando hosts dos bancos de dados..."

# Detectar MySQL
MYSQL_HOST=$(getent hosts | grep -E '(mysql|f2bmysql)' | head -1 | awk '{print $2}' || echo "localhost")
if [ "$MYSQL_HOST" != "localhost" ]; then
    echo "MySQL host detectado: $MYSQL_HOST"
    # Atualizar DATABASE_URL no .env
    sed -i "s|mysql://[^@]*@[^:]*:|mysql://admin:476de5e30f4c3efffd73879125503321@$MYSQL_HOST:|g" .env
fi

# Detectar MongoDB
MONGO_HOST=$(getent hosts | grep -E '(mongo|f2b-tylxfu)' | head -1 | awk '{print $2}' || echo "localhost")
if [ "$MONGO_HOST" != "localhost" ]; then
    echo "MongoDB host detectado: $MONGO_HOST"
    # Atualizar variáveis do MongoDB no .env
    sed -i "s|DB_MONGO_HOST=.*|DB_MONGO_HOST=$MONGO_HOST|g" .env
    sed -i "s|mongodb://[^@]*@[^:]*:|mongodb://admin:476de5e30f4c3efffd73879125503321@$MONGO_HOST:|g" .env
fi

# Gerar APP_KEY se não existir
if ! grep -q '^APP_KEY=' .env || grep -q '^APP_KEY=$' .env; then
    echo "Gerando chave da aplicação..."
    php artisan key:generate --no-interaction --force
fi

echo "Limpando cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

echo "Rodando migrações..."
php artisan migrate --force

# Rodar seed apenas se ainda não rodou
if [ ! -f storage/.seeded ]; then
    echo "Rodando seeders..."
    php artisan db:seed --force
    touch storage/.seeded
else
    echo "Seeders já rodaram, pulando..."
fi

echo "Instalando Passport..."
php artisan passport:install --force

php artisan storage:link

# Cache de otimização para produção
if [ "$APP_ENV" = "production" ]; then
    echo "Otimizando para produção..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

echo "=== Laravel Init finalizado ==="
