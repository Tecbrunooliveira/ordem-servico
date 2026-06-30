#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-$HOME/domains/equalizeinfo.com.br/public_html/osv2}"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"

cd "$APP_DIR"

echo "==> Rodando migrations de sistemas e repositório..."
"$PHP_BIN" artisan migrate --path=database/migrations/2026_06_30_140000_create_sistemas_table.php --force
"$PHP_BIN" artisan migrate --path=database/migrations/2026_06_30_140001_create_repositorio_erros_table.php --force

mkdir -p storage/app/public/repositorio
chmod -R ug+rwx storage 2>/dev/null || true

echo "==> Migrations de sistemas e repositório concluídas."
