#!/usr/bin/env bash
# فقط نصب اپلیکیشن — وقتی PHP/Nginx/MariaDB از قبل نصب است
# اجرا: bash scripts/manual-deploy.sh
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/leili-hr}"
REPO_URL="https://github.com/aradesign/hr-manager-v2.git"

echo "=== نصب دستی اپلیکیشن ==="

command -v php >/dev/null || { echo "PHP نصب نیست. اول راهنمای VPS را بخوانید."; exit 1; }
command -v composer >/dev/null || { echo "Composer نصب نیست."; exit 1; }
command -v git >/dev/null || { echo "Git نصب نیست: sudo apt install -y git"; exit 1; }

mkdir -p "$APP_DIR"
cd "$APP_DIR"

if [[ -d .git ]]; then
  git pull
else
  if [[ -n "$(ls -A 2>/dev/null || true)" ]]; then
    echo "خطا: پوشه $APP_DIR خالی نیست."
    echo "یا پاکش کنید: rm -rf ${APP_DIR}/*"
    echo "یا جای دیگر: APP_DIR=/var/www/leili-hr bash scripts/manual-deploy.sh"
    exit 1
  fi
  git clone "$REPO_URL" .
fi

[[ -f .env ]] || cp .env.example .env

echo "فایل .env را ویرایش کنید (DB و APP_URL)، سپس Enter..."
read -r _

php artisan key:generate --force
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "تمام. Nginx را به public/ این پوشه اشاره دهید."
