#!/usr/bin/env bash
# Cria as issues de docs/BACKLOG.md no GitHub via gh CLI.
#
# Pré-requisitos:
#   1. gh CLI instalado (https://cli.github.com)
#   2. `gh auth login` já feito
#   3. Rodar este script de dentro de um repositório já com remote no GitHub
#      (ou passar --repo dono/repo)
#
# Uso:
#   ./scripts/create-github-issues.sh
#   ./scripts/create-github-issues.sh --repo minha-org/cetsadp-lms

set -euo pipefail

REPO_FLAG=()
if [[ "${1:-}" == "--repo" && -n "${2:-}" ]]; then
  REPO_FLAG=(--repo "$2")
fi

command -v gh >/dev/null 2>&1 || { echo "gh CLI não encontrado. Instale: https://cli.github.com"; exit 1; }
gh auth status >/dev/null 2>&1 || { echo "Rode 'gh auth login' primeiro."; exit 1; }

echo "Garantindo labels padrão..."
gh label create bug         --color d73a4a --description "Correção"    --force "${REPO_FLAG[@]}" || true
gh label create enhancement --color a2eeef --description "Melhoria"    --force "${REPO_FLAG[@]}" || true
gh label create feature     --color 0e8a16 --description "Nova função" --force "${REPO_FLAG[@]}" || true
gh label create chore       --color cfd3d7 --description "Manutenção/infra" --force "${REPO_FLAG[@]}" || true
gh label create decision    --color fbca04 --description "Decisão/spike necessária" --force "${REPO_FLAG[@]}" || true
gh label create epic        --color 5319e7 --description "Agrupa várias issues menores" --force "${REPO_FLAG[@]}" || true

create_issue() {
  local title="$1" body="$2" labels="$3"
  echo "-> $title"
  gh issue create --title "$title" --body "$body" --label "$labels" "${REPO_FLAG[@]}"
}

create_issue \
  "Inicializar Git e criar repositório no GitHub" \
  $'Pré-requisito para o resto do backlog: o projeto ainda não tem `.git` nem remoto.\n\nVer detalhes em `docs/BACKLOG.md#1`.' \
  "chore"

create_issue \
  "Limpar artefatos soltos na raiz do projeto (arquivos-fragmento + SQLite solto)" \
  $'Raiz tem arquivos vazios como `default(true)`, `cascadeOnDelete()` etc. (erro de shell) e um banco SQLite (`cetsadp_lms`) fora do lugar.\n\nVer detalhes em `docs/BACKLOG.md#2`.' \
  "chore"

create_issue \
  "Formulários públicos não enviam dados ao backend (modo demonstração)" \
  $'Os 5 formulários do site (`inscForm`, `loginSecForm`, `loginAluForm`, `nlForm`, `contactForm`) têm `e.preventDefault()` fixo em `app.js` e nunca chegam ao backend. Formulário de inscrição também aponta `action` para o endpoint de contato e a maioria dos campos não tem `name=`.\n\nVer detalhes e critérios de aceite completos em `docs/BACKLOG.md#3`.' \
  "bug"

create_issue \
  "Decidir arquitetura do novo frontend (substituir produção ou camada adicional?)" \
  $'Decisão em aberto que afeta todas as issues de UI. Ver `docs/BACKLOG.md#4`.' \
  "decision"

create_issue \
  "Painel administrativo (CMS) para os partials do site" \
  $'CRUD no Filament para o conteúdo de `resources/views/site/partials` e `pages`. Ver `docs/BACKLOG.md#5`.' \
  "feature"

create_issue \
  "[epic] Motion, skeleton e lazy loading em toda a interface" \
  $'Ver racional completo em `docs/MOTION_GUIDELINES.md` e sub-itens em `docs/BACKLOG.md#6`.' \
  "epic,feature"

create_issue \
  "[epic] Observabilidade (Sentry + OpenTelemetry)" \
  $'Ver `docs/QUALIDADE_E_OBSERVABILIDADE.md` e sub-itens em `docs/BACKLOG.md#7`.' \
  "epic,chore"

create_issue \
  "[epic] Qualidade de código (Pint, PHPStan, Deptrac, Biome, Knip, Commitlint)" \
  $'Ver `docs/QUALIDADE_E_OBSERVABILIDADE.md` e sub-itens em `docs/BACKLOG.md#8`.' \
  "epic,chore"

create_issue \
  "[epic] Testes automatizados e cobertura (PHPUnit, Playwright, Codecov)" \
  $'Ver `docs/QUALIDADE_E_OBSERVABILIDADE.md` e sub-itens em `docs/BACKLOG.md#9`.' \
  "epic,chore"

create_issue \
  "CI/CD no GitHub Actions (lint + testes em PR, deploy em merge)" \
  $'Workflow inicial em `.github/workflows/ci.yml`. Ver `docs/BACKLOG.md#10`.' \
  "chore"

echo ""
echo "Pronto. Rode 'gh issue list' para conferir."
