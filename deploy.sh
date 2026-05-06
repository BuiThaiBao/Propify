#!/bin/bash
APP_DIR="/var/www/Propify"
LOG_FILE="/var/www/Propify/deploy.log"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "1. Bắt đầu tải mã nguồn mới nhất từ GitHub..."
cd $APP_DIR
git fetch origin main
git pull origin main

echo "2. Backend Laravel (API)..."
cd $APP_DIR/PropifyBackend
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan migrate --force

echo "3. Fix permissions trước khi build Frontend..."
cd $APP_DIR/PropifyFrontend
rm -rf dist
chown -R $(whoami):$(whoami) node_modules 2>/dev/null || true

echo "4. Frontend Vue (Vite Build)..."
npm ci --include=dev
./node_modules/.bin/vite build

echo "5. Copy giao diện Frontend ra ngoài Public..."
rm -rf $APP_DIR/PropifyBackend/public/assets
cp -r dist/* $APP_DIR/PropifyBackend/public/

echo "6. Fix permissions trước khi build Admin..."
cd $APP_DIR/PropifyAdmin
rm -rf dist
chown -R $(whoami):$(whoami) node_modules 2>/dev/null || true

echo "7. Admin Vue (Vite Build)..."
npm ci --include=dev
./node_modules/.bin/vite build

echo "8. Copy Admin vào thư mục /public/admin/..."
rm -rf $APP_DIR/PropifyBackend/public/admin
mkdir -p $APP_DIR/PropifyBackend/public/admin
cp -r dist/* $APP_DIR/PropifyBackend/public/admin/

echo "9. Fix ownership cho web server..."
chown -R www-data:www-data $APP_DIR/PropifyBackend/public/
chown -R www-data:www-data $APP_DIR/PropifyBackend/storage/
chmod -R 775 $APP_DIR/PropifyBackend/storage/

echo "10. Tối ưu Laravel..."
cd $APP_DIR/PropifyBackend
php artisan optimize

echo "11. Restart Reverb WebSocket server..."
sudo /usr/bin/supervisorctl restart reverb

echo "12. Restart Queue Workers..."
sudo /usr/bin/supervisorctl restart propify-worker:*

echo "DEPLOY HOÀN TẤT TUYỆT ĐỐI !!!"
