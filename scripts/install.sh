#!/usr/bin/env bash
#
# Simple HR — نصب کامل روی Ubuntu 22.04+
# اجرای تک‌خطی:
#   curl -fsSL https://raw.githubusercontent.com/aradesign/simple-hr/main/scripts/install.sh | sudo bash
#
# اجرای محلی:
#   sudo bash scripts/install.sh
#
set -euo pipefail

# ─── تنظیمات پیش‌فرض ───────────────────────────────────────────────
APP_NAME="${APP_NAME:-Simple HR}"
APP_DIR="${APP_DIR:-/var/www/simple-hr}"
REPO_URL="${REPO_URL:-https://github.com/aradesign/simple-hr.git}"
REPO_BRANCH="${REPO_BRANCH:-main}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DB_NAME="${DB_NAME:-simple_hr}"
DB_USER="${DB_USER:-simple_hr}"
NGINX_SITE="${NGINX_SITE:-simple-hr}"
PHP_VERSION="${PHP_VERSION:-8.3}"
SWAP_SIZE="${SWAP_SIZE:-2G}"

APP_DOMAIN=""
DB_PASSWORD=""
SSL_EMAIL=""
ADMIN_EMAIL=""
ADMIN_PASSWORD=""
SKIP_SSL=false
SKIP_FIREWALL=false
SKIP_WORKER=false
NON_INTERACTIVE=false

# ─── رنگ‌ها ────────────────────────────────────────────────────────
if [[ -t 1 ]]; then
  BOLD='\033[1m'; DIM='\033[2m'
  GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; CYAN='\033[0;36m'; RESET='\033[0m'
else
  BOLD=''; DIM=''; GREEN=''; YELLOW=''; RED=''; CYAN=''; RESET=''
fi

STEP=0
TOTAL_STEPS=12

log()  { echo -e "${DIM}[$(date +%H:%M:%S)]${RESET} $*"; }
ok()   { echo -e "${GREEN}✔${RESET} $*"; }
warn() { echo -e "${YELLOW}⚠${RESET} $*"; }
err()  { echo -e "${RED}✖${RESET} $*" >&2; }
step() { STEP=$((STEP + 1)); echo -e "\n${BOLD}${CYAN}[$STEP/$TOTAL_STEPS]${RESET} ${BOLD}$*${RESET}"; }

die() { err "$1"; exit 1; }

usage() {
  cat <<'EOF'
Simple HR Installer

استفاده:
  sudo bash install.sh [گزینه‌ها]

گزینه‌ها:
  --domain=hr.example.com     دامنه سایت
  --db-password=SECRET        رمز دیتابیس
  --admin-email=EMAIL         ایمیل ادمین (پیش‌فرض: admin@example.com)
  --admin-password=PASS       رمز ادمین (پیش‌فرض: password)
  --ssl-email=you@mail.com    ایمیل SSL (Let's Encrypt)
  --app-dir=/var/www/simple-hr
  --skip-ssl                  بدون SSL
  --skip-firewall             بدون UFW
  --skip-worker               بدون queue worker
  --yes                       بدون سؤال تأیید
  -h, --help                  راهنما

نصب تک‌خطی:
  curl -fsSL https://raw.githubusercontent.com/aradesign/simple-hr/main/scripts/install.sh | sudo bash
EOF
}

parse_args() {
  for arg in "$@"; do
    case "$arg" in
      --domain=*)        APP_DOMAIN="${arg#*=}"; NON_INTERACTIVE=true ;;
      --db-password=*)   DB_PASSWORD="${arg#*=}"; NON_INTERACTIVE=true ;;
      --admin-email=*)   ADMIN_EMAIL="${arg#*=}" ;;
      --admin-password=*) ADMIN_PASSWORD="${arg#*=}" ;;
      --ssl-email=*)     SSL_EMAIL="${arg#*=}" ;;
      --app-dir=*)       APP_DIR="${arg#*=}" ;;
      --skip-ssl)        SKIP_SSL=true ;;
      --skip-firewall)   SKIP_FIREWALL=true ;;
      --skip-worker)     SKIP_WORKER=true ;;
      --yes|-y)          NON_INTERACTIVE=true ;;
      -h|--help)         usage; exit 0 ;;
      *) die "گزینه ناشناخته: $arg (install.sh --help)" ;;
    esac
  done
}

banner() {
  clear 2>/dev/null || true
  echo -e "${BOLD}${CYAN}"
  cat <<'BANNER'
   ____  _                 _         _    _  ____  
  / ___|(_)_ __ ___  _ __ | | ___   | |  | |/ ___| 
  \___ \| | '_ ` _ \| '_ \| |/ _ \  | |__| | |     
   ___) | | | | | | | |_) | |  __/  |  __  | |___  
  |____/|_|_| |_| |_| .__/|_|\___|  |_|  |_|\____| 
                    |_|  — VPS Installer
