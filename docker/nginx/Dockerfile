# WordPress Playground - Nginx Dockerfile
# For production builds only (local dev uses bind mounts)

# Stage 1: Install composer dependencies (for WordPress static assets)
FROM composer:2 AS composer-build
WORKDIR /app
COPY wordpress-core/composer.json wordpress-core/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: Final nginx image
FROM nginx:stable-alpine

ENV DATA_DIR=/var/www/src

# Remove default nginx config
RUN rm -f /etc/nginx/conf.d/default.conf

# Create required directories
RUN mkdir -p ${DATA_DIR} \
    && mkdir -p /etc/nginx/templates

# Copy nginx configuration (to conf.d which is included by default)
COPY nginx/nginx-site.conf /etc/nginx/conf.d/default.conf
COPY nginx/includes/ /etc/nginx/templates/

# Copy www/ contents (index.php, wp-config.php, robots.txt, status.php) to webroot
COPY www/ ${DATA_DIR}/

# Copy WordPress core from composer stage (includes default wp-content with themes/plugins)
COPY --from=composer-build /app/wordpress/ ${DATA_DIR}/wordpress/

# Copy mu-plugins into WordPress wp-content
COPY wordpress-core/src/mu-plugins/ ${DATA_DIR}/wordpress/wp-content/mu-plugins/

# Add custom themes/plugins by copying into wp-content, e.g.:
# COPY wordpress-core/src/themes/my-theme/ ${DATA_DIR}/wordpress/wp-content/themes/my-theme/
# COPY wordpress-core/src/plugins/my-plugin/ ${DATA_DIR}/wordpress/wp-content/plugins/my-plugin/

WORKDIR ${DATA_DIR}

EXPOSE 80
