# Telegram administrativo — Etapa 1

## Preparação
- BotFather: criar bot e manter token fora do Git.
- No `.env`: `TELEGRAM_BOT_TOKEN=<token>`, `TELEGRAM_WEBHOOK_SECRET=<segredo_alatorio>` e `TELEGRAM_ADMIN_USER_ID=<id_do_seu_usuario_no_lms>`. Não compartilhe os segredos. O ID é o da tabela `users` do CETSADP, não o ID do Telegram.
- O acesso é exclusivo desse usuário, que também deve estar ativo e ter `nivel=admin`. Outros administradores não acessam a página, não geram códigos e não usam comandos. Sem um ID positivo válido configurado, o acesso permanece bloqueado. Não há seleção automática do primeiro administrador.
- Executar `php artisan migrate`; limpar cache de configuração conforme estratégia de implantação.
- Registrar webhook HTTPS do bot apontando a `https://SEU_DOMINIO/api/telegram/cetsadp/webhook` com o parâmetro `secret_token` igual a `TELEGRAM_WEBHOOK_SECRET`. Fazer esse passo apenas no ambiente autorizado.
- Entrar em `/admin/telegram` com o usuário configurado, gerar código e enviar `/vincular CODIGO` ao bot em conversa privada (validade 10 min).
- Comandos disponíveis: `/ajuda`, `/status` e `/resumo`. Não alteram dados acadêmicos ou financeiros; somente vínculos e controle de processamento são gravados.
- O resumo usa a mesma regra do LMS para matrículas ativas, incluindo os status legados `ativo` e `Ativa`.
- Revogação imediata na página `/admin/telegram`.

## Operação e segurança
- Somente chats privados; desativar, rebaixar ou trocar o ID configurado bloqueia novas consultas e o consumo de códigos pendentes do usuário anterior.
- Depois de trocar o administrador configurado, um código válido do novo dono pode recuperar o mesmo Telegram, removendo o vínculo obsoleto dentro da transação. Se o envio falhar, os vínculos e o código são restaurados para permitir nova tentativa.
- Webhook retorna 403 para segredo incorreto e 503 sem configuração. Exigir HTTPS, limitar rede na borda e armazenar token no gestor de segredos.
- Em caso de suspeita de vazamento, rotacionar token no BotFather e segredo do webhook.
- Atualizações concluídas são descartadas pelo identificador `update_id`. Registro, pareamento e envio ficam na mesma transação: falhas de conexão, respostas HTTP de erro ou `ok=false` revertem as gravações e retornam 503, permitindo reenvio do Telegram. O erro devolvido não inclui token nem URL da API.
- O envio tem timeout de 5 segundos e acontece durante a transação. Se o Telegram tiver aceitado a resposta antes de um timeout, uma repetição pode entregar a mesma resposta novamente. Não há garantia de entrega exatamente uma vez; esta etapa não executa alterações acadêmicas. Fila/outbox poderá substituir esse fluxo quando houver mais volume ou operações de escrita.
- Para longo prazo, limpar `telegram_updates` antigos com tarefa programada após janela de retenção definida; uma atualização removida poderá ser processada novamente se for reapresentada.
- Logs devem ocultar headers e payload de credenciais. Não habilitar body logging neste endpoint.
- Não ativar webhook em produção ou publicar dados reais antes de homologar autenticação, infraestrutura, testes e revisão de segurança.

## Verificação isolada
- PHPUnit: `php artisan test --filter=TelegramAdministrativoTest` com `APP_ENV=testing`, SQLite em memória, mail/cache/sessão em array e Telegram simulado. Nunca executar testes no banco de produção.
- A CI inclui o teste do bot no filtro de testes direcionados, além da validação de sintaxe e Pint.
- Não é necessário Nutgram, IA local, API paga ou outro servidor para os comandos desta etapa; o cliente HTTP do Laravel é suficiente.

## Ativação no piloto publicado

As credenciais ficam somente em `/srv/portal84/telegram.env`, com permissão 0600. O arquivo contém `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET` e `TELEGRAM_ADMIN_USER_ID`. O token é inserido pelo titular diretamente no VS Code conectado à VPS; não transita pela conversa, Git ou argumentos de processos.

Aplicar o override `docker/telegram-piloto.compose.yml` depois dos arquivos de homologação e piloto, carregando os arquivos privados com `--env-file`. Somente o web recebe as credenciais e participa da rede adicional de saída; PostgreSQL, worker e heartbeat mantêm suas redes isoladas. Nunca imprimir `docker compose config`, ambientes de containers ou exceções que contenham a URL da API; validar com `config --quiet`.

