# Notificações automáticas Telegram CETSADP — Design 2026-10-26

## Escopo
Aviso diário de pendências/agenda e relatório semanal agregados. Somente chat privado do proprietário autorizado com preferência ativada; identidade e token validados a cada execução. Sem nomes, telefone, CPF, documentos, IDs individuais ou dados financeiros.

## Decisão e tradeoffs
Canal de envio especializado para alertas, separado de telegram_outbox de respostas (achados D3/D5 ainda pendentes). Estado mínimo: user_id, tipo, periodo, status, timestamps; nunca reter conteúdo. Reserva atômica sob índice único antes de tentar HTTP, impedindo duplicidade entre workers, inclusive em resposta ambígua. A entrega é **at-most-once**: erros de rede não disparam retentativa automática na mesma janela, evitando duplos envios. Alguns alertas podem se perder. Não substitui o webhook nem interfere com confirmações acadêmicas.

## Ativação
Flag de ambiente TELEGRAM_AVISOS_AUTOMATICOS=false por padrão. Agenda cadastrada no Laravel somente se habilitado, com horários 08:00 diariamente e 08:05 segunda-feira em America/Sao_Paulo. Só habilitar no processo autorizado com Telegram token, credenciais fora de repositório e agenda/scheduler único. Primeiro testar --simular (nenhuma mutação/nem HTTP). Sem implantação/flag/cron real nesta PR.

## Limites
Conteúdo da mensagem construído em tempo de envio e não persistido; dados somente agregados. Opt-out imediato antes de enviar; revogar vínculo/conta bloqueia novas execuções. Uma revogação simultânea entre última checagem e chamada externa é limite residual e deve ser avaliada no rollout com controle de concorrência/locking adicional. Retenção de registro de deduplicação deve ser definida antes da limpeza. 
