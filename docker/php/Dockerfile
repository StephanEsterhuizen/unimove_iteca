# PHP 8.1 + Apache — matches InfinityFree's PHP version family.
FROM php:8.1-apache

# Install PDO + MySQL driver, GD (for image work), and mbstring
RUN apt-get update && apt-get install -y \
        libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mbstring gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Allow .htaccess overrides for /uploads/.htaccess to work
RUN printf '%s\n' \
        '<Directory /var/www/html>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
    > /etc/apache2/conf-available/unimove.conf \
    && a2enconf unimove

# Make uploads writable
RUN mkdir -p /var/www/html/uploads/listings \
    && chown -R www-data:www-data /var/www/html/uploads

# Raise PHP upload limits so a few decent-quality phone photos fit,
# AND turn off browser-visible error output so a hit limit gives us a
# clean redirect via PHP code instead of a wall of <b>Warning:</b> text.
RUN printf '%s\n' \
    'upload_max_filesize = 20M' \
    'post_max_size       = 120M' \
    'max_file_uploads    = 10' \
    'memory_limit        = 256M' \
    'display_errors      = Off' \
    'display_startup_errors = Off' \
    'log_errors          = On' \
    'error_log           = /var/log/php_errors.log' \
    > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
