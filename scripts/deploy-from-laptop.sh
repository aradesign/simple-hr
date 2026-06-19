#!/usr/bin/env bash
# انتقال و نصب از لپتاپ به سرور ایران — بدون GitHub روی سرور
#
# استفاده:
#   bash scripts/deploy-from-laptop.sh root@IP_SERVER
#   bash scripts/deploy-from-laptop.sh root@IP_SERVER --domain hr.example.com --db-password 'secret'
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SSH_TARGET=""
BUNDLE=""
APP_DOMAIN=""
DB_PASSWORD=""
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password"
SSL_EMAIL=""
SKIP_SSL=false
SKIP_BUILD=false
REMOTE_DIR="/tmp/simple-hr-deploy"

usage() {
  cat <<'EOF'
انتقال و نصب آفلاین Simple HR از لپتاپ به سرور

  bash scripts/deploy-from-laptop.sh USER@HOST [گزینه‌ها]

گزینه‌ها:
  --bundle=PATH          مسیر فایل tar.gz (پیش‌فرض: آخرین بسته dist/)
  --domain=DOMAIN        دامنه
  --db-password=PASS     رمز MariaDB
  --admin-email=EMAIL
  --admin-password=PASS
  --ssl-email=EMAIL      (خالی = بدون SSL)
  --skip-build           از بسته موجود استفاده کن
  --skip-ssl
  -h, --help

مثال:
  bash scripts/deploy-from-laptop.sh root@185.x.x.x --domain hr.my.ir --db-password 'MyPass123'
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --bundle=*) BUNDLE="${1#*=}"; shift ;;
    --domain=*) APP_DOMAIN="${1#*=}"; shift ;;
    --db-password=*) DB_PASSWORD="${1#*=}"; shift ;;
    --admin-email=*) ADMIN_EMAIL="${1#*=}"; shift ;;
    --admin-password=*) ADMIN_PASSWORD="${1#*=}"; shift ;;
    --ssl-email=*) SSL_EMAIL="${1#*=}"; shift ;;
    --skip-build) SKIP_BUILD=true; shift ;;
    --skip-ssl) SKIP_SSL=true; shift ;;
    -h|--help) usage; exit 0 ;;
    -*) echo "گزینه ناشناخته: $1"; usage; exit 1 ;;
    *)
      if [[ -z "$SSH_TARGET" ]]; then SSH_TARGET="$1"; else echo "آرگومان اضافی: $1"; exit 1; fi
      shift ;;
  esac
done

[[ -n "$SSH_TARGET" ]] || { usage; exit 1; }

echo "═══ Simple HR — Deploy از لپتاپ ═══"
echo "سرور: $SSH_TARGET"
echo ""

if [[ "$SKIP_BUILD" != true ]]; then
  echo "→ ساخت بسته آفلاین روی لپتاپ..."
  bash "${ROOT}/scripts/build-offline-bundle.sh"
fi

if [[ -z "$BUNDLE" ]]; then
  BUNDLE="${ROOT}/dist/simple-hr-offline-latest.tar.gz"
fi
[[ -f "$BUNDLE" ]] || { echo "بسته یافت نشد: $BUNDLE"; exit 1; }

BYTES=$(du -h "$BUNDLE" | cut -f1)
echo "→ بسته: $BUNDLE ($BYTES)"

if [[ -z "$APP_DOMAIN" ]]; then
  read -rp "دامنه سایت: " APP_DOMAIN
fi
if [[ -z "$DB_PASSWORD" ]]; then
  read -rsp "رمز دیتابیس MariaDB: " DB_PASSWORD; echo
fi

echo ""
echo "→ تست SSH..."
ssh -o ConnectTimeout=15 -o BatchMode=yes "$SSH_TARGET" "echo OK" 2>/dev/null || {
  echo "SSH وصل نشد. مطمئن شوید:"
  echo "  ssh $SSH_TARGET"
  echo "  (کلید SSH یا رمز را دارید)"
  exit 1
}

echo "→ آپلود بسته به سرور (ممکن است چند دقیقه طول بکشد)..."
ssh "$SSH_TARGET" "mkdir -p ${REMOTE_DIR} && rm -rf ${REMOTE_DIR}/bundle ${REMOTE_DIR}/*.tar.gz"
scp "$BUNDLE" "${SSH_TARGET}:${REMOTE_DIR}/bundle.tar.gz"

echo "→ استخراج و نصب روی سرور..."
ssh -t "$SSH_TARGET" bash -s <<REMOTE
set -euo pipefail
cd ${REMOTE_DIR}
rm -rf bundle && mkdir bundle
tar -xzf bundle.tar.gz -C bundle --strip-components=1
export APP_DOMAIN='${APP_DOMAIN}'
export DB_PASSWORD='${DB_PASSWORD}'
export ADMIN_EMAIL='${ADMIN_EMAIL}'
export ADMIN_PASSWORD='${ADMIN_PASSWORD}'
export SSL_EMAIL='${SSL_EMAIL}'
export SKIP_SSL='${SKIP_SSL}'
sudo -E bash bundle/server-install-offline.sh ${REMOTE_DIR}/bundle
REMOTE

echo ""
echo "✔ تمام — باز کنید: http://${APP_DOMAIN}/admin/login"