O Nginx encaminha apenas POST no caminho exato `/api/telegram/cetsadp/webhook`. O Laravel exige o segredo do header antes de processar o conteúdo. Registrar o webhook por HTTPS com `secret_token` e `allowed_updates=["message"]`, preservando atualizações pendentes. Validar token/identidade com `getMe`, registro com `getWebhookInfo`, rejeição sem segredo e acesso exclusivo do administrador escolhido.

Depois da ativação, gerar o código de pareamento do administrador autorizado e enviar `/vincular CODIGO` em conversa privada com o bot. O código expira em dez minutos. A confirmação da conexão técnica não significa que uma conta Telegram já esteja vinculada.


## Etapa 2 — menu e consultas (Issue #86; candidata em PR separado)

- `/menu` ou `/ajuda`: botões de alunos, inscrições, matrículas, turmas e aulas, com até 5 registros por página e no máximo 20 páginas. `/status` e `/resumo` continuam disponíveis.
- `/buscar NOME`: busca limitada a 2–60 caracteres e cinco correspondências de nome. `/aluno ID`: ficha acadêmica compacta com situação cadastral e até cinco matrículas.
- `/notas ID`: até cinco registros de notas **legadas**, vinculados a `user_id` (não há vínculo acadêmico inequívoco com uma disciplina por ID). `/frequencia ID`: resumo exclusivamente de chamadas concluídas por matrícula (até cinco matrículas).
- Botões são `callback_query` e só funcionam em chat privado do administrador previamente vinculado e ativo. A autorização é refeita antes de cada resposta; revogar/desativar bloqueia consultas posteriores.
- Não são mostrados CPF, telefone, endereço, documentos, pagamentos, senhas nem planilhas completas. Consultas aprofundadas permanecem no painel autenticado. A busca ainda não oferece pesquisa por CPF, para reduzir exposição.
- Implementação só de leitura nos models existentes. Não refaz pareamento, não altera webhook registrado nem exige migração adicional. O registro de `telegram_updates` é uma escrita técnica de idempotência, não de dados acadêmicos.
- O envio para o Telegram continua na transação atual. Antes de **qualquer** fluxo futuro que escreva dados acadêmicos, adotar outbox/entrega separada e revalidar titular em cada confirmação.
- Validar com `php artisan test --filter='TelegramMenuConsultaTest|TelegramAdministrativoTest'` e `vendor/bin/pint --test` nos arquivos PHP tocados em ambiente isolado. Jamais executar na base da VPS. Sem deploy automático nesta entrega.


## Etapa 3 — consultas operacionais (Issue #88)

- `/pendencias`: inscrições em análise e contagens de inconsistências estruturais já acompanhadas pelo serviço `PainelAcademicoQuery`. Não corrige ou altera registros.
- `/agenda`: até cinco próximos encontros do serviço acadêmico existente, sem compartilhar link de transmissão.
- `/cursos`: primeiros cinco cursos ativos por título.
- `/turma ID`, `/matricula ID` e `/inscricao UUID`: fichas resumidas por identificador explícito e único. Matrículas podem ter vínculos adicionais em `matricula_turma` e exigem consulta no painel para detalhes.
- Menu com botões para pendências, agenda e cursos. Sem CPF, telefone, endereço, documentos, dados bancários, valores ou credenciais.
- Nesta etapa não há escrita acadêmica, migrações, alteração do pareamento, publicação nem deploy. A autorização exclusiva do administrador vinculado continua sendo verificada antes das consultas.
- Homologação: `php artisan test --filter='TelegramOperacionalTest|TelegramMenuConsultaTest|TelegramAdministrativoTest'` e Pint em SQLite isolado, sem acesso ao banco real. PostgreSQL e Telegram real continuam pendentes de validação segura.


## Etapa 3.5 — central de consultas (Issue #90, PR draft)

