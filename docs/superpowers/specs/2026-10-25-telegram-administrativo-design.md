# Telegram administrativo CETSADP — etapa 1

## Objetivo
Bot privado e somente leitura acadêmica como extensão do painel Laravel/Filament, exclusivo do administrador ativo definido em `TELEGRAM_ADMIN_USER_ID`.

## Alternativas
1. Nutgram: facilita conversas complexas, mas acrescenta dependência e não substitui serviços de autorização.
2. SDK Telegram PHP: abstrai API, com dependência adicional.
3. **HTTP nativo do Laravel (escolhido para o MVP)**: superfície menor, auditável e suficiente para quatro comandos simples.

## Segurança
Webhook com segredo do Telegram; exigir chat privado e remetente compatível; parear via código aleatório com hash e validade de 10 minutos a partir do administrador configurado e autenticado; exigir o ID autorizado e nível admin ativo em cada operação; revogação; deduplicação via update_id após sucesso. Sem configuração válida do dono, negar acesso. Não registrar token em logs ou repositório. Não disponibilizar dados pessoais nem acesso em grupos.

## Escopo
/start e /ajuda; /status; /resumo com contagens agregadas. Sem escrita acadêmica, cobrança, publicação ou acesso a documentos. Exige configuração explícita de token e webhook pela equipe.

## Limitações
Envio HTTP ocorre durante processamento do webhook, com timeout de 5 segundos. A transação reverte o registro da atualização e eventual pareamento quando o envio falha, permitindo reenvio. Timeout após uma entrega efetiva pode duplicar uma resposta; não há garantia de entrega exatamente uma vez. Fila/outbox fica para etapa posterior, especialmente antes de operações de escrita. O servidor deve responder HTTPS publicamente; não foi ativado em produção.
