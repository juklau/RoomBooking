FROM php:8.3-fpm

# -------------------------------------------------------
# Extensions système
# -------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        xml \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# -------------------------------------------------------
# Composer
# -------------------------------------------------------
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# -------------------------------------------------------
# Symfony CLI (utile pour symfony check:requirements, etc.)
# -------------------------------------------------------
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

# -------------------------------------------------------
# Droits
# -------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]