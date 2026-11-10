# Estrutura acadêmica flexível — CETSADP

> Implementação em branch de integração: **depende da PR #134**, que depende da PR #133. Não é procedimento de deploy automático.

## O que foi alterado

- **Períodos letivos:** vários ativos simultaneamente; uma turma pode se vincular a diversos períodos. A restrição no banco e a validação de exclusividade foram removidas.
- **Turmas:** passam a ser ofertas de curso de longa duração (inclusive 3+ anos) com várias disciplinas. A coluna antiga `turmas.disciplina_id` permanece apenas como referência de compatibilidade.
- **Disciplinas:** uma disciplina pode conter diversos módulos com `disciplina_modulo`; o `disciplinas.modulo_id` anterior permanece como módulo principal.
- **Aulas:** continuam vinculadas a uma disciplina e a um módulo específico; podem escolher qualquer módulo vinculado àquela disciplina do mesmo curso.
- **Professores:** vários por turma; uma mesma pessoa pode ser designada para turma, disciplina, módulo, aula ou instrumento de avaliação. Cada atribuição possui permissões próprias (`visualizar`, `editar_conteudo`, `gerenciar_atividades`, `corrigir`, `lancar_notas`).
- **Status da turma:** situação acadêmica (`planejada`, `em_andamento`, `concluida`, `cancelada`, `arquivada`), situação das inscrições (`nao_iniciadas`, `abertas`, `encerradas`, `suspensas`) e publicação são independentes. Datas de abertura e encerramento de inscrições são verificadas na validação de novas matrículas da Central.
- **Códigos:** o sistema gera código quando não fornecido no cadastro de turmas com período informado ou no cadastro de disciplinas; códigos existentes não são substituídos.
- **Central de Matrículas e Turmas:** exibe novos status e total de disciplinas/designações; bloqueia vínculos de matrículas em turmas fora do período de inscrições; evita disciplinas simultâneas sobrepostas.

## Como utilizar após aprovação e implantação

1. Crie os **períodos letivos** (pode haver vários ativos).
2. Crie o **curso** e a **turma**, indicando curso, período de referência, datas, situação acadêmica, inscrições, publicação e capacidade.
3. Após salvar a turma, use **Períodos letivos da turma** para acrescentar os anos/semestres seguintes.
4. Use **Disciplinas oferecidas na turma** para adicionar quantas disciplinas forem necessárias, cada qual com ordem e período de oferta.
5. Na edição de cada **disciplina**, selecione diversos módulos do próprio curso. Insira aulas escolhendo o módulo de cada uma.
6. Em **Professores e responsabilidades**, designe vários docentes com escopo e permissões individuais. Para duas aulas distintas, crie duas atribuições do tipo aula.
7. A Central da Secretaria permite administrar matrículas da turma respeitando disponibilidade, datas, vagas e disciplinas.

## Compatibilidade e migração

A migração `2026_11_10_000001_flexibilizar_estrutura_academica.php` cria novas tabelas e faz **cópias aditivas** dos vínculos antigos:
- `turmas.periodo_letivo_id` → `turma_periodos`.
- `turmas.disciplina_id` → `turma_disciplinas`.
- `disciplinas.modulo_id` → `disciplina_modulo`.
- `turmas.professor_id` → `professor_atribuicoes`.

Os campos antigos não são removidos. Matrículas, notas, frequência, progresso, cobrança e histórico não são apagados nem convertidos. A migração **não altera automaticamente** contratos, regras de conclusão, notas ou frequência.

**Antes de qualquer deploy:**
- Faça snapshot/backup consistente do PostgreSQL de produção, banco e arquivos da aplicação.
- Teste a migração em cópia da base e confirme integridade de `matricula_turma`, `matriculas`, `notas`, `frequencias`, `progressos` e `mensalidades`.
- Execute testes: `php artisan test --filter=EstruturaAcademicaFlexivelTest`, `php artisan test --filter=PeriodoLetivoTest`, `php artisan test --filter=CentralMatriculasTest`, `php artisan test --filter=TurmaAcademicaTest`; depois a suíte completa.
- Revise migrações e execute `php artisan migrate --pretend` em staging antes de `php artisan migrate --force` na janela de manutenção aprovada.
- Valide no painel Filament, na Central da Secretaria e no Portal do Professor; confira regras de autorização e atendimento.

## Limites e decisões pendentes

- O portal do aluno e os instrumentos de avaliação da PR #134 **ainda não** executam todas as provas on-line ou lançam notas automaticamente; novas atribuições de correção não criam por si só esse fluxo.
- A permissão de docente para editar **conteúdo de aula compartilhado** altera o conteúdo da disciplina para outras turmas que o reutilizam. Requer modelagem de versões ou aulas por oferta para edições privativas.
- A transferência na Central é **integral** e só aceita turmas com exatamente as mesmas disciplinas. Transferências de uma disciplina isolada em turmas multidisciplinares exigem modelagem adicional de vínculo matrícula–oferta de disciplina.
- O novo campo `turmas.publicada` é informativo para a gestão e método `inscricoesAbertasEm`; é necessário atualizar os catálogos externos específicos que ainda utilizem exclusivamente os filtros legados.
- Para professor vinculado apenas a um instrumento de avaliação, a autorização para correção está modelada; implementar tela de correção e gravação de notas depende da ativação do fluxo de tentativas.
- A reversão da migração remove os relacionamentos N:N novos. **Não executar rollback em produção sem análise e backup**, pois as novas atribuições podem existir somente nesses vínculos.
- Registrar auditoria detalhada de mudanças de atribuições/disciplinas e automatizar abertura/fechamento visual das inscrições em filas/scheduler são melhorias posteriores; método de validação por data já existe no backend.

## Critérios mínimos de aceite

- [ ] Criar dois períodos letivos ativos e associar pelo menos três à mesma turma longa.
- [ ] Adicionar duas ou mais disciplinas na turma e dois módulos na mesma disciplina.
- [ ] Designar dois professores para a mesma turma; restringir um a uma aula e outro a uma avaliação.
- [ ] Rejeitar vínculos entre cursos incompatíveis e acessos de professores fora do escopo.
- [ ] Inscrições abertas em turma planejada, fechadas em turma em andamento e publicação independente.
- [ ] Preservar códigos, matrículas e históricos de produção na migração.
- [ ] CI e testes de integração, banco PostgreSQL e validação funcional no navegador aprovados.

**Estado:** código em revisão (não instalado na VPS).
