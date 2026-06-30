#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-$HOME/domains/equalizeinfo.com.br/public_html/osv2}"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
# Opcional: MIGRATE=1 bash scripts/deploy-server.sh
# Se o banco foi criado via SQL: MIGRATE_BASELINE=1 MIGRATE=1 bash scripts/deploy-server.sh
RUN_MIGRATE="${MIGRATE:-0}"
RUN_BASELINE="${MIGRATE_BASELINE:-0}"

cd "$APP_DIR"

echo "==> Diretório: $(pwd)"
echo "==> Commit local antes: $(git rev-parse --short HEAD 2>/dev/null || echo 'n/a')"

git fetch origin main
git reset --hard origin/main

echo "==> Commit após sync: $(git rev-parse --short HEAD)"
echo "==> Manifest CSS: $(grep -o 'app-[^\"]*\\.css' public/build/manifest.json | head -1 || true)"

rm -f public/hot

if [[ "$RUN_BASELINE" == "1" ]]; then
    echo "==> Registrando baseline de migrations (sem executar)..."
    "$PHP_BIN" artisan migrate:mark-applied --except=2026_06_30
fi

if [[ "$RUN_MIGRATE" == "1" ]]; then
    echo "==> Rodando migrations..."
    if ! "$PHP_BIN" artisan migrate --force; then
        echo ""
        echo "==> migrate falhou (tabelas já existem sem registro no Laravel)."
        echo "    Para sincronizar o baseline e rodar só as novas:"
        echo "    MIGRATE_BASELINE=1 MIGRATE=1 bash scripts/deploy-server.sh"
        echo ""
        echo "    Ou aplique migrations pontuais, ex.:"
        echo "    bash scripts/migrate-tarefas-cronometro.sh"
        exit 1
    fi
fi

"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan route:clear

if [[ ! -e public/storage ]]; then
    echo "==> Criando link public/storage → storage/app/public..."
    "$PHP_BIN" artisan storage:link
else
    echo "==> Link public/storage já existe."
fi

mkdir -p storage/app/public/empresa
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "==> Deploy concluído."
