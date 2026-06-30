#!/usr/bin/env bash
set -euo pipefail

PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
APP_DIR="${1:-$HOME/domains/equalizeinfo.com.br/public_html/osv2}"

cd "$APP_DIR"

echo "==> Aplicando migration cliente_id em tarefas..."

"$PHP_BIN" artisan migrate --path=database/migrations/2026_06_30_120000_add_cliente_id_to_tarefas_table.php --force

echo "==> Migration concluída."
