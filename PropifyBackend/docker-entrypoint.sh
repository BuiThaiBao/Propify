#!/bin/sh
# Chạy composer install mỗi khi container khởi động
composer install --prefer-dist --no-interaction

# Tiếp tục chạy lệnh chính (ở đây là php-fpm)
exec "$@"
