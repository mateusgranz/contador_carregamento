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
FROM php:8.4-fpm-alpine

# nginx serve os arquivos, supervisor mantém nginx e php-fpm juntos no container.
#
# Extensões: apenas o que as dependências realmente exigem (conferido no
# composer.lock). A extensão intl NÃO entra — nada no projeto usa
# NumberFormatter/Collator, os polyfills do Symfony cobrem o resto, e compilá-la
# levava mais de 4 minutos e estourava a memória de VPS pequena.
#
# -j2 em vez de $(nproc): gcc paralelo demais é o que derruba build em servidor
# com pouca RAM, e aqui sobrou pouca coisa para compilar.
RUN apk add --no-cache \
        nginx \
        supervisor \
        libpng \
        libjpeg-turbo \
        freetype \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 \
        pdo_mysql \
        gd \
        opcache \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

COPY docker/nginx.conf      /etc/nginx/nginx.conf
COPY docker/php.ini         /usr/local/etc/php/conf.d/99-app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh   /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# O php-fpm roda como www-data; só storage e bootstrap/cache precisam de escrita
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# O Coolify usa esta checagem para saber quando o container está pronto
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/up > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
