#!/usr/bin/env bash
# بسته DirectAdmin — data + public_html
# اجرا: bash scripts/build-directadmin.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${ROOT}/build/directadmin"
ZIP="${ROOT}/simple-hr-directadmin.zip"
SETUP_KEY="${SETUP_KEY:-simplehr$(date +%s | tail -c 6)}"

echo "→ پاکسازی..."
rm -rf "$OUT" "$ZIP"
mkdir -p "$OUT/data" "$OUT/public_html"

echo "→ کپی پروژه به data/..."
rsync -a \
  --exclude '.git' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude 'node_modules' \
  --exclude 'public' \
  --exclude 'dist' \
  --exclude 'build' \
  --exclude 'tests' \
  --exclude 'storage/logs/*.log' \
  --exclude 'storage/framework/cache/data/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'database/database.sqlite' \
  --exclude '.cursor' \
  --exclude 'simple-hr-directadmin.zip' \
  --exclude 'simple-hr-offline*.tar.gz' \
  "$ROOT/" "$OUT/data/"

echo "→ composer install (--no-dev)..."
touch "$OUT/data/database/database.sqlite"
(cd "$OUT/data" && composer install --no-dev --optimize-autoloader --no-interaction --no-scripts)
(cd "$OUT/data" && composer dump-autoload --optimize --no-interaction --no-scripts)
rm -f "$OUT/data/database/database.sqlite"

echo "→ public_html..."
rsync -a \
  --exclude '.DS_Store' \
  --exclude 'storage/*' \
  --exclude 'hot' \
  "$ROOT/public/" "$OUT/public_html/"
mkdir -p "$OUT/public_html/storage"
echo 'storage symlink placeholder' > "$OUT/public_html/storage/.gitignore" 2>/dev/null || true
touch "$OUT/public_html/storage/.gitignore"
cp "$ROOT/scripts/directadmin/public_html-index.php" "$OUT/public_html/index.php"

cp "$ROOT/.env.hosting.example" "$OUT/data/.env.hosting.example"

SETUP_FILE="$OUT/public_html/setup.php"
cp "$ROOT/scripts/directadmin/setup.php" "$SETUP_FILE"
cp "$ROOT/scripts/directadmin/check.php" "$OUT/public_html/check.php"
if [[ "$(uname)" == "Darwin" ]]; then
  sed -i '' "s/CHANGE_ME_BEFORE_UPLOAD/${SETUP_KEY}/" "$SETUP_FILE"
else
  sed -i "s/CHANGE_ME_BEFORE_UPLOAD/${SETUP_KEY}/" "$SETUP_FILE"
fi

cat > "$OUT/HOSTING-README.txt" <<README
═══════════════════════════════════════════════════════════
  Simple HR — راهنمای نصب DirectAdmin
═══════════════════════════════════════════════════════════

ساختار روی هاست:
  domains/دامنه.com/data/          ← محتوای پوشه data از ZIP
  domains/دامنه.com/public_html/   ← محتوای public_html از ZIP

مرحله ۱ — آپلود
  - ZIP را Extract کنید
  - پوشه data را بگذارید هم‌سطح public_html (نه داخل آن)
  - محتوای public_html را در public_html دامنه بریزید

مرحله ۲ — PHP
  - DirectAdmin → PHP Settings → PHP 8.1 یا 8.2
  - extensions: mbstring, xml, curl, zip, gd, bcmath, intl, pdo_mysql

مرحله ۳ — دیتابیس MySQL (مهم — نه SQLite!)
  ⚠️ دیتابیس لوکال SQLite است — روی هاست export/import نکنید!
  روی هاست:
    1) DirectAdmin → MySQL → Create Database + User
    2) data/.env.hosting.example را کپی کنید به data/.env
    3) DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL را پر کنید

  چرا export لوکال کار نمی‌کند؟
    - لوکال SQLite است، هاست MySQL است — فرمت فرق دارد
    - جداول را migration روی هاست می‌سازد (خالی + seed)
    - پرسنل و درخواست‌ها را بعداً از پنل → Import CSV بزنید

مرحله ۴ — دسترسی پوشه‌ها
  data/storage → 775
  data/bootstrap/cache → 775

مرحله ۵ — راه‌اندازی
  مرورگر:
  https://دامنه-شما/setup.php?key=${SETUP_KEY}

  بعد از موفقیت setup.php را حذف کنید!

  ورود: /admin/login
  admin@example.com / password

  اگر SSH دارید به‌جای setup.php:
    cd ~/domains/دامنه.com/data
    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed --force
    php artisan storage:link
    php artisan config:cache

═══════════════════════════════════════════════════════════
README

echo "→ فشرده‌سازی..."
(cd "$OUT" && zip -rq "$ZIP" .)

SIZE=$(du -h "$ZIP" | cut -f1)
echo ""
echo "✔ آماده: $ZIP ($SIZE)"
echo "✔ کلید setup: $SETUP_KEY"
echo "  URL: https://YOUR-DOMAIN/setup.php?key=${SETUP_KEY}"
