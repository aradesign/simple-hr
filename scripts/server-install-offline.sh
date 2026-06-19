#!/usr/bin/env bash
# نصب آفلاین روی سرور — فقط apt ایران + بسته محلی (بدون GitHub)
# معمولاً توسط deploy-from-laptop.sh فراخوانی می‌شود
set -euo pipefail

APP_NAME="${APP_NAME:-Simple HR}"
APP_DIR="${APP_DIR:-/var/www/simple-hr}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DB_NAME="${DB_NAME:-simple_hr}"
DB_USER="${DB_USER:-simple_hr}"
NGINX_SITE="${NGINX_SITE:-simple-hr}"
PHP_VERSION="${PHP_VERSION:-8.1}"
USE_IRAN_MIRROR="${USE_IRAN_MIRROR:-true}"

BUNDLE_DIR="${1:-/tmp/simple-hr-bundle}"
APP_DOMAIN="${APP_DOMAIN:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
SSL_EMAIL="${SSL_EMAIL:-}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-password}"
SKIP_SSL="${SKIP_SSL:-false}"

STEP=0; TOTAL=10
step() { STEP=$((STEP+1)); echo ""; echo "[$STEP/$TOTAL] $*"; }
ok() { echo "  ✔ $*"; }
warn() { echo "  ⚠ $*"; }
die() { echo "  ✖ $1" >&2; exit 1; }

[[ "${EUID:-0}" -eq 0 ]] || die "با sudo اجرا کنید."

if [[ -z "$APP_DOMAIN" || -z "$DB_PASSWORD" ]]; then
  echo "=== پیکربندی نصب آفلاین ==="
  read -rp "دامنه [hr.example.com]: " APP_DOMAIN
  APP_DOMAIN="${APP_DOMAIN:-hr.example.com}"
  while [[ -z "$DB_PASSWORD" ]]; do
    read -rsp "رمز دیتابیس: " DB_PASSWORD; echo
  done
  read -rp "ایمیل ادمین [$ADMIN_EMAIL]: " _e; ADMIN_EMAIL="${_e:-$ADMIN_EMAIL}"
  read -rsp "رمز ادمین [$ADMIN_PASSWORD]: " _p; echo; ADMIN_PASSWORD="${_p:-$ADMIN_PASSWORD}"
  [[ "$SKIP_SSL" == true ]] || read -rp "ایمیل SSL (خالی=بدون SSL): " SSL_EMAIL
fi

PROJECT_SRC="${BUNDLE_DIR}/project"
[[ -d "$PROJECT_SRC" ]] || die "پوشه project در بسته یافت نشد: $PROJECT_SRC"

configure_iran_apt() {
  step "تنظیم مخازن apt ایران"
  if [[ "$USE_IRAN_MIRROR" != true ]]; then
    warn "از مخازن پیش‌فرض استفاده می‌شود."
    return
  fi
  cp /etc/apt/sources.list /etc/apt/sources.list.bak."$(date +%s)" 2>/dev/null || true
  cat > /etc/apt/sources.list <<'EOF'
deb http://mirror.iranserver.com/ubuntu jammy main restricted universe multiverse
deb http://mirror.iranserver.com/ubuntu jammy-updates main restricted universe multiverse
deb http://mirror.iranserver.com/ubuntu jammy-security main restricted universe multiverse
EOF
  ok "mirror.iranserver.com"
}

install_system() {
  step "نصب Nginx, MariaDB, PHP ${PHP_VERSION}"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get install -y -qq nginx mariadb-server unzip curl \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl"
  systemctl enable nginx "php${PHP_VERSION}-fpm" mariadb
  ok "بسته‌های سیستمی"
}

setup_swap() {
  step "Swap 2GB"
  if swapon --show 2>/dev/null | grep -q /swapfile; then ok "از قبل فعال"; return; fi
  fallocate -l 2G /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048 status=none
  chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  ok "swap فعال"
}

setup_db() {
  step "MariaDB"
  mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
  mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null || true
  mysql -e "GRANT ALL ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"
  ok "$DB_NAME"
}

setup_user() {
  step "کاربر deploy"
  id "$DEPLOY_USER" &>/dev/null || adduser --disabled-password --gecos "" "$DEPLOY_USER"
  usermod -aG www-data "$DEPLOY_USER" 2>/dev/null || true
  ok "$DEPLOY_USER"
}

deploy_files() {
  step "استقرار فایل‌های پروژه"
  mkdir -p "$APP_DIR"
  if [[ -n "$(ls -A "$APP_DIR" 2>/dev/null || true)" ]]; then
    warn "پاکسازی $APP_DIR"
    find "$APP_DIR" -mindepth 1 -delete
  fi
  rsync -a "$PROJECT_SRC/" "$APP_DIR/"
  chown -R "$DEPLOY_USER:www-data" "$APP_DIR"
  ok "$APP_DIR"
}

