#!/usr/bin/env bash
# نصب خودکار Leili HR روی Ubuntu 22.04
# اجرا: sudo bash scripts/vps-install.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/leili-hr}"
REPO_URL="${REPO_URL:-https://github.com/aradesign/hr-manager-v2.git}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"

if [[ "${EUID:-0}" -ne 0 ]]; then
  echo "این اسکریپت را با sudo اجرا کنید:"
  echo "  sudo bash scripts/vps-install.sh"
  exit 1
fi

echo "=== Leili HR — نصب VPS ==="

read -rp "دامنه سایت (مثلاً hr.example.com): " APP_DOMAIN
read -rp "رمز دیتابیس MariaDB برای کاربر leili: " -s DB_PASSWORD
echo
read -rp "ایمیل SSL (برای Let's Encrypt، Enter برای رد): " SSL_EMAIL

export DEBIAN_FRONTEND=noninteractive

echo "→ به‌روزرسانی سیستم..."
apt-get update -qq
apt-get upgrade -y -qq

echo "→ نصب swap (۲GB)..."
if ! swapon --show | grep -q /swapfile; then
  fallocate -l 2G /swapfile || dd if=/dev/zero of=/swapfile bs=1M count=2048
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "→ نصب بسته‌ها..."
apt-get install -y -qq nginx mariadb-server git curl unzip software-properties-common \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl 2>/dev/null || {
  add-apt-repository ppa:ondrej/php -y
  apt-get update -qq
  apt-get install -y -qq nginx mariadb-server git curl unzip software-properties-common \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
}

if ! command -v composer >/dev/null 2>&1; then
  echo "→ نصب Composer..."
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! id "$DEPLOY_USER" &>/dev/null; then
  echo "→ ساخت کاربر $DEPLOY_USER ..."
  adduser --disabled-password --gecos "" "$DEPLOY_USER"
  usermod -aG www-data "$DEPLOY_USER"
fi

echo "→ دیتابیس..."
mysql -e "CREATE DATABASE IF NOT EXISTS leili_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'leili'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON leili_hr.* TO 'leili'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "→ دریافت پروژه..."
mkdir -p "$APP_DIR"
chown "$DEPLOY_USER:www-data" "$APP_DIR"

if [[ -d "$APP_DIR/.git" ]]; then
  sudo -u "$DEPLOY_USER" git -C "$APP_DIR" pull
else
  if [[ -n "$(ls -A "$APP_DIR" 2>/dev/null || true)" ]]; then
    echo "پوشه $APP_DIR خالی نیست. محتویات را پاک می‌کنم..."
    rm -rf "${APP_DIR:?}"/*
  fi
  sudo -u "$DEPLOY_USER" git clone "$REPO_URL" "$APP_DIR"
fi

echo "→ تنظیم .env ..."
cd "$APP_DIR"
if [[ ! -f .env ]]; then
  cp .env.example .env
fi

sudo -u "$DEPLOY_USER" php artisan key:generate --force

sed -i "s|^APP_NAME=.*|APP_NAME=\"Leili HR\"|" .env
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=https://${APP_DOMAIN}|" .env
sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
sed -i "s|^# DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|^# DB_PORT=.*|DB_PORT=3306|" .env
sed -i "s|^# DB_DATABASE=.*|DB_DATABASE=leili_hr|" .env
sed -i "s|^# DB_USERNAME=.*|DB_USERNAME=leili|" .env
sed -i "s|^# DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
grep -q '^DB_HOST=' .env || echo "DB_HOST=127.0.0.1" >> .env
grep -q '^DB_DATABASE=' .env || echo "DB_DATABASE=leili_hr" >> .env
grep -q '^DB_USERNAME=' .env || echo "DB_USERNAME=leili" >> .env
grep -q '^DB_PASSWORD=' .env || echo "DB_PASSWORD=${DB_PASSWORD}" >> .env

echo "→ Composer و Laravel..."
sudo -u "$DEPLOY_USER" composer install --no-dev --optimize-autoloader --no-interaction
sudo -u "$DEPLOY_USER" php artisan migrate --force
sudo -u "$DEPLOY_USER" php artisan db:seed --force
sudo -u "$DEPLOY_USER" php artisan storage:link || true
sudo -u "$DEPLOY_USER" php artisan config:cache
sudo -u "$DEPLOY_USER" php artisan route:cache
sudo -u "$DEPLOY_USER" php artisan view:cache

chown -R "$DEPLOY_USER:www-data" storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "→ Nginx..."
cat > /etc/nginx/sites-available/leili-hr <<NGINX
server {
    listen 80;
    server_name ${APP_DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php\$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/leili-hr /etc/nginx/sites-enabled/leili-hr
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
systemctl enable nginx php8.3-fpm mariadb

if [[ -n "$SSL_EMAIL" ]]; then
  echo "→ SSL..."
  apt-get install -y -qq certbot python3-certbot-nginx
  certbot --nginx -d "$APP_DOMAIN" --non-interactive --agree-tos -m "$SSL_EMAIL" || true
fi

echo ""
echo "============================================"
echo "  نصب تمام شد!"
echo "  آدرس: https://${APP_DOMAIN}/admin/login"
echo "  ورود: admin@example.com / password"
echo "  فوراً رمز را عوض کنید."
echo "============================================"
