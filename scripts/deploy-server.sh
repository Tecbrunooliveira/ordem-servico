#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-$HOME/domains/equalizeinfo.com.br/public_html/osv2}"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
# Opcional: MIGRATE=1 bash scripts/deploy-server.sh
RUN_MIGRATE="${MIGRATE:-0}"

cd "$APP_DIR"

echo "==> Diretório: $(pwd)"
echo "==> Commit local antes: $(git rev-parse --short HEAD 2>/dev/null || echo 'n/a')"

git fetch origin main
git reset --hard origin/main

echo "==> Commit após sync: $(git rev-parse --short HEAD)"
echo "==> Manifest CSS: $(grep -o 'app-[^\"]*\\.css' public/build/manifest.json | head -1 || true)"

rm -f public/hot

if [[ "$RUN_MIGRATE" == "1" ]]; then
    echo "==> Rodando migrations..."
    "$PHP_BIN" artisan migrate --force
fi

"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan route:clear

echo "==> Deploy concluído."
