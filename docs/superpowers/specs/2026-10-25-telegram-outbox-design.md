# Bot Telegram — outbox transacional (Issue #92)

## Objetivo
Separar **commit do banco** de **requisição de rede**. O update, o vínculo e a preferência opt-in permanecem consistentes mesmo se a API do Telegram falhar. Efeitos técnicos apenas; escrita acadêmica ainda não implementada.

## Alternativas
1. Mesmo DB transaction + HTTP: rejeitado por manter locks e rollback de negócio em timeout.
2. Queue/worker com rede: rejeitado nesta fase porque o consumidor do piloto não tem token nem acesso à API.
3. **Outbox no banco + entrega no processo web após commit + comando manual de retry**: escolhido. Exige migração e futura operação controlada para mensagens pendentes.

## Semântica
- **At least once**, não exatamente uma mensagem: timeout após sucesso remoto pode repetir mensagem. `update_id` não repete nenhum efeito de banco.
- A tabela `telegram_outbox` é ligada a `update_id` único e retém payload até entregar/descartar. Tentativas recebem lease de 30 segundos, backoff limitado e expiração de 15 minutos. A resposta do webhook é 200 após persistir, mesmo se Telegram estiver indisponível.
- Mensagens com dados acadêmicos são ligadas ao ID do dono autorizado e têm autorização revalidada ao entregar. Revogação/rebaixamento impedem entrega futura.
- O payload não inclui credenciais do Telegram; depois de entregar/descartar vira NULL. Em pendências, é dado pessoal protegido: backups, retenção e logs devem tratar como confidencial.
- O processo web tenta entrega logo após commit. `php artisan telegram:outbox-drenar` só deve ser executado no container **web com ambiente privado e rede já autorizados**, via operador. Não habilitar scheduler/worker automaticamente.
- Nenhuma operação de criar inscrição, matrícula, nota ou aula integra esta PR. Outbox é **pré-requisito**, não autorização para escrita acadêmica.

## Verificações
Tests com HTTP fake, SQLite e CI. Validar lock e migração em PostgreSQL isolado antes de deploy. Sem operação sobre a VPS ou sistema legado nesta etapa.
