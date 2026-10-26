# Conclusão administrativa Telegram — Issue #120

> Execução autorizada pelo usuário: concluir operações e atualizar piloto. Aplicar testes antes da implementação e revisão final.

**Objetivo:** administrar alunos, inscrições, notas e notícias pelo bot privado, sem serviços pagos e preservando o legado.

**Arquitetura:** reutilizar confirmação criptografada e auditada; serviços separados por domínio, autorização sob lock e snapshots; saída de e-mail durável fora da transação.

**Tecnologias:** Laravel 12, SQLite isolado em testes e PostgreSQL 16 descartável na homologação.

- [x] Cadastro/edição restrita de alunos e recuperação por e-mail verificado, com testes.
- [x] Decisão efetiva de inscrições usando serviço oficial, notificações somente após commit, com testes de repetição e obsolescência.
- [x] Lançamento/correção de notas, contrato legado compatível e validação de vínculo acadêmico.
- [x] Notícias persistidas e renderizadas com escape no site institucional.
- [x] Integrar comandos/menu, financeiro e resumo diário opt-in.
- [ ] Pint, suíte Telegram/negócio, revisão independente, concorrência PostgreSQL e CI.
- [ ] Commit convencional e atualização do PR #121 mantendo referência à Issue #120.
- [ ] Backup validado, restauração/ensaio descartável, implantação/rollback e checagem do piloto sem alterar dados reais como teste.

Ruling: o escopo e a execução já foram aprovados na conversa; não repetir aprovação de design. Serviços paralelos têm arquivos distintos; o implementador principal integra os arquivos comuns.

Revisão: separar ativação e comunicação da decisão em jobs distintos; bloquear array/log mesmo em failover; permitir reenvio explícito de ativação; ampliar `recurso_id` auditado para UUID sem destruir chaves numéricas existentes.

Homologação PostgreSQL 16 descartável: quatro rodadas de aprovação/rejeição com dois processos concorrentes; uma decisão, uma matrícula e jobs individuais não processados. Notas em recuperação e consulta isolada aprovadas. Backup validado por pg_restore, restaurado e migrado sem mudar contagens de 124 contas, 97 matrículas e zero notas da cópia do piloto. Sem gravações de teste no banco ativo.

Limitação externa: piloto permanece com MAIL_MAILER=array e sem credenciais SMTP de entrega. Os fluxos de acesso estão implementados/testados, mas envio real aguarda transporte configurado; não declarar e-mails entregues. Não há serviço pago obrigatório.
