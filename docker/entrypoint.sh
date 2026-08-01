#!/bin/sh
set -e

cd /var/www/html

echo "==> Verificando configuração"

if [ -z "${APP_KEY}" ]; then
    echo "ERRO: APP_KEY não está definida."
    echo "Gere uma com 'php artisan key:generate --show' e cadastre no Coolify."
    echo "Sem ela, sessões e dados criptografados não funcionam."
    exit 1
fi

# storage pode vir de um volume novo, sem as subpastas que o Laravel espera
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Aguardando o banco de dados"

tentativas=0
until php -r "
    \$dsn = getenv('DB_CONNECTION') === 'sqlite'
        ? 'sqlite:'.getenv('DB_DATABASE')
        : sprintf('mysql:host=%s;port=%s', getenv('DB_HOST'), getenv('DB_PORT') ?: 3306);
    try { new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); }
    catch (Throwable \$e) { exit(1); }
" 2>/dev/null; do
    tentativas=$((tentativas + 1))

    if [ "$tentativas" -ge 30 ]; then
        echo "ERRO: banco de dados não respondeu depois de 30 tentativas."
        exit 1
    fi

    echo "    banco ainda não respondeu (tentativa ${tentativas}/30)..."
    sleep 2
done

echo "==> Rodando migrations"
php artisan migrate --force --no-interaction

echo "==> Gerando cache de configuração"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Pronto — subindo nginx e php-fpm"

exec "$@"
