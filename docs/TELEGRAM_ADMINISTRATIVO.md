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
