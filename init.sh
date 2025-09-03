#!/bin/bash
cd /var/www/html || exit 1

echo "=== Iniciando Laravel F2B API ==="

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

echo "=== Laravel Init finalizado ==="