- `/hoje`: painel diário consolidado usando `PainelAcademicoQuery`, inscrições em análise e encontros próximos; indicadores do banco conectado, inclusive fixtures em homologação.
- `/agenda7`: cinco encontros programados para os próximos sete dias, sem link privado. `/relatorios`: até cinco cursos ativos e suas matrículas ativas.
- `/aulaspendentes`: total e até cinco aulas com `liberada=false`; somente informação, sem publicação.
- `/filtro status ativo`, `/filtro status inativo`, `/filtro curso ID`, `/filtro turma ID`: listas pequenas. O vínculo com turma considera o campo legado e pivô ativo; para detalhes consulte a Secretaria.
- A busca `/buscar NOME` usa `LOWER` e escape explícito para os caracteres `%` e `_`. Não é um sistema de busca sem acentos; `joao` e `João` permanecem pesquisas diferentes, e o comportamento deve ser homologado em PostgreSQL isolado.
- O comando **/alertas** consulta a preferência; **/alertas sim** e **/alertas nao** registram ou revogam consentimento em `telegram_alerta_preferencias` (migração nova, vinculada ao usuário administrativo). O cadastro do consentimento não agenda nem envia mensagens. **Alertas automáticos permanecem desativados e não implementados**: é necessário definir horário, outbox/idempotência e qual consumidor terá rede/credenciais antes de agendar envios. O scheduler piloto permanece heartbeat isolado.
- Este PR não altera pareamento, dados acadêmicos, Docker, webhook, banco do piloto ou sistema legado; não foi implantado. Nova migração de preferências só será aplicada se futuramente aprovada para homologação. Homologar com testes SQLite e CI antes de eventual deploy.

- A busca guiada apresenta botões para abrir a ficha acadêmica do aluno pelo ID; a ficha pode direcionar para notas e frequência. Os novos botões usam edição da própria mensagem (`editMessageText`) quando disponível, com retorno seguro de nova mensagem em caso de rejeição da edição.
- `/filtros`: escolhas guiadas de alunos por status, cinco cursos e cinco turmas. Consultas continuam paginadas e sem exposição de documentos.


## Etapa 4 — outbox durável para respostas Telegram (Issue #92, em revisão)

**Esta seção substitui, para a candidata da Issue #92, a semântica antiga de rollback por falha de envio descrita na Etapa 1.**

- Cada update é identificado em `telegram_updates` e a resposta em `telegram_outbox`, dentro de uma transação local. O HTTP com a API do Telegram acontece **após o commit**, sem rollback de pareamento ou opt-in por falha externa.
- O webhook confirma atualização persistida com HTTP 200 mesmo se a tentativa de envio falhar; falhas deixam outbox pendente para reentrega. Um update duplicado não gera nova mensagem na outbox.
- Mensagens de consultas com vínculo de administrador são descartadas se a conta estiver desativada, rebaixada ou desvinculada no momento da entrega.
- Outbox usa lease e retentativa com atraso, prazo curto de validade e limpeza do payload após entrega/descarte. **Uma mensagem pode ser entregue mais de uma vez se ocorrer timeout após aceite remoto**; isso não duplica o efeito no LMS.
- Operação manual futura: somente no serviço web autorizado e com credenciais, executar `php artisan telegram:outbox-drenar --limite=20`. **Não** habilitar cron, scheduler, worker ou rede de saída nova sem processo de implantação.
- A migração `create_telegram_outbox_table` exige revisão e homologação no PostgreSQL isolado antes de aplicar em qualquer banco real.
- Nenhuma ação acadêmica de escrita foi habilitada. Antes de permitir aprovações/matrículas é necessário ainda implementar confirmações de uso único e trilha de auditoria.


## Etapa 5 — confirmação de teste e trilha de auditoria (Issue #94)

- `/testeconfirmacao` cria uma proposta **de simulação sem efeito acadêmico**; o bot mostra o resumo e dois botões: **Confirmar** e **Cancelar**.
- Cada proposta possui código aleatório de 128 bits, somente SHA-256 no banco, vinculado ao usuário administrativo e chat privado, expira após dez minutos.
- A confirmação e o cancelamento usam `lockForUpdate`, status único e revalidação do dono. Um clique repetido não executa a decisão outra vez. Um código expirado não confirma.
- A preparação e a primeira decisão ficam em `telegram_auditoria`, com transição de estado, ator e `update_id`. Sem token, CPF, documentos ou payload acadêmico. Auditoria não é removida na revogação.
- A revogação cancela confirmações pendentes do usuário. Operações acadêmicas não foram conectadas a esse fluxo.
- Requer migração `telegram_confirmacoes` e `telegram_auditoria`. **Não migrar o banco da VPS sem homologação e backup**. Também requer revisão de concorrência em PostgreSQL isolado antes de habilitar escritas reais.


## Etapa 6 — pré-análise de inscrição (Issue #96)

