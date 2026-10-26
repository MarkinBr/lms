# Telegram CETSADP: Central de gestao e alertas (design 2026-10-26)

## Pedido
Sete capacidades: /central, /prioridades, /pesquisar, /desempenho, /agendainteligente, /relatoriosemanal e /notificacoes.

## Decisoes
- Reutilizar Laravel, models e autorizacao Telegram existentes. Sem IA externa e sem credenciais novas.
- Nesta entrega: consultas academicas sob demanda e preferencias de notificacoes. Nao programar envios automaticos antes de homologar outbox, retencao D3 e fencing D5.
- /pesquisar NOME reaproveita busca literal /buscar NOME, com no maximo cinco resultados.
- /desempenho mostra ate cinco turmas; /desempenho ID devolve contagem de matriculas ativas e amostra de frequencia de ate cinco registros de chamadas concluidas, sem ranking pessoal.
- /central exibe indicadores e botoes; /prioridades: inscricoes e pendencias estruturais anonimizadas.
- /agendainteligente: sete dias e preferencias, sem prometer lembretes. /relatoriosemanal: contagens dos ultimos sete dias.
- /notificacoes e /notificacoes sim|nao: preferencias administrativas, aviso explicito que envios automaticos estao desativados.
- Exigir chat privado, dono autorizado, outbox protegida e payload resumido. Nenhuma escrita academica.

## Abordagens
1. Incremental (escolhida): ampliar roteador, menu e consultas; alertas automatizados em PR posterior com controles.
2. IA externa: risco e custos sem homologacao, descartado.
3. Cron na outbox atual: risco de retencao e duplicacao, adiado.

## Aceite
Comandos e callbacks protegidos; nenhum CPF, contatos ou documentos no chat; testes negativos e CI verde. Sem merge/deploy/VPS/segredos.