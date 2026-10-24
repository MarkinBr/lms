#!/bin/bash
# ============================================================
# CETSADP LMS — Entrypoint de produção
# Falha cedo quando banco/cache essenciais não estão disponíveis.
# ============================================================
set -Eeuo pipefail

echo ""
echo "============================================"
echo "  🎓 CETSADP LMS — Inicializando..."
echo "============================================"
echo ""

# === 1. Verificar variáveis obrigatórias ===
if [ -z "${APP_KEY:-}" ]; then
    echo "❌ ERRO: APP_KEY não definida. Abortando."
    exit 1
fi

if [ -z "${DB_PASSWORD:-}" ]; then
    echo "❌ ERRO: DB_PASSWORD não definida. Abortando."
    exit 1
fi

# === 2. Aguardar PostgreSQL ficar pronto ===
echo "⏳ Aguardando PostgreSQL (${DB_HOST:-postgres}:${DB_PORT:-5432})..."
MAX_ATTEMPTS=30
ATTEMPT=0
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-cetsadp_user}" -q 2>/dev/null; do
    ATTEMPT=$((ATTEMPT + 1))
    if [ "$ATTEMPT" -ge "$MAX_ATTEMPTS" ]; then
        echo "❌ PostgreSQL não ficou pronto após ${MAX_ATTEMPTS} tentativas. Abortando."
        exit 1
    fi
    echo "  ⏳ Tentativa ${ATTEMPT}/${MAX_ATTEMPTS}..."
    sleep 2
done
echo "✅ PostgreSQL está pronto."

# === 3. Aguardar Redis quando ele for dependência configurada ===
REDIS_REQUIRED=false
for value in "${CACHE_STORE:-}" "${CACHE_DRIVER:-}" "${SESSION_DRIVER:-}" "${QUEUE_CONNECTION:-}"; do
    if [ "${value,,}" = "redis" ]; then
        REDIS_REQUIRED=true
        break
    fi
done

echo "⏳ Verificando Redis (${REDIS_HOST:-redis}:${REDIS_PORT:-6379})..."
ATTEMPT=0
until timeout 2 bash -c "</dev/tcp/${REDIS_HOST:-redis}/${REDIS_PORT:-6379}" 2>/dev/null; do
    ATTEMPT=$((ATTEMPT + 1))
    if [ "$ATTEMPT" -ge "$MAX_ATTEMPTS" ]; then
        if [ "$REDIS_REQUIRED" = true ]; then
            echo "❌ Redis é obrigatório pela configuração atual e não ficou disponível. Abortando."
            exit 1
        fi
        echo "⚠️  Redis indisponível, mas não está configurado como dependência obrigatória."
        break
    fi
    echo "  ⏳ Tentativa ${ATTEMPT}/${MAX_ATTEMPTS}..."
    sleep 2
done

if [ "$ATTEMPT" -lt "$MAX_ATTEMPTS" ]; then
    echo "✅ Redis está pronto."
fi

echo "📁 Configurando permissões..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "🗄️  Executando migrations..."
if ! php artisan migrate --force --no-interaction; then
    echo "❌ Migration falhou. Estado atual:"
    php artisan migrate:status || true
    exit 1
fi

echo "🔗 Verificando link de storage..."
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || echo "⚠️  Não foi possível criar o link de storage."
fi

if [ "${APP_ENV:-}" = "production" ]; then
    echo "🚀 Otimizando para produção..."
    php artisan config:cache
    if ! php artisan route:cache; then
        echo "⚠️  Route cache não pôde ser gerado; limpando cache de rotas e continuando."
        php artisan route:clear
    fi
    php artisan view:cache
    echo "✅ Cache de configuração/views gerado e rotas validadas."
fi

echo "ok" > /tmp/lms-ready

echo ""
echo "============================================"
echo "  ✅ CETSADP LMS pronto!"
echo "  🌐 Iniciando Apache..."
echo "============================================"
echo ""

exec apache2-foreground