- `/revisarinscricao UUID` consulta a inscrição pelo ID inequívoco e prepara revisão técnica apenas se o estado for `em_analise` ou `aprovado`. O bot apresenta candidato resumido, curso, modalidade, status e **contagem** de documentos. Não exibe CPF, e-mail, telefone, documentos, links nem valores.
- Botões **Registrar revisão** e **Cancelar** usam o controle transacional e auditoria já existentes. A decisão é de uso único e expira após dez minutos.
- A preparação armazena `application_id` e SHA-256 do estado observado (ID, status, última atualização, curso/modalidade). Se a inscrição mudar antes de confirmar, a proposta se torna `obsoleto` e não conclui. Não é uma decisão acadêmica.
- **Registrar revisão NÃO aprova, rejeita nem matricula**, não cria aluno, não envia e-mail e não aciona `AprovarInscricaoService`. A aprovação/rejeição real exige issue separada e homologação de efeitos externos, identidade e concorrência.
- A migração `2026_10_25_000009_add_contexto_to_telegram_confirmacoes.php` deve ser validada no PostgreSQL isolado antes de qualquer implantação. Base dependente de PRs #95 e anteriores. Sem deploy, merge ou alterações no legado.


## Auditoria pós-revisão independente (Issues #98–#104)
- Correções #98: callback não reabre mensagem entregue quando falha confirmação visual; UUID estrutural 8-4-4-4-12; fixtures não assumem admin ID 1.
- Segurança #99: `requer_autorizacao` persiste independentemente do `user_id`. Se o usuário protegido desaparecer, descartar a resposta. Registros anteriores com classificação desconhecida são tratados como protegidos.
- Consistência #102: a proposta de pré-análise invalida-se ao mudar dados exibidos do curso/modalidade, nome do candidato ou conjunto/versionamento/validação dos documentos ativos. Só é armazenado hash, sem dados pessoais de documentos.
- Revogação #104: cada confirmação pendente cancelada por revogação ganha registro append-only em `telegram_auditoria_revogacoes`; essa tabela não inventa `update_id` do Telegram. O histórico restringe exclusão física das entidades relacionadas.
- Limitações: snapshots das relações não garantem atomicidade contra mutações simultâneas sem travas compartilhadas nos serviços acadêmicos; validar em PostgreSQL isolado. Mensagens já entregues não podem ser revogadas do histórico do Telegram.
- Sem merge, sem deploy, sem mudanças de banco da VPS e sem aprovação acadêmica real.


## Auditoria independente Claude — correção D1 (Issue #110)
- Mensagens nascidas **após consulta autenticada** são classificadas como protegidas na origem (por padrão), independentemente de o vínculo ainda existir no momento do enfileiramento. Se o vínculo foi revogado durante a consulta, a mensagem mantém `requer_autorizacao=true` e é descartada pela Outbox quando `user_id` for nulo.
- Somente as respostas literais de acesso negado ou erro no código de vinculação são explicitamente registradas como mensagens genéricas. Um vínculo bem-sucedido recebe resposta protegida.
- Regressão determinística injeta `revogar()` durante a geração de `/aluno` e verifica que **nenhuma requisição HTTP Telegram** foi feita com o conteúdo.
- Ainda não resolvidos por este PR: D2 (revogação na inativação), D3 (purga sem rede), D4 (UUID de /inscricao), D5 (fencing da Outbox), D6 (editMessageText), G1 (lacuna do CI), efeitos externos do serviço acadêmico.
- Não houve implantação e não foram habilitadas operações de escrita acadêmica.

## Auditoria independente — D2: inativação e rebaixamento do administrador (Issue #112)

- Ao atualizar **por Eloquent** `User::update(['ativo' => false])` ou `User::update(['nivel' => ...])` para um perfil que não é administrador, `TelegramUsuarioObserver` revoga o vínculo, anula códigos de pareamento em aberto ao excluir a linha de `telegram_vinculos`, cancela propostas pendentes com registro em `telegram_auditoria_revogacoes` e descarta respostas protegidas ainda pendentes. Nenhuma mensagem é enviada pela revogação.
- Ao reativar a mesma conta ou promover novamente para administrador, **não é permitido reutilizar o chat anterior**: deve ser emitido um novo código e o administrador precisa executar `/vincular`.
- O método manual `revogar(User $usuario)` continua restrito ao proprietário ativo; a operação interna `revogarVinculo(int $usuarioId)` não depende de a conta continuar elegível e só deve ser chamada por código servidor confiável.
- A exclusão via `$usuario->delete()` também aciona a limpeza; vínculos de auditoria com `restrictOnDelete` podem impedir a exclusão física de usuários que possuam propostas — comportamento de retenção já existente e que requer política específica.
- Recuperação manual: um operador com acesso autorizado ao console pode executar `php artisan telegram:revogar ID` para remover vínculo remanescente de conta já inativa/rebaixada. O comando não depende de SMTP, token de bot ou rede de saída.
- **Limitação importante:** eventos Eloquent **não** são emitidos por atualização massiva `User::query()->where(...)->update(...)`, `DB::table('users')->update(...)` ou SQL direto. Esses fluxos precisam ser inventariados e migrados para serviços que revogam explicitamente o Telegram; a correção atual não garante revogação nesses caminhos.
- Não alterar produção, Docker, scheduler ou banco real sem homologação das migrações anteriores e dos controles de auditoria.


