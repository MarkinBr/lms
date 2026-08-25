# Issue #57 — Decisões de arquitetura

## 1. Base e isolamento

- Implementação feita em worktree limpo, branch `codex/57-gestao-academica-premium`, a partir de `main @ 386254f`.
- O hash foi conferido com `origin/main` antes do trabalho.
- O diretório principal do usuário, que contém alterações de outras Issues, não foi modificado.
- Nenhum arquivo dos fluxos de login administrativo (#54) ou login do aluno (#56) foi incorporado.

## 2. Central acadêmica como única entrada do menu

A rota `/admin/gestao-academica` é uma `Filament\Pages\Page` acessível somente a administrador ativo. Ela reúne:

- indicadores reais;
- atalhos operacionais;
- pendências estruturais;
- árvore curricular do curso selecionado;
- links para os Resources administrativos.

Os Resources de Curso, Módulo, Disciplina, Período Letivo, Turma, Matrícula e Aula ao Vivo mantêm suas rotas, mas não aparecem separadamente no menu. Isso elimina entradas repetidas sem retirar funções.

## 3. Hierarquia canônica

O fluxo administrativo passa a ser:

`Curso → Módulo → Disciplina → Aula`

- Módulo gerencia disciplinas.
- Disciplina gerencia aulas.
- Uma aula nova exige `disciplina_id`.
- O servidor deriva `aulas.modulo_id` da disciplina para manter compatibilidade com o portal legado.
- Disciplina valida que o módulo pertence ao mesmo curso.
- Aula valida a coerência disciplina↔módulo↔curso.
- Módulos e disciplinas com descendentes não podem ser movidos ou excluídos pelo fluxo Eloquent/Filament.
- Aulas antigas sem disciplina continuam visíveis em uma relação somente para saneamento.

## 4. Compatibilidade de banco

A migration `2026_08_24_000001_add_disciplina_id_to_aulas_table.php` adiciona:

- coluna nullable;
- índice;
- chave estrangeira para `disciplinas`;
- `RESTRICT` na exclusão.

Não existe backfill por tentativa de adivinhação. A central mostra as aulas sem disciplina como pendência para revisão humana. No SQLite, a migration preserva exatamente a definição preexistente da view `v_curso_estatisticas` durante a reconstrução da tabela.

## 5. Turmas híbridas

Todos os novos cadastros de turma usam modalidade híbrida:

- formulários de administrador e Secretaria fixam a modalidade;
- o model aplica híbrido como padrão;
- nova turma, ou alteração explícita de modalidade, rejeita valores não híbridos;
- valores históricos não híbridos permanecem legíveis enquanto não forem editados.

Turma continua validando curso, disciplina, período letivo, professor ativo e código único dentro do período.

## 6. Matrículas

O `MatriculaResource` foi alinhado ao schema atual:

- usa `user_id`, e não o relacionamento antigo de aluno;
- aceita somente usuário ativo com nível aluno;
- turma depende do curso selecionado;
- o servidor rejeita turma de outro curso;
- status antigos são normalizados para os valores canônicos;
- número de matrícula, datas e dia de vencimento são validados;
- exclusão e exclusão em massa não são oferecidas na interface, preservando histórico.

## 7. Professor

- O formulário de aula do professor seleciona uma disciplina e deixa o servidor derivar o módulo.
- A criação rápida de aula dentro de Módulo foi removida porque permitia aula sem disciplina.
- A duplicação parcial de curso foi removida porque copiava módulos/aulas sem garantir disciplinas e transação. Uma duplicação completa poderá voltar em Issue própria.

## 8. Desempenho

- Resumo por contagens agregadas.
- Árvore carregada somente para um curso por vez.
- Eager loading com colunas restritas.
- Busca de cursos limitada a 25 resultados.
- Nenhuma coleção Eloquent em propriedade pública do Livewire.
- Teste automatizado limita a quantidade de consultas e protege contra N+1.

## 9. Visual e acessibilidade

- Linguagem visual navy, creme e dourado adaptada do HTML de referência.
- Sem sidebar/topbar duplicadas, dados fictícios, autenticação em JavaScript ou links remotos.
- Manrope e Playfair Display self-hosted, com licenças OFL e registro de procedência.
- CSS isolado em `.ga-canvas`, carregado apenas no painel administrativo.
- Dark mode, foco visível, layout responsivo, skeleton de carregamento e `prefers-reduced-motion`.
- Contrastes principais medidos acima de WCAG AA.

## 10. Segurança

- A central exige usuário `admin` ativo.
- Conteúdo de banco é escapado pelo Blade.
- Não exibe CPF, e-mail, telefone, endereço ou credenciais.
- Ações destrutivas foram retiradas dos pontos em que poderiam apagar histórico.
- Regras de integridade existem no servidor, não apenas nos selects do formulário.

## 11. Limites conhecidos

As regras cruzadas de curso/módulo/disciplina/aula ainda dependem de Eloquent; SQL direto pode contorná-las. As FKs antigas de módulo também ainda usam `CASCADE`/ `SET NULL`. O endurecimento físico do banco, o backfill e o teste real em PostgreSQL estão registrados em `PENDENCIAS-FUTURAS.md`.
