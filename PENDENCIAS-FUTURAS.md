# Issue #57 — Pendências futuras

Estas pendências não bloqueiam a central acadêmica nem o fluxo normal validado nesta Issue. Elas exigem migração de dados, decisão arquitetural ou infraestrutura adicional.

## 1. Saneamento e NOT NULL

1. Revisar `disciplinas.modulo_id IS NULL`.
2. Revisar `aulas.disciplina_id IS NULL` pela central.
3. Vincular manualmente os registros legítimos e tratar órfãos.
4. Tornar os dois campos obrigatórios em migration posterior.

Não fazer backfill pelo nome ou pela posição: associações incorretas prejudicam histórico, frequência e conteúdo do aluno.

## 2. Integridade física no PostgreSQL

Hoje o fluxo Eloquent impede cruzamentos, mas SQL direto ou atualização em lote pode contornar os eventos dos models.

Issue futura recomendada:

- chave/constraint composta para garantir disciplina e módulo do mesmo curso;
- eliminar `aulas.modulo_id` redundante depois da migração completa, ou garantir fisicamente sua igualdade com a disciplina;
- trocar as FKs antigas de `modulos → aulas` e `modulos → disciplinas` para `RESTRICT`;
- usar transação e `lockForUpdate` nos movimentos estruturais;
- ensaiar migration e rollback em PostgreSQL real.

## 3. Convergência matrícula↔turma

`matriculas.turma_id` ainda convive com a pivot `matricula_turma`.

Próxima evolução:

1. definir a pivot como vínculo canônico;
2. migrar os vínculos legados;
3. comparar contagens e histórico;
4. remover a coluna legada somente após auditoria e rollback ensaiado.

## 4. Endurecer campos acadêmicos de Turma

Depois de corrigir as pendências existentes:

- tornar período letivo, disciplina, professor, código e modalidade obrigatórios;
- adicionar constraints de banco para o código único por período;
- normalizar definitivamente modalidades antigas para híbrido;
- decidir se `curso_id` será derivado da disciplina.

## 5. Duplicação segura de curso

A antiga ação foi removida porque copiava uma estrutura incompleta. Se voltar, deve copiar Curso→Módulos→Disciplinas→Aulas dentro de uma única transação, gerar códigos válidos, não copiar matrículas e ter teste de rollback.

## 6. CI e qualidade da base

- Adicionar job PostgreSQL ao CI.
- Corrigir `tests/Feature/Deploy/PatchValidationTest.php`, que mistura SQL PostgreSQL com SQLite e cobra migrations financeiras ausentes.
- Corrigir `tests/Feature/ExampleTest.php`, que acessa a home sem criar/migrar `pagina_inicials`.
- Migrar metadados PHPUnit em comentários para atributos antes do PHPUnit 12.
- Normalizar line endings do repositório em Issue separada; não alterar centenas de arquivos junto desta feature.

## 7. E2E visual

Adicionar Playwright para:

- desktop e mobile;
- dark mode;
- navegação central→Resource→retorno;
- estados vazio, carregando e com pendências;
- foco por teclado e ausência de overflow.
