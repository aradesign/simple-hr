#!/usr/bin/env bash
# ساخت بسته آفلاین روی لپتاپ — بدون نیاز به GitHub روی سرور
# اجرا: bash scripts/build-offline-bundle.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="${ROOT}/dist"
BUNDLE_NAME="simple-hr-offline"
STAMP="$(date +%Y%m%d-%H%M%S)"
WORK="${DIST}/${BUNDLE_NAME}-${STAMP}"
ARCHIVE="${DIST}/${BUNDLE_NAME}-${STAMP}.tar.gz"
LATEST_LINK="${DIST}/${BUNDLE_NAME}-latest.tar.gz"

GREEN='\033[0;32m'; CYAN='\033[0;36m'; RESET='\033[0m'
step() { echo -e "${CYAN}→${RESET} $*"; }
ok()   { echo -e "${GREEN}✔${RESET} $*"; }

command -v php >/dev/null    || { echo "PHP روی لپتاپ نصب نیست."; exit 1; }
command -v composer >/dev/null || { echo "Composer روی لپتاپ نصب نیست."; exit 1; }

PHP_LOCAL="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
step "PHP لپتاپ: ${PHP_LOCAL} (سرور: PHP 8.1 از مخازن اوبونتو ایران)"

rm -rf "$WORK"
mkdir -p "$WORK/tools" "$DIST"

step "کپی فایل‌های پروژه..."
rsync -a \
  --exclude '.git' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude 'node_modules' \
  --exclude 'dist' \
  --exclude 'storage/logs/*.log' \
  --exclude 'storage/framework/cache/data/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'storage/app/private/*' \
  --exclude 'database/database.sqlite' \
  --exclude '.cursor' \
  --exclude '.idea' \
  --exclude 'tests' \
  "$ROOT/" "$WORK/project/"

step "نصب وابستگی‌های PHP (vendor) برای انتقال آفلاین..."
touch "$WORK/project/database/database.sqlite"
(cd "$WORK/project" && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts)
(cd "$WORK/project" && composer dump-autoload --optimize --no-interaction --no-scripts)
rm -f "$WORK/project/database/database.sqlite"

step "دانلود composer.phar (پشتیبان روی سرور)..."
curl -fsSL https://getcomposer.org/download/latest-stable/composer.phar -o "$WORK/tools/composer.phar"
chmod +x "$WORK/tools/composer.phar"

cp "${ROOT}/scripts/server-install-offline.sh" "$WORK/server-install-offline.sh"
chmod +x "$WORK/server-install-offline.sh"

cat > "$WORK/BUNDLE_MANIFEST.txt" <<EOF
Simple HR Offline Bundle
Built: ${STAMP}
Built on: $(uname -s) $(uname -m)
PHP (build machine): ${PHP_LOCAL}
Laravel: $(cd "$WORK/project" && php artisan --version 2>/dev/null || echo unknown)
Includes: project + vendor + public/build + composer.phar
Server needs: Ubuntu 22.04 + apt (Iran mirror) — NO GitHub required
EOF

step "فشرده‌سازی..."
tar -czf "$ARCHIVE" -C "$DIST" "$(basename "$WORK")"
ln -sf "$(basename "$ARCHIVE")" "$LATEST_LINK"
rm -rf "$WORK"

BYTES=$(du -h "$ARCHIVE" | cut -f1)
ok "بسته آماده: $ARCHIVE ($BYTES)"
ok "لینک: $LATEST_LINK"
echo ""
echo "مرحله بعد — انتقال و نصب از لپتاپ:"
echo "  bash scripts/deploy-from-laptop.sh USER@SERVER_IP --bundle $ARCHIVE"
