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
