# Operações administrativas pelo Telegram — Issue #120

O bot usa o HTTP e o banco do Laravel. Não depende de IA, assinatura ou serviço pago adicional. Somente o administrador ativo configurado em `TELEGRAM_ADMIN_USER_ID`, vinculado ao chat privado, pode operar. O sistema legado e o novo portal continuam em paralelo.

## Operações deste incremento

Abra `/administrar` (também disponível no `/menu`) e escolha uma operação para receber instruções. Tocar no botão apenas mostra como preencher; não altera registros.

| Comando | Resultado após confirmação |
|---|---|
| `/novoaviso TÍTULO \| MENSAGEM` | Publica aviso geral aos alunos. |
| `/novoevento TÍTULO \| AAAA-MM-DD HH:MM \| DESCRIÇÃO` | Cria evento acadêmico geral no calendário dos alunos, no fuso do LMS. Não publica notícia no site institucional. |
| `/novaaula DISCIPLINA_ID \| TÍTULO \| URL_VIDEO \| URL_PDF` | Cria aula em rascunho na disciplina/módulo; aceita vídeo, PDF ou ambos. Use `-` para material ausente. URLs HTTPS, até 255 bytes; não faz download nem transcrição. |
| `/liberaraula AULA_ID` | Libera imediatamente uma aula pendente. Se ela mudar após a prévia, a confirmação é rejeitada. |
| `/novachamada TURMA_ID DISCIPLINA_ID AAAA-MM-DD` | Cria chamada em rascunho, verificando curso da turma e disciplina e duplicidade na data. |
| `/registrarpresenca CHAMADA_ID MATRICULA_ID presente` | Registra frequência de matrícula com vínculo ativo. Também aceita `falta` ou `falta_justificada`. Não sobrescreve presença existente. |
| `/concluirchamada CHAMADA_ID` | Conclui chamada com frequência registrada para todos os alunos ativos, sem registros adicionais. |

Use IDs do banco conectado: IDs locais e da VPS podem diferir. `/buscar`, `/aluno`, `/matricula`, `/turma`, `/aulaspendentes` e o painel autenticado ajudam a encontrar os registros. `/notas`, `/frequencia`, `/relatorios`, `/hoje`, `/agenda7` e `/alertas` continuam disponíveis.

## Confirmação e proteção

- Antes da escrita, o bot mostra uma prévia e os botões confirmar/cancelar, válidos por dez minutos. Callback contém somente código aleatório; hash fica no banco.
- A proposta fica criptografada em `telegram_publicacoes`. Cancelamento, expiração consumida e revogação descartam seu conteúdo. A trilha de auditoria registra ação e transições, sem guardar texto pessoal. A confirmação concluída preserva tabela e ID do registro afetado em `recurso_tipo` e `recurso_id`.
- Conclusão de chamada rejeita mudanças na data, frequência ou lista de alunos após a prévia. Bloqueia turma e frequências durante a validação final, coordenando com o serviço de vínculo de matrículas. O CI PostgreSQL disputa essa conclusão com um novo vínculo em outro processo.
- Confirmação bloqueia vínculo/proposta e revalida autorização e domínio dentro da transação. Updates repetidos e cliques adicionais não repetem a escrita.
- Mensagens são entregues pela outbox após a transação; a confirmação não depende de uma chamada HTTP enquanto os dados acadêmicos estão bloqueados.
- Avisos/eventos são gerais: revise a prévia antes de confirmar. Não existe disparo de comunicação em massa neste incremento.

## Etapas ainda abertas da Issue #120

Cadastro/edição de alunos, recuperação de acesso pelo bot, decisão efetiva de inscrições, lançamento/correção de notas, notícias institucionais, consultas financeiras e alertas automáticos ainda não são operações de escrita deste incremento. `/revisarinscricao` continua sendo pré-análise; `/notas` apenas consulta notas legadas.

Para implementar as próximas etapas, reutilizar `AprovarInscricaoService` e os fluxos existentes de senha, sem copiar a rotina de cadastro rápido com senha padrão. Antes de permitir notas, resolver o contrato entre notas legadas e avaliações: o modelo atual calcula situação `Recuperação`, que precisa ser compatível com a tabela de produção. Não criar lançamentos financeiros ou alterar pagamentos apenas para completar o menu.

## Verificação e implantação

1. Rodar Pint nos PHP alterados e a suíte Telegram com `php vendor/phpunit/phpunit/phpunit -c phpunit.xml --filter Telegram --no-coverage`. O `phpunit.xml` força SQLite em memória, correio em array e fila síncrona; testes simulam a API Telegram.
2. Aguardar os jobs do PR: operações administrativas, regressões existentes, concorrência PostgreSQL 16 efêmero, infraestrutura Linux e Playwright. A estação Windows não substitui esse teste PostgreSQL.
3. Integrar pela cadeia de PRs do Telegram. Esta branch parte de `feat/114-telegram-menu-comandos` (PR #115); não substituir arquivos diretamente a partir de `main`, que ainda não contém essa cadeia.
4. Antes de implantar no piloto, identificar a imagem atual, fazer backup verificável do banco/arquivos, testar migrações em banco PostgreSQL descartável e manter imagem/configuração anteriores para rollback. Não copiar banco local nem alterar o portal legado.
5. Aplicar a migração aditiva `2026_10_26_000001_create_telegram_publicacoes_table.php` antes de iniciar este código, pois revogação também usa a nova tabela. Preservar `APP_KEY` para descriptografar propostas e outbox.
6. Conferir webhook aceitando `message` e `callback_query`, executar `telegram:comandos-publicar --simular` e publicar o catálogo privado a partir do processo web autorizado. Não expor token, `.env` ou saída completa de configuração Docker.
7. Homologar pelo chat privado com registros sintéticos: menu, cancelamento, confirmação expirada, revogação e dupla confirmação. Nenhum desses testes deve publicar conteúdo real para alunos.

Rollback do código conserva a tabela aditiva e a auditoria. Não executar `migrate:rollback` em produção como atalho: a tabela pode conter propostas pendentes da versão nova.