## Menu de comandos do Telegram — Issue #114 (candidata não implantada)

- O catálogo `CatalogoComandosTelegram::listar()` descreve somente comandos implementados na branch candidata; os nomes no menu do Telegram **não publicam o código no servidor**.
- Os comandos com parâmetros (`/buscar`, `/aluno`, `/notas`, `/frequencia`, `/turma`, `/matricula`, `/inscricao`, `/revisarinscricao` e `/filtro`) respondem com um exemplo de uso quando enviados sem argumento. A pré-análise não aprova nem matricula.
- O novo comando Artisan `php artisan telegram:comandos-publicar --simular` confere o catálogo **sem executar HTTP**. Já `php artisan telegram:comandos-publicar` chama `setMyCommands`, limitado ao **chat privado atualmente vinculado** ao proprietário autorizado; não publica comandos globais para outros usuários.
- Ambos dependem do código candidato implantado. O comando real requer token presente somente no processo web com acesso de rede Telegram. Não executá-lo via worker/scheduler, na estação local ou antes de homologação; não imprimir token, `docker compose config` ou arquivos `.env`.
- Para **botões interativos** do menu avançado funcionarem, o webhook do piloto precisará aceitar os tipos `message` **e** `callback_query` em `allowed_updates`. A configuração original do piloto aceitava somente `message`. Alterar o webhook exige planejamento/autorizações e teste de integridade; **esta PR não altera o webhook**.
- Antes de implantação: revisar dependências de PRs empilhados, homologar migrações em PostgreSQL isolado, fazer backup da base do piloto, planejar rollback e validar a política LGPD. A publicação é uma **ação manual pós-implantação**, não um efeito automático de CI/merge.
- Depois de homologar e aplicar o código no serviço web, executar `php artisan telegram:comandos-publicar --simular` e só então `php artisan telegram:comandos-publicar`. Testar `/menu`, `/buscar`, `/pendencias` e `/revisarinscricao` com identificadores de teste, em conversa privada, sem gerar matrículas.
- Se o usuário perder o acesso, o servidor continuará negando consultas; um menu de comandos já registrado no cliente Telegram pode permanecer visível até ser atualizado. Visibilidade do menu não concede autorização.


## Central administrativa ampliada — Issue #116 (PR candidata)

- `/central`: contagens agregadas de alunos, matrículas, cursos, turmas, inscrições em análise e aulas não liberadas; botões para acesso rápido a outras áreas.
- `/prioridades`: apenas contagens de inscrições, aulas e pendências curriculares/estruturais. Não representa diagnóstico automatizado de gravidade.
- `/pesquisar NOME`: reaproveita a pesquisa literal e limitada a cinco pessoas por nome. `/pesquisar` sem argumento retorna orientação.
- `/desempenho`: seleciona turma com botões ou recebe `/desempenho ID`; exibe total de matrículas ativas relacionadas e no máximo cinco amostras de percentual de frequência para chamadas concluídas. Não fornece ranking nem substitui revisão pedagógica. Identificadores técnicos são resumidos.
- `/agendainteligente`: agenda de sete dias com até cinco eventos, reaproveitando consulta acadêmica anterior.
- `/relatoriosemanal`: relatório de sete dias móveis com novas inscrições, novas matrículas e datas de encontros; não confundir novas matrículas com aprovação/rejeição acadêmica. Envio somente sob demanda.
- `/notificacoes`, `/notificacoes sim`, `/notificacoes nao`: registro de preferência opt-in por administrador vinculado. **Nenhum disparo automático está habilitado nesta PR**. O controle pode também ser acessado pelo botão do menu.
- Todos os caminhos usam autorização existente, chat privado e respostas outbox protegidas. Só há escrita técnica de idempotência/saída e preferência; nenhuma escrita acadêmica.
- Para tornar os botões funcionais no piloto, o webhook precisa aceitar `callback_query` além de `message`. Não executar mudanças no webhook ou no scheduler sem plano de implantação homologado.
- Dependências a resolver antes de ativar alertas reais: limpeza de payload expirado (auditoria D3), fencing de workers de envio (D5), limites de dados, deduplicação de alertas e agendamento confiável. Ativação automática só em uma entrega separada de segurança.
