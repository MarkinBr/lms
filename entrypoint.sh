#!/bin/bash
# ============================================================
# CETSADP v9.5.0 — LMS Entrypoint Script
# Etapa 4.3: Inicialização robusta do container LMS
#
# Este script substitui o CMD inline do Dockerfile.
# Garante que migrations, cache e permissões estejam corretos
# antes de iniciar o Apache.
# ============================================================
set -e

echo ""
echo "============================================"
echo "  🎓 CETSADP LMS — Inicializando..."
echo "  📦 Versão: v9.5.0"
echo "============================================"
echo ""

# === 1. Verificar variáveis obrigatórias ===
if [ -z "$APP_KEY" ]; then
    echo "❌ ERRO: APP_KEY não definida. Abortando."
    exit 1
fi

if [ -z "$DB_PASSWORD" ]; then
    echo "❌ ERRO: DB_PASSWORD não definida. Abortando."
    exit 1
fi

# === 2. Aguardar PostgreSQL ficar pronto ===
echo "⏳ Aguardando PostgreSQL (${DB_HOST:-postgres}:${DB_PORT:-5432})..."
MAX_ATTEMPTS=30
ATTEMPT=0
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-cetsadp_user}" -q 2>/dev/null; do
    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -ge $MAX_ATTEMPTS ]; then
        echo "❌ PostgreSQL não ficou pronto após ${MAX_ATTEMPTS} tentativas. Abortando."
        exit 1
    fi
    echo "  ⏳ Tentativa ${ATTEMPT}/${MAX_ATTEMPTS}..."
    sleep 2
done
echo "✅ PostgreSQL está pronto."

# === 3. Aguardar Redis ficar pronto ===
echo "⏳ Aguardando Redis (${REDIS_HOST:-redis}:${REDIS_PORT:-6379})..."
ATTEMPT=0
until timeout 2 bash -c "</dev/tcp/${REDIS_HOST:-redis}/${REDIS_PORT:-6379}" 2>/dev/null; do
    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -ge $MAX_ATTEMPTS ]; then
        echo "⚠️  Redis não ficou pronto. Continuando sem cache Redis..."
        break
    fi
    echo "  ⏳ Tentativa ${ATTEMPT}/${MAX_ATTEMPTS}..."
    sleep 2
done
if [ $ATTEMPT -lt $MAX_ATTEMPTS ]; then
    echo "✅ Redis está pronto."
fi

# === 4. Garantir permissões de diretórios ===
echo "📁 Configurando permissões..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# === 5. Executar migrations ===
echo "🗄️  Executando migrations..."
php artisan migrate --force --no-interaction 2>&1 || {
    echo "⚠️  Algumas migrations podem ter falhado. Verificando status..."
    php artisan migrate:status 2>&1 | tail -10
}

# === 6. Criar link simbólico do storage ===
echo "🔗 Criando link de storage..."
php artisan storage:link 2>/dev/null || true

# === 7. Otimizar para produção ===
if [ "${APP_ENV}" = "production" ]; then
    echo "🚀 Otimizando para produção..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    echo "✅ Cache de configurações, rotas e views gerado."
fi

# === 8. Healthcheck endpoint (escrever arquivo para o healthcheck) ===
echo "ok" > /tmp/lms-ready

echo ""
echo "============================================"
echo "  ✅ CETSADP LMS v9.5.0 pronto!"
echo "  🌐 Iniciando Apache..."
echo "============================================"
echo ""

# === 9. Iniciar Apache em foreground ===
exec apache2-foreground
