# ============================================================
# CETSADP v9.3.6 — LMS Dockerfile
# Etapa 4.3: Entrypoint robusto + phpredis + healthcheck
#
# CORREÇÕES:
#   D05 — Healthcheck adicionado
#   I04 — Extensão phpredis instalada (necessária para QUEUE_CONNECTION=redis)
#   N03 — entrypoint.sh substitui CMD inline
#   N04 — pg_isready instalado para health check do PostgreSQL
# ============================================================
FROM php:8.4-apache

# 1. Instalar bibliotecas do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    gnupg \
    postgresql-client \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Instalar extensões PHP (CORREÇÃO I04: adicionado redis)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    zip \
    pdo \
    pdo_pgsql \
    bcmath \
    mbstring \
    exif \
    pcntl

# v9.3.4: Instalar phpredis via PECL (necessário para queue/cache/session Redis)
RUN pecl install redis && docker-php-ext-enable redis

# 3. Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

RUN a2enmod rewrite headers

# 4. Copiar arquivos
COPY . /var/www/html
WORKDIR /var/www/html

# 5. Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 6. Rodar Instalações
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# 7. Permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Tornar entrypoint executável
RUN chmod +x /var/www/html/entrypoint.sh

# 9. Healthcheck (CORREÇÃO D05)
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# 10. Inicialização via entrypoint.sh (CORREÇÃO N03)
ENTRYPOINT ["/var/www/html/entrypoint.sh"]
