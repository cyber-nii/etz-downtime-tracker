# ─── Stage 1: Composer dependencies ─────────────────────────────────────────
FROM composer:2.7 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./

# Install production dependencies only (no dev).
# --ignore-platform-reqs: the composer image lacks ext-gd/ext-zip; they
# are compiled into Stage 2 (php:8.2-apache), so this is safe to skip here.
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# ─── Stage 2: Production image ───────────────────────────────────────────────
FROM php:8.2-apache

# System dependencies required by PHPSpreadsheet and TCPDF
# libonig-dev  → required by mbstring (oniguruma regex library)
# libssl-dev   → required for openssl extension (HTTPS calls to external auth API)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libgd-dev \
    libonig-dev \
    libssl-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        gd \
        xml \
        mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite and headers module
RUN a2enmod rewrite headers

# Document root is public/ — all PHP pages live there
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Custom Apache vhost — document root at /var/www/html
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# PHP production ini settings
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Copy application source (excluding .dockerignore'd paths)
COPY --chown=www-data:www-data . /var/www/html

# Copy compiled vendor from stage 1
COPY --chown=www-data:www-data --from=composer-build /app/vendor /var/www/html/vendor

# Copy and register entrypoint script (sets volume permissions at runtime)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Pre-create directories (entrypoint will re-chown at runtime after volume mount)
RUN mkdir -p /var/www/html/public/uploads /var/www/html/config/logs

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -f http://localhost/login.php || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
