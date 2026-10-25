# syntax=docker/dockerfile:1
FROM composer:2 AS composer
FROM php:8.4-apache AS php-build
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev \
    libzip-dev libicu-dev libpq-dev unzip curl postgresql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 gd intl zip pdo_pgsql bcmath mbstring exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/portal.conf \
    && a2enconf portal && a2enmod rewrite headers \
    && printf 'ServerLimit 4\nStartServers 1\nMinSpareServers 1\nMaxSpareServers 2\nMaxRequestWorkers 4\nMaxConnectionsPerChild 500\n' > /etc/apache2/mods-available/mpm_prefork.conf
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
RUN test -s composer.lock && test -s package-lock.json \
    && mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && sed -i 's/\r$//' entrypoint.sh && chmod +x entrypoint.sh

FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.* postcss.config.js tailwind.config.js ./
COPY --from=php-build /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN mkdir -p storage/framework/views
RUN npm run build


FROM php-build AS runtime
COPY --from=assets /build/public/build ./public/build
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 CMD curl -fsS http://127.0.0.1/up || exit 1
ENTRYPOINT ["/var/www/html/entrypoint.sh"]
CMD ["web"]
