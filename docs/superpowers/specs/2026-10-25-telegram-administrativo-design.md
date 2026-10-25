# Telegram administrativo CETSADP — etapa 1

## Objetivo
Bot privado e somente leitura como extensão do painel Laravel/Filament, para administradores ativos.

## Alternativas
1. Nutgram: facilita conversas complexas, mas acrescenta dependência e não substitui serviços de autorização.
2. SDK Telegram PHP: abstrai API, com dependência adicional.
3. **HTTP nativo do Laravel (escolhido para o MVP)**: superfície menor, auditável e suficiente para quatro comandos simples.

## Segurança
Webhook com segredo do Telegram; exigir chat privado e remetente compatível; parear via código aleatório com hash e validade de 10 minutos a partir de um administrador autenticado; exigir nível admin ativo a cada consulta; revogação; deduplicação via update_id. Não registrar token em logs ou repositório. Não disponibilizar dados pessoais nem acesso em grupos.

## Escopo
/start e /ajuda; /status; /resumo com contagens agregadas. Sem escrita acadêmica, cobrança, publicação ou acesso a documentos. Exige configuração explícita de token e webhook pela equipe.

## Limitações
Envio HTTP ocorre durante processamento do webhook; a tabela de deduplicação é registrada antes do envio. Uma falha de rede após gravação pode exigir nova mensagem do usuário; fila transacional fica para etapa posterior. O servidor deve responder HTTPS publicamente; não foi ativado em produção.
