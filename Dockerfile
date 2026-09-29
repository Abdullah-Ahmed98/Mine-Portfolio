# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1 - front-end assets
# =============================================================================
# Vite is run in its own stage so Node and the 200MB of node_modules never reach
# the final image. The manifest it writes is what Laravel reads to resolve
# asset() calls, so without this stage the site deploys unstyled.
FROM node:22-alpine AS assets

WORKDIR /build

# The lockfile is copied on its own so npm ci is only re-run when dependencies
# actually change, not on every unrelated edit.
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build


# =============================================================================
# Stage 2 - application
# =============================================================================
# FrankenPHP rather than nginx + php-fpm because it is a single process, and
# because it serves files from public/ directly. That matters here: the portfolio
# is image-heavy, and routing those images through PHP would burn the free
# instance's CPU and re-send them on every page view.
FROM dunglas/frankenphp:1-php8.3

# gd with WebP is not optional: MediaService generates the @480 and @960
# variants on every upload. pdo_pgsql is what Neon speaks.
RUN install-php-extensions \
        gd \
        intl \
        opcache \
        pdo_pgsql \
        pdo_sqlite \
        zip

WORKDIR /app

# The image's own Caddyfile already sets root to public/ and enables php_server,
# which serves assets straight from disk and only falls through to index.php for
# real routes. SERVER_ROOT only overrides the path it serves from.
ENV SERVER_ROOT=/app/public

# Uploads default to 2MB, which quietly truncates larger screenshots. Raised
# alongside post_max_size because that, not upload_max_filesize, is what the
# web server enforces.
RUN printf '%s\n' \
        'upload_max_filesize = 12M' \
        'post_max_size = 16M' \
        'memory_limit = 256M' \
        'opcache.enable = 1' \
        'opcache.validate_timestamps = 0' \
        'opcache.max_accelerated_files = 20000' \
        > /usr/local/etc/php/conf.d/portfolio.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installed before the source so that editing a Blade file does not reinstall
# the entire dependency tree on every deploy.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .
COPY --from=assets /build/public/build ./public/build

# public/storage is what Storage::disk('public')->url() points at. Creating the
# link here means the host needs no shell and no artisan call to serve images.
RUN ln -sfn /app/storage/app/public /app/public/storage

# Only the autoloader and the compiled Blade views are cached at build time.
# Config and route caching is deliberately left to the start command: both bake
# environment variables into the cache, and those are not known until runtime.
RUN composer dump-autoload --optimize --no-dev \
    && php artisan view:cache

RUN chown -R www-data:www-data storage bootstrap/cache

# No CMD is set on purpose. The image's default entrypoint runs
# `frankenphp run --config /etc/frankenphp/Caddyfile`, which is the configuration
# upstream ships and tests. Overriding it here is how a Caddyfile gets silently
# ignored.
#
# php artisan migrate is NOT run here either. It runs in the start command, so a
# failed migration stops the boot loudly instead of shipping a half-built image.
