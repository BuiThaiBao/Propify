FROM php:8.2.12-fpm

# Cập nhật package và cài đặt các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
    libzip-dev \
    default-mysql-client \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd intl sockets exif pdo pdo_mysql zip

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy entrypoint script để tự chạy composer install khi container khởi động
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Cấu hình tăng giới hạn upload file cho PHP
RUN echo "upload_max_filesize = 35M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size = 35M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
