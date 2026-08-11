# syntax=docker/dockerfile:1

# ---------- Etapa 1: assets do front (Tailwind via Vite) ----------
# public/build está no .gitignore, então os assets precisam ser gerados aqui.
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build


# ---------- Etapa 2: dependências PHP ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
# --no-scripts porque os scripts do Laravel precisam do código completo
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev


# ---------- Etapa 3: imagem final ----------
#
# Alpine puro com os pacotes PHP já compilados, em vez da imagem php:8.4-fpm
# com docker-php-ext-install.
#
# Motivo: compilar extensão exige instalar ~390 MB de gcc/g++/binutils e
# rodar o compilador em paralelo com o build do Vite. Em VPS pequena isso
# estoura a memória e o kernel mata o build no meio, sem mensagem de erro.
# Aqui não há compilação alguma: só download de binários prontos.
FROM alpine:3.22

# Só as extensões que o composer.lock realmente exige, mais pdo_mysql,
# opcache e gd. Nenhuma delas é compilada — vêm prontas do repositório.
RUN apk add --no-cache \
        nginx \
        supervisor \
        php84 \
        php84-fpm \
        php84-opcache \
        php84-pdo \
        php84-pdo_mysql \
        php84-mysqlnd \
        php84-gd \
        php84-mbstring \
        php84-dom \
        php84-xml \
        php84-xmlreader \
        php84-xmlwriter \
        php84-simplexml \
        php84-tokenizer \
        php84-session \
        php84-fileinfo \
        php84-ctype \
        php84-iconv \
        php84-openssl \
        php84-phar \
        php84-curl \
        php84-zip \
    # O Laravel chama "php"; no Alpine o binário se chama php84
    && ln -sf /usr/bin/php84 /usr/bin/php

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/php.ini          /etc/php84/conf.d/99-app.ini
COPY docker/php-fpm.conf     /etc/php84/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# O php-fpm roda como nginx; só storage e bootstrap/cache precisam de escrita
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R nginx:nginx storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# O Coolify usa esta checagem para saber quando o container está pronto
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/up > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
