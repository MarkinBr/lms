#!/bin/bash
# Migração é uma operação explícita: nunca iniciada por web, worker ou heartbeat.
set -eu
fail() { echo "ERRO: $*" >&2; exit 1; }
[ -n "${APP_KEY:-}" ] || fail 'APP_KEY obrigatória.'
[ -n "${DB_PASSWORD:-}" ] || fail 'DB_PASSWORD obrigatória.'
mode="${1:-web}"
case "$mode" in web|worker|migrate|scheduler|scheduler-list) ;; *) fail 'Modo inválido.' ;; esac

if [ "${APP_ENV:-}" = homologacao ]; then
    [ "${APP_DEBUG:-}" = false ] || fail 'Debug deve estar desativado.'
    [ "${DB_HOST:-}" = postgres ] && [ "${DB_DATABASE:-}" = portal84_lms ] || fail 'Banco isolado obrigatório.'
    [ "${DB_USERNAME:-}" = portal84_teste ] || fail 'Usuário isolado obrigatório.'
    [ "${LEGADO_DB_HOST:-}" = postgres ] && [ "${LEGADO_DB_DATABASE:-}" = portal84_legado ] || fail 'Origem isolada obrigatória.'
    [ "${LEGADO_DB_USERNAME:-}" = "$DB_USERNAME" ] && [ "${LEGADO_DB_PASSWORD:-}" = "$DB_PASSWORD" ] || fail 'Credenciais da cópia inconsistentes.'
    [ "${MAIL_MAILER:-}" = array ] && [ -z "${TELEGRAM_BOT_TOKEN:-}" ] || fail 'Mensagens externas bloqueadas.'
    [ "${QUEUE_CONNECTION:-}" = database ] && [ "${DB_QUEUE:-}" = portal84-homologacao ] || fail 'Fila isolada obrigatória.'
    [ "${APP_CONFIG_CACHE:-}" = /tmp/portal84-config.php ] || fail 'Cache de configuração isolado obrigatório.'
fi

cd "${APP_ROOT:-/var/www/html}"
marker=''
if [ "${APP_ENV:-}" = homologacao ]; then
    case "${PORTAL84_RELEASE:-}" in portal84-*) ;; *) fail 'Versão de release obrigatória.' ;; esac
    case "$PORTAL84_RELEASE" in *[!a-z0-9._-]*) fail 'Versão de release inválida.' ;; esac
    marker="$PWD/storage/app/.portal84-migrated-$PORTAL84_RELEASE"
    if [ "$mode" = migrate ]; then
        # Invalidar sucesso anterior ANTES de tentar conexão/migração.
        rm -f "$marker"
    elif [ "$mode" != scheduler-list ]; then
        [ -f "$marker" ] && [ "$(cat "$marker")" = "$PORTAL84_RELEASE" ] || fail 'Migração desta release ainda não foi concluída com sucesso.'
    fi
fi
# Heartbeat propositalmente NÃO chama schedule:run/work. A agenda real inclui cobranças.
# schedule:list somente inspeciona a agenda; nenhum comando listado será executado.
if [ "$mode" = scheduler ]; then
    [ "${APP_ENV:-}" = homologacao ] || fail 'Heartbeat exclusivo de homologação.'
    trap 'exit 0' TERM INT
    while true; do
        date -u +%FT%TZ > /tmp/portal84-scheduler-heartbeat
        echo 'portal84: heartbeat; tarefas reais desativadas'
        sleep 60 &
        wait $!
    done
fi

attempt=0
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-}" -q; do
    attempt=$((attempt + 1))
    [ "$attempt" -lt 30 ] || fail 'PostgreSQL indisponível.'
    sleep 2
done

# Cada container possui framework/cache próprio; somente storage/app é compartilhado.
mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage/framework storage/logs bootstrap/cache
# Apenas o web prepara permissões do volume, antes de os consumidores iniciarem.
if [ "$mode" = web ]; then
    chown -R www-data:www-data storage/app
    if [ ! -L public/storage ]; then
        [ ! -e public/storage ] || fail 'public/storage existe e não é symlink.'
        ln -s ../storage/app/public public/storage
    fi
fi

case "$mode" in
    migrate)
        php artisan migrate --force --no-interaction
        if [ -n "$marker" ]; then
            printf '%s\n' "$PORTAL84_RELEASE" > "$marker"
        fi
        exit 0
        ;;
    worker) exec php artisan queue:work database --queue=portal84-homologacao --sleep=3 --tries=1 --timeout=60 --memory=192 --max-time=3600 ;;
    scheduler-list) exec php artisan schedule:list --no-interaction ;;
    web) exec apache2-foreground ;;
esac
