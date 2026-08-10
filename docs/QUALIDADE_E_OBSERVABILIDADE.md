# Qualidade de Código e Observabilidade — CETSADP LMS

Este documento centraliza as configurações e comandos para todas as ferramentas de qualidade de código, testes e observabilidade do projeto.

---

## 1. Observabilidade

### 1.1 Sentry (Error Tracking)

**Propósito:** Capturar exceptions PHP e erros JavaScript em produção.

**Instalação:**
```bash
composer require sentry/sentry-laravel
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

**Configuração mínima (.env):**
```env
SENTRY_LARAVEL_DSN=https://xxx@oXXX.ingest.sentry.io/XXX
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_ENVIRONMENT=production
```

**O que capturar:**
- Todas as exceptions não tratadas
- Erros JavaScript no frontend
- Queries N+1 detectadas
- Jobs falhando na fila

### 1.2 OpenTelemetry (Tracing Distribuído)

**Propósito:** Rastrear requests HTTP, queries lentas e chamadas entre serviços.

**Instalação:**
```bash
pecl install opentelemetry
composer require open-telemetry/sdk open-telemetry/exporter-otlp
```

**Configuração:**
- Trace de requests HTTP (entrada/saída)
- Trace de queries SQL >100ms
- Trace de chamadas a APIs externas (Mercado Pago, SSO)

### 1.3 Nota sobre Datadog/New Relic

> **Não use Sentry + Datadog + New Relic simultaneamente.** É redundante e caro.

**Recomendação:** Sentry (erros) + OpenTelemetry (tracing) cobre 95% das necessidades. Adicione Datadog/New Relic apenas se houver requisito específico de métricas de infra.

---

## 2. Qualidade de Código

### 2.1 PHP

#### Laravel Pint (Formatação)
```bash
composer require --dev laravel/pint
composer pint          # Formatar
composer pint --test   # Apenas verificar
```

#### PHPStan + Larastan (Análise Estática)
```bash
composer require --dev phpstan/phpstan nunomaduro/larastan
vendor/bin/phpstan analyse --level max
```

**Configuração (phpstan.neon):**
```neon
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:
    level: max
    paths:
        - app
        - database
    checkMissingIterableValueType: false
```

#### Deptrac (Arquitetura)
```bash
composer require --dev qossmic/deptrac
vendor/bin/deptrac analyse
```

**Configuração (deptrac.yaml):**
```yaml
deptrac:
    paths:
        - ./app
    exclude_files:
        - '#.*test.*#'
    layers:
        - name: Controllers
          collectors:
              - type: className
                regex: ^App\\Http\\Controllers\\.*
        - name: Services
          collectors:
              - type: className
                regex: ^App\\Services\\.*
        - name: Models
          collectors:
              - type: className
                regex: ^App\\Models\\.*
        - name: Filament
          collectors:
              - type: className
                regex: ^App\\Filament\\.*
    ruleset:
        Controllers:
            - Services
            - Models
            - Filament
        Services:
            - Models
        Models: []
        Filament:
            - Models
            - Services
```

### 2.2 JavaScript/CSS

#### Biome (Lint + Format)
```bash
npm install --save-dev @biomejs/biome
npx biome check .     # Lint
npx biome format .    # Format
npx biome check --apply  # Auto-fix
```

**Configuração (biome.json):**
```json
{
  "$schema": "https://biomejs.dev/schemas/1.0.0/schema.json",
  "organizeImports": { "enabled": true },
  "linter": {
    "enabled": true,
    "rules": { "recommended": true }
  },
  "formatter": {
    "enabled": true,
    "indentStyle": "space",
    "indentWidth": 2
  }
}
```

#### Knip (Código Morto)
```bash
npm install --save-dev knip
npx knip           # Detectar dead code
npx knip --fix     # Remover automaticamente
```

### 2.3 Commits

#### Commitlint + Husky
```bash
npm install --save-dev @commitlint/cli @commitlint/config-conventional husky
npx husky init
echo "npx commitlint --edit \$1" >> .husky/commit-msg
```

**Configuração (commitlint.config.js):**
```javascript
module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-enum': [2, 'always', ['feat', 'fix', 'chore', 'docs', 'style', 'refactor', 'test']],
    'subject-case': [2, 'never', ['sentence-case', 'start-case', 'pascal-case', 'upper-case']]
  }
};
```

---

## 3. Testes

### 3.1 PHPUnit (Unitário/Integração)
```bash
composer test              # Rodar testes
composer test -- coverage  # Com cobertura
```

**Configuração (phpunit.xml):**
```xml
<coverage includeUncoveredFiles="true">
    <report>
        <clover outputFile="coverage.xml"/>
    </report>
</coverage>
```

### 3.2 Playwright (E2E)
```bash
npm install --save-dev @playwright/test
npx playwright install
npx playwright test        # Rodar testes
npx playwright test --ui   # Interface gráfica
```

**Fluxos críticos a cobrir:**
- Inscrição via formulário público
- Login aluno
- Login secretaria
- Contato/newsletter
- Geração de declaração/certificado

### 3.3 Codecov (Cobertura)

**Configuração (.github/workflows/ci.yml):**
```yaml
- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v4
  with:
    files: ./coverage.xml
    fail_ci_if_error: false
```

### 3.4 Mutation Testing

#### Infection (PHP)
```bash
composer require --dev infection/infection
vendor/bin/infection --min-msi=80
```

#### Stryker (JS)
```bash
npm install --save-dev @stryker-mutator/core @stryker-mutator/jest-runner
npx stryker run
```

> **Nota:** Mutation testing é lento. Rodar sob demanda, não em todo PR.

---

## 4. CI/CD Integration

### Workflow GitHub Actions (.github/workflows/ci.yml)

```yaml
name: CI

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer:v2
      - run: composer install --no-interaction
      - run: composer pint --test
      - run: vendor/bin/phpstan analyse --level max
      - run: npm ci
      - run: npx biome check .

  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: cetsadp_test
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
        ports:
          - 5432:5432
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: pcov
      - run: composer install --no-interaction
      - run: php artisan test --coverage-clover=coverage.xml
      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          files: ./coverage.xml

  e2e:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci
      - uses: browser-actions/setup-playwright@latest
      - run: npx playwright test

  deploy:
    needs: [lint, test, e2e]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - name: Deploy via SSH
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            cd /var/www/cetsadp-lms
            git pull origin main
            docker compose up -d --build
```

---

## 5. Resumo de Comandos

| Ação | Comando |
|---|---|
| Format PHP | `composer pint` |
| Lint PHP | `vendor/bin/phpstan analyse` |
| Check Arquitetura | `vendor/bin/deptrac analyse` |
| Format/Lint JS | `npx biome check . && npx biome format .` |
| Dead Code JS | `npx knip` |
| Testes Unitários | `composer test` |
| Testes E2E | `npx playwright test` |
| Mutation PHP | `vendor/bin/infection` |
| Mutation JS | `npx stryker run` |

---

*Documento gerado como parte da padronização do processo de desenvolvimento.*