BANNER
  echo -e "${RESET}"
  echo -e "مخزن: ${REPO_URL}"
  echo -e "مسیر نصب: ${APP_DIR}"
  echo ""
}

prompt_defaults() {
  ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
  ADMIN_PASSWORD="${ADMIN_PASSWORD:-password}"

  if [[ "$NON_INTERACTIVE" == true ]]; then
    [[ -n "$APP_DOMAIN" ]]  || die "در حالت غیرتعاملی --domain= الزامی است."
    [[ -n "$DB_PASSWORD" ]] || die "در حالت غیرتعاملی --db-password= الزامی است."
    return
  fi

  echo -e "${BOLD}پیکربندی نصب${RESET} (Enter = پیش‌فرض)"
  echo ""

  read -rp "دامنه سایت [hr.example.com]: " input
  APP_DOMAIN="${input:-hr.example.com}"

  while [[ -z "$DB_PASSWORD" ]]; do
    read -rsp "رمز دیتابیس MariaDB: " DB_PASSWORD
    echo ""
    [[ -n "$DB_PASSWORD" ]] || warn "رمز دیتابیس الزامی است."
  done

  read -rp "ایمیل ادمین [$ADMIN_EMAIL]: " input
  ADMIN_EMAIL="${input:-$ADMIN_EMAIL}"

  read -rsp "رمز ادمین [$ADMIN_PASSWORD]: " input
  echo ""
  ADMIN_PASSWORD="${input:-$ADMIN_PASSWORD}"

  if [[ "$SKIP_SSL" != true ]]; then
    read -rp "ایمیل SSL (Let's Encrypt، خالی = بدون SSL): " SSL_EMAIL
  fi

  echo ""
  read -rp "ادامه نصب؟ [Y/n]: " confirm
  [[ "${confirm:-Y}" =~ ^[Yy]$ ]] || die "نصب لغو شد."
}

require_root() {
  [[ "${EUID:-0}" -eq 0 ]] || die "این اسکریپت را با sudo اجرا کنید."
}

require_os() {
  [[ -f /etc/os-release ]] || die "سیستم‌عامل شناسایی نشد."
  # shellcheck source=/dev/null
  source /etc/os-release
  [[ "$ID" == "ubuntu" || "$ID" == "debian" ]] || warn "تست شده روی Ubuntu 22.04 — ممکن است روی $PRETTY_NAME نیاز به تنظیم دستی باشد."
}

run_as_deploy() {
  sudo -u "$DEPLOY_USER" -H bash -c "cd '$APP_DIR' && $*"
}

set_env() {
  local key="$1" value="$2" file="$APP_DIR/.env"
  if grep -q "^${key}=" "$file" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$file"
  elif grep -q "^# ${key}=" "$file" 2>/dev/null; then
    sed -i "s|^# ${key}=.*|${key}=${value}|" "$file"
  else
    echo "${key}=${value}" >> "$file"
  fi
}

# ─── مراحل نصب ─────────────────────────────────────────────────────

install_swap() {
  step "فعال‌سازی Swap (${SWAP_SIZE})"
  if swapon --show 2>/dev/null | grep -q /swapfile; then
    ok "Swap از قبل فعال است."
    return
  fi
  fallocate -l "$SWAP_SIZE" /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048 status=none
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  ok "Swap فعال شد."
}

install_packages() {
  step "نصب Nginx، MariaDB، PHP ${PHP_VERSION}، Git"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get upgrade -y -qq

  local pkgs=(
    nginx mariadb-server git curl unzip software-properties-common
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql"
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl"
    "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-bcmath"
    "php${PHP_VERSION}-intl"
  )

  if ! apt-get install -y -qq "${pkgs[@]}" 2>/dev/null; then
    log "PPA ondrej/php اضافه می‌شود..."
    add-apt-repository ppa:ondrej/php -y
    apt-get update -qq
    apt-get install -y -qq "${pkgs[@]}"
  fi

  systemctl enable nginx "php${PHP_VERSION}-fpm" mariadb
  ok "بسته‌های سیستمی نصب شدند."
}

install_composer() {
  step "نصب Composer"
  if command -v composer >/dev/null 2>&1; then
    ok "Composer از قبل نصب است: $(composer --version 2>/dev/null | head -1)"
    return
  fi
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  ok "Composer نصب شد."
}

setup_deploy_user() {
  step "ساخت کاربر deploy"
  if id "$DEPLOY_USER" &>/dev/null; then
    ok "کاربر $DEPLOY_USER وجود دارد."
  else
    adduser --disabled-password --gecos "" "$DEPLOY_USER"
    ok "کاربر $DEPLOY_USER ساخته شد."
  fi
  usermod -aG www-data "$DEPLOY_USER" 2>/dev/null || true
}

