# Gestão de Acessos da Secretaria — Issue #126

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Publicar a central aprovada, exclusiva de Secretaria/admin, para cadastrar e atender acessos de alunos com cadastro completo, e-mail/WhatsApp e credenciais individuais.
**Architecture:** Rotas Blade próprias sob `/secretaria/central`, com entrada no painel Filament atual; serviços de domínio isolados e autorização no servidor. Dados acadêmicos apenas consultados. Acesso assistido separado da verificação de e-mail; revogação por versão persistente de sessão.
**Tech Stack:** Laravel 12, Blade, CSS/JS local, PostgreSQL/SQLite, fila database, PHPUnit.
**Spec:** `docs/superpowers/specs/2026-10-30-secretaria-acessos.md` (aprovada pelo usuário; revisões mais recentes prevalecem).

## Global Constraints
- Somente alunos como alvo; ator deve ser admin ou secretario ativo, sem elevação por super_admin fora desses perfis.
- Não tocar banco de produção em testes, criar cobrança ou alterar matrícula/nota como efeito do atendimento.
- Senha temporária: 8 dígitos criptográficos, 30 minutos, cinco tentativas, uso único; exigir senha definitiva em sessão restrita, não confirmar e-mail via WhatsApp.
- WhatsApp oficial por rascunho confirmado no dispositivo da equipe; e-mail em fila durável. Nenhum segredo em logs/histórico.
- Preservar legado, piloto e a prévia. Issue #126 → branch dedicada → PR; implantação é etapa separada da validação.

## Review Focus
- Sessão já autenticada e SSO não podem contornar bloqueio, revogação ou senha definitiva pendente.
- Troca de contato/perfil entre revisão, confirmação e envio deve invalidar proposta antiga.
- Código vencido, já consumido, reemitido ou com cinco erros nunca libera conta; fila não envia segredo obsoleto.
- E-mail alterado não é verificado automaticamente, e inscrição original não é sobrescrita silenciosamente.
- Cadastro duplicado/duplo clique/falha de entrega não cria segundo aluno nem vínculos acadêmicos.

## Task 1 — Política, cadastro e histórico (implementação principal)
**Files:** `app/Services/Acessos/AtendimentoAluno.php`, `CadastroAluno.php`, migração `2026_10_30_000010_create_gestao_acessos.php`, testes `tests/Feature/Secretaria/GestaoCadastroAcessosTest.php`.
**Interfaces:** AtendimentoAluno métodos estáticos `autorizar(User $ator, ?User $aluno=null):void`, `estado(User $aluno):string`, `registrar(User $ator,User $aluno,string $acao,string $motivo,array $detalhes=[]):void`, `revogar(User $aluno):void`. CadastroAluno `criar(User $ator,array $dados,string $motivo):User`, `atualizar(User $ator,User $aluno,array $dados,string $estado,string $motivo):User`.
- [x] Escrever e executar teste vermelho para autorização, concorrência de revisão, edição allowlist, criação sem matrícula, campos completos e duplicidade.
- [x] Implementar schema aditivo, validação, transações/locks e auditoria apenas de metadados.
- [x] Verificar bloqueio/desbloqueio/sessões, foto privada, inscrições múltiplas e dados de origem.

## Task 2 — Credenciais e autenticação (subtarefa independente)
**Files:** `app/Services/Acessos/CredenciaisAluno.php`, migração `2026_10_30_000011_create_acesso_credenciais.php`, controller público, middleware, alterações de login/SSO e testes `CredenciaisAcessoTest.php`.
**Interfaces:** `emitir(User $ator,User $aluno,string $tipo,string $canal,string $motivo):array` retorna `id`, `segredo`, `url`, `mensagem`, `expira_em`, `canal`; tipos `ativacao`, `recuperacao`, `temporaria`, canais `email`, `whatsapp`, `presencial`. Endpoint público e integração de login definidos pela subtarefa e comunicados para integrar nas rotas.
- [x] Escrever e observar regressões vermelhas (uso único, cinco erros, expiração, revogação e e-mail não confirmado via WhatsApp).
- [x] Implementar emissão/consumo atômicos, bloqueio de senha antiga, troca obrigatória, middleware de versão e registro de sessão no login.
- [x] Verificar autenticação comum/SSO e demais caminhos sem regressões.

## Task 3 — Central visual (subtarefa independente)
**Files:** views `resources/views/secretaria/central/`, CSS/JS `public/css/secretaria-central.css` e `public/js/secretaria-central.js`, testes de renderização quando integrados.
**Interfaces:** Rotas nomeadas `secretaria.central`, `secretaria.acessos.index`, `.show`, `.store`, `.update`, `.foto`, `.acao`, `.entrega`, `.mensagem`, `.email`; view params documentados no controller principal. Layout da prévia em `contexto-secretaria126` é referência de estilo, não código de produção.
- [x] Criar home de cartões, busca/listagem, ficha por abas, formulários de edição/revisão/cadastro e entrega por canal.
- [x] Garantir escape/CSRF, POST para operações, acessibilidade, estados de vazio/erro/carregamento e celular sem largura excedente.

## Task 4 — Completar inscrição pública (subtarefa independente)
**Files:** Application model/controller API V1, view inscrição, `public/js/site/app.js`, migração `2026_10_30_000012_add_campos_complementares_inscricao.php`, testes.
- [x] Regressão vermelha para dados complementares e lote de dois cursos.
- [x] Adicionar campos nomeados, payload, validação, persistência original e tratamento de dados antigos nulos.
- [x] Verificar inscrição e compatibilidade de dados anteriores, sem gerar registros reais.

## Task 5 — Integração de rotas, mensagens e fila (implementação principal)
**Files:** `app/Http/Controllers/Secretaria/CentralAcessosController.php`, `app/Jobs/EntregarAcessoAluno.php`, mail, rotas, entrada Filament.
- [x] Testes vermelhos para isolamento por perfil/ID, cadastro com entrega, snapshots, reautorização na fila e segredos cifrados.
- [x] Implementar controller com autorização, prévia confirmada por estado, fila durável e página de entrega única sem cache; abrir WhatsApp apenas por clique explícito após conferir destino.
- [x] Integrar correção/confirmacão de e-mail, mensagens genéricas, upload de foto e auditoria sem conteúdo sensível.

## Task 6 — Verificação, revisão e entrega
- [x] Pint nos arquivos alterados, suite específica e regressões de auth/aluno/inscrição pertinentes: aceitação final de 183 testes/1.052 verificações.
- [x] Biome, checagens JS, build e navegação local em desktop/celular com conta sintética descartável.
- [ ] PostgreSQL 16 isolado: runner com disputas reais adicionado ao CI; aguarda execução do PR.
- [x] Revisão independente de toda a branch e correção dos achados.
- [ ] Commit/push e PR com `Closes #126`, anexar PR, relatar evidências e limites sem declarar implantação inexistente.

## Decisões de execução
- A aprovação da prévia autoriza executar este plano sem nova rodada de aprovação de escolhas técnicas.
- Worktree gerenciada foi criada, mas o sandbox não permite editar nessa raiz e Git não reconhece worktree no acesso restrito. Usar branch dedicada no checkout original limpo (fallback da skill), sem alterar a branch anterior. A prévia ignorada permanece disponível.
- Separar cadastro, credenciais e interface permite execução paralela com propriedade de arquivos definida; revisão final independente.
