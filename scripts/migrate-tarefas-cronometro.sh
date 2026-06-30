#!/usr/bin/env bash
set -euo pipefail

PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
APP_DIR="${1:-$HOME/domains/equalizeinfo.com.br/public_html/osv2}"

cd "$APP_DIR"

echo "==> Aplicando migrations do cronômetro de tarefas..."

"$PHP_BIN" artisan migrate --path=database/migrations/2026_06_30_100000_add_execution_fields_to_tarefas_table.php --force
"$PHP_BIN" artisan migrate --path=database/migrations/2026_06_30_100001_create_tarefa_pausas_table.php --force

echo "==> Migrations do cronômetro concluídas."