setup_database() {
  step "پیکربندی MariaDB"
  mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
  mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null || true
  mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
  mysql -e "FLUSH PRIVILEGES;"
  ok "دیتابیس ${DB_NAME} آماده است."
}

clone_project() {
  step "دریافت پروژه از GitHub"
  mkdir -p "$APP_DIR"
  chown "$DEPLOY_USER:www-data" "$APP_DIR"

  if [[ -d "$APP_DIR/.git" ]]; then
    log "به‌روزرسانی مخزن موجود..."
    run_as_deploy "git fetch origin && git checkout '$REPO_BRANCH' && git pull origin '$REPO_BRANCH'"
  else
    if [[ -n "$(ls -A "$APP_DIR" 2>/dev/null || true)" ]]; then
      warn "پوشه $APP_DIR خالی نیست — پاکسازی..."
      find "$APP_DIR" -mindepth 1 -delete
    fi
    run_as_deploy "git clone --branch '$REPO_BRANCH' --depth 1 '$REPO_URL' '$APP_DIR'"
  fi
  ok "کد پروژه در $APP_DIR"
}

configure_laravel() {
  step "پیکربندی Laravel (.env)"
  cd "$APP_DIR"

  [[ -f .env ]] || cp .env.example .env
  chown "$DEPLOY_USER:www-data" .env

  run_as_deploy "php artisan key:generate --force"

  set_env "APP_NAME" "\"${APP_NAME}\""
  set_env "APP_ENV" "production"
  set_env "APP_DEBUG" "false"
  set_env "APP_URL" "\"https://${APP_DOMAIN}\""
  set_env "DB_CONNECTION" "mysql"
  set_env "DB_HOST" "127.0.0.1"
  set_env "DB_PORT" "3306"
  set_env "DB_DATABASE" "$DB_NAME"
  set_env "DB_USERNAME" "$DB_USER"
  set_env "DB_PASSWORD" "$DB_PASSWORD"
  set_env "SESSION_DRIVER" "database"
  set_env "CACHE_STORE" "database"
  set_env "QUEUE_CONNECTION" "database"
  set_env "LOG_LEVEL" "error"

  ok ".env تنظیم شد."
}

install_app() {
  step "نصب وابستگی‌ها و راه‌اندازی دیتابیس"
  run_as_deploy "composer install --no-dev --optimize-autoloader --no-interaction"
  run_as_deploy "php artisan migrate --force"
  run_as_deploy "php artisan db:seed --force"
  run_as_deploy "php artisan storage:link" 2>/dev/null || true

  # به‌روزرسانی یا ساخت ادمین
  run_as_deploy "php artisan tinker --execute=\"
    \\\$u = App\\\\Models\\\\User::query()->where('email', '${ADMIN_EMAIL}')->first()
      ?? App\\\\Models\\\\User::query()->where('role', 'super_admin')->first();
    if (\\\$u) {
      \\\$u->update(['email' => '${ADMIN_EMAIL}', 'password' => '${ADMIN_PASSWORD}', 'hr_access' => true]);
      echo 'admin-updated';
    } else {
      App\\\\Models\\\\User::query()->create([
        'name' => 'مدیر سیستم',
        'email' => '${ADMIN_EMAIL}',
        'password' => '${ADMIN_PASSWORD}',
        'role' => App\\\\Domain\\\\Enums\\\\UserRole::SuperAdmin,
        'hr_access' => true,
        'email_verified_at' => now(),
      ]);
      echo 'admin-created';
    }
  \""

  run_as_deploy "php artisan config:cache"
  run_as_deploy "php artisan route:cache"
  run_as_deploy "php artisan view:cache"

  chown -R "$DEPLOY_USER:www-data" storage bootstrap/cache
  chmod -R ug+rwx storage bootstrap/cache
  ok "اپلیکیشن آماده اجراست."
}

