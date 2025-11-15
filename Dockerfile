# Base stage
FROM dunglas/frankenphp:1-php8.4 AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    zip \
    opcache \
    redis \
    pcntl \
    sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Development stage
FROM base AS dev

# Copy PHP development configuration (disables OpCache delay)
COPY docker/php/dev.ini /usr/local/etc/php/conf.d/dev.ini

# Install XDebug for development
RUN install-php-extensions xdebug

# Configure XDebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log_level=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Node.js for Vite hot reload
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy composer files and install dependencies (with dev dependencies)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Set permissions
RUN mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

EXPOSE 80 443

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80", "--watch"]

# Production stage
FROM base AS production

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies only
RUN composer install --no-dev --no-scripts --no-autoload --prefer-dist --optimize-autoloader

# Copy application files
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Install Node.js and build assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install npm dependencies and build
COPY package*.json ./
RUN npm ci --omit=dev
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm run build && npm cache clean --force

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Optimize Laravel for production
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

EXPOSE 80 443

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80"]
