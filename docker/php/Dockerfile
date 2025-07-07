FROM php:8.3-fpm

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        zip \
        unzip \
        git \
        curl \
        libpq-dev \
        libicu-dev \
        libmcrypt-dev \
        libssl-dev \
        libmemcached-dev \
        libxslt1-dev \
        libwebp-dev \
        libjpeg-dev \
        libxpm-dev \
        libvpx-dev \
        libsqlite3-dev \
        libcurl4-openssl-dev \
        libreadline-dev \
        libtidy-dev \
        libgmp-dev \
        libldap2-dev \
        libpspell-dev \
        libsnmp-dev \
        librecode-dev \
        libxml2-dev \
        libbz2-dev \
        libevent-dev \
        libffi-dev \
        libkrb5-dev \
        libmagickwand-dev \
        libssh2-1-dev \
        libxslt1-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        opcache \
        intl \
        xml \
        mbstring \
        bcmath \
        pcntl \
        exif \
        soap \
        sockets \
        calendar \
        sysvmsg \
        sysvsem \
        sysvshm \
        tidy \
        gmp \
        xsl \
        pdo_sqlite \
        ldap \
        bz2 \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set recommended PHP.ini settings
COPY ./php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