configure_nginx() {
  step "پیکربندی Nginx"
  local php_sock="/var/run/php/php${PHP_VERSION}-fpm.sock"

  cat > "/etc/nginx/sites-available/${NGINX_SITE}" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${APP_DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    index index.php;
    charset utf-8;
    client_max_body_size 32M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:${php_sock};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

  ln -sf "/etc/nginx/sites-available/${NGINX_SITE}" "/etc/nginx/sites-enabled/${NGINX_SITE}"
  rm -f /etc/nginx/sites-enabled/default
  nginx -t
  systemctl reload nginx
  ok "Nginx برای ${APP_DOMAIN} فعال شد."
}

configure_ssl() {
  step "SSL (Let's Encrypt)"
  if [[ "$SKIP_SSL" == true || -z "$SSL_EMAIL" ]]; then
    warn "SSL رد شد — فعلاً فقط HTTP."
    set_env "APP_URL" "\"http://${APP_DOMAIN}\""
    run_as_deploy "php artisan config:cache"
    return
  fi
  apt-get install -y -qq certbot python3-certbot-nginx
  if certbot --nginx -d "$APP_DOMAIN" --non-interactive --agree-tos -m "$SSL_EMAIL" --redirect; then
    ok "SSL برای ${APP_DOMAIN} فعال شد."
  else
    warn "SSL ناموفق — سایت روی HTTP در دسترس است."
    set_env "APP_URL" "\"http://${APP_DOMAIN}\""
    run_as_deploy "php artisan config:cache"
  fi
}

configure_firewall() {
  step "فایروال UFW"
  if [[ "$SKIP_FIREWALL" == true ]]; then
    warn "فایروال رد شد."
    return
  fi
  if ! command -v ufw >/dev/null 2>&1; then
    apt-get install -y -qq ufw
  fi
  ufw allow OpenSSH >/dev/null 2>&1 || ufw allow 22/tcp
  ufw allow 'Nginx Full' >/dev/null 2>&1 || { ufw allow 80/tcp; ufw allow 443/tcp; }
  ufw --force enable
  ok "UFW فعال شد."
}

configure_worker_and_cron() {
  step "Queue Worker و Cron"
  if [[ "$SKIP_WORKER" == true ]]; then
    warn "Worker و Cron رد شدند."
    return
  fi

  apt-get install -y -qq supervisor

  cat > "/etc/supervisor/conf.d/${NGINX_SITE}-worker.conf" <<WORKER
[program:${NGINX_SITE}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${DEPLOY_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
WORKER

  supervisorctl reread
  supervisorctl update
  supervisorctl restart "${NGINX_SITE}-worker:" 2>/dev/null || supervisorctl start "${NGINX_SITE}-worker:"

  local cron_line="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
  (crontab -u "$DEPLOY_USER" -l 2>/dev/null | grep -v "artisan schedule:run"; echo "$cron_line") | crontab -u "$DEPLOY_USER" -
  ok "Worker و Cron تنظیم شدند."
}

health_check() {
  step "بررسی سلامت"
  local code
  code=$(curl -s -o /dev/null -w "%{http_code}" -H "Host: ${APP_DOMAIN}" "http://127.0.0.1/admin/login" || echo "000")
  if [[ "$code" == "200" ]]; then
    ok "پاسخ HTTP 200 از /admin/login"
  else
    warn "بررسی HTTP کد $code — ممکن است DNS هنوز propagate نشده باشد."
  fi
}

print_summary() {
  local scheme="https"
  [[ "$SKIP_SSL" == true || -z "$SSL_EMAIL" ]] && scheme="http"

  echo ""
  echo -e "${BOLD}${GREEN}════════════════════════════════════════════${RESET}"
  echo -e "${BOLD}  نصب Simple HR با موفقیت تمام شد${RESET}"
  echo -e "${BOLD}${GREEN}════════════════════════════════════════════${RESET}"
  echo ""
  echo -e "  ${BOLD}پنل ادمین:${RESET}  ${scheme}://${APP_DOMAIN}/admin/login"
  echo -e "  ${BOLD}استخدام:${RESET}    ${scheme}://${APP_DOMAIN}/recruitment/login"
  echo -e "  ${BOLD}پورتال:${RESET}     ${scheme}://${APP_DOMAIN}/portal/login"
  echo ""
  echo -e "  ${BOLD}ایمیل:${RESET}      ${ADMIN_EMAIL}"
  echo -e "  ${BOLD}رمز:${RESET}        ${ADMIN_PASSWORD}"
  echo ""
  echo -e "  ${BOLD}مسیر:${RESET}       ${APP_DIR}"
  echo -e "  ${BOLD}دیتابیس:${RESET}    ${DB_NAME}"
  echo ""
  warn "رمز ادمین را فوراً عوض کنید."
  echo ""
  echo -e "  به‌روزرسانی بعدی:"
  echo -e "  ${DIM}cd ${APP_DIR} && git pull && composer install --no-dev && php artisan migrate --force && php artisan config:cache${RESET}"
  echo ""
}

# ─── اجرا ──────────────────────────────────────────────────────────
main() {
  parse_args "$@"
  require_root
  require_os
  banner
  prompt_defaults

  install_swap
  install_packages
  install_composer
  setup_deploy_user
  setup_database
  clone_project
  configure_laravel
  install_app
  configure_nginx
  configure_ssl
  configure_firewall
  configure_worker_and_cron
  health_check
  print_summary
}

main "$@"
