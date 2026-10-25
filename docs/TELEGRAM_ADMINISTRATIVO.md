# Telegram administrativo — Etapa 1

## Preparação
- BotFather: criar bot e manter token fora do Git.
- No `.env`: `TELEGRAM_BOT_TOKEN=<token>` e `TELEGRAM_WEBHOOK_SECRET=<segredo_alatorio>`. Não compartilhe estes valores.
- Executar `php artisan migrate`; limpar cache de configuração conforme estratégia de implantação.
- Registrar webhook HTTPS do bot apontando a `https://SEU_DOMINIO/api/telegram/cetsadp/webhook` com o parâmetro `secret_token` igual a `TELEGRAM_WEBHOOK_SECRET`. Fazer esse passo apenas no ambiente autorizado.
- Entrar em `/admin/telegram` com usuário `nivel=admin` ativo, gerar código e enviar `/vincular CODIGO` ao bot em conversa privada (validade 10 min).
- Comandos disponíveis: `/ajuda`, `/status` e `/resumo`. Sem escrita de dados.
- Revogação imediata na página `/admin/telegram`.

## Operação e segurança
- Somente chats privados; administradores inativos ou desautorizados perdem acesso imediatamente.
- Webhook retorna 403 para segredo incorreto e 503 sem configuração. Exigir HTTPS, limitar rede na borda e armazenar token no gestor de segredos.
- Em caso de suspeita de vazamento, rotacionar token no BotFather e segredo do webhook.
- Atualizações repetidas são descartadas pelo identificador `update_id`. Para longo prazo, limpar `telegram_updates` antigos com tarefa programada após janela de retenção definida.
- Logs devem ocultar headers e payload de credenciais. Não habilitar body logging neste endpoint.
- Não ativar webhook em produção ou publicar dados reais antes de homologar autenticação, infraestrutura, testes e revisão de segurança.
