# syntax=docker/dockerfile:labs

# Stage 1: Install back-end dependencies
FROM dunglas/frankenphp:php8.5 AS back-deps
WORKDIR /app

# PHP and Laravel configuration
RUN install-php-extensions @composer zip pgsql pdo_pgsql intl && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV SERVER_NAME=:80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1 \
    INERTIA_SSR_ENABLED=true

# Install PHP dependencies with cache mount
COPY --link composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache/composer \
    composer install --no-dev --no-interaction --no-progress --no-autoloader --no-scripts

# Copy application code (optimized order for cache)
COPY --link app app
COPY --link bootstrap bootstrap
COPY --link config config
COPY --link database database
COPY --link public public
COPY --link resources resources
COPY --link routes routes
COPY --link storage storage
COPY --link artisan ./

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Set proper permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Configure entrypoint
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]
CMD ["--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]

# Stage 2: Build front-end assets
FROM node:lts-alpine AS front
WORKDIR /usr/src/app

# Install corepack and enable yarn
RUN npm -g install corepack --force && corepack enable

# Install Node dependencies with cache mount
COPY --link package.json .yarnrc.yml yarn.lock .yarn ./
COPY --link vite-plugin-inertia-ssg.ts vite.config.ts vite.config.exports.ts tsconfig.json ./
COPY --link resources resources

# Install and build in a single RUN to share the Yarn cache mount
RUN --mount=type=cache,target=/root/.yarn yarn install --immutable && yarn build

# Final stage: Combine back-end and front-end
FROM back-deps AS back
COPY --link --from=front /usr/src/app/public/build ./public/build
COPY --link --from=front /usr/src/app/public/build-exports ./public/build-exports

ARG UPDATED_AT
ENV UPDATED_AT=${UPDATED_AT}
