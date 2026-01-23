# WordPress Playground - PHP-FPM Dockerfile
# For production builds only (local dev uses bind mounts)

# Stage 1: Install composer dependencies
FROM composer:2 AS composer-build
WORKDIR /app
COPY wordpress-core/composer.json wordpress-core/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: PHP-FPM image
FROM php:8.3-fpm-alpine AS base

# Install PHP extensions required by WordPress
RUN apk add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        intl \
        opcache

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

ENV DATA_DIR=/var/www/src \
    UPLOADS_DIR=/var/www/src/wordpress/wp-content/uploads

# Create the WordPress uploads directory
RUN mkdir -p ${UPLOADS_DIR} \
    && chown -R www-data:www-data ${UPLOADS_DIR} \
    && chmod -R 775 ${UPLOADS_DIR}

# Stage 3: Final image
FROM base AS final

# Copy www/ contents (index.php, wp-config.php, robots.txt, status.php) to webroot
COPY --chown=www-data:www-data www/ ${DATA_DIR}/

# Copy WordPress core and vendor from composer stage
COPY --from=composer-build --chown=www-data:www-data /app/wordpress/ ${DATA_DIR}/wordpress/
COPY --from=composer-build --chown=www-data:www-data /app/vendor/ ${DATA_DIR}/vendor/

# Copy mu-plugins into WordPress wp-content
COPY --chown=www-data:www-data wordpress-core/src/mu-plugins/ ${DATA_DIR}/wordpress/wp-content/mu-plugins/

# Add custom themes/plugins by copying into wp-content, e.g.:
# COPY --chown=www-data:www-data wordpress-core/src/themes/my-theme/ ${DATA_DIR}/wordpress/wp-content/themes/my-theme/
# COPY --chown=www-data:www-data wordpress-core/src/plugins/my-plugin/ ${DATA_DIR}/wordpress/wp-content/plugins/my-plugin/

WORKDIR ${DATA_DIR}