set_env() {
  local k="$1" v="$2" f="$APP_DIR/.env"
  if grep -q "^${k}=" "$f" 2>/dev/null; then sed -i "s|^${k}=.*|${k}=${v}|" "$f"
  elif grep -q "^# ${k}=" "$f" 2>/dev/null; then sed -i "s|^# ${k}=.*|${k}=${v}|" "$f"
  else echo "${k}=${v}" >> "$f"; fi
}

configure_app() {
  step "Laravel"
  cd "$APP_DIR"
  [[ -f .env ]] || cp .env.example .env
  chown "$DEPLOY_USER:www-data" .env

  sudo -u "$DEPLOY_USER" -H php artisan key:generate --force
  set_env APP_NAME "\"${APP_NAME}\""
  set_env APP_ENV production
  set_env APP_DEBUG false
  set_env APP_URL "\"https://${APP_DOMAIN}\""
  set_env DB_CONNECTION mysql
  set_env DB_HOST 127.0.0.1
  set_env DB_PORT 3306
  set_env DB_DATABASE "$DB_NAME"
  set_env DB_USERNAME "$DB_USER"
  set_env DB_PASSWORD "$DB_PASSWORD"
  set_env SESSION_DRIVER database
  set_env CACHE_STORE database
  set_env QUEUE_CONNECTION database

  if [[ -f "${BUNDLE_DIR}/tools/composer.phar" ]] && [[ ! -d "$APP_DIR/vendor" ]]; then
    sudo -u "$DEPLOY_USER" -H php "${BUNDLE_DIR}/tools/composer.phar" install --no-dev --optimize-autoloader --no-interaction
  fi

  sudo -u "$DEPLOY_USER" -H php artisan migrate --force
  sudo -u "$DEPLOY_USER" -H php artisan db:seed --force
  sudo -u "$DEPLOY_USER" -H php artisan storage:link 2>/dev/null || true

  sudo -u "$DEPLOY_USER" -H php artisan tinker --execute="
    \$u = App\\Models\\User::query()->where('email', '${ADMIN_EMAIL}')->first()
      ?? App\\Models\\User::query()->where('role', 'super_admin')->first();
    if (\$u) { \$u->update(['email'=>'${ADMIN_EMAIL}','password'=>'${ADMIN_PASSWORD}','hr_access'=>true]); }
    else { App\\Models\\User::query()->create(['name'=>'مدیر','email'=>'${ADMIN_EMAIL}','password'=>'${ADMIN_PASSWORD}','role'=>App\\Domain\\Enums\\UserRole::SuperAdmin,'hr_access'=>true,'email_verified_at'=>now()]); }
  " 2>/dev/null || warn "tinker admin — دستی بررسی کنید"

  sudo -u "$DEPLOY_USER" -H php artisan config:cache
  sudo -u "$DEPLOY_USER" -H php artisan route:cache
  sudo -u "$DEPLOY_USER" -H php artisan view:cache
  chown -R "$DEPLOY_USER:www-data" storage bootstrap/cache
  chmod -R ug+rwx storage bootstrap/cache
  ok "اپلیکیشن"
}

configure_nginx() {
  step "Nginx"
  local sock="/var/run/php/php${PHP_VERSION}-fpm.sock"
  cat > "/etc/nginx/sites-available/${NGINX_SITE}" <<NGX
server {
    listen 80;
    server_name ${APP_DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    charset utf-8;
    client_max_body_size 32M;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \\.php\$ {
        fastcgi_pass unix:${sock};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\\.(?!well-known).* { deny all; }
}
NGX
  ln -sf "/etc/nginx/sites-available/${NGINX_SITE}" /etc/nginx/sites-enabled/
  rm -f /etc/nginx/sites-enabled/default
  nginx -t && systemctl reload nginx
  ok "http://${APP_DOMAIN}"
}

maybe_ssl() {
  step "SSL"
  if [[ -z "$SSL_EMAIL" || "$SKIP_SSL" == true ]]; then
    set_env APP_URL "\"http://${APP_DOMAIN}\""
    sudo -u "$DEPLOY_USER" -H php artisan config:cache
    warn "بدون SSL"
    return
  fi
  apt-get install -y -qq certbot python3-certbot-nginx
  certbot --nginx -d "$APP_DOMAIN" --non-interactive --agree-tos -m "$SSL_EMAIL" --redirect || warn "SSL ناموفق"
}

echo "======================================"
echo " Simple HR — نصب آفلاین"
echo " بسته: $BUNDLE_DIR"
echo "======================================"

configure_iran_apt
setup_swap
install_system
setup_db
setup_user
deploy_files
configure_app
configure_nginx
maybe_ssl

echo ""
echo "══════════════════════════════════════"
echo " نصب تمام شد"
echo " http://${APP_DOMAIN}/admin/login"
echo " ${ADMIN_EMAIL} / ${ADMIN_PASSWORD}"
echo "══════════════════════════════════════"
